<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

/**
 * Version metadata for Video Tracker Ultra.
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$plugin->component = 'mod_videotrackerultra';
$plugin->version = 2026100601;
$plugin->release = '1.0.1';
$plugin->requires = 2024100700;
$plugin->maturity = MATURITY_BETA;
$plugin->dependencies = [
    'local_video_bridge' => 2026100608,
];
