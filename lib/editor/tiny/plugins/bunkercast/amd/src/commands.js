// Toolbar button, menu item, and the picker dialog.

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import common from './common';
import {getContextId} from 'editor_tiny/options';

const {component, buttonName, icon} = common;

/**
 * Fetch the account's ready videos.
 *
 * @param {number} contextid
 * @returns {Promise<Array<{fileid: string, name: string, durationminutes: number}>>}
 */
const fetchVideos = async (contextid) => {
    const {videos} = await Ajax.call([{
        methodname: 'filter_bunkercast_get_videos',
        args: {contextid},
    }])[0];
    return videos;
};

/**
 * A TinyMCE-native dialog is used rather than a Moodle modal: it keeps this
 * plugin off Moodle's modal API, which has changed shape across versions, and
 * a select box is enough for the job.
 *
 * @param {tinyMCE.Editor} editor
 * @param {object} strings
 */
const openPicker = async (editor, strings) => {
    const contextid = getContextId(editor);

    // Shown while the library loads, so a slow API call is not a dead click.
    const loading = editor.windowManager.open({
        title: strings.dialogtitle,
        body: {type: 'panel', items: [{type: 'htmlpanel', html: `<p>${strings.loading}</p>`}]},
        buttons: [{type: 'cancel', text: strings.cancel}],
    });

    let videos;
    try {
        videos = await fetchVideos(contextid);
    } catch (e) {
        loading.close();
        editor.windowManager.alert(strings.loadfailed);
        window.console.warn('tiny_bunkercast: library load failed', e);
        return;
    }

    loading.close();

    if (!videos.length) {
        editor.windowManager.alert(strings.novideos);
        return;
    }

    const items = videos.map((v) => {
        const mins = v.durationminutes ? ` (${Math.round(v.durationminutes)} min)` : '';
        return {value: v.fileid, text: `${v.name}${mins}`};
    });

    editor.windowManager.open({
        title: strings.dialogtitle,
        body: {
            type: 'panel',
            items: [{
                type: 'selectbox',
                name: 'fileid',
                label: strings.selectvideo,
                items,
            }, {
                type: 'htmlpanel',
                html: `<p class="tiny_bunkercast-help">${strings.selectvideohelp}</p>`,
            }],
        },
        initialData: {fileid: items[0].value},
        buttons: [
            {type: 'cancel', text: strings.cancel},
            {type: 'submit', text: strings.insert, primary: true},
        ],
        onSubmit: (dialog) => {
            const {fileid} = dialog.getData();
            // Insert the same placeholder a teacher could type by hand. The
            // filter resolves it to a player at view time; nothing about the
            // video's protection depends on this plugin.
            editor.insertContent(`<p>[bunkercast:${fileid}]</p>`);
            dialog.close();
        },
    });
};

export const getSetup = async () => {
    const [
        buttonInsert,
        menuitemInsert,
        dialogtitle,
        selectvideo,
        selectvideohelp,
        insert,
        cancel,
        loading,
        novideos,
        loadfailed,
    ] = await getStrings([
        'button_insert',
        'menuitem_insert',
        'dialogtitle',
        'selectvideo',
        'selectvideo_help',
        'insert',
        'cancel',
        'loading',
        'novideos',
        'loadfailed',
    ].map((key) => ({key, component})));

    const strings = {
        dialogtitle, selectvideo, selectvideohelp, insert, cancel, loading, novideos, loadfailed,
    };

    return (editor) => {
        editor.ui.registry.addIcon(icon, '<svg width="24" height="24" viewBox="0 0 24 24">'
            + '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" '
            + 'fill="none" stroke="currentColor" stroke-width="2"/>'
            + '<path d="M10 9.5l5 2.5-5 2.5z" fill="currentColor"/></svg>');

        editor.ui.registry.addButton(buttonName, {
            icon,
            tooltip: buttonInsert,
            onAction: () => openPicker(editor, strings),
        });

        editor.ui.registry.addMenuItem(buttonName, {
            icon,
            text: menuitemInsert,
            onAction: () => openPicker(editor, strings),
        });
    };
};
