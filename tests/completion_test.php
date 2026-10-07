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
use local_video_bridge\analytics\manager as analytics_manager;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Integration coverage for recalculable completion.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('videotrackerultra_get_completion_state')]
final class completion_test extends advanced_testcase {
    /**
     * Completion changes when underlying Video Bridge facts change.
     *
     * @return void
     */
    public function test_completion_is_recalculated_from_bridge_facts(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/videotrackerultra/lib.php');

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_videotrackerultra');
        $activity = $generator->create_instance([
            'course' => $course->id,
            'minpercent' => 50,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);
        $cm = get_coursemodule_from_instance('videotrackerultra', $activity->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $activity = $DB->get_record('videotrackerultra', ['id' => $activity->id], '*', MUST_EXIST);

        $this->assertFalse(videotrackerultra_get_completion_state($course, $cm, $user->id, false));

        $mediahash = analytics_manager::media_hash($activity->videosource, $activity->sourceconfig);
        $now = time();
        $DB->insert_record('local_video_bridge_progress', (object)[
            'contextid' => $context->id,
            'component' => 'mod_videotrackerultra',
            'itemid' => $activity->id,
            'source' => $activity->videosource,
            'mediahash' => $mediahash,
            'userid' => $user->id,
            'currenttime' => 60,
            'duration' => 100,
            'percent' => 60,
            'map' => json_encode(range(1, 60)),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_video_bridge_session', (object)[
            'contextid' => $context->id,
            'component' => 'mod_videotrackerultra',
            'itemid' => $activity->id,
            'source' => $activity->videosource,
            'mediahash' => $mediahash,
            'userid' => $user->id,
            'sessionid' => 'testsession',
            'level' => 'detailed',
            'startedat' => $now - 60,
            'endedat' => $now,
            'duration' => 100,
            'watchtime' => 60,
            'plays' => 1,
            'pauses' => 0,
            'seeks' => 0,
            'replays' => 0,
            'skips' => 0,
            'dropoff' => 60,
            'maxposition' => 60,
            'speedavg' => 1,
            'ranges' => json_encode([[0, 60]]),
            'pausepoints' => '[]',
            'skippoints' => '[]',
            'replaypoints' => '[]',
            'rates' => json_encode(['1' => 60]),
            'continuousblocks' => json_encode([[0, 60, 60]]),
            'inactivitygaps' => '[]',
            'timecreated' => $now - 60,
            'timemodified' => $now,
        ]);

        $this->assertTrue(videotrackerultra_get_completion_state($course, $cm, $user->id, false));
    }
}
