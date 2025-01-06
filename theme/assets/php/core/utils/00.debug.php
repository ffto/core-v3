<?php 
/**
 * @package Utils\Debug
 */

// [ ] add a ERROR default page 

/* =====================================================================================================================
Quick
===================================================================================================================== */
/**
 * Add theme values, would be outputted in the Javascript console.
 *
 * @param string $key
 * @param mixed $value
 * @param string $group
 * @return array
 */
function _theme ($key=null, $value=null, $group=null){
	$meta = _global('$theme_meta', []);
	if (!$key) return $meta;

	if (is_array($key)){
		$values = $key;
		$group  = $value;
	}else{
		$values = [$key=>$value];
	}

	$group = $group ? $group : 10;
	foreach ($values as $i => $v){
		$meta[$group]     = isset($meta[$group]) ? $meta[$group] : [];
		$meta[$group][$i] = $v;
	}

	_global(['$theme_meta'=>$meta]);

	return $meta;
}

function _warn (){
	if (!ffto_is_dev()) return;
	$args = func_get_args();
	$args = array_merge([':warn'], $args);
	_log('js', $args, 2);
}

/* =====================================================================================================================
Functions
===================================================================================================================== */
/**
 * Set a debug value.
 *
 * @param string $key
 * @param mixed $value
 * @return void
 */
function ffto_set_debug ($key, $value=null){
	if (is_array($key)){
		foreach ($key as $i => $v){
			ffto_set_debug($i, $v);
		}
		return;
	}

	if (is_bool($key)){
		$value = $key;
		$key   = '*';
	}

	$key = trim(strtolower($key));
	$key = '$debug_modes/'.$key;
	_global($key, !!$value, true);
}

/**
 * Call a function many times and return the time/memory used
 *
 * @param mixed $callback 
 * @param int $count 
 * @return void
 */
function ffto_test ($callback, $count=1){
	$key = _random('test_');
	_performance($key);

	$count = $count < 1 ? 1 : $count;
	for ($i=0; $i<$count; ++$i){
		_call($callback);
	}
	
	$performance = _performance($key);
	$text 		 = "{$performance['text']} x{$count}";

	_log([
		'view'  => 'screen',
		'type'  => 'TESTING FUNCTION',
		'color' => '#0c28af',
		'trace' => false,
		'wrap'  => false,                // screen: do now style the wrapper
		// 'minimize' => false,     // js: don't minimize the output
	], $text);
}

/* =====================================================================================================================
Conditions
===================================================================================================================== */
/**
 * Check if a debug mode is true
 * @param string $key 
 * @return bool
 */
function ffto_is_debug ($key=null){
	$key = $key ? $key : '*';
	$key = trim(strtolower($key));
	$key = '$debug_modes/'.$key;
	return _global($key, false);
}

/* =====================================================================================================================
Logs
===================================================================================================================== */

/* =====================================================================================================================
Hooks: Init
===================================================================================================================== */
add_action('site/init', function ($set){
	$key = _get($set, 'debug_key'); // [true OR string] if string, check in the GET
	ffto_config_debug_modes($key);
});

/**
 * Get the debug changes in the $_REQUEST and set them in the $_SESSION
 *
 * @param string $key
 * @return void
 */
function ffto_config_debug_modes ($key=null){
	$key = $key ? $key : 'debug';
	if (!$key) return;

	!session_id() && session_start();
	
	$k       = '$debug';
	$session = isset($_SESSION[$k]) ? $_SESSION[$k] : [];

	// update the debug values
	if (isset($_REQUEST[$key])){
		$values = isset($_REQUEST[$key]) ? $_REQUEST[$key] : [];
		$values = is_array($values) ? $values : ['*'=>$values];
		
		$session = array_merge($session, $values);
		$session = array_filter($session);

		$_SESSION[$k] = $session;
	}

	ffto_set_debug($session);
}

/* =====================================================================================================================
Hooks: Startup
===================================================================================================================== */
add_action('site/startup', function (){
	_time('__PHP__');
	//ffto_startup_javascript(); // TODO this will be move the the <head> at some point
});

/**
 * Add default log Javascript
 *
 * @return void
 */
