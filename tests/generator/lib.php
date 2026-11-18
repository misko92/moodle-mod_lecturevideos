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
 * Test data generator.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_lecturevideos_generator extends testing_module_generator {
    /**
     * Create an activity.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (array)$record + ['sequential' => 0, 'allowseek' => 1, 'resumeplayback' => 1,
            'completionpercent' => 90, 'completionmode' => 1, 'grade' => 0];
        return parent::create_instance($record, $options);
    }

    /**
     * Add a video record (no file) with a known length.
     *
     * @param int $playlistid
     * @param float $duration Seconds.
     * @param int $minpercent
     * @return stdClass
     */
    public function create_video(int $playlistid, float $duration, int $minpercent = 90): stdClass {
        global $DB;
        $video = \mod_lecturevideos\local\videos::add($playlistid, 'Video', $minpercent);
        $DB->set_field('lecturevideos_videos', 'duration', $duration, ['id' => $video->id]);
        return $DB->get_record('lecturevideos_videos', ['id' => $video->id]);
    }
}
