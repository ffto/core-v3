<?php 
/**
 * List of debug functions
 */

// [ ] add a ERROR default page 

/**
 * Set a debug value.
 *
 * @param string $key
 * @param mixed $value
 * @return void
 */
function set_debug ($key, $value=null){
	if (is_array($key)){
		foreach ($key as $i => $v){
			set_debug($i, $v);
		}
		return;
	}

	if (is_bool($key)){
		$value = $key;
		$key   = '*';
	}

	$key = trim(strtolower($key));
	$key = '$debug_modes.'.$key;
	_global($key, !!$value, true);
}

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

/* =====================================================================================================================
Conditions
===================================================================================================================== */
/**
 * Check if a debug mode is true
 * @param string $key 
 * @return bool
 */
function is_debug ($key=null){
	$key = $key ? $key : '*';
	$key = trim(strtolower($key));
	$key = '$debug_modes.'.$key;
	return _global($key, false);
}

/* =====================================================================================================================
Logs
===================================================================================================================== */
/**
 * Return backtrace value. Convert it to a readable "debug" for debug functions
 *
 * @param mixed $index 
 * @param bool $only_debug 
 * @return mixed
 */
function _backtrace ($index=null, $only_debug=false){
	$min = 0;
	$max = 0;

	if (is_array($index)){
		$min = $index[0];
		$max = $index[1];
	}

	$abspath = defined('ABSPATH') ? ABSPATH : '';
	$_trace  = debug_backtrace(0, $max);
	$trace   = [];
	foreach ($_trace as $i => $v){
		if ($i < $min || !isset($v['line'])) continue;

		$ii    = $i + 1;
		$fn    = isset($_trace[$ii]) ? $_trace[$ii]['function'] : '';
		$path  = str_replace($abspath, '', $v['file']);
		$args  = isset($_trace[$ii]) ? $_trace[$ii]['args'] : '';
		$line  = $v['line'];
		$debug = $path . ($fn?" {$fn}()" : '')." [line {$line}]";

		$trace[] = $only_debug ? $debug : [
			'path'     => $path,
			'line'     => $line,
			'function' => $fn,
			'args'     => $args,
			'debug'    => $debug,
		];
	}
	
	return is_numeric($index) ? $trace[$index] : $trace;
}

/**
 * Log a list of parameters to either screen, error, js or csv. Normally not used directly. Use these instead: p(), _p(), _err(), _js();
 *
 * ```php
 * _log(['Bob', 132]);
 * _log([':warn', 'Bob', 132]);
 * ```
 * 
 * @todo make the "screen" type to have a "full screen" version
 * @todo csv output
 * @param mixed $args 
 *      - 'type' [screen] Type of media to output to (screen, error, js, csv)
 *      - 'prefix' [LOG] Add a prefix pill before the output
 *      - 'title' [true] Specify a title OR use the first parameter (if possible) as the title
 *      - 'trace' [true] Add the trace paths
 *      - 'color' [#000] Set color for the prefix and title
 *      - 'style' [] Add css style (with screen and js)
 *      
 *      Screen:
 *      - 'wrap' [true] wrap the output in a box
 *      
 *      Js:
 *      - 'group' [*] Add the logs to a specific group (with js)
 *      - 'minimize [true] Minimize the output by grouping together the same logs
 * @param mixed $params List of arguments/params that will be outputed
 * @return void
 */
