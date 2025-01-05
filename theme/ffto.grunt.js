// TODO
// [ ] Add "replace" and "rename" shortcut in _assets(), instead of the functions core.rename(), core.replace()
// [ ] find a way to "watch" the file dynamically, when you add a new file, to not have to re-start the grunt

// const { log } = require('console');
// const path = require('path');

// @info update NODE with "n" in the terminal: 
//  npm install -g n
//  sudo n latest

var config= {
	url   : '',
	wp    : false,
	import: true,
	theme : null,
	sass  : 'expanded',
	// sass  : 'compressed',
	// sass  : 'compact',
	style: {
		version: 2,
		core   : '@core/assets/style/$ver/',
		src    : '@style/src/',
		dist   : null,                         // dynamic according to config.wp 
	},
	js : {
		version: 2,
		core   : '@core/assets/js/$ver/',
		src    : '@js/src/',
		dist   : '@js/dist/',
	},
	php : {
		version: 2,
		core   : '@core/assets/php/$ver/',
		src    : '@php/core/',
		dist   : '@php/core/',
	},
};

var version = '2.0';
var core    = {};
var nodes   = {};
var grunt   = {};

var replace  = {};
var rename   = {};
var watching = {};

var alias = {
    '@core'       : __dirname,
    '@theme'      : './theme',
    '@style'      : './theme/assets/style/',
    '@js'         : './theme/assets/js/',
    '@php'        : './theme/assets/php/',
    // shortcuts --------------------------------------------------------------- 
    // TODO add the :icons shortcut
    ':style.base.css' : function (){
        core.css('base/base.scss', '@style.src/_/base.css', {
            'map'   : false,
            'output': 'expanded',
            'key'   : 'base.cache-old',
        }, [
            'base/vars',
        ]);

        // new naming
        core.css('base/cache.scss', '@style.src/_/cache.css', {
            'map'   : false,
            'output': 'expanded',
            'key'   : 'base.cache',
        }, [
            'base/vars',
        ]);
    },
    ':style.icons.css' : function (){
        core.css('base/icons.scss', '@style.src/_/icons.css', {
            'map'   : false,
            'output': 'expanded',
            'key'   : 'base.cache',
        }, [
            'base/vars',
        ]);
    },
    ':js.app' : function (){
        core.concat([
            '@js.src/setup',
            '@js.src/web/*',
            '@js.src/web-elements/*',
            '@js.src/elements/*',
            '@js.src/apis/*',
            '@js.src/api/*',
            '@js.dist/_', 
            '@js.dist/_items', 
            '@js.src/app',
        ], '@js.dist/app.js', {'ext':'.js'}, 'js.core.app');        
    },
};

