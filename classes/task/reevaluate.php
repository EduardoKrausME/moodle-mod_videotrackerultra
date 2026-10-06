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

namespace mod_videotrackerultra\task;

use completion_info;
use core\task\scheduled_task;
use mod_videotrackerultra\evaluation_manager;

/**
 * Re-evaluates existing learner cache rows so deadlines and corrected facts propagate.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reevaluate extends scheduled_task {
    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskreevaluate', 'videotrackerultra');
    }

    /**
     * Executes the task.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $manager = new evaluation_manager();
        $recordset = $DB->get_recordset_sql(
            "SELECT e.*
               FROM {videotrackerultra_eval} e
              ORDER BY e.timemodified ASC"
        );

        foreach ($recordset as $cached) {
            $activity = $DB->get_record('videotrackerultra', ['id' => $cached->videotrackerultraid]);
            if (!$activity) {
                continue;
            }
            $cm = get_coursemodule_from_instance(
                'videotrackerultra',
                $activity->id,
                $activity->course,
                false,
                IGNORE_MISSING
            );
            if (!$cm) {
                continue;
            }

            $manager->evaluate($activity, $cm, (int)$cached->userid, true);
            $completion = new completion_info(get_course($activity->course));
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_UNKNOWN, (int)$cached->userid);
            }
        }
        $recordset->close();
    }
}
