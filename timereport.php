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
 * Teacher report: how long students spent on each lesson page and, in total,
 * completing each attempt of the lesson.
 *
 * @package    local_lessontweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Lesson course module id.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'lesson');
require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/lesson:viewreports', $context);

$lesson = $DB->get_record('lesson', ['id' => $cm->instance], '*', MUST_EXIST);

$url = new moodle_url('/local/lessontweak/timereport.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($lesson->name) . ': ' . get_string('timereport', 'local_lessontweak'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('timereport', 'local_lessontweak'));

// Pull every page-time row for this lesson with the student name and page title.
$userfieldsapi = \core_user\fields::for_name();
$usernamefields = $userfieldsapi->get_sql('u', false, '', '', true)->selects;

$sql = "SELECT t.id, t.userid, t.pageid, t.attempt, t.timespent, t.timemodified,
               lp.title AS pagetitle $usernamefields
          FROM {local_lessontweak_ptime} t
          JOIN {user} u ON u.id = t.userid
     LEFT JOIN {lesson_pages} lp ON lp.id = t.pageid
         WHERE t.lessonid = :lessonid";
$rows = $DB->get_records_sql($sql, ['lessonid' => $lesson->id]);

if (!$rows) {
    echo $OUTPUT->notification(get_string('timenodata', 'local_lessontweak'), 'info');
    echo $OUTPUT->footer();
    die;
}

// Wall-clock attempt duration comes from the lesson's own {lesson_timer}: one
// row per attempt. Ordering each user's rows by starttime gives the same
// 0-indexed attempt number that page time is stored against. Build a lookup of
// userid|attempt => duration in seconds (null while an attempt is unfinished).
$walldurations = [];
$timers = $DB->get_records('lesson_timer',
    ['lessonid' => $lesson->id], 'userid ASC, starttime ASC',
    'id, userid, starttime, lessontime, completed');
$attemptindex = [];
foreach ($timers as $timer) {
    $uid = (int) $timer->userid;
    $index = $attemptindex[$uid] ?? 0;
    $attemptindex[$uid] = $index + 1;
    // lessontime is the last recorded activity; 0/absent means still in progress.
    $end = (int) $timer->lessontime;
    $walldurations[$uid . '|' . $index] =
        $end > (int) $timer->starttime ? $end - (int) $timer->starttime : null;
}

// Sort by student name, then attempt, then page so each student's attempt is a
// contiguous block we can total.
usort($rows, function($a, $b) {
    return [strtolower($a->lastname), strtolower($a->firstname), (int) $a->attempt, (int) $a->pageid]
       <=> [strtolower($b->lastname), strtolower($b->firstname), (int) $b->attempt, (int) $b->pageid];
});

$table = new html_table();
$table->head = [
    get_string('timestudent', 'local_lessontweak'),
    get_string('timeattempt', 'local_lessontweak'),
    get_string('timepage', 'local_lessontweak'),
    get_string('timespentcol', 'local_lessontweak'),
    get_string('timewall', 'local_lessontweak'),
    get_string('timeupdated', 'local_lessontweak'),
];
$table->attributes['class'] = 'generaltable lessontweak-time-report';
$table->data = [];

// Emit a bold per-attempt total row: total active time and wall-clock duration.
$flush = function(?stdClass $group) use ($table) {
    if ($group === null) {
        return;
    }
    $cell = new html_table_cell(get_string('timetotal', 'local_lessontweak'));
    $cell->colspan = 3;
    $cell->attributes['class'] = 'lessontweak-time-total-label';
    $total = new html_table_cell(format_time($group->total));
    $total->attributes['class'] = 'lessontweak-time-total-value';
    $wall = new html_table_cell($group->wall !== null ? format_time($group->wall) : '—');
    $wall->attributes['class'] = 'lessontweak-time-wall-value';
    $row = new html_table_row([$cell, $total, $wall, new html_table_cell(userdate($group->updated))]);
    $row->attributes['class'] = 'lessontweak-time-total';
    $table->data[] = $row;
};

$group = null;
foreach ($rows as $r) {
    $groupkey = $r->userid . '|' . $r->attempt;
    if ($group === null || $group->key !== $groupkey) {
        $flush($group);
        $group = (object) [
            'key'     => $groupkey,
            'total'   => 0,
            'updated' => 0,
            'wall'    => $walldurations[$groupkey] ?? null,
        ];
    }
    $group->total += (int) $r->timespent;
    $group->updated = max($group->updated, (int) $r->timemodified);

    $userurl = new moodle_url('/user/view.php', ['id' => $r->userid, 'course' => $course->id]);
    $pagetitle = $r->pagetitle !== null
        ? format_string($r->pagetitle)
        : get_string('timepagegone', 'local_lessontweak', $r->pageid);

    $table->data[] = [
        html_writer::link($userurl, fullname($r)),
        // Stored attempt is 0-indexed (lesson retry number); show 1-indexed.
        $r->attempt + 1,
        $pagetitle,
        format_time($r->timespent),
        // Wall-clock duration is per attempt, shown once on the total row.
        '',
        userdate($r->timemodified),
    ];
}
$flush($group);

echo html_writer::table($table);
echo $OUTPUT->footer();
