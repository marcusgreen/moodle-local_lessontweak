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
 * Teacher report: confidence values students reported on lesson question pages.
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

$url = new moodle_url('/local/lessontweak/report.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($lesson->name) . ': ' . get_string('confidencereport', 'local_lessontweak'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('confidencereport', 'local_lessontweak'));

// Pull confidence and time rows for this lesson, with student and page title,
// then merge them on user + page + attempt.
$userfieldsapi = \core_user\fields::for_name();
$usernamefields = $userfieldsapi->get_sql('u', false, '', '', true)->selects;

$confsql = "SELECT c.id, c.userid, c.pageid, c.attempt, c.confidence, c.timemodified,
                   lp.title AS pagetitle $usernamefields
              FROM {local_lessontweak_conf} c
              JOIN {user} u ON u.id = c.userid
         LEFT JOIN {lesson_pages} lp ON lp.id = c.pageid
             WHERE c.lessonid = :lessonid";
$confrows = $DB->get_records_sql($confsql, ['lessonid' => $lesson->id]);

$timesql = "SELECT t.id, t.userid, t.pageid, t.attempt, t.timespent, t.timemodified,
                   lp.title AS pagetitle $usernamefields
              FROM {local_lessontweak_ptime} t
              JOIN {user} u ON u.id = t.userid
         LEFT JOIN {lesson_pages} lp ON lp.id = t.pageid
             WHERE t.lessonid = :lessonid";
$timerows = $DB->get_records_sql($timesql, ['lessonid' => $lesson->id]);

if (!$confrows && !$timerows) {
    echo $OUTPUT->notification(get_string('confidencenodata', 'local_lessontweak'), 'info');
    echo $OUTPUT->footer();
    die;
}

// Merge both sources keyed by user|page|attempt.
$merged = [];
$keyof = fn($r) => $r->userid . '|' . $r->pageid . '|' . $r->attempt;
$basify = function($r) {
    $base = clone $r;
    $base->confidence = null;
    $base->timespent = null;
    $base->updated = $r->timemodified;
    unset($base->id, $base->timemodified);
    return $base;
};

foreach ($confrows as $r) {
    $key = $keyof($r);
    $merged[$key] = $merged[$key] ?? $basify($r);
    $merged[$key]->confidence = $r->confidence;
    $merged[$key]->updated = max($merged[$key]->updated, $r->timemodified);
}
foreach ($timerows as $r) {
    $key = $keyof($r);
    $merged[$key] = $merged[$key] ?? $basify($r);
    $merged[$key]->timespent = $r->timespent;
    $merged[$key]->updated = max($merged[$key]->updated, $r->timemodified);
}

// Sort by student name, then attempt, then page.
usort($merged, function($a, $b) {
    return [strtolower($a->lastname), strtolower($a->firstname), $a->attempt, $a->pageid]
       <=> [strtolower($b->lastname), strtolower($b->firstname), $b->attempt, $b->pageid];
});

$table = new html_table();
$table->head = [
    get_string('confidencestudent', 'local_lessontweak'),
    get_string('confidencepage', 'local_lessontweak'),
    get_string('confidenceattempt', 'local_lessontweak'),
    get_string('confidencevalue', 'local_lessontweak'),
    get_string('confidencetimespent', 'local_lessontweak'),
    get_string('confidenceupdated', 'local_lessontweak'),
];
$table->attributes['class'] = 'generaltable lessontweak-confidence-report';
$table->data = [];

foreach ($merged as $r) {
    $userurl = new moodle_url('/user/view.php', ['id' => $r->userid, 'course' => $course->id]);
    $studentname = html_writer::link($userurl, fullname($r));
    $pagetitle = $r->pagetitle !== null
        ? format_string($r->pagetitle)
        : get_string('confidencepagegone', 'local_lessontweak', $r->pageid);

    $table->data[] = [
        $studentname,
        $pagetitle,
        // Stored attempt is 0-indexed (lesson retry number); show 1-indexed.
        $r->attempt + 1,
        $r->confidence !== null ? $r->confidence . '%' : '—',
        $r->timespent !== null ? format_time($r->timespent) : '—',
        userdate($r->updated),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
