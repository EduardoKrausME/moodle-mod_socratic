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
 * Event fired when a Socratic conversation completes.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_socratic\event;

/**
 * Event fired when a Socratic conversation becomes complete.
 *
 * @package mod_socratic
 */
class conversation_completed extends \core\event\base {
    /**
     * Method init.
     *
     * @return void Return value.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'socratic_conversations';
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public static function get_name(): string {
        return get_string('eventconversationcompleted', 'mod_socratic');
    }

    /**
     * Method get_description.
     *
     * @return string Return value.
     */
    public function get_description(): string {
        $reason = $this->other['reason'] ?? 'unknown';
        return "Socratic conversation '{$this->objectid}' for user '{$this->relateduserid}' was completed ({$reason}).";
    }

    /**
     * Method get_objectid_mapping.
     *
     * @return array Return value.
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'socratic_conversations', 'restore' => 'socratic_conversation'];
    }
}
