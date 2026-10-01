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
 * Conversation service for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_socratic\local;

use completion_info;
use context_module;
use core_text;
use core\lock\lock_config;
use moodle_exception;
use Throwable;

/**
 * Owns conversation persistence, duplicate protection, rate limiting and completion updates.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class conversation_service {
    /** @var ai_client */
    private ai_client $aiclient;

    /** @var context_builder */
    private context_builder $contextbuilder;

    /**
     * Constructor.
     *
     * @param ai_client|null $aiclient
     * @param context_builder|null $contextbuilder
     */
    public function __construct(?ai_client $aiclient = null, ?context_builder $contextbuilder = null) {
        $this->aiclient = $aiclient ?? new ai_client();
        $this->contextbuilder = $contextbuilder ?? new context_builder();
    }

    /**
     * Returns the single conversation for this learner/activity, creating it when requested.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param int $userid
     * @param bool $create
     * @return \stdClass|null
     */
    public function get_conversation(\stdClass $socratic, \stdClass $cm, int $userid, bool $create = true): ?\stdClass {
        global $DB;

        $conversation = $DB->get_record('socratic_conversations', [
            'socraticid' => $socratic->id,
            'userid' => $userid,
        ]);
        if ($conversation || !$create) {
            return $conversation ?: null;
        }

        $now = time();
        $record = (object)[
            'socraticid' => $socratic->id,
            'userid' => $userid,
            'status' => 'active',
            'interactioncount' => 0,
            'completedreason' => null,
            'teachercompletedby' => null,
            'teachercompletedat' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('socratic_conversations', $record);

        $context = context_module::instance($cm->id);
        $event = \mod_socratic\event\conversation_started::create([
            'objectid' => $record->id,
            'context' => $context,
            'courseid' => $socratic->course,
            'relateduserid' => $userid,
        ]);
        $event->add_record_snapshot('socratic_conversations', $record);
        $event->trigger();
        return $record;
    }

    /**
     * Sends or retries one learner turn.
     *
     * The learner message is committed before the AI call, so bridge/provider failures
     * never make the learner lose what they submitted.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param int $userid
     * @param string $message
     * @param string $action
     * @param string $clientid
     * @return array
     */
    public function send(\stdClass $socratic, \stdClass $cm, int $userid, string $message,
            string $action, string $clientid): array {
        global $DB;

        $this->validate_action($socratic, $action);
        $clientid = clean_param($clientid, PARAM_ALPHANUMEXT);
        if ($clientid === '') {
            throw new \invalid_parameter_exception('Missing client idempotency token.');
        }
        $message = trim($message);
        if ($action === 'message' && $message === '') {
            throw new moodle_exception('empty_message', 'mod_socratic');
        }
        if ($action === 'hint') {
            $message = get_string('hint', 'mod_socratic');
        } else if ($action === 'final') {
            $message = get_string('finalanswer', 'mod_socratic');
        }
        $message = core_text::substr($message, 0, 12000);

        $factory = lock_config::get_lock_factory('mod_socratic_conversation');
        $lock = $factory->get_lock('activity:' . $socratic->id . ':user:' . $userid, 10);
        if (!$lock) {
            throw new moodle_exception('lockunavailable', 'mod_socratic');
        }

        try {
            $conversation = $this->get_conversation($socratic, $cm, $userid, true);
            if ($conversation->status !== 'active') {
                throw new moodle_exception('conversationended', 'mod_socratic');
            }

            $usermessage = $DB->get_record('socratic_messages', [
                'conversationid' => $conversation->id,
                'role' => 'user',
                'clientid' => $clientid,
            ]);

            if ($usermessage) {
                $assistant = $DB->get_record('socratic_messages', [
                    'conversationid' => $conversation->id,
                    'role' => 'assistant',
                    'replytoid' => $usermessage->id,
                ]);
                if ($assistant) {
                    return $this->response_payload($conversation, $usermessage, $assistant, $socratic);
                }
                $action = $usermessage->messagetype;
            } else {
                if ($this->get_pending_message((int)$conversation->id)) {
                    throw new moodle_exception('pendingresponse', 'mod_socratic');
                }
                $this->assert_rate_limit($conversation->id, $userid);
                if ((int)$conversation->interactioncount >= (int)$socratic->maxinteractions) {
                    throw new moodle_exception('maxinteractionsreached', 'mod_socratic');
                }

                $now = time();
                $usermessage = (object)[
                    'conversationid' => $conversation->id,
                    'userid' => $userid,
                    'role' => 'user',
                    'messagetype' => $action,
                    'content' => $message,
                    'clientid' => $clientid,
                    'replytoid' => null,
                    'timecreated' => $now,
                ];
                $usermessage->id = $DB->insert_record('socratic_messages', $usermessage);

                $conversation->interactioncount = (int)$conversation->interactioncount + 1;
                $conversation->timemodified = $now;
                $DB->update_record('socratic_conversations', $conversation);

                $context = context_module::instance($cm->id);
                $event = \mod_socratic\event\message_sent::create([
                    'objectid' => $usermessage->id,
                    'context' => $context,
                    'courseid' => $socratic->course,
                    'relateduserid' => $userid,
                    'other' => [
                        'conversationid' => $conversation->id,
                        'messagetype' => $action,
                    ],
                ]);
                $event->add_record_snapshot('socratic_messages', $usermessage);
                $event->trigger();

                $this->refresh_completion($socratic, $cm, $userid);
            }

            $context = context_module::instance($cm->id);
            $messages = $this->contextbuilder->build($socratic, $context, (int)$conversation->id, $action);
            try {
                $assistanttext = $this->aiclient->generate($messages);
            } catch (Throwable $e) {
                throw new moodle_exception('aiunavailable', 'mod_socratic', '', null, $e->getMessage());
            }
            if ($assistanttext === '') {
                throw new moodle_exception('aiunavailable', 'mod_socratic');
            }

            $assistant = (object)[
                'conversationid' => $conversation->id,
                'userid' => $userid,
                'role' => 'assistant',
                'messagetype' => 'message',
                'content' => core_text::substr($assistanttext, 0, 24000),
                'clientid' => null,
                'replytoid' => $usermessage->id,
                'timecreated' => time(),
            ];
            $assistant->id = $DB->insert_record('socratic_messages', $assistant);

            $conversation = $DB->get_record('socratic_conversations', ['id' => $conversation->id], '*', MUST_EXIST);
            if ((int)$conversation->interactioncount >= (int)$socratic->maxinteractions && $conversation->status === 'active') {
                $conversation = $this->complete($socratic, $cm, $conversation, 'limit', $userid);
            } else {
                $conversation->timemodified = time();
                $DB->update_record('socratic_conversations', $conversation);
            }
            return $this->response_payload($conversation, $usermessage, $assistant, $socratic);
        } finally {
            $lock->release();
        }
    }

    /**
     * Ends a learner conversation.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param int $userid
     * @return \stdClass
     */
    public function end(\stdClass $socratic, \stdClass $cm, int $userid): \stdClass {
        $factory = lock_config::get_lock_factory('mod_socratic_conversation');
        $lock = $factory->get_lock('activity:' . $socratic->id . ':user:' . $userid, 10);
        if (!$lock) {
            throw new moodle_exception('lockunavailable', 'mod_socratic');
        }
        try {
            $conversation = $this->get_conversation($socratic, $cm, $userid, true);
            if ($conversation->status === 'active') {
                $conversation = $this->complete($socratic, $cm, $conversation, 'student', $userid);
            }
            return $conversation;
        } finally {
            $lock->release();
        }
    }

    /**
     * Marks a learner conversation complete by a teacher.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param \stdClass $conversation
     * @param int $teacherid
     * @return \stdClass
     */
    public function teacher_complete(\stdClass $socratic, \stdClass $cm, \stdClass $conversation, int $teacherid): \stdClass {
        global $DB;

        $factory = lock_config::get_lock_factory('mod_socratic_conversation');
        $lock = $factory->get_lock(
            'activity:' . $socratic->id . ':user:' . $conversation->userid,
            10
        );
        if (!$lock) {
            throw new moodle_exception('lockunavailable', 'mod_socratic');
        }

        try {
            $conversation = $DB->get_record(
                'socratic_conversations',
                ['id' => $conversation->id, 'socraticid' => $socratic->id],
                '*',
                MUST_EXIST
            );
            if (!empty($conversation->teachercompletedat)) {
                return $conversation;
            }

            $conversation->teachercompletedby = $teacherid;
            $conversation->teachercompletedat = time();
            $conversation->timemodified = time();
            if ($conversation->status === 'active') {
                $conversation->status = 'completed';
                $conversation->completedreason = 'teacher';
            }
            $DB->update_record('socratic_conversations', $conversation);
            $this->trigger_completed_event($socratic, $cm, $conversation, 'teacher', $teacherid);
            $this->refresh_completion($socratic, $cm, (int)$conversation->userid);
            return $conversation;
        } finally {
            $lock->release();
        }
    }

    /**
     * Fetches ordered messages for rendering/export.
     *
     * @param int $conversationid
     * @return array
     */
    public function get_messages(int $conversationid): array {
        global $DB;
        return array_values($DB->get_records('socratic_messages', ['conversationid' => $conversationid], 'id ASC'));
    }

    /**
     * Returns the oldest learner message which does not yet have an assistant reply.
     *
     * @param int $conversationid
     * @return \stdClass|null
     */
    public function get_pending_message(int $conversationid): ?\stdClass {
        global $DB;

        $sql = "SELECT learner.*
                  FROM {socratic_messages} learner
             LEFT JOIN {socratic_messages} assistant
                    ON assistant.replytoid = learner.id
                   AND assistant.role = :assistantrole
                 WHERE learner.conversationid = :conversationid
                   AND learner.role = :learnerrole
                   AND assistant.id IS NULL
              ORDER BY learner.id ASC";
        $records = $DB->get_records_sql($sql, [
            'assistantrole' => 'assistant',
            'conversationid' => $conversationid,
            'learnerrole' => 'user',
        ], 0, 1);
        if (!$records) {
            return null;
        }
        return reset($records);
    }

    /**
     * Completes a conversation and emits the event once.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param \stdClass $conversation
     * @param string $reason
     * @param int $actorid
     * @return \stdClass
     */
    private function complete(\stdClass $socratic, \stdClass $cm, \stdClass $conversation,
            string $reason, int $actorid): \stdClass {
        global $DB;

        if ($conversation->status === 'completed') {
            return $conversation;
        }
        $conversation->status = 'completed';
        $conversation->completedreason = $reason;
        $conversation->timemodified = time();
        $DB->update_record('socratic_conversations', $conversation);
        $this->trigger_completed_event($socratic, $cm, $conversation, $reason, $actorid);
        $this->refresh_completion($socratic, $cm, (int)$conversation->userid);
        return $conversation;
    }

    /**
     * Emits the completion event.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param \stdClass $conversation
     * @param string $reason
     * @param int $actorid
     * @return void
     */
    private function trigger_completed_event(\stdClass $socratic, \stdClass $cm, \stdClass $conversation,
            string $reason, int $actorid): void {
        $context = context_module::instance($cm->id);
        $event = \mod_socratic\event\conversation_completed::create([
            'objectid' => $conversation->id,
            'context' => $context,
            'courseid' => $socratic->course,
            'userid' => $actorid,
            'relateduserid' => $conversation->userid,
            'other' => ['reason' => $reason],
        ]);
        $event->add_record_snapshot('socratic_conversations', $conversation);
        $event->trigger();
    }

    /**
     * Re-evaluates custom completion for a learner.
     *
     * @param \stdClass $socratic
     * @param \stdClass $cm
     * @param int $userid
     * @return void
     */
    private function refresh_completion(\stdClass $socratic, \stdClass $cm, int $userid): void {
        $course = get_course((int)$socratic->course);
        $completion = new completion_info($course);
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
    }

    /**
     * Enforces a basic local message rate limit in addition to bridge credits.
     *
     * @param int $conversationid
     * @param int $userid
     * @return void
     */
    private function assert_rate_limit(int $conversationid, int $userid): void {
        global $DB;

        $limit = max(1, min(60, (int)(get_config('mod_socratic', 'ratelimitperminute') ?: 8)));
        $count = $DB->count_records_select(
            'socratic_messages',
            'conversationid = :conversationid AND userid = :userid AND role = :role AND timecreated >= :since',
            [
                'conversationid' => $conversationid,
                'userid' => $userid,
                'role' => 'user',
                'since' => time() - 60,
            ]
        );
        if ($count >= $limit) {
            throw new moodle_exception('ratelimited', 'mod_socratic');
        }
    }

    /**
     * Validates action against activity settings.
     *
     * @param \stdClass $socratic
     * @param string $action
     * @return void
     */
    private function validate_action(\stdClass $socratic, string $action): void {
        if (!in_array($action, ['message', 'hint', 'final'], true)) {
            throw new moodle_exception('invalidaction', 'mod_socratic');
        }
        if ($action === 'hint' && empty($socratic->allowhint)) {
            throw new moodle_exception('invalidaction', 'mod_socratic');
        }
        if ($action === 'final' && empty($socratic->allowfinalanswer)) {
            throw new moodle_exception('invalidaction', 'mod_socratic');
        }
    }

    /**
     * Builds the AJAX response.
     *
     * @param \stdClass $conversation
     * @param \stdClass $usermessage
     * @param \stdClass $assistant
     * @param \stdClass $socratic
     * @return array
     */
    private function response_payload(\stdClass $conversation, \stdClass $usermessage,
            \stdClass $assistant, \stdClass $socratic): array {
        return [
            'conversationid' => (int)$conversation->id,
            'messageid' => (int)$usermessage->id,
            'assistant' => (string)$assistant->content,
            'completed' => $conversation->status === 'completed',
            'interactioncount' => (int)$conversation->interactioncount,
            'maxinteractions' => (int)$socratic->maxinteractions,
        ];
    }
}
