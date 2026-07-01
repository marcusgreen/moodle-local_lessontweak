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
 * Per-lesson timer badge for lesson pages: elapsed count-up or countdown.
 *
 * The server passes the mode and the seconds value at page load (elapsed
 * seconds for count-up, remaining seconds for countdown, both derived from
 * {lesson_timer}.starttime). The badge then ticks using the browser clock, so
 * it is immune to server/client clock skew. Display only — nothing is sent
 * back and the lesson grade is untouched.
 *
 * @module     local_lessontweak/elapsedtimer
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Log from 'core/log';

/**
 * Format a number of seconds as HH:MM:SS.
 *
 * @param {number} totalseconds
 * @return {string}
 */
const formatHms = (totalseconds) => {
    const s = Math.max(0, Math.floor(totalseconds));
    const hh = Math.floor(s / 3600);
    const mm = Math.floor((s % 3600) / 60);
    const ss = s % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return pad(hh) + ':' + pad(mm) + ':' + pad(ss);
};

/**
 * Insert the badge at the top of the main content region.
 *
 * @param {HTMLElement} badge
 */
const place = (badge) => {
    const main = document.querySelector('[role="main"]')
        || document.getElementById('region-main')
        || document.body;
    main.insertBefore(badge, main.firstChild);
};

/**
 * Initialise the timer badge.
 *
 * @param {object} params
 * @param {string} params.mode 'elapsed' or 'countdown'
 * @param {number} params.seconds elapsed seconds, or remaining seconds for countdown
 * @param {string} [params.size] clock font size in rem
 * @param {number} [params.bar] 1 to show a depleting bar (countdown only)
 * @param {number} [params.total] full countdown duration in seconds (for the bar)
 * @param {number} [params.frozen] 1 to show a static value and not tick (end of lesson)
 */
export const init = (params) => {
    const countdown = params.mode === 'countdown';
    const base = Math.max(0, parseInt(params.seconds, 10) || 0);
    const loadedAt = Date.now();
    const labelKey = countdown ? 'countdownlabel' : 'elapsedlabel';
    const size = parseFloat(params.size);
    const total = Math.max(0, parseInt(params.total, 10) || 0);
    const showBar = countdown && Number(params.bar) === 1 && total > 0;
    const frozen = Number(params.frozen) === 1;

    getString(labelKey, 'local_lessontweak').then((label) => {
        const badge = document.createElement('div');
        badge.className = 'lessontweak-elapsed';

        const text = document.createElement('span');
        text.className = 'lessontweak-elapsed-label';
        text.textContent = label + ' ';

        const clock = document.createElement('time');
        clock.className = 'lessontweak-elapsed-clock';
        clock.setAttribute('aria-live', 'off');
        if (size > 0) {
            clock.style.fontSize = size + 'rem';
        }

        badge.appendChild(text);
        badge.appendChild(clock);

        let fill = null;
        if (showBar) {
            badge.classList.add('lessontweak-elapsed-hasbar');
            const track = document.createElement('div');
            track.className = 'lessontweak-elapsed-bar';
            track.setAttribute('role', 'progressbar');
            track.setAttribute('aria-valuemin', '0');
            track.setAttribute('aria-valuemax', String(total));
            fill = document.createElement('div');
            fill.className = 'lessontweak-elapsed-bar-fill';
            track.appendChild(fill);
            badge.appendChild(track);
        }

        place(badge);

        let timer = null;
        const tick = () => {
            // Frozen (end of lesson): hold the value passed by the server.
            const delta = frozen ? 0 : (Date.now() - loadedAt) / 1000;
            const seconds = countdown ? (base - delta) : (base + delta);
            const clamped = Math.max(0, seconds);
            clock.textContent = formatHms(clamped);

            if (fill) {
                fill.style.width = Math.min(100, (clamped / total) * 100) + '%';
            }

            if (countdown && seconds <= 0) {
                badge.classList.add('lessontweak-elapsed-expired');
                if (fill) {
                    fill.style.width = '0%';
                }
                if (timer) {
                    window.clearInterval(timer);
                }
            } else if (countdown && seconds <= 60) {
                badge.classList.add('lessontweak-elapsed-warning');
            }
        };
        tick();
        if (!frozen) {
            timer = window.setInterval(tick, 1000);
        }
        return label;
    }).catch(Log.error);
};
