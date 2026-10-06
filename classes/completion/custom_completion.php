<?php
// This file is part of Moodle - http://moodle.org/.

namespace mod_videotrackerultra\completion;

use coding_exception;
use core_completion\activity_custom_completion;
use mod_videotrackerultra\evaluation_manager;

/**
 * Recalculable custom completion based on the rule set.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Returns the current state from a fresh evaluation of Video Bridge facts.
     *
     * @param string $rule Rule name.
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        if (!$this->is_defined($rule)) {
            throw new coding_exception("Undefined custom completion rule '{$rule}'");
        }

        $activity = $DB->get_record('videotrackerultra', ['id' => $this->cm->instance], '*', MUST_EXIST);
        $evaluation = (new evaluation_manager())->evaluate($activity, $this->cm, $this->userid, true);
        return in_array($evaluation['status'], ['valid', 'completed'], true)
            ? COMPLETION_COMPLETE
            : COMPLETION_INCOMPLETE;
    }

    /**
     * Returns supported custom rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionvalid'];
    }

    /**
     * Returns rule descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionvalid' => get_string('completiondetail:valid', 'videotrackerultra'),
        ];
    }

    /**
     * Returns display ordering.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpassgrade',
            'completionvalid',
        ];
    }
}
