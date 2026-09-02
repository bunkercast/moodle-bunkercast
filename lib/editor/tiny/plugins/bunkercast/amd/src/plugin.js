// Entry point. Must resolve with [pluginName, Configuration].

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';
import common from './common';
import {getSetup as getCommandSetup} from './commands';
import {register as registerOptions} from './options';
import * as Configuration from './configuration';

const {component, pluginName} = common;

export default new Promise(async(resolve) => {
    const [tinyMCE, pluginMetadata, setupCommands] = await Promise.all([
        getTinyMCE(),
        getPluginMetadata(component, pluginName),
        getCommandSetup(),
    ]);

    tinyMCE.PluginManager.add(pluginName, (editor) => {
        registerOptions(editor);
        setupCommands(editor);
        return pluginMetadata;
    });

    resolve([pluginName, Configuration]);
});
