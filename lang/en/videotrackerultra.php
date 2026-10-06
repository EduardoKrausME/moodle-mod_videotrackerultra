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
 * videotrackerultra.php
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['back'] = 'Back';
$string['capabilitynotice'] = 'Rules are checked against the capabilities declared by the selected Video Bridge provider. Unsupported rules cannot be saved.';
$string['capabilityunsupported'] = 'This video source does not support this rule ({$a}).';
$string['completiondetail:valid'] = 'Obtain a valid video-consumption evaluation';
$string['completionvalid'] = 'Require a valid Video Tracker Ultra evaluation';
$string['currentprogress'] = 'Current progress';
$string['deadline'] = 'Completion deadline';
$string['eventevaluationupdated'] = 'Video validation state updated';
$string['evidence'] = 'Evidence';
$string['evidence:continuous'] = 'longest continuous block {$a} seconds';
$string['evidence:end'] = 'end reached: {$a}';
$string['evidence:forwardseeks'] = '{$a} forward seeks';
$string['evidence:inactivity'] = '{$a} long inactivity periods';
$string['evidence:percent'] = '{$a}% watched';
$string['evidence:playbackrate'] = 'maximum {$a}x';
$string['evidence:realtime'] = '{$a} seconds of playback';
$string['evidence:seeksize'] = 'largest forward seek {$a} seconds';
$string['evidence:segment'] = '{$a}% of the segment';
$string['evidence:sessions'] = '{$a} sessions';
$string['fail'] = 'FAIL';
$string['forbidskipping'] = 'Do not allow skipped sections';
$string['inactivitythreshold'] = 'Long inactivity threshold (seconds)';
$string['informational'] = 'INFO';
$string['interruptiontolerance'] = 'Tolerance for short interruptions (seconds)';
$string['invalidminusone'] = 'Enter -1 to disable this rule, or zero/positive value.';
$string['invalidnonnegative'] = 'Enter zero or a positive number.';
$string['invalidpercent'] = 'Enter a percentage from 0 to 100.';
$string['invalidsegments'] = 'Required segments must use START-END, one per line, and each end must be after its start.';
$string['lastattempt'] = 'Last attempt';
$string['maxforwardseeks'] = 'Maximum forward seeks';
$string['maxlonginactivity'] = 'Maximum long inactivity periods';
$string['maxplaybackrate'] = 'Maximum allowed playback rate';
$string['maxseeksize'] = 'Maximum forward seek size (seconds)';
$string['maxsessions'] = 'Maximum sessions';
$string['mincontinuous'] = 'Minimum continuous playback block (seconds)';
$string['minpercent'] = 'Minimum effectively watched percentage';
$string['minrealtime'] = 'Minimum real playback time (seconds)';
$string['minsessions'] = 'Minimum sessions';
$string['modulename'] = 'Video Tracker Ultra';
$string['modulename_help'] = 'Validates observable video playback behaviour against teacher-defined rules calculated on the server.';
$string['modulenameplural'] = 'Video Tracker Ultra activities';
$string['no'] = 'No';
$string['norules'] = 'No validation rules are enabled.';
$string['noviolations'] = 'No blocking violations.';
$string['ok'] = 'OK';
$string['pending'] = 'PENDING';
$string['percentage'] = 'Percentage';
$string['pluginadministration'] = 'Video Tracker Ultra administration';
$string['pluginname'] = 'Video Tracker Ultra';
$string['policiesheader'] = 'Failure policies';
$string['policy'] = 'Policy';
$string['policy:continuous'] = 'Continuous playback failure policy';
$string['policy:deadline'] = 'Deadline failure policy';
$string['policy:end'] = 'End failure policy';
$string['policy:forwardseeks'] = 'Forward seeks failure policy';
$string['policy:inactivity'] = 'Inactivity failure policy';
$string['policy:maxsessions'] = 'Maximum sessions failure policy';
$string['policy:minsessions'] = 'Minimum sessions failure policy';
$string['policy:percent'] = 'Percentage failure policy';
$string['policy:playbackrate'] = 'Playback rate failure policy';
$string['policy:realtime'] = 'Playback time failure policy';
$string['policy:seeksize'] = 'Seek size failure policy';
$string['policy:segments'] = 'Required segments failure policy';
$string['policy:skipping'] = 'Skipping failure policy';
$string['policy:start'] = 'Beginning failure policy';
$string['policyblock'] = 'Temporarily block completion';
$string['policyinfo'] = 'Record information only';
$string['policyrewatch'] = 'Require a new viewing window';
$string['privacy:evaluation'] = 'Video validation evaluation';
$string['privacy:metadata:evaluation'] = 'Stores a recalculable cached validation result for reporting and Moodle completion.';
$string['privacy:metadata:evaluation:factsjson'] = 'A compact snapshot of the Video Bridge facts used by the latest evaluation.';
$string['privacy:metadata:evaluation:rulesjson'] = 'The calculated rule matrix and evidence.';
$string['privacy:metadata:evaluation:status'] = 'The current calculated validation state.';
$string['privacy:metadata:evaluation:timecreated'] = 'When the cache row was first created.';
$string['privacy:metadata:evaluation:timemodified'] = 'When the cache row was last recalculated.';
$string['privacy:metadata:evaluation:userid'] = 'The learner whose video consumption was evaluated.';
$string['privacy:metadata:evaluation:violationsjson'] = 'Calculated failures and their evidence.';
$string['privacy:metadata:evaluation:windowstart'] = 'The start of a fresh-viewing window when a policy requires rewatching.';
$string['realtime'] = 'Playback time';
$string['reevaluate'] = 'Recalculate';
$string['reevaluated'] = 'The validation state was recalculated.';
$string['reportempty'] = 'No learner evaluation is available yet.';
$string['requiredsegments'] = 'Required segments';
$string['requiredsegments_help'] = 'One segment per line using START-END, for example 00:00-03:00 or 08:30-11:00.';
$string['requireend'] = 'Require reaching the end of the video';
$string['requirestart'] = 'Require the beginning of the video';
$string['result'] = 'Result';
$string['rule'] = 'Rule';
$string['rule:continuous'] = 'Continuous playback >= {$a} seconds';
$string['rule:deadline'] = 'Complete before {$a}';
$string['rule:end'] = 'Reach the end of the video';
$string['rule:forwardseeks'] = 'Forward seeks <= {$a}';
$string['rule:inactivity'] = 'Long inactivity periods <= {$a}';
$string['rule:maxsessions'] = 'Sessions <= {$a}';
$string['rule:minsessions'] = 'Sessions >= {$a}';
$string['rule:percent'] = 'Watched percentage >= {$a}%';
$string['rule:playbackrate'] = 'Playback rate <= {$a}x';
$string['rule:realtime'] = 'Real playback time >= {$a} seconds';
$string['rule:seeksize'] = 'Largest forward seek <= {$a} seconds';
$string['rule:segment'] = 'Watch {$a}';
$string['rule:skipping'] = 'Do not skip video sections';
$string['rule:start'] = 'Watch the beginning of the video';
$string['rulesheader'] = 'Consumption validation rules';
$string['rulesmet'] = 'Rules met';
$string['rulespending'] = 'Rules pending';
$string['segmentcoverage'] = 'Required segment coverage (%)';
$string['segmentsheader'] = 'Required segments';
$string['servicegetstatus'] = 'Recalculates and returns the authenticated learner validation state.';
$string['sessions'] = 'Sessions';
$string['sourceheader'] = 'Video source';
$string['status'] = 'Status';
$string['status:completed'] = 'Completed';
$string['status:inprogress'] = 'In progress';
$string['status:invalid'] = 'Invalid';
$string['status:notstarted'] = 'Not started';
$string['status:valid'] = 'Valid';
$string['status:waiting'] = 'Waiting for requirement';
$string['student'] = 'Student';
$string['studentdetails'] = 'Student validation details';
$string['taskreevaluate'] = 'Reevaluate Video Tracker Ultra validation states';
$string['trackingnotice'] = 'This activity validates observable player behaviour. It does not prove human attention and is not a proctoring system.';
$string['videosource'] = 'Video source';
$string['videotrackerultra:addinstance'] = 'Add a new Video Tracker Ultra activity';
$string['videotrackerultra:reevaluate'] = 'Recalculate Video Tracker Ultra evaluations';
$string['videotrackerultra:view'] = 'View Video Tracker Ultra';
$string['videotrackerultra:viewreport'] = 'View Video Tracker Ultra reports';
$string['videotrackerultraname'] = 'Activity name';
$string['viewreport'] = 'Validation dashboard';
$string['violations'] = 'Violations';
$string['yes'] = 'Yes';
$string['yourrequirements'] = 'Your requirements';
$string['yourstatus'] = 'Your validation status';
