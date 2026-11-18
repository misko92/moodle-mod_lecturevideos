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

namespace mod_lecturevideos;

/**
 * Tests for watch tracking: honest viewing is credited, faked reports are not.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_lecturevideos\progress_manager::class)]
final class progress_manager_test extends \advanced_testcase {
    /** @var \stdClass */
    private $playlist;
    /** @var \stdClass */
    private $cm;
    /** @var \stdClass */
    private $student;
    /** @var \mod_lecturevideos_generator */
    private $generator;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->generator = $this->getDataGenerator()->get_plugin_generator('mod_lecturevideos');
        $this->playlist = $this->generator->create_instance(['course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $this->cm = get_coursemodule_from_instance('lecturevideos', $this->playlist->id);
    }

    /**
     * Send one progress report.
     *
     * @param \stdClass $video
     * @param string $key
     * @param int $seq
     * @param float $start
     * @param float $end
     * @param float|null $duration Length reported by the browser.
     * @return array
     */
    private function report(\stdClass $video, string $key, int $seq, float $start, float $end, ?float $duration = null): array {
        global $DB;
        $playlist = $DB->get_record('lecturevideos', ['id' => $this->playlist->id]);
        return (new progress_manager())->update($playlist, $video, $this->cm, $this->student->id, [
            'sessionkey' => $key, 'sequence' => $seq, 'segmentstart' => $start, 'segmentend' => $end,
            'currentposition' => $end, 'duration' => $duration ?? (float)$video->duration, 'playbackrate' => 1.0,
        ]);
    }

    /**
     * Pretend $seconds have passed since the last report.
     *
     * @param \stdClass $video
     * @param int $seconds
     */
    private function wait(\stdClass $video, int $seconds): void {
        global $DB;
        $DB->execute(
            'UPDATE {lecturevideos_progress} SET timemodified = timemodified - ? WHERE videoid = ? AND userid = ?',
            [$seconds, $video->id, $this->student->id]
        );
    }

    public function test_honest_viewing_is_credited_in_full(): void {
        $video = $this->generator->create_video($this->playlist->id, 100);
        $key = str_repeat('a', 32);
        $this->report($video, $key, 1, 0, 0);
        for ($i = 0; $i < 20; $i++) {
            $this->wait($video, 5);
            $result = $this->report($video, $key, $i + 2, $i * 5, $i * 5 + 5);
        }
        $this->assertEquals(100.0, $result['percent']);
    }

    public function test_rapid_requests_with_new_session_keys_are_capped(): void {
        $video = $this->generator->create_video($this->playlist->id, 400);
        for ($i = 0; $i < 50; $i++) {
            $result = $this->report($video, bin2hex(random_bytes(16)), 1, $i * 4, $i * 4 + 4);
        }
        // Only the starting allowance (plus at most a couple of real seconds) can be credited.
        $this->assertLessThan(3.0, $result['percent']);
    }

    public function test_rapid_requests_in_one_session_are_capped(): void {
        $video = $this->generator->create_video($this->playlist->id, 400);
        $key = str_repeat('b', 32);
        for ($i = 0; $i < 50; $i++) {
            $result = $this->report($video, $key, $i + 1, $i * 4, $i * 4 + 4);
        }
        $this->assertLessThan(3.0, $result['percent']);
    }

    public function test_reported_length_is_ignored_when_stored(): void {
        $video = $this->generator->create_video($this->playlist->id, 500);
        $result = $this->report($video, str_repeat('c', 32), 1, 0, 5, 5.0);
        $this->assertEquals(1.0, $result['percent']);
    }

    public function test_completion_requires_every_video(): void {
        $first = $this->generator->create_video($this->playlist->id, 20);
        $second = $this->generator->create_video($this->playlist->id, 20);
        $completion = new \completion_info(get_course($this->cm->course));
        foreach ([$first, $second] as $n => $video) {
            $key = str_repeat((string)$n, 32);
            $this->report($video, $key, 1, 0, 0);
            for ($i = 0; $i < 4; $i++) {
                $this->wait($video, 5);
                $this->report($video, $key, $i + 2, $i * 5, $i * 5 + 5);
            }
            $state = $completion->get_data($this->cm, false, $this->student->id)->completionstate;
            $this->assertEquals($n === 0 ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE, $state);
        }
    }
}
