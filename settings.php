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
 * Settings for local_lessontweak.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lessontweak', get_string('pluginname', 'local_lessontweak'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_lessontweak/enabledragreorder',
        get_string('enabledragreorder', 'local_lessontweak'),
        get_string('enabledragreorder_desc', 'local_lessontweak'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_lessontweak/enableconfidence',
        get_string('enableconfidence', 'local_lessontweak'),
        get_string('enableconfidence_desc', 'local_lessontweak'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_lessontweak/enabletimetracking',
        get_string('enabletimetracking', 'local_lessontweak'),
        get_string('enabletimetracking_desc', 'local_lessontweak'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_lessontweak/enableelapsedtimer',
        get_string('enableelapsedtimer', 'local_lessontweak'),
        get_string('enableelapsedtimer_desc', 'local_lessontweak'),
        0
    ));

    require_once($CFG->dirroot . '/local/lessontweak/lib.php');
    $settings->add(new admin_setting_configselect(
        'local_lessontweak/timersize',
        get_string('timersizedefault', 'local_lessontweak'),
        get_string('timersizedefault_desc', 'local_lessontweak'),
        '1.5',
        local_lessontweak_timer_sizes()
    ));
}
