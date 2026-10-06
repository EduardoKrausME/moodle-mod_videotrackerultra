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
 * index.php
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
$context = context_course::instance($course->id);

require_course_login($course);

$PAGE->set_url('/mod/videotrackerultra/index.php', ['id' => $course->id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('modulenameplural', 'videotrackerultra'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'videotrackerultra'));

$instances = get_all_instances_in_course('videotrackerultra', $course);
if (!$instances) {
    echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
    echo $OUTPUT->footer();
    return;
}

$items = [];
foreach ($instances as $instance) {
    if (empty($instance->visible) &&
            !has_capability('moodle/course:viewhiddenactivities', $context)) {
        continue;
    }

    $items[] = html_writer::link(
        new moodle_url('/mod/videotrackerultra/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name)
    );
}

if ($items) {
    echo html_writer::alist($items, ['class' => 'mod-videotrackerultra-index']);
} else {
    echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info');
}

echo $OUTPUT->footer();
