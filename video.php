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
 * video.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
$id = required_param('id', PARAM_INT);
$videoid = optional_param('videoid', 0, PARAM_INT);
$cm = get_coursemodule_from_id('lecturevideos', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$playlist = $DB->get_record('lecturevideos', ['id' => $cm->instance], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/lecturevideos:managevideos', $context);
$video = $videoid ? $DB->get_record('lecturevideos_videos',
    ['id' => $videoid, 'playlistid' => $playlist->id], '*', MUST_EXIST) : null;
$PAGE->set_url('/mod/lecturevideos/video.php', ['id' => $cm->id, 'videoid' => $videoid]);
$PAGE->set_title($video ? get_string('editvideo', 'lecturevideos') : get_string('addvideo', 'lecturevideos'));
$PAGE->set_heading(format_string($course->fullname));
$form = new \mod_lecturevideos\form\video_form();
$fileopts = ['subdirs' => 0, 'maxfiles' => 1];
$videodraft = file_get_submitted_draft_itemid('videofile');
$posterdraft = file_get_submitted_draft_itemid('posterfile');
if ($video) {
    file_prepare_draft_area($videodraft, $context->id, 'mod_lecturevideos', 'video', $video->id, $fileopts);
    file_prepare_draft_area($posterdraft, $context->id, 'mod_lecturevideos', 'poster', $video->id, $fileopts);
    $form->set_data((object)['cmid' => $cm->id, 'videoid' => $video->id, 'title' => $video->title,
        'description' => $video->description, 'minpercent' => $video->minpercent, 'enabled' => $video->enabled,
        'videofile' => $videodraft, 'posterfile' => $posterdraft]);
} else {
    file_prepare_draft_area($videodraft, null, null, null, null, $fileopts);
    file_prepare_draft_area($posterdraft, null, null, null, null, $fileopts);
    $form->set_data((object)['cmid' => $cm->id, 'videoid' => 0, 'videofile' => $videodraft, 'posterfile' => $posterdraft]);
}
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/lecturevideos/manage.php', ['id' => $cm->id]));
}
if ($data = $form->get_data()) {
    if ($video) {
        $video->title = $data->title;
        $video->description = $data->description;
        $video->minpercent = $data->minpercent;
        $video->enabled = $data->enabled;
        $video->timemodified = time();
        $DB->update_record('lecturevideos_videos', $video);
    } else {
        $video = \mod_lecturevideos\local\videos::add($playlist->id, $data->title, (int)$data->minpercent,
            (string)$data->description);
        $DB->set_field('lecturevideos_videos', 'enabled', $data->enabled, ['id' => $video->id]);
    }
    file_save_draft_area_files($data->videofile, $context->id, 'mod_lecturevideos', 'video', $video->id, $fileopts);
    file_save_draft_area_files($data->posterfile, $context->id, 'mod_lecturevideos', 'poster', $video->id, $fileopts);
    \mod_lecturevideos\local\videos::refresh_duration($context, $video);
    redirect(new moodle_url('/mod/lecturevideos/manage.php', ['id' => $cm->id]), get_string('videosaved', 'lecturevideos'));
}
echo $OUTPUT->header();
echo $form->render();
echo $OUTPUT->footer();
