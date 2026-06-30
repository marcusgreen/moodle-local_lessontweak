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

namespace local_lessontweak\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_lessontweak.
 *
 * Stores self-reported confidence values keyed by lesson module context.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the data this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_lessontweak_conf', [
            'userid'       => 'privacy:metadata:local_lessontweak_conf:userid',
            'lessonid'     => 'privacy:metadata:local_lessontweak_conf:lessonid',
            'pageid'       => 'privacy:metadata:local_lessontweak_conf:pageid',
            'attempt'      => 'privacy:metadata:local_lessontweak_conf:attempt',
            'confidence'   => 'privacy:metadata:local_lessontweak_conf:confidence',
            'timecreated'  => 'privacy:metadata:local_lessontweak_conf:timecreated',
            'timemodified' => 'privacy:metadata:local_lessontweak_conf:timemodified',
        ], 'privacy:metadata:local_lessontweak_conf');

        $collection->add_database_table('local_lessontweak_ptime', [
            'userid'       => 'privacy:metadata:local_lessontweak_ptime:userid',
            'lessonid'     => 'privacy:metadata:local_lessontweak_ptime:lessonid',
            'pageid'       => 'privacy:metadata:local_lessontweak_ptime:pageid',
            'attempt'      => 'privacy:metadata:local_lessontweak_ptime:attempt',
            'timespent'    => 'privacy:metadata:local_lessontweak_ptime:timespent',
            'timemodified' => 'privacy:metadata:local_lessontweak_ptime:timemodified',
        ], 'privacy:metadata:local_lessontweak_ptime');

        return $collection;
    }

    /**
     * Get the list of contexts that contain data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        foreach (['local_lessontweak_conf', 'local_lessontweak_ptime'] as $tablename) {
            $sql = "SELECT ctx.id
                      FROM {{$tablename}} d
                      JOIN {lesson} l ON l.id = d.lessonid
                      JOIN {course_modules} cm ON cm.instance = l.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                      JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :modlevel
                     WHERE d.userid = :userid";
            $contextlist->add_from_sql($sql, [
                'modname'  => 'lesson',
                'modlevel' => CONTEXT_MODULE,
                'userid'   => $userid,
            ]);
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        foreach (['local_lessontweak_conf', 'local_lessontweak_ptime'] as $tablename) {
            $sql = "SELECT d.userid
                      FROM {{$tablename}} d
                      JOIN {lesson} l ON l.id = d.lessonid
                      JOIN {course_modules} cm ON cm.instance = l.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                     WHERE cm.id = :cmid";
            $userlist->add_from_sql('userid', $sql, [
                'modname' => 'lesson',
                'cmid'    => $context->instanceid,
            ]);
        }
    }

    /**
     * Export all confidence data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('lesson', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $export = [];

            $confrecords = $DB->get_records('local_lessontweak_conf', [
                'userid'   => $user->id,
                'lessonid' => $cm->instance,
            ]);
            $confidence = [];
            foreach ($confrecords as $record) {
                $confidence[] = [
                    'pageid'       => $record->pageid,
                    'attempt'      => $record->attempt,
                    'confidence'   => $record->confidence,
                    'timecreated'  => \core_privacy\local\request\transform::datetime($record->timecreated),
                    'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                ];
            }
            if ($confidence) {
                $export['confidence'] = $confidence;
            }

            $timerecords = $DB->get_records('local_lessontweak_ptime', [
                'userid'   => $user->id,
                'lessonid' => $cm->instance,
            ]);
            $timespent = [];
            foreach ($timerecords as $record) {
                $timespent[] = [
                    'pageid'       => $record->pageid,
                    'attempt'      => $record->attempt,
                    'timespent'    => $record->timespent,
                    'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                ];
            }
            if ($timespent) {
                $export['timespent'] = $timespent;
            }

            if ($export) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_lessontweak')],
                    (object) $export
                );
            }
        }
    }

    /**
     * Delete all confidence data in the given context (for all users).
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('lesson', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('local_lessontweak_conf', ['lessonid' => $cm->instance]);
        $DB->delete_records('local_lessontweak_ptime', ['lessonid' => $cm->instance]);
    }

    /**
     * Delete all confidence data for one user across the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('lesson', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('local_lessontweak_conf', [
                'userid'   => $userid,
                'lessonid' => $cm->instance,
            ]);
            $DB->delete_records('local_lessontweak_ptime', [
                'userid'   => $userid,
                'lessonid' => $cm->instance,
            ]);
        }
    }

    /**
     * Delete data for multiple users in a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('lesson', $context->instanceid);
        if (!$cm) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ['lessonid' => $cm->instance]);
        $DB->delete_records_select('local_lessontweak_conf',
            "lessonid = :lessonid AND userid $insql", $params);
        $DB->delete_records_select('local_lessontweak_ptime',
            "lessonid = :lessonid AND userid $insql", $params);
    }
}
