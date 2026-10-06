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

use context_module;
use local_video_bridge\analytics\manager as analytics_manager;
use mod_videotrackerultra\rule\evaluator;
use stdClass;

/**
 * Coordinates Video Bridge facts, rule evaluation and the recalculable cache.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation_manager {
    /**
     * Recalculates one learner from source facts.
     *
     * @param stdClass $activity Activity.
     * @param stdClass $cm Course module.
     * @param int $userid Learner id.
     * @param bool $persist Whether to persist the reporting cache.
     * @return array
     */
    public function evaluate(stdClass $activity, stdClass $cm, int $userid, bool $persist = true): array {
        global $DB;

        $context = context_module::instance($cm->id);
        $existing = $DB->get_record('videotrackerultra_eval', [
            'videotrackerultraid' => $activity->id,
            'userid' => $userid,
        ]);
        $windowstart = $existing ? (int)$existing->windowstart : 0;

        $mediahash = analytics_manager::media_hash(
            (string)$activity->videosource,
            (string)$activity->sourceconfig
        );
        $filters = $windowstart > 0 ? ['from' => $windowstart] : [];
        $facts = analytics_manager::get_facts(
            $context->id,
            'mod_videotrackerultra',
            (int)$activity->id,
            $mediahash,
            $userid,
            $filters
        );

        $evaluation = evaluator::evaluate($activity, $facts);
        if ($windowstart > 0 && $evaluation['status'] === 'notstarted') {
            $evaluation['status'] = 'waiting';
        }

        $newwindowstart = $windowstart;
        if (!empty($evaluation['requires_rewatch']) && !empty($facts['latest_session'])) {
            $latest = $facts['latest_session'];
            $boundary = max(
                (int)($latest['started_at'] ?? 0),
                (int)($latest['ended_at'] ?? 0)
            ) + 1;
            if ($boundary > $newwindowstart) {
                $newwindowstart = $boundary;
            }
        }

        $evaluation['facts'] = $facts;
        $evaluation['windowstart'] = $newwindowstart;
        if ($persist) {
            $this->persist($activity->id, $userid, $evaluation, $facts, $newwindowstart, $existing ?: null);
        }
        return $evaluation;
    }

    /**
     * Returns a cached row when present.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @return stdClass|null
     */
    public function get_cached(int $activityid, int $userid): ?stdClass {
        global $DB;
        $record = $DB->get_record('videotrackerultra_eval', [
            'videotrackerultraid' => $activityid,
            'userid' => $userid,
        ]);
        return $record ?: null;
    }

    /**
     * Persists the latest calculated result as a cache, not as the source of truth.
     *
     * @param int $activityid Activity id.
     * @param int $userid User id.
     * @param array $evaluation Evaluation.
     * @param array $facts Facts.
     * @param int $windowstart Fresh-viewing boundary.
     * @param stdClass|null $existing Existing row.
     * @return void
     */
    private function persist(
        int $activityid,
        int $userid,
        array $evaluation,
        array $facts,
        int $windowstart,
        ?stdClass $existing
    ): void {
        global $DB;

        $now = time();
        $factsummary = [
            'percent_watched' => (float)($facts['percent_watched'] ?? 0),
            'unique_watch_time' => (float)($facts['unique_watch_time'] ?? 0),
            'playback_time' => (float)($facts['playback_time'] ?? 0),
            'real_session_time' => (float)($facts['real_session_time'] ?? 0),
            'maximum_playback_rate' => (float)($facts['maximum_playback_rate'] ?? 1),
            'average_playback_rate' => (float)($facts['average_playback_rate'] ?? 1),
            'seeks_forward' => (int)($facts['seeks_forward'] ?? 0),
            'largest_forward_seek' => (float)($facts['largest_forward_seek'] ?? 0),
            'pause_count' => (int)($facts['pause_count'] ?? 0),
            'session_count' => (int)($facts['session_count'] ?? 0),
            'reached_end' => !empty($facts['reached_end']),
            'duration' => (float)($facts['duration'] ?? 0),
            'latest_session' => $facts['latest_session'] ?? null,
        ];

        $record = (object)[
            'videotrackerultraid' => $activityid,
            'userid' => $userid,
            'status' => (string)$evaluation['status'],
            'windowstart' => $windowstart,
            'rulesjson' => json_encode($evaluation['rules'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'violationsjson' => json_encode($evaluation['violations'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'factsjson' => json_encode($factsummary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'timemodified' => $now,
        ];

        $oldstatus = $existing ? (string)$existing->status : null;
        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('videotrackerultra_eval', $record);
        } else {
            $record->timecreated = $now;
            try {
                $record->id = $DB->insert_record('videotrackerultra_eval', $record);
            } catch (\dml_write_exception $exception) {
                $current = $DB->get_record('videotrackerultra_eval', [
                    'videotrackerultraid' => $activityid,
                    'userid' => $userid,
                ], '*', MUST_EXIST);
                $record->id = $current->id;
                $record->timecreated = $current->timecreated;
                $oldstatus = (string)$current->status;
                $DB->update_record('videotrackerultra_eval', $record);
            }
        }

        if ($oldstatus !== $record->status) {
            $cm = get_coursemodule_from_instance('videotrackerultra', $activityid, 0, false, IGNORE_MISSING);
            if ($cm) {
                $event = \mod_videotrackerultra\event\evaluation_updated::create([
                    'objectid' => (int)$record->id,
                    'context' => \context_module::instance($cm->id),
                    'relateduserid' => $userid,
                    'other' => [
                        'oldstatus' => $oldstatus ?? '',
                        'newstatus' => $record->status,
                    ],
                ]);
                $event->trigger();
            }
        }
    }
}
