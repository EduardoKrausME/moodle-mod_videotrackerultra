<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_videotrackerultra_get_status' => [
        'classname' => '\mod_videotrackerultra\external\get_status',
        'methodname' => 'execute',
        'description' => 'Recalculates and returns the authenticated learner validation state.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/videotrackerultra:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
