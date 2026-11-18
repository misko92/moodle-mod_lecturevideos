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
 * Progress report: one expandable row per student.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright 2026 misko92 (expandable per-student layout)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');

use mod_lecturevideos\local\videos;
use mod_lecturevideos\progress_manager;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('lecturevideos', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$playlist = $DB->get_record('lecturevideos', ['id' => $cm->instance], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lecturevideos:viewreport', $context);
$PAGE->set_url('/mod/lecturevideos/report.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('reporttitle', 'lecturevideos'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_call_amd('mod_lecturevideos/report', 'init');

$manager = new progress_manager();
$videos = $manager->get_videos($playlist->id);

// All progress for this activity in one query, keyed by user then video.
$progress = [];
foreach ($DB->get_records('lecturevideos_progress', ['playlistid' => $playlist->id]) as $p) {
    $progress[$p->userid][$p->videoid] = $p;
}

$completion = new completion_info($course);
$trackcompletion = $completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC;
$students = get_enrolled_users($context, 'mod/lecturevideos:view', 0,
    'u.id,' . implode(',', \core_user\fields::get_name_fields(true)), 'u.lastname,u.firstname');

$rows = [];
$completecount = 0;
foreach ($students as $student) {
    if (has_capability('mod/lecturevideos:viewreport', $context, $student->id)) {
        continue;
    }
    $cells = [];
    $sum = 0.0;
    $last = 0;
    $watchedcount = 0;
    foreach ($videos as $video) {
        $p = $progress[$student->id][$video->id] ?? null;
        $percent = $p ? min(100.0, (float)$p->percent) : 0.0;
        $sum += $percent;
        $last = max($last, $p ? (int)$p->timemodified : 0);
        $duration = (float)$video->duration ?: ($p ? (float)$p->duration : 0);
        $segments = [];
        if ($p && $duration > 0) {
            foreach (json_decode((string)$p->watchedsegments, true) ?: [] as $s) {
                $segments[] = ['left' => round(100 * $s[0] / $duration, 2),
                    'width' => max(0.3, round(100 * ($s[1] - $s[0]) / $duration, 2))];
            }
        }
        $done = $percent >= (float)$video->minpercent;
        $watchedcount += $done ? 1 : 0;
        $cells[] = [
            'title' => format_string($video->title), 'percent' => round($percent), 'completed' => $done,
            'started' => (bool)$p, 'segments' => $segments,
            'watchtime' => $p ? videos::format_duration((float)$p->totalwatchtime) : '',
            'length' => videos::format_duration($duration),
            'lastwatched' => $p ? userdate($p->timemodified, get_string('strftimedatetimeshort', 'langconfig')) : '',
        ];
    }
    $overall = $videos ? round($sum / count($videos)) : 0;
    $complete = null;
    if ($trackcompletion) {
        $complete = $completion->get_data($cm, false, $student->id)->completionstate >= COMPLETION_COMPLETE;
        $completecount += $complete ? 1 : 0;
    }
    $rows[] = [
        'userid' => $student->id, 'fullname' => fullname($student), 'overall' => $overall,
        'watchedcount' => $watchedcount, 'videocount' => count($videos),
        'trackcompletion' => $trackcompletion, 'complete' => (bool)$complete,
        'lastwatched' => $last ? userdate($last, get_string('strftimedatetimeshort', 'langconfig')) : '',
        'notstarted' => !$last, 'videos' => $cells,
    ];
}

$data = [
    'name' => format_string($playlist->name),
    'students' => $rows, 'hasstudents' => !empty($rows),
    'trackcompletion' => $trackcompletion,
    'summary' => get_string('reportsummary', 'lecturevideos',
        ['complete' => $completecount, 'total' => count($rows), 'videos' => count($videos)]),
    'backurl' => (new moodle_url('/mod/lecturevideos/view.php', ['id' => $cm->id]))->out(false),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_lecturevideos/report', $data);
echo $OUTPUT->footer();
