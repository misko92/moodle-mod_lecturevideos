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
 * Attach subtitle (.vtt) files to the videos of a Lecture videos activity from the command line.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use mod_lecturevideos\local\videos;

[$options, $unrecognised] = cli_get_params(
    ['cmid' => 0, 'dir' => '', 'dry-run' => false, 'help' => false],
    ['h' => 'help', 'n' => 'dry-run']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || !$options['cmid'] || !$options['dir']) {
    echo "Attach subtitle files to the videos of a Lecture videos activity.

Each video is matched by the name of its stored video file: for a video stored as
'1.01 History of the Atom.mp4', the files '1.01 History of the Atom.en.vtt' and
'1.01 History of the Atom.es.vtt' in --dir become its English and Spanish subtitles.
An existing subtitle file for the same language is replaced.

Options:
  --cmid=N          Course module id of the activity (required)
  --dir=PATH        Folder with the <video name>.<lang>.vtt files (required)
  -n, --dry-run     Show what would be attached
  -h, --help        This help

Example:
  php mod/lecturevideos/cli/add_captions.php --cmid=123 --dir=/path/to/captions
";
    exit($options['help'] ? 0 : 1);
}

$cm = get_coursemodule_from_id('lecturevideos', (int)$options['cmid'], 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$dir = rtrim($options['dir'], '/');
if (!is_dir($dir)) {
    cli_error('Not a folder: ' . $dir);
}
\core\session\manager::set_user(get_admin());

$attached = 0;
$missing = [];
foreach ($DB->get_records('lecturevideos_videos', ['playlistid' => $cm->instance], 'sortorder') as $video) {
    $file = videos::get_file($context, $video->id, 'video');
    if (!$file) {
        continue;
    }
    $base = pathinfo($file->get_filename(), PATHINFO_FILENAME);
    $found = glob($dir . '/' . str_replace(['[', ']', '*', '?'], ['\[', '\]', '\*', '\?'], $base) . '.*.vtt') ?: [];
    $count = 0;
    foreach ($found as $path) {
        $lang = substr(basename($path), strlen($base) + 1, -4);
        if (!preg_match(videos::CAPTION_NAME, $lang . '.vtt')) {
            continue;
        }
        if (!$options['dry-run']) {
            videos::set_caption_from_path($context, $video, $path, $lang);
        }
        cli_writeln(($options['dry-run'] ? 'would attach ' : 'attached ') . "$lang  {$video->title}");
        $count++;
    }
    $attached += $count;
    if (!$count) {
        $missing[] = $video->title;
    }
}
cli_writeln("$attached subtitle file(s)" . ($options['dry-run'] ? ' would be attached.' : ' attached.'));
if ($missing) {
    cli_writeln('No subtitles found for: ' . implode('; ', $missing));
}
