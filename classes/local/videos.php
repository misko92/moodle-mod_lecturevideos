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

namespace mod_lecturevideos\local;

use context_module;
use moodle_url;
use stdClass;

/**
 * Video records and their files (video + optional poster image).
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class videos {
    /** @var string[] Accepted poster image extensions. */
    public const POSTER_TYPES = ['.jpg', '.jpeg', '.png', '.webp'];

    /** @var string Caption file names: a language code plus .vtt, e.g. en.vtt, es.vtt, es_mx.vtt. */
    public const CAPTION_NAME = '/^([a-z]{2,3})(_[a-z0-9]+)?\.vtt$/';

    /**
     * Add a video record at the end of the playlist.
     *
     * @param int $playlistid
     * @param string $title
     * @param int $minpercent
     * @param string $description
     * @return stdClass
     */
    public static function add(int $playlistid, string $title, int $minpercent = 90, string $description = ''): stdClass {
        global $DB;
        $max = $DB->get_field_sql(
            'SELECT COALESCE(MAX(sortorder), -1) FROM {lecturevideos_videos} WHERE playlistid = ?',
            [$playlistid]
        );
        $now = time();
        $video = (object)['playlistid' => $playlistid, 'title' => $title, 'description' => $description,
            'source' => 'upload', 'sourceurl' => '', 'minpercent' => $minpercent, 'duration' => 0,
            'sortorder' => ((int)$max) + 1, 'enabled' => 1, 'timecreated' => $now, 'timemodified' => $now];
        $video->id = $DB->insert_record('lecturevideos_videos', $video);
        return $video;
    }

    /**
     * Store a file from disk as the video (replacing any existing one). Used by the CLI loader.
     *
     * @param context_module $context
     * @param stdClass $video
     * @param string $path
     * @param string $area 'video' or 'poster'
     */
    public static function set_file_from_path(context_module $context, stdClass $video, string $path, string $area): void {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_lecturevideos', $area, $video->id);
        $fs->create_file_from_pathname(['contextid' => $context->id, 'component' => 'mod_lecturevideos',
            'filearea' => $area, 'itemid' => $video->id, 'filepath' => '/', 'filename' => basename($path)], $path);
        if ($area === 'video') {
            self::refresh_duration($context, $video);
        }
    }

    /**
     * Read the video length from the stored file and save it on the record.
     *
     * @param context_module $context
     * @param stdClass $video
     */
    public static function refresh_duration(context_module $context, stdClass $video): void {
        global $DB;
        $file = self::get_file($context, $video->id, 'video');
        $video->duration = $file ? mp4::duration($file) : 0;
        $DB->set_field('lecturevideos_videos', 'duration', $video->duration, ['id' => $video->id]);
    }

    /**
     * The single stored file in an area for a video.
     *
     * @param context_module $context
     * @param int $videoid
     * @param string $area
     * @return \stored_file|null
     */
    public static function get_file(context_module $context, int $videoid, string $area): ?\stored_file {
        $files = get_file_storage()->get_area_files($context->id, 'mod_lecturevideos', $area, $videoid, 'filename', false);
        return $files ? reset($files) : null;
    }

    /**
     * Pluginfile URL for a video's file, or '' if none.
     *
     * @param context_module $context
     * @param int $videoid
     * @param string $area
     * @return string
     */
    public static function url(context_module $context, int $videoid, string $area): string {
        $file = self::get_file($context, $videoid, $area);
        if (!$file) {
            return '';
        }
        return moodle_url::make_pluginfile_url(
            $context->id,
            'mod_lecturevideos',
            $area,
            $videoid,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
    }

    /**
     * Store a WebVTT caption file from disk (replacing one for the same language). Used by the CLI loaders.
     *
     * @param context_module $context
     * @param stdClass $video
     * @param string $path
     * @param string $lang Language code, e.g. en, es or es_mx.
     */
    public static function set_caption_from_path(context_module $context, stdClass $video, string $path, string $lang): void {
        $filename = $lang . '.vtt';
        if (!preg_match(self::CAPTION_NAME, $filename)) {
            throw new \coding_exception('Invalid caption language: ' . $lang);
        }
        $fs = get_file_storage();
        $record = ['contextid' => $context->id, 'component' => 'mod_lecturevideos', 'filearea' => 'captions',
            'itemid' => $video->id, 'filepath' => '/', 'filename' => $filename];
        if ($old = $fs->get_file(...array_values($record))) {
            $old->delete();
        }
        $fs->create_file_from_pathname($record, $path);
    }

    /**
     * The caption tracks of a video, for the <track> elements of the player.
     *
     * The track matching the viewer's language is marked as default (shown straight away), except
     * for English viewers: like YouTube, English captions are available but start switched off.
     *
     * @param context_module $context
     * @param int $videoid
     * @param string $userlang The viewer's current language, e.g. en or es_mx.
     * @return array[] Each: src, srclang, label, default.
     */
    public static function captions(context_module $context, int $videoid, string $userlang): array {
        $files = get_file_storage()->get_area_files($context->id, 'mod_lecturevideos', 'captions', $videoid, 'filename', false);
        $tracks = [];
        foreach ($files as $file) {
            if (!preg_match(self::CAPTION_NAME, $file->get_filename(), $m)) {
                continue;
            }
            $lang = $m[1] . ($m[2] ?? '');
            $url = moodle_url::make_pluginfile_url(
                $context->id,
                'mod_lecturevideos',
                'captions',
                $videoid,
                '/',
                $file->get_filename()
            );
            $tracks[$lang] = [
                'src' => $url->out(false),
                'srclang' => str_replace('_', '-', $lang),
                'label' => self::language_label($m[1]),
                'default' => false,
            ];
        }
        $userbase = explode('_', $userlang)[0];
        if ($userbase !== 'en') {
            // Exact match first (es_mx.vtt for es_mx), else the same base language (es.vtt for es_mx).
            $pick = isset($tracks[$userlang]) ? $userlang : null;
            foreach (array_keys($tracks) as $lang) {
                if ($pick === null && explode('_', $lang)[0] === $userbase) {
                    $pick = $lang;
                }
            }
            if ($pick !== null) {
                $tracks[$pick]['default'] = true;
            }
        }
        return array_values($tracks);
    }

    /**
     * A language's name in that language, e.g. "English", "Español".
     *
     * @param string $code ISO 639 code.
     * @return string
     */
    public static function language_label(string $code): string {
        // Native names for common languages, so labels don't depend on which language packs are installed.
        $native = ['en' => 'English', 'es' => 'Español', 'fr' => 'Français', 'de' => 'Deutsch', 'pt' => 'Português',
            'it' => 'Italiano', 'vi' => 'Tiếng Việt', 'zh' => '中文', 'ko' => '한국어', 'ja' => '日本語', 'ar' => 'العربية',
            'ru' => 'Русский', 'uk' => 'Українська', 'tl' => 'Tagalog', 'hi' => 'हिन्दी', 'so' => 'Soomaali'];
        if (isset($native[$code])) {
            return $native[$code];
        }
        $names = get_string_manager()->get_list_of_languages($code);
        if (empty($names[$code])) {
            $names = get_string_manager()->get_list_of_languages('en');
        }
        $name = trim(explode(';', $names[$code] ?? $code)[0]);
        return \core_text::strtoupper(\core_text::substr($name, 0, 1)) . \core_text::substr($name, 1);
    }

    /**
     * Delete a video, its files and all progress for it.
     *
     * @param context_module $context
     * @param stdClass $video
     */
    public static function delete(context_module $context, stdClass $video): void {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_lecturevideos', 'video', $video->id);
        $fs->delete_area_files($context->id, 'mod_lecturevideos', 'poster', $video->id);
        $fs->delete_area_files($context->id, 'mod_lecturevideos', 'captions', $video->id);
        $DB->delete_records('lecturevideos_sessions', ['videoid' => $video->id]);
        $DB->delete_records('lecturevideos_progress', ['videoid' => $video->id]);
        $DB->delete_records('lecturevideos_videos', ['id' => $video->id]);
    }

    /**
     * Format seconds as m:ss or h:mm:ss.
     *
     * @param float $seconds
     * @return string
     */
    public static function format_duration(float $seconds): string {
        $s = (int)round($seconds);
        if ($s <= 0) {
            return '';
        }
        return $s >= 3600 ? sprintf('%d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60)
            : sprintf('%d:%02d', intdiv($s, 60), $s % 60);
    }
}
