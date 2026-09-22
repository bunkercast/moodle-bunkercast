# Bunkercast video activity (mod_bunkercast)

Adds **Bunkercast video** to *Add an activity or resource*, for when a DRM-protected
video is the whole activity rather than something embedded inside other content.

**This plugin requires `filter_bunkercast` and does nothing without it.** All of the
access-control logic — minting playback links, checking that the viewer may see the
video, talking to the Bunkercast API — lives in the filter. This activity reuses the
filter's API client, web service, cache and JavaScript, and therefore ships no
JavaScript of its own.

Pick a video from your library when you add the activity. Viewers get a
per-viewer, short-lived playback link.

**The video is authorised for this activity alone** — not for the course around
it. That is what makes hiding this activity, or restricting it by group or by
date, actually withhold the video: a playback request has to name this activity,
and naming it is what makes Moodle check whether it is visible to that viewer. A
course-wide authorisation would be satisfied by anyone enrolled, asking from
anywhere in the course.

So adding the activity does not make the video usable elsewhere in the course. If
you also want it in a page or a forum post, insert it there with the Bunkercast
editor button, or authorise it for the course on the *Authorise Bunkercast videos*
page.

## What you need

- **A Bunkercast account** — <https://app.bunkercast.com>. New accounts include 60
  free minutes.
- **An API key**, created under *Account → Settings* and stored in the
  `filter_bunkercast` settings. This activity does not have settings of its own.
- **`filter_bunkercast` installed, enabled and configured first.**

Playback consumes minutes from your Bunkercast balance. For classroom use the
Professional plan is usually the right fit — 30 students × 45 minutes is 1,350
minutes. This plugin cannot top up your account.

## Requirements

- **Moodle 4.5 or later** (`$plugin->requires = 2024100700`).
- **`filter_bunkercast`**, declared as a hard dependency in `version.php`. Moodle
  will refuse to install this activity without it.
- PHP 8.1 or later, as required by Moodle 4.5.

## Installation

Install `filter_bunkercast` first, then:

1. Copy this directory to `mod/bunkercast` in your Moodle tree, or install the ZIP
   through *Site administration → Plugins → Install plugins*.
2. Visit *Site administration → Notifications* to complete the install.

There is nothing to configure — the API key and playback settings come from the
filter.

## Companion plugins

- **`filter_bunkercast`** — required. The renderer and the access boundary.
- **`tiny_bunkercast`** — optional. A TinyMCE button that lists your library and
  inserts a video reference into any editor, for videos that sit *inside* other
  content rather than being an activity of their own.

Both are published at <https://github.com/bunkercast/moodle-bunkercast>.

## Backup and restore

Supported. The activity stores only the Bunkercast video id — never a playback
credential — so a course backup carries the reference and a restored activity mints
against its own new module context.

## Known limits

- **Completion is view-based.** The activity can record that a student opened it,
  not how much they watched; reporting real watch time would need the player to send
  heartbeats back, which it does not.
- **No gradebook integration.**
- **The Moodle mobile app is not supported** — see the filter's README.

## Licence

GNU GPL v3 or later — see [LICENSE](LICENSE).
