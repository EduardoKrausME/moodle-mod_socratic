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
 * Restore task for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_socratic_activity_task extends restore_activity_task {
    /**
     * No custom task settings are required.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Adds the activity restore structure step.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_socratic_activity_structure_step('socratic_structure', 'socratic.xml'));
    }

    /**
     * Defines content fields that can contain links.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents(): array {
        return [
            new restore_decode_content('socratic', ['intro', 'objective', 'contentbase', 'tutorbehavior'], 'socratic'),
        ];
    }

    /**
     * Defines URL decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules(): array {
        return [
            new restore_decode_rule('SOCRATICVIEWBYID', '/mod/socratic/view.php?id=$1', 'course_module'),
            new restore_decode_rule('SOCRATICINDEX', '/mod/socratic/index.php?id=$1', 'course'),
        ];
    }
}