function _log ($args, $params=null, $trace_start=1){
	if (func_num_args() === 1){
		$params = $args;
		$args   = [];
	}

	$params = is_array($params) ? $params : [$params];
	if (empty($params)) return;

	$args = _args($args, [
		'type'   => true,
		'prefix' => 'LOG',
		'title'  => true, 
		'trace'  => true,
		'color'  => '#000',
	], 'type');

	// pre-defined styles
	$style = is_string($params[0]) && $params[0][0] === ':' ? $params[0] : '';
	$icon  = null;
	if (in_array($style, [':info',':warn',':error',':deprecated',':trace'])){
		array_shift($params);

		if ($style === ':info'){
			$args['prefix'] = 'INFO';
			$args['color']  = '#3e9d00';
			$icon           = 'ℹ️';
		}else if ($style === ':warn'){
			$args['prefix'] = 'WARN';
			$args['color']  = '#f58303';
			$icon           = '⚠️';
		}else if ($style === ':error'){
			$args['prefix'] = 'ERROR';
			$args['color']  = '#FF0000';
			$icon           = '❌';
		}else if ($style === ':deprecated'){
			$args['prefix'] = 'DEPRECATED';
			$args['color']  = '#6534AF';
		}else if ($style === ':trace'){
			$args['trace']  = _backtrace([$trace_start, 0], true);
			$args['trace']  = implode(NL, $args['trace']);
		}
	}

	// TODO alter the default `type` with a "_config()" property or something like that
	$type  = $args['type'] === true ? 'error' : $args['type'];
	$trace = $args['trace'] === true ? _backtrace($trace_start, true) : $args['trace'];
	$prefix= $args['prefix'];
	$color = $args['color'];
	
	// fetch the title from the params
	$title = null;
	if ($args['title'] === true && count($params) === 1){
		$title = !is_array($params[0]) && !is_object($params[0]) ? array_shift($params) : '';
	}else if ($args['title'] === true && isset($params[0])){
		$title = is_string($params[0]) ? array_shift($params) : '';
	}else if (is_string($args['title'])){
		$title = $args['title'];
	}

	// format the title 
	$title = is_string($title) ? $title : ($title === null ? 'null' : json_encode($title));
	
	if ($type === 'error'){
		$params = count($params) === 1 ? $params[0] : $params;

		// show the class of the object
		if (is_object($params)){
			$class  = get_class($params);
			$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$params = 'object('.$class.')' . $params;
		}else if (!empty($params)){
			$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}else{
			$params = '';
		}

		// simplify the output, so it's easy to copy/paste from the logs
		$params = strtr($params, array(
			'\\/' => '/',
			'\\"' => '"',
		));

		$msg = [];
		if ($trace) $msg[] = NL . NL .$trace . NL;

		// use the icon instead of the prefix        
		if ($icon){
			$prefix = $icon;
		}else if ($prefix){
			$prefix = "[{$prefix}]";
		}

		if ($prefix && is($title))  $msg[] = "{$prefix} {$title}" . NL;
		else if ($prefix)        	 $msg[] = "{$prefix}" . NL;
		else if (is($title))        $msg[] = "{$title}" . NL;

		if ($params){
			$msg[] = $params . NL;
		}

		$msg = implode(NL, $msg);
		$msg = preg_replace("/\n/", NL.TAB, $msg);
		
		error_log($msg);
	}else if ($type === 'screen'){
		$wrap  = _get($args, 'wrap', true);
		$style = _get($args, 'style', '');

		// get the $params from `var_dump()`
		if (count($params)){
			ob_start();
			// print_r(count($params) === 1 ? $params[0] : $params);
			var_dump(count($params) === 1 ? $params[0] : $params);
			$params = ob_get_clean();
		}else{
			$params = '';
		}

		echo '<pre style="
			font-size:14px; 
			font-family:monospace; 
			color:black; 
			'.($wrap?'
			box-shadow:inset 0 0 0 1px rgba(0, 0, 0, .3); 
			border-radius:3px; 
			background:#efefef; 
			max-width:100%; 
			text-align:left; 
			overflow:auto; 
			text-transform:none; 
			padding:1.2em; 
			margin:.5em; 
			':'').'
			position:relative; 
			z-index:999; '.
			$style.
		'">';
		
			$xml = ['<'=>'&lt;', '>'=>'&gt;'];
			
			if ($trace){
				echo '<div style="opacity:0.5; margin-bottom:0.5em;">'.$trace.'</div>';
			}
			if ($prefix){
				echo '<span style="
					background:'.$color.'; 
					display:inline-block; 
					color:white; 
					border-radius:2px; 
					font-weight:bold !important; 
					font-size:0.8em; 
					margin-right:1em; 
					padding:.25em .7em .3em;
				">'.$prefix.'</span>';
			}
			if (is($title)){
				$title  = strtr($title, $xml);
				echo '<strong style="display:inline-block; color:'.$color.';">'.$title.'</strong>';
			}
			if ($params){
				$params = strtr($params, $xml);
				echo '<div style="margin-top:0.5em;">'.$params.'</div>';
			}
		echo '</pre>';
	}else if ($type === 'js'){
		$group    = _get($args, 'group', '*');
		$minimize = _get($args, 'minimize', true);
		$key      = "js_logs.{$group}";
		$logs     = _global($key, ['last'=>null, 'items'=>[]]);

		// show the class of the object as the title
		$first = reset($params);
		if ($first && is_object($first) && !$title){
			$title = get_class($first);
			$title = 'object('.$title.')';
		}

		$params = json_encode($params);
		$params = preg_replace('/^\[|\]$/','', $params);

		$log = [
			'params' => $params,
			'count'  => 1,
			'args'   => [
				'trace'  => $trace,
				'prefix' => $prefix ? $prefix : "PHP",
				'title'  => $title,
				'color'  => $color,
			],
		];

		// minimize the output of logs (by grouping them)
		$log_key = $minimize ? md5(json_encode($trace . $title . $params)) : false;

		// same as last log, so augment the last log
		if ($log_key && $log_key === $logs['last']){
			$last = count($logs['items']) - 1;
			$logs['items'][$last]['count']++;
		}else if ($log){
			$logs['items'][] = $log;
		}

		$logs['last'] = $log_key;
		_global([$key => $logs]);
	}else if ($type === 'csv'){
		// TODO create a CSV version
	}else{
		// other types: a better JSON visualize, a table
	}
}

