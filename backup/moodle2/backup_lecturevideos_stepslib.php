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
 * backup_lecturevideos_stepslib.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class backup_lecturevideos_activity_structure_step.
 */
class backup_lecturevideos_activity_structure_step extends backup_activity_structure_step {
    /**
     * Method define_structure.
     *
     * @return mixed Return value.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $activity = new backup_nested_element(
            'lecturevideos',
            ['id'],
            [
                'name', 'intro', 'introformat', 'sequential', 'allowseek', 'resumeplayback',
                'completionpercent', 'completionmode', 'grade', 'timecreated', 'timemodified',
            ]
        );
        $videos = new backup_nested_element('videos');
        $video = new backup_nested_element(
            'video',
            ['id'],
            ['title', 'description', 'minpercent', 'duration', 'sortorder', 'enabled', 'timecreated', 'timemodified']
        );
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element(
            'progress',
            ['id'],
            [
                'videoid', 'userid', 'duration', 'lastposition', 'uniquewatched', 'totalwatchtime',
                'percent', 'watchedsegments', 'completed', 'timecreated', 'timemodified',
            ]
        );
        $activity->add_child($videos);
        $videos->add_child($video);
        $activity->add_child($progresses);
        $progresses->add_child($progress);
        $activity->set_source_table('lecturevideos', ['id' => backup::VAR_ACTIVITYID]);
        $video->set_source_table('lecturevideos_videos', ['playlistid' => backup::VAR_PARENTID]);
        if ($userinfo) {
            $progress->set_source_table('lecturevideos_progress', ['playlistid' => backup::VAR_ACTIVITYID]);
        }
        $progress->annotate_ids('user', 'userid');
        $activity->annotate_files('mod_lecturevideos', 'intro', null);
        $video->annotate_files('mod_lecturevideos', 'video', 'id');
        $video->annotate_files('mod_lecturevideos', 'poster', 'id');
        return $this->prepare_activity_structure($activity);
    }
}
