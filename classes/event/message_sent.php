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
 * Event fired when a learner sends a Socratic message.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_socratic\event;

/**
 * Event fired after a learner message is persisted.
 *
 * @package mod_socratic
 */
class message_sent extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'socratic_messages';
    }

    public static function get_name(): string {
        return get_string('eventmessagesent', 'mod_socratic');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' sent Socratic message '{$this->objectid}'.";
    }

    public static function get_objectid_mapping(): array {
        return ['db' => 'socratic_messages', 'restore' => 'socratic_message'];
    }

    public static function get_other_mapping(): array {
        $othermapped = [];
        $othermapped['conversationid'] = ['db' => 'socratic_conversations', 'restore' => 'socratic_conversation'];
        return $othermapped;
    }
}
