# Video Tracker Ultra

Video Tracker Ultra is a Moodle activity that validates whether observable video playback behaviour satisfies rules defined by the teacher.

It is deliberately different from a generic analytics report. The central question is not only “how much was watched?”, but “does the server-side evidence satisfy the rules configured for this activity?”.

## Shared video infrastructure

The activity requires [Moodle Video Bridge](https://github.com/EduardoKrausME/moodle-local_video_bridge).

Video Tracker Ultra does not implement its own provider integrations, player abstraction, basic viewing map, progress transport or provider-specific hacks. Video sources and playback capabilities come from `local_video_bridge\source\manager`, while detailed optional playback facts come from `local_video_bridge\analytics\manager`.

The bridge owns facts. Video Tracker Ultra owns pedagogical rules.

## Rules

An activity can independently require or restrict:

- minimum effectively watched percentage;
- minimum real playback time;
- maximum playback rate;
- maximum number of forward seeks;
- maximum forward-seek distance;
- skipping;
- required timeline segments;
- watching the beginning;
- reaching the end;
- a minimum continuous playback block;
- a maximum number of long inactivity periods;
- minimum and maximum session counts;
- a completion deadline;
- tolerance for short interruptions.

Required segments are evaluated independently from the overall percentage. Watching 90% of a video does not satisfy a required 08:30–11:00 segment if that segment itself was not sufficiently watched.

## Server-side evaluation

The browser never submits values such as `valid=true`, `completed=true` or an authoritative percentage. It submits only normalized playback observations accepted by Video Bridge. The server consolidates those observations into facts and Video Tracker Ultra evaluates the configured rules from those facts.

The saved evaluation row is a cache for reporting. It can always be rebuilt from the underlying analytics facts, so completion does not depend exclusively on a previously stored flag.

Possible states are:

- not started;
- in progress;
- valid;
- invalid;
- waiting for a requirement;
- completed.

An invalid observation is not a permanent punishment. Policies can be informational, temporarily blocking, or require a fresh viewing window. A later compliant viewing can therefore recover the activity state.

## Violations and evidence

The evaluator produces evidence such as:

- largest forward seek exceeded the configured limit;
- playback rate exceeded the configured maximum;
- required segment coverage is incomplete;
- real playback time is insufficient;
- effective watched percentage is insufficient;
- the end was not reached.

For each rule, teachers choose whether a failure is informational, temporarily blocks completion, or requires a new viewing window.

## Provider capabilities

Rules are checked against capabilities declared by the selected Video Bridge source. A teacher cannot save a rule that requires behaviour the source cannot reliably expose, such as seeking or playback-rate data when those capabilities are unavailable.

No provider-specific workaround is implemented by this module.

## What this proves — and what it does not

Video Tracker Ultra distinguishes four concepts:

**Tracking** records observable player events and playback positions.

**Progress** describes how much media content the server can reasonably consolidate as watched.

**Consumption validation** evaluates those observable facts against explicit activity rules.

**Attention proof** would require proving that a human was cognitively attentive. Browser player telemetry cannot prove that, and this plugin does not claim otherwise.

This activity is not proctoring and is not marketed as perfect anti-fraud technology. It validates observable behaviour in the player.

## Moodle integration

The plugin includes custom completion, recalculable evaluations, Moodle events, groups/group mode support, Privacy API, backup/restore, a scheduled reevaluation task, PHPUnit coverage for rule evaluation and Behat scenarios for configuration and completion.
