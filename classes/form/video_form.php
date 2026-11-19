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
 * video_form.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright 2026 misko92 (uploads only, poster image)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lecturevideos\form;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("{$CFG->libdir}/formslib.php");

/**
 * Add/edit one video of the playlist.
 */
class video_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'videoid');
        $mform->setType('videoid', PARAM_INT);
        $mform->addElement('text', 'title', get_string('videotitle', 'lecturevideos'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addElement(
            'textarea',
            'description',
            get_string('videodescription', 'lecturevideos'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement(
            'filemanager',
            'videofile',
            get_string('videofile', 'lecturevideos'),
            null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.mp4', '.m4v', '.mov', '.webm']]
        );
        $mform->addRule('videofile', null, 'required', null, 'client');
        $mform->addHelpButton('videofile', 'videofile', 'lecturevideos');
        $mform->addElement(
            'filemanager',
            'posterfile',
            get_string('posterfile', 'lecturevideos'),
            null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => \mod_lecturevideos\local\videos::POSTER_TYPES]
        );
        $mform->addHelpButton('posterfile', 'posterfile', 'lecturevideos');
        $mform->addElement(
            'filemanager',
            'captionfiles',
            get_string('captionfiles', 'lecturevideos'),
            null,
            ['subdirs' => 0, 'maxfiles' => 10, 'accepted_types' => ['.vtt']]
        );
        $mform->addHelpButton('captionfiles', 'captionfiles', 'lecturevideos');
        $mform->addElement('text', 'minpercent', get_string('minpercent', 'lecturevideos'), ['size' => 5]);
        $mform->setType('minpercent', PARAM_INT);
        $mform->setDefault('minpercent', 90);
        $mform->addHelpButton('minpercent', 'minpercent', 'lecturevideos');
        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'lecturevideos'));
        $mform->setDefault('enabled', 1);
        $this->add_action_buttons();
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ((int)$data['minpercent'] < 1 || (int)$data['minpercent'] > 100) {
            $errors['minpercent'] = get_string('errorpercent', 'lecturevideos');
        }
        $info = file_get_draft_area_info((int)($data['videofile'] ?? 0));
        if ((int)$info['filecount'] < 1) {
            $errors['videofile'] = get_string('required');
        }
        $captiondraft = (int)($data['captionfiles'] ?? 0);
        if ($captiondraft) {
            global $USER;
            $usercontext = \context_user::instance($USER->id);
            $drafts = get_file_storage()->get_area_files($usercontext->id, 'user', 'draft', $captiondraft, 'filename', false);
            foreach ($drafts as $file) {
                if (!preg_match(\mod_lecturevideos\local\videos::CAPTION_NAME, $file->get_filename())) {
                    $errors['captionfiles'] = get_string('errorcaptionname', 'lecturevideos', s($file->get_filename()));
                    break;
                }
            }
        }
        return $errors;
    }
}
