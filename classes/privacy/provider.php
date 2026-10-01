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
 * Privacy API implementation for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_socratic\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes stored and forwarded data.
     *
     * @param collection $items
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('socratic_conversations', [
            'socraticid' => 'privacy:metadata:conversations:socraticid',
            'userid' => 'privacy:metadata:conversations:userid',
            'status' => 'privacy:metadata:conversations:status',
            'interactioncount' => 'privacy:metadata:conversations:interactioncount',
            'completedreason' => 'privacy:metadata:conversations:completedreason',
            'teachercompletedby' => 'privacy:metadata:conversations:teachercompletedby',
            'teachercompletedat' => 'privacy:metadata:conversations:teachercompletedat',
            'timecreated' => 'privacy:metadata:conversations:timecreated',
            'timemodified' => 'privacy:metadata:conversations:timemodified',
        ], 'privacy:metadata:conversations');

        $items->add_database_table('socratic_messages', [
            'conversationid' => 'privacy:metadata:messages:conversationid',
            'userid' => 'privacy:metadata:messages:userid',
            'role' => 'privacy:metadata:messages:role',
            'messagetype' => 'privacy:metadata:messages:messagetype',
            'content' => 'privacy:metadata:messages:content',
            'clientid' => 'privacy:metadata:messages:clientid',
            'replytoid' => 'privacy:metadata:messages:replytoid',
            'timecreated' => 'privacy:metadata:messages:timecreated',
        ], 'privacy:metadata:messages');

        $items->add_external_location_link('local_ai_bridge', [
            'messages' => 'privacy:metadata:aibridge:messages',
            'contentbase' => 'privacy:metadata:aibridge:contentbase',
        ], 'privacy:metadata:aibridge');

        return $items;
    }

    /**
     * Finds module contexts containing data about a user, either as learner or teacher reviewer.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {socratic} s ON s.id = cm.instance
                  JOIN {socratic_conversations} c ON c.socraticid = s.id
                 WHERE c.userid = :learnerid OR c.teachercompletedby = :teacherid";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'socratic',
            'learnerid' => $userid,
            'teacherid' => $userid,
        ];
        $contexts = new contextlist();
        $contexts->add_from_sql($sql, $params);
        return $contexts;
    }

    /**
     * Adds users with learner or teacher-review data in one context.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT c.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname1
                  JOIN {socratic} s ON s.id = cm.instance
                  JOIN {socratic_conversations} c ON c.socraticid = s.id
                 WHERE cm.id = :cmid1
                 UNION
                SELECT c.teachercompletedby AS userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname2
                  JOIN {socratic} s ON s.id = cm.instance
                  JOIN {socratic_conversations} c ON c.socraticid = s.id
                 WHERE cm.id = :cmid2 AND c.teachercompletedby IS NOT NULL";
        $userlist->add_from_sql('userid', $sql, [
            'modname1' => 'socratic',
            'cmid1' => $context->instanceid,
            'modname2' => 'socratic',
            'cmid2' => $context->instanceid,
        ]);
    }

    /**
     * Exports all learner conversations and teacher completion actions in approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('socratic', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            $contextdata = helper::get_context_data($context, $user);
            $owned = $DB->get_record('socratic_conversations', [
                'socraticid' => $cm->instance,
                'userid' => $user->id,
            ]);
            if ($owned) {
                $messages = $DB->get_records('socratic_messages', ['conversationid' => $owned->id], 'id ASC');
                $exportmessages = [];
                foreach ($messages as $message) {
                    $exportmessages[] = (object)[
                        'role' => $message->role,
                        'type' => $message->messagetype,
                        'content' => $message->content,
                        'clientid' => $message->clientid,
                        'replytoid' => $message->replytoid,
                        'timecreated' => transform::datetime($message->timecreated),
                    ];
                }
                $contextdata->conversation = (object)[
                    'status' => $owned->status,
                    'interactioncount' => $owned->interactioncount,
                    'completedreason' => $owned->completedreason,
                    'timecreated' => transform::datetime($owned->timecreated),
                    'timemodified' => transform::datetime($owned->timemodified),
                    'messages' => $exportmessages,
                ];
            }

            $reviewed = $DB->get_records('socratic_conversations', [
                'socraticid' => $cm->instance,
                'teachercompletedby' => $user->id,
            ], 'teachercompletedat ASC', 'id,userid,teachercompletedat');
            if ($reviewed) {
                $contextdata->teacher_completion_actions = array_map(static function($item) {
                    return (object)[
                        'learnerid' => $item->userid,
                        'time' => transform::datetime($item->teachercompletedat),
                    ];
                }, array_values($reviewed));
            }

            writer::with_context($context)->export_data([], $contextdata);
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Deletes all user-owned data in a module context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('socratic', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $ids = $DB->get_fieldset_select('socratic_conversations', 'id', 'socraticid = :id', ['id' => $cm->instance]);
        if ($ids) {
            [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('socratic_messages', "conversationid {$insql}", $params);
        }
        $DB->delete_records('socratic_conversations', ['socraticid' => $cm->instance]);
    }

    /**
     * Deletes data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('socratic', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            self::delete_learner_data((int)$cm->instance, (int)$userid);
            self::anonymise_teacher_actions((int)$cm->instance, [(int)$userid]);
        }
    }

    /**
     * Deletes multiple users within one approved context.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('socratic', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            self::delete_learner_data((int)$cm->instance, (int)$userid);
        }
        if ($userids) {
            self::anonymise_teacher_actions((int)$cm->instance, array_map('intval', $userids));
        }
    }


    /**
     * Removes attribution from teacher completion actions without touching learner-owned data.
     *
     * @param int $socraticid
     * @param int[] $teacherids
     * @return void
     */
    private static function anonymise_teacher_actions(int $socraticid, array $teacherids): void {
        global $DB;

        if (!$teacherids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($teacherids, SQL_PARAMS_NAMED, 'teacher');
        $params['socraticid'] = $socraticid;
        $ids = $DB->get_fieldset_select(
            'socratic_conversations',
            'id',
            "socraticid = :socraticid AND teachercompletedby {$insql}",
            $params
        );
        if (!$ids) {
            return;
        }
        [$idsql, $idparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'conversation');
        $DB->set_field_select('socratic_conversations', 'teachercompletedby', null, "id {$idsql}", $idparams);
        $DB->set_field_select('socratic_conversations', 'teachercompletedat', null, "id {$idsql}", $idparams);
    }

    /**
     * Deletes conversation/message data owned by one learner.
     *
     * @param int $socraticid
     * @param int $userid
     * @return void
     */
    private static function delete_learner_data(int $socraticid, int $userid): void {
        global $DB;

        $conversationids = $DB->get_fieldset_select(
            'socratic_conversations',
            'id',
            'socraticid = :socraticid AND userid = :userid',
            ['socraticid' => $socraticid, 'userid' => $userid]
        );
        if ($conversationids) {
            [$insql, $params] = $DB->get_in_or_equal($conversationids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('socratic_messages', "conversationid {$insql}", $params);
            $DB->delete_records_select('socratic_conversations', "id {$insql}", $params);
        }
    }
}
