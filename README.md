# local_lessontweak

Companion plugin that enhances **mod_lesson** with **zero core modifications**.
Everything rides core extension points — output hooks, plugin web services, the
`coursemodule_*` form callbacks and the navigation callback — so no file under
`mod/lesson` is ever edited.

## Features

| Feature | Where | Persists? |
|---|---|---|
| **Drag-to-reorder pages** | lesson editor (`edit.php`) | yes — drives lesson's own move action |
| **Confidence slider** | question pages (`view.php`) | yes — own web service + own table |
| **Page time tracking** | every lesson page (`view.php`) | yes — heartbeat web service + own table |
| **Timer badge** (elapsed or countdown, optional depleting bar, sized) | every lesson page (`view.php`) | display only |
| **Confidence report** | activity menu → *Confidence report* | reads `_conf` (+ `_ptime`) |
| **Time report** | activity menu → *Time report* | reads `_ptime` |
| **Appearance tweaks** (per-lesson CSS) | every lesson page (`view.php`) | selection stored in `_lopt` |

Each feature has a site-wide on/off in *Site administration → Plugins → Local
plugins → Lesson tweaks*. The confidence slider and the timer also have
per-lesson controls in the lesson's own settings form; the timer additionally
has a site-wide default clock size that a lesson can override.

## How it works (no core changes)

### 1. Drag-to-reorder pages
`db/hooks.php` listens to `\core\hook\output\before_footer_html_generation`.
`classes/hook_callbacks.php` detects the editor via `pagetype === 'mod-lesson-edit'`
plus the module context (`$PAGE->cm` is empty at footer time) and loads
`amd/src/dragreorder.js`. On drop the JS navigates to lesson's **existing** move
action (`lesson.php?...&action=moveit&pageid=<pid>&after=<afterpid>`), so all
permission, event and sesskey handling stays in core lesson.

### 2. Confidence slider
On question pages `amd/src/confidence.js` adds a 0–100% slider. When the student
submits their answer the value is saved through the plugin's own web service
`local_lessontweak_save_confidence` into `local_lessontweak_conf`
(keyed user + lesson + page + attempt, upserted). It does **not** touch the
lesson grade. Per-lesson visibility is set in the lesson settings form.

### 3. Page time tracking
`amd/src/tracker.js` runs a heartbeat that only counts time while the tab is
visible. Each pulse sends the measured delta to
`local_lessontweak_track_time`, which accumulates `timespent` in
`local_lessontweak_ptime` (same key, delta clamped 0–120 s/ping). A final flush
fires on `visibilitychange → hidden` and `pagehide`.

### 4. Timer badge (elapsed or countdown)
Per lesson, the teacher chooses **No timer**, **Elapsed (count up)** or
**Countdown**. `classes/hook_callbacks.php` reads the attempt start from lesson's
own `{lesson_timer}.starttime` (written for every attempt), computes elapsed or
remaining seconds, and passes them to `amd/src/elapsedtimer.js`, which ticks on
the browser clock (no server/client skew). Countdown turns amber in the last
minute and red at zero, and can optionally show a **depleting progress bar**
alongside the clock. The **clock size** (Small → Extra large) comes from the
lesson's own override, else the site-wide default. Display only — the lesson
grade and time limit are untouched.

### 5. Teacher reports
`local_lessontweak_extend_settings_navigation()` adds up to two links to the
lesson's settings menu (both gated on `mod/lesson:viewreports`), each appearing
only when its site-wide feature is enabled:

- **Confidence report** (`report.php`, shown when the confidence slider is on) —
  merges the confidence and time tables: student, page, attempt, confidence %,
  time spent.
- **Time report** (`timereport.php`, shown when page time tracking is on) —
  per-page active time plus attempt duration and a per-attempt total.

### 6. Appearance tweaks (per-lesson CSS)
An administrator defines named CSS "tweaks" as a JSON array in the plugin
settings (`tweaks`, validated by `classes/admin/setting_tweaks.php`); two
examples ship by default. Each lesson's settings gains an **Appearance tweak**
dropdown listing those names. `classes/hook_callbacks.php` looks up the lesson's
chosen tweak on `mod-lesson-view` pages and hands its CSS to
`amd/src/tweak.js`, which injects a `<style>` element into the page head. CSS
only, student view only; only site admins can author tweaks. Selection is stored
in `local_lessontweak_lopt.tweak` (the tweak name); if a tweak is later removed
from settings the lesson silently falls back to no tweak.

## Files

```
local/lessontweak/
├── version.php
├── settings.php                      # site-wide toggles + default timer size
├── styles.css
├── lib.php                           # nav + coursemodule_* form callbacks, timer helpers
├── report.php                        # teacher confidence report (+ time column)
├── timereport.php                    # teacher time report (per page + attempt total)
├── docs/                             # drag-drop demo (gif + mp4)
├── lang/en/local_lessontweak.php
├── db/
│   ├── hooks.php                     # before_footer listener
│   ├── services.php                  # save_confidence + track_time web services
│   ├── install.xml                   # _conf, _lopt, _ptime tables
│   └── upgrade.php
├── classes/
│   ├── hook_callbacks.php            # loads the right AMD module per page
│   ├── admin/setting_tweaks.php      # validates the appearance-tweaks JSON
│   ├── external/
│   │   ├── save_confidence.php
│   │   └── track_time.php
│   └── privacy/provider.php          # exports/deletes _conf and _ptime
└── amd/
    ├── src/{dragreorder,confidence,tracker,elapsedtimer,tweak}.js
    └── build/*.min.js                # what Moodle serves
```

## Database

- `local_lessontweak_conf` — confidence per user/lesson/page/attempt.
- `local_lessontweak_ptime` — active seconds per user/lesson/page/attempt.
- `local_lessontweak_lopt` — per-lesson options (`showconfidence`, `timermode`,
  `timerminutes`, `timerbar`, `timersize`, `tweak`).

## Install

1. Copy this folder to `local/lessontweak` in your Moodle root.
2. *Site administration → Notifications* to install.
3. *Site administration → Plugins → Local plugins → Lesson tweaks* — enable the
   features you want. Drag-reorder is on by default; the others are off.

## Usage

- **Reorder:** lesson → *Edit → Collapsed*, drag a page row, release.
- **Confidence / timer (mode, countdown minutes, depleting bar, clock size) per
  lesson:** lesson → *Edit settings → Lesson tweaks*.
- **Reports:** lesson → activity menu (*More*) → *Confidence report* and/or
  *Time report* (each shown when its feature is enabled).

## Notes / limits

- Drag-reorder enhances the **collapsed** editor only; the expanded editor uses
  different markup and is out of scope.
- The timer's start comes from `{lesson_timer}.starttime`. If the lesson has its
  own time limit, core already shows a countdown — this badge sits alongside it.
- Time tracking's final partial span (≤ ping interval) is best-effort on unload;
  bulk active time is captured.
- Rebuild JS after editing source: `grunt amd` from this directory (pre-built
  `amd/build` files are shipped so it works without grunt).

## Why this exists

Demonstrates the "no core modification" path for improving lesson: output hooks,
plugin web services, form and navigation callbacks driving lesson's existing
data and endpoints. See `mod/lesson/poc/no_core_improvements.md` for the wider
catalogue, and `mod/lesson/poc/options.md` for changes that *do* need core (new
scored question types, question-bank sharing).

## Credits

Created as part of the **MoodleMoot DACH 26 Lesson improvement team** work.
