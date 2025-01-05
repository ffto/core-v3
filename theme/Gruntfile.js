/**
 * Gruntfile Core V2
 * =====================================================================================================================
 * This new Gruntfile.js is simplified by referencing the "grunt.core.js" in the "Core" codebase. The "grunt.core.js"
 * file has a list of functions that speed up the process of using grunt.
 * 
 * 
 * Config:
 * -----------------------------------------------------------------------------
 * By default, the configuration except a file/folder structure. But this can be 
 * easily changed from one computer to another, with the help of a ".env" file.
 * 
 * Create a ".env" file in the theme folder (make sure to NOT push it in the Git 
 * repository). In that file, you can overwrite every properties in the config.
 * 
 * For example, if the relative path to the "grunt.core.js" file is different,
 * Set it in the ".env" file like so:
 *  
 *      core_path="../../.....path to the core....." 
 * 
 * If the URL is different than specified, set it in the ".env" file:
 * 
 *      url="http://..... local url of the site ...."
 * 
 * 
 * Settings
 * -----------------------------------------------------------------------------
 * This section have the general setup for the code to work. We pass the 
 * necessary node variable (they are used in the the "grunt.core.js" code).
 * 
 */
 module.exports = function (grunt){
    const _       = require('lodash');
    const sass    = require('sass-embedded');
    const load    = require('load-grunt-tasks')(grunt);
    const babel   = require('@rollup/plugin-babel');
    const resolve = require('@rollup/plugin-node-resolve');
    let   config  = require('dotenv').config().parsed || {};
    let   core    = null;
    try{  core    = require('./ffto.grunt'); }catch(e){};
    
    // make sure the local cached ffto.grunt.js file exists, it will be updated when core.config() is called
    if (!core || !core.config) return console.log('\x1b[41m\n\n  [ERROR] ffto.grunt.js is missing. Make sure to copy it from the core into this project. \n\x1b[0m\n');
    
    // config ------------------------------------------------------------------
    config = _.merge({
        wp    : false,                                   // Specify if it's a Wordpress website, the "load()" functions below uses this
        url   : 'https://www.shared-expenses.local.com/',   // Url used with BrowserSync
        php   : { version : 3 },                        // Which PHP version of the code to use
        style : { version : 3 },                        // Which STYLE version of the code to use
        js    : { version : 1 },                        // Which JS version of the code to use
    }, config);

    // settings ----------------------------------------------------------------
    core = core.nodes({ grunt, sass, resolve, babel }).config(config, __dirname);

    core.replace('.scss', ':comments')
        .rename('.js', ':version');      // remove the "-v0" from the file when importing
        // .replace('.php', ':clean');     // add a string replace behavior in the imported code. Will replace "ffto_" to "h9_"
        
    // php ---------------------------------------------------------------------
    // Load all the necessassary PHP code and specify the extra needed files/plugins
    core.load(':php', {
        // 'files' : [
        //     'html/skip-link',
        // ],
        // 'files' : [
        //     'modules/files',
        //     'modules/html',
        //     'modules/form',
        //     'modules/form-repeater',
        //     'classes/storage',
        // ],
    });

    // css ---------------------------------------------------------------------
    // Load all the style files with the extra files
    core.load(':style', {
        // add to the "utils.scss" file
        'files' : [
            'items/icons/*',
        ],
        // add those files to the "_" folder
        'copy' : [
            // 'modules/icons'
        ]
    });

    // process the style files
    core.css(':style.base.css')
        .css('style.scss')
        .css('style-admin.scss');

    // js ----------------------------------------------------------------------
    // Load all the necessary JS code with extra files.
    core.load(':js', {
        'prepend' : [
            '../v2/utils',
            '../v2/utils/[0-9]*',
            '../v2/website',
        ],
        'files' : [
			// 'utils/layout', 
			// 'modules/items',
			// 'modules/element/media',
        ],
        'components' : [
			// 'container/accordion',
			// 'container/carousel',
			// 'container/tabs',
			// 'element/media',
			// 'behavior/tooltips-v2', 
        ]
    });

    // process the js files
    core.js(':js.app');

    // start -------------------------------------------------------------------
    core.start();
};