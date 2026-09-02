# Bunkercast for Moodle

DRM-protected video in Moodle, with playback granted per viewer by Moodle's own
enrolment rules rather than by a link anyone can forward.

**Status: working end to end**, verified on Moodle 4.5.13 / PHP 8.2 / PostgreSQL 17
(2026-09-02). Playback, per-viewer minting, caching and the authoring picker all
confirmed against a real Bunkercast account. Still alpha: see *Known gaps*.

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

## Verified behaviour

Measured on the reference install rather than assumed:

| | |
|---|---|
| Both plugins register | version `2026090200` each |
| Filter output | container with `data-fileid` only — **no credential in the HTML** |
| Web service, cache miss | ~526 ms (mints against Bunkercast) |
| Web service, cache hit | **~5 ms** (no mint) |
| DRM playback in Moodle | plays |
| Picker | lists the account's videos live, inserts the placeholder |

## Developing on this

`requirejs.php` serves **all** AMD modules as a single ~3.3 MB response keyed by
`jsrev`. So any JavaScript change needs three steps:

1. rebuild — `npx grunt amd --root=<plugin dir>`
2. `php admin/cli/purge_caches.php` (bumps `jsrev`)
3. a **hard** reload in the browser

Skipping any one of them reproduces a symptom that looks exactly like a code
defect. Two false trails during initial development came from this, plus one
from an `rsync --delete` that quietly removed `amd/build/`.

## Installing

Requires **Moodle 4.5+** — that is where filter classes moved to
`classes/text_filter.php`. On 4.1–4.4 the class must live in `filter.php`
instead; the documented approach is to keep the implementation where it is and
add a `class_alias()` shim in the old location. Not done yet.

Two plugins, and they go to different places — the picker lives inside the core
tree, which is where Moodle puts all `tiny_` plugins:

```
filter/bunkercast                        ->  <moodle>/filter/bunkercast
lib/editor/tiny/plugins/bunkercast       ->  <moodle>/lib/editor/tiny/plugins/bunkercast
```

1. Copy both directories into place.
2. Site administration → Notifications, to complete installation.
3. Build the JavaScript — `amd/build/` is generated and not committed:
   ```
   npx grunt amd --root=filter/bunkercast
   npx grunt amd --root=lib/editor/tiny/plugins/bunkercast
   ```
   Needs Node 22 (`lts/jod`, per Moodle's `.nvmrc`) and `npm install` in the
   Moodle root.
4. Enable the filter: Site administration → Plugins → Filters → Manage filters.
5. Configure it: Plugins → Filters → Bunkercast DRM video — paste the API key
   from Bunkercast (Account → Settings; shown once).

The picker needs no configuration. It hides itself unless an API key is set and
the user holds `filter/bunkercast:browselibrary` (editing teachers and managers
by default), so it never shows a button that would only fail.

## Choosing a plan

Pay-as-you-go is billed per minute watched and is the cheapest way to evaluate
this plugin. For real classroom use Professional is normally the right fit — a
45-minute lecture watched by 30 students is 1,350 minutes, so per-minute billing
adds up quickly, and Professional replaces it with a flat monthly fee.

**This plugin cannot see your balance or top it up.** An API key reaches only the
mint and list endpoints. Check the balance and plan in Bunkercast before a
lesson: if the account runs dry mid-class, every student in the room sees the
video fail at once.

## Gotchas the Moodle docs get wrong

Every one of these is a fatal or silent failure, and none is findable without a
running Moodle. Recorded because the docs still describe them the other way.

- **`filter()` signature.** The docs show `filter(string $text, ...)`. The parent
  declares `abstract public function filter($text, array $options = [])` —
  untyped. PHP forbids narrowing an untyped parameter, so the documented form is
  a **fatal error at class load**.
- **JS must be required from `setup()`, not `filter()`.** `core_filters\text_filter`
  provides `setup($page, $context)` for page requirements; every core filter
  needing JS uses it.
- **`new Promise(async (resolve) => ...)`** for a TinyMCE entry point, as the docs
  show, **fails Moodle's own ESLint** (`no-async-promise-executor`).
- **`addMenubarItemToPosition` does not exist.** It is `addMenubarItem`. A missing
  named export is `undefined`, and calling it throws inside `configure()` — which
  **takes the entire editor down**, leaving a plain textarea. The symptom looks
  nothing like a plugin fault.
- **`addToolbarButton` silently drops the button** when no section name matches,
  and `'content'` is not a default section (`history, formatting, view, alignment,
  indentation, lists, comments`). Create it first.
- **`simplekeys` cache definitions reject hyphens** — a uuid key throws a
  `coding_exception`. Strip them.

## Known gaps

- **The Moodle mobile app is untested.** Two things need proving: that DRM plays
  in the app's webview at all, and that the app sends a referrer. It generally
  does not, which is why **Restrict playback to this site is off by default** —
  turning it on will likely stop app users watching.
- **One API key per site**, so every teacher sees and can embed every video in
  the account, and deleting a video in Bunkercast silently breaks another
  teacher's course. Acceptable for a single school; per-course keys via
  `filterlocalsettings.php` would be the fix.
- **No "did they watch it" reporting**, so no completion tracking or gradebook
  integration. That would want a `mod_bunkercast` activity type.

## Layout

```
filter/bunkercast/                          the renderer — all security logic
├── version.php                             plain data, no side effects
├── settings.php                            API key, base URL, ttl, host lock
├── styles.css
├── db/services.php                         two AJAX web services
├── db/caches.php                           per-user minted-URL cache
├── db/access.php                           filter/bunkercast:browselibrary
├── lang/en/filter_bunkercast.php           strings (must define pluginname)
├── classes/text_filter.php                 placeholder → container; setup() loads the JS
├── classes/api.php                         Bunkercast HTTP client (server-side only)
├── classes/privacy/provider.php            declares the external transmission
├── classes/external/get_playback_url.php   authorisation + mint + cache
├── classes/external/get_videos.php         capability-gated library listing
└── amd/src/player.js                       fetches the URL, builds the iframe

lib/editor/tiny/plugins/bunkercast/         the picker — authoring convenience only
├── version.php                             depends on filter_bunkercast
├── lang/en/tiny_bunkercast.php
├── classes/plugininfo.php                  button/menu registration; hides itself
├── classes/privacy/provider.php            null_provider — stores nothing
└── amd/src/{common,plugin,commands,configuration}.js
```

The split matters: **everything that governs access lives in the filter.** The
picker only writes a placeholder a teacher could type by hand, so removing it
changes nothing about how videos are protected.
