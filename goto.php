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

/**
 * Site-independent link to a moment in a lecture video, e.g. from a quiz question's feedback.
 *
 * goto.php?cm=<activity ID number>&v=<start of the video title>&t=<start time>[&cm2=…&v2=…&t2=…][&cm3=…], e.g.
 * goto.php?cm=lv-ap-31&v=31.02&t=5:07 — see \mod_lecturevideos\local\review_link.
 *
 * @package    mod_lecturevideos
 * @copyright  2026 misko92
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$targets = \mod_lecturevideos\local\review_link::targets_from_request();
if (!$targets) {
    required_param('cm', PARAM_ALPHANUMEXT);
}

require_login(null, false);
$PAGE->set_url('/mod/lecturevideos/goto.php', ['cm' => $targets[0][0]]);
$PAGE->set_context(context_system::instance());

$url = \mod_lecturevideos\local\review_link::resolve_first($targets);
if ($url) {
    redirect($url);
}

// None of the destinations is open to this user (e.g. a question shared by two courses): say so politely.
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('modulename', 'mod_lecturevideos'));
echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('gotonotfound', 'mod_lecturevideos'), \core\output\notification::NOTIFY_INFO);
$back = get_local_referer(false);
echo $OUTPUT->continue_button($back ? new moodle_url($back) : new moodle_url('/my/'));
echo $OUTPUT->footer();
