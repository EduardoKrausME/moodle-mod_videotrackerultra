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

namespace mod_videotrackerultra\privacy;

use context;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the recalculable evaluation cache.
 *
 * Playback facts themselves belong to local_video_bridge and are covered by
 * that plugin's Privacy API.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Declares stored personal data.
     *
     * @param collection $collection Collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videotrackerultra_eval', [
            'userid' => 'privacy:metadata:evaluation:userid',
            'status' => 'privacy:metadata:evaluation:status',
            'windowstart' => 'privacy:metadata:evaluation:windowstart',
            'rulesjson' => 'privacy:metadata:evaluation:rulesjson',
            'violationsjson' => 'privacy:metadata:evaluation:violationsjson',
            'factsjson' => 'privacy:metadata:evaluation:factsjson',
            'timecreated' => 'privacy:metadata:evaluation:timecreated',
            'timemodified' => 'privacy:metadata:evaluation:timemodified',
        ], 'privacy:metadata:evaluation');
        return $collection;
    }

    /**
     * Finds contexts containing evaluations for one user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm
                    ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module AND m.name = :modname
                  JOIN {videotrackerultra_eval} e
                    ON e.videotrackerultraid = cm.instance
                 WHERE e.userid = :userid";
        $list = new contextlist();
        $list->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'videotrackerultra',
            'userid' => $userid,
        ]);
        return $list;
    }

    /**
     * Exports evaluation cache.
     *
     * @param approved_contextlist $contextlist Approved list.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videotrackerultra', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $record = $DB->get_record('videotrackerultra_eval', [
                'videotrackerultraid' => $cm->instance,
                'userid' => $userid,
            ]);
            if (!$record) {
                continue;
            }
            writer::with_context($context)->export_data(
                [get_string('privacy:evaluation', 'videotrackerultra')],
                (object)[
                    'status' => $record->status,
                    'windowstart' => $record->windowstart ? transform::datetime($record->windowstart) : null,
                    'rules' => json_decode($record->rulesjson, true) ?: [],
                    'violations' => json_decode($record->violationsjson, true) ?: [],
                    'facts' => json_decode($record->factsjson, true) ?: [],
                    'timecreated' => transform::datetime($record->timecreated),
                    'timemodified' => transform::datetime($record->timemodified),
                ]
            );
        }
    }

    /**
     * Deletes all evaluation rows in a module context.
     *
     * @param context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('videotrackerultra', $context->instanceid, 0, false, IGNORE_MISSING);
        if ($cm) {
            $DB->delete_records('videotrackerultra_eval', ['videotrackerultraid' => $cm->instance]);
        }
    }

    /**
     * Deletes one user's evaluation rows in approved contexts.
     *
     * @param approved_contextlist $contextlist Context list.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('videotrackerultra', $context->instanceid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $DB->delete_records('videotrackerultra_eval', [
                    'videotrackerultraid' => $cm->instance,
                    'userid' => $userid,
                ]);
            }
        }
    }
}
