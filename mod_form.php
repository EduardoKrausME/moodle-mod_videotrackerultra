<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\source\manager as source_manager;
use mod_videotrackerultra\rule\evaluator;
use mod_videotrackerultra\segments;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity configuration form.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videotrackerultra_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $sources = new source_manager();
        $options = $sources->get_options(['tracking']);
        if (!$options) {
            throw new moodle_exception('nosubplugins', 'local_video_bridge');
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('videotrackerultraname', 'videotrackerultra'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();

        $mform->addElement('header', 'sourceheader', get_string('sourceheader', 'videotrackerultra'));
        $mform->addElement('select', 'videosource', get_string('videosource', 'videotrackerultra'), $options);
        $mform->setType('videosource', PARAM_PLUGIN);
        $mform->setDefault('videosource', (string)(array_key_first($options) ?? ''));
        $sources->add_form_elements($mform, 'videosource');
        $mform->addElement('static', 'capabilitynotice', '',
            get_string('capabilitynotice', 'videotrackerultra') . $this->capability_matrix($sources));

        $mform->addElement('header', 'rulesheader', get_string('rulesheader', 'videotrackerultra'));
        $this->number('minpercent', 0, 0);
        $this->number('minrealtime', 0, 0);

        $mform->addElement('select', 'maxplaybackrate', get_string('maxplaybackrate', 'videotrackerultra'), [
            '0' => get_string('none'),
            '1' => '1x',
            '1.25' => '1.25x',
            '1.5' => '1.5x',
            '1.75' => '1.75x',
            '2' => '2x',
        ]);
        $mform->setDefault('maxplaybackrate', '0');

        $this->number('maxforwardseeks', -1, -1);
        $this->number('maxseeksize', 0, 0);
        $mform->addElement('selectyesno', 'forbidskipping', get_string('forbidskipping', 'videotrackerultra'));
        $mform->setDefault('forbidskipping', 0);
        $mform->addElement('selectyesno', 'requirestart', get_string('requirestart', 'videotrackerultra'));
        $mform->setDefault('requirestart', 0);
        $mform->addElement('selectyesno', 'requireend', get_string('requireend', 'videotrackerultra'));
        $mform->setDefault('requireend', 0);
        $this->number('mincontinuous', 0, 0);
        $this->number('inactivitythreshold', 60, 1);
        $this->number('maxlonginactivity', -1, -1);
        $this->number('minsessions', 0, 0);
        $this->number('maxsessions', 0, 0);

        $mform->addElement('date_time_selector', 'deadline', get_string('deadline', 'videotrackerultra'), [
            'optional' => true,
        ]);
        $this->number('interruptiontolerance', 5, 0);

        $mform->addElement('header', 'segmentsheader', get_string('segmentsheader', 'videotrackerultra'));
        $mform->addElement('textarea', 'requiredsegmentstext', get_string('requiredsegments', 'videotrackerultra'), [
            'rows' => 6,
            'cols' => 50,
        ]);
        $mform->setType('requiredsegmentstext', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('requiredsegmentstext', 'requiredsegments', 'videotrackerultra');
        $this->number('segmentcoverage', 95, 1);

        $mform->addElement('header', 'policiesheader', get_string('policiesheader', 'videotrackerultra'));
        foreach ($this->policy_fields() as $key => $stringkey) {
            $mform->addElement('select', 'policy_' . $key, get_string($stringkey, 'videotrackerultra'), [
                evaluator::POLICY_INFO => get_string('policyinfo', 'videotrackerultra'),
                evaluator::POLICY_BLOCK => get_string('policyblock', 'videotrackerultra'),
                evaluator::POLICY_REWATCH => get_string('policyrewatch', 'videotrackerultra'),
            ]);
            $mform->setDefault('policy_' . $key, evaluator::POLICY_BLOCK);
        }

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds a numeric field.
     *
     * @param string $name Field name.
     * @param int $default Default.
     * @param int $minimum Minimum accepted by client.
     * @return void
     */
    private function number(string $name, int $default, int $minimum): void {
        $mform = $this->_form;
        $mform->addElement('text', $name, get_string($name, 'videotrackerultra'), ['size' => 8]);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $default);
        $mform->addRule($name, null, 'numeric', null, 'client');
    }

    /**
     * Adds custom completion rule.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $field = 'completionvalid' . $this->get_completion_suffix();
        $this->_form->addElement('advcheckbox', $field, '', get_string('completionvalid', 'videotrackerultra'));
        $this->_form->setDefault($field, 1);
        return [$field];
    }

    /**
     * Returns whether custom completion is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        $field = 'completionvalid' . $this->get_completion_suffix();
        return !empty($data[$field]);
    }

    /**
     * Prepares persisted values.
     *
     * @param array $defaultvalues Values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (array_key_exists('completionvalid', $defaultvalues)) {
            $defaultvalues['completionvalid' . $this->get_completion_suffix()] = $defaultvalues['completionvalid'];
        }
        $defaultvalues['requiredsegmentstext'] = segments::to_text($defaultvalues['requiredsegments'] ?? '[]');

        $policies = json_decode((string)($defaultvalues['policies'] ?? ''), true);
        $policies = is_array($policies) ? $policies : [];
        foreach ($this->policy_fields() as $key => $unused) {
            $defaultvalues['policy_' . $key] = $policies[$key] ?? evaluator::POLICY_BLOCK;
        }

        if (!empty($this->current->instance)) {
            (new source_manager())->prepare_form_data($defaultvalues, $this->context);
        }
    }

    /**
     * Normalizes non-schema form controls before Moodle calls add/update.
     *
     * @return stdClass|false
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return $data;
        }

        $completionfield = 'completionvalid' . $this->get_completion_suffix();
        if (property_exists($data, $completionfield)) {
            $data->completionvalid = !empty($data->{$completionfield}) ? 1 : 0;
            unset($data->{$completionfield});
        }

        if (property_exists($data, 'requiredsegmentstext')) {
            $data->requiredsegments = json_encode(
                segments::parse((string)$data->requiredsegmentstext),
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            unset($data->requiredsegmentstext);
        }

        $policies = [];
        $haspolicies = false;
        foreach ($this->policy_fields() as $key => $unused) {
            $field = 'policy_' . $key;
            if (property_exists($data, $field)) {
                $haspolicies = true;
                $policies[$key] = (string)$data->{$field};
                unset($data->{$field});
            }
        }
        if ($haspolicies) {
            $data->policies = json_encode($policies, JSON_THROW_ON_ERROR);
        }
        return $data;
    }

    /**
     * Validates configuration and provider capabilities.
     *
     * @param array $data Form values.
     * @param array $files Files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $data = (array)$data;
        $errors += (new source_manager())->validation($data, (array)$files);

        if ((int)($data['minpercent'] ?? 0) < 0 || (int)($data['minpercent'] ?? 0) > 100) {
            $errors['minpercent'] = get_string('invalidpercent', 'videotrackerultra');
        }
        if ((int)($data['segmentcoverage'] ?? 95) < 1 || (int)($data['segmentcoverage'] ?? 95) > 100) {
            $errors['segmentcoverage'] = get_string('invalidpercent', 'videotrackerultra');
        }
        foreach (['minrealtime', 'maxseeksize', 'mincontinuous', 'minsessions', 'maxsessions', 'interruptiontolerance'] as $field) {
            if ((int)($data[$field] ?? 0) < 0) {
                $errors[$field] = get_string('invalidnonnegative', 'videotrackerultra');
            }
        }
        foreach (['maxforwardseeks', 'maxlonginactivity'] as $field) {
            if ((int)($data[$field] ?? -1) < -1) {
                $errors[$field] = get_string('invalidminusone', 'videotrackerultra');
            }
        }
        if ((int)($data['inactivitythreshold'] ?? 60) < 1) {
            $errors['inactivitythreshold'] = get_string('invalidnonnegative', 'videotrackerultra');
        }

        try {
            segments::parse((string)($data['requiredsegmentstext'] ?? ''));
        } catch (Throwable $exception) {
            $errors['requiredsegmentstext'] = get_string('invalidsegments', 'videotrackerultra');
        }

        $source = clean_param((string)($data['videosource'] ?? ''), PARAM_PLUGIN);
        if ($source !== '') {
            try {
                $caps = (new source_manager())->get_capabilities($source);
                $needs = [
                    'minpercent' => ['tracking', (int)($data['minpercent'] ?? 0) > 0],
                    'minrealtime' => ['tracking', (int)($data['minrealtime'] ?? 0) > 0],
                    'maxplaybackrate' => ['playbackrate', (float)($data['maxplaybackrate'] ?? 0) > 0],
                    'maxforwardseeks' => ['seeking', (int)($data['maxforwardseeks'] ?? -1) >= 0],
                    'maxseeksize' => ['seeking', (int)($data['maxseeksize'] ?? 0) > 0],
                    'forbidskipping' => ['seeking', !empty($data['forbidskipping'])],
                    'requiredsegmentstext' => ['tracking', trim((string)($data['requiredsegmentstext'] ?? '')) !== ''],
                    'requirestart' => ['tracking', !empty($data['requirestart'])],
                    'requireend' => ['playbackcontrol', !empty($data['requireend'])],
                    'mincontinuous' => ['playbackcontrol', (int)($data['mincontinuous'] ?? 0) > 0],
                    'maxlonginactivity' => ['playbackcontrol', (int)($data['maxlonginactivity'] ?? -1) >= 0],
                    'minsessions' => ['tracking', (int)($data['minsessions'] ?? 0) > 0],
                    'maxsessions' => ['tracking', (int)($data['maxsessions'] ?? 0) > 0],
                ];
                if (empty($caps['tracking'])) {
                    $errors['videosource'] = get_string('capabilityunsupported', 'videotrackerultra', 'tracking');
                }
                foreach ($needs as $field => [$capability, $enabled]) {
                    if ($enabled && empty($caps[$capability])) {
                        $errors[$field] = get_string('capabilityunsupported', 'videotrackerultra', $capability);
                    }
                }
            } catch (moodle_exception $exception) {
                $errors['videosource'] = $exception->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Builds a simple provider capability matrix visible in the configuration form.
     *
     * @param source_manager $manager Source manager.
     * @return string
     */
    private function capability_matrix(source_manager $manager): string {
        $rows = [];
        foreach ($manager->get_options() as $name => $label) {
            $caps = $manager->get_capabilities($name);
            $rows[] = html_writer::tag('tr',
                html_writer::tag('td', s($label)) .
                html_writer::tag('td', !empty($caps['tracking']) ? '✓' : '—') .
                html_writer::tag('td', !empty($caps['seeking']) ? '✓' : '—') .
                html_writer::tag('td', !empty($caps['playbackrate']) ? '✓' : '—') .
                html_writer::tag('td', !empty($caps['playbackcontrol']) ? '✓' : '—')
            );
        }
        $head = html_writer::tag('tr',
            html_writer::tag('th', get_string('videosource', 'videotrackerultra')) .
            html_writer::tag('th', 'tracking') .
            html_writer::tag('th', 'seeking') .
            html_writer::tag('th', 'playbackrate') .
            html_writer::tag('th', 'playbackcontrol')
        );
        return html_writer::tag('table', $head . implode('', $rows), ['class' => 'generaltable mt-2']);
    }

    /**
     * Policy controls persisted in one JSON field.
     *
     * @return array
     */
    private function policy_fields(): array {
        return [
            'percent' => 'policy:percent',
            'realtime' => 'policy:realtime',
            'playbackrate' => 'policy:playbackrate',
            'forwardseeks' => 'policy:forwardseeks',
            'seeksize' => 'policy:seeksize',
            'skipping' => 'policy:skipping',
            'segments' => 'policy:segments',
            'start' => 'policy:start',
            'end' => 'policy:end',
            'continuous' => 'policy:continuous',
            'inactivity' => 'policy:inactivity',
            'minsessions' => 'policy:minsessions',
            'maxsessions' => 'policy:maxsessions',
            'deadline' => 'policy:deadline',
        ];
    }

    /**
     * Maps the completion field back to the activity column for bulk completion forms.
     *
     * @param stdClass $data Form data.
     * @return void
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);
        $field = 'completionvalid' . $this->get_completion_suffix();
        if (property_exists($data, $field)) {
            $data->completionvalid = !empty($data->{$field}) ? 1 : 0;
        }
    }
}
