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

    $settings->add(new admin_setting_configcheckbox(
        'local_lessontweak/enabletweaks',
        get_string('enabletweaks', 'local_lessontweak'),
        get_string('enabletweaks_desc', 'local_lessontweak'),
        0
    ));

    // Two example tweaks; each is {name, css}. A lesson picks one by name.
    $defaulttweaks = json_encode([
        [
            'name' => 'Focus mode',
            'css'  => '.path-mod-lesson [data-region="blocks-column"], '
                . '.path-mod-lesson .block-region { display: none; } '
                . '.path-mod-lesson #region-main { margin: 0 auto; max-width: 50rem; }',
        ],
        [
            'name' => 'Large readable text',
            'css'  => '.path-mod-lesson .contents, .path-mod-lesson .box.contents '
                . '{ font-size: 1.25rem; line-height: 1.7; }',
        ],
        [
            'name' => 'Kids: big and colourful',
            'css'  => '.path-mod-lesson #region-main { '
                . 'font-family: "Comic Sans MS", "Comic Sans", "Chalkboard", cursive, sans-serif; '
                . 'background: #ffe3ec; }'
                . '.path-mod-lesson #region-main .contents, .path-mod-lesson #region-main .box.contents { '
                . 'font-size: 1.8rem; line-height: 1.9; letter-spacing: 0.02em; }'
                . '.path-mod-lesson #region-main h1, .path-mod-lesson #region-main h2, '
                . '.path-mod-lesson #region-main h3 { font-size: 2.4rem; color: #d6336c; }'
                . '.path-mod-lesson #region-main .box.contents { '
                . 'background: #fff9db; border: 4px dashed #ffd43b; border-radius: 1rem; padding: 1.5rem; }'
                . '.path-mod-lesson #region-main .btn, '
                . '.path-mod-lesson #region-main button[type="submit"] { '
                . 'font-size: 1.4rem; padding: 0.6rem 1.4rem; border-radius: 2rem; '
                . 'background: #40c057; border-color: #2f9e44; color: #fff; }',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $settings->add(new \local_lessontweak\admin\setting_tweaks(
        'local_lessontweak/tweaks',
        get_string('tweaks', 'local_lessontweak'),
        get_string('tweaks_desc', 'local_lessontweak'),
        $defaulttweaks,
        PARAM_RAW,
        60,
        12
    ));
}
