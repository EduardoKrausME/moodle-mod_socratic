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
 * Library callbacks for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var string Completion after a configured number of interactions. */
define('SOCRATIC_COMPLETION_INTERACTIONS', 'interactions');
/** @var string Completion when the conversation is ended. */
define('SOCRATIC_COMPLETION_ENDED', 'ended');
/** @var string Completion after teacher confirmation. */
define('SOCRATIC_COMPLETION_TEACHER', 'teacher');
/** @var string Moodle manual completion. */
define('SOCRATIC_COMPLETION_MANUAL', 'manual');

/**
 * Declares Moodle feature support.
 *
 * @param string $feature
 * @return mixed
 */
function socratic_supports(string $feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        default:
            return null;
    }
}

/**
 * Adds a Socratic activity instance.
 *
 * @param stdClass $data
 * @param mod_socratic_mod_form|null $mform
 * @return int
 */
function socratic_add_instance(stdClass $data, ?mod_socratic_mod_form $mform = null): int {
    global $DB;

    $now = time();
    $data->timecreated = $now;
    $data->timemodified = $now;
    $id = $DB->insert_record('socratic', $data);
    $data->id = $id;

    socratic_save_support_files($data);
    socratic_sync_completion_tracking($data);
    return $id;
}

/**
 * Updates a Socratic activity instance.
 *
 * @param stdClass $data
 * @param mod_socratic_mod_form|null $mform
 * @return bool
 */
function socratic_update_instance(stdClass $data, ?mod_socratic_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $result = $DB->update_record('socratic', $data);

    socratic_save_support_files($data);
    socratic_sync_completion_tracking($data);
    return $result;
}

/**
 * Deletes an activity instance and all module-owned data.
 *
 * @param int $id
 * @return bool
 */
function socratic_delete_instance(int $id): bool {
    global $DB;

    $instance = $DB->get_record('socratic', ['id' => $id]);
    if (!$instance) {
        return false;
    }

    $cm = get_coursemodule_from_instance('socratic', $id, $instance->course, false, IGNORE_MISSING);
    $context = $cm ? context_module::instance($cm->id, IGNORE_MISSING) : null;

    $conversationids = $DB->get_fieldset_select('socratic_conversations', 'id', 'socraticid = :id', ['id' => $id]);
    if ($conversationids) {
        [$insql, $params] = $DB->get_in_or_equal($conversationids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('socratic_messages', "conversationid {$insql}", $params);
    }
    $DB->delete_records('socratic_conversations', ['socraticid' => $id]);
    $DB->delete_records('socratic', ['id' => $id]);

    if ($context) {
        get_file_storage()->delete_area_files($context->id, 'mod_socratic');
    }
    return true;
}

/**
 * Saves support files from the form draft area.
 *
 * @param stdClass $data
 * @return void
 */
function socratic_save_support_files(stdClass $data): void {
    if (empty($data->coursemodule) || !isset($data->supportfiles)) {
        return;
    }
    $context = context_module::instance((int)$data->coursemodule);
    $options = [
        'subdirs' => 0,
        'maxfiles' => 20,
        'maxbytes' => 1024 * 1024,
        'accepted_types' => ['.txt', '.md', '.html', '.htm', '.csv', '.json', '.xml'],
    ];
    file_save_draft_area_files(
        (int)$data->supportfiles,
        $context->id,
        'mod_socratic',
        'supportfiles',
        0,
        $options
    );
}

/**
 * Keeps the Moodle completion tracking mode aligned with the activity criterion.
 *
 * @param stdClass $data
 * @return void
 */
function socratic_sync_completion_tracking(stdClass $data): void {
    global $DB;

    if (empty($data->coursemodule) || !isset($data->completioncriterion)) {
        return;
    }
    $completion = $data->completioncriterion === SOCRATIC_COMPLETION_MANUAL
        ? COMPLETION_TRACKING_MANUAL
        : COMPLETION_TRACKING_AUTOMATIC;
    $DB->set_field('course_modules', 'completion', $completion, ['id' => (int)$data->coursemodule]);
    rebuild_course_cache((int)$data->course, true);
}

/**
 * Returns cached course-module information including custom completion rules.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info|false
 */
function socratic_get_coursemodule_info(stdClass $coursemodule) {
    global $DB;

    $record = $DB->get_record('socratic', ['id' => $coursemodule->instance],
        'id,name,intro,introformat,completioncriterion,completioninteractions');
    if (!$record) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $record->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('socratic', $record, $coursemodule->id, false);
    }
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC &&
            $record->completioncriterion !== SOCRATIC_COMPLETION_MANUAL) {
        $info->customdata['customcompletionrules']['completioncondition'] = [
            'criterion' => $record->completioncriterion,
            'interactions' => (int)$record->completioninteractions,
        ];
    }
    return $info;
}

/**
 * Human-readable active completion descriptions.
 *
 * @param cm_info|stdClass $cm
 * @return array
 */
function mod_socratic_get_completion_active_rule_descriptions($cm): array {
    if ($cm->completion != COMPLETION_TRACKING_AUTOMATIC ||
            empty($cm->customdata['customcompletionrules']['completioncondition'])) {
        return [];
    }
    $rule = $cm->customdata['customcompletionrules']['completioncondition'];
    switch ($rule['criterion']) {
        case SOCRATIC_COMPLETION_INTERACTIONS:
            return [get_string('completiondetail:interactions', 'mod_socratic', $rule['interactions'])];
        case SOCRATIC_COMPLETION_ENDED:
            return [get_string('completiondetail:ended', 'mod_socratic')];
        case SOCRATIC_COMPLETION_TEACHER:
            return [get_string('completiondetail:teacher', 'mod_socratic')];
        default:
            return [];
    }
}

/**
 * Serves module files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function mod_socratic_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'supportfiles') {
        // The standard module intro area is served by Moodle core.
        return false;
    }
    require_login($course, true, $cm);
    require_capability('mod/socratic:view', $context);

    $itemid = (int)array_shift($args);
    if ($itemid !== 0 || !$args) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'mod_socratic', 'supportfiles', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Course reset callback.
 *
 * @param stdClass $data
 * @return array
 */
function socratic_reset_userdata(stdClass $data): array {
    global $DB;

    $status = [];
    $instances = $DB->get_fieldset_select('socratic', 'id', 'course = :course', ['course' => $data->courseid]);
    if (!$instances) {
        return $status;
    }
    [$insql, $params] = $DB->get_in_or_equal($instances, SQL_PARAMS_NAMED);
    $conversationids = $DB->get_fieldset_select('socratic_conversations', 'id', "socraticid {$insql}", $params);
    if ($conversationids) {
        [$msgsql, $msgparams] = $DB->get_in_or_equal($conversationids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('socratic_messages', "conversationid {$msgsql}", $msgparams);
    }
    $DB->delete_records_select('socratic_conversations', "socraticid {$insql}", $params);
    $status[] = [
        'component' => get_string('modulenameplural', 'mod_socratic'),
        'item' => get_string('conversations', 'mod_socratic'),
        'error' => false,
    ];
    return $status;
}
