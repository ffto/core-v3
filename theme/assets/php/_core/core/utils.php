<?php
// [ ] add "robots.txt" code to block AI: https://www.foundmyself.com/blog/how-to-protect-your-art-from-ai/
// [-] use a new namespace tddl_ (the dev daddy library) 
// [ ] simplify all the function prefix, instead of "string_", it will be "str_"
// [ ] The site functions will be simplified, with using files for the routes mostly
// [ ] for WP, the_css() should be able to target a specific page (only `post.php?action=edit` for example)
// [ ] try using "filter" name for callabck functions instead of "format"
// [ ] add simple tracking (turn on/off), a CSV file, like a simple Analytics 
// [ ] maybe for translation items, using "_t" and "__t" will return an Object, so you know it's already translated, no need to translate again in case __t() is called again on something that's already translated
// [ ] versionning should be the timestamp of the generated CSS and JS
// [ ] for translations, use a Class that wrap a string, so it's not translated a million time, AND it can add a "wrapper" for click-to-translate option when in "admin" mode (have a way to turn it off in some cases)

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
// [ ]		string.php
// [ ]	vendor
// [ ]	wp
// [ ]	wp-class
// [ ]	wp-modules
// [ ]	wp-plugins
// [ ]	site.php
// [ ]	utils.php
// [ ]	wp.php

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

/* =====================================================================================================================
Conditions
===================================================================================================================== */
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
 * @param mixed $v
 * @return boolean
 */
function is ($v){
	return $v || $v === 0 || $v === 0.0 || $v === '0' || (is_array($v) && !empty($v));
}

/**
 * Check if the value is falsy
 *
 * @param mixed $v 
 * @return bool
 */
function is_not ($v){
	return !is($v);
}

/**
 * Check if the value is defined (false or truthy)
 *
 * @param mixed $v 
 * @return bool
 */
function is_defined ($v){
	return $v === false || is($v);
}

/**
 * Check if it's a valid function
 *
 * @param mixed $v 
 * @param bool  $only_closure 	Return true only if the $callback is a Closure
 * @return bool
 */
function is_callback ($v, $only_closure=true){
	return $v && (
		(!$only_closure && is_callable($v)) ||
		($only_closure && $v instanceof Closure)
	);
}

// [ ] add is_str() to check if it's a string OR something considered like a string (eg.: instance of class Translation)
function is_str ($v, $like=null){
	// TODO match the like
	return is_string($v);
}

function is_key ($v){
	return is_string($v) || is_numeric($v);
}

/**
 * Check if the value is a valid number.
 *
 * ```php
 * is_nbr(45)  // true
 * is_nbr('45.56') // true
 * is_nbr('00045') // false
 * ```
 * 
 * @param mixed $v 
 * @return bool
 */
