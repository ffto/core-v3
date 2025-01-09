<?php
// [ ] add "robots.txt" code to block AI: https://www.foundmyself.com/blog/how-to-protect-your-art-from-ai/
// [-] use a new namespace tddl_ (the dev daddy library) 
// [ ] simplify all the function prefix, instead of "string_", it will be "str_"
// [ ] The site functions will be simplified, with using files for the routes mostly
// [ ] for WP, ffto_the_css() should be able to target a specific page (only `post.php?action=edit` for example)
// [ ] try using "filter" name for callabck functions instead of "format"
// [ ] add simple tracking (turn on/off), a CSV file, like a simple Analytics 
// [ ] maybe for translation items, using "_t" and "__t" will return an Object, so you know it's already translated, no need to translate again in case __t() is called again on something that's already translated
// [ ] versionning should be the timestamp of the generated CSS and JS
// [ ] for translations, use a Class that wrap a string, so it's not translated a million time, AND it can add a "wrapper" for click-to-translate option when in "admin" mode (have a way to turn it off in some cases)
// [ ] transform all css class to BEM type
// [ ] Add a "track access" globally with output code
// [ ] Add a "track server access" globally with output code


// Files migrated
// [ ]	classes
// [ ]	libraries
// [ ]	modules
// [ ]	utils
// [ ]		__dom.php
// [ ]		array.php
// [ ]		color.php
// [ ]		data.php
// [ ]		date.php
// [ ]		debug.php
// [ ]		dom.php
// [ ]		files.php
// [ ]		html-scripts.php
// [ ]		html.php
// [ ]		http.php
// [ ]		io.php
// [ ]		media_focus-js.php
// [ ]		media_player-js.php
// [ ]		media.php
// [ ]		number.php
// [x]		proxy.php
// [ ]		server.php
// [x]		string.php
// [ ]	vendor
// [ ]	wp
// [ ]	wp-class
// [ ]	wp-modules
// [ ]	wp-plugins
// [ ]	site.php
// [ ]	utils.php
// [ ]	wp.php

/* =====================================================================================================================
Quick Methods: Use on other websites as standalone
===================================================================================================================== */
/*
function __track__ ($key=null, $reset=false){
	global $__stats__, $__stats_logs__;

	$__stats__      = isset($__stats__) ? $__stats__ : [];
	$__stats_logs__ = isset($__stats_logs__) ? $__stats_logs__ : [];
	$key            = $key ? $key : '*';
	$real           = false;                                          // real = false is similar on LOCAL vs DEV server
	
	$time        = microtime(true);
	$memory      = memory_get_usage($real);
	$memory_peak = memory_get_peak_usage($real);
	if (!isset($__stats__[$key]) || $reset){
		$__stats__[$key] = ['time'=>$time, 'memory'=>$memory, 'memory_peak'=>$memory_peak, 'count'=>0];
	}else{
		$t  = $time - $__stats__[$key]['time'];
		$m  = $memory - $__stats__[$key]['memory'];
		$mp = $memory_peak - $__stats__[$key]['memory_peak'];
		$t  = number_format($t / 60 * 100, 2) . ' sec';
		$m  = number_format($m / 1024 / 1024, 2) . ' MB';
		$mp = number_format($mp / 1024 / 1024, 2) . ' MB';
		$k  = str_pad("%c{$key}%c ", 20, ' ');

		$__stats_logs__[] = "<script>console.log('{$k} {$t} / {$m} - peak:{$mp}', 'background:#0c58d0; color:white; border-radius:0.25em; padding:.25em 0.5em .25em; font-family:monospace; font-weight:bold;', 'color:#0c58d0; margin-top:.25em; font-weight:bold;')</script>";
		$__stats__[$key]['count']++;
	}

	$GLOBALS['__stats__']      = $__stats__;
	$GLOBALS['__stats_logs__'] = $__stats_logs__;
}
function __tracks__ (){
	global $__stats_logs__;
	foreach ($__stats_logs__ as $v) echo $v;
}
*/	

/**
 * Utils
 * 
 * List of helper functions used for building websites
 * 
 * @package Utils
 * @author 	Maxime Lefrancois
 * @version 3.0.0
 **/

/* =====================================================================================================================
Constants
===================================================================================================================== */
define('API_VERSION', '3.0.0');

/** Newline character constant */
define('NL', "\n"); 

/** Tab character constant */
define('TAB', "\t"); 

// For debugging 
define('START', '___START___');
define('STOP', '___STOP___');

/** Special key used in some functions */
define('KEY', '___KEY___'); 
define('BREAK_LOOP', '___BREAK___'); 
define('CONTINUE_LOOP', '___CONTINUE___'); 

/** Helping constants for time convertion */
if (!defined('MINUTE_IN_SECONDS')) 	define('MINUTE_IN_SECONDS', 60);
if (!defined('HOUR_IN_SECONDS')) 	define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
if (!defined('DAY_IN_SECONDS')) 	define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
if (!defined('WEEK_IN_SECONDS')) 	define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
if (!defined('MONTH_IN_SECONDS')) 	define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
if (!defined('YEAR_IN_SECONDS')) 	define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);

// =====================================================================================================================
// Validations/Conditions
// =====================================================================================================================
/**
 * Check if the value is valid (truthy or 0), not NULL/FALSE/''
 *
 * ```php
 * ffto_is(0);
 * // true
 * 
 * ffto_is('');
 * // false
 * ```
 * 
 * @param mixed $v
 * @return boolean
 */
function ffto_is ($v){
	return $v || $v === 0 || $v === 0.0 || $v === '0' || (is_array($v) && !empty($v));
}

/**
 * Check if the value is falsy
 *
 * @param mixed $v 
 * @return bool
 */
function ffto_is_not ($v){
	return !ffto_is($v);
}

/**
 * Check if the value is defined (false or truthy)
 *
 * @param mixed $v 
 * @return bool
 */
function ffto_is_defined ($v){
	return $v === false || ffto_is($v);
}

/**
 * Check if it's a valid function
 *
 * @param mixed $v 
 * @param bool  $only_closure 	Return true only if the $callback is a Closure
 * @return bool
 */
function ffto_is_callback ($v, $only_closure=true){
	return $v && (
		(!$only_closure && is_callable($v)) ||
		($only_closure && $v instanceof Closure)
	);
}

/**
 * Simple function for validating if a value is a string. A separate parameter helps matching the string (regexp OR in-string).
 * For a more complex "like", use the `ffto_is_like()` function.
 * 
 * @todo maybe add ffto_is_str() to check if it's a string OR something considered like a string (eg.: instance of class FFTO_Translation)
 *
 * @param [type] $v	
 * @param [type] $like	a simple match check by regexp OR in-string 
 * @return bool
 */
function ffto_is_str ($v, $like=null){
	if (!is_string($v)) return false;

	if (ffto_is_regexp($like)){
		return !!preg_match($like, $v);
	}else if (is_string($like)){
		return strpos(strtolower($v), strtolower($like)) !== false;
	}

	return true;
}

/**
 * Simple way of checking if a value can be an array $key (only string and numbers)
 *
 * @param [type] $v
 * @return bool
 */
function ffto_is_key ($v){
	return ffto_is($v) && (is_string($v) || is_numeric($v));
}

/**
 * Check if the value is a valid number.
 *
 * ```php
 * ffto_is_nbr(45)  // true
 * ffto_is_nbr('45.56') // true
 * ffto_is_nbr('00045') // false
 * ```
 * 
 * @param mixed $v 
 * @return bool
 */
function ffto_is_nbr ($v){
	if (!is_numeric($v)) return false;

	if (!is_string($v)){
		return true;
	// Validate if the string number is valid, without a "0" has the prefix (unless it's a float)
	}else if (is_string($v)){
		return strlen($v) == 1                        // 1 string
			|| strpos($v, '0.') === 0                 // float string
			|| (strlen($v) > 1 && ($v[0] !== '0'));   // number string that doesn't start with 0
	}else{
		return false;
	}
}

/**
 * Check if an array is a normal list of values, without string keys.
 *
 * @param mixed $v 
 * @param bool $match
 * @return bool
 */
function ffto_is_list ($v, $match=true){
	if (!is_array($v) || empty($v)) return false;

	if (function_exists('array_is_list')){
		return array_is_list($v) === $match;
	}
	
	// fallback code for checking if the array is a normal list
	$_list = true;
	foreach ($v as $ii => $vv){
		if (is_string($ii)){
			$_list = false;
			break;
		}
	}
	
	return $_list === $match;
}

/**
 * Check if an array is an object with string keys.
 *
 * @param mixed $v 
 * @return bool
 */
function ffto_is_obj ($v){
	return ffto_is_list($v, false);
}

/**
 * Return true if the value is true (true OR 1), when used in a string args
 *
 * @param mixed $v
 * @param boolean $true
 * @param boolean $false
 * @return boolean
 */
function ffto_is_truthy ($v, $true=true, $false=false){
	if ($v instanceof Closure){
		return $false;
	}
	return ($v === true || $v === 'true' || $v === 1 || $v === '1') ? $true : $false;
}

/**
 * Check if the value is falsy
 *
 * @param mixed 	$v
 * @param boolean 	$false
 * @param boolean 	$true
 * @return boolean
 */
function ffto_is_falsy ($v, $true=true, $false=false){
	if ($v instanceof Closure){
		return $false;
	}
	return ($v === false || $v === 'false' || $v === 0 || $v === '0') ? $true : $false;
}

/**
 * Check if the value is a RegExp
 *
 * @param mixed $value 
 * @return bool
 */
function ffto_is_regexp ($v){
	return is_string($v) && preg_match('/^\/.+\/(i|m|g)?$/', $v);
}

/**
 * Check if a value is a args/options that should be parsed with the `_args()` method. 
 * It needs to be a string with key/value pairs with '=' as the glue.
 *
 * @param mixed $str 
 * @return bool
 */
function ffto_is_args ($str){
	return is_array($str) || (
		is_string($str) &&              // needs to be a string
		strpos($str, '=') !== false &&  // there's no separator
		// (strpos($str, '=') !== false && strpos($str, '=>') === false) &&  // there's no separator
		strpos($str, 'http') !== 0 &&   // it's an URL 
		strpos($str, '<') !== 0         // it's html
	);
}

/**
 * Check if the value has the key
 *
 * @param mixed $v 
 * @param mixed $key 
 * @return bool
 */
function ffto_has ($v, $key){
	$v = _array($v);
	return _get($v, $key) !== null;
}

/**
 * Check if the value is in an array
 *
 * @param mixed $v 
 * @param mixed $arr 
 * @return booboollean
 */
function ffto_is_in ($v, $arr){
	$arr = _array($arr);
	return in_array($v, $arr);
}

/**
 * Check if a value is like a condition. There's multiple ways of comparaison:
 * 	- Similar 
 *  - Match a RegExp
 *  - Is empty
 *  - Is not empty
 *  - Match a function
 *  - Is an array and has all the $match values
 *  - Match a special code in an array:
 * 		- '>'			more than 
 * 		- '<' 			less than
 * 		- '<=' 			less than or equal
 * 		- '>=' 			more than or equal
 * 		- '<->' 		bettween
 * 		- '<=>' 		bettween or equal
 * 		- '>-<' 		outside
 * 		- '>=<' 		outside or equal
 * 		- ':like' 		calls ffto_is_like()
 * 		- ':not-like' 	calls ffto_is_like() and check if it's false
 * 		- ':in'			
 * 		- ':not-in'		
 *  - Is an array-object and all the match keys matches the array
 * 
 * ```php
 * ffto_is_like('John is 40', '/([a-z]+) is ([0-9]+)/i');
 * // true
 * 
 * ffto_is_like('John is in 40s', '/([a-z]+) is ([0-9]+)/i');
 * // false
 * 
 * ffto_is_like(['name'=>'John', 'age'=>40], function ($v){ return $v['age'] >= 40; });
 * // true
 * 
 * ffto_is_like(['name'=>'Bob', 'age'=>40], [
 *     'name' => ':is',
 *     'age'  => ['>='=>40],
 * ]);
 * // true
 * 
 * ffto_is_like('Bobby', 'bob'); // true
 * ffto_is_like([10,20,30], [20,30]); // true
 * ffto_is_like([10,20,30], [20,99]); // false
 * ffto_is_like(999, '>', 1000); // false
 * ffto_is_like(999, ['>'=>1000]); // false
 * ffto_is_like(5, '!=', 77); // true
 * ffto_is_like(999, ':in', [1,2,999]); // true
 * ffto_is_like(999, ':between', [1,1000]); // true
 * ffto_is_like('bob', ':end-with', 'B');	// false
 * ffto_is_like('bob', ':end-with', 'b');	// true
 * ```
 * 
 * @todo add ':count' comparation for sub-array, maybe adding a "$count" variable to arrays or something like that
 * @todo add a way to check deeper, using _get(), eg.: $search = ['location/is_open' => true]
 * @todo Maybe do complex searches, like a couple of "and" and "or"
 *
 * @param mixed $value 
 * @param mixed $search 
 * @param mixed $compare	When using the $search as a simple compare equation (like '>')
 * @return bool
 */
function ffto_is_like ($v, $search, $compare=null){
	$i = null;

	// simpler/quicker way to make a comparaison
	if (func_num_args() === 3){
		$search = [$search => $compare];
	}

	// if the value is an array, it could be an encoded $key,$value type
	if (is_array($v) && (isset($v['$key']) || isset($v['$value']))){
		list($i, $v) = _extract($v);

		// add the key back, so we can search/match on it
		if (is_array($v)){
			$v['$key'] = $i;

		}else if(is_object($v)){
			$v = clone $v;
			$v = _set($v, '$key', $i);
		}
	}
	
	// similar match
	if ($v === $search){
		return true;
	}else if (is_numeric($search) && is_numeric($v) && $search == $v){
		return true;
	}else if ($search === ':is' || $search === ':not-empty'){
		return ffto_is($v);
	}else if ($search === ':is-not' || $search === ':empty'){
		return ffto_is_not($v);
	// regexp
	}else if (is_string($v) && ffto_is_regexp($search)){
		return !!preg_match($search, $v);
	// 2 strings, so check if the $search is the same
	}else if (is_string($v) && is_string($search)){
		return $v == $search;
	// callback function
	}else if (is_callable($search)){
		return _call($search, $v, $i);
	// check if all values in $match array exists in $v
	}else if (ffto_is_list($v) && ffto_is_list($search)){
		foreach ($search as $vv){
			if (!in_array($vv, $v)) return false;
		}
		return true;
	}else if (is_array($v) && is_array($search)){
		foreach ($search as $ii => $vv){
			// the searched key doesn't exists
			if (
				!isset($v[$ii])
				|| !ffto_is_like($v[$ii], $vv)
			) return false;
		}
		return true;
	}else if (is_array($search)){
		$match = null;

		foreach ($search as $ii => $vv){
			$a   = is_array($vv) && array_key_exists(0, $vv) ? $vv[0] : $vv;
			$b   = is_array($vv) && array_key_exists(1, $vv) ? $vv[1] : null;
			$_s1 = is_string($v) ? $v : '';
			$_s2 = is_string($vv) ? $vv : '';
			
			if ($ii === '!=') 									$match = $v != $vv;
			elseif ($ii === '<') 								$match = $v < $vv;
			elseif ($ii === '>')								$match = $v > $vv; 
			elseif ($ii === '<=')								$match = $v <= $vv;
			elseif ($ii === '>=')								$match = $v >= $vv; 
			elseif ($ii === '<->' || $ii === ':between')		$match = $v > $a && $v < $b; 
			elseif ($ii === '<=>' || $ii === ':between-equal') 	$match = $v >= $a && $v <= $b; 
			elseif ($ii === '>-<' || $ii === ':outside') 		$match = $v < $a || $v > $b; 
			elseif ($ii === '>=<' || $ii === ':outside-equal') 	$match = $v <= $a || $v >= $b;
			elseif ($ii === ':like') 							$match = ffto_is_like($v, $vv);
			elseif ($ii === ':not-like') 						$match = !ffto_is_like($v, $vv);
			elseif ($ii === ':in') 								$match = in_array($v, $vv);
			elseif ($ii === ':not-in') 							$match = !in_array($v, $vv);
			elseif ($ii === ':contains') 						$match = $_s1 && strpos($_s1, $_s2) !== false;
			elseif ($ii === ':start-with') 						$match = $_s1 && strpos($_s1, $_s2) === 0;
			elseif ($ii === ':end-with') 						$match = $_s1 && ($_idx = strrpos($_s1, $_s2)) !== false && ($_idx + strlen($_s2) === strlen($_s1));

			if ($match === false){
				break;
			}
		}

		return $match ? true : false;
	}

	return false;
}

/**
 * Check if the site has a new version, compared with the `config:version` value
 *
 * @param string $old 
 * @return bool
 */
function ffto_is_new_version ($old=null){
	// check if the current site version is higher than the `$old` value
	if ($old){
		$version = _config('version');
		return version_compare($old, $version, '<');
	}else{
		// this is set when checking the `global:$config_file` in `ffto_config_set_version()`
		return !!_ctx('is_new_version');
	}
}

/**
 * Check if a value passes the filter. If the filter isn't set, it passes
 *
 * @param mixed $v
 * @param mixed $filter
 * @return bool
 */
function ffto_pass_filter ($v, $filter=null){
	return !$filter
		|| ($filter === true && ffto_is($v))
		|| !!_call($filter, $v);
}

/**
 * Helper for function that can return an object. Usually used with $args that are parsed with `_args()`
 *
 * @param mixed $v
 * @return bool
 */
function ffto_return_object ($v){
	return $v === true || (is_string($v) && ($v === ':object' || strtolower($v) === 'object'));
}

/**
 * Help finding a format depending on multiple conditions. The conditions are an array with keys (conditions) and values (results).
 * If $formats is set (either an array or a config value), and $format is a string, it will try to find the $format key in the $formats.
 *
 * ```php
 * $formats = [
 *	'*'					  => '-',
 *	'has_time'            => '`The time is` h:n',
 * 	'is_single'           => '`Single date` Y',
 * 	'is_single, has_time' => '`Single date` Y `and it\'s` h:n',
 * ];
 * ffto_to_conditional_format($value, $formats, [
 * 	'is_single' => false,
 * 	'has_time' 	=> false,
 * ]);
 * // '-'
 * 
 * ffto_to_conditional_format($value, $formats, [
 * 	'is_single' => true,
 * 	'has_time' 	=> false,
 * ]);
 * // '`Single date` Y'
 * 
 * ffto_to_conditional_format($value, $formats, [
 * 	'is_single' => true,
 * 	'has_time' 	=> true,
 * ]);
 * // '`Single date` Y `and it's` h:n'
 * 
 * ffto_to_conditional_format($value, $formats, [
 * 	'is_single' => false,
 * 	'has_time' 	=> true,
 * ]);
 * // '`The time is` h:n'
 * 
 * _config('test_formats', [
 * 	'*'        => '...',
 * 	':snippet' => 'Y.m.d',
 * ], true);
 * $v = ffto_to_conditional_format($value, ':snippet', [
 * 	'is_single' => false,
 * 	'has_time' 	=> true,
 * ], 'test_formats');
 * // 'Y.m.d'
 * ```
 * 
 * @param mixed 		$value 			The value/object to use when $format is a callback
 * @param string|array 	$format 		On format or many formats (with conditional keys)
 * @param array 		$conditions 	The list of true/false conditions
 * @param string|array 	$formats 		An extra $config list of formats to be used with $format
 * 
 * @return string
 */
