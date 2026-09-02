// Places the button on the toolbar and in the Insert menu.

import {addMenubarItemToPosition, addToolbarButton} from 'editor_tiny/utils';
import common from './common';

const {buttonName} = common;

export const configure = (instanceConfig) => ({
    toolbar: addToolbarButton(instanceConfig.toolbar, 'content', buttonName),
    menu: addMenubarItemToPosition(instanceConfig.menu, 'insert', buttonName, 'media'),
});
