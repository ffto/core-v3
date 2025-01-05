<?php
/**
 * Utils
 * 
 * List of helper functions used for building websites
 * 
 * @package Utils
 * @author 	Maxime Lefrancois
 * @version 2.2.0
 **/

$api_version = '2.2.0';

// Constants -------------------------------------------------------------------
/** Newline character constant */
define('NL', "\n"); 

/** Tab character constant */
define('TAB', "\t"); 

define('START', '► ⚀ ⚁ ⚂ START ⚃ ⚄ ⚅ ◄');
define('START_ERROR', '► ⚀ ⚁ ⚂ START ERROR ⚃ ⚄ ⚅ ◄');
define('STOP', '► ⚀ ⚁ ⚂ STOP ⚃ ⚄ ⚅ ◄');

/** Special key used in some functions */
define('KEY', '► ⚀ ⚁ ⚂ ⚃ ⚄ ⚅ ◄'); 
define('BREAK_LOOP', '► ⚀ ⚁ ⚂ BREAK ⚃ ⚄ ⚅ ◄'); 
define('CONTINUE_LOOP', '► ⚀ ⚁ ⚂ CONTINUE ⚃ ⚄ ⚅ ◄'); 

/** Text separator used for special encoded files  */
define('FILE_SEPARATOR', "\n----------\n");

// Helpers ---------------------------------------------------------------------
/**
 * Update a value on an item. Will update by property/index/method or use a callback function
 *
 * ```php
 * $item = ['a' => 135];
 * set_value($item, 'b', 999);
 * // $item = ['a' => 135, 'b' => 999]
 * ```
 * 
 * @param  mixed $item	
 * @param  mixed $key
 * @param  mixed $value
 * @return mixed 
 */
function set_value (&$item, $keys, $value){
	$path  = is_string($keys) ? explode('.', trim($keys)) : (is_array($keys) ? $keys : array($keys));
	$count = count($path);
	
	$walk = function (&$v, $callback, $i=0) use ($path, $count, $value){
		$last = $i === $count - 1;
		$p 	  = $path[$i];

		// get the value
		if (!$last){
			if 		(is_array($v) && array_key_exists($p, $v))	$value = $v[$p]; 
			elseif 	(is_object($v) && property_exists($v, $p))	$value = $v->$p;
			else 												$value = array();
		}

		// update the value
		if (is_callback($p))	$p($v, $value);
		elseif (is_array($v))		$v[$p] = $value; 
		elseif (is_object($v))		$v->$p = $value;

		// good deeper
		if (!$last){
			if 		(is_array($v))	$callback($v[$p], $callback, $i + 1); 
			elseif 	(is_object($v))	$callback($v->$p, $callback, $i + 1);
		}

		return $v;
	};

	$item = $walk($item, $walk);
	
	return $item;	
}

/**
 * Shortcut to set_value
 */
function _set (&$item, $keys, $value){
	return set_value($item, $keys, $value);
}

/**
 * Return the value of an item, goes through multiple ways to access a value.
 * 
 * ```php
 * $item  = ['a' => 135];
 * $value = get_value($item, 'a');
 * // 135
 * 
 * $item  = new DateTime('2020-01-01');
 * $value = get_value($item, 'getTimestamp');
 * // 1577854800
 * ```
 * 
 * TODO turn the $key into an array, to try to find the first defined attribute
 * 
 * @param  mixed $item		Item to find the value
 * @param  mixed $key		Key attribute/index/function/... on the item. Can be an array for finding the first defined key
 * @param  mixed $fallback 	Fallback value to the return
 * 
 * @return mixed
 */
function get_value ($item, $keys, $fallback=null, $args=null){
	if (!is($keys)) return $fallback;

	$args = _args($args, [
		'empty_allowed' => true,
	], 'empty_allowed');

	$keys  = is_array($keys) ? $keys : (is_string($keys) ? explode(',', $keys) : array($keys));
	$value = null;

	foreach ($keys as $key){
		$path  = is_string($key) && strpos($key, '[') !== false ? strtr($key, ['['=>'.', ']'=>'']) : $key;
		$path  = is_string($path) ? explode('.', trim($path)) : array($path);
		$count = count($path);
		
		$v = $item;
		for ($i=0, $l=$count; $i<$l; ++$i){
			$p = $path[$i];

			if (is_callback($p, true)){
				$v = $p($v, $fallback);
			}elseif (is_array($v) && array_key_exists($p, $v)){
				$v = $v[$p];
			}elseif (is_object($v) && method_exists($v, $p)){
				$v = $v->$p();
			}elseif (is_object($v) && property_exists($v, $p)){
				$v = $v->$p;
			}elseif (is_object($v) && method_exists($v, '__get')){
				try{ $v = $v->$p; }catch(Exception $e){};
			}else{
				$v = null;
			}

			// empty values are not valid 
			if (!is_defined($v)){
				$v = null;
			}

			if ($i === $l-1){
				$value = $v;
			}else if ($v === null){
				break;
			}
		}

		if (!$args['empty_allowed'] && empty($value)){
			$value = null;
		}

		if ($value !== null){
			return $value;
		}
	}

	return $fallback;
}

/**
 * Shortcut to get_value
 */
function _get (){
	return call_user_func_array('get_value', func_get_args());
}

/**
 * Call a function with a list of $params. Return the first param if the callback isn't valid
 * 
 * ```php
 * apply(function ($v, $v2){ return $v . ' is ' . $v2 . '!'; }, ['joe', '40']); 
 * // "joe is 40!"
 * 
 * apply('not a function', ['joe', '40']);
 * // "joe"
 * 
 * function add ($v){ return $v + 100; }
 * function mul ($v){ return $v * 100; }
 * apply(['add', 'mul'], [50]); 
 * // 15000
 * ```
 *
 * @param mixed $false_response 	Overwrite the $first param if the $response isn't returned // ??? what is this?
 * @param mixed $callback 
 * 
 * @return mixed
 */
function apply ($callbacks=null, $params=null, $args=''){
	$args = _args($args, array(
		'ctx'      => null,
		'null'     => false,
		'validate' => null,
		// 'fallback' => null,	// if none of the callbacks are valid
	));
	// ), 'fallback');

	$params = is_array($params) ? $params : array();

	if (array_key_exists('fallback', $args)){
		$value = $args['fallback'];
	}else{
		$value = empty($params) ? null : reset($params); 
	}

	// add context to the callbacks
	if ($args['ctx']){
		$callbacks = is_array($callbacks) ? $callbacks : array($callbacks);
		$callbacks = array_map(function ($v) use ($args){
			return array($args['ctx'], $v);
		}, $callbacks);
	}

	// Go through an array of callbacks (if valid)
	if (is_callable($callbacks, false) || is_array($callbacks)){
		$callbacks = is_array($callbacks) && !is_callable($callbacks) ? $callbacks : array($callbacks);

		foreach ($callbacks as $callback){
			if (
				!is_callable($callback) ||
				($args['validate'] && !$args['validate']($callback))			
			) continue;

			$response = call_user_func_array($callback, $params);

			if (isset($response) || $args['null']){
				$params[0] = $value = $response;
			};
		}		
	}

	return $value;
}

/**
 * Call ftto_apply with an unknown amount of parameters. 
 *
 * @param mixed $callbacks 
 * @return void
 */
function _apply ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return apply($callbacks, $params);
}

/**
 * Call ftto_apply with an unknown amount of parameters. If the callbacks return nothing, will return "null" instead of the first parameter
 *
 * @param mixed $callbacks 
 * @return void
 */
function _call ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return apply($callbacks, $params, ['fallback'=>null]);
}

/**
 * Helper for function that can return an object. Usually used with $args that are parsed with to_args()
 *
 * @param mixed $v
 * @return boolean
 */
function return_object ($v){
	return $v === 'object' || $v === true;
}

/**
 * Simple way of returning html and printing it to the screen.
 *
 * @param mixed $html 
 * @param bool $echo 
 * @return void
 */
function e ($html, $echo=true){
	if ($echo && (is_string($html) || is_numeric($html))){
		echo $html;
	}
	return $html;
}

// String ----------------------------------------------------------------------
/**
 * Parse a string and replace all instances of variables with a value (if found) in the $data. The variables
 * uses this pattern {{ variable_name }}. Has the option of alternative variable and a default value if none is found.
 * 
 * ```php
 * string_replace('{{ name }} is {{ age }} and {{ child_count }}', ['name' => 'John', 'age' => 40]);
 * // "John is 40 and {{ child_count }}"
 * 
 * string_replace('{{ name }} is {{ age }} and {{ child_count, count }}', [
 * 	'name'  => 'John',
 * 	'age'   => 40,
 * 	'count' => 3,
 * ]);
 * // "John is 40 and 3"
 * 
 * string_replace('{{ name }} is {{ age | 44 }}', [
 * 	'name'  => 'John',
 * ]);
 * // "John is 44"
 * ```
 *
 * @param string $str
 * @param array $data
 * @return string
 */
function string_replace ($str, $data=array(), $args=null){
	if (
		(is_string($str) && strpos($str, '{') === false && $args === null) 
		|| empty($data)
	){
		return $str;
	}
	
	if (is_string($data)){
		$data = to_args($data);
	}
	
	if (is_callback($str)){
		return $str($data);
	}

	$args = _args($args, array(
		'single'   => false,
		'match'    => null,
		'fallback' => null,
	), 'fallback');
	
	// different matching 
	$match = $args['single'] ?
			'/\{{1,2}\s*([^}|]+)(?:\|([^}]*))?\s*\}{1,2}/':	// { variable-name } or {{ variable-name }}
			'/\{{2}\s*([^}|]+)(?:\|([^}]*))?\s*\}{2}/';		// {{ variable-name }}

	if ($m = $args['match']){
		if ($m === '::'){										
			$match = '/::([a-z][a-z0-9_-]*)/';					// ::variable-name
		}else if ($m === ':'){
			$match = '/:([a-z][a-z0-9_-]*)/';					// :variable-name
		}else if ($m === '$'){
			$match = '/\$([a-z][a-z0-9_-]*)/';					// $variable-name
		}else{
			$match = $m;
		}
	}

	$str = preg_replace_callback($match, function ($m) use ($data, $args) {
		$value 		= null;
		$keys  		= explode(',', $m[1]);
		$keys		= array_map('trim', $keys);
		$fallback 	= isset($m[2]) ? trim($m[2]) : $args['fallback'];

		for ($i=0, $l=count($keys); $i<$l; ++$i){
			$key = $keys[$i];

			if (is_callback($data)){
				$value = call_user_func($data, $key);
			}else if (is_array($data) && isset($data[$key])){
				$value = $data[$key];
			}else{
				$value = get_value($data, $key);
			}

			if (is_callback($value)){
				$value = call_user_func($value);
			}

			if (!isset($value) && $value !== null){
				break;
			}
		}

		if ($value === null && $fallback === null){
			$k = trim($m[1]);
			return $fallback !== null ? $fallback : $m[0];
		}else if ($value === null){
			return $fallback;
		}else if (is_array($value) || is_object($value)){
			return '<pre>'.json_encode($value, JSON_PRETTY_PRINT).'</pre>';
			// return implode(',', $value);
		}else{
			return $value;
		}
	}, $str);
	
	return $str;
}

function string_random ($length = 16, $prefix=''){
	if (is_string($length)){
		$prefix = $length;
		$length = 16;
	}

	$str 		= "";
	$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
	$max 		= count($characters) - 1;

	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $max);
		$str .= $characters[$rand];
	}

	return ($prefix ? "{$prefix}_" : '') . $str;
}

// Array -----------------------------------------------------------------------
// function array_get ($arr, $path, $args=''){
// 	$args = to_args($args, array(
// 		'separator' => '.',
// 		'fallback'	=> null
// 	), 'fallback');

// 	$path  = explode($args['separator'], $path);
// 	$value = null;

// 	for ($i=0, $l=count($path); $i<$l; ++$i){
// 		$name = $path[$i];
// 		$arr  = isset($arr[$name]) ? $arr[$name] : null;

// 		if ($i === $l-1){
// 			$value = $arr;
// 		}else if ($arr === null){
// 			break;
// 		}
// 	}

// 	return $value === null ? $args['fallback'] : $value;
// }

// Validate --------------------------------------------------------------------
/**
 * Check if the value is valid (truthy or 0), not NULL/FALSE/''
 *
 * ```php
 * is(0);
 * // true
 * 
 * is('');
 * // false
 * ```
 * 
 * @param mixed $value
 * @param mixed $match
 * @return bool
 */
function is ($value){ //}, $match=true){
	return $value || $value === 0 || $value === 0.0 || $value === '0' || (is_array($value) && !empty($value));
	/*
	if ($match === true){
	}

	
	}else if (is_array($value) && is_array($match)){
		// TODO this should be 
		// foreach ($match as $i => $v){
		// 	if (
		// 		!array_key_exists($i, $value) 
		// 		|| $value[$i] != $v
		// 	) return false;
		// }
		// return true;
	}else 
	*/

	if ((is_int($value) || is_float($value)) && !is_finite($value)){
	 	return false;
	}
	
	if ($value || $value == 0 || $value === 0.0 || $value == '0') {
		return true;
	}

	return false;
}

