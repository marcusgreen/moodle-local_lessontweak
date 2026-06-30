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

        if (!get_config('local_lessontweak', 'enabledragreorder')) {
            return;
        }
        // The lesson editor page. $PAGE->cm is not reliably populated at footer
        // time, so detect via the page type and the module context instead.
        if ($PAGE->pagetype !== 'mod-lesson-edit') {
            return;
        }
        $context = $PAGE->context;
        if (empty($context) || $context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cmid = $context->instanceid;
        if (!has_capability('mod/lesson:edit', $context)) {
            return;
        }

        // js_call_amd spreads this list positionally into init(cmid, sesskey).
        $PAGE->requires->js_call_amd('local_lessontweak/dragreorder', 'init', [
            (int) $cmid,
            sesskey(),
        ]);
    }
}
