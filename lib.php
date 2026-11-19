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
 * lib.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_lecturevideos\progress_manager;

/**
 * Returns information about features supported by the activity module.
 *
 * @param string $feature The feature constant being queried.
 * @return mixed True or false if the feature is supported, null if unknown.
 */
function lecturevideos_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Creates a new lecture videos activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_lecturevideos_mod_form|null $mform The activity form.
 * @return int The new activity instance ID.
 */
function lecturevideos_add_instance(stdClass $data, ?mod_lecturevideos_mod_form $mform = null): int {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = time();
    $id = $DB->insert_record('lecturevideos', $data);
    $data->id = $id;
    lecturevideos_grade_item_update($data);
    return $id;
}

/**
 * Updates an existing lecture videos activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_lecturevideos_mod_form|null $mform The activity form.
 * @return bool True on success.
 */
function lecturevideos_update_instance(stdClass $data, ?mod_lecturevideos_mod_form $mform = null): bool {
    global $DB;
    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('lecturevideos', $data);
    lecturevideos_grade_item_update($data);
    return $result;
}

/**
 * Deletes a lecture videos activity instance and its related data.
 *
 * @param int $id The activity instance ID.
 * @return bool True on success, false if the instance does not exist.
 */
function lecturevideos_delete_instance(int $id): bool {
    global $DB;
    $playlist = $DB->get_record('lecturevideos', ['id' => $id]);
    if (!$playlist) {
        return false;
    }
    $cm = get_coursemodule_from_instance('lecturevideos', $id, $playlist->course, false, IGNORE_MISSING);
    if ($cm) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_lecturevideos');
    }
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('lecturevideos_sessions', ['playlistid' => $id]);
    $DB->delete_records('lecturevideos_progress', ['playlistid' => $id]);
    $DB->delete_records('lecturevideos_videos', ['playlistid' => $id]);
    $DB->delete_records('lecturevideos', ['id' => $id]);
    $transaction->allow_commit();
    lecturevideos_grade_item_delete($playlist);
    return true;
}

/**
 * Serves files stored by the lecture videos activity.
 *
 * @param stdClass $course The course record.
 * @param stdClass $cm The course module record.
 * @param context_module $context The module context.
 * @param string $filearea The requested file area.
 * @param array $args Path arguments.
 * @param bool $forcedownload Whether the file should be downloaded.
 * @param array $options Additional file serving options.
 * @return bool False when the requested file cannot be served.
 */
function mod_lecturevideos_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    global $DB, $USER;
    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ['video', 'poster', 'captions'], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/lecturevideos:view', $context);
    $videoid = (int)array_shift($args);
    $video = $DB->get_record('lecturevideos_videos', ['id' => $videoid, 'playlistid' => $cm->instance], '*', MUST_EXIST);
    if ($filearea !== 'poster' && !has_capability('mod/lecturevideos:managevideos', $context)) {
        // Students only get enabled videos (and their captions) they have unlocked.
        $playlist = $DB->get_record('lecturevideos', ['id' => $cm->instance], '*', MUST_EXIST);
        if (
            empty($video->enabled) ||
                !(new \mod_lecturevideos\progress_manager())->is_video_unlocked($playlist, $video, $USER->id)
        ) {
            return false;
        }
    }
    $filename = array_pop($args);
    $filepath = '/' . ($args ? implode('/', $args) . '/' : '');
    $file = get_file_storage()->get_file($context->id, 'mod_lecturevideos', $filearea, $videoid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    // Private caching: lets browsers keep the file a day without shared caches storing it.
    send_stored_file($file, DAYSECS, 0, $forcedownload, array_merge($options, ['cacheability' => 'private']));
}

/**
 * Returns the file areas used by the activity.
 *
 * @param stdClass $course The course record.
 * @param stdClass $cm The course module record.
 * @param context_module $context The module context.
 * @return array The available file areas.
 */
function lecturevideos_get_file_areas($course, $cm, $context): array {
    return [
        'video' => get_string('videofile', 'lecturevideos'),
        'poster' => get_string('posterfile', 'lecturevideos'),
        'captions' => get_string('captionfiles', 'lecturevideos'),
    ];
}

/**
 * Creates or updates the grade item for a lecture videos.
 *
 * @param stdClass $playlist The playlist record.
 * @param array|string|null $grades Optional grades to update, or 'reset' to clear all grades.
 * @return int Grade update status.
 */
function lecturevideos_grade_item_update(stdClass $playlist, array|string|null $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = [
        'itemname' => clean_param($playlist->name, PARAM_NOTAGS),
        'gradetype' => ((float)$playlist->grade > 0) ? GRADE_TYPE_VALUE : GRADE_TYPE_NONE,
        'grademin' => 0,
        'grademax' => 100,
    ];
    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/lecturevideos', $playlist->course, 'mod', 'lecturevideos', $playlist->id, 0, $grades, $item);
}

