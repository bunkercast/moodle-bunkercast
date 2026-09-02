// Fetches a playback URL per viewer and swaps it into an iframe.
//
// The filter deliberately emits only a file id, so nothing sensitive can end up
// in cached HTML. This module turns that into a player at view time.

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

        const frame = document.createElement('iframe');
        frame.src = url;
        frame.width = '100%';
        frame.title = fileid;
        frame.style.border = '0';
        frame.style.aspectRatio = '16 / 9';
        frame.setAttribute('allow', 'autoplay; fullscreen; encrypted-media');
        frame.setAttribute('allowfullscreen', '');

        node.replaceChildren(frame);
    } catch (e) {
        // A failure here is usually an empty balance or an unconfigured key —
        // both are the site's problem, not the viewer's, so say little.
        const message = document.createElement('div');
        message.className = 'bunkercast-video-error text-muted';
        message.textContent = await getString('unavailable', 'filter_bunkercast');
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