function ffto_to_conditional_format ($value, $format, $conditions, $formats=null){
	// If it's a string, it gonna check if there's formats already set in the $config
	if (is_string($formats)){
		$formats = _config($formats);
	}
	
	$formats = is_array($formats) && !empty($formats) ? $formats : null;

	// check in $formats, maybe the $format is a special variable key
	if (is_string($format) && $formats){
		$format = _get($formats, $format, $format);

		// if the $format is an array, get the general formats as defaults too
		if (is_array($format)){
			// TODO, maybe not merging stuff, since the order or items can be messy
			// $format = array_merge($formats, $format);
			// $format = _args($format, $formats);
		}
	}else if ($format === null && $formats){
		$format = $formats;
	}

	// figure out the $formatting
	if (ffto_is_callback($format)){
		$format = _call($format, $value, $conditions);
	// if it's an array, we have to go through all the format coded keys and find what matches
	}else if (is_array($format)){
		$_format = null;
	
		foreach ($format as $i => $v){
			// skip none-string values
			if (!is_string($v)) continue;
			
			$keys  = explode(',', $i);
			$match = true;
			
			// go through all the formats, match the last valid item
			foreach ($keys as $k){
				$k = trim($k);
				if ($k === '*') continue;
				
				$vv = array_key_exists($k, $conditions) ? $conditions[$k] : null;
				$vv = $vv === null ? _filters('ffto_get_conditional_format/match', null, $k, $value) : $vv;

				if ($vv === true){
					continue;
				}else{
					$match = false;
					break 1;
				}
			}
			
			$_format = $match ? $v : $_format;
		}

		$format = $_format ? $_format : $format;
	}
	
	return is_string($format) ? $format : null;
}

// =====================================================================================================================
// Casting
// =====================================================================================================================
/**
 * Convert a string to an array and add default values. If the string isn't a value "param=value&param2=value" string,
 * a default $key can be used.
 * 
 * ```php
 * _args('name=John&age=50', ['child'=>0]); 
 * // ["child"=>0, "name"=>"John", "age"=>50]
 * 
 * _args('John', ['child'=>0], 'name');
 * // ["child"=>0, "name"=>"John"]
 * ```
 *
 * @param string $args
 * @param array $defaults
 * @param mixed $args
 *      - 'key' [null] key for default value if the $value isn't a proper args (array, object, http-query)
 *      - 'bool' [false] convert "true"/"false" to proper type
 *      - 'merge' [null] other values to merge with the returned value
 *      - 'filter' [null] call a filter at the end of the parsing with the `_filters()` function
 * @param boolean $bool
 * @return array
 */
function _args ($value, $defaults=null, $args=null, $filter=null){
	// a key for the main value, in case it's not an array and not a args string
	if (is_string($args)){
		$args = ['key'=>$args];
	}

	// args
	$args = is_array($args) ? $args : [];
	$args = array_merge([
		'key'    => null,    // key for default value if the $value isn't a proper args (array, object, http-query)
		'bool'   => false,   // convert "true"/"false" to proper type
		'merge'  => null,    // other values to merge with the returned value 
		'filter' => $filter, // call a filter at the end of the parsing
	], $args);

	$value	  = ffto_is_defined($value) ? $value : [];
	$is_array = is_array($value);
	$is_string= false;

	// Try to check if the string isn't a good url parameter
	if (!$is_array && ($key = $args['key']) && !ffto_is_args($value)){
		$value = [$key => $value];
	}

	// Decode the $value (if object or string)
	if (is_object($value)){
		$value = get_object_vars($value);
	}elseif (is_string($value)){
		$is_string = true;
		parse_str($value, $value);
	}
	
	// make sure it's an array
	$value = is_array($value) ? $value : [];

	// cast to the proper type (if the source was a http-query)
	$value = $is_string ? _values($value, [
		'bool' => $args['bool'],  // TODO really ??? check why "true" / "false" aren't parsed
		'json' => false,
	]) : $value;

	// keep a copy of the value
	$_value = $value;

	// add the defaults (and overwrite 'null' value in $value)
	if (is_array($defaults)) foreach ($defaults as $i => $v){
		if (array_key_exists($i, $value) && $value[$i] !== null) continue;
		$value[$i] = $v;
	}

	// add "merge" values (to force values)
	if (is_array($args['merge'])){
		$value = array_merge($value, $args['merge']);
	}

	if ($args['filter']){
		$value = _filters($args['filter'], $value, $_value);
	}

	return $value;
}

/**
 * Transform a string into a slug version, simplifyed text.
 *
 * @param mixed $text 
 * @param mixed $args 
 * 	- 'return' [string] return format
 * 		- ['space', ' '] space separator
 * 		- ['space-words', ' A', 'A ', ' A '] space separator and all are words with uppercase first letter
 * 		- ['camel'] camel case
 *      - ['snake', 'underscore', '_'] Snake case
 * 		- ['words', 'A'] words with uppercase first letter
 * 		- other: use this string as the separator
 * @return string
 */
function _slug ($text, $args=null){
	if (!$text) return null;

	$args = _args($args, array(
		'return'   => '',
		'fallback' => '',
	), 'return');

	$text = str_replace(["’",'"',"'"], '', $text);                                                       // remove quotes
	$text = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');                                // make sure it's utf-8
	$text = preg_replace_callback('/[A-Z]+/', function ($m){ return " " . strtolower($m[0]); }, $text);  // every uppercase will be turned into a lowercase with a space before
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);                                                   // replace non letter or digits by -
	$text = trim($text, '-');                                                                            // trim
	$text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);                                                // transliterate (remove accents)
	$text = strtolower($text);                                                                           // lowercase
	$text = preg_replace('~[^-\w]+~', '', $text);                                                        // remove unwanted characters
	
	// type of returned formats
	$r = $args['return'];

	// space separator
	if ($r === 'space' || $r === ' '){
		$text = str_replace('-', ' ', $text);
	// space separator and all are words with uppercase first letter
	}else if ($r === 'space-words' || $r === 'A ' || $r === ' A' || $r === ' A '){
		$text = ucwords(str_replace('-', ' ', $text));
	// camel case format or words format (with first letter uppercase)
	}else if ($r === 'camel' || $r === 'words' || $r === 'A'){
		$text = str_replace(' ', '', ucwords(str_replace('-', ' ', $text)));

		if ($r === 'camel'){
			$text[0] = strtolower($text[0]);
		}
	}else if ($r === 'snake' || $r === 'underscore' || $r === '_'){
		$text = str_replace('-', '_', $text);
	// any other string will replace the "-" for it
	}else if ($r){
		$text = str_replace('-', $r, $text);
	}
	
	return empty($text) ? $args['fallback'] : $text;
}

/**
 * Return an array based on the $items. If it's a string, it parses it.
 * 
 * ```php
 * $items = _array('3,5,432,true', true);
 * // [4, 5, 432, true]
 * 
 * _array([':count'=>10]);
 * // [1,2,3,4,5,6,7,8,9,10]
 * ```
 * 
 * @param  mixed $items
 * @param  array $args	
 * 		- 'separator' [,] string spearator
 * 		- 'trim' [true] trim strings
 * 		- 'decode' [false] use "_value()" on the strings
 * 		- 'object' [true] consider object as array
 * 		- 'null' [false] null values allowed
 * 		- 'filter' [$callback] filter values
 * 		- 'return' [null] return array (if value = 'array')
 * @param  function $filter
 * @return array
 */
function _array ($arr, $args=null, $filter=false){
	if ($args === true){
		$args = ['decode'=>true];
	}else if (ffto_is_callback($args)){
		$args = ['filter'=>$args];
	}else if ($args === ':array'){
		$args = ['return'=>'array'];
	}

	$args = _args($args, [
		'separator' => ',',       // string spearator
		'trim'      => true,      // trim strings
		'decode'    => false,     // use "_value()"
		'object'    => false,     // [true = keeps object keys, false = the object is added to an array]
		'null'      => false,     // null values allowed
		'filter'    => $filter,   // filter values
		'return'    => null,      // return [array]
	], 'separator');

	if (ffto_is_not($arr)){
		$arr = [];
	}else if (is_string($arr)){
		$arr = trim($arr);
		$arr = explode($args['separator'], $arr);
	}else if (is_array($arr) && isset($arr[':count'])){
		$count = $arr[':count'];
		$arr   = $count == 0 ? [] : range(1, floor($count));
	}else if (ffto_is_obj($arr) && !$args['object']){
		$arr = [$arr];
	}else if (!is_array($arr) && !is_iterable($arr)){
		$arr = [$arr];
	}

	$i    = 0;
	$_arr = [];
	foreach ($arr as $key => $v){
		if (is_string($v)){
			$v = $args['trim'] ? trim($v) : $v;
			$v = $args['decode'] ? _value($v) : $v;
		}

		if (!ffto_pass_filter($v, $args['filter'])) continue;
		$_arr[$key] = $v;

		$i++;
	}

	if ($args['return'] === 'array'){
		$_arr = array_values($_arr);
	}

	return $_arr;
}

/**
 * Deep merging of arrays. If one of the arguments is not an array, it will be converted.
 *
 * ```php
 * $v = _extend([], ['name'=>'Bob', 'child'=>['Marie']], ['child'=>['Joane'], 'age'=>44], ['age'=>66]);
 * // ["name"=>"Bob", "child"=>["Marie", "Joane"], "age"=>66]
 * 
 * $v = _extend([], 'Marie, Bob, Coool', ['age'=>45], ['children'=>true]);
 * // ["Marie", "Bob", "Cool", "age"=>45, "children"=>true]
 * ```
 * 
 * @return array
 */
function _extend (){
	$items = func_get_args();
	$first = array_shift($items);
	$first = is_array($first) ? $first : _array($first);

	foreach ($items as $item){
		$item = is_array($item) ? $item : _array($item);
		foreach ($item as $i => $b){
			$a            = isset($first[$i]) ? $first[$i] : null;
			$is_both_arr  = is_array($a) && is_array($b);
			$is_both_list = ffto_is_list($a) && ffto_is_list($b);
			$is_both_obj  = ffto_is_obj($a) && ffto_is_obj($b);
			
			// keep the same array
			if ($is_both_arr && empty($b)){
				// nothing
			// merge objects
			}else if ($is_both_obj){
				$a = _extend($a, $b);				
			// append similar array
			}else if ($is_both_list){
				$a = array_merge($a, $b);
			// replace value
			}else{
				$a = $b;
			}	
			
			$first[$i] = $a;
		}
	}

	return $first;
}

/**
 * Extract a batch of $keys (an the $value, if set). Good to use with `list()`
 *
 * ```php
 * $v = ['$key'=>45, '$value'=>['name'=>'John', 'age'=>40]];
 * list($i, $v) = _extract($v);
 * // $i = 45;
 * // $v = ['name'=>'John', 'age'=>40];
 * 
 * list($i, $v) = _extract(['$key'=>'cool', 1,2,3]);
 * // $i = cool
 * // $v = [1,2,3]
 * 
 * list($key, $group, $value) = _extract([
 * 	'$key'   => 'person-id',
 * 	'$group' => ['name'=>'Group Name'],
 * 	'name'   => 'Mark',
 * 	'age'    => 123
 * ], '$key,$group');
 * // $key = "person-id";
 * // $group = ['name'=>'Group Name'];
 * // $value = ['name'=>'Mark', 'age'=>123];
 * ```
 * 
 * @param mixed $v
 * @param array|string $keys
 * @param string $value_key
 * @return array
 */
function _extract ($v, $keys=null, $value_key='$value'){
	if ($keys === null){
		$keys = ['$key'];
	}else if (ffto_is_key($keys)){
		$keys = array_map('trim', explode(',', (string)$keys));
	}
	
	$values = [];

	foreach ($keys as $i){
		$vv = null;

		if (is_array($v)){
			if (isset($v[$i])){
				$vv = $v[$i];
			}
			unset($v[$i]);
		}

		$values[] = $vv;
	}

	if (is_array($v) && isset($v[$value_key])){
		$v = $v[$value_key];
	}

	// add the main value
	$values[] = $v;

	return $values;
}

/**
 * Convert a value to a string. 
 * 
 * ```php
 * _string(true);
 * // "true"
 * 
 * _string(null);
 * // "null"
 * 
 * _string(new DateTime());
 * // "2024-03-03 18:17:48 UTC"
 * 
 * _string(['name'=>'John', 'age'=>40], 'pretty=0'); 
 * // '{"name":"John","age":40}'
 * 
 * _string(['name'=>'John', 'age'=>40], ['pair'=>'[{$key}="{$value}"]']); 
 * // '[name="John"][age="40"]'
 * 
 * _string(['name'=>'John', 'age'=>40], ['pair'=>'=']); 
 * // ["name=John", "age=40"]
 * 
 * _string([40,22,30], 'template=[{$key}:{$value}]&join=, ');
 * // "[0:40], [1:22], [2:30]"
 * 
 * _string('45.54', [
 * 	'prefix'  => '+',
 * 	'template'=>'<strong>{$value}$</strong>'
 * ]); 
 * // "+45.54$"
 * 
 * _string('John', ['tabs'=>3]); 
 * "			John"
 * 
 * $people = [
 *     ['name' => 'John Doe "asdf"', 'age' => 28, 'gender' => 'male', 'phone' => '555-1234'],
 *     ['name' => 'Jane Smith', 'age' => 32, 'gender' => 'female', 'phone' => '555-5678'],
 *     ['name' => 'Sam Johnson', 'age' => 24, 'gender' => 'male', 'phone' => '555-8765'],
 *     ['name' => 'Lisa Brown', 'age' => 29, 'gender' => 'female', 'phone' => '555-3456'],
 *     ['name' => 'Chris Green', 'age' => 35, 'gender' => 'male', 'phone' => '555-9876'],
 *     ['name' => 'Anna White', 'age' => 22, 'gender' => 'female', 'phone' => '555-5432'],
 *     ['name' => 'Paul Black', 'age' => 31, 'gender' => 'male', 'phone' => '555-6543'],
 *     ['name' => 'Emma Gray', 'age' => 27, 'gender' => 'female', 'phone' => '555-4321'],
 *     ['name' => 'David Blue', 'age' => 40, 'gender' => 'male', 'phone' => '555-8761'],
 *     ['name' => 'Sophia Red', 'age' => 29, 'gender' => 'female', 'phone' => '555-2345']
 * ];
 * 
 * _string($people, '{name}', ':ul');
 * // "<ul>
 * // 	<li>John Doe "asdf"</li>
 * // 	<li>Jane Smith</li>
 * // 	<li>Sam Johnson</li>
 * // 	<li>Lisa Brown</li>
 * // 	<li>Chris Green</li>
 * // 	<li>Anna White</li>
 * // 	<li>Paul Black</li>
 * // 	<li>Emma Gray</li>
 * // 	<li>David Blue</li>
 * // 	<li>Sophia Red</li>
 * // </ul>"
 * 
 * _string($people, '{name}', ':span[index="item-{index}"]');
 * // "<span index="item-0">John Doe "asdf"</span>
 * // <span index="item-1">Jane Smith</span>
 * // <span index="item-2">Sam Johnson</span>
 * // <span index="item-3">Lisa Brown</span>
 * // <span index="item-4">Chris Green</span>
 * // <span index="item-5">Anna White</span>
 * // <span index="item-6">Paul Black</span>
 * // <span index="item-7">Emma Gray</span>
 * // <span index="item-8">David Blue</span>
 * // <span index="item-9">Sophia Red</span>"
 * ```
 * 
 * @param mixed $v value
 * @param mixed $args 
 * 	- 'template' [null] Template to use 
 * 	- 'pair' [null] Gluing key and value with a pair item OR a pair template
 * 	- 'join' [$join] Join array values together
 * 	- 'prefix' [''] Add a prefix
 * 	- 'suffix' ['] Add a suffix
 * 	- 'tag' [null] Add a tag wrapper around all items
 * 	- 'container' [null] Add a HTML container around the items
 *  - 'pretty' [true] Make the JSON output pretty
 * 	- 'null' [false] Output null values
 * 	- 'tabs' [0] Add tabs at the begining of the text
 * @param bool $join [", "]
 * @return mixed
 */
