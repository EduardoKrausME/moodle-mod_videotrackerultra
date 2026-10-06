<?php
// This file is part of Moodle - http://moodle.org/.

require('../../config.php');

use mod_videotrackerultra\evaluation_manager;

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$reevaluate = optional_param('reevaluate', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('videotrackerultra', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackerultra', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

require_login($course, true, $cm);
require_capability('mod/videotrackerultra:viewreport', $context);
if (!is_enrolled($context, $user, 'mod/videotrackerultra:view', true)) {
    throw new invalid_parameter_exception('The selected user is not an enrolled participant in this activity.');
}

$groupmode = groups_get_activity_groupmode($cm);
if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
    $groupid = groups_get_activity_group($cm, true);
    if ($groupid <= 0 || !groups_is_member($groupid, $userid)) {
        throw new required_capability_exception($context, 'moodle/site:accessallgroups', 'nopermissions', '');
    }
}

$PAGE->set_url('/mod/videotrackerultra/student.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentdetails', 'videotrackerultra'));
$PAGE->set_heading(format_string($course->fullname));

if ($reevaluate) {
    require_sesskey();
    require_capability('mod/videotrackerultra:reevaluate', $context);
}

$evaluation = (new evaluation_manager())->evaluate($activity, $cm, $userid, true);
$facts = $evaluation['facts'];

if ($reevaluate) {
    redirect($PAGE->url, get_string('reevaluated', 'videotrackerultra'));
}

$rules = [];
foreach ($evaluation['rules'] as $rule) {
    $rules[] = [
        'label' => $rule['label'],
        'result' => match ($rule['result']) {
            'ok' => get_string('ok', 'videotrackerultra'),
            'fail' => get_string('fail', 'videotrackerultra'),
            default => get_string('pending', 'videotrackerultra'),
        },
        'resultkey' => $rule['result'],
        'evidence' => $rule['evidence'],
        'policy' => match ($rule['policy']) {
            'info' => get_string('policyinfo', 'videotrackerultra'),
            'rewatch' => get_string('policyrewatch', 'videotrackerultra'),
            default => get_string('policyblock', 'videotrackerultra'),
        },
    ];
}

$reevaluateurl = '';
if (has_capability('mod/videotrackerultra:reevaluate', $context)) {
    $reevaluateurl = (new moodle_url('/mod/videotrackerultra/student.php', [
        'id' => $cm->id,
        'userid' => $userid,
        'reevaluate' => 1,
        'sesskey' => sesskey(),
    ]))->out(false);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_videotrackerultra/student', [
    'name' => fullname($user),
    'email' => $user->email,
    'status' => get_string('status:' . $evaluation['status'], 'videotrackerultra'),
    'percent' => round((float)($facts['percent_watched'] ?? 0), 1),
    'realtime' => format_time((int)round((float)($facts['playback_time'] ?? 0))),
    'sessions' => (int)($facts['session_count'] ?? 0),
    'rules' => $rules,
    'hasrules' => (bool)$rules,
    'reevaluateurl' => $reevaluateurl,
    'canreevaluate' => $reevaluateurl !== '',
    'backurl' => (new moodle_url('/mod/videotrackerultra/report.php', ['id' => $cm->id]))->out(false),
]);
echo $OUTPUT->footer();
