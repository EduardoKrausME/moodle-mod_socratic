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
 * Backup structure for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_socratic_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backed-up XML tree.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $socratic = new backup_nested_element('socratic', ['id'], [
            'name', 'intro', 'introformat', 'objective', 'contentbase', 'tutorbehavior',
            'maxinteractions', 'allowhint', 'allowfinalanswer', 'completioncriterion',
            'completioninteractions', 'timecreated', 'timemodified',
        ]);
        $conversations = new backup_nested_element('conversations');
        $conversation = new backup_nested_element('conversation', ['id'], [
            'userid', 'status', 'interactioncount', 'completedreason', 'teachercompletedby',
            'teachercompletedat', 'timecreated', 'timemodified',
        ]);
        $messages = new backup_nested_element('messages');
        $message = new backup_nested_element('message', ['id'], [
            'userid', 'role', 'messagetype', 'content', 'clientid', 'replytoid', 'timecreated',
        ]);

        $socratic->add_child($conversations);
        $conversations->add_child($conversation);
        $conversation->add_child($messages);
        $messages->add_child($message);

        $socratic->set_source_table('socratic', ['id' => backup::VAR_ACTIVITYID]);
        if ($this->get_setting_value('userinfo')) {
            $conversation->set_source_table('socratic_conversations', ['socraticid' => backup::VAR_PARENTID], 'id ASC');
            $message->set_source_table('socratic_messages', ['conversationid' => backup::VAR_PARENTID], 'id ASC');
        }

        $conversation->annotate_ids('user', 'userid');
        $conversation->annotate_ids('user', 'teachercompletedby');
        $message->annotate_ids('user', 'userid');
        $socratic->annotate_files('mod_socratic', 'intro', null);
        $socratic->annotate_files('mod_socratic', 'supportfiles', null);

        return $this->prepare_activity_structure($socratic);
    }
}
