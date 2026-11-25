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
 * lecturevideos.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['addvideo'] = 'Add video';
$string['allowseek'] = 'Allow skipping ahead to parts not yet watched';
$string['back'] = 'Back';
$string['captionfiles'] = 'Subtitles';
$string['captionfiles_help'] = 'WebVTT subtitle files (.vtt), one per language. Name each file after its language: **en.vtt** for English, **es.vtt** for Spanish (or es_mx.vtt).

Students whose Moodle language is Spanish see Spanish subtitles straight away. Everyone can switch subtitles on, off or to another language with the CC button in the player.';
$string['complete'] = 'Complete';
$string['completiondetail:each'] = 'Watch at least {$a}% of every video';
$string['completiondetail:percent'] = 'Reach at least {$a}% average progress across the videos';
$string['completionmodeaverage'] = 'Average across all videos at least';
$string['completionmodeeach'] = 'Every video watched at least';
$string['completionpercent'] = 'Require watching';
$string['completionpercent_help'] = 'Only parts of a video that were actually played count. Skipping ahead or replaying the same part does not add progress.

* **Every video watched at least X%**: each video must reach X% on its own.
* **Average across all videos at least X%**: the average of all videos must reach X%, so a skipped video can be made up by others.

About 1% is usually lost to rounding at the very end of a video, so 100% is not recommended.';
$string['confirmdeletevideo'] = 'Delete video "{$a}" and all progress stored for it?';
$string['duration'] = 'Length';
$string['editvideo'] = 'Edit video';
$string['enabled'] = 'Enabled';
$string['errorcaptionname'] = '"{$a}": name subtitle files after their language, e.g. en.vtt or es.vtt.';
$string['errorpercent'] = 'Enter a percentage from 1 to 100.';
$string['gotochoose'] = 'Which video?';
$string['gotonotfound'] = 'This video isn\'t part of your course yet. Ask your teacher if you need it.';
$string['invalidtrackingdata'] = 'Invalid video tracking data.';
$string['lastwatched'] = 'Last watched';
$string['lecturevideos:addinstance'] = 'Add a new Lecture videos activity';
$string['lecturevideos:managevideos'] = 'Manage the videos of a Lecture videos activity';
$string['lecturevideos:resetprogress'] = 'Reset Lecture videos progress';
$string['lecturevideos:view'] = 'View Lecture videos';
$string['lecturevideos:viewreport'] = 'View the Lecture videos progress report';
$string['lecturevideosname'] = 'Name';
$string['managevideos'] = 'Manage videos';
$string['minpercent'] = 'Counts as watched at';
$string['minpercent_help'] = 'Percentage of this video a student must watch before it shows a tick in the list (and, if videos must be watched in order, before the next video unlocks).';
$string['modulename'] = 'Lecture videos';
$string['modulename_help'] = 'A playlist of uploaded videos with the list beside the player. Moodle records which parts of each video every student actually watched, resumes where they left off, and can complete the activity once enough has been watched. Teachers get a per-student, per-video report.';
$string['modulenameplural'] = 'Lecture videos';
$string['noactivities'] = 'There are no Lecture videos activities in this course.';
$string['nostudents'] = 'No students are enrolled yet.';
$string['notcomplete'] = 'Not yet';
$string['notstarted'] = 'Not started';
$string['novideos'] = 'No videos have been added yet.';
$string['overallprogress'] = 'Overall progress';
$string['partswatched'] = 'Parts watched';
$string['playlistsettings'] = 'Playback';
$string['pluginadministration'] = 'Lecture videos administration';
$string['pluginname'] = 'Lecture videos';
$string['posterfile'] = 'Preview image';
$string['posterfile_help'] = 'Optional picture shown before the video plays and as the thumbnail in the list (JPG, PNG or WebP, 16:9 looks best).';
$string['privacy:metadata:progress'] = 'Per-video viewing progress.';
$string['privacy:metadata:progress:lastposition'] = 'Last playback position.';
$string['privacy:metadata:progress:percent'] = 'Percentage watched.';
$string['privacy:metadata:progress:segments'] = 'Parts of the video actually watched.';
$string['privacy:metadata:progress:userid'] = 'The student.';
$string['privacy:metadata:progress:watchtime'] = 'Total time spent playing the video.';
$string['privacy:metadata:sessions'] = 'Technical record of each viewing session, used to check progress reports arrive in order and at a believable rate.';
$string['privacy:metadata:sessions:lastheartbeat'] = 'Time of the last progress report.';
$string['privacy:metadata:sessions:timecreated'] = 'Time the viewing session started.';
$string['privacy:metadata:sessions:userid'] = 'The student.';
$string['reportsummary'] = '{$a->complete} of {$a->total} students complete · {$a->videos} videos';
$string['reporttitle'] = 'Video progress report';
$string['resetprogress'] = 'Delete all video progress';
$string['resumeautomatic'] = 'Continue from where the student left off';
$string['resumefromstart'] = 'Always start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['sequential'] = 'Videos must be watched in order';
$string['statuscompleted'] = 'Watched';
$string['statuslocked'] = 'Locked';
$string['student'] = 'Student';
$string['videodeleted'] = 'Video deleted.';
$string['videodescription'] = 'Description';
$string['videofile'] = 'Video file';
$string['videofile_help'] = 'MP4 (H.264 video, AAC audio) plays on every device. Small screen recordings of slides work well at 720p.';
$string['videolocked'] = 'This video is locked until the previous video has been watched.';
$string['videoprogress'] = 'This video';
$string['videosaved'] = 'Video saved.';
$string['videoswatched'] = '{$a->done} of {$a->total} videos watched';
$string['videotitle'] = 'Video title';
$string['viewreport'] = 'Progress report';
$string['watchedparts'] = 'Green = parts you have watched';
$string['watchtime'] = 'Time spent';
