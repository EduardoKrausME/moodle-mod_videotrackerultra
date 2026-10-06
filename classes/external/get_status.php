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

namespace mod_videotrackerultra\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_videotrackerultra\evaluation_manager;

/**
 * AJAX service used only for the authenticated learner's own state.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_status extends external_api {
    /**
     * Defines parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
        ]);
    }

    /**
     * Recalculates current user's status. There is intentionally no userid parameter.
     *
     * @param int $cmid Course module id.
     * @return array
     */
    public static function execute(int $cmid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cm = get_coursemodule_from_id('videotrackerultra', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/videotrackerultra:view', $context);

        $activity = $DB->get_record('videotrackerultra', ['id' => $cm->instance], '*', MUST_EXIST);
        $evaluation = (new evaluation_manager())->evaluate($activity, $cm, (int)$USER->id, true);
        $facts = $evaluation['facts'];

        return [
            'status' => (string)$evaluation['status'],
            'statuslabel' => get_string('status:' . $evaluation['status'], 'videotrackerultra'),
            'percent' => (float)($facts['percent_watched'] ?? 0),
            'playbacktime' => (float)($facts['playback_time'] ?? 0),
            'sessions' => (int)($facts['session_count'] ?? 0),
            'rulesjson' => json_encode($evaluation['rules'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'violationsjson' => json_encode($evaluation['violations'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * Defines return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Calculated status'),
            'statuslabel' => new external_value(PARAM_TEXT, 'Localized status'),
            'percent' => new external_value(PARAM_FLOAT, 'Server-calculated watched percentage'),
            'playbacktime' => new external_value(PARAM_FLOAT, 'Observed playback time'),
            'sessions' => new external_value(PARAM_INT, 'Session count'),
            'rulesjson' => new external_value(PARAM_RAW, 'Rule matrix as JSON'),
            'violationsjson' => new external_value(PARAM_RAW, 'Failures as JSON'),
        ]);
    }
}
