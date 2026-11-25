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

$choices = \mod_lecturevideos\local\review_link::resolve_all($targets);
if (count($choices) === 1) {
    redirect($choices[0]['url']);
}

$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('modulename', 'mod_lecturevideos'));
echo $OUTPUT->header();
if ($choices) {
    // Several destinations are open to this user (a teacher sees every course): let them pick.
    echo $OUTPUT->heading(get_string('gotochoose', 'mod_lecturevideos'));
    $items = [];
    foreach ($choices as $c) {
        $label = $c['course'] . ': ' . $c['activity'] . ($c['video'] !== '' ? ' › ' . $c['video'] : '') .
            ($c['start'] !== '' ? ' (' . $c['start'] . ')' : '');
        $items[] = html_writer::link($c['url'], $label); // The parts are already formatted (escaped) by format_string().
    }
    echo html_writer::alist($items);
} else {
    // None of the destinations is open to this user (e.g. a question shared by two courses): say so politely.
    echo $OUTPUT->notification(get_string('gotonotfound', 'mod_lecturevideos'), \core\output\notification::NOTIFY_INFO);
}
$back = get_local_referer(false);
echo $OUTPUT->continue_button($back ? new moodle_url($back) : new moodle_url('/my/'));
echo $OUTPUT->footer();
