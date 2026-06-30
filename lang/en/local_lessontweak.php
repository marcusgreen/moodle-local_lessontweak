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
$string['enabletimetracking'] = 'Enable page time tracking';
$string['enabletimetracking_desc'] = 'Records how long students actively view each lesson page (a heartbeat that only counts visible time). Stored in this plugin\'s own table and shown in the confidence report; it does not affect the lesson grade.';
$string['enableelapsedtimer'] = 'Show a timer to students';
$string['enableelapsedtimer_desc'] = 'Allows a per-lesson timer badge (elapsed count-up or countdown) on lesson pages. The mode is chosen in each lesson\'s settings. Display only; it does not affect the lesson grade or time limit.';
$string['elapsedlabel'] = 'Time on this attempt:';
$string['countdownlabel'] = 'Time remaining:';
$string['timermode'] = 'Timer display';
$string['timermode_help'] = 'Choose what timer students see on this lesson\'s pages. Elapsed counts up from when they started the attempt; Countdown counts down from the number of minutes set below. The start time comes from the lesson\'s own attempt timer. Requires the site-wide timer setting to be enabled.';
$string['timermode_none'] = 'No timer';
$string['timermode_elapsed'] = 'Elapsed (count up)';
$string['timermode_countdown'] = 'Countdown';
$string['timerminutes'] = 'Countdown minutes';
$string['timersize'] = 'Clock size';
$string['timersize_help'] = 'How large the timer clock appears on this lesson\'s pages. Choose "Site default" to follow the site-wide setting.';
$string['timersize_default'] = 'Site default';
$string['timersize_small'] = 'Small';
$string['timersize_medium'] = 'Medium';
$string['timersize_large'] = 'Large';
$string['timersize_xlarge'] = 'Extra large';
$string['timersizedefault'] = 'Default timer clock size';
$string['timersizedefault_desc'] = 'The clock size used when a lesson does not set its own. Lessons can override this in their settings.';
$string['confidencereport'] = 'Confidence report';
$string['confidencestudent'] = 'Student';
$string['confidencetimespent'] = 'Time spent';
$string['confidenceupdated'] = 'Updated';
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
$string['privacy:metadata:local_lessontweak_ptime'] = 'Active time spent on lesson question and content pages.';
$string['privacy:metadata:local_lessontweak_ptime:userid'] = 'The user the time was recorded for.';
$string['privacy:metadata:local_lessontweak_ptime:lessonid'] = 'The lesson the time relates to.';
$string['privacy:metadata:local_lessontweak_ptime:pageid'] = 'The lesson page the time relates to.';
$string['privacy:metadata:local_lessontweak_ptime:attempt'] = 'The lesson attempt the time was recorded for.';
$string['privacy:metadata:local_lessontweak_ptime:timespent'] = 'Total active seconds spent on the page.';
$string['privacy:metadata:local_lessontweak_ptime:timemodified'] = 'When the time was last updated.';
$string['privacy:metadata'] = 'Information about how the Lesson tweaks plugin stores personal data.';
