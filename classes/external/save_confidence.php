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
 * External function: store a student's confidence for a lesson question page.
 *
 * Writes to the plugin's own table; it does not touch the lesson grade.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_confidence extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid'       => new external_value(PARAM_INT, 'Lesson course module id'),
            'pageid'     => new external_value(PARAM_INT, 'Lesson page id'),
            'confidence' => new external_value(PARAM_INT, 'Confidence percentage 0-100'),
        ]);
    }

    /**
     * Store the confidence value for the current user.
     *
     * @param int $cmid Lesson course module id
     * @param int $pageid Lesson page id
     * @param int $confidence Confidence percentage 0-100
     * @return array
     */
    public static function execute(int $cmid, int $pageid, int $confidence): array {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid'       => $cmid,
            'pageid'     => $pageid,
            'confidence' => $confidence,
        ]);

        // Clamp to the valid 0-100 range.
        $value = max(0, min(100, $params['confidence']));

        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'lesson');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/lesson:view', $context);

        $lesson = new \lesson($DB->get_record('lesson', ['id' => $cm->instance], '*', MUST_EXIST));

        // The page must belong to this lesson.
        $page = $DB->get_record('lesson_pages',
            ['id' => $params['pageid'], 'lessonid' => $lesson->id], '*', MUST_EXIST);

        // Tie the confidence to the user's current in-progress attempt (retry number).
        $attempt = $lesson->count_user_retries($USER->id);

        $now = time();
        $existing = $DB->get_record('local_lessontweak_conf', [
            'userid'   => $USER->id,
            'lessonid' => $lesson->id,
            'pageid'   => $page->id,
            'attempt'  => $attempt,
        ]);

        if ($existing) {
            $existing->confidence = $value;
            $existing->timemodified = $now;
            $DB->update_record('local_lessontweak_conf', $existing);
        } else {
            $DB->insert_record('local_lessontweak_conf', (object) [
                'userid'       => $USER->id,
                'lessonid'     => $lesson->id,
                'pageid'       => $page->id,
                'attempt'      => $attempt,
                'confidence'   => $value,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        return [
            'status'     => true,
            'confidence' => $value,
            'attempt'    => $attempt,
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status'     => new external_value(PARAM_BOOL, 'True if stored'),
            'confidence' => new external_value(PARAM_INT, 'The stored confidence value'),
            'attempt'    => new external_value(PARAM_INT, 'The attempt number it was stored against'),
        ]);
    }
}
