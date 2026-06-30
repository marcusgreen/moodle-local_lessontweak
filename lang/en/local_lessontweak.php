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
 * Strings for local_lessontweak.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Lesson tweaks';
$string['enabledragreorder'] = 'Enable drag-to-reorder pages';
$string['enabledragreorder_desc'] = 'Adds drag handles to the collapsed lesson editor so pages can be reordered by dragging. Uses the lesson module\'s own move action, so no core changes are required.';
$string['draghandle'] = 'Drag to reorder this page';
$string['reordering'] = 'Reordering…';

$string['enableconfidence'] = 'Enable confidence slider';
$string['enableconfidence_desc'] = 'Adds a 0–100% confidence slider to lesson question pages. The value is stored in this plugin\'s own table when the student submits an answer; it does not affect the lesson grade.';
$string['confidenceslider'] = 'Confidence slider';
$string['showconfidence'] = 'Show confidence slider to students';
$string['showconfidence_help'] = 'When ticked, students see a 0–100% confidence slider on this lesson\'s question pages. Their answer is unaffected; the value is stored only in the Lesson tweaks report. Requires the site-wide confidence slider setting to be enabled.';
$string['confidenceprompt'] = 'How confident are you in this answer?';
$string['confidencelow'] = 'Not at all';
$string['confidencehigh'] = 'Completely';
$string['confidencereport'] = 'Confidence report';
$string['confidencestudent'] = 'Student';
$string['confidencepage'] = 'Page';
$string['confidenceattempt'] = 'Attempt';
$string['confidencevalue'] = 'Confidence';
$string['confidencenodata'] = 'No students have reported a confidence value for this lesson yet.';
$string['confidencepagegone'] = 'Deleted page (id {$a})';

$string['privacy:metadata:local_lessontweak_conf'] = 'Self-reported confidence values for lesson question pages.';
$string['privacy:metadata:local_lessontweak_conf:userid'] = 'The user who reported the confidence.';
$string['privacy:metadata:local_lessontweak_conf:lessonid'] = 'The lesson the confidence relates to.';
$string['privacy:metadata:local_lessontweak_conf:pageid'] = 'The lesson page the confidence relates to.';
$string['privacy:metadata:local_lessontweak_conf:attempt'] = 'The lesson attempt the confidence was reported for.';
$string['privacy:metadata:local_lessontweak_conf:confidence'] = 'The confidence percentage (0-100).';
$string['privacy:metadata:local_lessontweak_conf:timecreated'] = 'When the confidence was first stored.';
$string['privacy:metadata:local_lessontweak_conf:timemodified'] = 'When the confidence was last updated.';
$string['privacy:metadata'] = 'Information about how the Lesson tweaks plugin stores personal data.';
