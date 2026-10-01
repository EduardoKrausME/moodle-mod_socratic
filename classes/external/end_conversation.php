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
 * External API for ending Socratic tutor conversations.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_socratic\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_socratic\local\conversation_service;

/**
 * AJAX endpoint for ending a learner conversation.
 *
 * @package mod_socratic
 */
class end_conversation extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    /**
     * Ends the current user's conversation.
     *
     * @param int $cmid
     * @return array
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'socratic');
        require_login($course, true, $cm);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/socratic:view', $context);

        $socratic = $DB->get_record('socratic', ['id' => $cm->instance], '*', MUST_EXIST);
        $service = new conversation_service();
        $conversation = $service->end($socratic, $cm, (int)$USER->id);
        return [
            'conversationid' => (int)$conversation->id,
            'completed' => $conversation->status === 'completed',
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'conversationid' => new external_value(PARAM_INT, 'Conversation id'),
            'completed' => new external_value(PARAM_BOOL, 'Whether it is complete'),
        ]);
    }
}