function is_nbr ($v){
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
function is_list ($v, $match=true){
	if (!is_array($v) || empty($v)) return false;

	if (function_exists('arrayis_list')){
		return arrayis_list($v) === $match;
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
function is_obj ($v){
	return is_list($v, false);
}

/**
 * Return true if the value is true (true OR 1), when used in a string args
 *
 * @param mixed $value
 * @param boolean $true
 * @param boolean $false
 * @return boolean
 */
function is_truthy ($v, $true=true, $false=false){
	if ($v instanceof Closure){
		return $false;
	}
	return ($v === true || $v === 'true' || $v == 1) ? $true : $false;
}

/**
 * Check if the value is falsy
 *
 * @param mixed 	$value
 * @param boolean 	$false
 * @param boolean 	$true
 * @return boolean
 */
function is_falsy ($v, $false=false, $true=true){
	return is_truthy($v, $false, $true); 
}

/**
 * Check if the value is a RegExp
 *
 * @param mixed $value 
 * @return bool
 */
function is_regexp ($v){
	return is_string($v) && preg_match('/^\/.+\/(i|m|g)?$/', $v);
}

/**
 * Check if a value is a args/options that should be parsed with the `_args()` method. 
 * It needs to be a string with key/value pairs with '=' as the glue.
 *
 * @param mixed $str 
 * @return bool
 */
function is_args ($str){
	return is_array($str) || (
		is_string($str) &&              // needs to be a string
		strpos($str, '=') !== false &&  // there's no separator
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
function _has ($v, $key){
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
function is_in ($v, $arr){
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
 * 		- ':like' 		calls is_like()
 * 		- ':not-like' 	calls is_like() and check if it's false
 *  - Is an array-object and all the match keys matches the array
 * 
 * ```php
 * is_like('John is 40', '/([a-z]+) is ([0-9]+)/i');
 * // true
 * 
 * is_like('John is in 40s', '/([a-z]+) is ([0-9]+)/i');
 * // false
 * 
 * is_like(['name'=>'John', 'age'=>40], function ($v){ return $v['age'] >= 40; });
 * // true
 * 
 * is_like('Bobby', 'bob');
 * // true
 * 
 * is_like([10,20,30], [20,30]);
 * // true
 * 
 * is_like([10,20,30], [20,99]);
 * // false
 * 
 * is_like(999, ['>'=>1000]);
 * // false
 * 
 * is_like(['name'=>'Bob', 'age'=>40], [
 *     'name' => ':is',
 *     'age'  => ['>='=>40],
 * ]);
 * // true
 * ```
 *
 * @param mixed $value 
 * @param mixed $match 
 * @return bool
 */
function is_like ($v, $match){
	$i = null;

	// if the value is an array, it could be an encoded $key,$value type
	if (is_array($v) && (isset($v['$key']) || isset($v['$value']))){
		list($i, $v) = _extract($v);
	}

	// similar match
	if ($v == $match){
		return true;
	}else if ($match === ':is' || $match === ':not-empty'){
		return is($v);
	}else if ($match === ':is-not' || $match === ':empty'){
		return is_not($v);
	// regexp
	}else if (is_string($v) && is_regexp($match)){
		return !!preg_match($match, $v);
	// 2 strings, so check if the $match is contained
	}else if (is_string($v) && is_string($match)){
		return stripos($v, $match) !== false;
	// callback function
	}else if (is_callable($match)){
		return _call($match, $v, $i);
	// check if all values in $match array exists in $v
	}else if (is_list($v) && is_list($match)){
		foreach ($match as $vv){
			if (!in_array($vv, $v)) return false;
		}
		return true;
	}else if (is_array($v) && is_array($match)){
		foreach ($match as $ii => $vv){
			// the searched key doesn't exists
			if (
				!isset($v[$ii])
				|| !is_like($v[$ii], $vv)
			) return false;
		}
		return true;
	}else if (is_array($match)){
		foreach ($match as $ii => $vv){
			$a = is_array($vv) && array_key_exists(0, $vv) ? $vv[0] : $vv;
			$b = is_array($vv) && array_key_exists(1, $vv) ? $vv[1] : null;

			if (($ii === '!=' && !($v != $a)) 
			 || ($ii === '<' && !($v < $a)) 
			 || ($ii === '>' && !($v > $a)) 
			 || ($ii === '<=' && !($v <= $a)) 
			 || ($ii === '>=' && !($v >= $a)) 
			 || (($ii === '<->' || $ii === ':between') && !($v > $a && $v < $b)) 
			 || (($ii === '<=>' || $ii === ':between-equal') && !($v >= $a && $v <= $b)) 
			 || (($ii === '>-<' || $ii === ':outside') && !($v < $a || $v > $b)) 
			 || (($ii === '>=<' || $ii === ':outside-equal') && !($v <= $a || $v >= $b))
			 || ($ii === ':like' && !is_like($v, $a))
			 || ($ii === ':not-like' && is_like($v, $a))
			 || ($ii === ':in' && !in_array($v, $a))
			 || ($ii === ':not-in' && in_array($v, $a))
			) return false;
		}

		return true;
		
	}

	return false;
}

/**
 * Check if the site has a new version, compared with the `config:version` value
 *
 * @param string $old 
 * @return bool
 */
function is_new_version ($old=null){
	// check if the current site version is higher than the `$old` value
	if ($old){
		$version = _config('version');
		return version_compare($old, $version, '<');
	}else{
		// this is set when checking the `global:$config_file` in `config_set_version()`
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
function pass_filter ($v, $filter=null){
	return !$filter
		|| ($filter === true && is($v))
		|| !!_call($filter, $v);
}

/**
 * Helper for function that can return an object. Usually used with $args that are parsed with `_args()`
 *
 * @param mixed $v
 * @return boolean
 */
function return_object ($v){
	return $v === true || (is_string($v) && ($v === ':object' || strtolower($v) === 'object'));
}

/* =====================================================================================================================
Casting
===================================================================================================================== */
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

	$value	  = is_defined($value) ? $value : [];
	$is_array = is_array($value);
	$is_string= false;

	// Try to check if the string isn't a good url parameter
	if (!$is_array && ($key = $args['key']) && !is_args($value)){
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
 * _array('1,2,3,,4', 'null=1', function ($v, $k, $i){
 *  return [
 *	    '$id'    => "item-{$i}",
 *		'$value' => $v,
 *	];
 * });
 * // ["item-0"=>1, "item-1"=>2, "item-2"=>3, "item-3"=>"", "item-4"=>4]
 * 
 * _array('Bob the <strong>dragqueen</strong>', ':words');
 * // ["Bob", "the", "dragqueen"]
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
	}else if (is_callback($args)){
		$args = ['filter'=>$args];
	}else if ($args === ':array'){
		$args = ['return'=>'array'];
	}

	$args = _args($args, [
		'separator' => ',',       // string spearator
		'trim'      => true,      // trim strings
		'decode'    => false,     // use "_value()"
		'object'    => false,     // consider object as array
		'null'      => false,     // null values allowed
		'filter'    => $filter,   // filter values
		'return'    => null,      // return [array]
	], 'separator');

	if (!is($arr)){
		$arr = [];
	}else if (is_string($arr)){
		$arr = trim($arr);
		$arr = explode($args['separator'], $arr);
	}else if (is_obj($arr) && !$args['object']){
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

		if (!pass_filter($v, $args['filter'])) continue;
		$_arr[$key] = $v;

		$i++;
	}

	if ($args['return'] === 'array'){
		$_arr = array_values($_arr);
	}

	return $_arr;
}

/**
 * Extract a batch of $keys (an the $value, if set). Good to use with `list()`
 *
 * ```php
 * $v = ['$key'=>45, '$value'=>['name'=>'John', 'age'=>40]];
 * list($i, $v) = _extract($v);
 * // $i = 45;
 * // $v = ['name'=>'John', 'age'=>40];
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
	}else if (is_key($keys)){
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
 * ```
 * 
 * @param mixed $v value
 * @param mixed $args 
 * 	- 'template' [null] Template to use 
 * 	- 'pair' [null] Gluing key and value with a pair item OR a pair template
 * 	- 'join' [$join] Join array values together
 * 	- 'prefix' [''] Add a prefix
 * 	- 'suffix' ['] Add a suffix
 * 	- 'pretty' [true] Make the JSON output pretty
 * 	- 'tabs' [0] Add tabs at the begining of the text
 * 
 * @param bool $join 
 * @return void
 */
function _string ($v, $args=null, $join=false){
	// nothing to convert
	if (is_string($v) && !$args){
		return $v;
	}

	if ($args === false){
		$args = ['pretty'=>false];
	}else if (is_numeric($args)){
		$args = ['tabs'=>$args];
	}

	$args = _args($args, array(
		'template' => null,
		'pair'     => null,
		'join'     => $join,
		'prefix'   => '',
		'suffix'   => '',
		'pretty'   => true,
		'tabs'     => 0,
	), 'template');

	// extract the values into key/value pair (keep a backup of $v for `_replace`)
	$_v = $v;
	list($key, $v) = _extract($v);
	
	if ($v === true){
		$v = 'true';
	}else if ($v === false){
		$v = 'false';
	}else if ($v === null){
		$v = 'null';
	}else if (is_a($v, 'DateTime')){
		$v = $v->format('Y-m-d H:i:s e');
	// array of items
	}else if (is_list($v)){
		$list = [];
		foreach ($v as $ii => $vv){
			if (!is($vv)) continue;
			
			$vv = [
				'$key'   => $ii,
				'$value' => $vv
			];

			$vv = _string($vv, [
				'pretty'   => $args['pretty'],
				'template' => $args['template'],
			]);

			$list[] = $vv;
		}

		$v = $list;
		if ($join = $args['join']){
			$v = implode($join === true ? '' : $join, $v);
		}
	// turn object into a pair of values
	}else if ((is_array($v) || is_object($v)) && $args['pair']){
		$list = [];
		foreach ($v as $ii => $vv){
			if (!is($vv)) continue;
			if (strpos($args['pair'], '{') !== false){
				$list[] = _replace($args['pair'], [
					'$key'   => $ii,
					'$value' => $vv,
				], '{}');
			}else{
				$list[] = $ii . $args['pair'] . _string($vv);
			}
		}

		$v = $list;
		if ($join = $args['join']){
			$v = implode($join === true ? '' : $join, $v);
		}
	// object that uses a template
	}else if ((is_array($v) || is_object($v)) && $args['template']){
		$v = _replace($args['template'], $_v, '{}');
	// pretty json
	}else if (is_array($v) || is_object($v)){
		$v = $args['pretty'] ? 
			json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 
			json_encode($v, JSON_UNESCAPED_UNICODE);			
	// use a template (if the value is a value value)
	}else if (is($v) && $args['template']){
		$v = _replace($args['template'], $v, '{}');
	}else{
		$v = (string)$v;
	}

	// prefix/suffix/tabs (if the value is valid)
	if (is($v) && is_string($v)){
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
 * Takes a string a convert it to a valid RegExp, escapes known characters
 * 
 * ```php
 * _regexp('a-long-name'); // "/a\-long\-name/"
 * ```
 *
 * @param string 	$re			String to convert
 * @param boolean 	$closed		Should the RegExp contain the opening/closing slashes
 * @return string
 */
function _regexp ($re, $closed=true){
	if (is_regexp($re)) return $re;
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
	$re = _regexp($re);

	$args = _args($args, [
		'group'    => false,     // groups instead of single value
		'multiple' => $multiple, // multiple items repeated
		'fallback' => null,
	], 'group');

	// find the value in the matched item
	$_value = function ($v) use ($args){
		if ($args['group']){
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
	}else{
		preg_match($re, $v, $match);
		$value = $_value($match);
	}

	// TODO return $fallback here too

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
		'callback' => null,     // callback that is called to return a value first
		'bool'     => true,
		'number'   => true,
		'string'   => true,
		'null'     => true,
		'json'     => false,
		'data'     => false,     // data URLs (used to be dataUrl)
		'trim'     => false,    // 
		'slash'	   => false,    // normally used with form/posts that adds by default slashes
		'filter'   => false,    // post filter
		'fallback' => $fallback,
	), 'fallback');

	$str  = trim($v, "\0");
	$istr = trim(strtolower($str), ' ');

	if (is_callable($args['callback'])){
		return $args['callback']($str);
	}else if ($args['data'] && strpos($istr, 'data:') === 0){
		// $data = to_match($str, '/data:(?P<type>[^;,]+)(?P<encoded>\;base64)?,(?P<data>.+)/');
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
	}else if ($args['number'] && is_nbr($str)){
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
	if ($args === true){
		$args = ['json'=>true];
	}

	$args = _args($args, array(
		'separator' => null,
	), 'separator');

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
 * Return a object set of key/value pair(s). If the $key is already an array, return it, if it's a string/int, create a set pair.
 * [old] to_set
 * 
 * ```php
 * to_pair('a', 123); // ['a'=>123];
 * ```
 * 
 * @param  mixed 	$key
 * @param  mixed 	$value
 * @param  boolean 	$allow_null	Allow null value to be used as a value
 * 
 * @return array 	Pair of key/values
 */
function to_pair ($key, $value=null, $allow_null=true){
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
 * 		- 'labels' [0] : 
 * 		- 'return' [0] : 
 * @return string
 */
function to_human_time ($ms, $args=null){
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
function to_human_bytes ($size, $args=null){
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

/* =====================================================================================================================
Helpers
===================================================================================================================== */
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
function apply ($callbacks, $params, $args=null){
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
	return apply($callbacks, $params);
}

/**
 * Call apply with an unknown amount of parameters. If the callbacks return nothing, will return "null" instead of the first parameter
 *
 * @param mixed $callbacks 
 * @return mixed
 */
function _call ($callbacks){
	$params = func_get_args();
	array_shift($params);
	return apply($callbacks, $params, [
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
	return apply($callbacks, $params, [
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
		if (is_callback($k)){
			$k($v, $value);     
		// make sure the element is an array and append/prepend the value/values
		}elseif (
			isset($value[':append']) 
			|| isset($value[':prepend']) 
			|| isset($value[':append-many'])
			|| isset($value[':prepend-many'])
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

	$keys  = str_replace('/', '.', $keys);	// alternate version of separating the $keys, with a "/"
	$keys  = str_replace('[]', '.+', $keys);
	$keys  = preg_replace('/\[([^\]]+)\]/', '.$1', $keys);
	$keys  = _array($keys, '.');
	$count = count($keys);
	
	$_walk = function (&$v, $_w, $i=0) use ($keys, $count, $value, $_set){
		$k       = $keys[$i];
		$is_last = $i === ($count-1);

		// simple way to append to a new/old list with a "+" key
		if ($k === '+'){
			$v = is_list($v) ? $v : [];
			$k = count($v);
		}

		// if not at the end of the path, $value will be path the value, not the one to save
		if (!$is_last){
			if (is_array($v) && array_key_exists($k, $v)){
				$value = $v[$k]; 
			}elseif (is_object($v) && property_exists($v, $k)){
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

	return $_walk($item, $_walk);
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
 * $v = _get($v, 'items.:first');
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
		|| is_not($keys)
		|| !(is_string($keys) || is_numeric($keys) || is_list($keys))
	) return $fallback;

	if (is_callable($args)){
		$args = ['filter'=>$args];
	}

	$args = _args($args, [
		'filter' => null,
		'empty'  => true,
	], 'empty');

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
		}else if (is_callback($k, true)){
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

	if (is_string($keys) && !preg_match('/\.|,|\/|\[/', $keys)){
		$keys = trim($keys);
		return $_get($item, $keys, 0, $fallback);
	}

	$alts  = is_array($keys) ? $keys : array_map('trim', explode(',', (string)$keys)); // to reduct dependencies of _array -> _get, we re-do a similar _array() behavior
	$value = null;

	foreach ($alts as $alt){
		// transform paths with square brackets to dots separators
		$keys = is_string($alt) ? strtr($alt, [
			'/' => '.',   // alternate version of separating the $path, with a "/"
			'[' => '.',   // simplify keys with brackets (eg.: values[5] = values.5)
			']' => ''
		]) : $alt;

		$keys  = _array($keys, '.');
		$count = count($keys);
		$v     = $item;
		for ($i=0, $l=$count; $i<$l; ++$i){
			$k = $keys[$i];

			// special code for path key
			if (is_list($v)){
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
 * _replace('{ name } is { age } and has { child_count, count } children. He\'s a { job || human }', [
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

	$data = $data ? _args($data) : $data;			 // make sure the data is an array
	$str  = is_callback($str) ? $str($data) : $str; // make sure the string is a string (if a callback, call it)

	if($args === '::')			$args = ['match'=>'::'];
	else if ($args === ':')		$args = ['match'=>':'];
	else if ($args === '`')		$args = ['match'=>'`'];
	else if ($args === '$')		$args = ['match'=>'$'];
	else if ($args === '[]')	$args = ['match'=>'[]'];
	else if ($args === '{}')	$args = ['match'=>'{}'];
	else if ($args === '{{}}')	$args = ['match'=>'{{}}'];

	// TODO maybe have other types of brackets, maybe use emoji for fun

	$args = _args($args, [
		'match'    => null,   // variable matching
		'fallback' => null,   // fallback value if nothing ... is this necessary??
		'match'	   => '{}',
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
	else if ($args['match']) 		  	$re = to_regexp($args['match']);  					// custom matching
	// else							 	$re = '/\{{2}\s*([^}|]+)(?:\|([^}]*))?\s*\}{2}/';  		// {{ value }} with fallback: {{ value | fallback }}
	


	$raw  = null;
	$_str = trim($str);
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
 * ```
 *
 * @param mixed $arr 
 * 	- 'separator' [','] use if the $arr is a string
 * 	- 'key' [null] key to use to get the element property
 * 	- 'template' [null] template to use to parse the item
 * 	- 'filter' [true] filter through the items. TRUE will return what's defined or a callback can be used
 * 	- 'return' [false] key to return, one of many keys
 * 	- 'reduce' [false] reduce the results into 1 value
 * 	- 'single' [false] return only 1 item
 * 	- 'join' [$join] join the array together with a glue 
 * 
 * @param mixed $args 
 * @param mixed $join 
 * @return void
 */
function _each ($arr, $args=null, $join=null){
	if (is_bool($args)){
		$args = ['single'=>$args];
	}else if (is_callback($args)){
		$args = ['template'=>$args];
	}else if ($args === ':words'){
		$args = ['separator'=>' '];
		$arr  = is_string($arr) ? strip_tags($arr) : $arr;
	}
	
	$args = _args($args, array(
		'separator' => ',',
		'key'       => null,       // get a specific value from the items
		'template'  => null,
		'filter'    => true,
		'return'	=> false,
		'reduce'	=> false, 	// if an array is returned, it will be merge with the results
		'single'    => null,
		'join'      => $join,
	), 'template');

	// get a range of values
	if ((is_int($arr) || is_float($arr))){
		$arr = $arr == 0 ? [] : range(1, floor($arr));
	}else{
		// if it's an object, and the single prop isn't set, consider it's to return an object
		if ($args['single'] === null && is_obj($arr)){
			$args['single'] = true;
		}

		$arr = _array($arr, [
			'separator' => $args['separator'],
			'object'	=> true,	// it's can't ever be just an object, always a list array
			'decode'    => true,	// parse/decode the values
		]);
	}

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

	$template = $args['template'];
	$index    = 0;
	$keys     = array_keys($arr);
	$count    = count($arr);	
	$items = [];
	foreach ($arr as $key => $v){
		$i = $index++;
		$v = $args['key'] ? _get($v, $args['key']) : $v;
	
		if (is_callback($template)){
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
			
			$v = _apply($template, $v, $key, $data);
		}elseif (is_regexp($template)){
			$v = _match($v, $template);
		}elseif (is_string($template)){
			$v = _replace($template, [
				'$index' => $i,
				'$key'   => $key,
				'$value' => $v,
			], '{}');
		}

		// get the key/value if set in in a special array with "$key" and/or "$value"
		list($key, $v) = _extract($v);

		// return a couple of specific keys
		if ($pluck_keys){
			$vv = [];
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
		if (!pass_filter($v, $args['filter'])) continue;

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
		
		// TODO maybe remove this commented old code
		// if (is_array($key)){
		// 	$items = _set($items, $key, $v);
		// }else{
		// 	$items[$key] = $v;
		// }
		_set($items, $key, $v);

		// only 1 item to process
		if ($args['single']){
			break;
		}
	}

	// return 1 item
	if ($args['single']){
		$items = reset($items);
	// wrapping the results means all items needs to be joinded
	}else if (is_array($items)){
		if ($join = $args['join']){
			$items = implode($join, $items);
		}
	}

	return $items;
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
 * @param mixed $key 
 * @param mixed $value 
 * @param mixed $apply_filters apply the filters on that global key (eg.: key=name, then "set_global-name" filter). Set to false to skip
 * @return mixed
 */
function set_global ($key, $value=null, $apply_filters=true){
	// set multiple values
	if (is_array($key)){
		foreach ($key as $i => $v) set_global($i, $v, $value);
		return $key;
	}

	global $GLOBALS_VALUES;
	$key    = is_object($key) || is_array($key) ? spl_object_hash($key) : $key; // ability to set a global value with an object/array
	$value  = $apply_filters ? _filters("set_global-{$key}", $value) : $value;
	$values = is_array($GLOBALS_VALUES) ? $GLOBALS_VALUES : [];
	_set($values, $key, $value);

	$GLOBALS['GLOBALS_VALUES'] = $values;
	
	return $value;
}

/**
 * Get a value from the $GLOBALS, return the $fallback if not found
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @param mixed $apply_filters apply the filters on that global key (eg.: key=name, then "set_global-name" filter). Set to false to skip
 * 
 * @return mixed
 */
function get_global ($key=null, $fallback=null, $apply_filters=true){
	global $GLOBALS_VALUES;
	$values = is_array($GLOBALS_VALUES) ? $GLOBALS_VALUES : [];

	if ($key){
		$key   = is_object($key) || is_array($key) ? spl_object_hash($key) : $key;
		$value = _get($values, $key, $fallback);
		$value = $apply_filters ? _filters("get_global-{$key}", $value) : $value;
		return $value;
	}else{
		return $values;
	}
}

/**
 * Shortcut to `set_global()` and `get_global()`
 *
 * @param mixed $key 
 * @param mixed $value Set value if $is_set is true, fallback value if not
 * @param bool $save Setting a value
 * @return mixed
 */
function _global ($key=null, $value=null, $save=false){
	if (is_array($key)){
		return set_global($key);
	}else if ($save){
		return set_global($key, $value);
	}else if ($key){
		return get_global($key, $value);
	}else{
		return get_global();
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
		return _global('$ctx.'.$key, $value, true);
	}else if ($key){
		return _global('$ctx.'.$key);
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
function add_hook ($hook, $key, $depth, $callback=null){
	if (is_callable($depth)){
		$callback = $depth;
		$depth    = 10;
	}else if ($depth === null){
		$depth 	  = 10;
	}

	$path  = '$hooks.'.$hook;
	$hooks = get_global($path, [], false);

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
	
	set_global($path, $hooks, false);
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
function remove_hook ($hook, $key, $depth=null, $callback=null){
	if (is_callable($depth)){
		$callback = $depth;
		$depth    = 10;
	}

	$path  = '$hooks.'.$hook;
	$hooks = get_global($path, [], false);
	
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

	set_global($path, $hooks, false);
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
function apply_hooks ($hook, $key){
	$path   = '$hooks.'.$hook.'.'.$key;
	$hooks  = get_global($path, [], false);
	$params = func_get_args();
	$params = array_slice($params, 2);
	
	foreach ($hooks as $callbacks){
		$params[0] = apply($callbacks, $params);
	}

	return isset($params[0]) ? $params[0] : null;
}

// Since Wordpress already has those functions, we'll created the some for the none-Wordpress websites
if (
	!function_exists('add_filter') && !function_exists('remove_filter') && !function_exists('apply_filters') && 
	!function_exists('add_action') && !function_exists('remove_action') && !function_exists('do_actions')
){
	function add_filter (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('add_hook', $params);
	}
	function remove_filter (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('remove_hook', $params);
	}
	function apply_filters (){
		$params = array_merge(['filters'], func_get_args());
		return call_user_func_array('apply_hooks', $params);
	}
	function add_action (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('add_hook', $params);
	}
	function remove_action (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('remove_hook', $params);
	}
	function do_actions (){
		$params = array_merge(['actions'], func_get_args());
		return call_user_func_array('apply_hooks', $params);
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

/* =====================================================================================================================
Debug
===================================================================================================================== */
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
	$path   = $_performance ? '$performances.'.$key : '$timing.'.$key;
	$time   = microtime(true);
	$memory = $_performance ? memory_get_usage() : 0;
	$item   = _global($path, [
		'time'   => $time,
		'memory' => $memory,
	]);

	// outputs
	$_time   = to_human_time($time - $item['time'], ['decimals'=>4, 'labels'=>':small']);
	$_memory = to_human_bytes(($memory - $item['memory']), 2);
	
	// save the new values
	$item['time']   = $time;
	$item['memory'] = $memory;
	_global($path, $item, true);

	// output the time in a log (js, error, screen, js by default)
	if ($log){
		$time   = '[' . str_pad($_time, 12, ' ', STR_PAD_LEFT) . ']';
		$memory = $_performance ? ' [' . str_pad($_memory, 12, ' ', STR_PAD_LEFT) . ']' : '';
		$msg 	= $time . $memory . (is_string($name) ? " {$name}" : '');

		// performances
		_log([
			'type'     => $log, 
			'prefix'   => $_performance ? 'PERFORMANCE' : 'TIME',
			'color'    => '#0c28af',
			'trace'    => false,
			'wrap'     => false,     // screen: do now style the wrapper
			'minimize' => false,     // js: don't minimize the output
		], $msg);
	}

	return $_performance ? [
		'time'   => $_time,
		'memory' => $_memory,
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

/* =====================================================================================================================
Files
===================================================================================================================== */
/**
 * Clean up a path/url by removing multiple slashes and resolve the path when using "." and ".."
 *
 * ```php
 * to_cleanpath('/aaa/bbb/.././file.ext');
 * // /aaa/file.ext
 * 
 * to_cleanpath('http://asdf/bbb/./asdf');
 * // "http://asdf/bbb/asdf"
 * ```
 * 
 * @param mixed $path 
 * @param bool $is_dir 
 * @return string
 */
function to_cleanpath ($path, $is_dir=false){
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
function to_path ($path, $args=null, $data=null){
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
		$aliases = _global('$directories.aliases', []);
		$path 	 = strtr($path, $aliases);
	}

	// data
	if (is_array($args['data'])){
		$path = _replace($path, $data);
	}

	$path = to_cleanpath($path, $args['is_dir']);

	if ($args['is_url']){
		$path = path_to_url($path);
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
 * get_path('@routes/index');
 * // "/Users/computer/Documents/Websites/_sites/project/theme/routes/index.php"
 * 
 * get_path('@routes/index', ':url');
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
function get_path ($path, $args=null, $fallback=null){
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
	$paths = _filters('to_path-paths', $paths);

	$_paths = [];
	foreach ($paths as $path){
		if (!$path || !is_string($path)) continue;

		// Return the URL right away
		if ($args['url'] && is_url($path)){
			return $path;
		}

		$path 	  = urldecode($path); // make sure the path is a proper one
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
		$path = to_path($path, null, $args['data']);
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
		$url = path_to_url($_path);
		$url = _version($url, $args['version']);
		return $url;
	}else if ($return = $args['return']){
		$info = get_file_info($_path, ['version'=>$args['version']]);

		if (return_object($return)){
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
function to_glob ($path, $args=null, $parse=true){
	$args = _args($args, [
		'parse' => $parse,
		'flags' => 0,
	]);

	$path = $args['parse'] ? to_path($path) : $path;

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
			$files = array_merge($files, to_glob($p, $args, false));
		}
	}

	$files = array_unique($files);
	sort($files);

	return $files;
}

/**
 * Find a list of files/$directories. 
 *
 * ```php
 * get_files('@routes/*.php', 'return=filename');
 * // ['+layout.phg', 'index.php', 'style.php']
 * 
 * get_files('@routes/*.php', 'return=filename&join= / ');
 * // "+layout.php / index.php / style.php"
 * ```
 * 
 * @param mixed $paths
 * @param mixed $args
 * 	- 'dir' [null] root directory
 * 	- 'type' [null] (file, dir, all/null)
 * 	- 'match' [null] match like for the paths
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
 * 	- 'sort' [null] sort the files (with `arr_sort()`)
 * 	- 'join' [null] join the files
 * 
 * @return array|string
 */
function get_files ($paths, $args=null, $_parent=null){
	if ($args === true){
		$args['info']    = true;
		$args['meta']    = true;
		$args['content'] = true;
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
		'info'    => false,       
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

	$dir         = to_path($args['dir'], true);
	$paths       = _array($paths);
	$exclude     = $args['exclude'];
	$match       = $args['match'];
	$only_file   = $args['type'] === 'file';
	$only_dir 	 = $args['type'] === 'dir';
	$format_pre  = _get($args, 'format.0, format');
	$format_post = _get($args, 'format.1');
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
		$path  = to_path($path);
		$ext   = pathinfo($path, PATHINFO_EXTENSION);
		$files = to_glob($path);
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
				($exclude && is_like($filename, $exclude)) 
				|| ($match && !is_like($filename, $match))
				|| ($only_file && !is_file($filepath))
				|| ($only_dir && !is_dir($filepath))
			){
				continue;
			}

			$is_file = is_file($filepath);
			$is_dir  = !$is_file;
			$routes  = _global('$directories.aliases.@routes');
			$route 	 = null;

			// include file
			if ($is_file && $args['include'] === ':once'){
				include_once $filepath;
			}else if ($is_file && $args['include']){
				include $filepath;
			}
			
			// find the route, if in the @routes
			if (strpos($filepath, $routes) === 0){
				$route = '/' . str_replace([$routes,'.php'], '', $filepath);
			}
			
			$file = [
				'index'        => $i,
				'length'       => $count,
				'depth'        => $_parent['depth'],
				'filename'	   => $filename,
				'ext'		   => $is_file ? pathinfo($filename, PATHINFO_EXTENSION) : '',
				'filepath'	   => $filepath,
				'directory'    => $dir,
				'relative' 	   => str_replace($_parent['dir'], '', $filepath),
				'route'		   => $route,
				'is_file'	   => $is_file,
				'is_dir'	   => !$is_file,
				'has_children' => false,
			];

			if ($args['info'] && function_exists('get_file_info')){
				$info = get_file_info($filepath);
				$file = array_merge($file, $info);
			}
			if ($args['meta'] && function_exists('get_file_meta')){
				$file['meta'] = get_file_meta($filepath);
			}
			if ($args['content'] && function_exists('_file')){
				$file['content'] = _file($filepath);
			}

			$file = _apply($format_pre, $file, $filepath, $args);
			
			// try extracting pair or $key/$value
			list($key, $file) = _extract($file);

			if ($return){
				$file = _get($file, $return);
			}

			if (!pass_filter($file, $args['filter'])) continue;

			// add the item to the files
			if ($key !== null)	$_files[$key] = $file; 
			else				$_files[] = $file;

			// go deeper
			if ($args['deep'] && $is_dir){
				$_args = array_merge($args, [
					'dir'  => $filepath,
					'deep' => is_numeric($args['deep']) ? $args['deep'] - 1 : $args['deep'],
				]);
				
				$children = get_files($paths, $_args, [
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
	if (($sort = $args['sort']) && function_exists('arr_sort')){
		$sort  = is_truthy($sort) ? '*' : $sort; 
		$items = arr_sort($items, $sort);
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
 * set_directory('@cache/stats');
 * ```
 * 
 * @param mixed $dir 
 * @return string
 */
function set_directory ($dir){
	$dir  = to_path($dir, true);
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
 * Recursivly delete a directory and it's children.
 *
 * @param mixed $path 
 * @return bool
 */
function remove_directory ($path){
	$path = get_path($path);
	
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

/**
 * Save a value into a file. For "json" and "csv" file extension, the content will be encoded properly.
 *
 * ```php
 * set_file('@cache/bob.txt', new DateTime(), true);
 * 
 * set_file('@cache/bob.txt', new DateTime(), ':url');
 * 
 * set_file('@cache/bob.json', [1,2,3]);
 * 
 * set_file('@cache/bob.csv', ['name'=>'John', 'age'=>40]);
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
function set_file ($path, $value='', $args=null){
	if ($args === true){
		$args = ['append'=>true];		
	}else if ($args === ':url'){
		$args = ['return'=>'url'];
	}

	$args = _args($args, array(
		'type'   => null,    // [null, csv, serialize, ...]
		'append' => false,
		'return' => false,
	), 'type');

	$path = to_path($path);
	$ext  = pathinfo($path, PATHINFO_EXTENSION);

	// automatic detection of file type
	if ($ext === 'csv') 		$args['type'] = 'csv';
	else if ($ext === 'json')	$args['type'] = 'json';
	
	// delete the file
	if (is_not($value)){
		is_file($path) && unlink($path);
	// create the directory and the file
	}else if (!file_exists($path)){
		$dir = pathinfo($path, PATHINFO_DIRNAME); 
		set_directory($dir);

		// create the file
		$file = fopen($path, 'a');
		chmod($path, 0755);
		fclose($file);
	}

	if (is($value)){
		if ($args['type'] === 'csv'){
			$value = is_list($value) ? $value : [$value];
			$file  = fopen($path, $args['append'] ? 'a' : 'w');

			// empty file, add the headers
			clearstatcache();
			if (!$args['append'] || !filesize($path)){
				$first   = reset($value);
    			$headers = array_keys($first);
				fputcsv($file, $headers);
			}

			// add all the rows
			foreach ($value as $row){
				fputcsv($file, $row);
			}

			fclose($file);
		}else{
			// encoding
			if (is_array($value) || is_object($value)){
				if ($args['type'] === 'json'){
					$value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				}else if ($args['type'] === 'serialize'){
					$value = serialize($value);
				}else{
					$value = _string($value);
				}
			}

			// encoding the paths (so when moving from one server to another, the paths can still work)
			$value = encode_paths($value);
			
			if ($args['append']){
				$separator = is_string($args['append']) ? $args['append'] : NL;
				$separator = filesize($path) ? $separator : '';	// only add the separator if there's already content
				file_put_contents($path, $separator.$value, FILE_APPEND);
			}else{
				file_put_contents($path, $value, LOCK_EX);
			}
		}
	}

	if (return_object($args['return'])){
		return [
			'path' => $path,
			'url'  => path_to_url($path),
		];
	}else if ($args['return'] === 'url'){
		return path_to_url($path);
	}

	return $path;
}

/**
 * Get the file data.
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
function get_file ($path, $args=null, $fallback=null){
	$path = get_path($path);
	$args = _args($args, array(
		'type'      => null,        // [null, csv, serialize, ...]
		'page'		=> 0,			// for CSV pagination
		'limit'		=> 0,			// for CSV
		'decode'	=> true,		// be able to get the value
		'fallback'  => $fallback,
	), 'type');

	if (!$path) return $args['fallback'];	

	// automatic detection of file type
	$ext = pathinfo($path, PATHINFO_EXTENSION);
	if ($ext === 'csv') 		$args['type'] = 'csv';
	else if ($ext === 'json')	$args['type'] = 'json';
	
	// TODO test this if the lock works well
	$tmp = fopen($path, 'rb');
	@flock($tmp, LOCK_SH);
		
	
	$value = null;
	if ($args['type'] === 'csv'){
		ini_set("auto_detect_line_endings", "1");
		ini_set("serialize_precision", "-1");
		
		if (($file = fopen($path, 'r')) !== false){
			$headers = null;
			$rows    = [];
			$page 	 = $args['page'] ? $args['page'] : 1;
			$limit 	 = $args['limit'];
			$from 	 = ($page-1) * $limit;
			$to      = $from + $limit;
			$index 	 = 0;

			while (($row = fgetcsv($file, 0)) !== false){
				if (!$headers){
					$headers = $row;
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
		fclose($file);

		return $rows;
	}else{
		$value = file_get_contents($path);
		$value = decode_paths($value);
		
		// TODO add pagination with separator option (page/limit/separator)

		// deal with types
		if ($args['type'] === 'json'){
			$value = _json($value, $value);
		}else if ($args['type'] === 'serialize'){
			$value = unserialize($value);
		}else{
			$value = $args['decode'] ? _value($value) : $value;
		}
	}	
	
	@flock($tmp, LOCK_UN);
	fclose($tmp);

	return $value;
}

/**
 * Encode paths/urls that doesn't transfers well when moving from one server to another. The directories for @env, @root and the @root url are encoded.
 *
 * @param mixed $v 
 * @return void
 */
function encode_paths ($v, $_decode=false){
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
function decode_paths ($v){
	return encode_paths($v, true);
}

/* =====================================================================================================================
Server
===================================================================================================================== */
/**
 * Return the IP addres of the user. Tries many ways to get the proper IP.
 *
 * @todo maybe cache this value in the global
 * @param bool $anonymize Should the IP be less specific
 * @return string
 */
function to_ip ($anonymize=false){
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
function is_https (){
	return _get($_SERVER, 'REQUEST_SCHEME') === 'https'
		|| _get($_SERVER, 'HTTP_X_FORWARDED_PROTO') === 'https'
		|| _get($_SERVER, 'HTTPS') === 'on'
		|| _get($_SERVER, 'SERVER_PORT') == 443;
}

/**
 * Check if the server is a local environment. Either if the remote address is localhost, or if the server name match the "$config.host_local" value
 *
 * @return bool
 */
function is_local (){
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
function is_staging (){
	$server = $_SERVER['SERVER_NAME'];
	$hosts  = _config('host_staging', '');
	return $hosts && strpos($server, $hosts) !== false;
}

/**
 * Check if the server is the LIVE one
 *
 * @return bool
 */
function is_live (){
	return !is_local() && !is_staging();
}

/**
 * Check if the server is the DEV one (local or staging)
 *
 * @return bool
 */
function is_dev (){
	return is_local () || is_staging();
}

/**
 * Get the server environment
 *
 * @return string
 */
function get_environment (){
	$value = defined('ENVIRONMENT') ? ENVIRONMENT : null;

	if (!$value){
		if (is_local())		$value = 'local';
		else if (is_staging())	$value = 'staging';
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
function get_host ($args=null){
	$args = _args($args, array(
		'port'  => false,
		'slash' => true,
	), 'slash');

	$host = _config('host');
	if (!$host){
		$scheme = is_https() ? 'https://' : 'http://';
		$host 	= _get($_SERVER, 'HTTP_HOST, SERVER_NAME');
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
	$host = to_cleanpath($host, $args['slash']);
	
	return $host;
}

/* =====================================================================================================================
Config
===================================================================================================================== */
function _config ($key=null, $value=null, $save=false){
	$config = _global('$config');
	$set    = is_array($key) || $save ? to_pair($key, $value) : [];

	// default setup of options
	if ($config === null){
		_global('$config', $set, true);

		$abspath = defined('ABSPATH') ? ABSPATH : __DIR__;
		$env 	 = get_environment();
		
		// default config options (these are fixed values, can't be edited in other ways)
		config_add_option('abspath', null, $abspath);
		config_add_option('domain', null, 'theme');
		config_add_option('host_local', null, '.local');
		config_add_option('host_staging', null, '.dev');
		config_add_option('host_folder', null, '');
		config_add_option('host', null, get_host());
		config_add_option('env', null, $env);
		config_add_option('dir', 'merge=1&update=config_set_directories', [
			'root'    => '',
			'theme'   => 'theme/',
			'data'    => '+data/',
			'env'     => "+env-{$env}/",
			'cache'   => '@data/cache/',
			'uploads' => '@env/uploads/',
			'assets'  => '@theme/assets/',
			'images'  => '@assets/images/',
			'fonts'   => '@assets/fonts/',
			'scripts' => '@assets/js/dist',
			'styles'  => '@assets/css/dist/',
			'php'     => '@assets/php/',
			'core'    => '@assets/php/core/',
			'items'	  => '@theme/template-items/',
			'routes'  => '@theme/routes/',
			'api'     => '@theme/routes-api/',
			'admin'   => '@theme/routes-admin/',
			'dir'	  => '', 						// TODO current directory, should be generated from the current route directory for example, ...
		]);

		// config file is now available, so things can be fetched/saved to it
		$config_file = get_file('@env/config.json', null, []);
		_global('$config_file', $config_file, true);

		// site versioning
		config_add_option('version', 'save=1&update=config_set_version', '1.0.0');

		// include the core files.
		get_files([
			'*.php',
			'utils/*.php',
			'utils/**/*.php',
			'**/*.php',
		], [
			'dir'     => '@core',
			'include' => ':once',
		]);
		
		_actions('site-init', $set);

		$config = _global('$config');
		$set    = null;

		_actions('site-startup');

		add_action('site-shutdown', 'shutdown_config_save_file', 999);

		// TODO
	}
	
	// set many values
	if ($set){
		$options = _global('$config_options', []);

		// go through all the options and try setting the coresponding formatter 
		foreach ($set as $i => $v){
			$option  = isset($options[$i]) ? $options[$i] : null;
			$set[$i] = $option ? config_set_option($i, $v, null, $option) : $v;
		}

		// save in the config all the config (since there might be keys without a set option)
		$config = array_merge($config, $set);
		_global('$config', $config, true);

		return $set;
	// get a value
	}else if ($key){
		return _get($config, $key, !$save ? $value : null);
	}	

	return $config;
}

function config_add_option ($key, $option=null, $default=null, $desc=null){
	$config_key = '$config.'.$key;
	$option_key = '$config_options.'.$key;
	
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
	$value = config_set_option($key, $value, $option);

	_global($config_key, $value, true);	

	return $value;
}

function config_set_option ($key, $value, $_option=null){
	$option_key = '$config_options.'.$key;
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
		$previous = isset($file[$save_key]) ? $file[$save_key] : null;
				
		if ($previous != $value){
			$file[$save_key] = $value;
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
function config_set_directories ($dir){
	$abspath = _config('abspath');
	$aliases = [];
	$paths   = [];
	$urls    = [];
	$host	 = get_host(false);

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
function config_set_version ($version){
	$file = _global('$config_file');
	$old  = _get($file, 'version');

	// change the site context "is_new_version" to know there might be things to update
	if (version_compare($old, $version, '<')){
		_ctx('is_new_version', true);
	}

	return $version;
}

/**
 * When the PHP process is done, the config.json file can be updated (if anything changed)
 *
 * @return void
 */
function shutdown_config_save_file (){
	$dirty = _ctx('config_file_dirty');	
	if (!$dirty) return;
	
	$file = _global('$config_file');
	$file = array_merge([
		'timestamp' => gmdate('Y-m-d H:i:s e'),	// keep the last time something changed
	], $file);
	
	set_file('@env/config.json', $file);
}

/**
 * Shutdown of the PHP processing
 */
register_shutdown_function(function (){ 
	_actions('site-shutdown'); 
});
