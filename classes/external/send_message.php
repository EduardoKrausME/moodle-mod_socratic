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
 * External API for sending Socratic tutor messages.
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
 * AJAX endpoint for learner messages.
 *
 * @package mod_socratic
 */
class send_message extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'message' => new external_value(PARAM_RAW, 'Learner message'),
            'action' => new external_value(PARAM_ALPHA, 'message, hint or final'),
            'clientid' => new external_value(PARAM_ALPHANUMEXT, 'Client-side idempotency token'),
        ]);
    }

    /**
     * Executes the send.
     *
     * @param int $cmid
     * @param string $message
     * @param string $action
     * @param string $clientid
     * @return array
     */
    public static function execute(int $cmid, string $message, string $action, string $clientid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('cmid', 'message', 'action', 'clientid'));
        [$course, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'socratic');
        require_login($course, true, $cm);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/socratic:view', $context);

        $socratic = $DB->get_record('socratic', ['id' => $cm->instance], '*', MUST_EXIST);
        $service = new conversation_service();
        return $service->send(
            $socratic,
            $cm,
            (int)$USER->id,
            $params['message'],
            $params['action'],
            $params['clientid']
        );
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'conversationid' => new external_value(PARAM_INT, 'Conversation id'),
            'messageid' => new external_value(PARAM_INT, 'Persisted learner message id'),
            'assistant' => new external_value(PARAM_RAW, 'Assistant response'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the conversation is complete'),
            'interactioncount' => new external_value(PARAM_INT, 'Current interaction count'),
            'maxinteractions' => new external_value(PARAM_INT, 'Configured maximum interactions'),
        ]);
    }
}