function _string ($v, $args=null, $join=', '){
	// nothing to convert
	if (is_string($v) && !$args){
		return $v;
	}

	if ($args === false){
		$args = ['pretty'=>false];
	}else if (is_numeric($args)){
		$args = ['tabs'=>$args];
	}

	$tag = null;
	if (is_string($join) && strpos($join, ':') === 0){
		$tag  = $join;
		$join = NL;
	}

	$args = _args($args, array(
		'template'    => null,
		'pair'        => null,
		'join'        => $join,
		'prefix'      => '',
		'suffix'      => '',
		'tag'         => $tag,
		'container'   => null,
		'pretty'      => true,
		'null'        => false,
		'tabs'        => 0,
	), 'template');

	// tags joining
	if ($tag = $args['tag']){
		$t = str_replace(':', '', $tag);

		// [ ] Add a way to specify the li, like:  ":ul > li[name="{name}"]"

		if (strpos($tag, ':ul') === 0){
			$args['tag']       = 'li';
			$args['container'] = $t;
		}else if (strpos($tag, ':ol') === 0){
			$args['tag']       = 'li';
			$args['container'] = $t;
		}else{
			$args['tag'] = $t;
		}
	}

	// Lots of special joins (with tag/container) or simple join
	$_join = function ($list, $refs) use ($args){
		$v = $list;

		if ($tag = $args['tag']){
			$_list = [];
			foreach ($list as $i => $v){
				$vars = array_merge([
					'index'=>$i
				], $refs[$i]);

				// add special classnames (is-first, is-last, ...)

				$html = __html($tag, ['html'=>$v, '$vars'=>$vars]);
				$_list[] = $html;
			}
			$v = $_list;
		}

		if ($container = $args['container']){
			$v = implode(NL, $v);
			$v = __html($container, ['html'=>$v]);
		}else if (is_string($join = $args['join'])){
			$v = implode($join, $v);
		}

		return $v;
	};

	// extract the values into key/value pair (keep a backup of $v for `_replace`)
	$_v = $v;
	list($key, $v) = _extract($v);

	$is_iterable = is_array($v) || is_object($v);
	
	if ($v === true){
		$v = 'true';
	}else if ($v === false){
		$v = 'false';
	}else if ($v === null){
		$v = 'null';
	}else if (is_a($v, 'DateTime')){
		$v = $v->format('Y-m-d H:i:s e');
	// array of items (not pretty encoded)
	}else if (ffto_is_list($v) && !$args['pretty']){
		$list = [];
		$refs = [];
		foreach ($v as $ii => $vv){
			// Skip null/undefined value
			if (
				!isset($vv) 
				&& !$args['null']
			) continue;

			$refs[] = $vv;

			$vv = _string($vv, [
				'pretty'   => $args['pretty'],
				'template' => $args['template'],
				'null'     => $args['null'],
			]);

			$list[] = $vv;
		}

		$v = $_join($list, $refs);
	// turn object into a pair of values
	}else if ($is_iterable && $args['pair']){
		$list = [];
		$refs = [];
		foreach ($v as $ii => $vv){
			if (ffto_is_not($vv)) continue;

			$refs[] = $vv;

			if (strpos($args['pair'], '{') !== false){
				$list[] = _replace($args['pair'], [
					'$key'   => $ii,
					'$value' => $vv,
				]);
			}else{
				$list[] = $ii . $args['pair'] . _string($vv);
			}
		}

		$v = $_join($list, $refs);
	// object that uses a template
	}else if ($is_iterable && $args['template']){
		$v = _replace($args['template'], $_v, '{}');
	// pretty json
	}else if ($is_iterable){
		$v = $args['pretty'] ? 
			json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 
			json_encode($v, JSON_UNESCAPED_UNICODE);

		// Try converting the output to a php array
		if ($args['pretty'] === 'php'){
			$v = strtr($v, [
				'": ' => '" => ',
				"{\n" => "[\n",
			]);
			$v = preg_replace('/\}(,|\n|$)/', ']$1', $v);
		}
	// use a template (if the value is a value value)
	}else if (ffto_is($v) && $args['template']){
		$v = _replace($args['template'], $v);
	}else{
		$v = (string)$v;
	}

	// prefix/suffix/tabs (if the value is valid)
	if (ffto_is($v) && is_string($v)){
		// prefix
		if ($prefix = $args['prefix']){
			$v = $prefix . $v;
		}

		// suffix 
		if ($suffix = $args['suffix']){
			$v = $v . $suffix;
		}

		// tabs
		if ($v && $tabs = $args['tabs']){
			$tabs = (int)$tabs;
			$tabs = str_repeat(TAB, $tabs);
			$v 	  = $tabs . str_replace(NL, NL.$tabs, $v);
		}
	}

	return $v;
}

/**
 * Return a random value: number, boolean, date, color, string, unique ID, array, ...
 *
 * ```php
 * _random(':color');
 * // #A85836
 * 
 * _random(':date');
 * // object(DateTime){[date]=>'2024-07-10'}
 * 
 * _random(':number');
 * // 631
 * 
 * _random(':number', 10, 300, true);
 * // 213.43
 * 
 * _random(':ratio');
 * // 0.778
 * 
 * _random(':boolean');
 * // true
 * 
 * _random([1,20,50,45,34,125,999]);
 * // 125
 * 
 * _random([1,20,50,45,34,125,999], 2);
 * // [50, 125]
 * 
 * _random(':uuid');
 * // '22fbff1a31be6d90be6fd7dc77a614361709487401904'
 * 
 * _random();
 * // 'WakVoSjc7bHg'
 * 
 * _random('prefix_');
 * // 'prefix_oi7zBBKCDJa0'
 * ```
 * 
 * @return mixed
 */
function _random ($a=null, $b=null, $c=null, $d=null){
	if ($a === ':color'){
		$r = str_pad((string)dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
		$g = str_pad((string)dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
		$b = str_pad((string)dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
		$v = strtoupper("#{$r}{$g}{$b}");
		return $v;	
	}else if ($a === ':date'){
		$dt = new DateTime('January 1st');
		$d  = mt_rand(1, 356);
		$dt->modify("{$d} day");
		return $dt;
	}else if ($a === ':number'){
		$min   = 0;
		$max   = 1000;
		$float = false;

		if (is_numeric($b) && is_numeric($c)){
			$min   = $b;
			$max   = $c;
			$float = $d === true;
		}else if (is_numeric($b)){
			$max   = $b;
			$float = $c === true;
		}else if ($b === true){
			$float = true;
		}

		if ($float){
			$v = mt_rand($min * 100, $max * 100) / 100;
		}else{
			$v = mt_rand($min, $max);
		}
		
		return $v;
	}else if ($a === ':ratio'){
		return mt_rand(0, 1000) / 1000;
	}else if ($a === ':boolean'){
		$a = [true, false];
		$i = mt_rand(0, 1);
		return $a[$i];
	}else if (is_array($a)){
		$min = null;
		$max = null;

		if (is_numeric($b) && is_numeric($c)){
			$min = $b;
			$max = $c;
		}else if (is_numeric($b)){
			$min = $b;
			$max = $b;
		}

		// return a random array
		if ($min !== null){
			$max   = min($max, count($a));
			$min   = min($min, $max);
			$count = mt_rand($min, $max);
			$keys  = (array)array_rand($a, $count);
			shuffle($keys);

			// keep the original keys
			$v = [];
			foreach ($keys as $i) $v[$i] = $a[$i];

			return $v;
		// only 1 item
		}else{
			$i = mt_rand(0, count($a)-1);
			return $a[$i];
		}
	}else if ($a === ':uuid'){
		$prefix = '';

		if (is_string($b)){
			$prefix = $b;
		}

		$r = bin2hex(random_bytes(16));
		$t = (int)(microtime(true) * 1000);
		$v = $prefix . $r . $t;
		
		return $v;
	}else{
		$prefix = '';
		$length = 12;

		if (is_string($a)){
			$prefix = $a;
			$length = is_numeric($b) ? $b : $length;
		}else if (is_numeric($a)){
			$length = (int)$a;
		}
		

		$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
		$max 		= count($characters) - 1;
		$v 			= "";

		for ($i = 0; $i < $length; $i++) {
			$r  = mt_rand(0, $max);
			$v .= $characters[$r];
		}

		return ($prefix ? $prefix : '') . $v;
	}
}

/**
 * Try decoding a JSON string to an array.
 *
 * @param mixed $v 
 * @param mixed $fallback
 * @return array
 */
function _json ($v, $fallback=null){
	if (is_array($v)){
		return $v;
	}

	if (!is_string($v)) return $fallback;

	$arr = json_decode($v, true);
		
	// validate the json
	if (function_exists('json_last_error')){
		$arr = json_last_error() == JSON_ERROR_NONE ? $arr : $fallback;
	}else{
		$arr = (is_object($arr) || is_array($arr)) ? $arr : $fallback;
	}

	return $arr;
}

/**
 * Test a string and return it's RegExp match(es)
 *
 * Arguments:
 * ```php
 * $args = [
 * 	"groups" => null,	// Return all the grouped matches (if there's matches many times)
 *  "return" => null,	// ["array" or default] Should ALWAYS return an array or just the match
 * ];
 * ```
 * 
 * ```php
 * _match('John is 40', '/^([a-z]+)/i'); 
 * // "John"
 * 
 * _match('John is 40', '/^([a-z]+).+?([0-9]+)/i', true); 
 * // ["John", "40"]
 * 
 * _match('John is 40', '/^(?<name>[a-z]+).+?(?<age>[0-9]+)/i', true); 
 * // ["name"=>"John", "age"=>"40"]
 * 
 * _match('a b c dd eee ffff', '/([a-z]{2,})/i'); 
 * // "dd"
 * 
 * _match('a b c dd eee ffff', '/([a-z]{2,})/i', false, true); 
 * // ["dd", "eee", "ffff"]
 * 
 * _match('a b c dd eee ffff', '/([a-z]{2})([a-z]+)/i', true, true); 
 * // [["ee","e"], ["ff","ff"]]
 * ```
 * 
 * @param string $v
 * @param string $re    RegExp string
 * @param mixed $args
 *      - 'group' [false] Return group instead of single value
 *      - 'multiple' [$multiple] Return multiple rows/matches
 *      - 'fallback' [null] Fallback value if nothing found
 * @return mixed
 */
function _match ($v, $re, $args=null, $multiple=false){
	if (!is_string($v)) return null;
	
	// make sure it's a RegExp string
	$re = ffto_to_regexp($re);

	$args = _args($args, [
		'group'    => null,     // groups instead of single value
		'multiple' => $multiple, // multiple items repeated
		'match'	   => false,
		'fallback' => null,
	], 'group');

	// find the value in the matched item
	$_value = function ($v) use ($args){
		if ($args['group'] === false){
			return $v[0];
		}else if ($args['group']){
			$arr = [];
			$obj = [];

			foreach ($v as $ii => $vv){ 
				if ($ii === 0) continue;

				// if there's a named group
				if (is_string($ii)){
					$obj[$ii] = $vv;
				}else{
					$arr[] = $vv;
				}
			}
			
			// return the named grouped if there's any found
			return empty($obj) ? $arr : $obj;
		}else if (isset($v[1])){
			return $v[1];
		}else if (isset($v[0])){
			return $v[0];
		}else{
			return $args['fallback'];
		}
	};

	$value = null;
	$_match= '';
	if ($args['multiple']){
		preg_match_all($re, $v, $match);
		$value = [];

		foreach ($match[0] as $i => $v){
			$m = [];

			// because the match list are spread separately, we need to reconstruct them in the same array
			foreach ($match as $ii => $vv){
				$m[$ii] = $match[$ii][$i];  
			}

			$value[] = $_value($m);
		}

		$_match = isset($match[0]) ? $match[0] : '';
	}else{
		preg_match($re, $v, $match);
		$value = $_value($match);

		$_match = isset($match[0]) ? $match[0] : '';
	}

	// TODO return $fallback here too
	if ($args['match']){
		$value = [
			'match' => $_match,
			'value' => $value,
		];
	}

	return $value;
}

/**
 * Convert a string value to it's proper type.
 *  
 * ```php
 * $value = _value('true');
 * // return true
 * 
 * $value = _value('[0, 3, 5]', true);
 * // return [0, 3, 5]
 * 
 * _value('$this', ['ctx' => 'John']);
 * // 'John'
 * ```
 * 
 * @param  mixed $str
 * @param  mixed $args
 *      - 'callback' [null] callback that is called to return a value first
 *      - 'bool' [true] Cast boolean
 *      - 'number' [true] Cast numbers
 *      - 'string' [true] Cast strings (by remove possible quotes around the string)
 *      - 'null' [true] Cast null
 *      - 'json' [false] Cast json data
 *      - 'data' [false] data URLs (used to be dataUrl)
 *      - 'trim' [false] trim strings
 *      - 'slash' [false] Remove slashes, normally used with form/posts that adds by default slashes
 *      - 'filter' [false] filter function
 * 		- 'ctx' [null] matches $this to a context
 * 		- 'ctxs' [null] matches the key (starting with '$') to a value in the ctx arrays
 *      - 'fallback' [$fallback] Fallback value
 * @param  mixed $fallback  Fallback value
 * @return mixed
 */
function _value ($v, $args=null, $fallback=null){
	if (!is_string($v)) return $v;
	
	if ($args === true){
		$args = ['json'=>true]; //, 'fallback'=>$v];
	}else if (is_callable($args)){
		$args = ['callback'=>$args];
	}

	$args = _args($args, array(
		'callback' => null,        // callback that is called to return a value first
		'bool'     => true,
		'number'   => true,
		'string'   => true,
		'null'     => true,
		'json'     => false,
		'data'     => false,       // data URLs (used to be dataUrl)
		'trim'     => false,       // 
		'slash'    => false,       // normally used with form/posts that adds by default slashes
		'filter'   => false,       // post filter
		'ctx'	   => null,
		'ctxs' 	   => null,
		'fallback' => $fallback,
	), 'fallback');

	$str  = trim($v, "\0");
	$istr = trim(strtolower($str), ' ');

	if (is_callable($args['callback'])){
		return $args['callback']($str);
	}else if ($args['data'] && strpos($istr, 'data:') === 0){
		// $data = ffto_to_match($str, '/data:(?P<type>[^;,]+)(?P<encoded>\;base64)?,(?P<data>.+)/');
		// $str  = $data['data'];
		// $str  = base64_decode($str);
		
		// if ($data['type'] === 'application/json'){
		// 	$str = json_decode($str);
		// }
		
		// TODO deal with image, .... and other types of mime types
	}else if ($args['bool'] && $istr === 'true'){
		return true;
	}else if ($args['bool'] && $istr === 'false'){
		return false;
	}else if ($args['null'] && $istr === 'null'){
		return null;
	}else if ($istr === '$this'){
		return $args['ctx'];
	}else if (strpos($istr, '$') === 0){
		$key = substr($istr, 1);
		return _get($args['ctxs'], $key);
	}else if ($args['number'] && ffto_is_nbr($str)){
		// @info large value like "2195382378469351263235594675996882415760431660453133648959" will give "9223372036854775807", that's an error. So if it's more than 19 characters, cast to float
		$nbr = strpos($str, '.') || strlen($str) > 19 ? (float)$str : (int)$str;
		return $nbr == $str ? $nbr : $str;
	}

	// decode the string to a json
	if ($args['json'] && is_string($str) && preg_match('/^[\{\[]([\s|\S]+)[\}\]]$/', $str)){
		$str = _json($str, $args['fallback']);
	}
		
	if (is_string($str)){
		// clear slashed, used mosty for 
		$str = $args['slash'] ? stripslashes($str) : $str;
		
		// remove the apostrophe wrapping the string
		if ($args['string']){
			$str = preg_replace('/^[\'"](.+)[\'"]$/', '$1', $str);
		}

		$str = $args['trim'] ? trim($str) : $str;
	}

	$str = _apply($args['filter'], $str);
	return $str;
}

/**
 * Parse values in an array to be their right type. It will try to match all types of values. A "separator" can be used in case it doesn't work properly or the separator is not ","
 *
 * ```php
 * _values('45, 88, true, "joe", "joe, is ok", [5,6,8], {"a":45}', true);
 * ```
 * 
 * @todo parse a Date string
 * 
 * @param mixed $str
 * @param array $args
 *      - 'separator' [null] 
 * 
 * @return array
 */
function _values ($v, $args=null){
	if (!ffto_is_defined($v)){
		return [];
	}

	if ($args === true){
		$args = ['json'=>true];
	}

	$args = _args($args, [
		'separator' => null,
	], 'separator');

	$_arr = [];
	if (is_array($v)){
		$_arr = $v;
	}else if (is_string($v) && ($sep = $args['separator'])){
		// finds the groups: "...", '...', {...}, [...], so we don't split those
		$re     = '/((?:\{[^}]+\})|(?:\[[^\]]+\])|(?:\"[^"]+?\")|(?:\'[^\']+?\'))/'; 
		$values = [];

		// match specific groups (with quotes of brackets)
		$_arr = preg_replace_callback($re, function ($m) use (&$values){
			$values[] = $m[1];
			return KEY;
		}, $v);		

		$_arr = explode($sep, $_arr);

		// re-add the value that were in groups
		$_arr = array_map(function ($v) use (&$values){ 
			$v = trim($v);
			$v = $v === KEY ? array_shift($values) : $v;
			return $v;
		}, $_arr);
	}else if (is_string($v) && preg_match_all('/[\"\'\[\{\(]/', $v)){
		// complex match groups (with quotes or brackets)
		$re   = '/(?:,|^|;)((?:[^,\("\'\{\[]+(?:(?:\(.+?\))|(?:(\{.+?\}))|(?:(\[.+?\]))|(?:\".+?\")|(?:\'.+?\'))?)|(?:\(.+?\))|(?:\".+?\")|(?:\'.+?\'))/';
		$count = preg_match_all($re, $v, $matches);

		if ($count) foreach ($matches[1] as $v){
			$_arr[] = trim($v);
		}
	}else{
		$_arr = explode(',', $v);
	}

	// decode values
	foreach ($_arr as $i => $v){
		$_arr[$i] = _value($v, $args);
	}

	return $_arr;
}

/**
 * Takes a string a convert it to a valid RegExp, escapes known characters
 * 
 * ```php
 * ffto_to_regexp('a-long-name'); // "/a\-long\-name/"
 * ```
 *
 * @param string 	$re			String to convert
 * @param boolean 	$closed		Should the RegExp contain the opening/closing slashes
 * @return string
 */
function ffto_to_regexp ($re, $closed=true){
	if (!ffto_is_regexp($re)){
		$re = preg_replace('/([-+_$().*\/\[\]])/', '\\\$1', $re);
		$re = $closed ? "/{$re}/" : $re;
	}

	// special shortcuts
	if (is_string($re)){
		$re = strtr($re, [
			'\slug' => '(?:[a-z][\w-]*)',
		]);
	}

	return $re;
}

/**
 * Return a object set of key/value pair(s). If the $key is already an array, return it, if it's a string/int, create a set pair.
 * [old] ffto_to_set
 * 
 * ```php
 * ffto_to_pair('a', 123); // ['a'=>123];
 * ```
 * 
 * @param  mixed 	$key
 * @param  mixed 	$value
 * @param  boolean 	$allow_null	Allow null value to be used as a value
 * 
 * @return array 	Pair of key/values
 */
function ffto_to_pair ($key, $value=null, $allow_null=true){
	if (is_array($key)){
		return $key;
	}else if ($key && func_num_args() >= 2 && ($value !== null || $allow_null)){
		$object 	  = array();
		$object[$key] = $value;
		return $object;
	}
	return null;
}

/**
 * Format milliseconds in an easy human readable version
 *
 * @param float $ms 
 * @param array $args 
 * 		- 'decimals' [0] : 
 * 		- 'labels' : 
 * 		- 'return' : 
 * @return string
 */
function ffto_to_human_time ($ms, $args=null){
	$args = _args($args, [
		'decimals' => 0,
		'labels'   => null,
		'return'   => null,
	], 'decimals');

	$labels = $args['labels'];
	if ($labels === ':small'){
		$labels = [
			'second' => 's',
			'minute' => 'm',
			'hour'   => 'h',
			'day'    => 'd',
			'year'   => 'y',
			'plural' => '',
		];
	}

	// default labels
	$labels = _args($labels, [
		'second' => 'second',
		'minute' => 'min',
		'hour'   => 'hour',
		'day'    => 'day',
		'year'   => 'year',
		'plural' => 's',
	]);

	if ($ms < 0.0001){
		return "0{$labels['second']}";
	}else if ($ms < 60){
		$ms   = $args['decimals'] ? number_format($ms, $args['decimals']) : ceil($ms);
		$time = "{$ms}{$labels['second']}";
	}else{
		$ms    = (int)$ms;
		$secs  = floor(($ms%60));
		$mins  = floor(($ms%3600)/60);
		$hours = floor(($ms%86400)/3600);
		$days  = floor(($ms%2592000)/86400);
		// $years = floor(($ms%2592000)/86400);
		
		// TODO deal with "plural", which would add an "s" or have an option

		$days  = $days ? "{$days}{$labels['day']}".($days>1?$labels['plural']:'') : null;
		$hours = $hours ? "{$hours}{$labels['hour']}".($hours>1?$labels['plural']:'') : null;
		$mins  = $mins ? "{$mins}{$labels['minute']}".($mins>1?$labels['plural']:'') : null;
		$secs  = $secs ? "{$secs}{$labels['second']}".($secs>1?$labels['plural']:'') : null;

		if ($args['return'] === 'simple'){
			$time = [$days, $hours, $mins, $secs];
			$time = array_filter($time);
			$time = [$time[0]];
		}else if ($args['return'] === 'full'){
			$time = [$days, $hours, $mins, $secs];
		}else{
			$time = [$days, $hours, $mins];
		}

		$time = implode(' ', array_filter($time));
	}
	return $time;
}

/**
 * Format bytes to a human readable value
 *
 * @param mixed $size 
 * @param int $decimals 
 * @param mixed $units 
 * @return string
 */
function ffto_to_human_bytes ($size, $args=null){
	if (!$size || $size < 0) return 0;

	$args = _args($args, [
		'decimals' => 0,
		'labels'   => null,
		'return'   => null,
	], 'decimals');

	$labels = $args['labels'];
	if (!$labels){
		$labels = ['B','KB','MB','GB','TB','PB'];
		// $labels = ['b','kb','mb','gb','tb','pb'];
	}

	$index	= floor(log($size,1024));
	$total  = pow(1024, $index);
	return isset($labels[$index]) ? ($total ? round($size/$total, $args['decimals']) : 0).$labels[$index] : '-';
}

/**
 * Check through all the possible $keys, if a value is the key or in the values (the alternative).
 * Return the matching keys
 * 
 * ```php
 * $data = [
 *	'color'  => 'red,blue,green',
 * 	'danger' => 'red,orange',
 * ];
 * 
 * $v = ffto_to_keys('red', $data);
 * // ['color','danger']
 * 
 * $v = ffto_to_keys('green', $data);
 * // ['color']
 * ```
 *
 * @param mixed $v 
 * @param array $keys 
 * @param bool $single 
 * @return mixed
 */
function ffto_to_keys ($v, $keys, $single=false){
	$_v    = function ($vv){ return is_string($vv) ? strtolower(trim($vv)) : $vv; };
	$v     = $_v($v);
	$items = [];
	foreach ($keys as $key => $alts){
		$alts = _array($alts, function ($vv) use ($_v){ return $_v($vv); });
		if ($v === $key || in_array($v, $alts)){
			$items[] = $key;
		}
	}

	return $single ? reset($items) : $items;
}

/**
 * Uses {@see ffto_to_keys} to get a valid key.
 * 
 * ```php
 * $data = [
 *	'color'  => 'red,blue,green',
 * 	'danger' => 'red,orange',
 * ];
 * 
 * $v = ffto_to_key('red', $data);
 * // 'color'
 * ```
 *
 * @param mixed $v 
 * @param mixed $keys 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_to_key ($v, $keys, $fallback=null){
	$key = ffto_to_keys($v, $keys, true);
	return ffto_is($key) ? $key : $fallback;
}

// =====================================================================================================================
// Helpers
// =====================================================================================================================
/**
 * Call a function with a list of $params. Return the first param if the callback isn't valid
 * 
 * ```php
 * ffto_apply(function ($v, $v2){ return $v . ' is ' . $v2 . '!'; }, ['joe', '40']); 
 * // "joe is 40!"
 * 
 * ffto_apply('not a function', ['joe', '40']);
 * // "joe"
 * 
 * function add ($v){ return $v + 100; }
 * function mul ($v){ return $v * 100; }
 * ffto_apply(['add', 'mul'], [50]); 
 * // 15000
 * ```
 *
 * @param Array|Function    $callbacks
 * @param Array             $params 
 * @param mixed             $args
 *      - 'ctx': Added context for calling the callbacks
 *      - 'null': Are null value allowed (a callback will return null if nothing is returned by it)
 *      - 'validate': validate if the callback should be used (used in `storage` class)
 *      - 'fallback': if none of the callbacks are valid, return this
 * 
 * @return mixed
 */
function ffto_apply ($callbacks, $params, $args=null){
	$args = _args($args, array(
		'ctx'      => null,     // added context for calling the callbacks
		'null'     => false,    // is null value allowed (a callback will return null if nothing is returned by it)
		'validate' => null,     // validate if the callback should be used (used in `storage` class)
		'fallback' => ':first',	// if none of the callbacks are valid
	));

	$params = is_array($params) ? $params : array();
	$value  = $args['fallback'];

	// get the first params as the fallback 
	if ($value === ':first'){
		$value = count($params) ? reset($params) : null;
	}

	// make sure the callbacks is an array
	$callbacks = is_array($callbacks) && !is_callable($callbacks) ? $callbacks : [$callbacks];

	// go through all the callbacks
	if (!empty($callbacks)){
		// add context to the callbacks
		if ($ctx = $args['ctx']){
			$callbacks = array_map(function ($v) use ($ctx){
				if (!is_array($v)){
					$v = [$ctx, $v];
				}
				return $v;
			}, $callbacks);
		}

		foreach ($callbacks as $callback){
			if (
				!is_callable($callback) ||
				($args['validate'] && !$args['validate']($callback))	// check if the method is allowed		
			) continue;

			$response = call_user_func_array($callback, $params);

			// update the $value and the $params if a value is returned
			if (isset($response) || $args['null']){
				$params[0] = $value = $response;
				
				// print_r(['->', $response, $args['null']]);
			};
		}		
	}

	return $value;
}

/**
 * Call ftto_apply with an unknown amount of parameters. 
 *
 * @param mixed $callbacks 
 * @return mixed
 */
function _apply ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return ffto_apply($callbacks, $params);
}

/**
 * Call {@see ffto_apply} with an unknown amount of parameters. If the callbacks return nothing, will return "null" instead of the first parameter
 *
 * @param mixed $callbacks 
 * @return mixed
 */
function _call ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return ffto_apply($callbacks, $params, [
		'fallback' => null,
		'null'     => true
	]);
}

/**
 * Try going thought the callbacks, if the callbacks aren't valid, return the first valid (since it's probably just another type of value)
 *
 * @param mixed $callbacks 
 * @return mixed
 */
function _format ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return ffto_apply($callbacks, $params, [
		'fallback' => $callbacks,
		'null'     => true
	]);
}

