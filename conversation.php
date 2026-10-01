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
 * Teacher view of one learner conversation.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$conversation = $DB->get_record('socratic_conversations', ['id' => $id], '*', MUST_EXIST);
$socratic = $DB->get_record('socratic', ['id' => $conversation->socraticid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('socratic', $socratic->id, $socratic->course, false, MUST_EXIST);
$course = get_course($socratic->course);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/socratic:viewallconversations', $context);

$conversationurl = new moodle_url('/mod/socratic/conversation.php', ['id' => $conversation->id]);
$PAGE->set_url($conversationurl);

$groupmode = groups_get_activity_groupmode($cm);
if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $teachergroups = array_keys(groups_get_all_groups($course->id, $USER->id, $cm->groupingid, 'g.id'));
    $learnergroups = array_keys(groups_get_all_groups($course->id, $conversation->userid, $cm->groupingid, 'g.id'));
    if (!array_intersect($teachergroups, $learnergroups)) {
        throw new required_capability_exception($context, 'mod/socratic:viewallconversations', 'nopermissions', '');
    }
}

if (optional_param('markcomplete', 0, PARAM_BOOL)) {
    require_sesskey();
    if ($socratic->completioncriterion === SOCRATIC_COMPLETION_TEACHER) {
        $service = new \mod_socratic\local\conversation_service();
        $conversation = $service->teacher_complete($socratic, $cm, $conversation, (int)$USER->id);
        redirect($conversationurl, get_string('completionupdated', 'mod_socratic'));
    }
}

$learner = core_user::get_user($conversation->userid, '*', MUST_EXIST);
$service = new \mod_socratic\local\conversation_service();
$messages = $service->get_messages((int)$conversation->id);

$PAGE->set_title(get_string('conversation', 'mod_socratic'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$data = [
    'learner' => fullname($learner),
    'status' => get_string($conversation->status === 'completed' ? 'statuscompleted' : 'statusactive', 'mod_socratic'),
    'interactioncount' => (int)$conversation->interactioncount,
    'messages' => [],
    'canmarkcomplete' => $socratic->completioncriterion === SOCRATIC_COMPLETION_TEACHER && empty($conversation->teachercompletedat),
    'markcompleteurl' => (new moodle_url('/mod/socratic/conversation.php', [
        'id' => $conversation->id,
        'markcomplete' => 1,
        'sesskey' => sesskey(),
    ]))->out(false),
    'backurl' => (new moodle_url('/mod/socratic/conversations.php', ['id' => $cm->id]))->out(false),
];
foreach ($messages as $message) {
    $data['messages'][] = [
        'isuser' => $message->role === 'user',
        'isassistant' => $message->role === 'assistant',
        'content' => $message->content,
        'time' => userdate($message->timecreated, get_string('strftimetime', 'langconfig')),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_socratic/conversation_detail', $data);
echo $OUTPUT->footer();