/**
 * Deletes the grade item for a lecture videos.
 *
 * @param stdClass $playlist The playlist record.
 * @return int Grade update status.
 */
function lecturevideos_grade_item_delete(stdClass $playlist): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    return grade_update('mod/lecturevideos', $playlist->course, 'mod', 'lecturevideos', $playlist->id, 0, null, ['deleted' => 1]);
}

/**
 * Updates grades from learner playlist progress.
 *
 * @param stdClass $playlist The playlist record.
 * @param int $userid Optional user ID. Zero updates all users with progress.
 * @param bool $nullifnone Whether to send a null grade when the user has no progress.
 * @return void
 */
function lecturevideos_update_grades(stdClass $playlist, int $userid = 0, bool $nullifnone = true): void {
    global $DB;
    $manager = new progress_manager();
    $grades = [];
    if ($userid) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => $manager->get_overall_percent($playlist->id, $userid)];
    } else {
        $userids = $DB->get_fieldset_select(
            'lecturevideos_progress',
            'DISTINCT userid',
            'playlistid = :id',
            ['id' => $playlist->id]
        );
        foreach ($userids as $uid) {
            $grades[$uid] = (object)['userid' => $uid, 'rawgrade' => $manager->get_overall_percent($playlist->id, $uid)];
        }
    }
    if (!$grades && $userid && $nullifnone) {
        $grades[$userid] = (object)['userid' => $userid, 'rawgrade' => null];
    }
    lecturevideos_grade_item_update($playlist, $grades);
}

/**
 * Returns cached course module information for the activity.
 *
 * @param stdClass $cm The course module record.
 * @return cached_cm_info|null Course module information, or null when the activity is missing.
 */
function lecturevideos_get_coursemodule_info(stdClass $cm): cached_cm_info|null {
    global $DB;
    $playlist = $DB->get_record(
        'lecturevideos',
        ['id' => $cm->instance],
        'id, name, intro, introformat, completionpercent, completionmode'
    );
    if (!$playlist) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $playlist->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('lecturevideos', $playlist, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = ['completionpercent' => (int)$playlist->completionpercent];
        $info->customdata['completionmode'] = (int)$playlist->completionmode;
    }
    return $info;
}

/**
 * Returns descriptions of the active custom completion rules.
 *
 * @param cached_cm_info $cm Cached course module information.
 * @return array Active completion rule descriptions.
 */
function lecturevideos_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if (
        (int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC ||
        empty($cm->customdata['customcompletionrules']['completionpercent'])
    ) {
        return [];
    }
    $key = empty($cm->customdata['completionmode']) ? 'completiondetail:percent' : 'completiondetail:each';
    return [get_string($key, 'lecturevideos', $cm->customdata['customcompletionrules']['completionpercent'])];
}

/**
 * Returns the completion state for a learner.
 *
 * @param stdClass $course The course record.
 * @param stdClass $cm The course module record.
 * @param int $userid The user ID.
 * @param bool $type Expected completion state.
 * @return bool True when the configured completion percentage has been reached.
 */
function lecturevideos_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;
    $playlist = $DB->get_record('lecturevideos', ['id' => $cm->instance], '*', MUST_EXIST);
    return (new progress_manager())->get_overall_percent($playlist->id, $userid) >= (float)$playlist->completionpercent;
}

/**
 * Course reset form: offer to delete all video progress.
 *
 * @param MoodleQuickForm $mform
 */
function lecturevideos_reset_course_form_definition(&$mform): void {
    $mform->addElement('header', 'lecturevideosheader', get_string('modulenameplural', 'lecturevideos'));
    $mform->addElement('advcheckbox', 'reset_lecturevideos_progress', get_string('resetprogress', 'lecturevideos'));
}

/**
 * Course reset defaults.
 *
 * @param stdClass $course
 * @return array
 */
function lecturevideos_reset_course_form_defaults($course): array {
    return ['reset_lecturevideos_progress' => 1];
}

/**
 * Course reset: delete all students' progress (and grades) in this course's activities.
 *
 * @param stdClass $data Reset form data.
 * @return array Status lines.
 */
function lecturevideos_reset_userdata($data): array {
    global $DB;
    if (empty($data->reset_lecturevideos_progress)) {
        return [];
    }
    $playlists = $DB->get_records('lecturevideos', ['course' => $data->courseid]);
    foreach ($playlists as $playlist) {
        $DB->delete_records('lecturevideos_sessions', ['playlistid' => $playlist->id]);
        $DB->delete_records('lecturevideos_progress', ['playlistid' => $playlist->id]);
        if ((float)$playlist->grade > 0) {
            lecturevideos_grade_item_update($playlist, 'reset');
        }
    }
    return [['component' => get_string('modulenameplural', 'lecturevideos'),
        'item' => get_string('resetprogress', 'lecturevideos'), 'error' => false]];
}
