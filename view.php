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
 * view.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright 2026 misko92 (single-page player, posters, durations)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use mod_lecturevideos\local\videos;
use mod_lecturevideos\progress_manager;

$id = required_param('id', PARAM_INT);
$videoid = optional_param('video', 0, PARAM_INT);
$cm = get_coursemodule_from_id('lecturevideos', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$playlist = $DB->get_record('lecturevideos', ['id' => $cm->instance], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lecturevideos:view', $context);
$PAGE->set_url('/mod/lecturevideos/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($playlist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->js_call_amd('mod_lecturevideos/player', 'init');
$manager = new progress_manager();
$items = [];
foreach ($manager->get_videos($playlist->id) as $i => $video) {
    $progress = $manager->get_progress($video->id, $USER->id);
    $unlocked = $manager->is_video_unlocked($playlist, $video, $USER->id);
    $percent = $progress ? (float)$progress->percent : 0.0;
    $items[$video->id] = [
        'id' => $video->id, 'index' => $i + 1, 'title' => format_string($video->title),
        'description' => (string)$video->description,
        'duration' => videos::format_duration((float)$video->duration),
        'percent' => round($percent), 'percentexact' => round($percent, 2),
        'completed' => $percent >= (float)$video->minpercent, 'minpercent' => (int)$video->minpercent,
        'unlocked' => $unlocked, 'locked' => !$unlocked, 'started' => (bool)$progress,
        'src' => $unlocked ? videos::url($context, $video->id, 'video') : '',
        'poster' => videos::url($context, $video->id, 'poster'),
        'lastposition' => $progress && $playlist->resumeplayback ? (float)$progress->lastposition : 0,
        'segments' => $progress ? (string)$progress->watchedsegments : '[]',
        'current' => false,
    ];
}
// Start on the requested video, else the first unlocked one not yet completed, else the first unlocked one.
$currentid = 0;
if ($videoid && !empty($items[$videoid]['unlocked'])) {
    $currentid = $videoid;
} else {
    foreach ($items as $vid => $item) {
        if ($item['unlocked'] && !$item['completed']) {
            $currentid = $vid;
            break;
        }
    }
    if (!$currentid) {
        foreach ($items as $vid => $item) {
            if ($item['unlocked']) {
                $currentid = $vid;
                break;
            }
        }
    }
}
if ($currentid) {
    $items[$currentid]['current'] = true;
}
$data = [
    'cmid' => $cm->id, 'allowseek' => (int)!empty($playlist->allowseek),
    'videos' => array_values($items), 'hasvideos' => !empty($items),
    'current' => $currentid ? $items[$currentid] : null,
    'overallpercent' => round($manager->get_overall_percent($playlist->id, $USER->id)),
    'showoverall' => count($items) > 1,
    'manageurl' => (new moodle_url('/mod/lecturevideos/manage.php', ['id' => $cm->id]))->out(false),
    'reporturl' => (new moodle_url('/mod/lecturevideos/report.php', ['id' => $cm->id]))->out(false),
    'canmanage' => has_capability('mod/lecturevideos:managevideos', $context),
    'canreport' => has_capability('mod/lecturevideos:viewreport', $context),
];
$data['hastools'] = $data['canmanage'] || $data['canreport'];
$event = \mod_lecturevideos\event\course_module_viewed::create(['objectid' => $playlist->id, 'context' => $context]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->trigger();
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_lecturevideos/view', $data);
echo $OUTPUT->footer();
