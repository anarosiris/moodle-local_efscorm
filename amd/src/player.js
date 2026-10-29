// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Full-screen SCORM player: the top bar with the activity name and the exit
 * button, and the sizing of the content frame to the window.
 *
 * The exit button reuses the link of the core exit button (so the SCORM is
 * closed and saved the usual way); when the core has none (new-window player)
 * or the course is a "single activity" one, it goes to the fallback URL.
 *
 * @module     local_efscorm/player
 * @copyright  2026 Ana Rosiris Castro Mendez, eLearning Fácil (https://elearningfacil.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    COREEXIT: '#page-mod-scorm-player .flex-row-reverse > a.btn, #page-mod-scorm-player .d-flex.flex-row-reverse a.btn',
    OBJECT: 'scorm_object',
    PAGE: 'scormpage',
    NAVPANEL: 'scorm_navpanel',
    CONTENT: 'scorm_content',
    TOCTOGGLE: '#scorm_toc_toggle',
};

/**
 * Measures what sits above the content frame (collapsed table of contents)
 * and below it (the SCO navigation panel) and hands the values to the CSS.
 */
const fit = () => {
    const object = document.getElementById(SELECTORS.OBJECT);
    const page = document.getElementById(SELECTORS.PAGE);
    if (!object || !page) {
        return;
    }
    const top = object.getBoundingClientRect().top - page.getBoundingClientRect().top + page.scrollTop;
    const nav = document.getElementById(SELECTORS.NAVPANEL);
    const bottom = (nav && nav.offsetParent) ? nav.offsetHeight : 0;
    document.documentElement.style.setProperty('--local-efscorm-top', Math.max(0, Math.round(top)) + 'px');
    document.documentElement.style.setProperty('--local-efscorm-bottom', Math.round(bottom) + 'px');
};

/**
 * Re-measures after the layout settles.
 */
const refit = () => {
    window.requestAnimationFrame(fit);
    setTimeout(fit, 300);
};

/**
 * Builds the top bar.
 *
 * @param {String} title Activity name (plain text).
 * @param {String} exit Label of the exit button.
 * @param {String} href URL of the exit button.
 */
const addBar = (title, exit, href) => {
    const bar = document.createElement('header');
    bar.id = 'local-efscorm-bar';
    bar.setAttribute('role', 'banner');
    const heading = document.createElement('h1');
    heading.className = 'local-efscorm-title';
    heading.textContent = title;
    heading.title = title;
    const link = document.createElement('a');
    link.className = 'local-efscorm-exit';
    link.href = href;
    link.textContent = exit;
    bar.appendChild(heading);
    bar.appendChild(link);
    document.body.insertBefore(bar, document.body.firstChild);
};

/**
 * Sets up the full-screen player page.
 *
 * @param {Object} data
 * @param {String} data.title Activity name, as plain text.
 * @param {String} data.exit Label of the exit button.
 * @param {String} data.fallback URL to leave to when the core exit button cannot be used.
 * @param {Boolean} data.force Always use the fallback URL (single-activity course).
 */
export const init = (data) => {
    const core = document.querySelector(SELECTORS.COREEXIT);
    const href = (core && !data.force) ? core.getAttribute('href') : data.fallback;
    if (core && core.parentElement) {
        core.parentElement.classList.add('local-efscorm-coreexit');
    }
    addBar(data.title, data.exit, href);

    fit();
    window.addEventListener('resize', refit);
    setTimeout(fit, 500);
    setTimeout(fit, 2000);
    document.addEventListener('click', (e) => {
        if (e.target && e.target.closest && e.target.closest(SELECTORS.TOCTOGGLE)) {
            refit();
        }
    });
    const content = document.getElementById(SELECTORS.CONTENT);
    if (content && window.ResizeObserver) {
        new ResizeObserver(refit).observe(content);
    }
};
