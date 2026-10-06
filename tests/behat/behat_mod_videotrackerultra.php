<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\analytics\manager as analytics_manager;
use local_video_bridge\event\analytics_updated;

defined('MOODLE_INTERNAL') || die;

/**
 * Behat steps for Video Tracker Ultra.
 *
 * @package mod_videotrackerultra
 */
class behat_mod_videotrackerultra extends behat_base {
    /**
     * Seeds trusted test fixtures representing facts already accepted by Video Bridge.
     *
     * Behat is testing Ultra's server-side evaluation and completion integration here,
     * not the browser transport that belongs to local_video_bridge.
     *
     * @Given /^the Video Tracker Ultra activity "(?P<activity>[^"]+)" has valid playback evidence for "(?P<username>[^"]+)"$/
     * @param string $activityname Activity name.
     * @param string $username Username.
     * @return void
     */
    public function activity_has_valid_playback_evidence(string $activityname, string $username): void {
        global $DB;

        $activity = $DB->get_record('videotrackerultra', ['name' => $activityname], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance(
            'videotrackerultra',
            $activity->id,
            $activity->course,
            false,
            MUST_EXIST
        );
        $context = context_module::instance($cm->id);
        $mediahash = analytics_manager::media_hash(
            (string)$activity->videosource,
            (string)$activity->sourceconfig
        );

        $now = time();
        $duration = 600;
        $progressid = $DB->insert_record('local_video_bridge_progress', (object)[
            'contextid' => $context->id,
            'component' => 'mod_videotrackerultra',
            'itemid' => $activity->id,
            'source' => $activity->videosource,
            'mediahash' => $mediahash,
            'userid' => $user->id,
            'currenttime' => $duration,
            'duration' => $duration,
            'percent' => 100,
            'map' => json_encode(range(1, 100), JSON_THROW_ON_ERROR),
            'timecreated' => $now - $duration,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_video_bridge_session', (object)[
            'contextid' => $context->id,
            'component' => 'mod_videotrackerultra',
            'itemid' => $activity->id,
            'source' => $activity->videosource,
            'mediahash' => $mediahash,
            'userid' => $user->id,
            'sessionid' => 'behat-' . $activity->id . '-' . $user->id,
            'level' => 'detailed',
            'startedat' => $now - $duration,
            'endedat' => $now,
            'duration' => $duration,
            'watchtime' => $duration,
            'plays' => 1,
            'pauses' => 0,
            'seeks' => 0,
            'replays' => 0,
            'skips' => 0,
            'dropoff' => $duration,
            'maxposition' => $duration,
            'speedavg' => 1,
            'ranges' => json_encode([[0, $duration]], JSON_THROW_ON_ERROR),
            'pausepoints' => '[]',
            'skippoints' => '[]',
            'replaypoints' => '[]',
            'rates' => json_encode(['1' => $duration], JSON_THROW_ON_ERROR),
            'continuousblocks' => json_encode([[0, $duration, $duration]], JSON_THROW_ON_ERROR),
            'inactivitygaps' => '[]',
            'timecreated' => $now - $duration,
            'timemodified' => $now,
        ]);

        $event = analytics_updated::create([
            'objectid' => $progressid,
            'context' => $context,
            'relateduserid' => $user->id,
            'other' => [
                'component' => 'mod_videotrackerultra',
                'itemid' => $activity->id,
                'source' => $activity->videosource,
                'mediahash' => $mediahash,
                'percent' => 100,
            ],
        ]);
        $event->trigger();
    }
}
