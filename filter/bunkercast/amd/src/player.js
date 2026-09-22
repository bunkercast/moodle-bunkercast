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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Fetches a playback URL per viewer and swaps it into an iframe.
 *
 * The filter deliberately emits only a file id, so nothing sensitive can end up
 * in cached HTML. This module turns that into a player at view time.
 *
 * @module     filter_bunkercast/player
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';
import {eventTypes} from 'core_filters/events';

const SELECTOR = '.bunkercast-video[data-fileid]:not([data-bc-done])';

/**
 * Replace one container with a player iframe.
 *
 * @param {HTMLElement} node
 */
const load = async (node) => {
    // Mark first, so a filterContentUpdated event firing mid-flight cannot
    // start a second request for the same container.
    node.setAttribute('data-bc-done', '1');

    const fileid = node.getAttribute('data-fileid');
    const contextid = parseInt(node.getAttribute('data-contextid'), 10);

    try {
        const {url} = await Ajax.call([{
            methodname: 'filter_bunkercast_get_playback_url',
            args: {fileid, contextid},
        }])[0];

        // Sizing lives entirely in styles.css: the container carries the aspect
        // ratio and the width cap, and the iframe fills it. Setting a ratio here
        // too would be a second source of truth that silently overrides it.
        const frame = document.createElement('iframe');
        frame.src = url;
        frame.title = fileid;
        frame.setAttribute('allow', 'autoplay; fullscreen; encrypted-media');
        frame.setAttribute('allowfullscreen', '');

        node.replaceChildren(frame);
    } catch (e) {
        // Most failures are the site's problem, not the viewer's — an empty
        // balance, an unconfigured key — so say little.
        //
        // One exception. An unauthorised video is repairable BY THE PERSON LOOKING
        // AT IT, so it gets its own message naming the fix. Without this branch
        // every failure reads "unavailable" and a teacher whose pasted placeholder
        // was never authorised goes looking at their balance and their API key.
        const key = (e && e.errorcode === 'notembeddedhere') ? 'notembeddedhere' : 'unavailable';
        const message = document.createElement('div');
        message.className = 'bunkercast-video-error text-muted';
        message.textContent = await getString(key, 'filter_bunkercast');
        node.replaceChildren(message);
        window.console.warn('filter_bunkercast: could not load video', e);
    }
};

/**
 * @param {ParentNode} root
 */
const process = (root) => {
    root.querySelectorAll(SELECTOR).forEach(load);
};

export const init = () => {
    process(document);

    // Content loaded by AJAX — a forum post expanding, a modal, a dynamic
    // course page — is filtered after this module ran, so re-scan on the event
    // core_filters fires for exactly this case.
    document.addEventListener(eventTypes.filterContentUpdated, (e) => {
        process(e.target instanceof Element ? e.target : document);
    });
};