/**
 * Output log to the screen and kill the PHP process
 */
function p (){
	_log('screen', func_get_args(), 2);
	die();
}

/**
 * Output log to the screen
 */
function _p (){
	_log('screen', func_get_args(), 2);
}

/**
 * Output log to the Javascript console
 */
function _js (){
	// TODO only add if localhost
	_log('js', func_get_args(), 2);
}

/**
 * Output log to the error logs
 */
function _err (){
	// TODO only add if localhost
	// if (!is_dev()) return false;
	_log('error', func_get_args(), 2);
}

/**
 * Conditional debug logger. First, for the function to work, it needs to be started by calling `_debug(START)`, 
 * after that, the function can be called like the other log options. To stop the debugger, call `_debug(STOP)`.
 * 
 * Also, you can set the type of logs (javascript, screen or error logs) like this:
 * `_debug(START, 'js')`. By default it logs in the error logs.
 * 
 * ```php
 * _debug(START);
 * _debug(1, 5, 65);
 * _debug(STOP);
 * ```
 *
 * @param mixed $msg 
 * @param string $type 
 * @return void
 */
function _debug ($msg=null, $type='error'){
	// enable/disable this logging option
	if (in_array($msg, [START, STOP])){
		_debugging('logs', $type);
		return;
	}

	// it hasn't been turned on, so skip
	$debug = _debugging('logs');
	if (!$debug) return;

	_log([
		'prefix' => 'DEBUG',
		'type'   => $debug,
		'color'  => 'gray',
	], func_get_args(), 2);
}

/* =====================================================================================================================
Hooks: Init
===================================================================================================================== */
add_action('site-init', function ($set){
	$key = _get($set, 'debug_key'); // [true OR string] if string, check in the GET
	config_debug_modes($key);
});

/**
 * Get the debug changes in the $_REQUEST and set them in the $_SESSION
 *
 * @param string $key
 * @return void
 */
function config_debug_modes ($key=null){
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

	set_debug($session);
}

/* =====================================================================================================================
Hooks: Startup
===================================================================================================================== */
add_action('site-startup', function (){
	_time('__PHP__');

	//startup_javascript(); // TODO this will be move the the <head> at some point
});

/**
 * Add default log Javascript
 *
 * @return void
 */
