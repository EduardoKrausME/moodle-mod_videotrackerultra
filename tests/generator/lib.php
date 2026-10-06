<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die;

/**
 * Test data generator for Video Tracker Ultra.
 */
class mod_videotrackerultra_generator extends testing_module_generator {
    /**
     * Creates an activity with a direct URL source by default.
     *
     * @param array|stdClass|null $record Record.
     * @param array|null $options Options.
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null): stdClass {
        $record = (object)(array)($record ?? []);
        $record->name ??= 'Video Tracker Ultra';
        $record->videosource ??= 'url';
        $record->videourl ??= 'https://example.com/video.mp4';
        $record->minpercent ??= 0;
        $record->minrealtime ??= 0;
        $record->maxplaybackrate ??= 0;
        $record->maxforwardseeks ??= -1;
        $record->maxseeksize ??= 0;
        $record->forbidskipping ??= 0;
        $record->requirestart ??= 0;
        $record->requireend ??= 0;
        $record->mincontinuous ??= 0;
        $record->inactivitythreshold ??= 60;
        $record->maxlonginactivity ??= -1;
        $record->minsessions ??= 0;
        $record->maxsessions ??= 0;
        $record->deadline ??= 0;
        $record->interruptiontolerance ??= 5;
        $record->segmentcoverage ??= 95;
        $record->requiredsegments ??= '[]';
        $record->policies ??= '{}';
        $record->completionvalid ??= 1;
        return parent::create_instance($record, $options);
    }
}
