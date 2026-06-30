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

/**
 * Upgrade steps for local_lessontweak.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool
 */
function xmldb_local_lessontweak_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026063001) {

        // Define table local_lessontweak_conf to be created.
        $table = new xmldb_table('local_lessontweak_conf');

        // Adding fields to table local_lessontweak_conf.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attempt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('confidence', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_lessontweak_conf.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('lessonid', XMLDB_KEY_FOREIGN, ['lessonid'], 'lesson', ['id']);
        $table->add_key('pageid', XMLDB_KEY_FOREIGN, ['pageid'], 'lesson_pages', ['id']);

        // Adding indexes to table local_lessontweak_conf.
        $table->add_index('userid-lessonid-pageid-attempt', XMLDB_INDEX_UNIQUE,
            ['userid', 'lessonid', 'pageid', 'attempt']);

        // Conditionally launch create table for local_lessontweak_conf.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lessontweak savepoint reached.
        upgrade_plugin_savepoint(true, 2026063001, 'local', 'lessontweak');
    }

    if ($oldversion < 2026063002) {

        // Define table local_lessontweak_lopt to be created.
        $table = new xmldb_table('local_lessontweak_lopt');

        // Adding fields to table local_lessontweak_lopt.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('lessonid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('showconfidence', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_lessontweak_lopt.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('lessonid', XMLDB_KEY_FOREIGN_UNIQUE, ['lessonid'], 'lesson', ['id']);

        // Conditionally launch create table for local_lessontweak_lopt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Lessontweak savepoint reached.
        upgrade_plugin_savepoint(true, 2026063002, 'local', 'lessontweak');
    }

    return true;
}