function is_not ($value){ //}, $match=true){
	return !is($value); //, $match);
}

/**
 * is_match
 *  
 * Check if a value match a condition. Either a RegExp, a specific string or using a callback to test it.
 * 
 * ```php
 * is('John is 40', '/([a-z]+) is ([0-9]+)/i');
 * // true
 * 
 * is('John is in 40s', '/([a-z]+) is ([0-9]+)/i');
 * // false
 * 
 * is(['name'=>'John', 'age'=>40], function ($v){ return $v['age'] >= 40; });
 * // true
 * ```
 *
 * @param mixed $value 
 * @param mixed $match 
 * @param mixed $keys 
 * @return void
 */
function is_match ($value, $match, $args=null){
	$args = _args($args, [
		'key'	   => null,
		'keys'     => null,    // only compare some keys of the element
		'contains' => false,   // for text
	], 'contains');

	// if the value is an array, it could be an encoded $id,$value type
	if (is_array($value)){
		$key   = _get($value, '$id,$key', null);
		$value = _get($value, '$value', $value);
	}

	if ($value == $match){
		return true;
	}else if (is_string($value) && is_regexp($match)){
		return !!preg_match($match, $value);
	}else if (is_string($match) && $args['contains']){
		return stripos($value, $match) !== false;
	}else if (is_string($match)){
		return $match == $value;
	}else if (is_callable($match)){
		return _call($match, $value, $key);
	}else if (is_array($value) && is_array($match)){
		// only compare some keys of the element
		if ($args['keys']){
			$keys  = to_array($args['keys']);
			$filter= function ($v, $i) use ($keys){ return in_array($i, $keys) ? $v : null; };
			$value = array_each($value, $filter);
			$match = array_each($value, $filter);
		}

		if (arr_is_obj($match)){
			foreach ($match as $i => $m){
				// check for the '$id' (if it has been passed in the $value)
				if ($i === '$id' || $i === '$key'){
					$v = $key;
				}else if (!array_key_exists($i, $value)){
					return false;
				}else{
					$v = _get($value, $i);
				}

				if (is_string($m) && strpos($m, ':') === 0){
					// TODO add special functions with values (eg.: ":>(40)", ":regexp()"), ... 
					if (
						($m === ':empty' && is($v))
						|| ($m === ':not-empty' && is_not($v))
					) return false;
				}else if (is_array($m) && count(array_intersect(array_keys($m), ['<','<=','>','>=','<>','<=>','><','>=<',':like',':in',':not-in']))){
					$compare = array_key_first($m);
					$vv      = reset($m);
					$a 	     = is_array($vv) && array_key_exists(0, $vv) ? $vv[0] : null;
					$b 	 	 = is_array($vv) && array_key_exists(1, $vv) ? $vv[1] : null;
					
					if (
						($compare === '<' && !($v < $vv))
						|| ($compare === '<=' && !($v <= $vv))
						|| ($compare === '>' && !($v > $vv))
						|| ($compare === '>=' && !($v >= $vv))
						|| ($compare === '<>' && !($v > $a && $v < $b))			// between
						|| ($compare === '<=>' && !($v >= $a && $v <= $b))		// between or equal
						|| ($compare === '><' && !($v < $a || $v > $b))			// before/after
						|| ($compare === '>=<' && !($v <= $a || $v >= $b))		// before/after or equal
						|| ($compare === ':like' && (is_regexp($vv) && !preg_match($v, $vv)))
						|| ($compare === ':like' && strpos($v, $vv) === false)
						|| ($compare === ':like-insensitive' && stripos($v, $vv) === false)
						|| ($compare === ':in' && !in_array($v, $vv))
						|| ($compare === ':not-in' && in_array($v, $vv))
					) return false;			
				}else if ($m != $v){
					return false;
				}
			}

			return true;
		}else{
			$v1 = array_values($value);
			$v2 = array_values($match);
			sort($v1);
			sort($v2);
			$v1 = implode(',', $v1);
			$v2 = implode(',', $v2);
			return $v1 == $v2;
		}
	}
	return false;
}

function is_defined ($value){
	return $value === false || is($value);
}

function in ($value, $arr){
	$arr = to_array($arr);
	return in_array($value, $arr);
}

function has ($value, $key){
	return is_array($value) && (_get($value, $key) !== null);
}

/**
 * Return true if the value is true (true OR 1), when used in a string args
 *
 * @param mixed $value
 * @param boolean $true
 * @param boolean $false
 * @return boolean
 */
function is_truthy ($value, $true=true, $false=false){
	if ($value instanceof Closure){
		return $false;
	}
	return ($value === true || $value === 'true' || $value == 1) ? $true : $false;
}

/**
 * Check if the value is falsy
 *
 * @param mixed 	$value
 * @param boolean 	$false
 * @param boolean 	$true
 * @return boolean
 */
function is_falsy ($value, $false=false, $true=true){
	return is_truthy($value, $false, $true); 
}

/**
 * Check if the value is a RegExp
 *
 * @param mixed $value 
 * @return boolean
 */
function is_regexp ($value){
	return is_string($value) && preg_match('/^\/.+\/(i|m|g)?$/', $value);
}

/**
 * Check if it's a valid function
 *
 * @param mixed $callback 
 * @param bool $closure_only 	Return true only if the $callback is a Closure
 * @return bool
 */
function is_callback ($callback, $closure_only=true){
	return $callback && (
		(!$closure_only && is_callable($callback)) ||
		($closure_only && $callback instanceof Closure)
	);
}

// Cast ------------------------------------------------------------------------
/**
 * Return a set of key/value pair(s). If the $key is already an array, return it, if it's a string/int,
 * create a set pair
 *
 * ```php
 * to_set('a', 123); // ['a'=>123];
 * ```
 * 
 * @param  mixed 	$key
 * @param  mixed 	$value
 * @param  boolean 	$is_object	Force the return to be an array, even if the $value is null
 * 
 * @return array 	Pair of key/values
 */
function to_set ($key, $value=null, $accept_null=true){
	if (is_array($key)){
		return $key;
	}else if ($key && ($value !== null || $accept_null)){
		$object 	  = array();
		$object[$key] = $value;
		return $object;
	}
	return null;
}

/**
 * Simply return true, use with callback functions
 * 
 * ```php
 * call_user_func('to_true'); // true
 * ```
 * 
 * @return boolean
 */
function to_true (){
	return true;
}

/**
 * Simply return false, use with callback functions
 * 
 * ```php
 * call_user_func('to_false'); // false
 * ```
 * 
 * @return boolean	
 */
function to_false (){
	return false;
}

/**
 * Convert a value to a string. 
 *
 * ```php
 * to_string(true); // "true"
 * 
 * to_string(null); // "null"
 * 
 * to_string(new DateTime()); 
 * // "2022-04-26 20:37:02 America/Los_Angeles"
 * 
 * to_string(['name'=>'John', 'age'=>40]); 
 * // { "name": "John", "age": 40 }
 * 
 * to_string(['name'=>'John', 'age'=>40], [
 * 	'pair'=>':', 
 * 	'join'=>'; '
 * ]);
 * // "name:John; age:40"
 * 
 * to_string(['name'=>'John', 'age'=>40], [
 * 	'pair'=>' {{ key }}="{{ value }}"', 
 * 	'join'=>''
 * ]);
 * // ' name="John" age="40"'
 * ```
 * 
 * @param mixed $v
 * @return string
 */