/**
 * Update a value on an item. Will update by property/index/method or use a callback function.
 * 
 * Special value with coded key can also be used:
 * - [':append'=>5] : make sure the parent is an array, then append the value
 * - [':prepend'=>5] : make sure the parent is an array, then prepend the value
 * - [':append-many'=>[1,2]] : append multiple values
 * - [':prepend-many'=>[1,2]] : prepend multiple values
 *
 * ```php
 * $item = ['a' => 135];
 * _set($item, 'b', 999);
 * // ['a' => 135, 'b' => 999]
 * 
 * $item = [];
 * _set($item, 'info.children', [1, 5]);
 * // ['info' => [1, 5]]
 * 
 * $value = ['items'=>[1,20,50,45,34,125,999]];
 * _set($value, 'items', [':prepend'=>666]);
 * // ['items'=>[666,1,20,50,45,34,125,999]]
 * 
 * $value = ['name'=>'John'];
 * _set($v, 'values', [
 *     ':append-many' => [500, 999],
 * ]);
 * _set($v, 'values', [
 *     ':prepend-many' => [1, 9],
 * ]);
 * // ['name'=>'John', 'values'=>[1,9,500,999]]
 * 
 * ```
 * 
 * @param  mixed &$item	
 * @param  string $path
 * @param  mixed $value
 * 
 * @return mixed
 */
function _set (&$item, $keys, $value=null){
	$_set = function (&$v, $k, $value){
		if (ffto_is_callback($k)){
			$k($v, $value);     
		// make sure the element is an array and append/prepend the value/values
		}elseif (
			isset($value[':append']) 
			|| isset($value[':prepend']) 
			|| isset($value[':append-many'])
			|| isset($value[':prepend-many'])
			|| isset($value[':merge'])
		){
			$v[$k] = is_array($v[$k]) ? $v[$k] : [];

			if (isset($value[':prepend'])){
				array_unshift($v[$k], $value[':prepend']);
			}
			if (isset($value[':prepend-many'])){
				$v[$k] = array_merge($value[':prepend-many'], $v[$k]);
			}
			if (isset($value[':append'])){
				array_push($v[$k], $value[':append']);
			}
			if (isset($value[':append-many'])){
				$v[$k] = array_merge($v[$k], $value[':append-many']);
			}
			if (isset($value[':merge'])){
				$v[$k] = array_merge($v[$k], $value[':merge']);
			}
		// remove the array value
		}elseif (is_array($v) && $value === null){
			unset($v[$k]);
		// array type
		}elseif (is_array($v)){
			$v[$k] = $value;
		// remove the object value
		}elseif (is_object($v) && $value === null){
			unset($v->$k);
		// object type
		}elseif (is_object($v)){
			$v->$k = $value;
		}

		return $v;
	};

	if (is_string($keys) && !preg_match('/\.|\/|\[/', $keys)){
		$_set($item, $keys, $value);
		return $item;
	}

	if (is_string($keys)){
		$keys = str_replace('[]', '/+', $keys);
		$keys = preg_replace('/\[([^\]]+)\]/', '/$1', $keys);
	}

	$keys  = _array($keys, '/');
	$count = count($keys);
	
	$_walk = function (&$v, $_w, $i=0) use ($keys, $count, $value, $_set){
		// the $i doesn't exists (won't happen since we guardrail it before calling $_walk the first time)
		if (!isset($keys[$i])) return;
	
		$k       = $keys[$i];
		$is_last = $i === ($count-1);

		// simple way to append to a new/old list with a "+" key
		if ($k === '+'){
			$v = is_array($v) ? $v : [];
			$k = count($v);
		}
		
		// if not at the end of the path, $value will be path the value, not the one to save
		if (!$is_last){
			if (is_array($v) && array_key_exists($k, $v)){
				$value = $v[$k] ? $v[$k] : []; 
			}elseif (is_object($v) && property_exists($v, $k)){
				$v->$k = $v->$k ? $v->$k : [];
				$value = $v->$k;
			}else{
				$value = [];
			}
		}

		// update the value ----------------------------------------------------
		$v = $_set($v, $k, $value);
	
		// go deeper
		if (!$is_last){
			if 		(is_array($v))	$_w($v[$k], $_w, $i + 1); 
			elseif 	(is_object($v))	$_w($v->$k, $_w, $i + 1);
		}

		return $v;
	};

	return $keys ? $_walk($item, $_walk, 0) : $value;
}

/**
 * Return the value of an item, goes through multiple ways to access a value. To have multiple options of fetching until there's a value, 
 * use a "," coma between keys groups. For separator the key traversing, use either a "." dot or a "/" slash.
 * 
 * Special path keys can be used:
 * - ':first' : first value in an array
 * - ':last' : last value in an array
 * - '?' : random value in an array
 * 
 * ```php
 * $item  = ['a' => 135];
 * $value = _get($item, 'a');
 * // 135
 * 
 * $item  = new DateTime('2020-01-01');
 * $value = _get($item, 'getTimestamp');
 * // 1577854800
 * 
 * $v = ['items'=>[1,20,50,45,34,125,999]];
 * $v = _get($v, 'items/:first');
 * // 1
 * 
 * $v = ['items'=>[1,20,50,45,34,125,999]];
 * $v = _get($v, 'items/:last');
 * // 999
 * 
 * $v = ['name'=>'John', 'age'=>40];
 * $v = _get($v, 'names || age'); // tries "ages", falls back to age if not found
 * // 40
 * ```
 * 
 * // [ ] add a way to get the value without splitting the $keys, in case in the $key, there's a ".", but it's not meant to be a separator
 * 
 * @param  mixed $item		Item to find the value
 * @param  mixed $key		Key attribute/index/function/... on the item. Can be an array for finding the first defined key
 * @param  mixed $fallback 	Fallback value to the return
 * @param  mixed $args 	
 *      - filter [null] 
 *      - empty [true] Allow empty values (null, '', [])
 * 
 * @return mixed
 */
function _get ($item, $keys, $fallback=null, $args=null){
	if (
		!is_array($item) 
		|| ffto_is_not($keys)
		|| !(is_string($keys) || is_numeric($keys) || ffto_is_list($keys))
	) return $fallback;

	if (is_callable($args)){
		$args = ['filter'=>$args];
	}

	$args = _args($args, [
		'filter'    => null,
		'empty'     => true,
		'separator' => '/',    // "children" separator (used to be ".", but since text has a bigger chance of using "." than "/", it's been changed)
		'or'        => '||',   // "or" separator
	], 'empty');

	// _get($_SERVER, 'HTTP_HOST || SERVER_NAME');
	// TODO separators, change the "." separator to something else OR have none

	// try extracting special keys
	list($_key, $_index, $_value) = _extract($item, '$key,$index');
	$item = $_value;

	$_get = function ($v, $k, $i=0, $_fallback=null) use ($_key, $_index, $_value, $fallback){
		// Special "$key" if set
		if ($i === 0 && $k === '$key'){
			$v = $_key;
		// Special "$index" if set
		}else if ($i === 0 && $k === '$index'){
			$v = $_index;
		// Special "$value" if set
		}else if ($i === 0 && $k === '$value'){
			$v = $_value;
		// function
		}else if (ffto_is_callback($k, true)){
			$v = $k($v, $fallback);
		// array value
		}elseif (is_array($v) && array_key_exists($k, $v)){
			$v = $v[$k];
		// object method
		}elseif (is_object($v) && method_exists($v, $k)){
			$v = $v->$k();
		// object property
		}elseif (is_object($v) && property_exists($v, $k)){
			$v = $v->$k;
		// try using a magic method on an object 
		}elseif (is_object($v) && method_exists($v, '__get')){
			try{ $v = $v->$k; }catch(Exception $e){};
		}else{
			$v = $_fallback;
		}
		return $v;
	};

	if (is_string($keys) && (
		($args['separator'] && strpos($keys, $args['separator']) === false) &&
		($args['or'] && strpos($keys, $args['or']) === false)
	)){ 
		$keys = trim($keys);
		return $_get($item, $keys, 0, $fallback);
	}

	$alts  = is_array($keys) ? $keys : array_map('trim', explode($args['or'], (string)$keys)); // to reduct dependencies of _array -> _get, we re-do a similar _array() behavior
	$value = null;


	foreach ($alts as $alt){
		// transform paths with square brackets to dots separators
		$keys = is_string($alt) ? strtr($alt, [
			'[' => $args['separator'],   // simplify keys with brackets (eg.: values[5] = values/5)
			']' => ''
		]) : $alt;

		$keys  = _array($keys, $args['separator']);
		$count = count($keys);
		$v     = $item;
		for ($i=0, $l=$count; $i<$l; ++$i){
			$k = $keys[$i];

			// special code for path key
			if (ffto_is_list($v)){
				if ($k === ':first')	 $k = 0;
				else if ($k === ':last') $k = count($v)-1;
				else if ($k === '?')	 $k = array_rand($v);
			}

			$v = $_get($v, $k, $i);

			if ($i === $l-1){
				$value = $v;
			}else if ($v === null){
				break;
			}
		}

		// empty values are not permitted, so set to null
		if (!$args['empty'] && empty($value)){
			$value = null;
		}

		if ($value !== null){
			return _apply($args['filter'], $value);
		}
	}

	return $fallback;
}

/**
 * Parse a string and replace all instances of variables with a value (if found) in the `$data`. The variables
 * uses this pattern "{ variable_name }". Has the option of alternative variable and a default value if none is found.
 * 
 * @todo add modifiers, like ":bold", ":italic"
 *
 * ```php
 * _replace('{name} is {age} and has {child_count, count} children. He\'s a {job || human}', [
 * 	'name'  => 'John',
 * 	'age'   => 40,
 * 	'count' => 3,
 * ]);
 * // John is 40 and has 3 children. He's a human
 * 
 * _replace('::name is ::age', ['name'=>'John', 'age'=>40], '::');
 * // John is 40
 * ```
 * 
 * @param mixed $str 
 * @param mixed $data 
 * @param mixed $args 
 * @return string|mixed
 */
function _replace ($str, $data, $args=null){
	if (
		!is_string($str)
		|| empty($data)
		|| (strpos($str, '{') === false && $args === null) // default pattern for the string, it has a "{" at least 
	){
		return $str;
	}
	
	$data = $data ? _args($data, null, '$value') : $data;   // make sure the data is an array
	$str  = ffto_is_callback($str) ? $str($data) : $str;  	// make sure the string is a string (if a callback, call it)

	if($args === '::')			$args = ['match'=>'::'];
	else if ($args === ':')		$args = ['match'=>':'];
	else if ($args === '`')		$args = ['match'=>'`'];
	else if ($args === '$')		$args = ['match'=>'$'];
	else if ($args === '[]')	$args = ['match'=>'[]'];
	else if ($args === '{}')	$args = ['match'=>'{}'];
	else if ($args === '{{}}')	$args = ['match'=>'{{}}'];

	// TODO maybe have other types of brackets, maybe use emoji for fun


	if (ffto_is_callback($args)){
		$args = ['format'=>$args];
	}

	$args = _args($args, [
		'match'    => null,   // variable matching
		'fallback' => null,   // fallback value if nothing ... is this necessary??
		'format'   => null,
		'match'    => '{}',
	], 'fallback');

	// different type of pre-made regexp matching
	$re = '';
	if ($args['match'] === '::')			$re = '/::([a-z][a-z0-9_-]*)/';  						// ::value
	else if ($args['match'] == ':')			$re = '/:([a-z][a-z0-9_-]*)/';  						// :value
	else if ($args['match'] == '$')			$re = '/\$([a-z][a-z0-9_-]*)/';  						// $value
	else if ($args['match'] === '{}') 		$re = '/\{{1,2}\s*([^}|]+)(?:\|{2}([^}]*))?\s*\}{1,2}/';  	// { value } or {{ value }} with fallback: { value || fallback } or {{ value || fallback }}
	else if ($args['match'] === '{{}}') 	$re = '/\{{2}\s*([^}|]+)(?:\|{2}([^}]*))?\s*\}{2}/';  		// {{ value }} with fallback: {{ value || fallback }}
	// TODO
	// else if ($args['match'] === '[]') 	$re = '/\[{1,2}\s*([^}|]+)(?:\|{2}([^}]*))?\s*\}{1,2}/';  	// [ value ] with fallback: [ value | fallback ]
	// TODO
	// else if ($args['match'] === '`') 	$re = '/\[{1,2}\s*([^}|]+)(?:\|{2}([^}]*))?\s*\}{1,2}/';  	// ` value ` with fallback: ` value | fallback `
	else if ($args['match']) 		  	$re = ffto_to_regexp($args['match']);  					// custom matching
	// else							 	$re = '/\{{2}\s*([^}|]+)(?:\|([^}]*))?\s*\}{2}/';  		// {{ value }} with fallback: {{ value | fallback }}

	$raw  = null;
	$_str = $str;
	$str  = preg_replace_callback($re, function ($m) use (&$raw, $_str, $data, $args){
		$keys		= $m[1];
		$fallback 	= isset($m[2]) ? trim($m[2]) : $args['fallback'];
		$value 		= _get($data, $keys, $fallback);

		// if there's only a variable to return
		if ($_str === $m[0]){
			$raw = $value;
		}

		// make sure the value returned is a string
		if (is_array($value) || is_object($value)){
			$value = _string($value);
		}

		// process the value
		if ($format = $args['format']){
			$value = _apply($format, $value);
		}

		return $value;
	}, $str);

	return $raw !== null ? $raw : $str;
}

