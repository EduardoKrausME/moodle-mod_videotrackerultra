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
 * report.php
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

use mod_videotrackerultra\evaluation_manager;

$id = required_param('id', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 50;

$cm = get_coursemodule_from_id('videotrackerultra', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$activity = $DB->get_record('videotrackerultra', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/videotrackerultra:viewreport', $context);

$PAGE->set_url('/mod/videotrackerultra/report.php', ['id' => $cm->id, 'page' => $page]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewreport', 'videotrackerultra'));
$PAGE->set_heading(format_string($course->fullname));

$groupmode = groups_get_activity_groupmode($cm);
$groupid = $groupmode ? groups_get_activity_group($cm, true) : 0;
$total = count_enrolled_users($context, '', $groupid);
$users = get_enrolled_users(
    $context,
    '',
    $groupid,
    'u.id,u.firstname,u.lastname,u.email',
    'u.lastname ASC,u.firstname ASC',
    $page * $perpage,
    $perpage
);

$rows = [];
$manager = new evaluation_manager();
foreach ($users as $user) {
    if (has_capability('mod/videotrackerultra:viewreport', $context, $user->id)) {
        continue;
    }
    $evaluation = $manager->evaluate($activity, $cm, (int)$user->id, true);
    $facts = $evaluation['facts'];
    $latest = $facts['latest_session'] ?? null;

    $rows[] = [
        'name' => fullname($user),
        'email' => $user->email,
        'percent' => round((float)($facts['percent_watched'] ?? 0), 1),
        'realtime' => format_time((int)round((float)($facts['playback_time'] ?? 0))),
        'status' => get_string('status:' . $evaluation['status'], 'videotrackerultra'),
        'rulesmet' => (int)$evaluation['rulesmet'],
        'rulespending' => (int)$evaluation['rulespending'],
        'violations' => count($evaluation['violations']),
        'sessions' => (int)($facts['session_count'] ?? 0),
        'lastattempt' => $latest && !empty($latest['started_at'])
            ? userdate((int)$latest['started_at'])
            : '-',
        'url' => (new moodle_url('/mod/videotrackerultra/student.php', [
            'id' => $cm->id,
            'userid' => $user->id,
        ]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewreport', 'videotrackerultra'));

if ($groupmode) {
    groups_print_activity_menu($cm, $PAGE->url);
}

echo $OUTPUT->render_from_template('mod_videotrackerultra/report', [
    'rows' => $rows,
    'hasrows' => (bool)$rows,
]);
echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/mod/videotrackerultra/report.php', ['id' => $cm->id]));
echo $OUTPUT->footer();
