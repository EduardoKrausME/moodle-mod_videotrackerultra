<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

/**
 * Restores Video Tracker Ultra configuration and optional cached evaluations.
 */
class restore_videotrackerultra_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines restore paths.
     *
     * @return array
     */
    protected function define_structure(): array {
        $paths = [
            new restore_path_element('videotrackerultra', '/activity/videotrackerultra'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'videotrackerultra_evaluation',
                '/activity/videotrackerultra/evaluations/evaluation'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores activity record.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videotrackerultra(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->id = $DB->insert_record('videotrackerultra', $data);
        $this->apply_activity_instance($data->id);
        $this->set_mapping('videotrackerultra', $oldid, $data->id, true);
    }

    /**
     * Restores one cached evaluation. It remains recalculable from Video Bridge facts.
     *
     * @param array $data Backup data.
     * @return void
     */
    protected function process_videotrackerultra_evaluation(array $data): void {
        global $DB;

        $data = (object)$data;
        $data->videotrackerultraid = $this->get_new_parentid('videotrackerultra');
        $data->userid = $this->get_mappingid('user', $data->userid, 0);
        if (!$data->userid) {
            return;
        }
        $DB->insert_record('videotrackerultra_eval', $data);
    }

    /**
     * Restores shared Video Bridge source files.
     *
     * @return void
     */
    protected function after_execute(): void {
        $this->add_related_files('local_video_bridge', 'video', null);
    }
}