/**
 * Go through an array and process it
 * 
 * ```php
 * _each('Bob the <strong>dragqueen</strong>', ':words', '[{$value}]');
 * // ["[Bob]", "[the]", "[dragqueen]"]
 * 
 * _each([1,50,999], '[{$value}]', ', ');
 * // "[1], [50], [999]"
 * 
 * _each([1,50,999], function ($v){ return $v * 2; });
 * // [2, 100, 1998]
 * 
 * _each(['Bob', 'John'], '/o\w+/');
 * // ["ob", "ohn"]
 * 
 * _each([
 * 	['name'=>'John', 'age'=>40],
 * 	['name'=>'Bob', 'age'=>32],
 * ], '[{name} -> {age}]', ' / ');
 * // "[John -> 40] / [Bob -> 32]"
 * 
 * _each([
 * 	['name'=>'John', 'age'=>40],
 * 	['name'=>'Bob', 'age'=>32],
 * ], function ($v){
 * 	return [
 * 		'$key'   => 'person_' . strtolower($v['name']),
 * 		'$value' => $v['age'],
 * 	];
 * });
 * // ["person_john"=>40, "person_bob"=>32]
 * 
 * _each("1,5,,50", ['reduce'=>[45, function ($a, $b){ return $a + $b; }]]);
 * // 101
 * 
 * $people = [
 *     ['name' => 'John Doe "asdf"', 'age' => 28, 'gender' => 'male', 'phone' => '555-1234'],
 *     ['name' => 'Jane Smith', 'age' => 32, 'gender' => 'female', 'phone' => '555-5678'],
 *     ['name' => 'Sam Johnson', 'age' => 24, 'gender' => 'male', 'phone' => '555-8765'],
 * ];
 * _each($people, 'set_key=phone');
 * // [
 * //	"555-1234" => ["name"=>"John Doe \"asdf\"", "age"=>28, "gender"=>"male", "phone"=>"555-1234"],
 * //	"555-5678" => ["name"=>"Jane Smith", "age"=>32, "gender"=>"female", "phone"=>"555-5678"],
 * //	"555-8765" => ["name"=>"Sam Johnson", "age"=>24, "gender"=>"male", "phone"=>"555-8765"],
 * // ];
 * ```
 *
 * @param mixed $arr 
 * 	- 'separator' [','] use if the $arr is a string
 * 	- 'key' [null] key to use to get the element property
 * 	- 'set_key' [null] set the keys by getting a property OR using a template (with "{" and "}")
 * 	- 'format' [null] template to use to parse the item
 * 	- 'filter' [true] filter through the items. TRUE will return what's defined or a callback can be used
 * 	- 'return' [false] key to return, one of many keys
 * 	- 'reduce' [false] reduce the results into 1 value
 * 	- 'single' [false] return only 1 item
 * 	- 'join' [$join] join the array together with a glue 
 * 
 * @param mixed $args 
 * @param mixed $join 
 * @return array|string
 */
function _each ($arr, $args=null, $join=null){
	if ($args === true){
		$args = ['single'=>$args];
	}else if ($args === false){
		$args = ['filter'=>false];
	}else if (ffto_is_callback($args)){
		$args = ['format'=>$args];
	}else if ($args === ':words'){
		$args = ['separator'=>' '];
		$arr  = is_string($arr) ? strip_tags($arr) : $arr;
	}
	
	$args = _args($args, array(
		'separator' => ',',
		'key'       => null,       // get a specific value from the items
		'set_key'	=> null,		// updating the keys		
		'format'    => null,
		'filter'    => true,
		'return'	=> false,
		'reduce'	=> false, 	// if an array is returned, it will be merge with the results
		'single'    => null,
		'join'      => $join,
	), 'format');

	$arr = _array($arr, [
		'separator' => $args['separator'],
		'object'	=> true,	// it can't ever be just an object, always a list array
		'decode'    => true,	// parse/decode the values
	]);

	// reduce function (and optional inital value)
	$reduce = $args['reduce'];
	if (is_callable($reduce)){
		$reduce = [null, $reduce];
	}

	// key plucking
	$pluck_key  = $args['return'];
	$pluck_keys = null;
	if (
		(is_string($pluck_key) && strpos(',', $pluck_key))
		|| is_array($pluck_key)
	){
		$pluck_keys = _array($pluck_key);
		$pluck_key  = null;
	}

	$format = $args['format'];
	$index  = 0;
	$keys   = array_keys($arr);
	$count  = count($arr);
	$items  = [];
	foreach ($arr as $key => $_v){
		$i = $index++;
		$v = $args['key'] ? _get($_v, $args['key']) : $_v;
	
		if (ffto_is_callback($format)){
			$isFirst = $i === 0;
			$isLast  = $i === ($count-1);
			
			$data = [
				'index'     => $i,
				'length'    => $count,
				'isFirst'   => $isFirst,
				'isLast'    => $isLast,
				'isBetween' => !$isFirst && !$isLast,
				'previous'  => isset($keys[$i-1]) ? $arr[$keys[$i-1]] : null,
				'next'      => isset($keys[$i+1]) ? $arr[$keys[$i+1]] : null,
			];
			
			$v = _apply($format, $v, $key, $data);
		}elseif (ffto_is_regexp($format)){
			$v = _match($v, $format);
		}elseif (is_string($format)){
			$v = _replace($format, [
				'$index' => $i,
				'$key'   => $key,
				'$value' => $v,
			]);
		}
		
		// get the key/value if set in in a special array with "$key" and/or "$value"
		list($_key, $v) = _extract($v);
		$key = $_key ? $_key : $key;
		
		// setting keys
		if ($k = $args['set_key']){
			if (ffto_is_str($k, '{')){
				$key = _replace($k, $_v);
			}else{
				$key = _get($_v, $k);
			}
		}

		// return a couple of specific keys
		if ($pluck_keys){
			$vv = [];

			// [ ] instead of use _get() right away, maybe try 'is_array' and isset($v[$k]) to simplyfy/speedify the process

			foreach ($pluck_keys as $iii => $vvv){
				// if it's an object array, the key is the key to use, the value is the path to get (in get it's a complex object)
				if (is_string($iii)){
					$vv[$iii] = _get($v, $vvv);
				}else{
					$vv[$vvv] = _get($v, $vvv);
				}
			}
			$v = $vv;		
		// return a specific key
		}else if ($pluck_key){
			$v = _get($v, $args['return']);
		}

		// filter the value
		if (!ffto_pass_filter($v, $args['filter'])) continue;

		// reduce the value
		if ($reduce){
			$vv = $reduce[0];
			$c  = $reduce[1];
			
			// first time you reduce and there's no initial value, just return the value
			if ($vv === null){
				$vv = $v;
			// call the reducer with both the current value and the previous one
			}else{
				$vv = _call($c, $v, $vv);
			}
			
			$reduce[0] = $vv;
			$items     = $vv;
			continue;
		}
		
		if ($key !== null){
			_set($items, $key, $v);
		}else{
			$items[] = $v;
		}

		// only 1 item to process
		if ($args['single']){
			break;
		}
	}

	// return 1 item
	if ($args['single']){
		$items = reset($items);
	// wrapping the results means all items needs to be joinded
	}else if (is_array($items) && ($join = $args['join'])){
		$items = implode($join, $items);
	}

	return $items;
}

/**
 * Try calling a function if it exists
 *
 * @param string $callback
 * @return bool
 */
function _try ($callback){
	$params = func_get_args();
	array_shift($params);

	if (function_exists($callback)){
		call_user_func_array($callback, $params);
		return true;
	}

	return false;
}

/**
 * Simple way of returning html and printing it to the screen.
 * [old] e()
 *
 * @param mixed $html 
 * @param bool $echo 
 * @return void
 */
function _echo ($html, $echo=true){
	if ($echo && (is_string($html) || is_numeric($html))){
		echo $html;
	}
	return $html;
}

// =====================================================================================================================
// Data
// =====================================================================================================================
/**
 * Set a value in the $GLOBALS variable
 *
 * @todo the filter, needs to use the last key
 * 
 * @param mixed $key 
 * @param mixed $value 
 * @param mixed $apply_filters apply the filters on that global key (eg.: key=name, then "ffto_set_global/name" filter). Set to false to skip
 * @return mixed
 */
function ffto_set_global ($key, $value=null, $apply_filters=true){
	// set multiple values
	if (is_array($key)){
		foreach ($key as $i => $v) ffto_set_global($i, $v, $value);
		return $key;
	}

	global $FFTO_GLOBALS_VALUES;
	$key    = is_object($key) || is_array($key) ? spl_object_hash($key) : $key; // ability to set a global value with an object/array
	$value  = $apply_filters ? _filters("ffto_set_global/{$key}", $value) : $value;
	$values = is_array($FFTO_GLOBALS_VALUES) ? $FFTO_GLOBALS_VALUES : [];
	_set($values, $key, $value);
	$GLOBALS['FFTO_GLOBALS_VALUES'] = $values;
	
	return $value;
}

/**
 * Get a value from the $GLOBALS, return the $fallback if not found
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @param mixed $apply_filters apply the filters on that global key (eg.: key=name, then "ffto_set_global-name" filter). Set to false to skip
 * 
 * @return mixed
 */
function ffto_get_global ($key=null, $fallback=null, $apply_filters=true){
	global $FFTO_GLOBALS_VALUES;
	$values = is_array($FFTO_GLOBALS_VALUES) ? $FFTO_GLOBALS_VALUES : [];

	if ($key){
		$key   = is_object($key) || is_array($key) ? spl_object_hash($key) : $key;
		$value = _get($values, $key, $fallback);
		$value = $apply_filters ? _filters("ffto_get_global/{$key}", $value) : $value;
		return $value;
	}else{
		return $values;
	}
}

/**
 * Shortcut to `ffto_set_global()` and `ffto_get_global()`
 *
 * @param mixed $key 
 * @param mixed $value Set value if $is_set is true, fallback value if not
 * @param bool $save Setting a value
 * @return mixed
 */
function _global ($key=null, $value=null, $save=false){
	if (is_array($key)){
		return ffto_set_global($key);
	}else if ($save){
		return ffto_set_global($key, $value);
	}else if ($key){
		return ffto_get_global($key, $value);
	}else{
		return ffto_get_global();
	}
}

/**
 * Set a certain context a value. Good for when needed to output certain stuff depending on the context.
 * 
 * @param $key
 * @param $value
 * @return mixed
 */
function _ctx ($key=null, $value=null){
	if (is_string($key)){
		$key = strtolower($key);
	}

	if (func_num_args() >= 2){
		return _global('$ctx/'.$key, $value, true);
	}else if ($key){
		return _global('$ctx/'.$key);
	}else{
		return _global('$ctx', []);
	}
}

/**
 * Check and/or update the context after returning the value. Good for checking if a context had a certain value before being updated.
 * 
 * ```php
 * $v = _did('added_javascript', true);
 * // null
 * 
 * $v = _did('added_javascript');
 * // true
 * ```
 *
 * @param mixed $key 
 * @param mixed $value 
 * @return void
 */
function _did ($key, $value=null){
	$v = _ctx($key);
		
	if (func_num_args() >= 2){
		_ctx($key, $value);
	}

	return $v;
}

// =====================================================================================================================
// Filters/Actions
// =====================================================================================================================
/**
 * Add hook action
 * 
 * @param string $hook
 * @param string $key
 * @param number $depth
 * @param function $callback
 * 
 * @return void
 */
function ffto_add_hook ($hook, $key, $depth, $callback=null){
	if (is_callable($depth)){
		$callback = $depth;
		$depth    = 10;
	}else if ($depth === null){
		$depth 	  = 10;
	}

	$path  = '$hooks/'.$hook;
	$hooks = ffto_get_global($path, [], false);

	// turn the invalid callback (like a simple string) into a function that will return that value.
	if ($callback !== null && !is_callable($callback)){
		$v        = $callback;
		$callback = function () use ($v){ return $v; };
	}

	// make sure the structure is there
	$hooks[$key]         = isset($hooks[$key]) ? $hooks[$key] : [];
	$hooks[$key][$depth] = isset($hooks[$key][$depth]) ? $hooks[$key][$depth] : [];
	$hooks[$key][$depth][] = $callback;

	// order by keys
	ksort($hooks[$key], SORT_NUMERIC);
	
	ffto_set_global($path, $hooks, false);
}

/**
 * Remove hook action
 * 
 * @param string $hook
 * @param string $key
 * @param number $depth
 * @param function $callback
 * 
 * @return void
 */
function ffto_remove_hook ($hook, $key, $depth=null, $callback=null){
	if (is_callable($depth)){
		$callback = $depth;
		$depth    = 10;
	}

	$path  = '$hooks/'.$hook;
	$hooks = ffto_get_global($path, [], false);
	
	if ($callback){
		$callbacks = isset($hooks[$key][$depth]) ? $hooks[$key][$depth] : [];
		$index 	   = array_search($callback, $callbacks);
		
		if ($index !== false){
			array_splice($callbacks, $index, 1);
			$hooks[$key][$depth] = $callbacks;
		}		
	}else if ($depth !== null){
		unset($hooks[$key][$depth]);
	}else{
		unset($hooks[$key]);
	}

	ffto_set_global($path, $hooks, false);
}

/**
 * Apply hook actions to a list of parameters
 * 
 * ```php
 * $filter1 = function ($v){ return $v . '!!'; };
 * $filter2 = function ($v){ return '[' . $v . ']'; };
 * add_hook('test', 'str', $filter1);
 * add_hook('test', 'str', $filter2);
 * $v = apply_hooks('test', 'str', 'John');
 * // "[John!!]"
 * ```
 * 
 * @param string $hook
 * @param string $key
 * 
 * @return mixed
 */
function ffto_apply_hooks ($hook, $key){
	$path   = '$hooks/'.$hook;
	$hooks  = ffto_get_global($path, [], false);
	$hooks  = isset($hooks[$key]) ? $hooks[$key] : [];
	$params = func_get_args();
	$params = array_slice($params, 2);
	
	foreach ($hooks as $callbacks){
		$params[0] = ffto_apply($callbacks, $params);
	}

	return isset($params[0]) ? $params[0] : null;
}

// Since Wordpress already has those functions, we'll created them for the none-Wordpress websites
if (
	!function_exists('add_filter') && !function_exists('remove_filter') && !function_exists('apply_filters') && 
	!function_exists('add_action') && !function_exists('remove_action') && !function_exists('do_actions')
){
	function add_filter (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('ffto_add_hook', $params);
	}
	function remove_filter (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('ffto_remove_hook', $params);
	}
	function apply_filters (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('ffto_apply_hooks', $params);
	}
	function add_action (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('ffto_add_hook', $params);
	}
	function remove_action (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('ffto_remove_hook', $params);
	}
	function do_actions (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('ffto_apply_hooks', $params);
	}
}

/**
 * Shortcut to `apply_filters()`
 *
 * @return mixed
 */
function _filters (){
	$params = func_get_args();
	return call_user_func_array('apply_filters', $params);
}

/**
 * Shortcut to `do_actions()`
 *
 * @return mixed
 */
function _actions (){
	$params = func_get_args();
	return call_user_func_array('do_actions', $params);
}

// =====================================================================================================================
// Debug
// =====================================================================================================================
/**
 * Calculate time for a process to be done. Use this function multiple time to calculate times.
 * 
 * ```php
 *  _time('test');
 *  $a = _time('test');
 * ```
 *
 * @param mixed $name 
 * @param mixed $log			Log the time
 * @param mixed $_performance	Also calculate the memory used
 * @return mixed
 */
function _time ($name=null, $log=null, $_performance=false){
	if ($name === true){
		$log  = true;
		$name = null;
	}

	$key    = $name ? $name : '*';
	$path   = $_performance ? '$timing_performances/'.$key : '$timing/'.$key;
	$time   = microtime(true);
	$memory = $_performance ? memory_get_usage() : 0;
	$item   = _global($path, [
		'time'   => $time,
		'memory' => $memory,
	]);

	// outputs
	$_time   = ffto_to_human_time($time - $item['time'], ['decimals'=>4, 'labels'=>':small']);
	$_memory = ffto_to_human_bytes(($memory - $item['memory']), 2);
	
	// save the new values
	$item['time']   = $time;
	$item['memory'] = $memory;
	_global($path, $item, true);

	$time   = '[' . str_pad($_time, 12, ' ', STR_PAD_LEFT) . ']';
	$memory = $_performance ? ' [' . str_pad($_memory, 12, ' ', STR_PAD_LEFT) . ']' : '';
	$text 	= $time . $memory;
	$_text 	= $text . (is_string($name) ? " {$name}" : '');

	// output the time in a log (js, error, screen, js by default)
	$log && _log([
		'view'     => $log, 
		'prefix'   => $_performance ? 'PERFORMANCE' : 'TIME',
		'color'    => '#0c28af',
		'trace'    => false,
		'wrap'     => false,     // screen: do now style the wrapper
		'minimize' => false,     // js: don't minimize the output
	], $text);

	return $_performance ? [
		'time'   => $_time,
		'memory' => $_memory,
		'name'	 => $name,
		'text'	 => $text,
	] : $_time;
}

/**
 * Calculate the time and memory used for processing something.
 *
 * @param mixed $name 
 * @param mixed $log 
 * @return array
 */
function _performance ($name=null, $log=null){
	return _time($name, $log, true);
}

