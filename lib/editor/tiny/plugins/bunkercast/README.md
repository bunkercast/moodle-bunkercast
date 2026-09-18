# Bunkercast video picker (tiny_bunkercast)

Adds a **Bunkercast** button to the TinyMCE editor. It lists the videos in your
Bunkercast library and inserts the reference for you, so you never copy a
36-character id by hand.

Use it wherever a video belongs *inside* other content — a page, a label, an
assignment description, a forum post, next to your own text. When the video is the
whole activity, use `mod_bunkercast` instead.

**This plugin requires `filter_bunkercast` and does nothing without it.** It only
inserts a reference; the filter is what turns that reference into a player and
decides who may watch.

## What you need

- **A Bunkercast account** — <https://app.bunkercast.com>. New accounts include 60
  free minutes.
- **An API key**, created under *Account → Settings* and stored in the
  `filter_bunkercast` settings. This plugin does not have settings of its own.
- **`filter_bunkercast` installed, enabled and configured first.**
- The **Browse the Bunkercast video library** capability
  (`filter/bunkercast:browselibrary`) in the course you are editing. The button is
  hidden from users who do not hold it, and from places where a Bunkercast video
  cannot be used.

## Requirements

- **Moodle 4.5 or later** (`$plugin->requires = 2024100700`).
- **`filter_bunkercast`**, declared as a hard dependency in `version.php`.
- **TinyMCE** as the active editor. Users whose editor preference is *Plain text
  area* will not see the button.
- PHP 8.1 or later, as required by Moodle 4.5.

## Installation

Install `filter_bunkercast` first, then:

1. Copy this directory to `lib/editor/tiny/plugins/bunkercast` in your Moodle tree,
   or install the ZIP through *Site administration → Plugins → Install plugins*.
2. Visit *Site administration → Notifications* to complete the install.

There is nothing to configure. The button appears in the editor toolbar and under
*Insert → Bunkercast video*.

## Companion plugins

- **`filter_bunkercast`** — required. The renderer and the access boundary.
- **`mod_bunkercast`** — optional. Adds *Bunkercast video* to *Add an activity or
  resource*, for when the video is the whole activity.

Both are published at <https://github.com/bunkercast/moodle-bunkercast>.

## Licence

GNU GPL v3 or later — see [LICENSE](LICENSE).
