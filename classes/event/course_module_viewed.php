<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerultra\event;

/**
 * Course module viewed event.
 *
 * @package   mod_videotrackerultra
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Initializes event properties.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'videotrackerultra';
        parent::init();
    }

    /**
     * Returns localized name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventcoursemoduleviewed', 'core');
    }
}