// =====================================================================================================================
// Files
// =====================================================================================================================
/**
 * Clean up a path/url by removing multiple slashes and resolve the path when using "." and ".."
 *
 * ```php
 * ffto_to_cleanpath('/aaa/bbb/.././file.ext');
 * // /aaa/file.ext
 * 
 * ffto_to_cleanpath('http://asdf/bbb/./asdf');
 * // "http://asdf/bbb/asdf"
 * ```
 * 
 * @param mixed $path 
 * @param bool $is_dir 
 * @return string
 */
function ffto_to_cleanpath ($path, $is_dir=false){
	$path   = $path ? $path : '';
	$scheme = _match($path, '/^((?:[a-z+-]+\:(?:\/{1,2})?)|(?:\/{1,2}))/', 'fallback=');
	$path   = $scheme ? substr($path, strlen($scheme)) : $path;                              // remove the scheme prefix
	$sep    = in_array($scheme, ['http://', 'https://', '//', '/']) ? '/' : DIRECTORY_SEPARATOR;  // make sure to use "/" if it's an url

	// only with url // [ ] Maybe use the $sep instead 
	$path = preg_replace('/\/{2,}/', '/', $path);  // fix the double slash
	$path = preg_replace('/\/$/', '', $path);      // remove last slash	

	// fix path for an absolute path
	if (preg_match("/\\{$sep}\.{1,2}\\{$sep}/", $path)){
		$parts    = array_filter(explode($sep, $path), 'strlen');
		$absolute = [];
		foreach ($parts as $part) {
			if ('.' == $part) continue;
			if ('..' == $part){
				array_pop($absolute);
			} else {
				$absolute[] = $part;
			}
		}
	
		$path = implode($sep, $absolute);
	}
	
	$path = $scheme.$path;	
	$path = $is_dir || is_dir($path) ? "{$path}/" : $path;

	return $path;
}
 
/**
 * Clean up and format the path and replace the "@alias" and data
 * 
 * ```php
 * to_path('@uploads/cool.png');
 * // /Users/my-computer/Documents/Websites/_sites/project/_env-local/uploads/cool.png
 * 
 * to_path('@uploads/cool.png', ':url');
 * // https://www.project.local.com/_env-local/uploads/cool.png
 * 
 * to_path('@uploads/{{ value }}/cool.png', ':url', ['value'=>45]);
 * // https://www.project.local.com/_env-local/uploads/45/cool.png
 * ```
 * @return string
 */
function ffto_to_path ($path, $args=null, $data=null){
	if (!$path) return '';

	if ($args === ':url'){
		$args = ['is_url'=>true];
	}

	$args = _args($args, [
		'is_dir' => false,
		'is_url' => false,
		'data'   => $data,
	], 'is_dir');

	// aliases
	if (strpos($path, '@') !== false){
		$aliases = _global('$directories/aliases', []);
		$path 	 = strtr($path, $aliases);
	}

	// data
	if (is_array($args['data'])){
		$path = _replace($path, $data);
	}

	$path = ffto_to_cleanpath($path, $args['is_dir']);

	if ($args['is_url']){
		$path = ffto_path_to_url($path);
	}

	return $path;
}

/**
 * Search for a file/directory with 1 or many paths. Only return a valid path.
 * 
 * Arguments shorcuts: 
 * 	- ':js' search for javascript files with "js" extension
 * 	- ':css' search for style files with "css" extension
 * 	- ':file' search for only files
 * 	- ':dir' search for only directories
 * 	- ':url' return an url
 * 	- true return the fileinfo
 *
 * ```php
 * ffto_get_path('@routes/index');
 * // "/Users/computer/Documents/Websites/_sites/project/theme/routes/index.php"
 * 
 * ffto_get_path('@routes/index', ':url');
 * // "https://www.project.local.com/theme/routes/index.php"
 * ```
 * 
 * @param array|string $path 
 * @param mixed $args 
 * 	- 'dir' [config:abspath] root directoty
 * 	- 'type' [null] filter the file type (file or directory)
 * 	- 'ext' [null] specify a default type of extension
 * 	- 'url' [false] accept external URLs, the url will be returned right away if matched
 * 	- 'data' [null] data to replace in the path
 * 	- 'version' [false] add versioning
 * 	- 'fallback' [$fallback] fallback
 * 	- 'return' [null] (url, object)
 * 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_path ($path, $args=null, $fallback=null){
	if ($args === ':js'){
		$args = ['type'=>'file', 'ext'=>'js'];
	}else if ($args === ':css'){
		$args = ['type'=>'file', 'ext'=>'css'];
	}else if ($args === ':file'){
		$args = ['type'=>'file'];	
	}else if ($args === ':dir'){
		$args = ['type'=>'dir'];	
	}else if ($args === ':url'){
		$args = ['return'=>'url'];
	}else if ($args === true){
		$args = ['return'=>'object'];
	}

	$args = _args($args, array(
		'dir'      => _config('abspath'),
		'type'     => null,
		'ext'      => null,                 // specify a default type of extension
		'url'      => false,                // accept external URLs, the url will be returned right away if matched
		'data'     => null,                 // data to replace in the path
		'version'  => false,                // add versioning
		'fallback' => $fallback,            // fallback
		'return'   => null,                 // (url, object)
	), 'return');
	
	if (!$path) return false;

	$dir   = $args['dir'];
	$ext   = $args['ext'] ? $args['ext'] : 'php';
	$paths = _array($path);
	$paths = _filters('ffto_to_path/paths', $paths);

	$_paths = [];
	foreach ($paths as $path){
		if (!$path || !is_string($path)) continue;

		// Return the URL right away
		if ($args['url'] && is_url($path)){
			return $path;
		}

		// $path 	  = urldecode($path); // make sure the path is a proper one
		$_paths[] = "{$dir}/{$path}";
		$_paths[] = "{$dir}/{$path}.{$ext}";
		$_paths[] = "{$dir}/{$path}/index.{$ext}";
		$_paths[] = "./{$path}";
		$_paths[] = "./{$path}.{$ext}";
		$_paths[] = "./{$path}/index.{$ext}";
		$_paths[] = "{$path}";
		$_paths[] = "{$path}.{$ext}";
		$_paths[] = "{$path}/index.{$ext}";
	}
	
	$is_file = $args['type'] === 'file';
	$is_dir  = $args['type'] === 'dir';
	$_path   = null;
	foreach ($_paths as $path){
		// Check first if the path works (in case it's a symlink, do not resolve to full path with realpath)
		$path = ffto_to_path($path, null, $args['data']);
		$real = realpath($path);
		$path = file_exists($path) ? $path : $real;

		if ($path && file_exists($path) && (
			!$args['type']
			|| ($is_file && is_file($path))
			|| ($is_dir && is_dir($path))
		)){
			$_path = $path;
			break;
		}
	}

	
	// fallback
	if (!$_path) return $args['fallback'];
	
	if ($args['return'] === 'url'){
		$url = ffto_path_to_url($_path);
		$url = _version($url, $args['version']);
		return $url;
	}else if ($return = $args['return']){
		$info = ffto_to_file($_path, ['version'=>$args['version']]);
		
		if (ffto_return_object($return)){
			return $info;
		}else{
			return _get($info, $return);
		}
	}
	
	return $_path;
}

/**
 * A better glob with recursive abilities when using ** in the path
 * @link https://gist.github.com/funkjedi/3feee27d873ae2297b8e2370a7082aad
 *
 * @param string $path
 * @param mixed $args
 * @param boolean $parse
 * @return array
 */
function ffto_to_glob ($path, $args=null, $parse=true){
	$args = _args($args, [
		'parse' => $parse,
		'flags' => 0,
	]);

	$path = $args['parse'] ? ffto_to_path($path) : $path;
	if (stripos($path, '**') === false) {
		$files = glob($path, $args['flags']);
	}else{
		$position  = stripos($path, '**');
		$root      = substr($path, 0, $position - 1);
		$rest      = substr($path, $position + 2);
		$paths     = array($root.$rest);
		$root     .= '/*';

		while ($dirs = glob($root, GLOB_ONLYDIR)){
			$root .= '/*';

			foreach ($dirs as $dir){
				$paths[] = $dir . $rest;
			}
		}

		$files = array();
		foreach ($paths as $p){
			$files = array_merge($files, ffto_to_glob($p, $args, false));
		}
	}

	$files = array_unique($files);
	sort($files);

	return $files;
}

/**
 * Faster way to return the mime type of a file if it has an extension.
 *
 * @param mixed $path 
 * @param mixed $args 
 * 	- 'real' [false] Use PHP function to get the mime type, by default it tries figuring out the mimetype by it's file extension
 *  - 'fallback' [false] If nothing found, what to return
 *  - 'return' [false] decode the mimetype with type and subtype
 * @return string
 */
function ffto_to_mime_type ($path, $args=null, $fallback=false){
	if (
		!is_string($path) 
		|| !file_exists($path)
	) return false;
	
	$args = _args($args, [
		'real'     => false, // open the file to get the mime/type, if not, it will check the file extension
		'fallback' => $fallback,
		'return'   => false
	], 'return');

	$mime = null;
	if (is_dir($path)){
		$mime = 'directory';
	}else if ($args['real'] || !($ext = pathinfo($path, PATHINFO_EXTENSION))){
		$file = finfo_open(FILEINFO_MIME_TYPE);  // Initialize with MIME type
		$mime = finfo_file($file, $path);
		finfo_close($file);  					 // Close to free up memory
	}else if ($ext){
		$extensions = [
			'txt'  => 'text/plain',
			'htm'  => 'text/html',
			'html' => 'text/html',
			'php'  => 'text/php',
			'css'  => 'text/style',
			'js'   => 'text/javascript',
			'json' => 'text/json',
			'xml'  => 'text/xml',
			'swf'  => 'application/flash',
			// images
			'png'  => 'image/png',
			'jpe'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/icon',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'svg'  => 'image/svg',
			'svgz' => 'image/svg',
			// archives
			'zip' => 'archive/zip',
			'rar' => 'archive/rar',
			'exe' => 'archive/exe',
			'msi' => 'archive/msi',
			'cab' => 'archive/cab',
			// audio
			'mp3'  => 'audio/mp3',
			'wav'  => 'audio/wav',
			// video
			'mp4'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogv'  => 'video/ogg',
			'qt'   => 'video/quicktime',
			'mov'  => 'video/quicktime',
			'flv'  => 'video/flv',
			// fonts
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
			'woff'  => 'font/woff',
			'woff2' => 'font/woff2',
			'eot'   => 'font/eot',
			'sfnt'  => 'font/sfnt',
			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai'  => 'application/postscript',
			'eps' => 'application/postscript',
			'ps'  => 'application/postscript',
			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			// embed provider source
			'youtube' => 'embed/video',
			'vimeo'   => 'embed/video',
		];

		// remove anything after the extension
		$ext  = preg_replace('/[?#].+$/', '', $ext);
		$mime = isset($extensions[$ext]) ? $extensions[$ext] : null;
	}

	if ($return = $args['return']){
		$pair    = explode('/', $mime);
		$type    = $pair[0];
		$subtype = isset($pair[1]) ? $pair[1] : null;

		if ($return === 'type')					$mime = $type;
		elseif ($args['return'] === 'subtype')	$mime = $subtype;
		else									$mime = ['type'=>$type, 'subtype'=>$subtype];
	}

	return $mime === null ? $args['fallback'] : $mime;
}

/**
 * Retreive a file and a bunch of about it: path, url, type, mime_type, title, filename, fullname, ext, date_created, date_modified, meta, ...
 * 
 * @param string $path 
 * @param mixed $args 
 * 	- 'meta' [false] add the meta data (with `ffto_get_file_meta`)
 * 	- 'content' [false] fetch the content of the file
 * 	- 'version' [false] add versioning to the file URL
 * @return array
 */
function ffto_to_file ($path, $args=null){
	if (!($path = ffto_get_path($path))) return false;

	if ($args === true){
		$args = ['meta'=>true];
	}else if ($args === ':full'){
		$args = ['meta'=>true, 'content'=>true];
	}

	$args = _args($args, [
		'meta'    => false,
		'content' => false,
		'version' => false,
	]);

	$is_file = is_file($path);
	$is_dir  = !$is_file;
	$name    = pathinfo($path, PATHINFO_FILENAME);
	$url     = function_exists('ffto_path_to_url') ? ffto_path_to_url($path, $args['version']) : null;
	$mime    = ffto_to_mime_type($path);
	$type    = is_dir($path) ? 'directory' : $mime;
	$sub     = null;

	// get the right mime-type 
	if (strpos($type, '/') !== false){
		$type = explode('/', $type);
		$sub  = $type[1];
		$type = $type[0];
	}

	$file = [
		'title'         => _slug($name, 'space-words'),
		'name'          => $name,
		'ext'           => $is_file ? pathinfo($path, PATHINFO_EXTENSION) : '',
		'filename'      => pathinfo($path, PATHINFO_BASENAME),
		'path'          => $path,
		'dir'           => pathinfo($path, PATHINFO_DIRNAME),
		'url'           => $url,
		'type'          => $type,
		'subtype'       => $sub,
		'mime'          => $mime,
		'is_file'       => $is_file,
		'is_dir'        => $is_dir,
		'date_created'  => filectime($path),
		'date_modified' => filemtime($path),
	];

	if ($is_file){
		if ($args['meta'] && function_exists('ffto_get_file_meta')){
			$file['meta'] = ffto_get_file_meta($path);
		}
		if ($args['content']){
			$file['content'] = ffto_get_file($path);
		}
	}

	return $file;
}

/**
 * Find a list of files/$directories. 
 *
 * ```php
 * ffto_get_files('@routes/*.php', 'return=filename');
 * // ['+layout.phg', 'index.php', 'style.php']
 * 
 * ffto_get_files('@routes/*.php', 'return=filename&join= / ');
 * // "+layout.php / index.php / style.php"
 * ```
 * 
 * @param mixed $paths
 * @param mixed $args
 * 	- 'dir' [null] root directory
 * 	- 'type' [null] (file, dir, all/null)
 * 	- 'match' [null] match like for the paths	// [ ] Maybe rename "match" for "search"
 * 	- 'deep' [null] true or number (max-depth)
 * 	- 'exclude' ['/^_|copy/'] skip files with prefix "__" or the word "copy" by default
 * 	- 'info' [false] get more fileinfo
 * 	- 'meta' [false] get the meta fileinfo
 * 	- 'content' [false] get the file content
 * 	- 'format' [null] function or array of 2 function,
 * 	- 'filter' [true] filter out invalid files
 * 	- 'reverse' [false] reverse the files
 * 	- 'tree' [false] return the files/$directories in tree format
 * 	- 'include' [false] include file (true, or ':once' to call include_once) 
 * 	- 'return' [null] return a specific value
 * 	- 'sort' [null] sort the files (with `ffto_arr_sort()`)
 * 	- 'join' [null] join the files
 * 
 * @return array|string
 */
function ffto_get_files ($paths, $args=null, $_parent=null){
	if ($args === true){
		$args['info']    = true;
		$args['meta']    = true;
		$args['content'] = true;
	}else if (ffto_is_callback($args)){
		$args['format'] = $args;	
	}else if ($args === ':include-once'){
		$args['include'] = ':once';	
	}else if ($args === ':include'){
		$args['include'] = true;	
	}
	
	$args = _args($args, [
		'dir'     => null,     
		'type'    => null,      
		'match'   => null,       
		'deep'    => false,       
		'exclude' => '/^_|copy/', 
		// 'info'    => false,       
		'meta'    => false,
		'content' => false,       
		'format'  => null,        
		'filter'  => true,        
		'reverse' => false,
		'tree'    => false,			
		'include' => false,		
		'return'  => null,
		'sort'    => null,
		'join'    => null,
	], 'dir');

	$dir         = ffto_to_path($args['dir'], true);
	$paths       = _array($paths);
	$exclude     = $args['exclude'];
	$match       = $args['match'];
	$only_file   = $args['type'] === 'file';
	$only_dir 	 = $args['type'] === 'dir';
	$format_pre  = _get($args, 'format/0 || format');
	$format_post = _get($args, 'format/1');
	$return		 = $args['return'];

	$_parent = _args($_parent, [
		'dir'	=> $dir,
		'depth' => 0,
	]);

	$_tree  = [];
	$_files = [];
	foreach ($paths as $path){
		// if the path starts with an alias, do not add the directory
		$path  = substr($path, 0, 1) === '@' ? $path : "{$dir}{$path}";
		$path  = ffto_to_path($path);
		$ext   = pathinfo($path, PATHINFO_EXTENSION);
		$files = ffto_to_glob($path);
		$count = count($files);
		
		// re-order the directories before the files (only if the search is on a directory, witout an extension)
		!$ext && usort($paths, function ($a, $b){
			$ad = is_dir($a);
			$bd = is_dir($b);
			if ($ad === $bd) 		return strnatcasecmp($a, $b);
			elseif ($ad && !$bd)	return -1;
			elseif (!$ad && $bd)	return 1;
		});

		// reverse the order
		$files = $args['reverse'] ? array_reverse($files) : $files;

		foreach ($files as $i => $filepath){
			$filename = pathinfo($filepath, PATHINFO_BASENAME);

			// skipping files
			if (
				($exclude && ffto_is_like($filename, $exclude)) 
				|| ($match && !ffto_is_like($filename, $match))
				|| ($only_file && !is_file($filepath))
				|| ($only_dir && !is_dir($filepath))
			){
				continue;
			}

			$file = ffto_to_file($filepath, [
				'meta'    => $args['meta'],
				'content' => $args['content'],
			]);

			// include file
			if ($file['is_file'] && $args['include'] === ':once'){
				include_once $filepath;
			}else if ($file['is_file'] && $args['include']){
				include $filepath;
			}
			
			// find the route, if in the @routes
			$routes  = _global('$directories/aliases/@routes');
			$route 	 = null;
			if (strpos($filepath, $routes) === 0){
				$route = '/' . str_replace([$routes,'.php'], '', $filepath);
			}

			$file = array_merge($file, [
				'index'        => $i,
				'length'       => $count,
				'depth'        => $_parent['depth'],
				'relative' 	   => str_replace($_parent['dir'], '', $filepath),
				'route'		   => $route,
				'has_children' => false,
			]);
			
			$file = _apply($format_pre, $file, $filepath, $args);
			
			// try extracting pair or $key/$value
			list($key, $file) = _extract($file);

			if ($return){
				$file = _get($file, $return);
			}

			if (!ffto_pass_filter($file, $args['filter'])) continue;

			// add the item to the files
			if ($key !== null)	$_files[$key] = $file; 
			else				$_files[] = $file;

			// go deeper
			if ($args['deep'] && $is_dir){
				$_args = array_merge($args, [
					'dir'  => $filepath,
					'deep' => is_numeric($args['deep']) ? $args['deep'] - 1 : $args['deep'],
				]);
				
				$children = ffto_get_files($paths, $_args, [
					'dir'   => $_parent['dir'],
					'depth' => $_parent['depth'] + 1,
				]);

				$file['has_children'] = count($children) > 0;
				
				if ($args['tree']){
					$file['children'] = $children;
				}else{
					$_files = array_merge($_files, $children);
				}

				_call($format_post, $file, $filepath, $args);
			}

			if ($args['tree']){
				$_tree[] = $file;
			}
		}
	}

	$files = $args['tree'] ? $_tree : $_files;

	// sort
	if (($sort = $args['sort']) && function_exists('ffto_arr_sort')){
		$sort  = ffto_is_truthy($sort) ? '*' : $sort; 
		$items = ffto_arr_sort($items, $sort);
	}

	// join
	if ($args['join'] !== null){
		$files = _string($files, null, $args['join']);
	}

	return $files;
}

