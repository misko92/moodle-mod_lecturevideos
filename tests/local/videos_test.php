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

/**
 * Tests for caption (subtitle) files.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(videos::class)]
final class videos_test extends \advanced_testcase {
    /** @var \context_module */
    private $context;
    /** @var \stdClass */
    private $video;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lecturevideos');
        $playlist = $generator->create_instance(['course' => $course->id]);
        $this->context = \context_module::instance(get_coursemodule_from_instance('lecturevideos', $playlist->id)->id);
        $this->video = $generator->create_video($playlist->id, 60);
    }

    /**
     * Store a small caption file for a language.
     *
     * @param string $lang
     */
    private function add_caption(string $lang): void {
        $path = make_request_directory() . '/c.vtt';
        file_put_contents($path, "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n");
        videos::set_caption_from_path($this->context, $this->video, $path, $lang);
    }

    /**
     * Pick out the default track's language from a captions() result.
     *
     * @param array $tracks
     * @return string|null
     */
    private function default_lang(array $tracks): ?string {
        foreach ($tracks as $t) {
            if ($t['default']) {
                return $t['srclang'];
            }
        }
        return null;
    }

    public function test_no_captions(): void {
        $this->assertSame([], videos::captions($this->context, $this->video->id, 'es_mx'));
    }

    public function test_default_follows_viewer_language(): void {
        $this->add_caption('en');
        $this->add_caption('es');
        $tracks = videos::captions($this->context, $this->video->id, 'en');
        $this->assertCount(2, $tracks);
        $this->assertSame(['English', 'Español'], array_column($tracks, 'label'));
        // English viewers: captions available but off by default.
        $this->assertNull($this->default_lang($tracks));
        // Mexican Spanish viewers get the general Spanish track.
        $this->assertSame('es', $this->default_lang(videos::captions($this->context, $this->video->id, 'es_mx')));
        $this->assertSame('es', $this->default_lang(videos::captions($this->context, $this->video->id, 'es')));
        // No track in the viewer's language: none is forced on.
        $this->assertNull($this->default_lang(videos::captions($this->context, $this->video->id, 'fr')));
    }

    public function test_exact_regional_track_wins(): void {
        $this->add_caption('es');
        $this->add_caption('es_mx');
        $this->assertSame('es-mx', $this->default_lang(videos::captions($this->context, $this->video->id, 'es_mx')));
        $this->assertSame('es', $this->default_lang(videos::captions($this->context, $this->video->id, 'es')));
    }

    public function test_replace_and_delete(): void {
        $this->add_caption('en');
        $this->add_caption('en');
        $this->assertCount(1, videos::captions($this->context, $this->video->id, 'en'));
        videos::delete($this->context, $this->video);
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_lecturevideos', 'captions', $this->video->id, '', false);
        $this->assertEmpty($files);
    }

    public function test_bad_language_rejected(): void {
        $this->expectException(\coding_exception::class);
        $this->add_caption('../x');
    }
}
