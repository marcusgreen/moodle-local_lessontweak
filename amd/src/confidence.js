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
 * Confidence slider on lesson question pages.
 *
 * Adds a 0-100 slider to the answer form. When the student submits their
 * answer the value is stored through the plugin's own web service
 * (local_lessontweak_save_confidence) into the plugin's own table; it does
 * not touch the lesson grade.
 *
 * @module     local_lessontweak/confidence
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import Log from 'core/log';

const SELECTOR = {
    slider: 'lessontweak-confidence-slider',
    wrapper: 'lessontweak-confidence',
};

/**
 * Is this a lesson question answer form (as opposed to a content/branch form)?
 *
 * @param {HTMLFormElement} form
 * @return {boolean}
 */
const isQuestionForm = (form) => {
    if (!form.querySelector('input[name="pageid"]')) {
        return false;
    }
    // Question pages collect an answer; content/branch pages do not.
    return !!form.querySelector(
        'input[name^="answer"], textarea[name^="answer"], select[name^="answer"], ' +
        'input[type="radio"], input[name^="response"]'
    );
};

/**
 * Build the slider UI and insert it near the bottom of the form.
 *
 * @param {HTMLFormElement} form
 * @param {object} strings resolved {label, low, high}
 * @return {HTMLInputElement} the slider input
 */
const buildSlider = (form, strings) => {
    const wrapper = document.createElement('div');
    wrapper.className = SELECTOR.wrapper;

    const label = document.createElement('label');
    label.className = 'lessontweak-confidence-label';
    label.setAttribute('for', SELECTOR.slider);
    label.textContent = strings.label;

    const slider = document.createElement('input');
    slider.type = 'range';
    slider.id = SELECTOR.slider;
    slider.className = SELECTOR.slider;
    slider.min = '0';
    slider.max = '100';
    slider.step = '1';
    slider.value = '50';

    const output = document.createElement('output');
    output.className = 'lessontweak-confidence-value';
    output.setAttribute('for', SELECTOR.slider);
    output.textContent = slider.value + '%';

    const scale = document.createElement('div');
    scale.className = 'lessontweak-confidence-scale';
    const low = document.createElement('span');
    low.textContent = strings.low;
    const high = document.createElement('span');
    high.textContent = strings.high;
    scale.appendChild(low);
    scale.appendChild(high);

    slider.addEventListener('input', () => {
        output.textContent = slider.value + '%';
    });

    wrapper.appendChild(label);
    wrapper.appendChild(slider);
    wrapper.appendChild(output);
    wrapper.appendChild(scale);

    // Insert before the form's action buttons if we can find them, else append.
    const buttons = form.querySelector('.form-actions, [id^="fitem_id_submitbutton"]');
    if (buttons && buttons.parentNode) {
        buttons.parentNode.insertBefore(wrapper, buttons);
    } else {
        form.appendChild(wrapper);
    }

    return slider;
};

/**
 * Persist the confidence value via the plugin's web service.
 *
 * @param {number} cmid course module id
 * @param {number} pageid lesson page id
 * @param {number} confidence 0-100
 * @return {Promise}
 */
const save = (cmid, pageid, confidence) => {
    return Ajax.call([{
        methodname: 'local_lessontweak_save_confidence',
        args: {cmid: cmid, pageid: pageid, confidence: confidence},
    }])[0];
};

/**
 * Initialise the confidence slider on the current lesson question page.
 *
 * @param {number} cmid course module id
 */
export const init = (cmid) => {
    const forms = [...document.querySelectorAll('form')].filter(isQuestionForm);
    if (!forms.length) {
        return;
    }
    const form = forms[0];
    const pageInput = form.querySelector('input[name="pageid"]');
    const pageid = parseInt(pageInput.value, 10) || 0;
    if (!pageid) {
        return;
    }

    getStrings([
        {key: 'confidenceprompt', component: 'local_lessontweak'},
        {key: 'confidencelow', component: 'local_lessontweak'},
        {key: 'confidencehigh', component: 'local_lessontweak'},
    ]).then(([label, low, high]) => {
        const slider = buildSlider(form, {label, low, high});
        let saved = false;

        form.addEventListener('submit', (e) => {
            // Already saved on a previous (re-entrant) submit: let it through.
            if (saved) {
                return;
            }
            const confidence = parseInt(slider.value, 10);
            e.preventDefault();

            // Submit the original answer form once the save settles, success or
            // not: storing confidence must never block answering the question.
            const resubmit = () => {
                saved = true;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            };

            save(cmid, pageid, confidence).then(resubmit, (error) => {
                Log.error('local_lessontweak: failed to save confidence');
                Log.error(error);
                resubmit();
            });
        });
        return slider;
    }).catch(Log.error);
};