/**
 * Makes sure a directory exists with the right permission for the PHP to access it.
 *
 * ```php
 * ffto_set_directory('@cache/stats');
 * ```
 * 
 * @param mixed $dir 
 * @return string
 */
function ffto_set_directory ($dir){
	$dir  = ffto_to_path($dir, true);
	$info = pathinfo($dir);

	if (isset($info['extension'])){
		$dir = $info['dirname'];
	}
	
	if (!file_exists($dir)){
		$umask = umask(0);
		mkdir($dir, 0755, true);
		umask($umask);
	}
	
	return $dir;
}

/**
 * Save a value into a file. For "json" and "csv" file extension, the content will be encoded properly.
 *
 * ```php
 * ffto_set_file('@cache/bob.txt', new DateTime(), true);
 * 
 * ffto_set_file('@cache/bob.txt', new DateTime(), ':url');
 * 
 * ffto_set_file('@cache/bob.json', [1,2,3]);
 * 
 * ffto_set_file('@cache/bob.csv', ['name'=>'John', 'age'=>40]);
 * 
 * ffto_set_file(':memory', ['name'=>'John', 'age'=>40], 'return=value');
 * // {"name":"John", "age":40}
 * 
 * ffto_set_file(':memory', ['name'=>'John', 'age'=>40], 'type=csv&return=value');
 * // name,age
 * // John,40
 * ```
 * 
 * @param mixed $path 
 * @param string $value 
 * @param mixed $args 
 * 	- 'type' [null] encoding type (options: json, csv, serialize, nothing)
 * 	- 'append' [false] append the content to the value
 * 	- 'return' [false] return a diffent value
 * 		- url : return the URL of the file
 * 		- true|object : return object, the path and the URL
 * 
 * 	shortcuts:
 *  - true = append to the file
 *  - ':url' = return the URL of the file
 * 
 * @return void
 */
function ffto_set_file ($path, $value='', $args=null){
	if ($args === true){
		$args = ['append'=>true];		
	}else if ($args === ':url'){
		$args = ['return'=>'url'];
	}

	$args = _args($args, array(
		'type'   => null,    // [null, csv, serialize, ...]
		'append' => false,
		'return' => false,	 // [path, url, value, file, object]
	), 'type');

	$path = ($path === ':memory' || $path === 'php://memory') ? 'php://memory' : ffto_to_path($path);
	$ext  = pathinfo($path, PATHINFO_EXTENSION);

	// automatic detection of file type
	if ($ext === 'csv') 		$args['type'] = 'csv';
	else if ($ext === 'json')	$args['type'] = 'json';

	$is_memory = $path === 'php://memory';
	$file      = null;
	
	if ($value === null){
		is_file($path) && unlink($path);
	}else{
		$mode = $args['append'] ? 'a' : 'w';
		$file = fopen($path, $mode);

		// make sure the folder exists
		if (!$is_memory){
			$dir = pathinfo($path, PATHINFO_DIRNAME); 
			ffto_set_directory($dir);

			// create the file
			$file = fopen($path, $mode);
			chmod($path, 0755);
		}
		
		// make sure the pointer is at the end
		if ($args['append']){
			fseek($file, 0, SEEK_END);
		}

		!$is_memory && @flock($file, LOCK_SH);
		$is_empty = !ftell($file);

		// save the content
		if ($args['type'] === 'csv'){
			$value = ffto_is_list($value) ? $value : [$value];

			// empty file, add the headers
			clearstatcache();
			if (!$args['append'] || $is_empty){
				$first   = reset($value);
    			$headers = array_keys($first);
				fputcsv($file, $headers);
			}

			// add all the rows
			foreach ($value as $row){
				fputcsv($file, $row);
			}
		}else{
			// TODO if json && append, maybe try something like adding a "," at the end. OR decode the file, merge it with the new content, then save. Or instead of "append", there could be a "merge" instead

			// encoding
			if (is_array($value) || is_object($value)){
				if ($args['type'] === 'json')			$value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				else if ($args['type'] === 'serialize')	$value = serialize($value);
				else									$value = _string($value, null, ',');
			}

			// encoding the paths (so when moving from one server to another, the paths can still work)
			$value = ffto_encode_paths($value);
			
			// append separator (only add if there's already content)
			if ($args['append'] && !$is_empty){
				$separator = is_string($args['append']) ? $args['append'] : NL;
				$value 	   = $separator.$value;
			}
			
			fwrite($file, $value);
		}			
	}

	// remove the lock
	!$is_memory && $file && @flock($file, LOCK_UN);

	// do not close the file yet for some returns
	$r = $args['return']; 
	if ($r === 'file'){
		return $file;
	}else if ($r === 'value'){
		$file && rewind($file);
		$value = $file ? stream_get_contents($file) : null;
	}

	// close the stream
	$file && fclose($file);

	if (ffto_return_object($r)){
		return [
			'path' => $path,
			'url'  => ffto_path_to_url($path),
		];
	}else if ($r === 'url'){
		return ffto_path_to_url($path);
	} 

	return $r === 'value' ? $value : $path;
}

/**
 * Get the file data.
 *
 * ```php
 * ffto_set_file('file.txt', [1,2,3]);
 * ffto_get_file('file.txt');
 * // file.txt = 1,2,3
 * 
 * ffto_set_file('file.json', [1,2,3]);
 * ffto_get_file('file.json');
 * // [1, 2, 3]
 * ```
 * 
 * @param mixed $path 
 * @param mixed $args
 * 	- 'type' [null] type of file encoding (json, csv, serialize, nothing)
 * 	- 'decode' [true] parse the value to be typed
 * 	- 'limit' [0] return a limited amount of values (for CSV) 
 * 	- 'page' [0] when using `limit`, you can choose which page of that limit (for CSV) 
 * 	- 'fallback' [$fallback]
 * 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_file ($path, $args=null, $fallback=null){
	$args = _args($args, array(
		'type'      => null,        // [null, csv, serialize, ...]
		'page'		=> 0,			// for CSV pagination
		'limit'		=> 0,			// for CSV
		'decode'	=> true,		// be able to get the value
		'fallback'  => $fallback,
	), 'type');

	$is_memory = false;
	$file      = false;
	$ext       = null;
	
	// put the string in memory (for parsing CSV generally)
	if (isset($path[':memory'])){
		$is_memory = true;
		$data      = $path[':memory'];
		$file      = ffto_set_file(':memory', $data, 'return=file');
		fseek($file, 0);
	}else{
		$path = ffto_get_path($path);
		if (!$path) return $args['fallback'];	

		// automatic detection of file type
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if ($ext === 'csv') 		$args['type'] = 'csv';
		else if ($ext === 'json')	$args['type'] = 'json';
	}
		
	$value = null;
	if ($args['type'] === 'csv'){
		// ini_set("auto_detect_line_endings", "1");
		ini_set("serialize_precision", "-1");
		
		$file = $file ? $file : fopen($path, 'r');
		@flock($file, LOCK_SH);
		if ($file !== false){
			$headers = null;
			$rows    = [];
			$page 	 = $args['page'] ? $args['page'] : 1;
			$limit 	 = $args['limit'];
			$from 	 = ($page-1) * $limit;
			$to      = $from + $limit;
			$index 	 = 0;

			// TODO move this to elsewhere maybe, the pagination could be in arr_query or something like that

			while (($row = fgetcsv($file, 0)) !== false){
				if (!$headers){
					// cleaning up the headers
					$headers = array_map(function ($v){
						$v = preg_replace("/(\n|\r|\t)/", ' ', $v);
						$v = preg_replace('/\s{2,}/', ' ', $v);
						return trim($v);
					}, $row);
					continue;
				}

				$_row = _values($row);
				$row  = [];
				foreach ($headers as $i => $v){
					$row[$v] = isset($_row[$i]) ? $_row[$i] : null;
				}

				// limit the returned results to a pagination
				if ($limit && $index < $from){
					$index++;
					continue;
				}

				$row    = $args['decode'] ? _values($row) : $row;
				$rows[] = $row;
				
				if ($limit && $index >= $to){
					break;
				}
				
				$index++;
			}
		}
		
		@flock($file, LOCK_UN);
		$value = $rows;
	}else{
		if ($file)	$value = stream_get_contents($file);
		else		$value = file_get_contents($path);
		$value = ffto_decode_paths($value);
		
		// TODO add pagination with separator option (page/limit/separator)
		// TODO maybe a JSON list of items, but seperated by a special characters, like ' ------- ', instead of a full array of objects
		
		// deal with types
		if ($args['type'] === 'json'){
			$value = _json($value, $value);
		}else if ($args['type'] === 'serialize'){
			$value = unserialize($value);
		}else{
			$value = $args['decode'] ? _value($value) : $value;
		}
	}	
	
	$file && fclose($file);

	return $value;
}

/**
 * Encode paths/urls that doesn't transfers well when moving from one server to another. The directories for @env, @root and the @root url are encoded.
 *
 * @param mixed $v 
 * @return void
 */
function ffto_encode_paths ($v, $_decode=false){
	$dirs    = _global('$directories');
	$aliases = [
		'@env-dir/'  => $dirs['paths']['env'],
		'@root-dir/' => $dirs['paths']['root'],
		'@root-url/' => $dirs['urls']['root'],
	];
	
	$_encode = function ($t) use ($aliases, $_decode){
		foreach ($aliases as $alias => $path){
			$from = $_decode ? $alias : $path;
			$to   = $_decode ? $path : $alias;
			$t    = str_replace($from, $to, $t);
		}
        return $t;
    };

	$_walk = function ($t, $_walk) use ($_encode){
        if (is_string($t)){
            $t = $_encode($t);
        }else if (is_array($t)){
            foreach ($t as $i => $v){
                $t[$i] = $_walk($v, $_walk);
            }
        }

        return $t;
    };

    return $_walk($v, $_walk);
}

/**
 * Decode the paths/urls.
 *
 * @param mixed $v 
 * @return void
 */
function ffto_decode_paths ($v){
	return ffto_encode_paths($v, true);
}

// =====================================================================================================================
// Server
// =====================================================================================================================
/**
 * Return the IP addres of the user. Tries many ways to get the proper IP.
 *
 * @todo maybe cache this value in the global
 * @param bool $anonymize Should the IP be less specific
 * @return string
 */
function ffto_to_ip ($anonymize=false){
	$user_ip       = _get($_SERVER, 'REMOTE_ADDR');
	$client_ip     = _get($_SERVER, 'HTTP_CLIENY_IP');
	$proxy_ips     = _get($_SERVER, 'HTTP_X_FORWARDED_FOR', [], '_array');
	$cloudflare_ip = _get($_SERVER, 'HTTP_CF_CONNECTING_IP');
	
	$ip_address = $client_ip ? $client_ip : (count($proxy_ips) ? current($proxy_ips) : ($cloudflare_ip ? $cloudflare_ip : $user_ip));
	$ip_address = trim($ip_address);

	if ($anonymize){
		$ip_address = preg_replace('/\.\d*$/', '.000', $ip_address);
		$ip_address = preg_replace('/[\da-f]*:[\da-f]*$/', '0000:0000', $ip_address);
	}

	return $ip_address;
}

/**
 * Check if the current server is using HTTPs
 *
 * @return bool
 */
function ffto_is_https (){
	return _get($_SERVER, 'REQUEST_SCHEME') === 'https'
		|| _get($_SERVER, 'HTTP_X_FORWARDED_PROTO') === 'https'
		|| _get($_SERVER, 'HTTPS') === 'on'
		|| _get($_SERVER, 'SERVER_PORT') == 443;
}

/**
 * ffto_to_url
 *
 * ```php
 * ffto_to_url();
 * // 'https://www.project.local.com/'
 * 
 * ffto_to_url('list/45');
 * // 'https://www.project.local.com/list/45'
 * 
 * ffto_to_url('list/45?color=red');
 * // 'https://www.project.local.com/list/45/?color=red'
 * 
 * ffto_to_url('list/45?color=red', null, 'background=blue');
 * // https://www.project.local.com/list/45/?color=red&background=blue
 * 
 * ffto_to_url('list/45?color=red', false, 'background=blue');
 * // https://www.project.local.com/list/45/?background=blue ($args=false means $args['query']=false and that remove the old query)
 * 
 * ffto_to_url(null, 'search=Bob');
 * // https://www.project.local.com/#:~:text=Bob
 * 
 * ffto_to_url(null, ['port'=>true], 'search=Bob');
 * // https://www.project.local.com:443/?search=Bob
 * 
 * ffto_to_url('list/45?color=red', null, ['color'=>null]);
 * // https://www.project.local.com/list/45/
 * 
 * ffto_to_url('bob@project.com');
 * // mailto:bob@project.com
 * 
 * ffto_to_url('list/45?color=red', 'host=bob.com');
 * // https://bob.com/list/45/?color=red
 * ```
 * @todo add support for detecting a phone $url and add 'tel:' scheme, 'mailto:' also
 * 
 * @param mixed $url 
 * @param mixed $args 
 * 	- 'host' 	[null] remove the host (by setting false) OR update the host
 * 	- 'query' 	[null] set to false to remove the old one OR specify a string/array to overwrite, null will keep the current page query
 * 	- 'anchor' 	[null] set an anchor to the url, false will remove it if the current URL is used
 * 	- 'search' 	[null] add a search anchor (eg.: #:~:text=Bob)
 * 	- 'port' 	[null] specify OR force the visibility of the port (if true)
 * 	- 'slash' 	[true] always add an end slash after the path
 *  - 'current' [true] fill the empty keys with the current URL
 * 	- 'return' 	[null] return a specific key
 * 
 * @param mixed $query Shortcut to set the query of the url
 * @return mixed
 */
function ffto_to_url ($url=null, $args=null, $query=true){
	if (function_exists('ffto_is_email') && ffto_is_email($url)){
		$url = "mailto:{$url}";
	}else if (function_exists('ffto_path_to_url') && ($path = ffto_get_path($url, ':file'))){
		$path = ffto_path_to_url($path);
	}

	if (is_string($url)){
		$url = parse_url($url);
	}
	
	$url = _args($url, [
		'scheme'   => '',
		'host'     => '',
		'port'     => null,
		'path'     => '',
		'query'    => null,
		'fragment' => null,
	]);
	
	$args = _args($args, [
		'host'	   => null,
		'query'    => null,
		'anchor'   => null,
		'search'   => null,
		'port'     => null,
		'slash'    => true,
		'current'  => true,
		'absolute' => _config('absolute_url', true),
		'version'  => false,
		'return'   => null,
	], 'query');
	
	// try decoding the path, if it's missing it's scheme, check if has a common domain extension
	if (!$url['scheme'] && $url['path']){
		$ext    = _match($url['path'], '/(?:[a-z0-9]+\.)?(?:[a-z0-9][a-z0-9-]+[a-z0-9]\.)([a-z]+)?/');
		$common = _config('domain_ext', '');
		$common = _array($common);
		$common = array_merge($common, ['com','org','net','edu','gov','info','biz','io','ca','uk']);

		if (in_array($ext, $common)){
			$_url = (ffto_is_https() ? 'https' : 'http') . '://' . $url['path'];
			$_url = parse_url($_url);
			$url  = array_merge($url, ['path'=>''], $_url);
		}
	}
	
	if ($args['current']){
		$url['scheme'] = $url['scheme'] ? $url['scheme'] : (ffto_is_https() ? 'https' : 'http');
		$url['host']   = $url['host'] ? $url['host'] : _get($_SERVER, 'HTTP_HOST || SERVER_NAME');
		$url['port']   = $url['port'] ? $url['port'] : _get($_SERVER, 'SERVER_PORT');
	}
	
	// specify a port
	if (is_numeric($args['port'])){
		$url['port'] = $args['port'];
	}

	// path --------------------------------------------------------------------
	if (ffto_is_falsy($args['host'])){
		$url['host'] = '';
	}else if ($args['host']){
		$url['host'] = $args['host'];
	}

	if (!$url['path'] && $args['current']){
		$uri             = _get($_SERVER, "REQUEST_URI");
		$uri             = parse_url($uri);
		$url['path']     = _get($uri, 'path');
		$url['query']    = _get($uri, 'query', $url['query']);
		$url['fragment'] = _get($uri, 'fragment', $url['fragment']);
	}

	// anchor ------------------------------------------------------------------
	// adding a search anchor
	if ($args['search']){
		$url['fragment'] = ':~:text=' . urlencode($args['search']);
	}else if ($args['anchor'] === false){
		$url['fragment'] = false;
	}elseif (is_string($args['anchor'])){
		$url['fragment'] = $args['anchor'];
	}
	
	// query -------------------------------------------------------------------
	// TODO review and re-test this code, it's not working properly

	// clear the query
	if (ffto_is_falsy($args['query'])){
		$url['query'] = [];
	// set the query with the $args['query']
	}else if(is_array($args['query'])){
		$url['query'] = $args['query'];
	// decode the $args query
	}else if (is_string($args['query'])){
		parse_str($args['query'], $_query);
		$url['query'] = $_query;
	// decode the current query
	}else if ($url['query']){
		parse_str($url['query'], $_query);
		$url['query'] = $_query;
	}else{
		$url['query'] = [];
	}
	
	// merge the new query with the current
	if ($query){
		is_string($query) && parse_str($query, $query);
		$query = is_array($query) ? $query : [];
		$query = array_merge($url['query'], $query);
	}

	// clean query (remove undefined)
	$query = array_filter($query, function ($v){ return ffto_is_defined($v); });
	
	// this can be used to add a "lang" value (either in the path, or the query) when the site can be multilangual
	$url = _filters('ffto_to_url/url', $url, $args);

	// clean host and path
	$host      = preg_replace('/^\/|\/$/', '', trim($url['host']));
	$path      = ffto_to_cleanpath($url['path']);
	$path      = preg_replace('/^\/|\/$/', '', $path);
	$port      = $url['port'] && ($args['port'] || !in_array($url['port'], [80,443])) ? ":{$url['port']}" : '';
	$query	   = is_array($query) ? http_build_query($query) : '';
	
	// slashes
	$ext 	   = $url['path'] ? pathinfo($url['path'], PATHINFO_EXTENSION) : null;
	$end_slash = (!$ext && $args['slash'] ? '/' : '');
	$slashes   = '//';

	// http scheme
	$http_scheme = preg_match('/^https?$/', $url['scheme']);
	if ($http_scheme){
		$path = $path ? "/$path" : '';
	}else{
		$slashes   = '';
		$host      = '';
		$port      = '';
		$end_slash = '';
	}

	// merge all the values together for a full url
	$full = ($host ? ($url['scheme'] . ':' . $slashes . $host . $port) : '') . $path . $end_slash .
			($query ? "?{$query}" : '') . 
			($url['fragment'] ? "#{$url['fragment']}" : '');

	$full = _version($full, $args['version']);

	// make the url relative instead
	if (!$args['absolute']){
		$host = ffto_get_host();
		$full = str_replace($host, '/', $full);
	}

	$url['full'] = $full;

	if (ffto_return_object($args['return'])){
		return $url;
	}else if ($args['return']){
		return _get($url, $args['return']);
	}

	return $url['full'];
}