function to_string ($value, $args=null, $join=false){
	if (is_string($value) && $args === null){
		return $value;
	}

	if ($args === true){
		$args = array('json' => true);
	}
	if (is_string($args) && !is_args($args)){
		$args = array('pair'=>$args);
	}

	$args = _args($args, array(
		'prefix'   => '',
		'suffix'   => '',
		'pair'     => false,
		'join'     => $join,
		'pretty'   => true,     // pretty JSON
		'json'     => false,
		'encode'   => false,
		'tabs'     => 0,
		// 'alias'	   => null, // maybe add this
		'fallback' => $value,
	), 'pair');

	if (isset($args['template'])){
		$args['pair'] = $args['template'];
	}
	if (isset($args['separator'])){
		$args['join'] = $args['separator'];
	}

	// for simplicity, put an object in an array
	if (is_object($value) && $args['pair'] !== false){
		$value  	  = array($value);
		$args['join'] = '';
	}

	// TODO change this function to work with array_is_obj().... to turn into a string

	if ($value === true){
		$args['json'] = false;
		$value  	  = 'true';
	}else if ($value === false){
		$args['json'] = false;
		$value  	  = 'false';
	}else if ($value === null){
		$args['json'] = false;
		$value  	  = 'null';
	}else if (is_array($value) && ($args['pair'] !== false || $args['join'] !== false)){
		$pair  = $args['pair'] === true ? '' : $args['pair'];
		$join  = $args['join'] === true ? '' : $args['join'];
		$tabs  = $args['tabs'] ? str_repeat(TAB, $args['tabs']) : '';
		$items = array();

		foreach ($value as $i => $v){
			if (!is($v)) continue;

			if (is_string($pair) && strpos($pair, '{') !== false){
				$d = is_object($v) ? clone $v : (array)$v;
				set_value($d, '$id', $i);
				set_value($d, '$value', $v);
				$items[] = string_replace($pair, $d);
			}else if (is_numeric($i) && is_string($v)){
				$items[] = $v;
			}else if (($v || $v == 0) && $v !== '' && $v !== false){
				if (is_string($pair) && strpos($pair, '{') !== false){
					// $v 		 = is_array($v) || is_object($v) ? $v : array('$value'=>$v, '$id'=>$i);
					$d           = (array)$v;
					$d['$value'] = $v;
					$d['$id']    = $i;
					$items[] = string_replace($pair, $v);
				}else if (is_callback($pair)){
					$items[] = _apply($pair, $v, $i);
				}else{
					$v 		 = is_object($v) ? (string)$v : $v;
					$v 		 = is_array($v) ? json_encode($v, JSON_HEX_QUOT) : $v;
					$items[] = $i . $pair . $v;
				}
			}
		}

		$args['json'] = false;
		
		if ($join !== false){
			$value = count($items) ? $tabs . $args['prefix'] . implode($join . $tabs, $items) . $args['suffix'] : '';
		}else{
			$value = $items;
		}
	}else if (is_array($value) && $args['pretty']){
		$args['json'] = false;
		$value  	  = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}else if (is_array($value)){
		$args['json'] = false;
		$value  	  = json_encode($value, JSON_UNESCAPED_UNICODE);
	}else if (is_a($value, 'DateTime')){
		$value = $value->format('Y-m-d H:i:s e');
	}else if (is_object($value) && $args['json']){
		$args['json'] = false;
		$value 		  = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}else if (is_object($value)){		
		$value = (string)$value;
	}else if ($args['pair']){
		$value = string_replace($args['pair'], array('$value'=>$value));
	}else{
		$value = $args['fallback'];
	}

	if ($args['json']){
		$value = $args['pretty'] ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	if ($args['encode'] && is_string($value)){
		$value = md5($value);
	}

	return $value;
}

/**
 * Takes a string a convert it to a valid RegExp, escapes known characters
 * 
 * ```php
 * to_regexp('a-long-name'); // "/a\-long\-name/"
 * ```
 *
 * @param string 	$re			String to convert
 * @param boolean 	$closed		Should the RegExp contain the opening/closing slashes
 * @return string
 */
function to_regexp ($re, $closed=true){
	$re = preg_replace('/([-+_$().*\/\[\]])/', '\\\$1', $re);
	$re = $closed ? "/{$re}/" : $re;
	return $re;
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
 * to_match('John is 40', '/^([a-z]+)/i') // "John"
 * 
 * to_match('John is 40', '/^([a-z]+).+([0-9])/i'); // ["John", "0"]
 * 
 * to_match('a b c dd eee ffff', '/([a-z]{2,})/i'); // "dd"
 * 
 * to_match('a b c dd eee ffff', '/([a-z]{2,})/i', true); // ["dd", "eee", "ffff"]
 * ```
 * 
 * @param string $str
 * @param string $regexp
 * @param string $args
 * @return mixed
 */
function to_match ($str, $regexp, $args='', $return=false){
	if (!is_string($str)){
		return null;
	}

	// transform the $regexp string to valid regexp
	$regexp = !is_regexp($regexp) ? to_regexp($regexp) : $regexp;
	
	if (is_bool($args)){
		$args = array(
			'groups'   => $args,
			'multiple' => $args,
		);
	}
	
	$args = to_args($args, [
		'trim'     => true,
		'multiple' => null,    // multiple items repeated
		'groups'   => null,    // [true, false, null] all the matches returns the grouped values 
		'fallback' => null,
		'return'   => $return,
	], 'return');
		
	if ($args['multiple']){
		preg_match_all($regexp, $str, $match);
	}else{
		preg_match($regexp, $str, $match);
	}	
	
	if ($args['multiple']){
		$items = array();
		$count = count($match);

		if ($args['groups']){
			foreach ($match[0] as $i => $v){
				$item = array();
				for ($ii=1, $ll=$count; $ii<$ll; ++$ii){
					$v 		= $match[$ii][$i];
					$item[] = $args['trim'] ? trim($v) : $v; 
				}
				$items[] = $item; 
			}
		}else{
			$count = count($match[0]);
			for ($i=0, $l=$count; $i<$l; ++$i){
				if ($args['groups'] === null){
					$item    = isset($match[1][$i]) ? $match[1][$i] : $match[0][$i];
					$items[] = $args['trim'] ? trim($item) : $item;
				}else{
					$item 	 = $match[0][$i];
					$items[] = $args['trim'] ? trim($item) : $item;
				}
			}
		}

		return $items;
	}else if ($return = $args['return']){
		$is_match = isset($match[0]);
		$groups   = array_slice($match, 1);	
		$groups   = $args['trim'] ? array_map('trim', $groups) : $groups;

		if ($return === ":group"){
			return isset($groups[0]) ? $groups[0] : null;
		}else if ($return === ':groups'){
			return $is_match ? $groups : null;
		}else if ($return && $is_match){
			return isset($groups[0]) ? $groups[0] : $match[0];
		}		
	}elseif ($args['groups']){
		$groups = array_slice($match, 1);
		$groups = $args['trim'] ? array_map('trim', $groups) : $groups;
		return $groups;
	}else if (count($match) && $args['groups'] === null){
		return isset($match[1]) ? array_slice($match, 1) : $match[0];
	}else if (count($match)){
		return $match[0];
	}

	return $args['fallback'];
}

/**
 * Return an array based on the $items. If it's a string, it parses it.
 *
 * Argument options:
 * ```php
 * $args = [
 * 	'separator'	=> ',',		// Use this character to split a string
 * 	'trim'		=> true,	// Trim all the values if they are string
 * 	'filter'	=> false,	// remove empty/null values or use a callback function
 * ]
 * ```
 * 
 * ```php
 * $items = to_array('3,5,432,true', 'parse=1');
 * // [4, 5, 432, true]
 * ```
 * 
 * @param  mixed $items
 * @param  array $args	
 * 
 * @return array
 */
function to_array ($items, $args=array(), $callback=null){
	if (is_bool($args) || is_callback($args)){
		$args = array('filter'=>$args);
	}

	$args = to_args($args, array(
		'separator' => ',',
		'trim'      => true,
		'filter'    => $callback,
		'parse'		=> false,
		'object'	=> true, 	// convert object to array
		'return'	=> false,	// [array, object]
	), 'separator');

	// 
	$separator = is_string($args['separator']) ? $args['separator'] : ',';

	if (!$args['object'] && arr_is_obj($items)){
		$items = array($items);
	}

	if (is_string($items)){
		$items = $items ? explode($separator, $items) : array();
	}else if (!is_array($items) && !is_iterable($items)){
		$items = $items ? array($items) : array();
	}

	// Trim all the string value if specified
	$arr = [];
	$i 	 = 0;
	foreach ($items as $key => $v){
		$v = $args['trim'] && is_string($v) ? trim($v) : $v;
		$v = $args['parse'] ? to_value($v) : $v;
		
		if (is_callback($args['filter'])){
			$v = apply($args['filter'], [$v, $key], 'null=1');
		}
		
		if ($args['return'] === 'object'){
			$i = $key;
		}
		
		if (!$args['filter'] || ($args['filter'] && is($v))){
			if (is_array($v) && isset($v['$id']) && isset($v['$value'])){
				$ii = $v['$id'];
				$vv = $v['$value'];
				$arr[$ii] = $vv;
			}else{
				$arr[$i] = $v;
			}

			$i++;
		}
	}
		
	return $arr; // make sure it's an array starting at index 0
}

/**
 * Convert a string value to it's proper type.
 * 
 * Argument:
 * ```php
 * $args = [
 * 	'bool'   => true,	// Convert boolean
 * 	'number' => true,	// Convert numeric values
 * 	'string' => true,	// Remove apostrophe wrapping a string
 * 	'null'   => true,   // Convert null
 * 	'json'   => false,  // Convert string to json
 * ];
 * ```
 *  
 * ```php
 * $value = to_value('true');
 * // return true
 * 
 * $value = to_value('[0, 3, 5]', 'json=1');
 * // return [0, 3, 5]
 * ```
 * 
 * @param  mixed $str
 * @param  mixed $args
 * @return mixed
 */
function to_value ($str, $args=''){
	if (!is_string($str)){
		return $str;
	}else if ($args === true){
		$args = array('json'=>true, 'fallback'=>$str);
	}else if (is_callable($args)){
		$args = array('callback'=>$args);
	}

	$args = to_args($args, array(
		'bool'     => true,
		'number'   => true,
		'string'   => true,
		'null'     => true,
		'json'     => true,
		'slash'	   => false,
		'dataUrl'  => true,    // data URLs
		'format'   => false,
		'callback' => null,
		'fallback' => null,
	), 'fallback');

	$str = trim($str, "\0");
	// $str = trim($str);
	$istr= trim(strtolower($str), ' ');

	if (is_callable($args['callback'])){
		return $args['callback']($str);
	}else if ($args['bool'] && $istr === 'true'){
		return true;
	}else if ($args['dataUrl'] && strpos($istr, 'data:') === 0){
		$data = to_match($str, '/data:(?P<type>[^;,]+)(?P<encoded>\;base64)?,(?P<data>.+)/');
		$str  = $data['data'];
		$str  = base64_decode($str);
		
		if ($data['type'] === 'application/json'){
			$str = json_decode($str);
		}
		
		// TODO deal with image, .... and other types of mime types
	}else if ($args['bool'] && $istr === 'false'){
		return false;
	}else if ($args['null'] && $istr === 'null'){
		return null;
	}else if ($args['number'] && is_numeric($str) && (strlen($str) == 1 || strpos($str, '0.') === 0 || (strlen($str) > 1 && ($str[0] !== '0')))){
		// @info large value like "2195382378469351263235594675996882415760431660453133648959" will give "9223372036854775807", that's an error
		$nbr = strpos($str, '.') ? (float)$str : (int)$str;
		return $nbr == $str ? $nbr : $str;
	}

	// decode the string to a json
	if ($args['json'] && is_string($str) && preg_match('/^[\{\[]([\s|\S]+)[\}\]]$/', $str)){
		$arr = json_decode($str, true);
		
		// validate the json
		if (function_exists('json_last_error')){
			$str = json_last_error() == JSON_ERROR_NONE ? $arr : $args['fallback'];
		}else{
			$str = (is_object($arr) || is_array($arr)) ? $arr : $args['fallback'];
		}
	}
		
	if (is_string($str)){
		$str = $args['slash'] ? stripslashes($str) : $str;
		
		// remove the apostrophe wrapping the string
		if ($args['string']){
			$str = preg_replace('/^[\'"](.+)[\'"]$/', '$1', $str);
		}
	}

	$str = is_callback($args['format']) ? _apply($args['format'], $str) : $str;

	return $str;
}

/**
 * Parse values in an array to be their right type 
 *
 * Argument:
 * ```php
 * $args = [
 * 	'separator'	=> ',',		// Use this character to split a string
 * ]
 * ```

 * ```php
 * to_values('45, 88, true, "joe", "joe, is ok", [5,6,8], {"a":45}', 'json=1');
 * ```
 * 
 * @param mixed $str
 * @param array $args
 * 
 * TODO parse a Date string
 * 
 * @return void
 */
function to_values ($str, $args=array()){
	$args = to_args($args, array(
		'trim'		=> false,
		'separator' => ',',
	));

	$items = array();

	if (is_array($str)){
		$items = $str;
	}else if (is_string($args['separator']) && is_string($str)){
		$sep = $args['separator'];
		
		$str = preg_replace_callback('/((?:\"[^"]+?\")|(?:\'[^\']+?\'))/', function ($m) use ($sep){
			return str_replace($sep, KEY, $m[1]);
		}, $str);		
		$items = explode($sep, $str);
        $items = array_map(function ($v) use ($sep){ return str_replace(KEY, $sep, trim($v)); }, $items);
	}else if (preg_match_all('/[\"\'\[\{\(]/', $str)){
		// TODO test this RegExp compared to the JS one
		$count = preg_match_all('/(?:,|^|;)((?:[^,\("\'\{\[]+(?:(?:\(.+?\))|(?:(\{.+?\}))|(?:(\[.+?\]))|(?:\".+?\")|(?:\'.+?\'))?)|(?:\(.+?\))|(?:\".+?\")|(?:\'.+?\'))/', $str, $matches);

		if ($count){
			foreach ($matches[1] as $v){
				$items[] = trim($v);
			}
		}
	}else{
		$items = explode(',', $str);
	}

	$values = array();
	foreach ($items as $i => $v){
		$v 			= $args['trim'] && is_string($v) ? trim($v) : $v;
		$values[$i] = to_value($v, $args);
	}

	return $values;
}

function to_random ($type=null, $a=null, $b=null, $c=null){
	$r = rand(0,1000)/1000;

	if ($type === 'color'){
		// var r = Math.floor(Math.random() * 255).toString(16);
		// var g = Math.floor(Math.random() * 255).toString(16);
		// var b = Math.floor(Math.random() * 255).toString(16);
		// r = (r.length == 1 ? '0' : '') + r;
		// g = (g.length == 1 ? '0' : '') + g;
		// b = (b.length == 1 ? '0' : '') + b;
		// return '#'+[r,g,b].join('').toUpperCase();
	}else if ($type === 'date' || is_a($type, 'DateTime')){
		$dt  = is_a($type, 'DateTime') ? clone $type : new DateTime('now');
		$add = round($r * 356) * (rand(0, 1) ? 1 : -1);
		$add = $add > 0 ? "+{$add}" : $add;
		$dt  = $dt->modify("{$add} day");
		return $dt;
	}else if (is_array($type)){
		$i = round($r * (count($type)-1));
		return $type[$i];
	}else if ($type === 'number' || is_numeric($type)){
		$min = 0;
		$max = 1000;

		if (is_numeric($type)){
			$c = $b;
			$b = $a;
			$a = $type;	
		}

		if (is_numeric($b)){
			$min = $a;
			$max = $b;
		}else if (is_numeric($a)){
			$max = $a;
		}

		$min = (float)$min;
		$max = (float)$max;

		$a = $r * ($max - $min) + $min;
		if ($b === true || $c === true){
			$a = round($a);
		}

		return $a;
	}else if ($type === 'ratio'){
		return rand(0,10000)/10000;
	}else if ($type === 'boolean' || $type === 'bool' || is_bool($type)){
		$list = [true, false];
		$i    = round($r);
		return $list[$i];
	}else{
		$a = floor(rand() * 0xFFFFFF);
		$a = dechex($a);
		return is_string($type) ? "{$type}{$a}" : $a;
	}
}

/**
 * Return backtrace value. Convert it to a readable "debug" for debug functions
 *
 * @param mixed $index 
 * @return mixed
 */
function to_backtrack ($index=null, $debug=false){
	$min = 0;
	$max = 0;

	if (is_array($index)){
		$min = $index[0];
		$max = $index[1];
	}else if (is_numeric($index)){
		$max = (int)$index;
	}

	$items   = debug_backtrace($max);
	$trails  = array();

	foreach ($items as $i => $item){
		if (
			!isset($item['line'])
			|| $i < $min
		) continue;
		
		$item['path']  		= to_simple_path($item['file']);
		$item['debug'] 		= "[LOG] {$item['path']} [line {$item['line']}]";
		$item['function']	= isset($items[$i+1]) ? $items[$i+1]['function'] : '';	// we need to know the function where the log function has been called
		$trails[] 	  		= $debug ? $item['debug'] : $item;
	}
	
	return is_numeric($index) ? $trails[$index] : $trails;
}

function to_version ($url, $version=true){
	if (is_falsy($version)) return $url;
	$version = 'ver=' . ($version === true ? _config('version') : $version);
	// TODO deal with anchor "#"
	return strpos($url, '?') !== false ? $url.'&'.$version : $url.'?'.$version;
}

/**
 * Return the name of the called method
 *
 * @return string
 */
function to_called_method (){
	$b = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	return isset($b[1]) ? $b[1]['function'] : '';
}

function is_args ($str){
	return is_array($str) || (
		is_string($str) && 
		strpos($str, '=') !== false && 
		strpos($str, 'http') !== 0 && 
		strpos($str, '<') !== 0
	);
}

/**
 * Convert a string to an array and add default values. If the string isn't a value "param=value&param2=value" string,
 * a default $key can be used.
 * 
 * ```php
 * to_args('name=John&age=50', ['child'=>0]); 
 * // ["child"=>0, "name"=>"John", "age"=>50]
 * 
 * to_args('John', ['child'=>0], 'name');
 * // ["child"=>0, "name"=>"John"]
 * ```
 *
 * @param string $args
 * @param [type] $defaults
 * @param [type] $key
 * @param boolean $bool
 * @return void
 */
// taken from Wordpress function "parse_args" https://core.trac.wordpress.org/browser/trunk/src/wp-includes/functions.php#L3067
function to_args ($value='', $defaults=null, $options=null, $merge=[], $filter=null){
	if (is_string($options)){
		$options = array('key'=>$options);
	}
	if (is_string($merge)){
		$filter = $merge;
		$merge  = [];
	}

	$options = array_merge(array(
		'key'    => null,		// key for default value if the $value isn't a proper args (array, object, http-query)
		'bool'   => false,		// convert "true"/"false" to proper type
		'filter' => $filter,	// call a filter at the end of the parsing 
	), is_array($options) ? $options : array());

	$value	  = $value === '' || $value === null ? array() : $value;
	$is_array = is_array($value);

	// Try to check if the string isn't a good url parameter
	if (!$is_array && ($key = $options['key']) && (!is_string($value) || !is_args($value))){
		$value = to_set($key, $value, true);
	}

	if (is_object($value)){
		$value = get_object_vars($value);
	}elseif (!is_array($value)){
		$value = __to_args_str($value);
	}

	// convert to proper type of values (if the source was a http-query)
	if (!$is_array){
		$value = to_values($value, [
			'bool' => $options['bool'],
			'json' => false,
		]);
	}

	// make a copy of the value, so it can be used, to see if the value has been defined in the call
	$defined = $value;

	if (is_array($defaults)){
		// skip "null" value in $values and add the $defaults
		foreach ($defaults as $i => $v){
			if (array_key_exists($i, $value) && $value[$i] !== null) continue;
			$value[$i] = $v;
		}
	}
	if (is_array($merge)){
		$value = array_merge($value, $merge);
	}

	if ($options['filter']){
		$value = _filters($options['filter'], $value, $defined);
	}

	return $value;
}

function _args (){
	return call_user_func_array('to_args', func_get_args());
}

function __args (){

	// $args             = _args($args, [], 'value');
	// $args['multiple'] = true;

}

/** @ignore */
function __to_args_str ($string){
	// TODO make sure to keep "+"
	$array = array();
	parse_str($string, $array);
	$array = __stripslashes_deep($array);
	return $array;
}

/** @ignore */
function __stripslashes_deep ($value) {
	if (is_array($value)){
		$value = array_map('__stripslashes_deep', $value);
	}elseif (is_object($value)) {
		$vars = get_object_vars($value);
		foreach ($vars as $key=>$data){
			$value->{$key} = __stripslashes_deep($data);
		}
	}else{
		$value = stripslashes($value);
	}
	return $value;
}

// Filters & Actions -----------------------------------------------------------
function __filter_add ($name, $key, $depth, $callback=null){
	$list = get_global($name, array(), false);

	if (is_callback($depth)){
		$callback = $depth;
		$depth    = 10;
	}else if ($depth === null){
		$depth = 10;
	}

	// turn the invalid callback into a function that will return that value
	if (!is_callback($callback) && $callback !== null){
		$value    = $callback;
		$callback = function () use ($value){ return $value; };
	}

	if (!isset($list[$key])) 		 $list[$key] = array();
	if (!isset($list[$key][$depth])) $list[$key][$depth] = array();

	$list[$key][$depth][] = $callback;

	// order by keys
	ksort($list[$key], SORT_NUMERIC);

	set_global($name, $list, false);
}
function __filter_remove ($name, $key, $depth=null, $callback=null){
	$list = get_global($name, array(), false);

	if (is_callback($depth)){
		$callback = $depth;
		$depth    = 10;
	}

	if ($callback){
		array_remove($list[$key][$depth], $callback);
	}else if ($depth){
		unset($list[$key][$depth]);
	}else{
		unset($list[$key]);
	}

	// order by keys
	if (isset($list[$key])){
		ksort($list[$key], SORT_NUMERIC);
	}

	set_global($name, $list, false);
}
function __filter_apply ($name, $key){
	$list 	= get_global($name, array(), false);
	$params = func_get_args();
	$params = array_slice($params, 2);
	$value  = reset($params);
	
	if (!isset($list[$key])){
		return $value;
	}

	foreach ($list[$key] as $callbacks){
		foreach ($callbacks as $callback){
			$response = call_user_func_array($callback, $params);
			if (isset($response)){
				$params[0] = $response;
			}
		}
	}

	return reset($params);
}

// TODO clean this below

if (!function_exists('add_filter')){
	/**
	 * Add a filter function. Will be use to parse a value on the site
	 *
	 * @param string 	$key
	 * @param int 		$depth
	 * @param callback 	$callback
	 * @return void
	 */
	function add_filter ($key, $depth, $callback=null){
		__filter_add('filters', $key, $depth, $callback);
	}
}
if (!function_exists('remove_filter')){
	/**
	 * Remove a filter function.
	 *
	 * @param string 	$key
	 * @param int 		$depth
	 * @param callback 	$callback
	 * @return void
	 */
	function remove_filter ($key, $depth, $callback=null){
		__filter_remove('filters', $key, $depth, $callback);
	}
}
if (!function_exists('apply_filters')){
	/**
	 * Apply a list of filter functions on a value. Goes in order of the depth set first.
	 *
	 * @param string $key
	 * @param array  all the rest of the values are passed to the filter callbacks
	 * @return void
	 */
	function apply_filters ($key){
		$params = func_get_args();
		array_unshift($params, 'filters');
		return call_user_func_array('__filter_apply', $params);
	}
}

if (!function_exists('add_action')){
	function add_action ($key, $depth, $callback=null){
		__filter_add('actions', $key, $depth, $callback);
	}
}
if (!function_exists('remove_action')){
	function remove_action ($key, $depth, $callback=null){
		__filter_remove('actions', $key, $depth, $callback);
	}
}
if (!function_exists('do_action')){
	function do_action ($key){
		$params = func_get_args();
		array_unshift($params, 'actions');
		return call_user_func_array('__filter_apply', $params);
	}
}

if (!function_exists('add_action')){
	function add_action (){ $params = func_get_args(); return add_action(...$params); }
}
if (!function_exists('remove_action')){
	function remove_action (){ $params = func_get_args(); return remove_action(...$params); }
}
if (!function_exists('do_action')){
	function do_action (){ $params = func_get_args(); return do_action(...$params); }
}

// @maybe add option to PAUSE a filter, or filter and DEPTH

/**
 * Call apply_filters OR apply_filters if that function exists (for Wordpress)
 *
 * @return mixed
 */
function _filters (){
	$args     = func_get_args();
	$response = null;

	// if using Wordpress, use their filter system
	if (is_callable('apply_filters')){
		$response = $args[1] = call_user_func_array('apply_filters', $args);
	}
	if ('apply_filters' !== 'apply_filters' && is_callable('apply_filters')){
		$response = call_user_func_array('apply_filters', $args);
	}

	return $response;
}

function _action (){
	$args     = func_get_args();
	$response = null;

	// if using Wordpress, use their filter system
	if (is_callable('do_action')){
		$response = $args[1] = call_user_func_array('do_action', $args);
	}
	if ('do_action' !== 'do_action' && is_callable('do_action')){
		$response = call_user_func_array('do_action', $args);
	}

	return $response;
}

// Data ------------------------------------------------------------------------
/**
 * Set a value in the $GLOBALS
 *
 * @param mixed $key 
 * @param mixed $value 
 * @return void
 */
function set_global ($key, $value=array(), $args=array()){
	if (is_array($key)){
		foreach ($key as $i => $v){
			set_global($i, $v, $value);
		}
		return $key;
	}

	if (is_bool($args)){
		$args = array('filter' => $args);
	}
	$args = array_merge(array(
		'filter'     => true,
		'to_content' => false,
	), $args);

	global $GLOBALS_VALUES;
	$key    = is_object($key) || is_array($key) ? spl_object_hash($key) : $key;
	$value  = $args['filter'] ? _filters("set_global-{$key}", $value) : $value;
	$values = is_array($GLOBALS_VALUES) ? $GLOBALS_VALUES : array();
		
	if ($value === null){
		unset($values[$key]);
	}else{
		$values[$key] = $value;
	}
	
	$GLOBALS['GLOBALS_VALUES'] = $values;
	
	return $value;
}

/**
 * Get a value from the $GLOBALS, return the $fallback if not found
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * 
 * @return void
 */
function get_global ($key=null, $fallback=null, $args=array()){
	if (is_bool($args)){
		$args = array('filter' => $args);
	}
	$args = array_merge(array(
		'filter'     => true,
		'to_content' => false,
	), $args);

	global $GLOBALS_VALUES;
	$values = is_array($GLOBALS_VALUES) ? $GLOBALS_VALUES : array();

	if ($key){
		$key   = is_object($key) || is_array($key) ? spl_object_hash($key) : $key;
		$value = isset($values[$key]) ? $values[$key] : $fallback;
		$value = $args['filter'] ? _filters("get_global-{$key}", $value) : $value;

		if ($args['to_content'] && function_exists('to_content') && is_callable($value)){
			$value = to_content($value, ['file'=>false, 'url'=>false]);
		}

		return $value;
	}else{
		return $values;
	}
}

function _global ($key=null, $fallback=null){
	if (is_array($key)){
		return set_global($key);
	}else if ($key){
		return get_global($key, $fallback, ['to_content'=>true]);
	}else{
		return get_global();
	}
}

function _g ($key, $value=null){
	$all = get_global($key);

	if (func_num_args() === 1){
		return $all;
	}

	if (is_array($value)){
		$all = is_array($all) ? $all : [];
		$all = array_merge($all, $value);

		if (arr_is_list($all)){
			$all = array_unique($all);
		}
	}else{
		$all = $value;
	}

	set_global($key, $all);

	return $all;
}


function set_cookie ($key, $value, $expire='1 week'){
	$key 	= _config('cookie_prefix', '') . $key;
	$expire = strtotime($expire);
	// TODO maybe augment the expire when doing an action

	if ($value === null || $value === false){
		unset($_COOKIE[$key]);
		setcookie($key, '', -1, '/');
	}else{
		$value  	   = to_string($value, 'pretty=0');
		$expire		   = !$expire ? 0 : $expire;
		$_COOKIE[$key] = $value;
		setcookie($key, $value, $expire, '/');
	}
}

function get_cookie ($key, $fallback=null){
	$key   = _config('cookie_prefix', '') . $key;
	$value = isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
	if ($value !== null){
		$value = stripslashes($value);
		$value = to_value($value, true);
	}
	return $value === null ? $fallback : $value;
}

function remove_cookie ($key){
	set_cookie($key, null);
}

function get_session_id (){
	$is_cookie  = _config('session_cookie', false);
	$session_id = null;

	if ($is_cookie){
		$key 		= 'session';
		$expire 	= _config('session_lifetime');
		$session_id = get_cookie($key);
		
		if (!$session_id){
			$session_id = string_random();
			set_cookie($key, $session_id, $expire);
		}else{
			// reset the $expire, keep the same session_id while navigating the site
			set_cookie($key, $session_id, $expire);
		}
	}else{
		!session_id() && session_start();
		$session_id = session_id();
	}

	return $session_id;
}

function set_session ($key, $value){
	get_session_id();

	$is_cookie = _config('session_cookie', false);
	if ($is_cookie){
		$key    = "session_{$key}";
		$expire = _config('session_lifetime');
		set_cookie($key, $value, $expire);
	}else{
		if ($value === null){
			unset($_SESSION[$key]);
		}else{
			$_SESSION[$key] = $value;
		}
	}

	return $value;
}

function get_session ($key, $fallback=null){
	get_session_id();
	$is_cookie = _config('session_cookie', false);

	if ($is_cookie){
		$key   = "session_{$key}";
		$value = get_cookie($key, $fallback);
	}else{
		$value = isset($_SESSION[$key]) ? $_SESSION[$key] : $fallback;
	}

	return $value;
}

// URL & Server ----------------------------------------------------------------
function to_ip_address ($anonymize=false){
	$user_ip 		= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	$client_ip 		= isset($_SERVER['HTTP_CLIENY_IP']) ? $_SERVER['HTTP_CLIENY_IP'] : null;
	$proxy_ips 		= isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? to_array($_SERVER['HTTP_X_FORWARDED_FOR']) : array();
	$cloudFlare_ip 	= isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : null;
	$client_ip 		= $client_ip ? $client_ip : (count($proxy_ips) ? current($proxy_ips) : ($cloudFlare_ip ? $cloudFlare_ip : $user_ip));
	$client_ip 		= trim($client_ip);

	if ($anonymize){
		$client_ip = preg_replace(
			array('/\.\d*$/', '/[\da-f]*:[\da-f]*$/'),
			array('.000', '0000:0000'),
			$client_ip
		);
	}

	return $client_ip;
}

/**
 * Check if the current server is using HTTPs
 *
 * @return void
 */
function is_https (){
	return (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
		(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
		(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
		(!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443');
}

function is_local (){
	return $_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1';
}

function is_dev ($local=true){
	return _config('env') === 'dev' || ($local && is_local());
}

function is_live (){
	return _config('env') === 'live';
}

function is_debug ($key='*'){
	$values = _config('debug', []);
	$values = is_array($values) ? $values : ['*'=>$values];

	if (is_string($key) && $key !== '*'){
		$key = to_slug($key);
	}

	return array_key_exists($key, $values) ? !!$values[$key] : false;
}

function is_ajax (){
	$headers = apache_request_headers();
	$ajax 	 = isset($headers['Ajax']) ? $headers['Ajax'] : null;
	return !!$ajax;
}

// TODO add the "refresh codes": embed, image_edit, image_delete, ...
function is_refresh ($key=null, $strict=false){
	$refresh = array_key_exists('refresh', $_GET) ? $_GET['refresh'] : false;
	// $refresh = _global("refresh:{$key}");

	$refresh = $refresh === '' ? true : $refresh;
	$refresh = _filters('is_refresh', $refresh, $key);

	if ($key !== null){
		return $refresh == $key || (!$strict && $refresh === true);
	}else{
		return $refresh === true;
	}
}

// function set_refresh ($key){
// 	_global("refresh:{$key}", true);
// }

/**
 * Get the host URL
 *
 * @param string $args 
 * @return void
 */
function get_host ($args='') {
	if (is_bool($args)){
		$args = array('slash'=>$args);
	}

	// TODO have a way to change the default host

	$args = to_args($args, array(
		'port'  => false,
		'slash' => true,
		// 'closed' => true,
	));

	$host = _config('host');

	if (!$host){
		$host = (is_https() ? 'https://' : 'http://') . (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER["SERVER_NAME"]);
	}

	$port = $args['port'] === true ? $_SERVER['SERVER_PORT'] : $args['port'];

	// port 80 & 443 are the default http/https ports
	if ($port && $port !== '80' && $port !== '443'){
		$host .= ":{$port}";
	}

	$host .= _config('folder');

	return $host . ($args['slash'] ? '/' : '');
}

// Files -----------------------------------------------------------------------
/**
 * Transform a $path into an absolute path, deal with "../" and "./" in a path (filepath OR URLs)
 *
 * ```php
 * get_absolute_path('/aaa/bbb/.././file.ext');
 * // "/aaa/file.ext"
 * 
 * get_absolute_path('http://asdf/bbb/./asdf');
 * // "http://asdf/bbb/asdf"
 * ```
 * 
 * @param mixed $path 
 * @return string
 */ 
function get_absolute_path ($path){
	$prefix    = to_match($path, '/^((https?\:\/\/)|(\/))/', false);
	$path 	   = $path ? $path : '';
	$path 	   = $prefix ? substr_replace($path, '', 0, strlen($prefix)) : $path;
	$path      = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	$parts     = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
	$absolutes = array();

	foreach ($parts as $part) {
		if ('.' == $part) continue;
		if ('..' == $part) {
			array_pop($absolutes);
		} else {
			$absolutes[] = $part;
		}
	}

	return $prefix . implode(DIRECTORY_SEPARATOR, $absolutes);
}

function clean_path ($path, $is_dir=false){
	$path = get_absolute_path($path);
	$path = str_replace('/./', '/', $path);
	$path = preg_replace('/\/{2,}/', '/', $path);
	$path = preg_replace('/:\//', '://', $path);
	$path = $is_dir ? "{$path}/" : $path;
	return $path;
}

/**
 * Filter a filepath by replacing alias values and $data 
 * 
 * ```php
 * $path = parse_filepath('@theme/folder/{{ value }}/file.ext', ['value' => 'sub-folder']);
 * // "/Users/???/Documents/Websites/_core/theme/folder/sub-folder/file.ext"
 * ```
 *
 * @param  string 	$path filepath
 * @param  array 	$data	array of vars to replace the path
 * 
 * @return string
 */
function parse_path ($path, $data=array(), $is_dir=false){
	if (is_bool($data)){
		$is_dir = $data;
		$data   = array();
	}

	if ($path && strpos($path, '@') !== false){
		$aliases = _config('aliases', array());
		$path 	 = strtr($path, $aliases);
	}
	
	if (!empty($data)){
		$path = string_replace($path, $data);
	}

	$path = clean_path($path, $is_dir);
	
	return $path;
}

// Find the first valid path to a file/directory
function to_path ($path, $args=''){
	$args = to_args($args, array(
		'dir'      => config('abspath'),
		'url'      => false,                    // accept external URLs
		'ext'      => 'php',
		'type'     => null,
		'data'     => null,
		'fallback' => null,
		'return'   => null,
		'version'  => null,
	), 'type');

	if (!$path){
		return false;
	}

	$dir   = parse_path($args['dir'], true);
	$ext   = $args['ext'] ? $args['ext'] : 'php';
	$paths = is_array($path) ? $path : array($path);
	$paths = _filters('to_path-paths', $paths);
	$items = [];

	foreach ($paths as $p){
		if (!$p || !is_string($p)) continue;
		$p 		 = urldecode($p); // make sure the path is a proper one
		$items[] = parse_path("{$dir}{$p}", $args['data']);
		$items[] = parse_path("{$dir}{$p}.{$ext}", $args['data']);
		$items[] = parse_path("{$dir}{$p}/index.{$ext}", $args['data']);
		$items[] = parse_path("./{$p}", $args['data']);
		$items[] = parse_path("./{$p}.{$ext}", $args['data']);
		$items[] = parse_path("./{$p}/index.{$ext}", $args['data']);
		$items[] = parse_path("{$p}", $args['data']);
		$items[] = parse_path("{$p}.{$ext}", $args['data']);
		$items[] = parse_path("{$p}/index.{$ext}", $args['data']);
	}
	
	$path = null;
	foreach ($items as $i => $v){
		// Return the URL right away
		if ($args['url'] && string_is_url($v)){
			return $v;
		}

		$p1 = $v;
		$p2 = $items[$i] = realpath($v);
		$p  = file_exists($p1) ? $p1 : $p2; // Check first if the path works (in case it's a symlink, do not resolve it's full path with realpath)

		if (
			(!$args['type'] && file_exists($p)) ||
			($args['type'] === 'file' && is_file($p)) ||
			($args['type'] === 'dir' && is_dir($p))
		){
			$path = $p;
			break;
		}
	}

	// make sure there's a slash at the end
	$path = is_string($path) && is_dir($path) ? "{$path}/" : $path;
	
	if ($path && $args['return']){
		$info = to_fileinfo($path, array(
			'version' => $args['version'],
		), true);

		if (return_object($args['return'])){
			return $info;
		}else if ($args['return'] === 'url'){
			return $info['url'];
		}
	}

	return $path === null ? $args['fallback'] : $path;
}

function to_simple_path ($path){
	$abspath = _config('abspath');
	$path 	 = str_replace($abspath, '/', $path);
	$path 	 = str_replace('//', '/', $path);
	return $path;
}

function to_filepath ($path, $args=''){
	$args 		  = to_args($args);
	$args['type'] = 'file';
	return to_path($path, $args);
}

function to_dirpath ($path, $args=''){
	$args 		  = to_args($args);
	$args['type'] = 'dir';
	return to_path($path, $args);
}

function to_fileinfo ($path, $args='', $skip_parse=false){
	$path = $skip_parse ? $path : parse_path($path);
	if (!file_exists($path)) return;

	$args = _args($args, array(
		'version' => null,
	));
	
	$url  = path_to_url($path) . ($args['version'] ? "?ver=" . $args['version'] : '');
	$info = pathinfo($path);
	$mime = mime_content_type($path);
	$type = is_dir($path) ? 'directory' : $mime;

	if (strpos($type, '/') !== false){
		$type = explode('/', $type);
		$type = $type[0];
	}

	$file = array(
		'path'          => $path,
		'url'           => $url,
		'type'          => $type,
		'mime'          => $mime,
		'title'         => ucwords(str_replace(array('-', '.'), ' ', $info['filename'])),
		'name'          => $info['filename'],
		'filename'      => $info['basename'],
		'date_created'  => filectime($path),
		'date_modified' => filemtime($path),
		'ext'           => isset($info['extension']) ? $info['extension'] : '',
	);

	return $file;
}

//Create the folder of the current directory 
function set_directory ($dir){
	$dir  = parse_path($dir, true);
	$info = pathinfo($dir);

	if (isset($info['extension'])){
		$dir = $info['dirname'];
	}

	if (!file_exists($dir)){
		$umask = umask(0);
		mkdir($dir, 0755, true);
		umask($umask);
	}

	// make sure there's a "/" at the end
	$dir = preg_replace('/\/$/', '', $dir) . '/';

	return $dir;
}

function set_file ($path, $content='', $args=null){
	if ($args === true){
		$args = array('append'=>true);
	}
	
	$args = _args($args, array(
		'type'      => null,    // [null, csv, ...]
		'append'    => false,
		'paths'     => true,
		'minify'    => false,
		'url'		=> false,
		'separator' => '',
	), 'type');

	$path = parse_path($path);
	$ext  = pathinfo($path, PATHINFO_EXTENSION);

	// automatic detection of file type
	if ($ext === 'csv' && $args['type'] === null){
		$args['type'] = 'csv';
	}

	if ($content === false || $content === null){
		is_file($path) && unlink($path);
	}else if (!file_exists($path)){
		// make sure to get the DIR, maybe the $path has no extension
		$dir = pathinfo($path, PATHINFO_DIRNAME); 
		set_directory($dir);

		$file = fopen($path, 'a');
		chmod($path, 0755);
		fclose($file);
	}

	if ($content){
		if ($args['type'] === 'csv'){
			$content = !arr_is_list($content) ? array($content) : $content;
			$file    = fopen($path, $args['append'] ? 'a' : 'w');

			// empty file, add the headers
			clearstatcache();
			if (!$args['append'] || !filesize($path)){
				$first   = reset($content);
    			$headers = array_keys($first);
				fputcsv($file, $headers);
			}

			// add all the rows
			foreach ($content as $row) fputcsv($file, $row);

			fclose($file);
		}else{
			$content = !is_string($content) && $content !== null ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $content;
			$content = $args['paths'] && is_string($content) ? encode_paths($content) : $content;

			if (is_string($content) && $args['minify']){
				$content = to_minify($content, $ext);
			}
			
			if ($args['append']){
				$separator = $args['separator'] === true ? NL : $args['separator'];
				file_put_contents($path, $separator . $content, LOCK_EX | FILE_APPEND);
			}else{
				file_put_contents($path, $content, LOCK_EX);
			}
		}
	}

	if ($args['url']){
		$path = path_to_url($path);
	}

	return $path;
}

function get_file ($path, $args='', $fallback=''){
	if (is_bool($args)){
		$args = array('parse'=>$args);
	}

	$args = to_args($args, array(
		'type'      => null,        // [null, csv, ...]
		'limit'		=> 0,			// for csv
		'parse'     => true,        // transform to proper value types
		'paths'     => true,        // decode paths
		'minify'    => false,
		'separator' => ',',         // for csv type
		'fallback'  => $fallback,
	), 'type');

	$path = parse_path($path);

	if (!is_file($path)){
		return $args['fallback'];
	}

	$ext = pathinfo($path, PATHINFO_EXTENSION);

	// automatic detection of file type
	if ($ext === 'csv' && !$args['type']){
		$args['type'] = 'csv';
	}

	if ($args['type'] === 'csv'){
		ini_set("auto_detect_line_endings", "1");
		ini_set("serialize_precision", "-1");

		if (($file = fopen($path, 'r')) !== false){
			$headers = null;
			$rows    = [];
			$limit 	 = $args['limit'];
			while (($row = fgetcsv($file, 0, $args['separator'])) !== false){
				if (!$headers){
					$headers = $row;
				}else{
					$row = to_values($row);
					$row = array_fuse($headers, $row);
					$rows[] = $row;
				}

				if ($limit && count($rows) >= $limit){
					break;
				}
			}
		}
		fclose($file);

		return $rows;
	}else{
		$content = $path && is_file($path) ? file_get_contents($path) : $args['fallback'];
		$content = $args['paths'] && is_string($content) ? decode_paths($content, is_array($args['paths']) ? $args['paths'] : null) : $content;
		$content = $args['parse'] && is_string($content) ? to_value($content, ['json'=>true, 'fallback'=>$content]) : $content;
	}
	
	if (is_string($content) && $args['minify']){
		$ext     = pathinfo($path, PATHINFO_EXTENSION);
		$content = to_minify($content, $ext);
	}

	return $content;
}

function _file ($path, $value=null){
	$path = parse_path($path);

	// get
	if (func_num_args() === 1){
		$value = file_exists($path) ? file_get_contents($path) : null;
		$value = $value !== null ? unserialize($value) : null;
		return $value;
	}else{
		if (!file_exists($path)){
			$dir = pathinfo($path, PATHINFO_DIRNAME); 
			set_directory($dir);

			$file = fopen($path, 'a');
			chmod($path, 0755);
			fclose($file);
		}

		$value = serialize($value);
		file_put_contents($path, $value, LOCK_EX);
	}
}

// Remove a file/folder
function remove_file ($path){
	$path = parse_path($path);

	if (is_file($path)){
		unlink($path);
		return true;
	}else if (is_dir($path)){
		$it 	= new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
		$files 	= new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

		foreach($files as $file) {
			if ($file->isDir()){
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}

		rmdir($path);
		return true;
	}
	return false;
}

// source: https://gist.github.com/funkjedi/3feee27d873ae2297b8e2370a7082aad
// glob() with support for recusrsive check (by using "**")
function glob_deep ($path, $args=null, $parse=true) {
	$args = _args($args, [
		'parse' => $parse,
		'flags' => 0,
	]);

	$path = $args['parse'] ? parse_path($path) : $path;

	if (stripos($path, '**') === false) {
		$files = glob($path, $args['flags']);
	}else{
		$position     = stripos($path, '**');
		$rootPattern  = substr($path, 0, $position - 1);
		$restPattern  = substr($path, $position + 2);
		$paths        = array($rootPattern.$restPattern);
		$rootPattern .= '/*';

		while($dirs = glob($rootPattern, GLOB_ONLYDIR)) {
			$rootPattern .= '/*';
			foreach($dirs as $dir) {
				$paths[] = $dir . $restPattern;
			}
		}

		$files = array();
		foreach($paths as $p) {
			$files = array_merge($files, glob_deep($p, $args, false));
		}
	}

	$files = array_unique($files);
	sort($files);

	return $files;
}

function each_files ($search, $args='', $callbacks=null, $recursive=null){
	if ($args === true){
		$args = array('dir'=>'@php');
	}else if (is_callable($args)){
		$recursive = $callbacks;
		$callbacks = $args;
		$args      = '';
	}

	$args = to_args($args, array(
		'dir'          => '',            // root directory
		'type'         => null,          // [file, folder, null]
		'match'        => null,          // regexp match for file type
		'exclude'      => '/^_|copy/',   // skip files with prefix "__" or the word "copy" by default
		'exclude_path' => '/^_|copy/',   // skip filepath
		'meta'         => false,
		'deep'         => false,         // [bool, max-depth]
		'reverse'      => false,
		'info'		   => true,			 // get the fileinfo content
		'content'	   => false,		 // get the file content
		'filter'       => null,          //
		'return'       => null,          // [true, :tree, false, key]
		'tree'         => false,
		'join'         => null,
		'sort'		   => null,
		'debug'        => false,
	), 'return');

	$dir       = parse_path($args['dir'], true);
	$search    = to_array($search);
	$file_tree = array();
	$files     = array();
	$return    = $args['return'];
	$debug 	   = $args['debug'];
		
	$callback_pre  = null;
	$callback      = null;
	$callback_post = null;
	
	$recursive = to_args($recursive, array(
		'depth' => 0,
		'dir'	=> $dir,
	));

	if (is_array($callbacks)){
		$callback_pre  = isset($callbacks[0]) ? $callbacks[0] : null;
		$callback      = isset($callbacks[1]) ? $callbacks[1] : null;
		$callback_post = isset($callbacks[2]) ? $callbacks[2] : null;
	}else if (is_callback($callbacks)){
		$callback	   = $callbacks;
	}

	$debug && __time('each_files');

	foreach ($search as $path){
		$prefix= substr($path, 0, 1);
		$path  = $prefix === '@' ? $path : "{$dir}{$path}";
		$ext   = pathinfo($path, PATHINFO_EXTENSION);
		// $path  = parse_path($path);
		$paths = glob_deep($path);
		$count = count($paths);

		// $debug && __err([$search, $path, $paths]);

		// dirs before the files
		!$ext && usort($paths, function ($a, $b){
			$ad = is_dir($a);
			$bd = is_dir($b);
			if ($ad === $bd) 		return strnatcasecmp($a, $b);
			elseif ($ad && !$bd)	return -1;
			elseif (!$ad && $bd)	return 1;
		});
		
		$paths = $args['reverse'] ? array_reverse($paths) : $paths;

		foreach ($paths as $i => $filepath){
			$filename = pathinfo($filepath, PATHINFO_BASENAME);

			// TODO skip files with '.'

			if (
				($args['exclude'] && is_match($filename, $args['exclude'])) ||
				($args['exclude_path'] && is_match($filepath, $args['exclude_path'])) ||
				($args['match'] && !is_match($args['match'], $filename)) ||
				($args['type'] === 'file' && !is_file($filepath)) ||
				($args['type'] === 'folder' && !is_dir($filepath))
			){
				continue;
			}
			
			$root    = _config('aliases.@routes', '');
			$route   = '/' . str_replace([$root, '.php'], '', $filepath);
			$is_file = is_file($filepath);
			$file	 = array(
				'index'        => $i,
				'length'       => $count,
				'depth'        => $recursive['depth'],
				'dir'          => $dir,
				'route'		   => $route,
				'relativepath' => str_replace($recursive['dir'], '', $filepath),
				'is_file'	   => $is_file,
				'is_dir'	   => !$is_file,
				'has_children' => false,
			);

			if ($args['info']){
				$info = to_fileinfo($filepath, null, true);
				$file = array_merge($info, $file);
			}
			if ($args['content']){
				$file['content'] = get_file($filepath);
			}

			// get the meta-data from the file
			if ($args['meta']){
				$meta 		  = to_file_meta($filepath);
				$file['meta'] = $meta ? $meta : array();
			}
			
			$filepath = is_dir($filepath) ? "{$filepath}/" : $filepath;
			$response = apply([$args['filter'], $callback], [$file, $filepath, $args], ['fallback'=>null]);

			if ($response === false) continue;
			
			if ($return || $response){
				$key  = count($files);
				$file = $response ? $response : $file;

				// reformat the returned item
				if (isset($file['$id'])){
					$key = $file['$id'];
					unset($file['$id']);
				}
				if (isset($file['$value'])){
					$file = $file['$value'];
				}

				if (is_string($return)){
					$file = isset($file[$return]) ? $file[$return] : null;
				}

				if (is($file)){
					$files[$key] = $file;
				}
			}else{
				$files[] = $filepath;
			}
			
			if ($args['deep'] && is_dir($filepath)){
				_call($callback_pre, $file, $filepath);

				$children = each_files($search, array_merge($args, array(
					'dir'  => "{$filepath}/",
					'deep' => is_numeric($args['deep']) ? $args['deep'] - 1 : $args['deep'],
					'tree' => $args['tree'],
				)), $callbacks, array(
					'dir'   => $recursive['dir'],
					'depth' => $recursive['depth'] + 1,
				));
				
				$file['has_children'] = count($children) > 0;
				
				if ($args['tree']){
					$file['children'] = $children;
				}else{
					$files = array_merge($files, $children);
				}

				_call($callback_post, $file, $filepath);
			}

			$file_tree[] = $file;
		}
	}	

	$items = $args['tree'] ? $file_tree : $files;

	// sort
	if ($sort = $args['sort']){
		$sort  = $sort == 1 || $sort == true ? '*' : $sort; 
		$items = array_sort($items, $sort);
	}
	// join
	if ($args['join'] !== null){
		$items = to_string($items, null, $args['join']);
	}
	
	$debug && __err([
		'time'  => __time('each_files'),
		'count' => count($items),
	]);

	return $items;
}

function include_files ($search, $args=null){
	if ($args === true){
		$args = array('once'=>true);
	}

	$args = to_args($args, array(
		'dir'  => '@php',
		'once' => false,
	), 'dir');

	$args['type'] = 'file';

	each_files($search, $args, function ($file, $path, $a) use ($args){ 		
		if ($a['debug']){
			__err($path);
		}

		if ($args['once']){
			include_once $path; 
		}else{
			include $path; 
		}
	});
}

// Config ----------------------------------------------------------------------
function config ($key=null, $value=null){
	$args       = to_set($key, $value, false);
	$config     = get_global('config');
	$has_config = !!$config;

	// default values
	if (!$has_config){
		set_global('startup', [
			'start'  => microtime(true),
			'memory' => memory_get_usage(),
		]);

		$domain  = defined('DOMAIN') ? DOMAIN : 'theme';
		$abspath = defined('ABSPATH') ? ABSPATH : __DIR__;
		
		$config = array(
			'domain'        => $domain,
			'abspath'    	=> $abspath,
			'env'			=> null,
			// theme files ---------------------------------------------------------
			'root_dir'     	=> '',							// Sub-folder
			'theme_dir'		=> '/theme/',					// hold the theme files (assets, templates, ...)
			'env_dir'		=> '/data-{{ env }}/',			// File for a specific environment
			'data_dir'      => '/data/',					// Data files
			'cache_dir'     => '@env/cache/',				// Caching files
			'upload_dir'	=> '@env/upload/',				
			'items_dir'	  	=> '@theme/template-items/',
			'assets_dir'	=> '@theme/assets/',
			'images_dir'  	=> '@assets/images/',
			'fonts_dir'  	=> '@assets/fonts/',
			'scripts_dir' 	=> '@assets/js/dist/',	
			'styles_dir'  	=> '@assets/style/dist/',	
			'php_dir'	  	=> '@assets/php/',
			'php_core_dir'	=> '@assets/php/core',
			'dir'			=> '',							// current dir	
			// site ------------------------------------------------------------
			'host'			   => null,
			'folder'		   => '',
			'hash'             => '',                                               // coded hash to add security to the site
			'nonce'            => true,
			'debug'            => 'debug',                                          // [true OR string] if string, check in the GET
			'host_local'       => '.local',
			'host_dev'         => '.dev',
			'session_cookie'   => true,
			'session_lifetime' => '30 minutes',
			'version'          => defined('VERSION') ? VERSION : '1.0',
			// data ------------------------------------------------------------
			'timezone'		   => 'America/Toronto',
			// ...
			// actions ---------------------------------------------------------
			'lang'			=> null,
			'locale'   	 	=> null,    // current locale (language)
			'on_config'		=> null,
			// errors ----------------------------------------------------------
			'error_logs'	=> false, 			// specific path for logs of errors
			'error_message' => '500 Internal Server Error',
			'on_error'		=> null,
		);

		// For local, set the right timezone
		// is_local() && date_default_timezone_set('America/Toronto');
	}
			
	if ($args){
		$args    = apply_filters('config-args', $args, $has_config);
		$config  = array_merge($config, $args);
		set_global('config', $config);

		$abspath = realpath($config['abspath']);
		$host    = get_host(false);

		if (!$config['host']){
			$config['host'] = $host;
		}
		
		// environment
		$server = $_SERVER['SERVER_NAME'];
		$env    = 'live';
		if (is_local() || strpos($server, $config['host_local']) !== false){
			$env = 'local';
		}else if (strpos($server, $config['host_dev']) !== false){
			$env = 'dev';
		}
		$config['env'] = $env = isset($config['env']) ? $config['env'] : $env;

		// directories
		$aliases = array(
			'@root'      => $config['root_dir'],
			'@data'      => $config['data_dir'],
			'@env'       => string_replace($config['env_dir'], ['env'=>$env]),
			'@cache'     => $config['cache_dir'],
			'@upload'    => $config['upload_dir'],
			'@theme'     => $config['theme_dir'],
			'@items'     => $config['items_dir'],
			'@assets'    => $config['assets_dir'],
			'@images'    => $config['images_dir'],
			'@fonts'     => $config['fonts_dir'],
			'@scripts'   => $config['scripts_dir'],
			'@styles'    => $config['styles_dir'],
			'@php'       => $config['php_dir'],
			'@core'      => $config['php_core_dir'],
			'@dir'		 => $config['dir'],				// used with basic site
		);

		$paths = array();
		$urls  = array();
		// save the directories/path/urls
		foreach ($aliases as $i => $v){
			$ii 		 = str_replace('@', '', $i);
			$v 		 	 = strtr($v, $aliases);
			$v 		 	 = '/' . preg_replace('/^\/|\/$/', '', $v) . '/';	// make sure there's a slash at beginning and end
			$path 		 = $abspath . str_replace($abspath, '', $v);
			$path 		 = preg_replace('/\/{2,}/', '/', $path);			// only 1 slash at a time
			$url 		 = $host . str_replace($abspath, '', $path);
			$aliases[$i] = $path;
			$paths[$ii]  = $path;
			$urls[$ii] 	 = $url;
		}

		// add new custom paths
		foreach ($config as $i => $v){
			if ($i[0] !== '@') continue;
			$alias 		 = strtr($v, $aliases);
			$aliases[$i] = clean_path($alias, true);
		}
		
		// $config['host']         = get_host(false);
		$config['paths']        = $paths;
		$config['urls']         = $urls;
		$config['aliases']      = $aliases;
		$config['cookie_prefix']= isset($config['cookie_prefix']) ? $config['cookie_prefix'] : strtolower(preg_replace('/[^a-z0-9_-]/i', '_', $config['domain'])) . '_';

		set_global('config', $config);

		// debugging options ---------------------------------------------------
		$key = $config['debug'];
		if (is_string($key) && array_key_exists($key, $_GET)){
			$value  = $_GET[$key];
			$value  = is_array($value) ? $value : ['*'=>$value];

			// merge all the possible debug mode
			$values = get_session('debug', []);
			$values = is_array($values) ? $values : ['*'=>$values];
			$values = array_merge($values, $value);
			$values = array_filter($values);
			$values = empty($values) ? null : $values;

			$config['debug'] = $values;

			set_session('debug', $values);
		}else{
			$config['debug'] = get_session('debug', false);
		}

		// TODO add more "session" variable, for editing as admin, ...
		
		// domain --------------------------------------------------------------
		!defined('DOMAIN') && define('DOMAIN', $config['domain']);

		// version -------------------------------------------------------------
		!defined('VERSION') && $config['version'] && define('VERSION', $config['version']);

		// final global config -------------------------------------------------
		set_global('config', $config);

		// always include everything found in the "utils/" folder
		if (!$has_config){
			include_files('*.php, **/*.php', 'dir=@core&once=1');
		}

		// save default settings in a json file
		if (isset($args['settings']) && !empty($args['settings'])){
			settings(true, $args['settings']);
			unset($config['settings']);
		}

		// include files found in the PHP assets folder
		if (isset($args['includes'])){
			include_files($args['includes'] , 'dir=@php&once=1');
			unset($config['includes']);
		}
		
		if (!$has_config && isset($args['on_config'])){
			$config = _apply($args['on_config'], $config);
			set_global('config', $config);
		}

		// call includes AFTER the on_config function 
		if (isset($args['post_includes'])){
			include_files($args['post_includes'] , 'dir=@php&once=1');
			unset($config['post_includes']);
		}

		if (!$has_config && $config['error_logs']){
			set_exception_handler(function ($e) use ($config){
				echo '<div style="font:14px monospace; padding:3em;">';
				echo '<h1>' . $config['error_message'] . '</h1>';

				if (is_dev() || is_debug()){
					echo '<div style="opacity:0.5;">[ERROR] ' . $e->getFile() . ' [line ' . $e->getLine() . ']</div><br>';
					echo $e->getMessage();
					echo '<pre style="opacity:0.5; margin-top:1.5em; font-size:12px;">'.$e->getTraceAsString().'</pre>';
				}
				
				echo '</div>';

				_call($config['on_error'], $config);
			});
		}
	}else if ($key){
		return get_value($config, $key);
	}
	
	return $config;
}

function settings ($key=null, $value=null, $fallback=null){
	$path = parse_path('@env/settings.json');
	$data = get_file($path);
	$data = is_array($data) ? $data : array();
	$set  = null;

	// set config defaults
	if ($key === true && !empty($value)){
		$keys1 = array_keys($value);
		$keys2 = array_keys($data);
		$diff  = array_diff($keys1, $keys2);
				
		if (count($diff)){
			$set = array_join([$value, $data]);
		}
	}else{
		$set = to_set($key, $value, false);
	}	
	
	if ($set){
		// Add "timestamp" first, so it's first in the array
		$now  = to_date('now', 'UTC');
		$data = array_join([
			['timestamp'=>$now], 
			$data, 
			$set
		], 'null=1');
		set_file($path, $data);
		return $data;
	}else if ($key){
		return isset($data[$key]) ? $data[$key] : $fallback;
	}else{
		return $data;
	}
}

function path_to_url ($path){
	$root_path = _config('paths.root', '');
	$root_url  = _config('urls.root', '');
	$path      = parse_path($path);
	$url       = strpos($path, $root_path) === 0 ? str_replace($root_path, $root_url, $path) : $root_url . preg_replace('/^\//', '', $path);
	return $url;
}

function url_to_path ($url){
	$root_path = _config('paths.root', '');
	$root_url  = _config('urls.root', '');
	$url       = preg_replace('/[?#].+$/', '', $url);
	$path      = str_replace($root_url, $root_path, $url);
	return $path;
}

function encode_paths ($value, $decode=false, $aliases=null){
	$aliases = _args($aliases, [
		'host'	=> _config('host'),
		'env'	=> _config('aliases.@env'),
		'abs'	=> _config('abspath'),
	]);

    $host  = $aliases['host'];
    $env   = $aliases['env'];
	$abs   = $aliases['abs'];
    $host2 = str_replace('/', '\/', $host);
	$env2  = str_replace('/', '\/', $env);
	$abs2  = str_replace('/', '\/', $abs);

	// TODO change the root path too (abspath)

    $encode = function ($t) use ($host, $host2, $env, $env2, $abs, $abs2, $decode){
        if ($decode){
			$t = strtr($t, [
				'@host/'  => $host,
				'@host\/' => $host2,
				'@env/'   => $env,
				'@env\/'  => $env2,
				'@abs/'	  => $abs,
				'@abs\/'  => $abs2,
			]);
            // $t = str_replace('@host/', $host, $t);
            // $t = str_replace('@host\/', $host2, $t);
            // $t = str_replace('@env/', $env, $t);
            // $t = str_replace('@env\/', $env2, $t);
        }else{
			$t = strtr($t, [
				$host  => '@host/',
				$host2 => '@host\/',
				$env   => '@env/',
				$env2  => '@env\/',
				$abs   => '@abs/',
				$abs2  => '@abs\/',
			]);

            // $t = str_replace($host, '@host/', $t);
            // $t = str_replace($host2, '@host\/', $t);
            // $t = str_replace($env, '@env/', $t);
            // $t = str_replace($env2, '@env\/', $t);
        }
        return $t;
    };

    $walk = function ($t, $walk) use ($encode){
        if (is_string($t)){
            $t = $encode($t);
        }else if (is_array($t)){
            foreach ($t as $i => $v){
                $t[$i] = $walk($v, $walk);
            }
        }

        return $t;
    };

    return $walk($value, $walk);
}

function decode_paths ($value, $aliases=null){
    return encode_paths($value, true, $aliases);
}

function _config ($key=null, $fallback=null){
	$value = config($key);
	return $value !== null ? $value : $fallback;
}

function _setting ($key=null, $fallback=null){
	$value = settings($key);
	return $value !== null ? $value : $fallback;
}

function is_older_version (){
	$v_old = _setting('version');
	$v_now = _config('version');

	if (version_compare($v_old, $v_now, '<')){
		return true;
	}

	return false;
}

function sync_version (){
	$old = _setting('version');
	$now = _config('version');

	if ($old !== $now && _config('sync_version', true)){
		_info("Theme has been updated to \"{$now}\"");
		_setting(['version'=>$now]);
	}
}

function is_admin (){
    $value = _filters('is_admin', false);
    return $value;
}

// Add string to be access by the PHP and/or the JS
function add_strings ($strings){
	$all = get_global('theme_strings', array());
	$all = array_merge($all, $strings);
	set_global('theme_strings', $all);
}

function get_string ($key, $fallback=null){
	$all = get_global('theme_strings', array());

	if ($key == null){
		return $all;
	}

	return array_key_exists($key, $all) ? $all[$key] : $fallback;
}

/**
 * Merge properties together. Check for "prefix" (eg.: "prefix/key-name") in the properties keys, 
 * if they exists but don't match the prefix, we skip them.
 *
 * ```php
 * to_config_args("{{ value }}", 'templates', [
 * 		"*" 	=> "{{ value }}",
 * 		"en/*" 	=> "english: {{ value }}
 * ], "en")
 * ```
 * 
 * @param mixed $defaults 
 * @param mixed $config 
 * @param mixed $values 
 * 
 * @return void
 */
function to_config_args ($config, $values, $defaults, $prefix=null){
	// find the prefix (languages/admin)
	if ($prefix === null){
		$prefix = '';
		if (function_exists('is_admin') && is_admin()){
			$prefix = 'admin';
		}else if (function_exists('get_lang')){
			$prefix = get_lang();
		}
	}


	// format the values to keep those without a prefix OR the targeted prefixed
	$to_values = function ($items) use ($prefix){
		if (is_string($items)){
			return ['*'=>$items];
		}else if (!is_array($items)){
			return [];
		}

		$values = [];

		// find the current lang values
		foreach ($items as $i => $v){
			$pair = explode('/', $i);

			if (count($pair) > 1){
				if ($pair[0] === $prefix){
					$i = $pair[1];
				}else{
					continue;
				}
			}

			$values[$i] = $v;
		}

		return $values;
	};

	// cache the config
	if (is_string($config)){
		$key   = 'config-args:'.$config;
		$cache = _global($key);
		if (!$cache){
			$cache = _config($config);
			$cache = $to_values($cache);
			_global($key, $cache);
		}
		$config = $cache;
	}else if (!is_array($config)){
		$config = [];
	}

	
	$defaults = $to_values($defaults);
	
	if (is_string($values) && array_key_exists($values, $config)){
		$values = $config[$values];
	}
	
	$values = $to_values($values);
	$values = array_merge($defaults, $config, $values);

	return $values;
}

// Cron/Others -----------------------------------------------------------------
function every ($name, $time, $callback=null, $refresh=false){
	if (is_callable($time)){
		$refresh  = $callback;
		$callback = $time;
		$time     = $name;
	}

	$filename = md5($name);
	$filepath = parse_path('@env/cron/'.$filename);
	$now 	  = microtime(true);
	$modified = is_file($filepath) ? filemtime($filepath) : 0;
	$expire   = strtotime("+{$time}", $modified);
	
	if ($now > $expire || $refresh){
		apply($callback);

		$time	= (int)$time;
		$expire = strtotime("+{$time}", $now);
		$expire = to_date($expire, 'UTC');

		set_file($filepath, $expire);
	}
}

function once ($name, $callback){
	$key  = "once_action-{$name}";
	$done = _global($key);
	if ($done) return;
	_call($callback);
	_global([$key=>true]);
}

function test ($callback, $count=1){
	$start_tm = microtime(true);
	$start_mb = memory_get_usage(true);

	$count = $count < 1 ? 1 : $count;
	for ($i=0; $i<$count; ++$i){
		$callback();
	}
	
	$tm   = microtime(true) - $start_tm;
	$tm   = number_format($tm, 4) . 's';
	$mb   = memory_get_usage(true) - $start_mb;
	$mb   = $mb > 0 ? to_human_filesize($mb) : 0;
	$text = $tm . ($mb ? " ({$mb})" : '') . ($count > 1 ? " [x{$count}]" : '');
	
	add_log($text, [
		'prefix' => 'TEST',
		'file'	 => false,
		'group'  => 'times',
		'output' => 'js',
	]);
}


// Debug -----------------------------------------------------------------------
// TODO 
// [x] add code to log stuff when in AJAX call, maybe returned by the call OR in the header??
// [ ] add a way with the QUERY (?debug) to show the logs
// [ ] add type of logs to save into a file
// [x] add way of logging javascript actions, by ajax, then have a page to see the outputs
// [ ] logs page, for errors, ...
// [ ] if some page is called by AJAX, try figuring it out, and the logs output should be dealt there

function add_log ($params, $args=null){
	if (!is_array($params)){
		$params = array($params);
	}

	$args = to_args($args, array(
		'prefix' => 'LOG',
		'title'  => true,     // add title to log
		'file'   => true,
		'trim'	 => true,
		'style'  => '',
		'color'  => '#000',
		'type'   => 'log',
		'output' => 'php',
		'group'  => 'logs',
		'die'    => false,
	), 'output');

	if (is_array($args['file'])){
		$back = $args['file'];
	}else{
		$back = to_backtrack(2);
	}
	
	if (count($params)){
		$title = false;
		if ($args['title'] === true){
			$first = reset($params);

			if (is_string($first)){
				$title = array_shift($params);
			}else{
				$title = '';
			}
		}else if (is($args['title'])){
			$title = $args['title'];
		}

		if (is($title)){
			$title = to_string($title);
			$title = str_replace('\'', '\\\'', $title);
		}

		$fct 	= isset($back['function']) ? "[{$back['function']}]" : '';
		$file   = $args['file'] ? "{$back['path']} ".$fct."[line {$back['line']}]" : '';
		$prefix = $args['prefix'] ? $args['prefix'] : '';
		$color  = $args['color'];

		if ($args['output'] === 'js'){
			$all    = get_global('all_logs', array());
			$group  = $args['group'];
			$logs   = isset($all[$group]) ? $all[$group] : array('last'=>null, 'logs'=>array());
			$file 	= $file ? "{$file}{{ count }}" : '';
			$params = json_encode($params);
			$params = preg_replace('/^\[|\]$/','', $params);

			$prefix_style = "'background:{$color}; color:white; border-radius:2px; font-weight:bold; font-size:10px; padding:.25em .7em .3em;'";
			$file_style   = "'font-size:10px; color:#999; margin-bottom:3px;'";
			$title_style  = "'margin-top:.25em; color:{$color}; font-weight:bold; {$args['style']}'";
			if ($prefix && $file && $title){
				$log = "'%c{$file}\\n%c{$prefix}%c {$title}', {$file_style}, {$prefix_style}, {$title_style}";
			}else if ($prefix && $title){
				$log = "'%c{$prefix}%c {$title}', {$prefix_style}, {$title_style}";
			}else if ($title){
				$log = "'%c{$title}', {$title_style}";	
			}else if ($prefix && $file){
				$log = "'%c{$prefix}%c {$file}', {$prefix_style}, {$file_style}";
			}else if ($file){
				$log = "'%c{$file}', {$file_style}";
			}else{
				$log = '';
			}

			$last_key = $log.$params;
			// augment count
			if ($logs['last'] === $last_key){
				$last_index = count($logs['logs']) - 1;
				$logs['logs'][$last_index]['count']++;
			// add logs
			}else if ($log){
				$logs['logs'][] = ['type'=>$args['type'], 'msg'=>$log, 'count'=>1, 'params'=>$params];
			}

			$logs['last'] = $last_key;
			$all[$group]  = $logs;
			set_global('all_logs', $all);	
		}else if ($args['output'] === 'error'){
			if (count($params) === 1){
				$params = $params[0];
			}

			// if (count($params) === 1 && is_object($params[0])){
			// 	$params = $params[0];
			// 	$class  = get_class($first);
			// 	$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			// 	$params = 'object('.$class.')' . $params;
			// }else if (count($params) === 1){
			// 	$params = $params[0]; 
			// }

			if (is_object($params)){
				$class  = get_class($params);
				$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				$params = 'object('.$class.')' . $params;
			}else if (empty($params)){
				$params = '';
			}else{
				$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}

			// $params = count($params) === 1 ? $params[0] : $params;
			// // $params = !empty($params) ? (is_string($params) ? $params : json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) : '';
			// if (!is_array($params) || (is_array($params) && !empty($params))){
			// 	$params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			// }else{
			// 	$params = '';
			// }

			// simplify the output, so it's easy to copy/paste from the logs
			$params = strtr($params, array(
				'\\/' => '/',
				'\\"' => '"',
			));

			$msg = '';
			if ($prefix) $msg = "[{$prefix}] ";
			if ($file) 	 $msg = "{$msg}{$file}\n";
			if ($title)  $msg = "{$msg}\n  ▶ {$title}\n";

			$params = NL.TAB.preg_replace("/\n/", NL.TAB, $params);
			$msg 	= "{$msg}{$params}\n";
			
			error_log($msg);
		}else{
			// transform html to be visible in the output
			$title = strtr($title, [
				'<' => '&lt;',
				'>' => '&gt;',
			]);

			// php error
			echo '<pre style="font-size:14px; font-family:monospace; color:black; box-shadow:inset 0 0 0 1px rgba(0, 0, 0, .3); border-radius:3px; background:#efefef; max-width:100%; text-align:left; overflow:auto; text-transform:none; padding:1em; margin:.5em; position:relative; z-index:99999; '.$args['style'].'">';
			echo $prefix ? '<span style="background:'.$color.'; display:inline-block; color:white; border-radius:2px; font-weight:bold !important; font-size:0.8em; margin-right:.5em; padding:.25em .7em .3em;">'.$prefix.'</span>' : '';
			echo $file ? '<span style="opacity:0.5;">'.$file.'</span>' . NL : '';

			if ($title){
				echo '<strong style="font-size:1.5em; display:block;">'.$title.'</strong>' . NL;
			}
		
			if (count($params)){
				ob_start();
				
				var_dump(count($params) === 1 ? $params[0] : $params);

				$html = ob_get_clean();
				$html = strtr($html, [
					'<' => '&lt;',
					'>' => '&gt;',
				]);

				echo $html;
			}

			echo '</pre>';
		}
	}

	if ($args['die']) die();
}

function p (){
	add_log(func_get_args(), [
		'title' => false,
		'die'	=> true,
	]);
}

function _p (){
	add_log(func_get_args());
}

function _json (){
	_global('output_logs', false);

	$back   = to_backtrack(1);
	$params = func_get_args();
	array_unshift($params, $back['debug']);
	$params = json_encode($params);

	echo $params;
	header('Content-Type: application/json');

	die();
}

function _js (){
	add_log(func_get_args(), 'js');
}

function __js ($args){
	$args = to_args($args, array(
		'output' => 'js',
	));

	$params = func_get_args();
	array_shift($params);
	
	add_log($params, $args);
}

function _info ($msg=null){
	add_log(func_get_args(), [
		'prefix' => 'INFO',
		'color'  => '#3e9d00',
		// 'title'  => true,
		'file'   => false,
		'output' => 'js',
	]);
	return false;
}

function _warn ($msg=null){
	if (!is_dev()) return false;
	add_log(func_get_args(), [
		'prefix' => 'WARN',
		'color'  => '#f58303',
		'title'  => true,
		'output' => 'js',
	]);
	return false;
}

function _deprecated ($msg=null){
	if (!is_dev()) return false;
	add_log(func_get_args(), [
		'prefix' => 'DEPRECATED',
		'color'  => '#6534AF',
		'title'  => true,
		'output' => 'js',
	]);
	return false;
}

function _debug ($msg=null){
	if (in_array($msg, [START, START_ERROR, STOP])){
		set_global('debug_log', $msg);
		return;
	}

	if (!($type = get_global('debug_log'))) return;

	add_log(func_get_args(), [
		'prefix' => 'DEBUG',
		'color'  => 'gray',
		'output' => START_ERROR === $type ? 'error' : null,
		// 'output' => 'error',
		// 'output' => 'error'
	]);
}

function err ($msg){	
	// TODO add condition to not show this on LIVE server

	// throw new Exception($msg);

	// Add multiple ways of showing errors (as print, js, error logs) 	
	
	add_log(func_get_args(), [
		'prefix'=> 'ERROR',
		'title' => true,
		'style' => 'font-size:14px; color:red; font-family:monospace; max-width:100%; overflow:auto;',
	]);

	die();
}

function _err ($msg=null){
	if (!is_dev()) return false;
	add_log(func_get_args(), [
		'prefix' => 'ERROR',
		'color'	 => 'red',
		'title'  => true,
		'output' => 'js',
	]);
	return false;
}

function __err (){
	add_log(func_get_args(), 'error');
}

function ___err (){
	add_log(func_get_args(), [
		'title'  => false,
		'file'   => false,
		'output' => 'error'
	]);
}

function __trace (){
	$trace = array_map(function ($v){ 
		return "{$v['path']} [{$v['function']}][line {$v['line']}]";; 
	}, to_backtrack());

	$args = func_get_args();
	$url  = function_exists('get_url') ? get_url() : '';
	$trace= '[TRACE] ' . $url . NL . NL . implode(NL, $trace);
	$trace= str_replace("\n", "\n\t", $trace);
	$args = array_merge([$trace], $args);

	add_log($args, 'error');
}

function __time ($name=null, $args=null, $log=false){
	if ($args === true){
		$args = ['reset'=>true];
	}else if ($args === ':error'){
		$args = ['log'=>'error'];
	}

	$args = _args($args, [
		'reset' => false,
		'log'   => $log,
		'text'	=> '',
		'return'=> null,
	], 'text');

	$startup = get_global('startup', []);
	$timers  = get_global('timers', []); 
	$start 	 = $name ? get_value($timers, $name, null) : $startup['start'];
	$reset   = $args['reset'] || $start === null;

	$now     = microtime(true);
	$time 	 = $start ? ($now - $start) : 0;
	$time  	 = $time >= 0.0001 ? number_format($time, 4).'s' : '0s';

	// save the new time
	$timers[$name] = $now;
	set_global('timers', $timers);
	
	if (!$reset && $args['log']){
		$log = is_string($name) ? $name : '';

		if ($args['text']){
			$log = $log ? "{$log}: {$args['text']}" : $args['text']; 
		}

		$log = '[' . str_pad($time, 10, ' ', STR_PAD_LEFT) . '] ' . $log;

		if ($args['log'] === 'js'){
			add_log($log, [
				'prefix' => 'Timer',
				'color'  => '#0c28af',
				'file'   => false,
				'title'  => true,
				'output' => 'js',
			]);
		}else{
			error_log("[Timer]{$log}");
		}
	}

	if ($args['return'] === 'seconds'){
		return (float)$time;
	}

	return $time;
}

function _time ($name=null, $args=null){
	$log = _config('time_log_type', 'js');
	__time($name, $args, $log);
}

function __performance ($key='*', $start=false){
	$performances = _global('performances', []);
	$performance  = isset($performances[$key]) ? $performances[$key] : null;
	
	if ($start || !$performance){
		$performances[$key] = [
			'time'  => microtime(true),
			'bytes' => memory_get_usage(),
		];
		_global(['performances'=>$performances]);
	}else{
		$time  = microtime(true) - $performance['time'];
		$time  = $time >= 0.0001 ? number_format($time, 4).'s' : '0s';
		$bytes = memory_get_usage() - $performance['bytes'];
		$bytes = $bytes > 0 ? to_human_filesize($bytes) : 0;
		$key   = $key === '*' ? '' : $key;
		__err("[PERFORMANCE] {$key} \n\tTime:   {$time} \n\tMemory: {$bytes}");
	}
}

function to_site_logs ($args=null){
	$args = _args($args, [
		'return' => '',	// ajax
		'echo'   => false,
	], 'echo');

	// Fatal error
	if ($e = error_get_last()){
		$types = array(
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
		);

		$type = get_value($types, $e['type']);
		if ($type){
			$params = explode('Stack trace:', $e['message']);

			if (isset($params[1])){
				$params[1] = 'Stack trace:' . to_simple_path($params[1]);
			}

			add_log($params, [
				'prefix' => $type,
				'title'  => true,
				'color'  => 'red',
				'file'   => ['path'=>to_simple_path($e['file']), 'line'=>$e['line']],
				'group'  => 'errors',
				'output' => 'js',
			]);
		}
	}
	
	$logs = get_global('all_logs');
	if (!$logs) return;

	$all  = [];
	$html = [];
	foreach ($logs as $item){ foreach ($item['logs'] as $log){
		$type  = $log['type'];
		$params= $log['params'];
		$count = $log['count'] > 1 ? "[x{$log['count']}]" : '';		
		$msg   = str_replace('{{ count }}', $count, $log['msg']);
		$msg   = str_replace("\n", "\\n", $msg);
		$all[] = $msg;
		$all[] = $params;
		$html[]= "console.{$type}({$msg})";
		if ($params) $html[]= "console.log({$params})";
	}}

	if ($args['echo']){ 
		?>
		<script>(function (){
			<?php echo implode(NL, $html); ?>
		}());</script>
		<?php 
	}
	
	set_global('logs', null);

	if ($args['return'] === 'ajax'){
		$startup = get_global('startup', array('start'=>null, 'memory'=>null));
		$time    = (microtime(true) - $startup['start']);
		$time    = number_format($time, 4) . "s";
		$logs    = base64_encode(json_encode($all));
		$all 	 = [
			'time' => $time,
			'logs' => $logs,
		];
	}

	return $all;
}

function to_site_meta ($echo=false){
	if (!_config('output_meta', true)) return;

	$startup = _global('startup', [
		'start'  => null,
		'memory' => null,
	]);

	$time   = (microtime(true) - $startup['start']);
	$memory = memory_get_usage() - $startup['memory'];

	global $api_version;
	
	$meta 					 = array();
	$other	 				 = get_global('site_meta', array());
	$meta['❤ Host']			 = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '?';
	
	if (is_debug()){
		$meta['⍰ Debug OFF'] = get_url() . '?debug=0';
	}else{
		$meta['⍰ Debug ON']	= get_url() . '?debug=1';
	}

	$meta['⸫ Server IP']	 = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '?';
	$meta['⸪ Client IP']	 = to_ip_address() . (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? ' (cloudflare)' : '');
	$meta['⊡ PHP']	 		 = 'v'.phpversion();
	$meta['⊡ PHP API']	 	 = "v{$api_version}";
	$meta['⊡ JS API']	 	 = '%js';
	$meta[]	= '-';
	$meta['✎ Theme'] 		 = 'v'._config('version');
	$meta['⚑ Domain']   	 = DOMAIN;
	$meta['☖ Environment']   = _config('env');
	$meta['◷ Time']          = number_format($time, 4) . "s";
	$meta['⌘ Memory']        = to_human_filesize($memory);

	if (function_exists('get_lang')){
		$meta['⍝ Lang'] = get_lang();
	}
	
	if ($echo){
		$bears = array(
			'ᕦʕ •ᴥ•ʔᕤ           ',
			'ʕ ㅇ ᴥ ㅇʔ           ',
			'ʕง•ᴥ•ʔง             ',
			'ʕ•ᴥ•ʔﾉ♡             ',
			'ʕ ꈍᴥꈍʔ              ',
			'ʕ – ᴥ – ʔ           ',
			'ʕノ•ᴥ•ʔノ ︵ ┻━┻   '
		);
		$bear = array_random($bears, true);
		$m    = array_each($meta, function ($v, $i){ return is_numeric($i) ? $v : str_pad($i, 24) . " {$v}"; });
		$o    = array_each($other, function ($v, $i){ 
			$has_icon = preg_match('/[^a-z0-9_\-\s]/i', $i);
			$pad 	  = $has_icon ? 24 : 22;
			$v 		  = is_array($v) || is_object($v) ? json_encode($v) : $v; return str_pad($i, $pad) . " {$v}"; 
		});
		$logs = array_merge($m, $o);
		$logs = implode('\n', $logs);
		?>
		<script>(function (){ setTimeout(function (){
			// wait a bit, since we need to get the Javascript window.site variables
			var color = '#000';
			var site  = window.site || {};
			var logs  = "<?php echo $logs; ?>".replace(/\%([a-z]+)/g, function (m, $1){ return (site[$1] || '-').toString().split(',').join(' - '); });
			console.info(
				'\n%c<?php echo $bear; ?>   ffto/core%c\n'+logs+'\n',
				'font-weight:bold; color:'+color+';',
				'font-weight:normal; color:#777;',
			);
		}, 1000); }());</script>
		<?php 
	}

	$meta = array_merge($meta, $other);

	return $meta;
}

function _site ($key, $value, $args=null){
	if (is_array($key)){
		$args  = $value;
		$value = null;
	}
	if (is_bool($args)){
		$args = array('before'=>$args);
	}

	$args = _args($args, array(
		'before' => false,
	));

	$meta = get_global('site_meta', []);
	$set  = to_set($key, $value, true);

	if ($args['before']){
		$meta = array_merge($set, $meta);
	}else{
		$meta = array_merge($meta, $set);
	}

	set_global('site_meta', $meta);
}
