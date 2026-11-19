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
 * Add many videos to a Lecture videos activity from the command line.
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
    ['cmid' => 0, 'dir' => '', 'csv' => '', 'minpercent' => 90, 'replace' => false, 'dry-run' => false, 'help' => false],
    ['h' => 'help', 'n' => 'dry-run']
);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognised)));
}
if ($options['help'] || !$options['cmid'] || (!$options['dir'] && !$options['csv'])) {
    echo "Add videos to a Lecture videos activity.

Options:
  --cmid=N          Course module id of the activity (required)
  --dir=PATH        Add every .mp4/.m4v/.mov/.webm in PATH, in natural filename order.
                    Title = filename without extension. A same-named .jpg/.jpeg/.png/.webp
                    next to a video becomes its preview image, and same-named
                    <name>.en.vtt / <name>.es.vtt files become its subtitles.
  --csv=FILE        Instead of --dir: rows of title,videopath[,posterpath] (no header).
  --minpercent=N    'Counts as watched at' for each video (default 90)
  --replace         Delete the activity's existing videos (and their progress) first
  -n, --dry-run     Show what would be added
  -h, --help        This help

Example:
  php mod/lecturevideos/cli/add_videos.php --cmid=123 --dir=/path/to/lecture01
";
    exit($options['help'] ? 0 : 1);
}

$cm = get_coursemodule_from_id('lecturevideos', (int)$options['cmid'], 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
\core\session\manager::set_user(get_admin());

$rows = [];
if ($options['csv']) {
    $fh = fopen($options['csv'], 'r') ?: cli_error('Cannot read ' . $options['csv']);
    while (($r = fgetcsv($fh, null, ',', '"', '')) !== false) {
        if (count($r) >= 2 && trim($r[0]) !== '') {
            $rows[] = ['title' => trim($r[0]), 'video' => $r[1], 'poster' => $r[2] ?? ''];
        }
    }
} else {
    $dir = rtrim($options['dir'], '/');
    $found = glob($dir . '/*.{mp4,m4v,mov,webm,MP4,M4V,MOV,WEBM}', GLOB_BRACE) ?: [];
    natcasesort($found);
    foreach ($found as $path) {
        $base = substr($path, 0, strrpos($path, '.'));
        $poster = '';
        foreach (videos::POSTER_TYPES as $ext) {
            if (is_readable($base . $ext)) {
                $poster = $base . $ext;
                break;
            }
        }
        $captions = [];
        foreach (glob(str_replace(['[', ']', '*', '?'], ['\\[', '\\]', '\\*', '\\?'], $base) . '.*.vtt') ?: [] as $vtt) {
            $lang = substr($vtt, strlen($base) + 1, -4);
            if (preg_match(videos::CAPTION_NAME, $lang . '.vtt')) {
                $captions[$lang] = $vtt;
            }
        }
        $rows[] = ['title' => basename($base), 'video' => $path, 'poster' => $poster, 'captions' => $captions];
    }
}
if (!$rows) {
    cli_error('No videos found.');
}
foreach ($rows as $r) {
    if (!is_readable($r['video'])) {
        cli_error('Cannot read ' . $r['video']);
    }
    if ($r['poster'] !== '' && !is_readable($r['poster'])) {
        cli_error('Cannot read ' . $r['poster']);
    }
}

if ($options['replace'] && !$options['dry-run']) {
    foreach ($DB->get_records('lecturevideos_videos', ['playlistid' => $cm->instance]) as $old) {
        videos::delete($context, $old);
    }
}
foreach ($rows as $r) {
    $line = sprintf(
        '%s  [%s%s%s]',
        $r['title'],
        basename($r['video']),
        $r['poster'] ? ' + ' . basename($r['poster']) : '',
        empty($r['captions']) ? '' : ' + subtitles ' . implode(', ', array_keys($r['captions']))
    );
    if ($options['dry-run']) {
        cli_writeln('would add ' . $line);
        continue;
    }
    $video = videos::add($cm->instance, $r['title'], (int)$options['minpercent']);
    videos::set_file_from_path($context, $video, $r['video'], 'video');
    if ($r['poster'] !== '') {
        videos::set_file_from_path($context, $video, $r['poster'], 'poster');
    }
    foreach ($r['captions'] ?? [] as $lang => $vtt) {
        videos::set_caption_from_path($context, $video, $vtt, $lang);
    }
    cli_writeln(sprintf('added %s (%s)', $line, videos::format_duration((float)$video->duration) ?: 'length unknown'));
}
rebuild_course_cache($cm->course, true);
