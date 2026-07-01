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
 * Add the Lesson tweaks report links to a lesson's settings navigation.
 *
 * The confidence report appears when the confidence slider is enabled; the time
 * report appears when page time tracking is enabled. Each is independent.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_lessontweak_extend_settings_navigation(settings_navigation $settingsnav, context $context): void {
    $confidence = get_config('local_lessontweak', 'enableconfidence');
    $timetracking = get_config('local_lessontweak', 'enabletimetracking');
    if (!$confidence && !$timetracking) {
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

    if ($confidence) {
        $node->add(
            get_string('confidencereport', 'local_lessontweak'),
            new moodle_url('/local/lessontweak/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'lessontweakconfidence',
            new pix_icon('i/report', '')
        );
    }

    if ($timetracking) {
        $node->add(
            get_string('timereport', 'local_lessontweak'),
            new moodle_url('/local/lessontweak/timereport.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'lessontweaktime',
            new pix_icon('i/report', '')
        );
    }
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

/** Timer disabled for the lesson. */
define('LOCAL_LESSONTWEAK_TIMER_NONE', 0);
/** Count-up elapsed timer. */
define('LOCAL_LESSONTWEAK_TIMER_ELAPSED', 1);
/** Countdown from a set number of minutes. */
define('LOCAL_LESSONTWEAK_TIMER_COUNTDOWN', 2);

/**
 * The per-lesson timer configuration.
 *
 * Defaults to elapsed mode when no per-lesson row exists yet.
 *
 * @param int $lessonid
 * @return array [int mode, int minutes, bool bar]
 */
function local_lessontweak_timer_config(int $lessonid): array {
    global $DB;
    $row = $DB->get_record('local_lessontweak_lopt', ['lessonid' => $lessonid],
        'timermode, timerminutes, timerbar');
    if (!$row) {
        return [LOCAL_LESSONTWEAK_TIMER_ELAPSED, 0, false];
    }
    return [(int) $row->timermode, (int) $row->timerminutes, (bool) $row->timerbar];
}

/**
 * The clock sizes offered for the timer badge, keyed by rem value.
 *
 * @return array [string rem => string label]
 */
function local_lessontweak_timer_sizes(): array {
    return [
        '1'   => get_string('timersize_small', 'local_lessontweak'),
        '1.5' => get_string('timersize_medium', 'local_lessontweak'),
        '2'   => get_string('timersize_large', 'local_lessontweak'),
        '2.5' => get_string('timersize_xlarge', 'local_lessontweak'),
    ];
}

/**
 * The default clock size (rem) from site configuration.
 *
 * @return string
 */
function local_lessontweak_default_timer_size(): string {
    $value = get_config('local_lessontweak', 'timersize');
    return $value !== false && $value !== '' ? $value : '1.5';
}

/**
 * The clock size (rem) to use for a lesson: its own override, else the site default.
 *
 * @param int $lessonid
 * @return string
 */
function local_lessontweak_timer_size(int $lessonid): string {
    global $DB;
    $value = $DB->get_field('local_lessontweak_lopt', 'timersize', ['lessonid' => $lessonid]);
    if ($value === false || $value === null || $value === '') {
        return local_lessontweak_default_timer_size();
    }
    return $value;
}

/**
 * Add per-lesson Lesson tweaks checkboxes to the lesson settings form.
 *
 * Uses the core coursemodule form callback, so no lesson core file is modified.
 * Each checkbox only appears when its site-wide feature is enabled.
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 */
function local_lessontweak_coursemodule_standard_elements($formwrapper, $mform): void {
    global $DB;

    $confidence = get_config('local_lessontweak', 'enableconfidence');
    $elapsed = get_config('local_lessontweak', 'enableelapsedtimer');
    if (!$confidence && !$elapsed) {
        return;
    }
    $current = $formwrapper->get_current();
    if (empty($current->modulename) || $current->modulename !== 'lesson') {
        return;
    }

    // get_instance() returns the lesson instance id (0 for a new lesson).
    $lessonid = (int) $formwrapper->get_instance();

    $mform->addElement('header', 'lessontweakheader',
        get_string('pluginname', 'local_lessontweak'));

    if ($confidence) {
        $mform->addElement('advcheckbox', 'lessontweakshowconfidence',
            get_string('showconfidence', 'local_lessontweak'));
        $mform->addHelpButton('lessontweakshowconfidence', 'showconfidence', 'local_lessontweak');
        $mform->setType('lessontweakshowconfidence', PARAM_BOOL);
        $mform->setDefault('lessontweakshowconfidence',
            $lessonid ? (int) local_lessontweak_show_confidence($lessonid) : 1);
    }

    if ($elapsed) {
        $modes = [
            LOCAL_LESSONTWEAK_TIMER_NONE      => get_string('timermode_none', 'local_lessontweak'),
            LOCAL_LESSONTWEAK_TIMER_ELAPSED   => get_string('timermode_elapsed', 'local_lessontweak'),
            LOCAL_LESSONTWEAK_TIMER_COUNTDOWN => get_string('timermode_countdown', 'local_lessontweak'),
        ];
        $mform->addElement('select', 'lessontweaktimermode',
            get_string('timermode', 'local_lessontweak'), $modes);
        $mform->addHelpButton('lessontweaktimermode', 'timermode', 'local_lessontweak');

        $mform->addElement('text', 'lessontweaktimerminutes',
            get_string('timerminutes', 'local_lessontweak'), ['size' => 5]);
        $mform->setType('lessontweaktimerminutes', PARAM_INT);
        $mform->hideIf('lessontweaktimerminutes', 'lessontweaktimermode', 'neq',
            LOCAL_LESSONTWEAK_TIMER_COUNTDOWN);

        // Depleting progress bar, countdown only.
        $mform->addElement('advcheckbox', 'lessontweaktimerbar',
            get_string('timerbar', 'local_lessontweak'));
        $mform->addHelpButton('lessontweaktimerbar', 'timerbar', 'local_lessontweak');
        $mform->setType('lessontweaktimerbar', PARAM_BOOL);
        $mform->hideIf('lessontweaktimerbar', 'lessontweaktimermode', 'neq',
            LOCAL_LESSONTWEAK_TIMER_COUNTDOWN);

        // Clock size: an empty first option falls back to the site default.
        $sizeoptions = ['' => get_string('timersize_default', 'local_lessontweak')]
            + local_lessontweak_timer_sizes();
        $mform->addElement('select', 'lessontweaktimersize',
            get_string('timersize', 'local_lessontweak'), $sizeoptions);
        $mform->addHelpButton('lessontweaktimersize', 'timersize', 'local_lessontweak');
        $mform->hideIf('lessontweaktimersize', 'lessontweaktimermode', 'eq',
            LOCAL_LESSONTWEAK_TIMER_NONE);

        [$defmode, $defmin, $defbar] = $lessonid
            ? local_lessontweak_timer_config($lessonid)
            : [LOCAL_LESSONTWEAK_TIMER_ELAPSED, 0, false];
        $defsize = $lessonid
            ? (string) ($DB->get_field('local_lessontweak_lopt', 'timersize', ['lessonid' => $lessonid]) ?: '')
            : '';
        $mform->setDefault('lessontweaktimermode', $defmode);
        $mform->setDefault('lessontweaktimerminutes', $defmin);
        $mform->setDefault('lessontweaktimerbar', (int) $defbar);
        $mform->setDefault('lessontweaktimersize', $defsize);
    }
}

/**
 * Persist the per-lesson Lesson tweaks checkboxes when a lesson is saved.
 *
 * Only columns whose checkbox was present (feature enabled) are written, so a
 * disabled feature does not clobber a stored value.
 *
 * @param stdClass $data submitted module data (includes our elements)
 * @param stdClass $course
 * @return stdClass
 */
function local_lessontweak_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    if (empty($data->modulename) || $data->modulename !== 'lesson' || empty($data->instance)) {
        return $data;
    }

    $fields = [];
    if (property_exists($data, 'lessontweakshowconfidence')) {
        $fields['showconfidence'] = !empty($data->lessontweakshowconfidence) ? 1 : 0;
    }
    if (property_exists($data, 'lessontweaktimermode')) {
        $fields['timermode'] = (int) $data->lessontweaktimermode;
        $fields['timerminutes'] = max(0, (int) ($data->lessontweaktimerminutes ?? 0));
        $fields['timerbar'] = !empty($data->lessontweaktimerbar) ? 1 : 0;
    }
    if (property_exists($data, 'lessontweaktimersize')) {
        // Empty means "use the site default".
        $sizes = local_lessontweak_timer_sizes();
        $size = (string) $data->lessontweaktimersize;
        $fields['timersize'] = isset($sizes[$size]) ? $size : null;
    }
    if (!$fields) {
        return $data;
    }

    $existing = $DB->get_record('local_lessontweak_lopt', ['lessonid' => $data->instance]);
    if ($existing) {
        foreach ($fields as $name => $value) {
            $existing->$name = $value;
        }
        $existing->timemodified = time();
        $DB->update_record('local_lessontweak_lopt', $existing);
    } else {
        $DB->insert_record('local_lessontweak_lopt', (object) ($fields + [
            'lessonid'     => $data->instance,
            'timemodified' => time(),
        ]));
    }

    return $data;
}
