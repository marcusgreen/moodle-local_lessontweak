// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Active-time heartbeat for lesson pages.
 *
 * Measures how long a student actively views a lesson page and sends the
 * elapsed delta to the plugin's own web service (local_lessontweak_track_time)
 * every interval while the tab is visible, plus a final flush when the page is
 * hidden or unloaded. Time only accumulates while document.visibilityState is
 * 'visible', so a backgrounded tab does not inflate the total.
 *
 * @module     local_lessontweak/tracker
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Log from 'core/log';

// How often to pulse (ms). Browsers throttle background timers, but the
// visibility guard means those throttled ticks send nothing anyway.
const INTERVAL = 20000;

// Never report more than this in a single delta (seconds): guards against
// clock jumps and timer throttling on wake.
const MAX_DELTA = 120;

/**
 * Read the lesson page id from the answer/continue form's hidden field.
 *
 * @return {number} page id, or 0 if not present
 */
const pageIdFromDom = () => {
    const input = document.querySelector('form input[name="pageid"]');
    return input ? (parseInt(input.value, 10) || 0) : 0;
};

/**
 * Initialise active-time tracking on the current lesson page.
 *
 * @param {number} cmid course module id
 */
export const init = (cmid) => {
    const pageid = pageIdFromDom();
    if (!pageid) {
        // No question/continue form on this page (e.g. lesson menu): nothing to track.
        return;
    }

    // Wall-clock marker for the start of the current visible span.
    let last = Date.now();
    let sending = false;

    /**
     * Seconds elapsed since the last marker, clamped, and advance the marker.
     *
     * @return {number}
     */
    const takeDelta = () => {
        const now = Date.now();
        const seconds = Math.min(MAX_DELTA, Math.round((now - last) / 1000));
        last = now;
        return Math.max(0, seconds);
    };

    /**
     * Send the accumulated delta to the web service.
     *
     * @param {number} delta seconds
     */
    const send = (delta) => {
        if (delta <= 0 || sending) {
            return;
        }
        sending = true;
        Ajax.call([{
            methodname: 'local_lessontweak_track_time',
            args: {cmid: cmid, pageid: pageid, delta: delta},
        }])[0].then(() => {
            sending = false;
            return null;
        }).catch((error) => {
            sending = false;
            Log.error('local_lessontweak: failed to record page time');
            Log.error(error);
        });
    };

    // Regular heartbeat while the tab is visible.
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            send(takeDelta());
        } else {
            // Hidden: drop the idle span so it is not counted on return.
            last = Date.now();
        }
    }, INTERVAL);

    // Flush the partial span when the student leaves or hides the page.
    const flush = () => send(takeDelta());
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flush();
        } else {
            last = Date.now();
        }
    });
    window.addEventListener('pagehide', flush);
};
