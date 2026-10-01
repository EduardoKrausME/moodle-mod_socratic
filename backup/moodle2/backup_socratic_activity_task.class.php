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
 * Backup task for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_socratic_activity_task extends backup_activity_task {
    /**
     * No custom task settings are required.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Adds the activity structure step.
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_socratic_activity_structure_step('socratic_structure', 'socratic.xml'));
    }

    /**
     * Encodes activity links in backed-up content.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace(
            "/({$base}\\/mod\\/socratic\\/index.php\\?id=)([0-9]+)/",
            '$@SOCRATICINDEX*$2@$',
            $content
        );
        return preg_replace(
            "/({$base}\\/mod\\/socratic\\/view.php\\?id=)([0-9]+)/",
            '$@SOCRATICVIEWBYID*$2@$',
            $content
        );
    }
}
