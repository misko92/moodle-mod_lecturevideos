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
 * mod_form.php
 *
 * @package   mod_lecturevideos
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Class mod_lecturevideos_mod_form.
 */
class mod_lecturevideos_mod_form extends moodleform_mod {
    /**
     * Method definition.
     *
     * @return void Return value.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('lecturevideosname', 'lecturevideos'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();
        $mform->addElement('html', '<h3>' . get_string('playlistsettings', 'lecturevideos') . '</h3>');
        $mform->addElement('selectyesno', 'sequential', get_string('sequential', 'lecturevideos'));
        $mform->setDefault('sequential', 0);
        $mform->addElement('selectyesno', 'allowseek', get_string('allowseek', 'lecturevideos'));
        $mform->setDefault('allowseek', 1);
        $mform->addElement('select', 'resumeplayback', get_string('resumeplayback', 'lecturevideos'), [
            1 => get_string('resumeautomatic', 'lecturevideos'),
            0 => get_string('resumefromstart', 'lecturevideos'),
        ]);
        $mform->setDefault('resumeplayback', 1);
        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 0);
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Method add_completion_rules.
     *
     * @return array Return value.
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $suffix = '_lecturevideos';
        $group = [
            $mform->createElement('select', 'completionmode' . $suffix, '', [
                1 => get_string('completionmodeeach', 'lecturevideos'),
                0 => get_string('completionmodeaverage', 'lecturevideos'),
            ]),
            $mform->createElement('text', 'completionpercent' . $suffix, '', ['size' => 4]),
            $mform->createElement('static', 'completionpercentsign' . $suffix, '', '%'),
        ];
        $mform->addGroup($group, 'completiongroup' . $suffix, get_string('completionpercent', 'lecturevideos'), ' ', false);
        $mform->addHelpButton('completiongroup' . $suffix, 'completionpercent', 'lecturevideos');
        $mform->setType('completionpercent' . $suffix, PARAM_INT);
        $mform->setDefault('completionpercent' . $suffix, 90);
        $mform->setDefault('completionmode' . $suffix, 1);
        return ['completiongroup' . $suffix];
    }

    /**
     * Method completion_rule_enabled.
     *
     * @param mixed $data Parameter data.
     * @return bool Return value.
     */
    public function completion_rule_enabled($data): bool {
        return !empty($data['completionpercent_lecturevideos']);
    }

    /**
     * Method data_preprocessing.
     *
     * @param mixed $defaultvalues Parameter defaultvalues.
     * @return void Return value.
     */
    public function data_preprocessing(&$defaultvalues): void {
        foreach (['completionpercent', 'completionmode'] as $field) {
            if (array_key_exists($field, $defaultvalues)) {
                $defaultvalues[$field . '_lecturevideos'] = $defaultvalues[$field];
            }
        }
    }

    /**
     * Method get_data.
     *
     * @return mixed Return value.
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            foreach (['completionpercent', 'completionmode'] as $field) {
                if (property_exists($data, $field . '_lecturevideos')) {
                    $data->$field = $data->{$field . '_lecturevideos'};
                    unset($data->{$field . '_lecturevideos'});
                }
            }
        }
        return $data;
    }

    /**
     * Method validation.
     *
     * @param mixed $data Parameter data.
     * @param mixed $files Parameter files.
     * @return array Return value.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (
            isset($data['completionpercent_lecturevideos']) &&
            ((int)$data['completionpercent_lecturevideos'] < 1 || (int)$data['completionpercent_lecturevideos'] > 100)
        ) {
            $errors['completionpercent_lecturevideos'] = get_string('errorpercent', 'lecturevideos');
        }
        return $errors;
    }
}
