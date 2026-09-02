// Places the button on the toolbar and in the Insert menu.

import {addMenubarItem, addToolbarButton, addToolbarSection} from 'editor_tiny/utils';
import common from './common';

const {buttonName} = common;

export const configure = (instanceConfig) => {
    let toolbar = instanceConfig.toolbar;

    // addToolbarButton appends only where a section's name matches, and SILENTLY
    // DROPS the button otherwise. 'content' is not one of Moodle's default
    // toolbar sections — those are history, formatting, view, alignment,
    // indentation, lists and comments (lib/editor/tiny/amd/src/defaults.js).
    // Core's h5p and noautolink target 'content' anyway, which only works if
    // something else created it, so create it when absent.
    const hasContent = Array.isArray(toolbar) && toolbar.some((section) => section.name === 'content');
    if (!hasContent) {
        toolbar = addToolbarSection(toolbar, 'content', 'lists', true);
    }

    return {
        toolbar: addToolbarButton(toolbar, 'content', buttonName),
        // NOT addMenubarItemToPosition — that does not exist in editor_tiny/utils.
        // Importing a missing named export yields undefined, and calling it threw
        // inside configure(), which took the whole editor down and left a plain
        // textarea. The real function is addMenubarItem.
        menu: addMenubarItem(instanceConfig.menu, 'insert', buttonName, 'media'),
    };
};
