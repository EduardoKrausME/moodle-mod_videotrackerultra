<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackerultra/backup/moodle2/restore_videotrackerultra_stepslib.php');

/**
 * Restore task for Video Tracker Ultra.
 */
class restore_videotrackerultra_activity_task extends restore_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_videotrackerultra_activity_structure_step(
            'videotrackerultra_structure',
            'videotrackerultra.xml'
        ));
    }

    public static function define_decode_contents(): array {
        return [];
    }

    public static function define_decode_rules(): array {
        return [];
    }

    public static function define_restore_log_rules(): array {
        return [];
    }

    public static function define_restore_log_rules_for_course(): array {
        return [];
    }
}