// helpers ---------------------------------------------------------------------
function _isobj (item) {
	return (item && typeof item === 'object' && !Array.isArray(item));
}
function _merge (target, ...sources) {
	if (!sources.length) return target;
	const source = sources.shift();

	if (_isobj(target) && _isobj(source)) {
		for (const key in source) {
			if (_isobj(source[key])) {
				if (!target[key]) Object.assign(target, { [key]: {} });
				_merge(target[key], source[key]);
			} else {
				Object.assign(target, { [key]: source[key] });
			}
		}
	}

	return _merge(target, ...sources);
}
function _repeat (str, times){
    if (typeof times !== 'number' || times < 0) return '';
    return new Array((times+1) | 0).join(str);
};
function _arr (list, arr){
    list = typeof(list) === 'string' ? list.split(',') : list;
    list = list.map(function (v){ return v.trim(); });

    if (arr instanceof Array){
        list = arr.concat(list);
    }

    return list;
}
function _log (msg){
    params = Array.prototype.slice.apply(arguments, [1]);

    console.log('\x1b[2m\n[LOG] '+msg+'\x1b[0m\n');

    if (params.length){
        console.log.apply(null, params);
        console.log('\n');
    }
}
function _warn (msg){
    params = Array.prototype.slice.apply(arguments, [1]);

    console.log('\n\x1b[43m\x1b[30m\n\n  [WARN] '+msg+'   \n\x1b[0m');

    if (params.length){
        console.log.apply(null, params);
        console.log('\n');
    }
}
function _inject (txt, pad, offset, align){
    txt   = txt.toString();
    align = align || 'center';

    var length = pad.length - txt.length + (offset||0);
    if (align === 'left'){
        return txt + pad.substring(txt.length);
    }else if (align === 'center'){
        var before = Math.floor(length/2);
        var after  = Math.ceil(length/2);
        return pad.substring(0, before) + txt + pad.substring(0, after);
    }
}
function _ext (path, ext){
	return ext ? path.replace(/\.[a-z][a-z0-9]+$/, '') + ext : path;
}
function _fileinfo (path){
    if (typeof(path) !== 'string') return;
    var pair     = path.match(/(.+\/)?(.*\.[a-z][a-z0-9]*)?/i);
    var folder   = (pair ? pair[1] : '') || '';
    var basename = (pair ? pair[2] : '') || '';
    var ext      = basename.match(/\..+?$/);

    ext = ext ? ext[0] : '';

    return {
        basename: basename,
        name    : basename.replace(ext, ''),
        folder  : folder,
        ext     : ext,
    };
}
function _value (key, obj){
    var item = typeof(alias[key]) === 'function' ? alias[key]() : alias[key];
    return _merge(item || {}, obj || {});
}
function _key (){
    // TODO generate a key for watching
}
function _alias (item, list, prefix, version){
	if (typeof(item) === 'string'){
        item = item || '';
        list = list || alias;
        
        // replace vars that starts with "@" or ":" or "$"
        item = item.replace(/[\@\:\$][^$\/]+/g, function (m){
            return list[m] ? list[m]: m;
        });

        item = item.replace(/\/+/g, '/');   // make sure there's no double slash
    }else if (item && typeof(item) === 'object'){
        var aaa    = {...alias, ...{'$ver':`v${version}`}};
        var prefix = prefix ? prefix + '.' : '';
        for (var i in list){
            item['@' + prefix + i] = _alias(list[i], aaa);
        }
    }

	return item;
}
function _resolve (dir, paths, ext){
    dir   = dir || '';
    dir   = dir ? dir + '/' : '';
    paths = paths || '';
    paths = typeof(paths) === 'string'? paths.split(',') : paths;
    return paths.map(function (v){
        v = v.trim();
		v = _alias((v[0] === '@' ? '' : dir) + v); // only add the dir if the path doens't already have a set path
		v = _ext(v, ext);
		return v;
	});
}
function _assets (type, files, to, args, key, prefixed){
    if (args === ':php'){
        args = {
            'from': '@php.core/',
            'to'  : '@php.dist/',
            'ext' : '.php',
        };
    }else if (args === ':style'){
        args = {
            'from': '@style.core/',
            'to'  : '@style.src/',
            'ext' : '.scss',
        };
    }else if (args === ':js'){
        args = {
            'from': '@js.core/',
            'to'  : '@js.dist/',
            'ext' : '.js',
        };
    }else if (typeof(args) === 'string'){
        args = {'ext':args};
    }

    // find an alias 
    if (files in alias){   
        args  = _value(files, args);
        files = null;
	}

	args       = _merge({}, args || {}); // make sure it's a copy of the args
    args.key   = args.key || key;
    args.files = args.files || '';
    args.from  = args.from || '';
	args.to    = args.to || '';
	args.ext   = args.ext || '';

    // global rename/replaces by file extension
    var info = _fileinfo(args.ext);

    if (info && info.ext){
        if (!args.rename && info.ext in rename){
            args.rename = function (d, s){
                for (var i in rename[info.ext]){ s = rename[info.ext][i](d, s); }
                return s;
            }
        }
        if (!args.replace && info.ext in replace){
            args.replace = function (c){
                for (var i in replace[info.ext]){ c = replace[info.ext][i](c); }
                return c;
            }
        }
    }else{
        // TODO validate that this works properly
        args.replace = function (c, p){
            var info  = _fileinfo(p);
            var items = replace[info.ext] || {};
            for (var i in items){ c = items[i](c); }
            return c;
        }
    }

    // output
    files       = files || args.files;
	args.files  = _resolve(prefixed ? args.from : '', files, args.ext);
	args.to     = _alias(args.to + (to || ''));
    args.from   = _alias(args.from);

    // auto-watch
    var w = args.watch;
    if (w !== false){
        w     = (typeof(w) === 'string' ? {'key':w} : w) || {};
        w.key = w.key || args.key;

        var files    = _resolve(args.from, files, args.ext, args.alias);
        var list     = watching[w.key] = watching[w.key] || {files:[], tasks:[], options:{}};
        list.options = {...list.options, ...(w.options || {})};
        list.files   = list.files.concat(files);
        list.tasks   = list.tasks.concat([type + ':' + args.key]);

        if (w.files){
            list.files = list.tasks.concat(w.files);
        }
        if (w.tasks){
            list.tasks = list.tasks.concat(w.tasks);
        }
    }

    return args;
}
function _brand (){ 
    var bears = [
        ['ᕦʕ •ᴥ•ʔᕤ'],
        ['ʕ ㅇ ᴥ ㅇʔ',-2],
        ['ʕ´• ᴥ•̥`ʔ',1],
        ['ʕง•ᴥ•ʔง'],
        ['ʕ•ᴥ•ʔﾉ♡'],
        ['ʕ ꈍᴥꈍʔ',-2],
        ['ʕ -㉨- ʔ',-1],
        ['ʕ – ᴥ – ʔ'],
        ['ʕ≧ᴥ≦ʔ'],
        ['ʕ　·㉨·ʔ',-2],
        ['ʕノ•ᴥ•ʔノ ︵ ┻━┻',-3]
    ];
    var colors = [
        '\x1b[41m', // red,
        '\x1b[42m', // green,
        '\x1b[43m', // yellow,
        '\x1b[44m', // blue,
        '\x1b[45m', // magenta,
        '\x1b[46m', // cyan,
        '\x1b[47m', // white,
    ];

    var index  = (Math.random() * bears.length) | 0;
    var pad    = '                    ';
    var bear   = _inject(bears[index][0], pad, bears[index][1]);
    
    // @info color coding the console.log (https://stackoverflow.com/questions/9781218/how-to-change-node-jss-console-font-color)
    var bg    = '\x1b[47m';
    var fg    = '\x1b[37m';
    var reset = '\x1b[0m';

    // for(var i in bears) console.log('\x1b[43m\x1b[30m%s\x1b[0m', _inject(bears[i][0], pad, bears[i][1]));
    
    console.log("\n");
    console.log(`${bg}%s${reset}`, pad);
    console.log(`${bg}%s${reset}   ${fg}%s${reset}`, bear, 'ffto/grunt v'+version);
    console.log(`${bg}%s${reset}`, pad);
    console.log("");

    var shortcuts = {
        '--core'    : 'Setting the relative path (with a number) of the core "ffto.grunt.js" path. The path can be also set in a ".env" instead.',
        '--browser' : 'Start browser sync',
        '--prod'    : 'Uglify JS and CSS files (saving space)',
    };
    for (var i in shortcuts){
        var c = _inject('grunt '+i, '                       ', false, 'left');
        var a = shortcuts[i];
        console.log('\x1b[2m'+c+a+'\x1b[0m');
    }
    console.log("\n");


    // TODO
    // [ ] Add info here, eg.: Is Wordpress site, is only local parsing, ...
}

