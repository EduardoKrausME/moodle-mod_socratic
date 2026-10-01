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
 * Restore structure for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_socratic_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines restore paths.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('socratic', '/activity/socratic'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('socratic_conversation', '/activity/socratic/conversations/conversation');
            $paths[] = new restore_path_element(
                'socratic_message',
                '/activity/socratic/conversations/conversation/messages/message'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity record.
     *
     * @param array $data
     * @return void
     */
    protected function process_socratic($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = time();
        $newid = $DB->insert_record('socratic', $data);
        $this->apply_activity_instance($newid);
        $this->set_mapping('socratic', $oldid, $newid, true);
    }

    /**
     * Restores one learner conversation.
     *
     * @param array $data
     * @return void
     */
    protected function process_socratic_conversation($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->socraticid = $this->get_new_parentid('socratic');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        if (!empty($data->teachercompletedby)) {
            $data->teachercompletedby = $this->get_mappingid('user', $data->teachercompletedby, 0) ?: null;
        }
        $newid = $DB->insert_record('socratic_conversations', $data);
        $this->set_mapping('socratic_conversation', $oldid, $newid);
    }

    /**
     * Restores one message.
     *
     * @param array $data
     * @return void
     */
    protected function process_socratic_message($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->conversationid = $this->get_new_parentid('socratic_conversation');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->conversationid || !$data->userid) {
            return;
        }
        if (!empty($data->replytoid)) {
            $data->replytoid = $this->get_mappingid('socratic_message', $data->replytoid, 0) ?: null;
        }
        $newid = $DB->insert_record('socratic_messages', $data);
        $this->set_mapping('socratic_message', $oldid, $newid);
    }

    /**
     * Restores module files.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_socratic', 'intro', null);
        $this->add_related_files('mod_socratic', 'supportfiles', null);
    }
}
