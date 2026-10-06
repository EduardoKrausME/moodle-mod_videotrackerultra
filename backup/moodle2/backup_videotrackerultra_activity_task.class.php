<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/videotrackerultra/backup/moodle2/backup_videotrackerultra_stepslib.php');

/**
 * Backup task for Video Tracker Ultra.
 */
class backup_videotrackerultra_activity_task extends backup_activity_task {
    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_videotrackerultra_activity_structure_step(
            'videotrackerultra_structure',
            'videotrackerultra.xml'
        ));
    }

    public static function encode_content_links($content): string {
        return $content;
    }
}
