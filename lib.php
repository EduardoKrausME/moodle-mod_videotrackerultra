<?php
// This file is part of Moodle - http://moodle.org/.

use local_video_bridge\source\manager as source_manager;
use local_video_bridge\progress\manager as bridge_progress_manager;

/**
 * Declares supported Moodle features.
 *
 * @param string $feature Feature.
 * @return bool|null
 */
function videotrackerultra_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * Adds an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videotrackerultra_mod_form|null $mform Form.
 * @return int
 */
function videotrackerultra_add_instance(stdClass $data, ?mod_videotrackerultra_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    (new source_manager())->normalise_record($data);
    $id = $DB->insert_record('videotrackerultra', $data);
    $data->id = $id;
    videotrackerultra_save_source_files($data);
    return $id;
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_videotrackerultra_mod_form|null $mform Form.
 * @return bool
 */
function videotrackerultra_update_instance(stdClass $data, ?mod_videotrackerultra_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $previoussource = (string)$DB->get_field('videotrackerultra', 'videosource', ['id' => $data->id], MUST_EXIST);
    $data->timemodified = time();
    (new source_manager())->normalise_record($data);
    $result = $DB->update_record('videotrackerultra', $data);
    videotrackerultra_save_source_files($data, $previoussource);

    // Configuration changes make the cache stale by design.
    $DB->delete_records('videotrackerultra_eval', ['videotrackerultraid' => $data->id]);
    return $result;
}

/**
 * Saves provider-owned source files.
 *
 * @param stdClass $activity Activity.
 * @param string|null $previoussource Previous source.
 * @return void
 */
function videotrackerultra_save_source_files(stdClass $activity, ?string $previoussource = null): void {
    if (!empty($activity->coursemodule)) {
        $context = context_module::instance((int)$activity->coursemodule);
        (new source_manager())->save_files($activity, $context, $previoussource);
        return;
    }

    $cm = get_coursemodule_from_instance(
        'videotrackerultra',
        $activity->id,
        $activity->course,
        false,
        IGNORE_MISSING
    );
    if ($cm) {
        (new source_manager())->save_files($activity, context_module::instance($cm->id), $previoussource);
    }
}

/**
 * Deletes an activity.
 *
 * @param int $id Activity id.
 * @return bool
 */
function videotrackerultra_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('videotrackerultra', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $cm = get_coursemodule_from_instance(
        'videotrackerultra',
        $id,
        $activity->course,
        false,
        IGNORE_MISSING
    );
    if ($cm) {
        $context = context_module::instance($cm->id);
        (new source_manager())->delete_files($context);
        bridge_progress_manager::delete_consumer($context->id, 'mod_videotrackerultra', $id);
    }

    $DB->delete_records('videotrackerultra_eval', ['videotrackerultraid' => $id]);
    $DB->delete_records('videotrackerultra', ['id' => $id]);
    return true;
}

/**
 * Builds cached course-module data for completion.
 *
 * @param stdClass $cm Course module.
 * @return cached_cm_info|null
 */
function videotrackerultra_get_coursemodule_info(stdClass $cm): ?cached_cm_info {
    global $DB;

    $activity = $DB->get_record(
        'videotrackerultra',
        ['id' => $cm->instance],
        'id,name,intro,introformat,completionvalid'
    );
    if (!$activity) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $activity->name;
    if ($cm->showdescription) {
        $info->content = format_module_intro('videotrackerultra', $activity, $cm->id, false);
    }
    if ((int)$cm->completion === COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules'] = [
            'completionvalid' => (bool)$activity->completionvalid,
        ];
    }
    return $info;
}

/**
 * Returns active rule descriptions on the course page.
 *
 * @param cached_cm_info $cm Cached CM.
 * @return array
 */
function videotrackerultra_get_completion_active_rule_descriptions(cached_cm_info $cm): array {
    if ((int)$cm->completion !== COMPLETION_TRACKING_AUTOMATIC ||
            empty($cm->customdata['customcompletionrules']['completionvalid'])) {
        return [];
    }
    return [get_string('completiondetail:valid', 'videotrackerultra')];
}

/**
 * Legacy completion callback kept for integrations that still call it.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param int $userid User id.
 * @param bool $type Expected type.
 * @return bool
 */
function videotrackerultra_get_completion_state($course, $cm, int $userid, bool $type): bool {
    global $DB;

    $activity = $DB->get_record('videotrackerultra', ['id' => $cm->instance], '*', MUST_EXIST);
    $evaluation = (new \mod_videotrackerultra\evaluation_manager())->evaluate($activity, $cm, $userid, true);
    return in_array($evaluation['status'], ['valid', 'completed'], true);
}
