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
 * Privacy tests for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace mod_socratic;

use advanced_testcase;
use core_privacy\local\request\userlist;
use mod_socratic\privacy\provider;

/**
 * Privacy provider tests.
 *
 * @package mod_socratic
 * @covers \mod_socratic\privacy\provider
 */
final class privacy_provider_test extends advanced_testcase {
    /**
     * Method test_get_users_and_delete_all_in_context.
     *
     * @return void Return value.
     */
    public function test_get_users_and_delete_all_in_context(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $activity = $this->getDataGenerator()->create_module('socratic', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $conversationid = $DB->insert_record('socratic_conversations', (object)[
            'socraticid' => $activity->id,
            'userid' => $student->id,
            'status' => 'completed',
            'interactioncount' => 1,
            'teachercompletedby' => $teacher->id,
            'teachercompletedat' => time(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('socratic_messages', (object)[
            'conversationid' => $conversationid,
            'userid' => $student->id,
            'role' => 'user',
            'messagetype' => 'message',
            'content' => 'Private reasoning',
            'clientid' => 'privacy-test',
            'timecreated' => time(),
        ]);

        $userlist = new userlist($context, 'mod_socratic');
        provider::get_users_in_context($userlist);
        $ids = $userlist->get_userids();
        $this->assertContains((int)$student->id, $ids);
        $this->assertContains((int)$teacher->id, $ids);

        provider::delete_data_for_all_users_in_context($context);
        $this->assertFalse($DB->record_exists('socratic_conversations', ['id' => $conversationid]));
        $this->assertFalse($DB->record_exists('socratic_messages', ['conversationid' => $conversationid]));
    }

    /**
     * Method test_contexts_include_teacher_completion_reference.
     *
     * @return void Return value.
     */
    public function test_contexts_include_teacher_completion_reference(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $activity = $this->getDataGenerator()->create_module('socratic', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);
        $DB->insert_record('socratic_conversations', (object)[
            'socraticid' => $activity->id,
            'userid' => $student->id,
            'status' => 'completed',
            'interactioncount' => 1,
            'teachercompletedby' => $teacher->id,
            'teachercompletedat' => time(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $contexts = provider::get_contexts_for_userid((int)$teacher->id);
        $this->assertContains((int)$context->id, $contexts->get_contextids());
    }
}
