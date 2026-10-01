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
 * Administrative settings for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'mod_socratic/ratelimitperminute',
        get_string('settings:ratelimitperminute', 'mod_socratic'),
        get_string('settings:ratelimitperminute_desc', 'mod_socratic'),
        8,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'mod_socratic/historymessages',
        get_string('settings:historymessages', 'mod_socratic'),
        get_string('settings:historymessages_desc', 'mod_socratic'),
        16,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'mod_socratic/maxcontextchars',
        get_string('settings:maxcontextchars', 'mod_socratic'),
        get_string('settings:maxcontextchars_desc', 'mod_socratic'),
        24000,
        PARAM_INT
    ));
}
