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

/**
 * view.php
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use local_video_bridge\analytics;
use local_video_bridge\source\manager as source_manager;
use mod_videotrackerultra\evaluation_manager;
use mod_videotrackerultra\event\course_module_viewed;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('videotrackerultra', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackerultra', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerultra:view', $context);

$PAGE->set_url('/mod/videotrackerultra/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($activity->name));
$PAGE->set_heading(format_string($course->fullname));

$completion = new completion_info($course);
if ($completion->is_enabled($cm)) {
    $completion->set_module_viewed($cm);
}

$event = course_module_viewed::create([
    'objectid' => $activity->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('videotrackerultra', $activity);
$event->trigger();

$evaluation = (new evaluation_manager())->evaluate($activity, $cm, (int)$USER->id, true);
$facts = $evaluation['facts'];

$source = new source_manager();
$player = $source->get_player_config($activity, $context, analytics::LEVEL_DETAILED);
$playerclient = $player;
unset($playerclient['sourcetemplate']);
$player['sourcehtml'] = $OUTPUT->render_from_template($player['sourcetemplate'], ['player' => $player]);

$rules = [];
foreach ($evaluation['rules'] as $rule) {
    $rule['resultlabel'] = match ($rule['result']) {
        'ok' => get_string('ok', 'videotrackerultra'),
        'fail' => get_string('fail', 'videotrackerultra'),
        default => get_string('pending', 'videotrackerultra'),
    };
    $rules[] = $rule;
}

$config = [
    'cmid' => $cm->id,
    'player' => $playerclient,
];

$data = [
    'name' => format_string($activity->name),
    'intro' => format_module_intro('videotrackerultra', $activity, $cm->id),
    'hasintro' => trim((string)$activity->intro) !== '',
    'player' => $player,
    'status' => $evaluation['status'],
    'statuslabel' => get_string('status:' . $evaluation['status'], 'videotrackerultra'),
    'percent' => round((float)($facts['percent_watched'] ?? 0), 1),
    'playbacktime' => format_time((int)round((float)($facts['playback_time'] ?? 0))),
    'sessions' => (int)($facts['session_count'] ?? 0),
    'rules' => $rules,
    'hasrules' => (bool)$rules,
    'notice' => get_string('trackingnotice', 'videotrackerultra'),
    'canviewreport' => has_capability('mod/videotrackerultra:viewreport', $context),
    'reporturl' => (new moodle_url('/mod/videotrackerultra/report.php', ['id' => $cm->id]))->out(false),
    'configjson' => json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
];

$PAGE->requires->js_call_amd('mod_videotrackerultra/player', 'init');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerultra/view', $data);
echo $OUTPUT->footer();