/**
 * Check if the server is a local environment. Either if the remote address is localhost, or if the server name match the "$config.host_local" value
 *
 * @return bool
 */
function ffto_is_local (){
	$server = $_SERVER['SERVER_NAME'];
	$hosts  = _config('host_local', '');
	return $_SERVER['REMOTE_ADDR'] == '127.0.0.1' 
		|| $_SERVER['REMOTE_ADDR'] == '::1'
		|| ($hosts && strpos($server, $hosts) !== false);
}

/**
 * Check if the server is a staging environment. If the server name match the "$config.host_staging" value
 *
 * @return bool
 */
function ffto_is_staging (){
	$server = $_SERVER['SERVER_NAME'];
	$hosts  = _config('host_staging', '');
	return $hosts && strpos($server, $hosts) !== false;
}

/**
 * Check if the server is the LIVE one
 *
 * @return bool
 */
function ffto_is_live (){
	return !ffto_is_local() && !ffto_is_staging();
}

/**
 * Check if the server is the DEV one (local or staging)
 *
 * @return bool
 */
function ffto_is_dev (){
	return ffto_is_local () || ffto_is_staging();
}

/**
 * Get the server environment
 *
 * @return string
 */
function ffto_get_environment (){
	$value = defined('ENVIRONMENT') ? ENVIRONMENT : null;

	if (!$value){
		if (ffto_is_local())		$value = 'local';
		else if (ffto_is_staging())	$value = 'staging';
		else						$value = 'live';
	}

	return $value;
}

/**
 * Return the host of the current server
 * 
 * @param mixed $args 
 * 	- 'port' [false] add the port in the host
 * 	- 'slash' [true] add final slash to the host
 * @return string
 */
function ffto_get_host ($args=null){
	$args = _args($args, array(
		'port'  => false,
		'slash' => true,
	), 'slash');

	$host = _config('host');
	if (!$host){
		$scheme = ffto_is_https() ? 'https://' : 'http://';
		$host 	= _get($_SERVER, 'HTTP_HOST || SERVER_NAME');
		$host   = $scheme.$host;
	}

	// port 80 & 443 are the default http/https ports
	if ($port = $args['port']){
		$port = $port === true ? $_SERVER['SERVER_PORT'] : $port;
		$host = $host.':'.$port;
	}

	if ($folder = _config('host_folder')){
		$host = $host.'/'.$folder;
	}

	// cleanup the path
	$host = ffto_to_cleanpath($host, $args['slash']);
	
	return $host;
}

// =====================================================================================================================
// Text
// =====================================================================================================================


// =====================================================================================================================
// Debug
// =====================================================================================================================
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
 *      - 'view' [screen] Type of media view to output to (screen, error, js, csv)
 *      - 'type' [LOG] Add a prefix pill before the output
 *      - 'title' [true] Specify a title OR use the first parameter (if possible) as the title
 *      - 'trace' [true] Add the trace paths
 *      - 'color' [#000] Set color for the type prefix and title
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
		'view'  => true,
		'type'  => 'LOG',
		'title' => true,
		'trace' => true,
		'color' => '#000',
	], 'view');

	// pre-defined styles
	$first = reset($params);
	$style = is_string($first) && isset($first[0]) && $first[0] === ':' ? $first : '';
	$icon  = null;
	if (in_array($style, [':info',':warn',':error',':deprecated',':trace'])){
		array_shift($params);

		if ($style === ':info'){
			$args['type']  = 'INFO';
			$args['color'] = '#3e9d00';
			$icon          = 'ℹ️';
		}else if ($style === ':warn'){
			$args['type']  = 'WARN';
			$args['color'] = '#f58303';
			$icon          = '⚠️';
		}else if ($style === ':error'){
			$args['type']  = 'ERROR';
			$args['color'] = '#FF0000';
			$icon          = '❌';
		}else if ($style === ':deprecated'){
			$args['type']  = 'DEPRECATED';
			$args['color'] = '#6534AF';
		}else if ($style === ':trace'){
			$args['trace']  = _backtrace([$trace_start, 0], true);
			$args['trace']  = implode(NL, $args['trace']);
		}
	}

	// TODO alter the default `type` with a "_config()" property or something like that
	$view  = $args['view']  === true ? 'error' : $args['view'];
	$trace = $args['trace'] === true ? _backtrace($trace_start, true) : $args['trace'];
	$type  = $args['type'];
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
	
	if ($view === 'error'){
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

		// use the icon instead of the $type        
		if ($icon){
			$type = $icon;
		}else if ($type){
			$type = "[{$type}]";
		}

		if ($type && ffto_is($title))  	$msg[] = "{$type} {$title}" . NL;
		else if ($type)        		 	$msg[] = "{$type}" . NL;
		else if (ffto_is($title))       $msg[] = "{$title}" . NL;

		if ($params){
			$msg[] = $params . NL;
		}

		$msg = implode(NL, $msg);
		$msg = preg_replace("/\n/", NL.TAB, $msg);
		
		error_log($msg);
	}else if ($view === 'screen'){
		$wrap  = _get($args, 'wrap', true);
		$style = _get($args, 'style', '');

		// get the $params from `var_dump()`
		if (count($params)){
			ob_start();
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
			':'margin:5px;').'
			position:relative; 
			z-index:999; '.
			$style.
		'">';
		
			$xml = ['<'=>'&lt;', '>'=>'&gt;'];
			
			if ($trace){
				echo '<div style="opacity:0.5; margin-bottom:0.5em;">'.$trace.'</div>';
			}
			if ($type){
				echo '<span style="
					background:'.$color.'; 
					display:inline-block; 
					color:white; 
					border-radius:2px; 
					font-weight:bold !important; 
					font-size:0.8em; 
					line-height:1; 
					margin-right:1em; 
					padding:.4em .7em .3em;
				">'.$type.'</span>';
			}
			if (ffto_is($title)){
				$title  = strtr($title, $xml);
				echo '<strong style="display:inline-block; color:'.$color.';">'.$title.'</strong>';
			}
			if ($params){
				$params = strtr($params, $xml);
				
				echo '<div style="margin-top:0.5em;">'.$params.'</div>';
			}
		echo '</pre>';
	}else if ($view === 'js'){
		$group    = _get($args, 'group', '*');
		$minimize = _get($args, 'minimize', true);
		$key      = "js_logs/{$group}";
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
				'trace' => $trace,
				'type'  => $type ? $type : "PHP",
				'title' => $title,
				'color' => $color,
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
	}else if ($view === 'csv'){
		$path = _get($args, 'path', '@cache/logs/{today}.csv');
		$path = _replace($path, [
			'today' => date('Y-m-d'),
			'month' => date('Y-m'),
			'year'  => date('Y'),
			'week'  => 'week-'.date('W'),
		]);
		 
		// format the first value
		$first = reset($params);
		if ($first && is_object($first) && !$title){
			$title = get_class($first);
			$title = 'object('.$title.')';
		}

		$params = json_encode($params);
		// $params = preg_replace('/^\[|\]$/','', $params);

		$data = _get($args, 'data');
		if ($data){
			$data = is_array($data) ? $data : ['value'=>$data];
			$data = array_merge([
				'timestamp' 	=> date('Y-m-d H:i:s'),
				'timestamp_utc' => gmdate('Y-m-d H:i:s'),
			], $data);
		}else{
			$data = [
				'timestamp' 	=> date('Y-m-d H:i:s'),
				'timestamp_utc' => gmdate('Y-m-d H:i:s'),
				'ip_address'	=> ffto_to_ip(true),
				'session_id'	=> session_id(),
				'url'			=> ffto_to_url(),
				'referer'		=> _get($_SERVER, 'HTTP_REFERER'),
				'method'		=> _get($_SERVER, 'REQUEST_METHOD'),
				'type'			=> $type,
				'title'			=> $title,
				'params'	    => $params,
			];
		}
		
		ffto_set_file($path, $data, true);
	}else{
		// other types: a better JSON visualize, a table, ... other types
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
 * A simpler version from `_p()`
 *
 * @return void
 */
function __p (){
	_log([
		'view'  => 'screen',
		'trace' => false,
		'wrap'  => false,
	], func_get_args(), 2);
}

/**
 * Output log to the Javascript console
 */
function _js (){
	// TODO only add if localhost
	_log('js', func_get_args(), 2);
}

// [ ] What was this function for? to always show a JS message, even if we're not in LOCALHOST??
// function _msg (){
// 	_log('js', func_get_args(), 2);
// }

/**
 * Output log to the error logs
 */
function _err (){
	// TODO only add if localhost
	// if (!ffto_is_dev()) return false;
	_log('error', func_get_args(), 2);
}

/**
 * Same as _err() for backward compability 
 */
function __err (){
	_log('error', func_get_args(), 2);
}

/**
 * Add an automatic log to a CSV file (with a bunch of automatic values)
 *
 * @return void
 */
function _csv (){
	_log('csv', func_get_args(), 2);
}

/**
 * Track in a CSV file specific data. The first 2 columns are timestamp/timestamp_utc and the rest is the data passed.
 *
 * ```php
 * _track('@cache/logs/test-{today}.csv', ['name'=>'Bob', 'age'=>40]);
 * ```
 * 
 * @param string $path
 * @param mixed $data
 * @return void
 */
function _track ($path, $data){
	_log([
		'view' => 'csv',
		'path' => $path,
		'data' => $data,
	], null, 2);
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
		if ($msg === STOP){
			_ctx('debug_logs', null);
		}else{
			_ctx('debug_logs', $type);
		}
		return;
	}

	// it hasn't been turned on, so skip
	$debug = _ctx('debug_logs');
	if (!$debug) return;
	
	_log([
		'type'  => 'DEBUG',
		'view'  => $debug,
		'color' => 'gray',
	], func_get_args(), 2);
}

/**
 * Super simple error log function.
 *
 * @deprecated 
 * @param mixed $v 
 * @return void
 */
function _r (){
	$params = func_get_args();
	$v      = count($params) === 1 ? $params[0] : $params;

	ob_start();
	var_dump($v);
	$v = ob_get_clean();

	$v = strtr($v, array(
		'\\/' => '/',
		'\\"' => '"',
	));

	error_log(NL . NL . $v . NL);
}

// =====================================================================================================================
// Config
// =====================================================================================================================
$ffto_start_memory = memory_get_peak_usage();
$ffto_start_time   = microtime(true);

function _config ($key=null, $value=null, $save=false){
	$config = _global('$config');
	$set    = is_array($key) || $save ? ffto_to_pair($key, $value) : [];

	// default setup of options
	if ($config === null){
		_global('$config', $set, true);

		$abspath = defined('ABSPATH') ? ABSPATH : __DIR__;
		$env 	 = ffto_get_environment();
		
		// default config options (these are fixed values, can't be edited in other ways)
		ffto_config_add_option('abspath', null, $abspath);
		ffto_config_add_option('domain', null, 'theme');
		ffto_config_add_option('host_local', null, '.local');
		ffto_config_add_option('host_staging', null, '.dev');
		ffto_config_add_option('host_folder', null, '');
		ffto_config_add_option('host', null, ffto_get_host());
		ffto_config_add_option('env', null, $env);
		ffto_config_add_option('dir', 'merge=1&update=ffto_config_set_directories', [
			'root'    => '',
			'theme'   => 'theme/',
			'data'    => '+data/',
			'env'     => "+data-{$env}/",
			'cache'   => '@env/cache/',
			'uploads' => '@env/uploads/',
			'assets'  => '@theme/assets/',
			'images'  => '@assets/images/',
			'fonts'   => '@assets/fonts/',
			'scripts' => '@assets/js/dist',
			'styles'  => '@assets/style/dist/',
			'php'     => '@assets/php/',
			'core'    => '@assets/php/core/',
			'items'	  => '@theme/+items/',
			'routes'  => '@theme/routes/',
			'api'     => '@theme/routes-api/',
			'admin'   => '@theme/routes-admin/',
			'dir'	  => '', 						// TODO current directory, should be generated from the current route directory for example, ...
		]);

		// [ ] Be able to to use the "./" for path in the current __FILE__ path

		// config file is now available, so things can be fetched/saved to it
		$config_file = ffto_get_file('@env/config.json', null, []);
		_global('$config_file', $config_file, true);

		// site versioning
		ffto_config_add_option('version', 'save=1&update=ffto_config_set_version', '1.0.0');

		// include the core files.
		ffto_get_files([
			'*.php',
			'utils/*.php',
			'utils/**/*.php',
			'**/*.php',
		], [
			'dir'     => '@core',
			'include' => ':once',
		]);
		
		_actions('site/init', $set);

		$config = _global('$config');
		$set    = null;

		_actions('site/startup');

		add_action('site/shutdown', 'ffto_shutdown_config_save_file', 999);

		/*
		// Little stats testing
		global $ffto_start_memory;
		global $ffto_start_time;
		$memory = memory_get_peak_usage() - $ffto_start_memory;
		$time   = microtime(true) - $ffto_start_time;
		$memory = ffto_to_human_bytes($memory);
		$time   = ffto_to_human_time($time);
		_err("Memory: {$memory} - Time: {$time}");
		*/
	}
	
	// set many values
	if ($set){
		$options = _global('$config_options', []);

		// go through all the options and try setting the coresponding formatter 
		$_set = [];
		foreach ($set as $i => $v){
			// $option = isset($options[$i]) ? $options[$i] : null;
			$option = _get($options, $i);
			$option = $option ? ffto_config_set_option($i, $v, null, $option) : $v;
			_set($_set, $i, $option);
		}

		// save in the config all the config (since there might be keys without a set option)
		$config = array_merge($config, $_set);
		_global('$config', $config, true);

		return $_set;
	// get a value
	}else if ($key){
		return _get($config, $key, !$save ? $value : null);
	}	

	return $config;
}

function ffto_config_add_option ($key, $option=null, $default=null, $desc=null){
	$config_key = '$config/'.$key;
	$option_key = '$config_options/'.$key;
	
	if (is_callable($option)){
		$option = ['update'=>$option];
	}
	$option = _args($option, [
		'update'      => null,
		'merge'       => false,   // merge previous values, for objects
		'save'        => null,    // save in file the value
		'default'	  => $default,
		'description' => $desc,
	], 'update');

	// save the option
	_global($option_key, $option, true);	
		
	// set and save the value, first check in the 
	$value = _global($config_key, $default);		// check first if the value is defined in the 
	$value = ffto_config_set_option($key, $value, $option);

	_global($config_key, $value, true);	

	return $value;
}

function ffto_config_set_option ($key, $value, $_option=null){
	$option_key = '$config_options/'.$key;
	$option     = $_option ? $_option : _global($option_key);

	// check if there's a registered option to set the value
	if (!$option){
		return $value;
	}

	// merge the default with the value
	if ($option['merge'] && is_array($value)){
		$current = is_array($option['default']) ? $option['default'] : [];
		$value   = array_merge($current, $value);
	}

	// call an update function
	if ($option['update']){
		$response = $option['update']($value, $option);
		if (isset($response)){
			$value = $response;
		}
	}
	
	// save value to a file (the "dirs" needs to set first)
	if (
		($save_key = $option['save']) 
		&& ($file = _global('$config_file')) !== null
	){
		$save_key = is_string($save_key) ? $save_key : $key;
		// $previous = isset($file[$save_key]) ? $file[$save_key] : null;
		$previous = _get($file, $save_key);
				
		if ($previous != $value){
			// $file[$save_key] = $value;
			_set($file, $save_key, $value);
			_global('$config_file', $file, true);
			_ctx('config_file_dirty', true);
		}
	}

	return $value;
}

/**
 * With a list of directories, this function will resolve all the paths, create multiple aliases, paths and urls.
 *
 * @param mixed $dir 
 * @param mixed $args 
 * @return void
 */
function ffto_config_set_directories ($dir){
	$abspath = _config('abspath');
	$aliases = [];
	$paths   = [];
	$urls    = [];
	$host	 = ffto_get_host(false);

	// first make sure the aliases exists
	foreach ($dir as $i => $v){
		$key           = '@' . str_replace('@', '', $i);
		$aliases[$key] = $v;
	}

	// resolve all the aliases/paths/urls
	foreach ($aliases as $i => $v){
		$key  = str_replace('@', '', $i);
		$v    = strtr($v, $aliases);
		$v    = '/' . preg_replace('/^\/|\/$/', '', $v) . '/';  // make sure there's a slash at beginning and end
		$path = $abspath . str_replace($abspath, '', $v);
		$path = preg_replace('/\/{2,}/', '/', $path);           // only 1 slash at a time
		$url  = $host . str_replace($abspath, '', $path);

		$aliases[$i] = $path;
		$paths[$key] = $path;
		$urls[$key]  = $url;
	}

	_global('$directories', [
		'aliases' => $aliases,
		'paths'   => $paths,
		'urls'    => $urls,
	], true);
}

/**
 * When setting the version of the site, we can check if it has updated.
 *
 * @param mixed $version 
 * @return void
 */
function ffto_config_set_version ($version){
	$file = _global('$config_file');
	$old  = _get($file, 'version');

	// change the site context "is_new_version" to know there might be things to update
	if (!$old || version_compare($old, $version, '<')){
		_ctx('is_new_version', true);
	}

	return $version;
}

/**
 * When the PHP process is done, the config.json file can be updated (if anything changed)
 *
 * @return void
 */
function ffto_shutdown_config_save_file (){
	$dirty = _ctx('config_file_dirty');	
	if (!$dirty) return;
	
	$file = _global('$config_file');
	$file = array_merge([
		'timestamp' => gmdate('Y-m-d H:i:s e'),	// keep the last time something changed
	], $file);
	
	ffto_set_file('@env/config.json', $file);
}

/**
 * Shutdown of the PHP processing
 */
register_shutdown_function(function (){ 
	_actions('site/shutdown'); 
});