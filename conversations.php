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
 * Teacher overview of learner conversations.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'socratic');
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/socratic:viewallconversations', $context);
$socratic = $DB->get_record('socratic', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/socratic/conversations.php', ['id' => $cm->id]);
$PAGE->set_title(get_string('conversations', 'mod_socratic'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$groupid = 0;
$groupmode = groups_get_activity_groupmode($cm);
if ($groupmode) {
    $groupid = groups_get_activity_group($cm, true);
}

$params = ['socraticid' => $socratic->id];
$sql = "SELECT c.*, u.firstname, u.lastname, u.email
          FROM {socratic_conversations} c
          JOIN {user} u ON u.id = c.userid
         WHERE c.socraticid = :socraticid
      ORDER BY c.timemodified DESC";
$conversations = $DB->get_records_sql($sql, $params);

if ($groupid) {
    $conversations = array_filter($conversations, static function($conversation) use ($groupid) {
        return groups_is_member($groupid, $conversation->userid);
    });
} else if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $teachergroups = array_keys(groups_get_all_groups($course->id, $USER->id, $cm->groupingid, 'g.id'));
    $conversations = array_filter($conversations, static function($conversation) use ($course, $cm, $teachergroups) {
        if (!$teachergroups) {
            return false;
        }
        $learnergroups = array_keys(groups_get_all_groups($course->id, $conversation->userid, $cm->groupingid, 'g.id'));
        return (bool)array_intersect($teachergroups, $learnergroups);
    });
}

$data = [
    'rows' => [],
    'hasrows' => !empty($conversations),
    'activityurl' => (new moodle_url('/mod/socratic/view.php', ['id' => $cm->id]))->out(false),
];
foreach ($conversations as $conversation) {
    $data['rows'][] = [
        'fullname' => fullname($conversation),
        'status' => get_string($conversation->status === 'completed' ? 'statuscompleted' : 'statusactive', 'mod_socratic'),
        'interactioncount' => (int)$conversation->interactioncount,
        'lastupdated' => userdate($conversation->timemodified),
        'url' => (new moodle_url('/mod/socratic/conversation.php', ['id' => $conversation->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('conversations', 'mod_socratic'));
if ($groupmode) {
    groups_print_activity_menu($cm, $PAGE->url);
}
echo $OUTPUT->render_from_template('mod_socratic/conversation_list', $data);
echo $OUTPUT->footer();
