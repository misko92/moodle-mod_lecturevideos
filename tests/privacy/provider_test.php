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

namespace mod_lecturevideos\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_lecturevideos\progress_manager;

/**
 * Privacy provider tests.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_lecturevideos\privacy\provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Course, activity and two students who each watched a little.
     *
     * @return array [context, cm, student1, student2]
     */
    private function setup_data(): array {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $s1 = $gen->create_and_enrol($course, 'student');
        $s2 = $gen->create_and_enrol($course, 'student');
        $plugingen = $gen->get_plugin_generator('mod_lecturevideos');
        $playlist = $plugingen->create_instance(['course' => $course->id]);
        $video = $plugingen->create_video($playlist->id, 60);
        $cm = get_coursemodule_from_instance('lecturevideos', $playlist->id);
        foreach ([$s1, $s2] as $student) {
            (new progress_manager())->update($playlist, $video, $cm, $student->id, ['sessionkey' => str_repeat('k', 32),
                'sequence' => 1, 'segmentstart' => 0, 'segmentend' => 5, 'currentposition' => 5, 'duration' => 60,
                'playbackrate' => 1.0]);
        }
        return [\context_module::instance($cm->id), $cm, $s1, $s2];
    }

    public function test_contexts_and_export(): void {
        [$context, , $s1] = $this->setup_data();
        $contextlist = provider::get_contexts_for_userid($s1->id);
        $this->assertEquals([$context->id], $contextlist->get_contextids());
        provider::export_user_data(new approved_contextlist($s1, 'mod_lecturevideos', [$context->id]));
        $this->assertTrue(writer::with_context($context)->has_any_data());
    }

    public function test_users_in_context_and_delete_for_users(): void {
        global $DB;
        [$context, $cm, $s1, $s2] = $this->setup_data();
        $userlist = new userlist($context, 'mod_lecturevideos');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$s1->id, $s2->id], $userlist->get_userids());

        provider::delete_data_for_users(new approved_userlist($context, 'mod_lecturevideos', [$s1->id]));
        $this->assertFalse($DB->record_exists('lecturevideos_progress', ['userid' => $s1->id]));
        $this->assertFalse($DB->record_exists('lecturevideos_sessions', ['userid' => $s1->id]));
        $this->assertTrue($DB->record_exists('lecturevideos_progress', ['userid' => $s2->id]));
    }

    public function test_delete_all_in_context(): void {
        global $DB;
        [$context, $cm] = $this->setup_data();
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals(0, $DB->count_records('lecturevideos_progress', ['playlistid' => $cm->instance]));
    }
}
