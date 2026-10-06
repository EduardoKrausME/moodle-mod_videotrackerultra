<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerultra\event;

/**
 * Fired when a cached validation state changes.
 *
 * @package   mod_videotrackerultra
 */
class evaluation_updated extends \core\event\base {
    /**
     * Initializes event properties.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'videotrackerultra_eval';
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventevaluationupdated', 'videotrackerultra');
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description(): string {
        return "The validation state for user '{$this->relateduserid}' was recalculated.";
    }

    /**
     * URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/videotrackerultra/student.php', [
            'id' => $this->contextinstanceid,
            'userid' => $this->relateduserid,
        ]);
    }
}
