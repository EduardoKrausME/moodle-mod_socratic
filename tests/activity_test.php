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
 * CRUD and capability tests for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace mod_socratic;

use advanced_testcase;

/**
 * CRUD and capability tests.
 *
 * @package mod_socratic
 * @coversNothing
 */
final class activity_test extends advanced_testcase {
    /**
     * Method setUp.
     *
     * @return void Return value.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Method test_activity_crud.
     *
     * @return void Return value.
     */
    public function test_activity_crud(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('socratic', [
            'course' => $course->id,
            'name' => 'Reasoning practice',
            'objective' => 'Defend an argument.',
            'contentbase' => 'Evidence A supports claim B.',
        ]);

        $record = $DB->get_record('socratic', ['id' => $activity->id], '*', MUST_EXIST);
        $this->assertSame('Reasoning practice', $record->name);
        $this->assertSame('Defend an argument.', $record->objective);

        $record->name = 'Updated reasoning practice';
        $record->instance = $record->id;
        $record->coursemodule = $activity->cmid;
        socratic_update_instance($record);
        $this->assertSame('Updated reasoning practice', $DB->get_field('socratic', 'name', ['id' => $activity->id]));

        $this->assertTrue(socratic_delete_instance((int)$activity->id));
        $this->assertFalse($DB->record_exists('socratic', ['id' => $activity->id]));
    }

    /**
     * Method test_capabilities_for_student_and_teacher.
     *
     * @return void Return value.
     */
    public function test_capabilities_for_student_and_teacher(): void {
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $activity = $this->getDataGenerator()->create_module('socratic', ['course' => $course->id]);
        $context = \context_module::instance($activity->cmid);

        $this->assertTrue(has_capability('mod/socratic:view', $context, $student));
        $this->assertFalse(has_capability('mod/socratic:viewallconversations', $context, $student));
        $this->assertTrue(has_capability('mod/socratic:viewallconversations', $context, $teacher));
    }
}
