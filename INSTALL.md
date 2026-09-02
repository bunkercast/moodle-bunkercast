# Trying Bunkercast in your Moodle

A guide for Moodle administrators and teachers. You do not need any developer
tools — no Node, no command line, no server access beyond the usual plugin
install.

Roughly fifteen minutes end to end, most of it waiting for a video to encode.

---

## Before you start

**A Moodle 4.5 or newer site**, and an administrator account on it. Earlier
versions are not yet supported — see *Older Moodle* at the end.

**A Bunkercast account** with at least one video uploaded and encoded. Sign up
at [bunkercast.com](https://bunkercast.com); the free 60 minutes are enough to
test with. Upload a short video in Studio, press **Encode**, and wait for it to
show as ready — a couple of minutes for a small file.

**Somewhere to test that isn't your live site**, ideally. This is alpha
software.

---

## 1. Create an API key

In Bunkercast: **Account → Settings → API access → Create key**.

The key is shown **once**. Copy it somewhere safe before closing the dialog — if
you lose it, revoke it and make another.

It looks like `bck_live_` followed by two long hex strings.

> **Treat it like a password.** Anyone holding it can create playback links for
> every video in your account. It stays on your Moodle server and is never sent
> to a browser.

---

## 2. Install the three plugins

They install independently and in any order. **Site administration → Plugins →
Install plugins**, then upload each ZIP.

| Plugin | Type Moodle will detect | What it does |
|---|---|---|
| `filter_bunkercast` | Filter | Turns a reference into a player. **Required.** |
| `mod_bunkercast` | Activity module | Adds *Bunkercast video* to the activity chooser |
| `tiny_bunkercast` | TinyMCE editor plugin | Adds a picker button to the editor |

**Only the filter is required.** The other two are conveniences and both depend
on it, so install the filter first if Moodle complains about dependencies.

If you prefer copying files directly, the directories go to:

```
filter/bunkercast                      →  <moodle>/filter/bunkercast
mod/bunkercast                         →  <moodle>/mod/bunkercast
lib/editor/tiny/plugins/bunkercast     →  <moodle>/lib/editor/tiny/plugins/bunkercast
```

Then visit **Site administration → Notifications** to complete the install.

---

## 3. Turn the filter on

Filters arrive switched off. **Site administration → Plugins → Filters → Manage
filters** and set **Bunkercast DRM video** to **On**.

Nothing will play until you do this — not the activity either.

---

## 4. Paste in your API key

**Site administration → Plugins → Filters → Bunkercast DRM video.**

- **API key** — paste the key from step 1. This is the only required setting.
- **Player base URL** — leave alone unless Bunkercast gave you a different one.
- **Playback link lifetime** — one hour suits a normal lesson. See below.
- **Restrict playback to this site** — **leave off.** It is stronger, but it
  relies on the browser sending a referrer and the Moodle mobile app generally
  does not, so switching it on can stop app users watching.

Save. You are ready.

---

## 5. Add a video, three ways

### As an activity — the usual choice

In a course, turn editing on, then **Add an activity or resource → Bunkercast
video**. Give it a name, choose your video from the dropdown, save.

The video appears as its own item in the course, and Moodle can mark it complete
once a student has viewed it (**Completion conditions → Student must view this
activity**).

### With the editor button — for a video inside other content

Anywhere you get Moodle's text editor — a Text and media area, a Page, a forum
post, an assignment description — click the **Bunkercast** button on the toolbar,
or **Insert → Bunkercast video**. Pick a video and it drops the reference in
place.

Use this when the video belongs *within* something else, next to your own text.

### By hand — no plugins beyond the filter

Type the reference yourself, anywhere the editor works:

```
[bunkercast:36bb40db-1a39-480f-8148-4346a76388fd]
```

The id comes from **Bunkercast → Studio**. This is the fallback if you only
installed the filter.

---

## How access actually works

Worth understanding, because it is the point of the plugin.

**There is no shareable link.** Each time someone opens the page, your Moodle
decides whether they may watch — using enrolment, groups, availability
conditions, everything it already knows — and only then asks Bunkercast for a
credential valid for that one person for a short time.

So:

- **A copied URL stops working**, on its own, when the link expires.
- **Unenrol a student and they cannot watch**, immediately, with nothing to
  revoke and nothing to remember.
- **The video files are encrypted.** Even a downloaded fragment is unplayable
  without a licence, unlike an expiring link to an ordinary MP4.

Screen recording remains possible, as it does on every platform. Optional
Screenshot Protection in Bunkercast raises that bar considerably on supported
devices; it is per-video and set in Studio, not here.

---

## What it costs, and which plan you want

Bunkercast bills **minutes watched**, not minutes stored.

That adds up faster than people expect in a classroom:

| | minutes consumed |
|---|---|
| 45-minute lecture, 1 viewer | 45 |
| 45-minute lecture, 30 students | **1,350** |
| Twelve such lectures, 30 students | **16,200** |

**Pay-as-you-go is right for evaluating this plugin.** For real teaching, the
**Professional** plan replaces per-minute billing with a flat monthly fee, which
is almost always the correct fit for a class or a cohort.

> **This plugin cannot see your balance and cannot top it up.** If the account
> runs dry mid-lesson, every student in the room sees the video fail at the same
> moment. Check your balance in Bunkercast before a class — a habit worth
> forming while you are on pay-as-you-go.

**Playback link lifetime** trades the same way: shorter means a copied link dies
sooner, longer means fewer requests. One hour is a sensible default. It does not
affect what you are billed — only watching does that.

---

## If something does not work

**"This video is currently unavailable"** — the commonest cause is an empty
balance; the second is a wrong or revoked API key. Check both in Bunkercast.

**The video area stays black saying "Loading video…"** — the filter is producing
the player but the browser cannot reach Bunkercast. Check your site can make
outbound HTTPS requests, and look for a blocked request in the browser console.

**The dropdown is empty when adding an activity** — Moodle cannot list your
library. Either the API key is wrong, or you have no *encoded* videos yet
(uploaded is not enough — press Encode and wait).

**No Bunkercast button in the editor** — the picker hides itself unless an API
key is set and you have permission to browse the library (editing teachers and
managers by default). It never shows a button that would only fail.

**Nothing plays anywhere** — check the filter is **On** in Manage filters. This
catches most first-time installs.

---

## Known limits, plainly

**Restoring into a Moodle connected to a different Bunkercast account will not
play.** Course backup and restore work, and the video reference travels with the
course — but it refers to a video in *your* Bunkercast account. Restore that
course onto a site using a different account and the activity will say the video
is unavailable, because Bunkercast will not issue a credential for a video that
account does not own. That is deliberate: a backup file must not be a way to
inherit someone else's video.

**The mobile app is untested.** We do not yet know whether DRM playback works in
the Moodle app's browser view. Test it yourself before promising it to students,
and leave *Restrict playback to this site* off — it would very likely break the
app.

**One Bunkercast account per Moodle site.** Every teacher who can add a video
sees the whole account's library, and deleting a video in Bunkercast will break
any activity using it, in any course.

**No "did they watch it" reporting.** Moodle can record that a student *opened*
the activity. Actual watch time is not reported back.

---

## Older Moodle

Moodle 4.5 moved where filter plugins keep their code, and this plugin uses the
new location. On 4.1–4.4 it will not install. A compatibility shim is
straightforward but not written yet — tell us if you need it, since that tells
us it is worth doing.

---

## Getting help

Include your Moodle version, which of the three plugins you installed, and the
exact message you saw. If a video will not play, the browser console usually
carries the real reason.
