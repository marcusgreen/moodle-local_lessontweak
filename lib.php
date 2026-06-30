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
 * Library functions for local_lessontweak.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a "Confidence report" link to a lesson's settings navigation.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_lessontweak_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    if (!get_config('local_lessontweak', 'enableconfidence')) {
        return;
    }
    if (!$context instanceof context_module) {
        return;
    }
    if (!has_capability('mod/lesson:viewreports', $context)) {
        return;
    }

    $cm = get_coursemodule_from_id('lesson', $context->instanceid);
    if (!$cm) {
        return;
    }

    // Attach to the activity's settings node when present, else the nav root.
    $node = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING)
        ?: $settingsnav;

    $url = new moodle_url('/local/lessontweak/report.php', ['id' => $cm->id]);
    $node->add(
        get_string('confidencereport', 'local_lessontweak'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'lessontweakconfidence',
        new pix_icon('i/report', '')
    );
}

/**
 * Whether the confidence slider should show on a given lesson.
 *
 * Defaults to on when no per-lesson row exists yet.
 *
 * @param int $lessonid
 * @return bool
 */
function local_lessontweak_show_confidence(int $lessonid): bool {
    global $DB;
    $value = $DB->get_field('local_lessontweak_lopt', 'showconfidence', ['lessonid' => $lessonid]);
    // false === no row yet: default to showing.
    return $value === false ? true : (bool) $value;
}

/**
 * Add the "Show confidence slider" checkbox to the lesson settings form.
 *
 * Uses the core coursemodule form callback, so no lesson core file is modified.
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 */
function local_lessontweak_coursemodule_standard_elements($formwrapper, $mform): void {
    if (!get_config('local_lessontweak', 'enableconfidence')) {
        return;
    }
    $current = $formwrapper->get_current();
    if (empty($current->modulename) || $current->modulename !== 'lesson') {
        return;
    }

    $mform->addElement('header', 'lessontweakheader',
        get_string('confidenceslider', 'local_lessontweak'));

    $mform->addElement('advcheckbox', 'lessontweakshowconfidence',
        get_string('showconfidence', 'local_lessontweak'));
    $mform->addHelpButton('lessontweakshowconfidence', 'showconfidence', 'local_lessontweak');
    $mform->setType('lessontweakshowconfidence', PARAM_BOOL);

    // Default to the stored value, or on for a new lesson.
    $instance = $formwrapper->get_instance();
    $lessonid = !empty($instance->id) ? (int) $instance->id : 0;
    $mform->setDefault('lessontweakshowconfidence',
        $lessonid ? (int) local_lessontweak_show_confidence($lessonid) : 1);
}

/**
 * Persist the "Show confidence slider" checkbox when a lesson is saved.
 *
 * @param stdClass $data submitted module data (includes our element)
 * @param stdClass $course
 * @return stdClass
 */
function local_lessontweak_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    if (empty($data->modulename) || $data->modulename !== 'lesson' || empty($data->instance)) {
        return $data;
    }

    $show = !empty($data->lessontweakshowconfidence) ? 1 : 0;
    $existing = $DB->get_record('local_lessontweak_lopt', ['lessonid' => $data->instance]);
    if ($existing) {
        $existing->showconfidence = $show;
        $existing->timemodified = time();
        $DB->update_record('local_lessontweak_lopt', $existing);
    } else {
        $DB->insert_record('local_lessontweak_lopt', (object) [
            'lessonid'       => $data->instance,
            'showconfidence' => $show,
            'timemodified'   => time(),
        ]);
    }

    return $data;
}
