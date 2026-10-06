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

use advanced_testcase;
use mod_videotrackerultra\rule\evaluator;

/**
 * Unit coverage for every Video Tracker Ultra rule family.
 *
 * @package   mod_videotrackerultra
 * @covers    \mod_videotrackerultra\rule\evaluator
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class rule_evaluator_test extends advanced_testcase {
    /**
     * Returns a neutral activity with every rule disabled.
     *
     * @return \stdClass
     */
    private function activity(): \stdClass {
        return (object)[
            'minpercent' => 0,
            'minrealtime' => 0,
            'maxplaybackrate' => 0,
            'maxforwardseeks' => -1,
            'maxseeksize' => 0,
            'forbidskipping' => 0,
            'requirestart' => 0,
            'requireend' => 0,
            'mincontinuous' => 0,
            'inactivitythreshold' => 60,
            'maxlonginactivity' => -1,
            'minsessions' => 0,
            'maxsessions' => 0,
            'deadline' => 0,
            'interruptiontolerance' => 0,
            'segmentcoverage' => 95,
            'requiredsegments' => '[]',
            'policies' => '{}',
            'completionvalid' => 1,
        ];
    }

    /**
     * Returns a valid baseline fact set.
     *
     * @return array
     */
    private function facts(): array {
        return [
            'percent_watched' => 100,
            'unique_watch_time' => 100,
            'playback_time' => 100,
            'real_session_time' => 100,
            'maximum_playback_rate' => 1,
            'average_playback_rate' => 1,
            'seeks_forward' => 0,
            'seeks_backward' => 0,
            'largest_forward_seek' => 0,
            'pause_count' => 0,
            'session_count' => 1,
            'reached_end' => true,
            'watched_ranges' => [[0, 100]],
            'continuous_blocks' => [[0, 100, 100]],
            'inactivity_gaps' => [],
            'duration' => 100,
            'latest_session' => [
                'started_at' => 1000,
                'ended_at' => 1100,
                'maximum_playback_rate' => 1,
                'seeks_forward' => 0,
                'seeks_backward' => 0,
                'largest_forward_seek' => 0,
                'inactivity_gaps' => [],
            ],
        ];
    }

    /**
     * Asserts one rule result.
     *
     * @param \stdClass $activity Activity.
     * @param array $facts Facts.
     * @param string $key Rule key prefix.
     * @param string $expected Expected result.
     * @param int $now Time.
     * @return void
     */
    private function assert_rule(\stdClass $activity, array $facts, string $key, string $expected, int $now = 2000): void {
        $result = evaluator::evaluate($activity, $facts, $now);
        $matches = array_values(array_filter(
            $result['rules'],
            static fn(array $rule): bool => $rule['key'] === $key
        ));
        $this->assertCount(1, $matches, 'Expected rule ' . $key);
        $this->assertSame($expected, $matches[0]['result']);
    }

    /**
     * Method test_minimum_percentage_rule.
     *
     * @return void Return value.
     */
    public function test_minimum_percentage_rule(): void {
        $a = $this->activity();
        $a->minpercent = 90;
        $f = $this->facts();
        $f['percent_watched'] = 80;
        $this->assert_rule($a, $f, 'percent', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_minimum_real_playback_time_rule.
     *
     * @return void Return value.
     */
    public function test_minimum_real_playback_time_rule(): void {
        $a = $this->activity();
        $a->minrealtime = 120;
        $f = $this->facts();
        $f['playback_time'] = 80;
        $this->assert_rule($a, $f, 'realtime', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_maximum_playback_rate_rule.
     *
     * @return void Return value.
     */
    public function test_maximum_playback_rate_rule(): void {
        $a = $this->activity();
        $a->maxplaybackrate = 1.5;
        $f = $this->facts();
        $f['latest_session']['maximum_playback_rate'] = 2;
        $this->assert_rule($a, $f, 'playbackrate', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_forward_seek_count_rule.
     *
     * @return void Return value.
     */
    public function test_forward_seek_count_rule(): void {
        $a = $this->activity();
        $a->maxforwardseeks = 1;
        $f = $this->facts();
        $f['latest_session']['seeks_forward'] = 2;
        $this->assert_rule($a, $f, 'forwardseeks', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_forward_seek_size_rule.
     *
     * @return void Return value.
     */
    public function test_forward_seek_size_rule(): void {
        $a = $this->activity();
        $a->maxseeksize = 60;
        $f = $this->facts();
        $f['latest_session']['largest_forward_seek'] = 61;
        $this->assert_rule($a, $f, 'seeksize', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_skip_prohibition_rule.
     *
     * @return void Return value.
     */
    public function test_skip_prohibition_rule(): void {
        $a = $this->activity();
        $a->forbidskipping = 1;
        $f = $this->facts();
        $f['latest_session']['seeks_forward'] = 1;
        $this->assert_rule($a, $f, 'skipping', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_required_segment_is_independent_from_overall_percentage.
     *
     * @return void Return value.
     */
    public function test_required_segment_is_independent_from_overall_percentage(): void {
        $a = $this->activity();
        $a->minpercent = 90;
        $a->requiredsegments = json_encode([['start' => 40, 'end' => 60, 'label' => '00:40–01:00']]);
        $f = $this->facts();
        $f['percent_watched'] = 95;
        $f['watched_ranges'] = [[0, 40], [60, 100]];
        $this->assert_rule($a, $f, 'segment:0', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_beginning_rule.
     *
     * @return void Return value.
     */
    public function test_beginning_rule(): void {
        $a = $this->activity();
        $a->requirestart = 1;
        $f = $this->facts();
        $f['watched_ranges'] = [[10, 100]];
        $this->assert_rule($a, $f, 'start', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_end_rule.
     *
     * @return void Return value.
     */
    public function test_end_rule(): void {
        $a = $this->activity();
        $a->requireend = 1;
        $f = $this->facts();
        $f['reached_end'] = false;
        $this->assert_rule($a, $f, 'end', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_continuous_playback_rule.
     *
     * @return void Return value.
     */
    public function test_continuous_playback_rule(): void {
        $a = $this->activity();
        $a->mincontinuous = 60;
        $f = $this->facts();
        $f['continuous_blocks'] = [[0, 20, 20], [21, 50, 29]];
        $this->assert_rule($a, $f, 'continuous', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_long_inactivity_rule.
     *
     * @return void Return value.
     */
    public function test_long_inactivity_rule(): void {
        $a = $this->activity();
        $a->inactivitythreshold = 60;
        $a->maxlonginactivity = 1;
        $f = $this->facts();
        $f['latest_session']['inactivity_gaps'] = [10, 61, 80];
        $this->assert_rule($a, $f, 'inactivity', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_minimum_sessions_rule.
     *
     * @return void Return value.
     */
    public function test_minimum_sessions_rule(): void {
        $a = $this->activity();
        $a->minsessions = 2;
        $f = $this->facts();
        $f['session_count'] = 1;
        $this->assert_rule($a, $f, 'minsessions', evaluator::RESULT_PENDING);
    }

    /**
     * Method test_maximum_sessions_rule.
     *
     * @return void Return value.
     */
    public function test_maximum_sessions_rule(): void {
        $a = $this->activity();
        $a->maxsessions = 2;
        $f = $this->facts();
        $f['session_count'] = 3;
        $this->assert_rule($a, $f, 'maxsessions', evaluator::RESULT_FAIL);
    }

    /**
     * Method test_deadline_rule.
     *
     * @return void Return value.
     */
    public function test_deadline_rule(): void {
        $a = $this->activity();
        $a->deadline = 1000;
        $this->assert_rule($a, $this->facts(), 'deadline', evaluator::RESULT_FAIL, 2000);
    }

    /**
     * Method test_informational_violation_does_not_block_completion.
     *
     * @return void Return value.
     */
    public function test_informational_violation_does_not_block_completion(): void {
        $a = $this->activity();
        $a->maxplaybackrate = 1.5;
        $a->policies = json_encode(['playbackrate' => evaluator::POLICY_INFO]);
        $f = $this->facts();
        $f['latest_session']['maximum_playback_rate'] = 2;
        $result = evaluator::evaluate($a, $f, 2000);
        $this->assertSame('completed', $result['status']);
        $this->assertCount(1, $result['violations']);
    }

    /**
     * Method test_rewatch_policy_requests_fresh_viewing_window.
     *
     * @return void Return value.
     */
    public function test_rewatch_policy_requests_fresh_viewing_window(): void {
        $a = $this->activity();
        $a->maxplaybackrate = 1.5;
        $a->policies = json_encode(['playbackrate' => evaluator::POLICY_REWATCH]);
        $f = $this->facts();
        $f['latest_session']['maximum_playback_rate'] = 2;
        $result = evaluator::evaluate($a, $f, 2000);
        $this->assertSame('invalid', $result['status']);
        $this->assertTrue($result['requires_rewatch']);
    }

    /**
     * Method test_clean_latest_session_recovers_temporary_behaviour_failure.
     *
     * @return void Return value.
     */
    public function test_clean_latest_session_recovers_temporary_behaviour_failure(): void {
        $a = $this->activity();
        $a->maxplaybackrate = 1.5;
        $f = $this->facts();
        // Historical aggregate may be high, but temporary behavior rules use the latest session.
        $f['maximum_playback_rate'] = 2;
        $f['latest_session']['maximum_playback_rate'] = 1.25;
        $result = evaluator::evaluate($a, $f, 2000);
        $this->assertSame('completed', $result['status']);
    }
}
