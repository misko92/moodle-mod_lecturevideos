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
 * Tests for site-independent review links (goto.php).
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(review_link::class)]
final class review_link_test extends \advanced_testcase {
    /**
     * Make an activity with an ID number and two titled videos.
     *
     * @param \stdClass $course
     * @param string $idnumber
     * @param bool $visible
     * @return array [cm, video 1, video 2]
     */
    private function make_activity(\stdClass $course, string $idnumber, bool $visible = true): array {
        global $DB;
        $gen = $this->getDataGenerator()->get_plugin_generator('mod_lecturevideos');
        $playlist = $gen->create_instance(['course' => $course->id, 'idnumber' => $idnumber, 'visible' => (int)$visible]);
        $v1 = $gen->create_video($playlist->id, 600);
        $v2 = $gen->create_video($playlist->id, 600);
        $DB->set_field('lecturevideos_videos', 'title', '31.01 Ka and Kb', ['id' => $v1->id]);
        $DB->set_field('lecturevideos_videos', 'title', '31.02 Sample Calculations Part 1', ['id' => $v2->id]);
        return [get_coursemodule_from_instance('lecturevideos', $playlist->id), $v1, $v2];
    }

    public function test_resolve_finds_activity_video_and_time(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        [$cm, , $v2] = $this->make_activity($course, 'lv-ap-31');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));

        $url = review_link::resolve('lv-ap-31', '31.02', '5:07');
        $this->assertSame((string)$cm->id, $url->param('id'));
        $this->assertSame((string)$v2->id, $url->param('video'));
        $this->assertSame('5:07', $url->param('t'));

        // Title prefix must end at a word: "31.0" is not "31.02".
        $url = review_link::resolve('lv-ap-31', '31.0', '307');
        $this->assertNull($url->param('video'));
        $this->assertNull($url->param('t'));

        // No video: the activity itself.
        $url = review_link::resolve('lv-ap-31', '', '');
        $this->assertSame((string)$cm->id, $url->param('id'));
        $this->assertNull($url->param('video'));
    }

    public function test_resolve_skips_what_the_user_cannot_open(): void {
        $this->resetAfterTest();
        $other = $this->getDataGenerator()->create_course();
        $mine = $this->getDataGenerator()->create_course();
        $this->make_activity($other, 'lv-ap-31');
        [$hidden] = $this->make_activity($mine, 'lv-ap-32', false);
        [$cm] = $this->make_activity($mine, 'lv-ap-31');
        $this->setUser($this->getDataGenerator()->create_and_enrol($mine, 'student'));

        // The copy in the course the student is not in is skipped.
        $this->assertSame((string)$cm->id, review_link::resolve('lv-ap-31', '31.01', '')->param('id'));
        $this->assertNull(review_link::resolve('lv-ap-32', '', ''));
        $this->assertNull(review_link::resolve('lv-nothing', '', ''));
        $this->assertNotEmpty($hidden->id);
    }

    public function test_clean_start(): void {
        $this->assertSame('307', review_link::clean_start(' 307 '));
        $this->assertSame('5m07s', review_link::clean_start('5m07s'));
        $this->assertSame('1:02:03', review_link::clean_start('1:02:03'));
        $this->assertSame('', review_link::clean_start('5:07"><script>'));
        $this->assertSame('', review_link::clean_start(''));
    }
}
