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
 * Drag-to-reorder pages in the collapsed lesson editor.
 *
 * Drives the lesson module's existing move action
 * (lesson.php?action=moveit&pageid=..&after=..) so no core change is needed.
 * On drop the row order is read from the DOM and the browser navigates to the
 * move URL; the server reorders and reloads the editor.
 *
 * @module     local_lessontweak/dragreorder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';

/**
 * Read the lesson page id encoded in a row's title anchor (id="lesson-<pageid>").
 *
 * @param {HTMLElement} row
 * @return {number} page id, or 0 if not found
 */
const pageIdOf = (row) => {
    if (!row) {
        return 0;
    }
    const anchor = row.querySelector('a[id^="lesson-"]');
    if (!anchor) {
        return 0;
    }
    return parseInt(anchor.id.replace('lesson-', ''), 10) || 0;
};

/**
 * Find the editor table: the one whose rows carry lesson-<id> title anchors.
 *
 * @return {HTMLTableElement|null}
 */
const findTable = () => {
    const anchor = document.querySelector('table a[id^="lesson-"]');
    return anchor ? anchor.closest('table') : null;
};

/**
 * Work out the row the dragged row should be dropped before, from cursor Y.
 *
 * @param {HTMLElement} container tbody
 * @param {number} y clientY
 * @param {HTMLElement} dragging the row being dragged
 * @return {HTMLElement|null} row to insert before, or null to append
 */
const rowAfterCursor = (container, y, dragging) => {
    const rows = [...container.querySelectorAll('tr')].filter((r) => r !== dragging);
    for (const row of rows) {
        const box = row.getBoundingClientRect();
        if (y < box.top + box.height / 2) {
            return row;
        }
    }
    return null;
};

/**
 * Initialise drag-to-reorder.
 *
 * @param {number} cmid course module id
 * @param {string} sesskey session key
 */
export const init = (cmid, sesskey) => {
    const table = findTable();
    if (!table) {
        return;
    }
    const tbody = table.querySelector('tbody') || table;
    let dragging = null;

    tbody.querySelectorAll('tr').forEach((row) => {
        if (!pageIdOf(row)) {
            return;
        }
        row.setAttribute('draggable', 'true');
        row.classList.add('lessontweak-draggable');

        // Stop inner links/images hijacking the drag with their native behaviour.
        row.querySelectorAll('a, img').forEach((el) => el.setAttribute('draggable', 'false'));

        // Visible grip in the first cell.
        const firstCell = row.querySelector('td, th');
        if (firstCell && !firstCell.querySelector('.lessontweak-handle')) {
            const handle = document.createElement('span');
            handle.className = 'lessontweak-handle';
            handle.setAttribute('aria-hidden', 'true');
            handle.textContent = '⠇'; // Braille dots, a common drag grip.
            firstCell.insertBefore(handle, firstCell.firstChild);
        }

        row.addEventListener('dragstart', (e) => {
            dragging = row;
            row.classList.add('lessontweak-dragging');
            e.dataTransfer.effectAllowed = 'move';
            // Firefox needs data set for the drag to start.
            e.dataTransfer.setData('text/plain', String(pageIdOf(row)));
        });

        row.addEventListener('dragend', () => {
            if (!dragging) {
                return;
            }
            row.classList.remove('lessontweak-dragging');

            // The page now preceding the dragged row decides the "after" param.
            const after = pageIdOf(dragging.previousElementSibling);
            const pageid = pageIdOf(dragging);
            dragging = null;

            const url = Config.wwwroot + '/mod/lesson/lesson.php'
                + '?id=' + encodeURIComponent(cmid)
                + '&sesskey=' + encodeURIComponent(sesskey)
                + '&action=moveit'
                + '&pageid=' + encodeURIComponent(pageid)
                + '&after=' + encodeURIComponent(after);

            window.location.assign(url);
        });
    });

    tbody.addEventListener('dragover', (e) => {
        if (!dragging) {
            return;
        }
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const before = rowAfterCursor(tbody, e.clientY, dragging);
        if (before) {
            tbody.insertBefore(dragging, before);
        } else {
            tbody.appendChild(dragging);
        }
    });

    // Some browsers require a drop handler that prevents default to complete the drag.
    tbody.addEventListener('drop', (e) => {
        if (dragging) {
            e.preventDefault();
        }
    });
};