// core ------------------------------------------------------------------------
core.nodes = function (objects){
	nodes = {...nodes, ...objects};
	return this;
};

core.config = function (v, update){
	config = _merge(config, v);
    
    // try updating the root files
    if (update){
        var relative = parseInt(nodes.grunt.option('core') || 0);
        var path     = config.core_path || (relative ? _repeat('../',relative)+'ffto.grunt.js' : null) || (config.wp ? '../../../../../_core/ffto.grunt.js' : '../../../_core/ffto.grunt.js');
        var dir      = ((typeof update === 'string') ? update : __dirname) + '/';
        var fullpath = path[0] !== '/' ? dir + path : path;

        try{
            const fs  = require('fs');
            const alt = require(fullpath);
            
            // update the current local "ffto.grunt" with this live one
            fs.copyFile(fullpath, dir + 'ffto.grunt.js', function (err){
                err && _warn('Copying the "ffto.core" failed', err);
            });
            
            _log('"ffto.grunt.js" has been copied to the local project folder.');

            // tell to use the newly imported code
            return alt.nodes(nodes).config(config);
        }catch(e){
            config.import = false;
            _warn('The core ffto.grunt.js couldn\'t be found, the local version will be used. Try using "grunt --core=?" with a number. Or set the "core_path" in the ".env" file.', 'Path:', fullpath);
        };
    }

    // alias
    config.theme      = config.theme || './';
    config.style.dist = config.style.dist || (config.wp ? '@theme' : '@style/dist/');

    alias['@theme'] = config.theme;
    alias['@style'] = `${config.theme}assets/style/`;
    alias['@js']    = `${config.theme}assets/js/`;
    alias['@php']   = `${config.theme}assets/php/`;

    alias = _alias(alias, config.style, 'style', config.style.version);
    alias = _alias(alias, config.php, 'php', config.php.version);
    alias = _alias(alias, config.js, 'js', config.js.version);

	return this;
};

