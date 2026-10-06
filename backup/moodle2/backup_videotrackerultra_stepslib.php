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
 * backup_videotrackerultra_stepslib.php
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Backup structure for Video Tracker Ultra.
 */
class backup_videotrackerultra_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines activity and optional cached user evaluations.
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $activity = new backup_nested_element('videotrackerultra', ['id'], [
            'name', 'intro', 'introformat', 'videosource', 'sourceconfig', 'videourl',
            'minpercent', 'minrealtime', 'maxplaybackrate', 'maxforwardseeks',
            'maxseeksize', 'forbidskipping', 'requirestart', 'requireend',
            'mincontinuous', 'inactivitythreshold', 'maxlonginactivity',
            'minsessions', 'maxsessions', 'deadline', 'interruptiontolerance',
            'segmentcoverage', 'requiredsegments', 'policies', 'completionvalid',
            'timecreated', 'timemodified',
        ]);

        $evaluations = new backup_nested_element('evaluations');
        $evaluation = new backup_nested_element('evaluation', ['id'], [
            'userid', 'status', 'windowstart', 'rulesjson', 'violationsjson',
            'factsjson', 'timecreated', 'timemodified',
        ]);

        $activity->add_child($evaluations);
        $evaluations->add_child($evaluation);
        $activity->set_source_table('videotrackerultra', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $evaluation->set_source_table('videotrackerultra_eval', [
                'videotrackerultraid' => backup::VAR_PARENTID,
            ]);
        }
        $evaluation->annotate_ids('user', 'userid');

        // Source-owned uploads remain in the shared Video Bridge component.
        $activity->annotate_files('local_video_bridge', 'video', null);

        return $this->prepare_activity_structure($activity);
    }
}
