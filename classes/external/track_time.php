<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_lessontweak\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * External function: accumulate active time a student spends on a lesson page.
 *
 * Each call adds the elapsed delta (seconds) measured client-side to the
 * student's running total for the page and attempt. Writes to the plugin's own
 * table; it does not touch the lesson grade.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class track_time extends external_api {

    /** @var int Largest delta accepted in one ping, guards against bad clocks/throttling. */
    const MAX_DELTA = 120;

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'   => new external_value(PARAM_INT, 'Lesson course module id'),
            'pageid' => new external_value(PARAM_INT, 'Lesson page id'),
            'delta'  => new external_value(PARAM_INT, 'Active seconds elapsed since the last ping'),
        ]);
    }

    /**
     * Add the elapsed seconds to the user's total for this page and attempt.
     *
     * @param int $cmid Lesson course module id
     * @param int $pageid Lesson page id
     * @param int $delta Active seconds since the last ping
     * @return array
     */
    public static function execute(int $cmid, int $pageid, int $delta): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'   => $cmid,
            'pageid' => $pageid,
            'delta'  => $delta,
        ]);

        // Clamp the delta: never negative, never more than one capped interval.
        $delta = max(0, min(self::MAX_DELTA, $params['delta']));

        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'lesson');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/lesson:view', $context);

        $lesson = new \lesson($DB->get_record('lesson', ['id' => $cm->instance], '*', MUST_EXIST));

        $page = $DB->get_record('lesson_pages',
            ['id' => $params['pageid'], 'lessonid' => $lesson->id], '*', MUST_EXIST);

        $attempt = $lesson->count_user_retries($USER->id);
        $now = time();

        $key = [
            'userid'   => $USER->id,
            'lessonid' => $lesson->id,
            'pageid'   => $page->id,
            'attempt'  => $attempt,
        ];

        $total = $delta;
        $existing = $DB->get_record('local_lessontweak_ptime', $key);
        if ($existing) {
            if ($delta > 0) {
                $existing->timespent += $delta;
                $existing->timemodified = $now;
                $DB->update_record('local_lessontweak_ptime', $existing);
            }
            $total = $existing->timespent;
        } else {
            $record = (object) ($key + [
                'timespent'    => $delta,
                'timemodified' => $now,
            ]);
            $DB->insert_record('local_lessontweak_ptime', $record);
        }

        return [
            'status'    => true,
            'timespent' => $total,
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'    => new external_value(PARAM_BOOL, 'True if stored'),
            'timespent' => new external_value(PARAM_INT, 'Total active seconds on this page for this attempt'),
        ]);
    }
}
