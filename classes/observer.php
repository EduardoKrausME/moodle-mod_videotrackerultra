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

namespace mod_videotrackerultra;

use completion_info;
use context_module;
use local_video_bridge\analytics\manager as analytics_manager;
use local_video_bridge\event\analytics_updated;

/**
 * Reacts to accepted Video Bridge facts without coupling the bridge to Ultra rules.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {
    /**
     * Recalculates the affected learner when Video Bridge accepts new analytics.
     *
     * @param analytics_updated $event Bridge analytics event.
     * @return void
     */
    public static function bridge_analytics_updated(analytics_updated $event): void {
        global $DB;

        $data = $event->get_data();
        $other = is_array($data['other'] ?? null) ? $data['other'] : [];
        if (($other['component'] ?? '') !== 'mod_videotrackerultra') {
            return;
        }

        $activityid = (int)($other['itemid'] ?? 0);
        $userid = (int)($data['relateduserid'] ?? 0);
        if ($activityid <= 0 || $userid <= 0) {
            return;
        }

        $activity = $DB->get_record('videotrackerultra', ['id' => $activityid]);
        if (!$activity) {
            return;
        }

        $cm = get_coursemodule_from_instance(
            'videotrackerultra',
            $activity->id,
            $activity->course,
            false,
            IGNORE_MISSING
        );
        if (!$cm) {
            return;
        }

        $context = context_module::instance($cm->id);
        if ((int)($data['contextid'] ?? 0) !== $context->id) {
            return;
        }

        $expectedhash = analytics_manager::media_hash(
            (string)$activity->videosource,
            (string)$activity->sourceconfig
        );
        $eventhash = (string)($other['mediahash'] ?? '');
        if ($eventhash === '' || !hash_equals($expectedhash, $eventhash)) {
            return;
        }

        (new evaluation_manager())->evaluate($activity, $cm, $userid, true);

        $completion = new completion_info(get_course($activity->course));
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
    }
}
