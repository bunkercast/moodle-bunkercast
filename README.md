# Bunkercast for Moodle

DRM-protected video in Moodle, with playback granted per viewer by Moodle's own
enrolment rules rather than by a link anyone can forward.

**Status: alpha, not yet installed into a Moodle.** Nothing here has run.

## What it does

A teacher puts a placeholder in any Moodle content:

```
[bunkercast:36bb40db-1a39-480f-8148-4346a76388fd]
```

At page render the filter turns that into a player container carrying only the
file id. A small AMD module then calls a Moodle web service, which checks the
user may access that context and mints a short-lived playback link from
Bunkercast, and the iframe loads.

## The design decision that matters

**The playback credential never enters filtered HTML.**

Every active filter runs over every string Moodle outputs, and Moodle caches
formatted text in several places — as do blocks and plugins that render their own
HTML. A token embedded in filtered output could therefore be served from cache
to a *different* student: one viewer's credential handed to another.

So the filter emits a file id, and the credential is fetched per user over AJAX.
Cached HTML contains nothing worth stealing.

The same choice rules out the tempting alternative of pasting a long-lived embed
code into the content. That leaves a permanent, forwardable token in the page,
visible in view-source, unaffected by anyone losing access.

## Access control comes from Moodle

`classes/external/get_playback_url.php` calls `self::validate_context()` before
minting. By the time Moodle renders a page it has already decided about
enrolment, groups, availability conditions and prerequisites — so that one call
inherits all of it. Unenrol a student and the next mint simply does not happen;
there is no revocation step to remember.

## Caching is not optional

A course page with ten videos, opened by a class of thirty, is three hundred mint
requests. Bunkercast rate limits per API key.

`db/caches.php` defines a cache keyed `<userid>_<fileid>`, holding each minted
URL for its own lifetime. That turns the above into roughly one request per
student per video per lesson. The user id is in the key deliberately: a token
belongs to one viewer and must never be handed to another.

## Installing

Requires **Moodle 4.5+** — that is where filter classes moved to
`classes/text_filter.php`. On 4.1–4.4 the class must live in `filter.php`
instead; the documented approach is to keep the implementation where it is and
add a `class_alias()` shim in the old location. Not done yet.

1. Copy `filter/bunkercast` into your Moodle's `filter/` directory.
2. Visit Site administration → Notifications to complete installation.
3. Build the JavaScript: `npx grunt amd` from your Moodle root (needs a Moodle
   development checkout — `amd/build/` is generated and is not committed here).
4. Enable the filter: Site administration → Plugins → Filters → Manage filters.
5. Configure it: add the API key from Bunkercast (Account → Settings).

## Choosing a plan

Pay-as-you-go is billed per minute watched and is the cheapest way to evaluate
this plugin. For real classroom use Professional is normally the right fit — a
45-minute lecture watched by 30 students is 1,350 minutes, so per-minute billing
adds up quickly, and Professional replaces it with a flat monthly fee.

**This plugin cannot see your balance or top it up.** An API key reaches only the
mint and list endpoints. Check the balance and plan in Bunkercast before a
lesson: if the account runs dry mid-class, every student in the room sees the
video fail at once.

## Known gaps

- **The Moodle mobile app is untested.** Two things need proving: that DRM plays
  in the app's webview at all, and that the app sends a referrer. It generally
  does not, which is why **Restrict playback to this site is off by default** —
  turning it on will likely stop app users watching.
- **No authoring UI yet.** The placeholder is typed by hand. A TinyMCE plugin
  (`tiny_bunkercast`) with a library picker is the next piece.
- **One API key per site**, so every teacher sees and can embed every video in
  the account, and deleting a video in Bunkercast silently breaks another
  teacher's course. Acceptable for a single school; per-course keys via
  `filterlocalsettings.php` would be the fix.
- **No "did they watch it" reporting**, so no completion tracking or gradebook
  integration. That would want a `mod_bunkercast` activity type.

## Layout

```
filter/bunkercast/
├── version.php                          plain data, no side effects
├── settings.php                         API key, base URL, ttl, host lock
├── db/services.php                      the AJAX web service
├── db/caches.php                        per-user minted-URL cache
├── lang/en/filter_bunkercast.php        strings (must define pluginname)
├── classes/text_filter.php              placeholder → container
├── classes/api.php                      Bunkercast HTTP client (server-side only)
├── classes/external/get_playback_url.php authorisation + mint + cache
└── amd/src/player.js                    fetches the URL, builds the iframe
```
