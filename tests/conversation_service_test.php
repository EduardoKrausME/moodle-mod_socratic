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
 * Conversation service tests for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace mod_socratic;

use advanced_testcase;
use mod_socratic\local\ai_client;
use mod_socratic\local\conversation_service;
use moodle_exception;

/**
 * Conversation, history, error and idempotency tests.
 *
 * @package mod_socratic
 * @covers \mod_socratic\local\conversation_service
 */
final class conversation_service_test extends advanced_testcase {
    /**
     * Method test_bridge_failure_keeps_learner_message.
     *
     * @return void Return value.
     */
    public function test_bridge_failure_keeps_learner_message(): void {
        global $DB;

        $this->resetAfterTest();
        [$course, $activity, $user, $cm, $socratic] = $this->fixture();
        $this->setUser($user);
        $client = new class extends ai_client {
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                throw new moodle_exception('provider failed');
            }
        };
        $service = new conversation_service($client);

        try {
            $service->send($socratic, $cm, $user->id, 'My reasoning', 'message', 'client-1');
            $this->fail('Expected an AI error.');
        } catch (moodle_exception $e) {
            $this->assertSame('aiunavailable', $e->errorcode);
        }

        $conversation = $DB->get_record('socratic_conversations', ['socraticid' => $activity->id, 'userid' => $user->id]);
        $this->assertNotFalse($conversation);
        $this->assertSame(1, $DB->count_records('socratic_messages', ['conversationid' => $conversation->id, 'role' => 'user']));
        $this->assertSame('My reasoning', $DB->get_field('socratic_messages', 'content', ['conversationid' => $conversation->id]));
    }

    /**
     * Method test_retry_after_bridge_failure_reuses_pending_turn.
     *
     * @return void Return value.
     */
    public function test_retry_after_bridge_failure_reuses_pending_turn(): void {
        global $DB;

        $this->resetAfterTest();
        [, $activity, $user, $cm, $socratic] = $this->fixture();
        $this->setUser($user);

        $failingclient = new class extends ai_client {
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                throw new moodle_exception('provider failed');
            }
        };
        $service = new conversation_service($failingclient);
        try {
            $service->send($socratic, $cm, $user->id, 'My first reasoning', 'message', 'retry-client-id');
            $this->fail('Expected an AI error.');
        } catch (moodle_exception $e) {
            $this->assertSame('aiunavailable', $e->errorcode);
        }

        $blockingclient = new class extends ai_client {
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                return 'This should never be called for a different client id while a turn is pending.';
            }
        };
        $blockingservice = new conversation_service($blockingclient);
        try {
            $blockingservice->send($socratic, $cm, $user->id, 'Skip pending turn', 'message', 'different-client-id');
            $this->fail('Expected the pending response guard.');
        } catch (moodle_exception $e) {
            $this->assertSame('pendingresponse', $e->errorcode);
        }

        $workingclient = new class extends ai_client {
            /**
             * Property calls.
             *
             * @var int
             */
            public int $calls = 0;
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                $this->calls++;
                return 'Which premise in your reasoning is the strongest?';
            }
        };
        $service = new conversation_service($workingclient);
        $result = $service->send(
            $socratic,
            $cm,
            $user->id,
            'This text must not create a second learner message.',
            'message',
            'retry-client-id'
        );

        $conversation = $DB->get_record('socratic_conversations', [
            'socraticid' => $activity->id,
            'userid' => $user->id,
        ], '*', MUST_EXIST);
        $this->assertSame(1, (int)$conversation->interactioncount);
        $this->assertSame(1, $workingclient->calls);
        $this->assertSame(1, $DB->count_records('socratic_messages', [
            'conversationid' => $conversation->id,
            'role' => 'user',
        ]));
        $this->assertSame(1, $DB->count_records('socratic_messages', [
            'conversationid' => $conversation->id,
            'role' => 'assistant',
        ]));
        $this->assertNotEmpty($result['messageid']);
        $this->assertSame('My first reasoning', $DB->get_field('socratic_messages', 'content', [
            'conversationid' => $conversation->id,
            'role' => 'user',
        ]));
    }

    /**
     * Method test_same_clientid_is_idempotent.
     *
     * @return void Return value.
     */
    public function test_same_clientid_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest();
        [, $activity, $user, $cm, $socratic] = $this->fixture();
        $this->setUser($user);
        $client = new class extends ai_client {
            /**
             * Property calls.
             *
             * @var int
             */
            public int $calls = 0;
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                $this->calls++;
                return 'What evidence supports that conclusion?';
            }
        };
        $service = new conversation_service($client);
        $first = $service->send($socratic, $cm, $user->id, 'Because of A.', 'message', 'same-client-id');
        $second = $service->send($socratic, $cm, $user->id, 'Because of A.', 'message', 'same-client-id');

        $this->assertSame($first['messageid'], $second['messageid']);
        $this->assertSame(1, $client->calls);
        $conversation = $DB->get_record('socratic_conversations', ['socraticid' => $activity->id, 'userid' => $user->id]);
        $this->assertSame(1, (int)$conversation->interactioncount);
        $this->assertSame(2, $DB->count_records('socratic_messages', ['conversationid' => $conversation->id]));
    }

    /**
     * Method test_history_sent_to_ai_is_bounded_and_contains_system_grounding.
     *
     * @return void Return value.
     */
    public function test_history_sent_to_ai_is_bounded_and_contains_system_grounding(): void {
        $this->resetAfterTest();
        [, , $user, $cm, $socratic] = $this->fixture();
        $this->setUser($user);
        set_config('historymessages', 4, 'mod_socratic');
        $client = new class extends ai_client {
            /**
             * Property calls.
             *
             * @var array
             */
            public array $calls = [];
            /**
             * Method generate.
             *
             * @param array $messages Parameter messages.
             * @return string Return value.
             */
            public function generate(array $messages): string {
                $this->calls[] = $messages;
                return 'Please justify that step.';
            }
        };
        $service = new conversation_service($client);
        for ($i = 0; $i < 4; $i++) {
            $service->send($socratic, $cm, $user->id, 'Turn ' . $i, 'message', 'client-' . $i);
        }

        $last = end($client->calls);
        $this->assertLessThanOrEqual(5, count($last));
        $this->assertSame('system', $last[0]['role']);
        $this->assertStringContainsString('AUTHORITATIVE KNOWLEDGE BASE START', $last[0]['content']);
    }

    /**
     * Method test_message_template_escapes_html.
     *
     * @return void Return value.
     */
    public function test_message_template_escapes_html(): void {
        global $PAGE, $OUTPUT;

        $this->resetAfterTest();
        $PAGE->set_url('/');
        $html = $OUTPUT->render_from_template('mod_socratic/message', [
            'isuser' => true,
            'content' => '<script>alert(1)</script><b>unsafe</b>',
            'time' => '',
        ]);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;unsafe&lt;/b&gt;', $html);
    }

    /**
     * Method fixture.
     *
     * @return array Return value.
     */
    private function fixture(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $activity = $this->getDataGenerator()->create_module('socratic', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'contentbase' => 'A causes B according to the supplied lesson.',
            'maxinteractions' => 20,
        ]);
        $cm = get_coursemodule_from_id('socratic', $activity->cmid, 0, false, MUST_EXIST);
        $socratic = $DB->get_record('socratic', ['id' => $activity->id], '*', MUST_EXIST);
        return [$course, $activity, $user, $cm, $socratic];
    }
}
