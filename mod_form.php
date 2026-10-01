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
 * Activity settings form for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/lib.php');

/**
 * Module form.
 */
class mod_socratic_mod_form extends moodleform_mod {
    /**
     * Defines the activity form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('textarea', 'objective', get_string('objective', 'mod_socratic'), ['rows' => 5, 'cols' => 80]);
        $mform->setType('objective', PARAM_RAW);
        $mform->addRule('objective', null, 'required', null, 'client');
        $mform->addHelpButton('objective', 'objective', 'mod_socratic');

        $mform->addElement('textarea', 'contentbase', get_string('contentbase', 'mod_socratic'), ['rows' => 14, 'cols' => 80]);
        $mform->setType('contentbase', PARAM_RAW);
        $mform->addRule('contentbase', null, 'required', null, 'client');
        $mform->addHelpButton('contentbase', 'contentbase', 'mod_socratic');

        $fileoptions = [
            'subdirs' => 0,
            'maxfiles' => 20,
            'maxbytes' => 1024 * 1024,
            'accepted_types' => ['.txt', '.md', '.html', '.htm', '.csv', '.json', '.xml'],
        ];
        $mform->addElement('filemanager', 'supportfiles', get_string('supportfiles', 'mod_socratic'), null, $fileoptions);
        $mform->addHelpButton('supportfiles', 'supportfiles', 'mod_socratic');

        $mform->addElement('textarea', 'tutorbehavior', get_string('tutorbehavior', 'mod_socratic'), ['rows' => 6, 'cols' => 80]);
        $mform->setType('tutorbehavior', PARAM_RAW);
        $mform->addHelpButton('tutorbehavior', 'tutorbehavior', 'mod_socratic');

        $mform->addElement('text', 'maxinteractions', get_string('maxinteractions', 'mod_socratic'), ['size' => 6]);
        $mform->setType('maxinteractions', PARAM_INT);
        $mform->setDefault('maxinteractions', 20);
        $mform->addRule('maxinteractions', null, 'required', null, 'client');
        $mform->addRule('maxinteractions', null, 'numeric', null, 'client');
        $mform->addHelpButton('maxinteractions', 'maxinteractions', 'mod_socratic');

        $mform->addElement('advcheckbox', 'allowhint', get_string('allowhint', 'mod_socratic'));
        $mform->setDefault('allowhint', 1);
        $mform->addElement('advcheckbox', 'allowfinalanswer', get_string('allowfinalanswer', 'mod_socratic'));
        $mform->setDefault('allowfinalanswer', 0);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds Socratic custom completion fields to Moodle's completion section.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $criterionfield = 'completioncriterion' . $suffix;
        $interactionsfield = 'completioninteractions' . $suffix;
        $options = [
            SOCRATIC_COMPLETION_INTERACTIONS => get_string('completioncriterion:interactions', 'mod_socratic'),
            SOCRATIC_COMPLETION_ENDED => get_string('completioncriterion:ended', 'mod_socratic'),
            SOCRATIC_COMPLETION_TEACHER => get_string('completioncriterion:teacher', 'mod_socratic'),
            SOCRATIC_COMPLETION_MANUAL => get_string('completioncriterion:manual', 'mod_socratic'),
        ];
        $mform->addElement('select', $criterionfield, get_string('completioncriterion', 'mod_socratic'), $options);
        $mform->setDefault($criterionfield, SOCRATIC_COMPLETION_INTERACTIONS);

        $mform->addElement('text', $interactionsfield, get_string('completioninteractions', 'mod_socratic'), ['size' => 6]);
        $mform->setType($interactionsfield, PARAM_INT);
        $mform->setDefault($interactionsfield, 5);
        $mform->hideIf($interactionsfield, $criterionfield, 'neq', SOCRATIC_COMPLETION_INTERACTIONS);
        return [$criterionfield, $interactionsfield];
    }

    /**
     * Reports whether a custom completion rule is enabled.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        $field = 'completioncriterion' . $this->get_suffix();
        return !empty($data[$field]) && $data[$field] !== SOCRATIC_COMPLETION_MANUAL;
    }

    /**
     * Ensures Moodle's completion tracking mode matches the selected criterion.
     *
     * @param stdClass $data
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);
        $suffix = $this->get_suffix();
        $field = 'completioncriterion' . $suffix;
        if ($suffix === '' && !empty($data->{$field})) {
            $data->completion = $data->{$field} === SOCRATIC_COMPLETION_MANUAL
                ? COMPLETION_TRACKING_MANUAL
                : COMPLETION_TRACKING_AUTOMATIC;
        }
    }

    /**
     * Prepares support files when editing an existing activity.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);
        if (!$this->_cm) {
            return;
        }
        $context = context_module::instance($this->_cm->id);
        $draftitemid = file_get_submitted_draft_itemid('supportfiles');
        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'mod_socratic',
            'supportfiles',
            0,
            [
                'subdirs' => 0,
                'maxfiles' => 20,
                'maxbytes' => 1024 * 1024,
                'accepted_types' => ['.txt', '.md', '.html', '.htm', '.csv', '.json', '.xml'],
            ]
        );
        $defaultvalues['supportfiles'] = $draftitemid;
    }

    /**
     * Validates cross-field constraints.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ((int)$data['maxinteractions'] < 1 || (int)$data['maxinteractions'] > 200) {
            $errors['maxinteractions'] = get_string('invaliddata', 'error');
        }
        if (($data['completioncriterion'] ?? '') === SOCRATIC_COMPLETION_INTERACTIONS) {
            $required = (int)($data['completioninteractions'] ?? 0);
            if ($required < 1 || $required > (int)$data['maxinteractions']) {
                $errors['completioninteractions'] = get_string('invaliddata', 'error');
            }
        }
        return $errors;
    }
}
