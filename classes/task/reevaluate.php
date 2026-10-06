<?php
// This file is part of Moodle - http://moodle.org/.

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
