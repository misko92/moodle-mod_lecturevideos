<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_lecturevideos\local;

use moodle_url;

/**
 * Resolves site-independent links to a moment in a lecture video (goto.php).
 *
 * The activity is found by its ID number (course module idnumber) and the video by the start of its title,
 * so one link works on every site that has the activity, whatever its course module and video ids are there.
 *
 * @package    mod_lecturevideos
 * @copyright  2026 misko92
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class review_link {
    /**
     * Clean a start time: seconds, m:ss, h:mm:ss or 5m07s (the player understands these), else ''.
     *
     * @param string $start the raw value
     * @return string
     */
    public static function clean_start(string $start): string {
        $start = trim($start);
        return preg_match('/^[0-9hms:.]{1,12}$/', $start) ? $start : '';
    }

    /**
     * Find where a link should go for the current user.
     *
     * @param string $idnumber the activity's ID number, e.g. lv-ap-31
     * @param string $title the start of the video title, e.g. 31.02 ('' = the activity itself)
     * @param string $start the start time
     * @return moodle_url|null the view.php URL, or null when no activity with that ID number is available to the user
     */
    public static function resolve(string $idnumber, string $title, string $start): ?moodle_url {
        global $DB;
        if ($idnumber === '') {
            return null;
        }
        // Every activity with this ID number (a course copy has its own): the first one this user can open.
        $sql = "SELECT cm.id, cm.course, cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'lecturevideos'
                 WHERE cm.idnumber = :idnumber
              ORDER BY cm.id";
        $target = null;
        foreach ($DB->get_records_sql($sql, ['idnumber' => $idnumber]) as $record) {
            if (get_fast_modinfo($record->course)->get_cm($record->id)->uservisible) {
                $target = $record;
                break;
            }
        }
        if (!$target) {
            return null;
        }
        $params = ['id' => $target->id];
        $title = trim($title);
        if ($title !== '') {
            $select = 'playlistid = :playlistid AND (title = :exact OR ' . $DB->sql_like('title', ':prefix') . ')';
            $videos = $DB->get_records_select(
                'lecturevideos_videos',
                $select,
                ['playlistid' => $target->instance, 'exact' => $title, 'prefix' => $DB->sql_like_escape($title) . ' %'],
                'sortorder',
                'id',
                0,
                1
            );
            if ($videos) {
                $params['video'] = reset($videos)->id;
                $start = self::clean_start($start);
                if ($start !== '') {
                    $params['t'] = $start;
                }
            }
        }
        return new moodle_url('/mod/lecturevideos/view.php', $params);
    }
}
