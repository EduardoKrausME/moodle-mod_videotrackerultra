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

namespace mod_videotrackerultra\rule;

use mod_videotrackerultra\segments;
use stdClass;

/**
 * Pure server-side evaluator for Video Tracker Ultra rules.
 *
 * It receives provider-independent facts and never trusts a browser-provided
 * validity, completion flag, or watched percentage.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluator {
    /** @var string */
    public const POLICY_INFO = 'info';

    /** @var string */
    public const POLICY_BLOCK = 'block';

    /** @var string */
    public const POLICY_REWATCH = 'rewatch';

    /** @var string */
    public const RESULT_OK = 'ok';

    /** @var string */
    public const RESULT_PENDING = 'pending';

    /** @var string */
    public const RESULT_FAIL = 'fail';

    /** @var float */
    private const START_SECONDS = 5.0;

    /**
     * Evaluates all enabled rules.
     *
     * @param stdClass $activity Activity configuration.
     * @param array $facts Facts returned by local_video_bridge\analytics\manager.
     * @param int|null $now Current server time, injectable for tests.
     * @return array
     */
    public static function evaluate(stdClass $activity, array $facts, ?int $now = null): array {
        $now ??= time();
        $policies = self::policies($activity);
        $rules = [];
        $deadlinepassed = !empty($activity->deadline) && $now > (int)$activity->deadline;
        $latest = is_array($facts['latest_session'] ?? null) ? $facts['latest_session'] : [];
        $hasactivity = (int)($facts['session_count'] ?? 0) > 0 ||
            (float)($facts['percent_watched'] ?? 0) > 0;

        if ((int)$activity->minpercent > 0) {
            $actual = (float)($facts['percent_watched'] ?? 0);
            self::add($rules, 'percent', $actual >= (int)$activity->minpercent,
                get_string('rule:percent', 'videotrackerultra', (int)$activity->minpercent),
                get_string('evidence:percent', 'videotrackerultra', round($actual, 2)),
                $policies, true, $deadlinepassed);
        }

        if ((int)$activity->minrealtime > 0) {
            $actual = (float)($facts['playback_time'] ?? 0);
            self::add($rules, 'realtime', $actual >= (int)$activity->minrealtime,
                get_string('rule:realtime', 'videotrackerultra', (int)$activity->minrealtime),
                get_string('evidence:realtime', 'videotrackerultra', round($actual, 1)),
                $policies, true, $deadlinepassed);
        }

        if ((float)$activity->maxplaybackrate > 0) {
            $actual = (float)($latest['maximum_playback_rate'] ?? $facts['maximum_playback_rate'] ?? 1);
            self::add($rules, 'playbackrate', $actual <= (float)$activity->maxplaybackrate,
                get_string('rule:playbackrate', 'videotrackerultra', (float)$activity->maxplaybackrate),
                get_string('evidence:playbackrate', 'videotrackerultra', round($actual, 2)),
                $policies, false, false);
        }

        if ((int)$activity->maxforwardseeks >= 0) {
            $actual = (int)($latest['seeks_forward'] ?? 0);
            self::add($rules, 'forwardseeks', $actual <= (int)$activity->maxforwardseeks,
                get_string('rule:forwardseeks', 'videotrackerultra', (int)$activity->maxforwardseeks),
                get_string('evidence:forwardseeks', 'videotrackerultra', $actual),
                $policies, false, false);
        }

        if ((int)$activity->maxseeksize > 0) {
            $actual = (float)($latest['largest_forward_seek'] ?? 0);
            self::add($rules, 'seeksize', $actual <= (int)$activity->maxseeksize,
                get_string('rule:seeksize', 'videotrackerultra', (int)$activity->maxseeksize),
                get_string('evidence:seeksize', 'videotrackerultra', round($actual, 1)),
                $policies, false, false);
        }

        if (!empty($activity->forbidskipping)) {
            $actual = (int)($latest['seeks_forward'] ?? 0);
            self::add($rules, 'skipping', $actual === 0,
                get_string('rule:skipping', 'videotrackerultra'),
                get_string('evidence:forwardseeks', 'videotrackerultra', $actual),
                $policies, false, false);
        }

        $ranges = self::merge_ranges(
            (array)($facts['watched_ranges'] ?? []),
            max(0, (int)$activity->interruptiontolerance)
        );
        $coverage = max(1, min(100, (int)$activity->segmentcoverage));

        foreach (segments::decode((string)$activity->requiredsegments) as $index => $segment) {
            $start = (float)($segment['start'] ?? 0);
            $end = (float)($segment['end'] ?? 0);
            if ($end <= $start) {
                continue;
            }
            $actual = self::segment_coverage($ranges, $start, $end);
            $key = 'segment:' . $index;
            self::add($rules, $key, $actual >= $coverage,
                get_string('rule:segment', 'videotrackerultra',
                    $segment['label'] ?? segments::format($start) . '–' . segments::format($end)),
                get_string('evidence:segment', 'videotrackerultra', round($actual, 1)),
                $policies, true, $deadlinepassed, 'segments');
        }

        if (!empty($activity->requirestart)) {
            $duration = max(0.0, (float)($facts['duration'] ?? 0));
            $end = $duration > 0 ? min(self::START_SECONDS, $duration) : self::START_SECONDS;
            $actual = self::segment_coverage($ranges, 0.0, $end);
            self::add($rules, 'start', $actual >= $coverage,
                get_string('rule:start', 'videotrackerultra'),
                get_string('evidence:segment', 'videotrackerultra', round($actual, 1)),
                $policies, true, $deadlinepassed);
        }

        if (!empty($activity->requireend)) {
            $actual = !empty($facts['reached_end']);
            self::add($rules, 'end', $actual,
                get_string('rule:end', 'videotrackerultra'),
                get_string('evidence:end', 'videotrackerultra',
                    $actual ? get_string('yes', 'videotrackerultra') : get_string('no', 'videotrackerultra')),
                $policies, true, $deadlinepassed);
        }

        if ((int)$activity->mincontinuous > 0) {
            $longest = 0.0;
            foreach ((array)($facts['continuous_blocks'] ?? []) as $block) {
                if (is_array($block) && isset($block[2])) {
                    $longest = max($longest, (float)$block[2]);
                }
            }
            self::add($rules, 'continuous', $longest >= (int)$activity->mincontinuous,
                get_string('rule:continuous', 'videotrackerultra', (int)$activity->mincontinuous),
                get_string('evidence:continuous', 'videotrackerultra', round($longest, 1)),
                $policies, true, $deadlinepassed);
        }

        if ((int)$activity->maxlonginactivity >= 0) {
            $threshold = max(1, (int)$activity->inactivitythreshold);
            $longgaps = array_filter(
                (array)($latest['inactivity_gaps'] ?? []),
                static fn($seconds): bool => (float)$seconds >= $threshold
            );
            $actual = count($longgaps);
            self::add($rules, 'inactivity', $actual <= (int)$activity->maxlonginactivity,
                get_string('rule:inactivity', 'videotrackerultra', (int)$activity->maxlonginactivity),
                get_string('evidence:inactivity', 'videotrackerultra', $actual),
                $policies, false, false);
        }

        if ((int)$activity->minsessions > 0) {
            $actual = (int)($facts['session_count'] ?? 0);
            self::add($rules, 'minsessions', $actual >= (int)$activity->minsessions,
                get_string('rule:minsessions', 'videotrackerultra', (int)$activity->minsessions),
                get_string('evidence:sessions', 'videotrackerultra', $actual),
                $policies, true, $deadlinepassed);
        }

        if ((int)$activity->maxsessions > 0) {
            $actual = (int)($facts['session_count'] ?? 0);
            self::add($rules, 'maxsessions', $actual <= (int)$activity->maxsessions,
                get_string('rule:maxsessions', 'videotrackerultra', (int)$activity->maxsessions),
                get_string('evidence:sessions', 'videotrackerultra', $actual),
                $policies, false, false);
        }

        if (!empty($activity->deadline)) {
            $passed = $now > (int)$activity->deadline;
            self::add($rules, 'deadline', !$passed,
                get_string('rule:deadline', 'videotrackerultra', userdate((int)$activity->deadline)),
                userdate((int)$activity->deadline),
                $policies, false, false);
        }

        $blockingfail = false;
        $blockingpending = false;
        $rewatch = false;
        $met = 0;
        $pending = 0;
        $violations = [];

        foreach ($rules as $rule) {
            if ($rule['result'] === self::RESULT_OK) {
                $met++;
                continue;
            }
            $pending++;
            $violations[] = $rule;
            if ($rule['policy'] === self::POLICY_INFO) {
                continue;
            }
            if ($rule['result'] === self::RESULT_PENDING) {
                $blockingpending = true;
            } else {
                $blockingfail = true;
                if ($rule['policy'] === self::POLICY_REWATCH) {
                    $rewatch = true;
                }
            }
        }

        if (!$hasactivity) {
            $status = 'notstarted';
        } else if ($blockingfail) {
            $status = 'invalid';
        } else if ($blockingpending) {
            $status = 'inprogress';
        } else {
            $status = !empty($activity->completionvalid) ? 'completed' : 'valid';
        }

        return [
            'status' => $status,
            'rules' => array_values($rules),
            'violations' => $violations,
            'rulesmet' => $met,
            'rulespending' => $pending,
            'requires_rewatch' => $rewatch,
        ];
    }

    /**
     * Adds one normalized rule result.
     *
     * @param array $rules Destination array.
     * @param string $key Rule key.
     * @param bool $passed Whether the rule currently passes.
     * @param string $label Human-readable rule.
     * @param string $evidence Evidence.
     * @param array $policies Policies.
     * @param bool $canimprove Whether future viewing can satisfy the rule.
     * @param bool $deadlinepassed Whether a deadline already prevents improvement.
     * @param string|null $policykey Optional policy key.
     * @return void
     */
    private static function add(
        array &$rules,
        string $key,
        bool $passed,
        string $label,
        string $evidence,
        array $policies,
        bool $canimprove,
        bool $deadlinepassed,
        ?string $policykey = null
    ): void {
        $policy = $policies[$policykey ?? $key] ?? self::POLICY_BLOCK;
        $result = self::RESULT_OK;
        if (!$passed) {
            $result = $canimprove && !$deadlinepassed ? self::RESULT_PENDING : self::RESULT_FAIL;
        }
        $rules[] = [
            'key' => $key,
            'label' => $label,
            'result' => $result,
            'evidence' => $evidence,
            'policy' => $policy,
            'blocking' => $policy !== self::POLICY_INFO,
        ];
    }

    /**
     * Decodes policies with safe defaults.
     *
     * @param stdClass $activity Activity.
     * @return array
     */
    private static function policies(stdClass $activity): array {
        $decoded = json_decode((string)($activity->policies ?? ''), true);
        $decoded = is_array($decoded) ? $decoded : [];
        foreach ($decoded as $key => $policy) {
            if (!in_array($policy, [self::POLICY_INFO, self::POLICY_BLOCK, self::POLICY_REWATCH], true)) {
                unset($decoded[$key]);
            }
        }
        return $decoded;
    }

    /**
     * Merges watched ranges and tolerates small content gaps configured by the teacher.
     *
     * @param array $ranges Watched ranges.
     * @param int $tolerance Allowed gap in seconds.
     * @return array
     */
    private static function merge_ranges(array $ranges, int $tolerance): array {
        $clean = [];
        foreach ($ranges as $range) {
            if (!is_array($range) || count($range) < 2) {
                continue;
            }
            $start = max(0.0, (float)$range[0]);
            $end = max($start, (float)$range[1]);
            if ($end > $start) {
                $clean[] = [$start, $end];
            }
        }
        usort($clean, static fn(array $a, array $b): int => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($clean as $range) {
            $last = count($merged) - 1;
            if ($last >= 0 && $range[0] <= $merged[$last][1] + $tolerance) {
                $merged[$last][1] = max($merged[$last][1], $range[1]);
            } else {
                $merged[] = $range;
            }
        }
        return $merged;
    }

    /**
     * Calculates coverage of one required interval.
     *
     * @param array $ranges Watched ranges.
     * @param float $start Required start.
     * @param float $end Required end.
     * @return float
     */
    private static function segment_coverage(array $ranges, float $start, float $end): float {
        if ($end <= $start) {
            return 0.0;
        }
        $covered = 0.0;
        foreach ($ranges as $range) {
            $covered += max(0.0, min($end, $range[1]) - max($start, $range[0]));
        }
        return min(100.0, round(($covered / ($end - $start)) * 100, 2));
    }
}