function ffto_startup_javascript (){
	if (_did('ffto_startup_javascript', true)) return;

	?>
	<script>(function ($win){
		$win.START = '___START___';
		$win.STOP  = '___STOP___';
	
		$win._backtrace = function (index, only_debug){
			var host   = window.location.protocol + '//' + window.location.hostname;
			var script = '';
	
			// need to deal with min
			var min = 0;
			var max = 0;
	
			if (Array.isArray(index)){
				min = index[0];
				max = index[1];
			}
	
			var trace = (new Error()).stack.split("\n").map(function (v){
				var v = v.match(/^(?:\s*at)?(.*?)?\@?(?:\(?(http.+))(?:\:(\d+))(?:\:(\d+))/);
				if (!v) return;
		
				var path  = (v[2] || '').trim().replace(script, '/').replace(host, '/').replace(/[?#].+$/, '').replace(/\/+/, '\/').replace('/http', 'http');
				var fn    = (v[1] || '').trim().replace('@', '');
				var line  = v[3];
				var iframe = window.parent !== window;
				var debug = path + (fn?' '+fn+'()':'') + (iframe?' [iframe]':'') + ' [line ' + line + ']';
	
				return only_debug ? debug : {
					'path'    : path,
					'line'    : line,
					'function': fn,
					'args'    : null,
					'iframe'  : iframe,
					'debug'   : debug,
				};
			}).filter(function (v){ return !!v; });
		
			if (min){
				trace = trace.slice(min);
			}
	
			return typeof index === 'number' ? trace[index] : trace;
		};
	
		$win._log = function (args, params, trace_start, count){
			if (params === undefined){
				params = args;
				args   = {};
			}
	
			// make sure the params is an array
			if (typeof params === 'object' && Symbol.iterator in params){
				params = Array.prototype.slice.call(params);
			}else{
				params = Array.isArray(params) ? params : [params];
			}
	
			args       = args || {};
			args.trace = 'trace' in args ? args.trace : true;
			args.type  = 'type' in args ? args.type : 'LOG';
			args.title = 'title' in args ? args.title : true;
			args.color = 'color' in args ? args.color : 'black';
	
			// styles
			var style = typeof params[0] === 'string' && params[0][0] === ':' ? params[0] : '';
			if (~[':info',':warn',':error',':deprecated',':trace'].indexOf(style)){
				params.shift();
				
				if (style === ':info'){
					args.type  = 'INFO';
					args.color = '#3e9d00';
				}else if (style === ':warn'){
					args.type  = 'WARN';
					args.color = '#f58303';
				}else if (style === ':error'){
					args.type  = 'ERROR';
					args.color = '#FF0000';
				}else if (style === ':deprecated'){ 
					args.type  = 'DEPRECATED';
					args.color = '#6534AF';
				}else if (style === ':trace'){
					// TODO
					args.trace = _backtrace([trace_start, 0], true).join("\n");
				}
			}
	
			// [ ] once only
			// [ ] extra styles (:warn, :error, ...)
			var trace_start = trace_start ? trace_start : 2;
			var trace       = args.trace === true ? _backtrace(trace_start, true) : args.trace;
			var type        = args.type;
			
			// find the title
			var title  = null;
			if (args.title === true && params.length === 1){
				title = typeof params[0] !== 'object' ? params.shift() : '';
			}else if (args.title === true){
				title = typeof params[0] === 'string' ? params.shift() : '';
			}else if (typeof args.title === 'string'){
				title = args.title;
			}
	
			// msg -----------------------------------------------------------------
			var msg    = [];
			var styles = [];
	
			if (trace && count > 1){
				trace = trace + ' x' + count;
			}
			if (trace){
				msg.push('%c'+trace+'\n');
				styles.push('font-size:10px; color:#999; margin-bottom:3px;');
			}
			if (type){
				msg.push('%c'+type);
				styles.push('background:'+args.color+'; color:white; border-radius:2px; font-weight:bold; font-size:10px; padding:.25em .7em .3em; margin-right:5px;');
			}
			if (title){
				msg.push('%c'+title);
				styles.push('color:'+args.color+'; margin-top:.25em; font-weight:bold;');
			}
	
			if (msg.length){
				msg = msg.join('').trim();
				msg = [msg].concat(styles);
				console.log.apply(null, msg);
			}
	
			params && console.log.apply(null, params);        
		};
	
		$win._js = function (){
			_log(null, arguments, 3);
		};
	
		$win._debug = function (){
			// TODO
		};
	
		$win._theme = function (meta){
			var bears = ['ᕦʕ •ᴥ•ʔᕤ', 'ʕ ㅇ ᴥ ㅇʔ', 'ʕง•ᴥ•ʔง', 'ʕ•ᴥ•ʔﾉ♡', 'ʕ ꈍᴥꈍʔ', 'ʕ – ᴥ – ʔ', 'ʕノ•ᴥ•ʔノ ︵ ┻━┻'];
			var index = (Math.random() * bears.length) | 0;
			var bear  = bears[index];

			var $_meta = [];
			for (var i in meta){
				var v = meta[i];
	
				if ($_meta.length){
					$_meta.push('· · · · · · ·');
				}
	
				for (var ii in v){
					var key  = ii.toString().padEnd(20, ' ');
					var text = v[ii];
					$_meta.push(key+' '+text);
				}
			}
			$_meta = $_meta.join("\n");
			
			if ($_meta){
				console.log('%c'+bear+'%c\n'+$_meta, 'color:black; font-weight:bold; font-size:20px; margin-top:0.25em; margin-bottom:0.25em;', 'color:gray;');
			}
		};
	}(window));</script>
	<?php 
}

/* =====================================================================================================================
Hooks: Shutdown
===================================================================================================================== */
add_action('site/shutdown', function (){
	ffto_shutdown_theme_meta();
	ffto_shutdown_catch_error();
	ffto_shutdown_logs();
});

/**
 * Output the theme information meta data
 */
function ffto_shutdown_theme_meta (){
	ffto_startup_javascript(); // make sure the JS is there

	$debug = _global('$debug_modes');
	$debug = _each($debug, function ($v, $i){ return $v ? $i : false; }, ', ');

	_theme([
		'⸫ Server IP'	=> isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '?',
		'⸪ Client IP'	=> ffto_to_ip() . (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? ' (cloudflare)' : ''),
		'⊡ PHP'			=> 'v'.phpversion(),
		'⊡ PHP API'		=> 'v'.API_VERSION,
		// '⊡ JS API'		=> '',		
	], 1);

	global $ffto_memory_start;
	$memory = memory_get_peak_usage(); // - $ffto_memory_start;

	_theme([
		'✎ Theme'       => _config('version'),
		'⚑ Domain'      => _config('domain'),
		'☖ Environment' => _config('env'),
		'◷ Time'        => _time('__PHP__'),
		'⌘ Memory'      => ffto_to_human_bytes($memory, 2),
		'⌁ Debugging'   => $debug ? $debug : '- none -',
	], 2);

	$meta = _theme();
	$meta = json_encode($meta);
	echo '<script>window._theme && _theme('.$meta.')</script>';
}

/**
 * Add the last php error to the JS logs
 */
function ffto_shutdown_catch_error (){
	if (!($e = error_get_last())) return;

	// Fatal error
	$codes = [
		-1					=> 'E_FATAL_ERROR',
		E_ERROR             => 'E_ERROR',               // 1
			// E_WARNING           => 'E_WARNING',             // 2
			// E_PARSE             => 'E_PARSE',               // 4
			// E_NOTICE            => 'E_NOTICE',              // 8
		E_CORE_ERROR        => 'E_CORE_ERROR',          // 16
		E_CORE_WARNING      => 'E_CORE_WARNING',        // 32
		E_COMPILE_ERROR     => 'E_COMPILE_ERROR',       // 64
		E_COMPILE_WARNING   => 'E_COMPILE_WARNING',     // 128
		E_USER_ERROR        => 'E_USER_ERROR',          // 256
		E_USER_WARNING      => 'E_USER_WARNING',        // 512
		E_USER_NOTICE       => 'E_USER_NOTICE',         // 1024
		E_STRICT            => 'E_STRICT',              // 2048
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',   // 4096
			// E_DEPRECATED        => 'E_DEPRECATED',          // 8192
		E_USER_DEPRECATED   => 'E_USER_DEPRECATED',     // 16384
	];

	$code = _get($codes, $e['type']);
	if (!$code) return;
	
	// simplify the paths
	$abspath = defined('ABSPATH') ? ABSPATH : '';
	$file 	 = $abspath ? str_replace($abspath, '', $e['file']) : $e['file'];
	$msg     = $abspath ? str_replace($abspath, '', $e['message']) : $e['message'];
	$params  = explode('Stack trace:', $msg);
	$params  = array_map('trim', $params);

	_log([
		'view'  => 'js',
		'type'  => $code,
		'trace' => $file,
		'color' => 'red',
	], $params);
}

/**
 * Output all the JS logs added with `_js()`
 */
function ffto_shutdown_logs (){
	ffto_startup_javascript(); // make sure the JS is there

	$logs  = _global('js_logs/*/items', []);
	$_logs = [];
	foreach ($logs as $v){
		$a       = json_encode($v['args']);
		$c       = $v['count'];
		$p       = $v['params'];
		$_logs[] = "_log({$a}, [{$p}], null, {$c});";
	}

	echo '<script>if (window._log){ '.NL.implode(NL,$_logs).NL.'}else{ console.log("The _log() function doesn\'t exists"); };</script>';
}

/*
set_error_handler(function ($e){
	$trace = $e->getTraceAsString();
	$trace = str_replace(ABSPATH, '', $trace);

	echo '<div style="
		font-size:14px; 
		font-family:monospace; 
		color:black; 
		position:relative; 
		padding:3em;
		box-sizing:border-box;
		z-index:999;
		display:flex;
		flex-direction:column;
		align-items:flex-start;
		justify-content:center;
		height:100vh;
		width:100vw;
		position:fixed;
		top:0;
		left:0;
	">';
	
		echo '<div>';
			echo '<span style="
				background:black; 
				display:inline-block; 
				color:white; 
				border-radius:2px; 
				font-weight:bold !important; 
				font-size:0.8em; 
				margin-right:1em; 
				padding:.25em .7em .3em;
			">Error</span>';
			echo '<strong style="display:inline-block; position:relative; top:0.1em;">'.$e->getMessage().'</strong>';
		echo '</div>';

		echo '<div style="margin-top:0.5em; font-size:14px; opacity:0.5;">'.$trace.'</div>';
	echo '</div>';
});
*/