# Bunkercast DRM video (filter_bunkercast)

Plays DRM-protected Bunkercast videos inside Moodle. A reference like

```
[bunkercast:36bb40db-1a39-480f-8148-4346a76388fd]
```

becomes a player anywhere Moodle formats text — a page, a label, an activity
description, a forum post.

**A video plays in a course only once it has been authorised for that course.**
Inserting it with the Bunkercast button in the editor does that as it goes, and so
does adding a Bunkercast activity, so most people never think about it. For a
reference typed or pasted by hand, a teacher authorises the video once under
*Course administration → Authorise Bunkercast videos*.

That step is what stops a reference being pasted somewhere it was never meant to
appear. Moodle alone cannot tell the difference: anyone who can write a forum post
could otherwise paste any video id from the library and have it play.

Each viewer gets their own short-lived playback link, minted server-side when they
open the page. No playback credential is ever written into the page HTML, because
Moodle caches formatted text in several places and a token stored there could be
served from cache to a different student.

Access follows Moodle. The link is issued only if Moodle says the viewer may see
the place the video is published, so enrolment, group restrictions, availability
conditions and completion prerequisites all apply with no extra configuration —
unenrol a student and their next request is refused.

## What you need

- **A Bunkercast account** — <https://app.bunkercast.com>. New accounts include 60
  free minutes, which is enough to evaluate the plugin.
- **An API key**, created under *Account → Settings*. It is shown once, so store it
  straight away. The key stays on the Moodle server and is never sent to a browser:
  anyone holding it can create playback links for your whole library.
- **Videos uploaded and encoded** in Bunkercast Studio. The Studio is where you copy
  a video's id from.

Playback consumes minutes from your Bunkercast balance. Pay-as-you-go is billed per
minute watched; for classroom use the Professional plan is usually the right fit,
since a lecture watched by a whole class consumes minutes quickly (30 students ×
45 minutes is 1,350 minutes). This plugin cannot top up your account — check your
balance before a lesson.

## Requirements

- **Moodle 4.5 or later** (`$plugin->requires = 2024100700`). Moodle 4.5 is where
  filter classes moved to `classes/text_filter.php`; on 4.1–4.4 the class must live
  in `filter.php` instead, which this plugin does not ship.
- PHP 8.1 or later, as required by Moodle 4.5.
- Outbound HTTPS from the Moodle server to the Bunkercast API.

## Installation

1. Copy this directory to `filter/bunkercast` in your Moodle tree, or install the
   ZIP through *Site administration → Plugins → Install plugins*.
2. Visit *Site administration → Notifications* to complete the install.
3. Turn the filter on at *Site administration → Plugins → Filters → Manage filters*.
   Set **Bunkercast DRM video** to *On*.
4. Add your API key at *Site administration → Plugins → Filters → Bunkercast DRM
   video*.

## Companion plugins

Both are optional and both depend on this one, which holds all of the access-control
logic. They are published alongside it at
<https://github.com/bunkercast/moodle-bunkercast>.

- **`mod_bunkercast`** — adds *Bunkercast video* to *Add an activity or resource*,
  for when the video is the whole activity. Reuses this plugin's API client, web
  service, cache and JavaScript, so it ships no JavaScript of its own.
- **`tiny_bunkercast`** — adds a button to the TinyMCE editor that lists your
  Bunkercast library and inserts the reference for you, so you never copy an id by
  hand.

## Privacy

The plugin sends an opaque reference to the viewing user — their Moodle user id —
to Bunkercast so a playback link can be issued for that person alone, and so a
leaked link can be traced. No name, email address or other personal detail is
transmitted. See the plugin's privacy provider for the declared metadata.

## Known limits

- **The Moodle mobile app is not supported.** DRM plays in a webview, but the app
  has no RequireJS, so nothing fills the container this filter emits.
- **Host locking is off by default.** It asks Bunkercast to refuse playback unless
  the player is loaded from your Moodle site, which is stronger — but it relies on
  the browser sending a referrer, and the mobile app usually does not.
- **Completion is view-based** where the activity module is used. Reporting real
  watch time would need the player to send heartbeats back, which it does not.

## Licence

GNU GPL v3 or later — see [LICENSE](LICENSE).
