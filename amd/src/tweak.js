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
 * Apply a per-lesson appearance tweak on lesson view pages.
 *
 * The server passes admin-authored CSS chosen for this lesson. The module
 * injects it into a <style> element in the document head. Appearance only —
 * nothing is sent back and the lesson grade is untouched.
 *
 * @module     local_lessontweak/tweak
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (params) => {
    const css = params && params.css ? String(params.css) : '';
    if (!css) {
        return;
    }
    const style = document.createElement('style');
    style.setAttribute('data-local-lessontweak', 'tweak');
    style.textContent = css;
    document.head.appendChild(style);
};
