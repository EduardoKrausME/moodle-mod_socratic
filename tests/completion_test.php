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
 * Completion tests for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace mod_socratic;

use advanced_testcase;
use mod_socratic\completion\custom_completion;

/**
 * Completion tests.
 *
 * @package mod_socratic
 * @covers \mod_socratic\completion\custom_completion
 */
final class completion_test extends advanced_testcase {
    public function test_interaction_completion_rule(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $activity = $this->getDataGenerator()->create_module('socratic', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncriterion' => 'interactions',
            'completioninteractions' => 2,
        ]);

        $conversationid = $DB->insert_record('socratic_conversations', (object)[
            'socraticid' => $activity->id,
            'userid' => $user->id,
            'status' => 'active',
            'interactioncount' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        rebuild_course_cache($course->id, true);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $completion = new custom_completion($cm, $user->id);
        $this->assertSame(COMPLETION_INCOMPLETE, $completion->get_state('completioncondition'));

        $DB->set_field('socratic_conversations', 'interactioncount', 2, ['id' => $conversationid]);
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completioncondition'));
    }

    public function test_teacher_completion_rule(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $activity = $this->getDataGenerator()->create_module('socratic', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncriterion' => 'teacher',
        ]);
        $DB->insert_record('socratic_conversations', (object)[
            'socraticid' => $activity->id,
            'userid' => $user->id,
            'status' => 'completed',
            'interactioncount' => 1,
            'teachercompletedby' => $teacher->id,
            'teachercompletedat' => time(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        rebuild_course_cache($course->id, true);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $completion = new custom_completion($cm, $user->id);
        $this->assertSame(COMPLETION_COMPLETE, $completion->get_state('completioncondition'));
    }
}