function startup_javascript (){
	if (_did('startup_javascript', true)) return;

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
	
			args        = args || {};
			args.trace  = 'trace' in args ? args.trace : true;
			args.prefix = 'prefix' in args ? args.prefix : 'LOG';
			args.title  = 'title' in args ? args.title : true;
			args.color  = 'color' in args ? args.color : 'black';
	
			// styles
			var style = typeof params[0] === 'string' && params[0][0] === ':' ? params[0] : '';
			if (~[':info',':warn',':error',':deprecated',':trace'].indexOf(style)){
				params.shift();
				
				if (style === ':info'){
					args.prefix = 'INFO';
					args.color  = '#3e9d00';
				}else if (style === ':warn'){
					args.prefix = 'WARN';
					args.color  = '#f58303';
				}else if (style === ':error'){
					args.prefix = 'ERROR';
					args.color  = '#FF0000';
				}else if (style === ':deprecated'){ 
					args.prefix = 'DEPRECATED';
					args.color  = '#6534AF';
				}else if (style === ':trace'){
					// TODO
					args.trace = _backtrace([trace_start, 0], true).join("\n");
				}
			}
	
			// [ ] once only
			// [ ] extra styles (:warn, :error, ...)
			var trace_start = trace_start ? trace_start : 2;
			var trace       = args.trace === true ? _backtrace(trace_start, true) : args.trace;
			var prefix      = args.prefix;
			
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
			if (prefix){
				msg.push('%c'+prefix);
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
			var bears = [
				'         ᕦʕ •ᴥ•ʔᕤ        ',
				'        ʕ ㅇ ᴥ ㅇʔ         ',
				'          ʕง•ᴥ•ʔง         ',
				'          ʕ•ᴥ•ʔﾉ♡         ',
				'          ʕ ꈍᴥꈍʔ          ',
				'        ʕ – ᴥ – ʔ         ',
				'      ʕノ•ᴥ•ʔノ ︵ ┻━┻     ',
			];
				
			var index = (Math.random() * bears.length) | 0;
			var bear  = bears[index];
			console.log('%c'+bear, 'background:#FFFF55; color:black; border-radius:0; padding:2em 0; margin:2em 0 2em 0; text-align:center;');

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
				console.log('%c[ffto/core]%c\n'+$_meta, 'color:black; font-weight:bold; margin-top:2em; margin-bottom:3px;', 'color:gray;');
			}
		};
	}(window));</script>
	<?php 
}

/* =====================================================================================================================
Hooks: Shutdown
===================================================================================================================== */
add_action('site-shutdown', function (){
	shutdown_theme_meta();
	shutdown_catch_error();
	shutdown_logs();
});

/**
 * Output the theme information meta data
 */
function shutdown_theme_meta (){
	startup_javascript(); // make sure the JS is there

	$debug = _global('$debug_modes');
	$debug = _each($debug, function ($v, $i){ return $v ? $i : false; }, ', ');

	_theme([
		'⸫ Server IP'	=> isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '?',
		'⸪ Client IP'	=> to_ip() . (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? ' (cloudflare)' : ''),
		'⊡ PHP'			=> 'v'.phpversion(),
		'⊡ PHP API'		=> 'v'.API_VERSION,
		// '⊡ JS API'		=> '',		
	], 1);

	_theme([
		'✎ Theme'       => _config('version'),
		'⚑ Domain'      => _config('domain'),
		'☖ Environment' => _config('env'),
		'◷ Time'        => _time('__PHP__'),
		'⌘ Memory'      => to_human_bytes(memory_get_peak_usage(), 2),
		'⌁ Debugging'   => $debug ? $debug : '- none -',
	], 2);

	$meta = _theme();
	$meta = json_encode($meta);
	echo '<script>window._theme && _theme('.$meta.')</script>';
}

/**
 * Add the last php error to the JS logs
 */
function shutdown_catch_error (){
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
	$msg     = $abspath ? str_replace($abspath, '', $e['message']) : $e['message'];
	$params  = explode('Stack trace:', $msg);
	$params  = array_map('trim', $params);

	_log([
		'type'   => 'js',
		'prefix' => $code,
		'trace'	 => false,
		'color'  => 'red',
	], $params);
}

/**
 * Output all the JS logs added with `_js()`
 */
function shutdown_logs (){
	startup_javascript(); // make sure the JS is there

	$logs  = _global('js_logs.*.items', []);
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