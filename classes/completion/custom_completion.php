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
 * Custom completion implementation for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_socratic\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Calculates the state of the single dynamic Socratic completion condition.
     *
     * @param string $rule
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $socratic = $DB->get_record('socratic', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $conversation = $DB->get_record('socratic_conversations', [
            'socraticid' => $socratic->id,
            'userid' => $this->userid,
        ]);
        if (!$conversation) {
            return COMPLETION_INCOMPLETE;
        }

        $complete = false;
        switch ($socratic->completioncriterion) {
            case 'interactions':
                $complete = (int)$conversation->interactioncount >= (int)$socratic->completioninteractions;
                break;
            case 'ended':
                $complete = $conversation->status === 'completed';
                break;
            case 'teacher':
                $complete = !empty($conversation->teachercompletedat);
                break;
        }
        return $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns the custom rule identifiers.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completioncondition'];
    }

    /**
     * Returns descriptions for active custom rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $socratic = $DB->get_record('socratic', ['id' => $this->cm->instance], '*', MUST_EXIST);
        switch ($socratic->completioncriterion) {
            case 'interactions':
                $description = get_string('completiondetail:interactions', 'mod_socratic', $socratic->completioninteractions);
                break;
            case 'ended':
                $description = get_string('completiondetail:ended', 'mod_socratic');
                break;
            case 'teacher':
                $description = get_string('completiondetail:teacher', 'mod_socratic');
                break;
            default:
                $description = '';
        }
        return ['completioncondition' => $description];
    }

    /**
     * Sort order among Moodle completion rules.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionview', 'completioncondition'];
    }
}
