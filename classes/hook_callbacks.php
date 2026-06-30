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

namespace local_lessontweak;

/**
 * Hook callbacks.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Inject the drag-to-reorder behaviour on the lesson editing page.
     *
     * Runs on every page; cheaply bails unless this is mod/lesson/edit.php and the
     * user can edit the lesson. No core modification — the JS drives the lesson
     * module's existing move action.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(
        \core\hook\output\before_footer_html_generation $hook
    ): void {
        global $PAGE;

        self::maybe_add_dragreorder($PAGE);
        self::maybe_add_confidence_slider($PAGE);
        self::maybe_add_time_tracker($PAGE);
        self::maybe_add_elapsed_timer($PAGE);
    }

    /**
     * Show a count-up "time since you started this attempt" badge (view.php).
     *
     * Reads the attempt start from the lesson's own {lesson_timer} table and
     * passes the already-elapsed seconds to the AMD module. Display only.
     *
     * @param \moodle_page $PAGE
     */
    protected static function maybe_add_elapsed_timer(\moodle_page $PAGE): void {
        global $CFG, $DB, $USER;

        if (!get_config('local_lessontweak', 'enableelapsedtimer')) {
            return;
        }
        if ($PAGE->pagetype !== 'mod-lesson-view') {
            return;
        }
        $context = $PAGE->context;
        if (empty($context) || $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!has_capability('mod/lesson:view', $context)) {
            return;
        }

        $cm = get_coursemodule_from_id('lesson', $context->instanceid);
        if (!$cm) {
            return;
        }

        require_once($CFG->dirroot . '/local/lessontweak/lib.php');
        [$mode, $minutes] = local_lessontweak_timer_config((int) $cm->instance);
        if ($mode === LOCAL_LESSONTWEAK_TIMER_NONE) {
            return;
        }

        // Latest attempt start for this user (lesson records it for every attempt).
        $starttime = $DB->get_field_sql(
            "SELECT MAX(starttime) FROM {lesson_timer} WHERE lessonid = :lessonid AND userid = :userid",
            ['lessonid' => $cm->instance, 'userid' => $USER->id]
        );
        if (empty($starttime)) {
            // No timer row (e.g. a teacher previewing): nothing to count from.
            return;
        }

        $elapsed = max(0, time() - (int) $starttime);

        if ($mode === LOCAL_LESSONTWEAK_TIMER_COUNTDOWN) {
            if ($minutes <= 0) {
                return;
            }
            $seconds = max(0, ($minutes * 60) - $elapsed);
            $jsmode = 'countdown';
        } else {
            $seconds = $elapsed;
            $jsmode = 'elapsed';
        }

        $PAGE->requires->js_call_amd('local_lessontweak/elapsedtimer', 'init', [[
            'mode'    => $jsmode,
            'seconds' => $seconds,
            'size'    => local_lessontweak_timer_size((int) $cm->instance),
        ]]);
    }

    /**
     * Add the active-time heartbeat on lesson question/content pages (view.php).
     *
     * Time is stored through the plugin's own web service into its own table —
     * it does not affect the lesson grade.
     *
     * @param \moodle_page $PAGE
     */
    protected static function maybe_add_time_tracker(\moodle_page $PAGE): void {
        if (!get_config('local_lessontweak', 'enabletimetracking')) {
            return;
        }
        if ($PAGE->pagetype !== 'mod-lesson-view') {
            return;
        }
        $context = $PAGE->context;
        if (empty($context) || $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!has_capability('mod/lesson:view', $context)) {
            return;
        }

        $PAGE->requires->js_call_amd('local_lessontweak/tracker', 'init', [
            (int) $context->instanceid,
        ]);
    }

    /**
     * Add drag-to-reorder on the lesson editing page (edit.php).
     *
     * $PAGE->cm is not reliably populated at footer time, so detect via the
     * page type and the module context instead. No core modification — the JS
     * drives the lesson module's existing move action.
     *
     * @param \moodle_page $PAGE
     */
    protected static function maybe_add_dragreorder(\moodle_page $PAGE): void {
        if (!get_config('local_lessontweak', 'enabledragreorder')) {
            return;
        }
        if ($PAGE->pagetype !== 'mod-lesson-edit') {
            return;
        }
        $context = $PAGE->context;
        if (empty($context) || $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!has_capability('mod/lesson:edit', $context)) {
            return;
        }

        // js_call_amd spreads this list positionally into init(cmid, sesskey).
        $PAGE->requires->js_call_amd('local_lessontweak/dragreorder', 'init', [
            (int) $context->instanceid,
            sesskey(),
        ]);
    }

    /**
     * Add the confidence slider on lesson question pages (view.php).
     *
     * The slider value is stored through the plugin's own web service into its
     * own table — it does not affect the lesson grade.
     *
     * @param \moodle_page $PAGE
     */
    protected static function maybe_add_confidence_slider(\moodle_page $PAGE): void {
        global $CFG;

        if (!get_config('local_lessontweak', 'enableconfidence')) {
            return;
        }
        if ($PAGE->pagetype !== 'mod-lesson-view') {
            return;
        }
        $context = $PAGE->context;
        if (empty($context) || $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!has_capability('mod/lesson:view', $context)) {
            return;
        }

        // Respect the per-lesson "Show confidence slider" setting.
        require_once($CFG->dirroot . '/local/lessontweak/lib.php');
        $cm = get_coursemodule_from_id('lesson', $context->instanceid);
        if (!$cm || !local_lessontweak_show_confidence((int) $cm->instance)) {
            return;
        }

        $PAGE->requires->js_call_amd('local_lessontweak/confidence', 'init', [
            (int) $context->instanceid,
        ]);
    }
}
