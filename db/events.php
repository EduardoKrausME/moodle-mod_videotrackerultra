<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

$observers = [
    [
        'eventname' => '\\local_video_bridge\\event\\analytics_updated',
        'callback' => '\\mod_videotrackerultra\\observer::bridge_analytics_updated',
        'priority' => 1000,
    ],
];