core.update = function (type, key, data){
    if (typeof(key) === 'object'){
        data = key;
        key  = null;
    }

    var item = grunt[type] = grunt[type] || {};
    
    // sub-item
    if (key){
        item = item[key] = _merge(item[key] || {}, data);
    }else{
        item = grunt[type] = _merge(item || {}, data);
    }

    return this;
};

core.replace = function (ext, callback){
    var items = replace[ext] = replace[ext] || [];

    if (callback === ':comments'){
        callback = function (v){
            if (ext === '.scss'){
                return v.toString()
                    .replace(/\/\*[^*][\s|\S]+?\*\//gm, '');    // multiline comment    
            }

            return v.toString()
                .replace(/\/\/.+\n/gm, '')                  // normal comments
                .replace(/\/\*[^*][\s|\S]+?\*\//gm, '');    // multiline comment
        };
    }else if (callback === ':clean'){
        callback = function (v){
            return v.toString()
                .replace(/ffto\_/g, '')
                .replace(/ffto\//g, '')
                .replace(/FFTO\_/g, '')
                .replace(/\[FFTO.*\]/g, '');
        };
    // }else if (callback === ':itdd'){
    //     callback = function (v){
    //         return v.toString()
    //             .replace(/itdd\_/g, '')
    //             .replace(/ITDD\_/g, '')
    //             .replace(/\[ITDD.*\]/g, '');
    //     };
    }else if (callback === ':h9'){
        callback = function (v){
            return v.toString()
                .replace(/ffto\_/g, 'h9_')
                .replace(/ffto\//g, 'h9/')
                .replace(/FFTO\_/g, 'H9_')
                .replace(/\[FFTO(.*)\]/g, '[H9$1]');
        };
    }

    items.push(callback);

    return this;
};

core.rename = function (ext, callback){
    var items = rename[ext] = rename[ext] || [];

    if (callback === ':version'){
        callback = function (dest, src){
            return dest + src.replace(/[-_\.]v\d+\./, '.');
        };
    }

    items.push(callback);

    return this;
};

core.remove = function (paths, key){
    paths = _resolve(null, paths);
    
    var all    = grunt.clean = grunt.clean || {options:{}};
    var remove = all[key]  = {
        src : paths,
    };
};

core.concat = function (files, to, args, key){
    args = _assets('concat', files, to, args, key, true);

    var all    = grunt.concat = grunt.concat || {options:{stripBanners:true}};
    var to     = _ext(args.to, args.ext);
    var concat = all[args.key] = {
        src    : args.files,
        dest   : to,
        options: {},
    };

    if (args.replace){
        concat.options.process = args.replace;
    }    

	return this;
};

core.copy = function (files, to, args, key){
    if (args === true){
        args = {'flatten':true};
    }
    if (to && typeof(to) === 'object'){
        key  = args;
        args = to;
        to   = '';
    }

    // fix folder/rename
    var info = to ? _fileinfo(to || '') : '';
    to = info ? info.folder : '';
    
	args         = _assets('copy', files, to, args, key);
    args.expand  = 'expand' in args ? args.expand : true;
    args.flatten = 'flatten' in args ? args.flatten : false;
    args.rename  = args.rename ? args.rename : (info.name ? info.basename : false);

    var all  = grunt.copy = grunt.copy || {options:{}};
    var copy = all[args.key] = {
        src    : args.files,
        cwd    : args.from,
        dest   : args.to,
        flatten: args.flatten,
        expand : args.expand,
        options: {},
    };

    if (args.replace){
        copy.options.process = args.replace;
    }    

    // rename function
    if (args.rename && typeof(args.rename) === 'function'){
        copy.rename = args.rename;
    // specific name to use
    }else if (args.rename){
        copy.rename = function (dest, src){ return dest + args.rename; }
    }
    
	return this;
};

core.css = function (from, to, args, watch){
    // shortcuts
    if (typeof alias[from] === 'function'){
        alias[from]();
        return this;
    }
    
    // find one/many file(s) and output it right beside
    // eg.: core.css('@core/theme/**/*.scss', true);
    if (to === true){
        var path  = _resolve(null, from);
        var files = nodes.grunt.file.expand(path);
        var a     = {...args, ...{resolve:false}};  
        files.forEach(function (f){
            var t = f.replace('.scss', '.css');
            core.css(f, t, a);
        });
        return;
    }

    if (args in alias){
        args = alias[args];
    }
    if (typeof(args) === 'string'){
        args = {'key':args};
    }else if (args === false){
        args = {'map':false};
    }

    args         = args || {};
    args.key     = args.key || 'default';
    args.map     = 'map' in args ? args.map : true;
    args.expand  = 'expand' in args ? args.expand : false;
    args.resolve = 'resolve' in args ? args.resolve : true;
    args.output  = args.output || null;
        
    if (args.resolve){
        to   = args.expand ? to || from : _resolve('@style.dist', to || from, '.css')[0];
        from = args.expand ? from : _resolve('@style.src', from)[0];
    }
    
    var file = {};
    var sass = grunt.sass = grunt.sass || {
        options:{
            implementation      : nodes.sass,
            outputStyle         : config.sass,
            sourceMap           : true,
            api                 : 'modern-compiler',
            // sass_embedded_legacy: false,
            // quiet         : true,
            // quietDeps     : true,
            // verbose       : false,
            silenceDeprecations: [
                'legacy-js-api', 
                'import'
            ],
    }};

    // TODO "import" issue https://stefaniefluin.medium.com/the-new-sass-module-system-out-with-import-in-with-use-e1bd8ba032d0

    var item = sass[args.key] = sass[args.key] || {files:{}, options:{}};
    
    if (args.expand){
        item.files = [{
            'expand': true,
            'cwd'   : _resolve(args.from)[0],
            'dest'  : _resolve(args.to)[0],
            'src'   : _resolve('', from, '/**/*.scss'),
            'ext'   : '.css',
        }];
        // TODO deal with the WATCH for those "expand" 
    }else{
        file       = {[to]:from};
        item.files = _merge(item.files, file);
    }

    item.options.sourceMap = args.map;
    args.output && (item.options.outputStyle = args.output);
    
    // sass watching
    var k = 'sass:'+args.key;
    var w = watching[k] = watching[k] || {'files':null, 'tasks':[], options:{livereload:false}};

    // all sub-folders (for the default sass key)
    if (w.files === null && args.key === 'default'){
        w.files = [];
        w.files = _resolve('@style.src', ['*/**/*.scss', '*/**/*.css']);
    }else if (w.files === null){
        w.files = [];
    }

    w.files.push(from);

    // add more files to watch
    if (watch){
        watch   = _resolve('@style.src', watch, '.scss');
        w.files = w.files.concat(watch);
    }

    // add the task (only if not Already in the tasks)
    if (!~w.tasks.indexOf(k)){
        w.tasks.push(k);
    }

    watching[k] = w;

    // css watching
    var w = watching.css = watching.css || {files:[], options:{livereload:true}};
    w.files.push(to);
    
    // console.log(JSON.stringify(watching, null, 4));
    // console.log(JSON.stringify(item, null, 4));
    // _log(JSON.stringify(watching));

	return this;
};

core.js = function (from, to, args){
    // shortcuts
    if (typeof alias[from] === 'function'){
        alias[from]();
        return this;
    }

    if (args in alias){
        args = alias[args];
    }else if (to === false || (typeof(to) === 'string' && to[0] === ':')){
        args = to;
        to   = '';
    }
    
    if (typeof(args) === 'string' && [':amd', ':cjs', ':es', ':iife', ':umd', ':system'].indexOf(args)){
        args = {'format':args.slice(1)};
    }else if (typeof(args) === 'string'){
        args = {'key':args};
    }else if (args === false){
        args = {'process':false};
    }

    args         = args || {};
    args.key     = args.key || 'files';
    args.format  = args.format || 'cjs';
    args.process = 'process' in args ? args.process : true;
        
    var key  = args.key;

    // rollup ------------------------------------------------------------------
    if (args.process){
        to   = to || from;
        from = _resolve('@js.src', from)[0];

        // make sure the file has a full name
        var info = _fileinfo(to);
        if (!info.name){
            info = _fileinfo(from);
            to   = to + info.name;
        }

        to = _resolve('@js.dist', to, '.js')[0];

        // var file = {[to]:from};
        var all = grunt.rollup = grunt.rollup || {
            options : {
                format : args.format,   // amd, cjs, es, iife, umd, system
                plugins : function (){ 
                    var list = [];

                    if (nodes.resolve)      list.push(nodes.resolve.nodeResolve());
                    if (nodes.typescript)   list.push(nodes.typescript());

                    // add more plugins
                    if (core._plugins instanceof Array){
                        list = list.concat(core._plugins);
                    }

                    if (nodes.babel) list.push(nodes.babel({
                        babelHelpers: 'bundled',
                        exclude     : './node_modules/**',
                        presets     : ['@babel/preset-env'],
                    }));

                    return list; 
                }
            }
        };

        var item  = all[key] = all[key] || {};
        item.src  = from;
        item.dest = to;


        var wkey = 'rollup.' + key;
        var tkey = 'rollup:' + key;
        var list = watching[wkey] = watching[wkey] || {files:[], tasks:[], options:{}};
        list.files = list.files.concat(from);
        list.tasks = list.tasks.concat(tkey);        
    }else{
        core.copy(from, to, {
            'from': '@js.src/',
            'to'  : '@js.dist/',
        }, 'js.test-file');
    }
};

core.load = function (key, args, watch){
    if (args === false){
        args = {'process':false};
    }
    
    args         = args || {};
    args         = {...args};
    args.rename  = args.rename || null;
    args.replace = args.replace || null;
    
    if (key === ':style'){
        args = _merge(args, {
            'from' : '@style.core/',
            'to'   : '@style.src/',
            'ext'  : '.scss',
            'watch': {options:{livereload:false}},
        });

        if (config.import){
            // utils -----------------------------------------------------------
            var files = _arr('_vars.scss, _functions.scss, vars.scss, functions.scss, utils.scss, utils/*, modules/debug.scss');
            if (args.files){
                files = _arr(args.files, files);
            }

            // get the list of "utils" scss files
            core.concat(files, '_/utils', args, 'sass.core.utils');

            // html ------------------------------------------------------------
            // get the list of "html" scss files
            var html = _arr('html/classes.scss, html/html.scss');
            if (config.style.version === 3){
                html = _arr('base/html.scss, base/classes.scss');
            }

            if (args.html){
                html = _arr(args.html, html);
            }
            core.concat(html, '_/html', args, 'sass.core.html');

            // copy ------------------------------------------------------------
            if (args.copy){
                core.copy(args.copy, '_/', {...args, ...{
                    'flatten': true,
                }}, 'sass.core.copy');
            }

            // wp --------------------------------------------------------------
            // load the "wp" helpers only if needed
            var helpers = _arr('modules/wp-base.scss, modules/wp-frontend.scss, modules/wp-backend.scss, modules/wp-editor.scss');
            if (config.style.version === 3){
                helpers = _arr('base/wp-frontend.scss, base/wp-backend.scss');
            }

            config.wp && core.copy(helpers, '_/', {...args, ...{
                'flatten': true,
            }}, 'sass.core.wp');

            // site ------------------------------------------------------------
            !config.wp && core.copy('modules/backend.scss', '_/', {...args, ...{
                'flatten': true,
            }}, 'sass.core.copy');
        }
    }else if (key === ':php'){
        args = _merge(args, {
            'from': '@php.core/',
            'to'  : '@php.src/',
            'ext' : '.php'
        });

        // core ----------------------------------------------------------------
        if (config.import){
            // clean the core folder
            core.remove('@php.src', 'php');

            var files = [];
            if (config.wp){
                core.copy('utils, wp', args, 'php.core');
                files = 'utils/**/*, wp/*'.split(',');
            }else{
                core.copy('utils, site', args, 'php.core');
                files = 'utils/**/*, modules/website*'.split(',');
            }
            
            // [ ] Try add comments for all files
            // fetch all the scss files and move them to the scss folder
            core.concat(files, '_/items', {
                'from' : '@php.core/',
                'to'   : '@style.src/',
                'ext'  : '.scss',
                'watch': {options:{livereload:false}},
            }, 'sass.php.items');

            // Adds all the JS from the PHP html items to the @js folder
            core.concat(files, '_items', {
                'from' : '@php.core/',
                'to'   : '@js.dist/',
                'ext'  : '.js',
            }, 'js.php.items');

            files = args.files ? files.concat(args.files) : files;
            core.copy(files, args, 'php.core.utils');
        }

        // wp plugins ----------------------------------------------------------
        if (config.import && config.wp && args.plugins){
            // the plugins aren't compatible with V3 (only v2)
            if (config.style.version === 2){
                core.css(args.plugins, '.', {...args, ...{
                    'key'   : 'sass.core.plugins',
                    'from'  : '@php.core/wp-plugins/',
                    'to'    : '@php.core/wp-plugins/',
                    'ext'   : '/**/*.scss',
                    'expand': true,
                }}, '@php.core/wp-plugins/**/*.scss');
            }else if (config.style.version === 3){
                _warn('WP-Plugins does not support Style:V3, their SASS files won\'t be processed, they still work.');
            }

            core.copy(args.plugins, '', {...args, ...{
                'from': '@php.core/wp-plugins/',
                'to'  : '@theme/../../plugins/',
                'ext' : '/**/*',
            }}, 'php.core.plugins');            
        }

        // watch ---------------------------------------------------------------
        var w = [
            '@php/theme.php', 
            '@php/theme/*.php', 
            '@php/models/*.php', 
            '@php/api/*.php', 
            '@php/apis/*.php', 
            '@php/theme/**/*.php', 
            '@php/api/*.php',
            '@theme/*.php',
            '@theme/models/**/*.php',
            '@theme/routes/**/*.php',
            '@theme/api/**/*.php',
            '@theme/acf-files/**/*.php',
            '@theme/admin-pages/**/*.php',
            '@theme/templates/**/*.php',
            '@theme/template-parts/**/*.php',
            '@theme/template-items/**/*.php',
            '@theme/template-blocks/**/*.php',
            '@theme/template-admin/**/*.php',
            '@theme/template-woo/**/*.php',
            '@theme/template-*/**/*.php',
            '@theme/woocommerce/**/*.php',
            '@theme/+*/**/*.php',            
        ];

        if (watch){
            watch = _arr(watch);
            w     = w.concat(watch);
        }

        core.watch(w, null, null, 'php.files');
    }else if (key === ':js'){
        args = _merge(args, {
            'from': '@js.core/',
            'to'  : '@js.dist/',
            'ext' : '.js'
        });

        if (args.plugins){
            core._plugins = args.plugins;
        }

        // core ----------------------------------------------------------------
        var files      = [];
        var components = args.components instanceof Array ? args.components : [];

        if (config.import){
            if (config.js.version === 1){
                files = _arr('utils, utils/[0-9]*, class, events, filters, states');
                
                // default files for components to work
                args.components && (files = _arr([
                    'utils/media', 
                    'mixins/fallback', 
                    'mixins/style', 
                    'mixins/element', 
                    'mixins/collection', 
                    'modules/props', 
                    'modules/query', 
                    'modules/browser', 
                    'component'
                ], files));
            }else{
                files = _arr('utils, utils/[0-9]*, website, class, classes/[0-9]*');
            }

            // prepend files
            if (args.prepend){
                files = _arr(args.prepend).concat(files);
            }
            
            files = args.files ? files.concat(args.files) : files;

            core.concat(files, '_.js', args, 'js.core');
        }

        // components ----------------------------------------------------------
        config.import && components.length && core.copy(components, 'components/', {
            'from' : '@js.core/components/',
            'to'   : '@js.dist/',
            'ext'  : '.js',
        }, 'js.core.components');

        // local components
        core.copy('**/*.js', 'components/', {
            'from' : '@js.src/components/',
            'to'   : '@js.dist/',
            'ext'  : '.js',
        }, 'js.core.local-components');
        
        // watch ---------------------------------------------------------------
        // @fix when using v2 JS files also
        core.watch([
            '@js.core/../v2/**/*.js',
        ], ['concat:js.core', 'concat:js.core.app'], null, 'TEST');
        
        // @info this causes a bug on the main JS files at the root. Techinically, it's not necessary, since reloading JS files without specifing they need to be processed isn't a good idea
        // core.watch([
        //     '@js.dist/_.js', 
        // ], null, null, 'js.files');

        // core.watch([
        //     '@js.src/*/**/*.js', 
        // ], null, null, 'js.files');
    }

    return this;
}

core.watch = function (files, tasks, args, key){
    if (args === false){
        args = {'reload':false};
    }else if (typeof(args) === 'string'){
        args = {'key':args};
    }

    args        = args || {};
    args.key    = args.key || key || 'default';
    args.reload = 'reload' in args ? args.reload : true;
    
    var all = grunt.watch = grunt.watch || {options:{livereload:args.reload, debounceDelay:10}};
    var key = args.key;

    if (files){
        var w = all[key] = all[key] || {files:[], tasks:[]};

        if (files){
            files   = _resolve(null, files);
            w.files = w.files.concat(files);
        }
        if (tasks){
            w.tasks = w.tasks.concat(tasks);
        }

        all[key] = w;
    }

    // add the auto watching
    grunt.watch    = _merge(grunt.watch, watching);
    watching       = {};
    
    return this;
};

core.start = function (args){
    if (args === true){
        args = {'browser':true};
    }

    _brand();

    args         = args || {};
    args.tasks   = args.tasks || null;
    // args.debug   = args.debug || false;
    args.debug   = 'debug' in args ? args.debug : (nodes.grunt.option('debug') !== undefined);
    args.browser = 'browser' in args ? args.browser : (nodes.grunt.option('browser') !== undefined);

    // uglify / minify
    if (nodes.grunt.option('prod') !== undefined){
        // sass / css
        var sass    = grunt.sass = grunt.sass || {};
        var options = sass.options = sass.options || {};
       
        options.outputStyle = 'compressed';
        
        // js
        var dist   = alias['@js.dist'];
        var source = _resolve('@js.dist', 'source.map')[0];
        grunt.uglify = {
            options: {
                mangle       : false,
                compress     : false,
                sourceMap    : true
            },
            js : {
                files  : [{expand:true, cwd:dist, dest:dist, src:'**/*.js'}],
                options: {sourceMapName:source}
            },
        };
    }

    // make sure all the auto-watch are added
    core.watch();

    if (args.browser && config.url){
        var files = [
            `${config.theme}**/*.php`,
            `${config.theme}**/*.(js|css)`,
        ];

        grunt.browserSync = {
            files : {
                src : files,
            },
            options : {     
                // https    : true,
                proxy    : config.url,
                // watch    : true,
                watchTask: true,
                ghostMode: false,
                port : 8080,
                // server: {
                //     baseDir: './'
                // },        
            }
        }
    }

    // debug
    if (args.debug){
        console.log(JSON.stringify(grunt, null, 4));
        return;
    }

    all   = Object.keys(grunt);
    tasks = (args.tasks || ['browserSync', 'clean', 'concat', 'copy', 'sass', 'rollup', 'uglify', 'watch']);
    tasks = tasks.filter(function (v, i){ return !!~all.indexOf(v); });
    
    nodes.grunt.initConfig(grunt);
    nodes.grunt.registerTask('default', tasks);

    return this;
};

module.exports = core;