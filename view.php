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
 * Learner-facing Socratic activity page.
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
require_capability('mod/socratic:view', $context);

$socratic = $DB->get_record('socratic', ['id' => $cm->instance], '*', MUST_EXIST);
$service = new \mod_socratic\local\conversation_service();
$conversation = $service->get_conversation($socratic, $cm, (int)$USER->id, false);
$messages = $conversation ? $service->get_messages((int)$conversation->id) : [];
$pendingmessage = ($conversation && $conversation->status === 'active')
    ? $service->get_pending_message((int)$conversation->id)
    : null;

$PAGE->set_url('/mod/socratic/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($socratic->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-socratic-view');

$modulecontext = [
    'cmid' => (int)$cm->id,
    'name' => format_string($socratic->name),
    'intro' => format_module_intro('socratic', $socratic, $cm->id),
    'objective' => format_text($socratic->objective, FORMAT_HTML, ['context' => $context]),
    'allowhint' => !empty($socratic->allowhint),
    'allowfinalanswer' => !empty($socratic->allowfinalanswer),
    'maxinteractions' => (int)$socratic->maxinteractions,
    'interactioncount' => $conversation ? (int)$conversation->interactioncount : 0,
    'completed' => $conversation ? $conversation->status === 'completed' : false,
    'hasconversation' => !empty($conversation),
    'canviewall' => has_capability('mod/socratic:viewallconversations', $context),
    'conversationsurl' => (new moodle_url('/mod/socratic/conversations.php', ['id' => $cm->id]))->out(false),
    'messages' => [],
    'supportfiles' => [],
    'haspending' => !empty($pendingmessage),
];

foreach ($messages as $message) {
    $modulecontext['messages'][] = [
        'isuser' => $message->role === 'user',
        'isassistant' => $message->role === 'assistant',
        'content' => $message->content,
        'time' => userdate($message->timecreated, get_string('strftimetime', 'langconfig')),
    ];
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_socratic', 'supportfiles', 0, 'filename', false);
$textmimes = ['text/plain', 'text/markdown', 'text/html', 'text/csv', 'application/json', 'application/xml', 'text/xml'];
foreach ($files as $file) {
    $url = moodle_url::make_pluginfile_url(
        $context->id,
        'mod_socratic',
        'supportfiles',
        0,
        $file->get_filepath(),
        $file->get_filename(),
        true
    );
    $modulecontext['supportfiles'][] = [
        'name' => $file->get_filename(),
        'url' => $url->out(false),
        'processedincontext' => in_array($file->get_mimetype(), $textmimes, true),
    ];
}
$modulecontext['hassupportfiles'] = !empty($modulecontext['supportfiles']);

$PAGE->requires->js_call_amd('mod_socratic/chat', 'init', [[
    'cmid' => (int)$cm->id,
    'allowhint' => !empty($socratic->allowhint),
    'allowfinalanswer' => !empty($socratic->allowfinalanswer),
    'completed' => $modulecontext['completed'],
    'interactioncount' => $modulecontext['interactioncount'],
    'maxinteractions' => (int)$socratic->maxinteractions,
    'hintlabel' => get_string('hint', 'mod_socratic'),
    'finallabel' => get_string('finalanswer', 'mod_socratic'),
    'retryableerror' => get_string('retryableerror', 'mod_socratic'),
    'conversationended' => get_string('conversationended', 'mod_socratic'),
    'pending' => $pendingmessage ? [
        'action' => $pendingmessage->messagetype,
        'message' => $pendingmessage->content,
        'display' => $pendingmessage->content,
        'clientid' => $pendingmessage->clientid,
    ] : null,
]]);

$event = \mod_socratic\event\course_module_viewed::create([
    'objectid' => $socratic->id,
    'context' => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('socratic', $socratic);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_socratic/chat', $modulecontext);
echo $OUTPUT->footer();
