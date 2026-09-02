// Entry point. Must resolve with [pluginName, Configuration].

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';
import common from './common';
import {getSetup as getCommandSetup} from './commands';
import * as Configuration from './configuration';

const {component, pluginName} = common;

// Deliberately not `new Promise(async (resolve) => ...)`, which is what the
// Moodle developer docs show: its own ESLint config rejects that with
// no-async-promise-executor, and the rule is right — a rejection inside an async
// executor is swallowed rather than propagated. Promise.all().then() satisfies
// the same editor_tiny contract and surfaces failures.
export default Promise.all([
    getTinyMCE(),
    getPluginMetadata(component, pluginName),
    getCommandSetup(),
]).then(([tinyMCE, pluginMetadata, setupCommands]) => {
    tinyMCE.PluginManager.add(pluginName, (editor) => {
        setupCommands(editor);
        return pluginMetadata;
    });

    return [pluginName, Configuration];
});
