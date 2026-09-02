// Editor options for tiny_bunkercast.
//
// The context id comes from plugininfo::get_plugin_configuration_for_context
// and is needed for the web-service call, so it is registered as an option
// rather than looked up in the DOM.

import {getPluginOptionName} from 'editor_tiny/options';
import common from './common';

const contextIdName = getPluginOptionName(common.pluginName, 'contextid');

export const register = (editor) => {
    editor.options.register(contextIdName, {
        processor: 'number',
        default: 0,
    });
};

export const getContextId = (editor) => editor.options.get(contextIdName);
