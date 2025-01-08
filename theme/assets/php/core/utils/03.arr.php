<?php 
/**
 * @package Utils\Array
 */

/* =====================================================================================================================
Contants 
===================================================================================================================== */
define('RE_SORT_ITEMS', '/(?:(\$?[a-z*][a-z_0-9\-\.]*)((?:\s?(?:(?:\[[^\]]+\])|(?:[^,]+)))*))(?:,|$)/i');  // match: "gender [male, female, nonbinary] DESC NULL_BEFORE, phone ASC, age DESC" => "(gender)( [male, female, nonbinary] DESC NULL_BEFORE)", "(phone)( ASC)", "(age)( DESC)"
define('RE_SORT_FIELDS', '/\[([^\]]+)\]/');                                                                // match: "gender [male, female, nonbinary]" => "[(male, female, nonbinary)]

/* =====================================================================================================================
Quick functions
===================================================================================================================== */

/* =====================================================================================================================
Functions
===================================================================================================================== */
/**
 * Find item/items through an array.
 *
 * ```php
 * $people = [
 *     ['name' => 'John Doe', 'age' => 28, 'gender' => 'male', 'phone' => '555-1234'],
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
 * ffto_arr_find($people, ['age'=>29], true);
 * // ['name' => 'Lisa Brown', 'age' => 29, 'gender' => 'female', 'phone' => '555-3456']
 * 
 * ffto_arr_find($people, ['age'=>29], 'name');
 * // ["Lisa Brown", "Sophia Red"]
 * 
 * ffto_arr_find($people, ['$key'=>['>'=>4]], 'name');
 * // ["Anna White", "Paul Black", "Emma Gray", "David Blue", "Sophia Red"]
 * 
 * ffto_arr_find($people, ['age'=>['>='=>35]], ':key');
 * // [4, 8]
 * 
 * ffto_arr_find($people, ['age'=>['>='=>35]], ':key->value');
 * // [
 * // 	4 => ['name' => 'Chris Green', 'age' => 35, 'gender' => 'male', 'phone' => '555-9876'],
 * // 	8 => ['name' => 'David Blue', 'age' => 40, 'gender' => 'male', 'phone' => '555-8761']
 * // ]
 * 
 * $items = [1,10,'asdf',['bob'=>true], 99];
 * ffto_arr_find($items, 10, ':key', true);
 * // 1
 * 
 * ffto_arr_find($items, function ($v){ return is_numeric($v); }, ':key');
 * // [0, 1, 4]
 * ```
 * 
 * @todo find other types, like random, last, first, ...
 * 
 * @param mixed $arr 	
 * @param mixed $search 
 * @param mixed $args 
 * 	- 'single' [false]
 * 	- 'return' [false] Should you return a specific value, or maybe a key. 
 * 		- ':key'        = Return the key of the matched items
 * 		- ':key/value'  = Return the matched items with their key added (eg.: ['$key'=>'profile-00', 'name'=>'Bob', 'age'=>40])
 * 		- ':key->value' = Keep the value in the returned array
 * 		- other         = Tries to fetch the attributes in the matched itesm
 * @return array|mixed
 */
function ffto_arr_find ($arr, $search, $args=null, $single=false){	
	if (is_bool($args)){
		$args = ['single'=>$args];
	}

	$args = _args($args, array(
		'single' => $single,	// only 1 item to return
		'return' => false,		// ':key', ':key/value', or a _get()  ... maybe ':key' should be '$key' instead... or not
	), 'return');
	
	$return = $args['return'];
	$items  = [];
	foreach ($arr as $i => $v){
		$_v = ['$key'=>$i, '$value'=>$v];
		
		if (ffto_is_like($_v, $search)){
			if ($return === ':key'){
				$v = $i;
			}else if ($return === ':key/value'){
				$v = _set($v, '$key', $i);
			}else if ($return === ':key->value'){
				// nothing here, it's the default 
			}else if ($return){
				$v = _get($v, $return);
			}

			if (isset($v)){
				$items[$i] = $v;
			}

			if ($args['single']){
				break;
			}
		}
	}
	
	if ($return !== ':key->value'){
		$items = array_values($items);
	}
	
	return $args['single'] ? reset($items) : $items;
}

/**
 * Similar to array_combine, but does not break when the count of $keys and $values aren't the same
 *
 * ```php
 * ffto_arr_fuse(['name', 'age', 'gender'], ['Bob', 44]);
 * // ["name"=>"Bob", "age"=>44", "gender"=>null]
 * ```
 * 
 * @param mixed $keys 
 * @param mixed $values 
 * @param mixed $args 
 * 		- 'decode' [false] Decode the values for automatically formatting them
 * @return array
 */
function ffto_arr_fuse ($keys, $values, $args=null){
	$args = _args($args, [
		'decode' => false,
	]);

	$keys 	= is_array($keys) ? $keys : _array($keys);
	$values	= is_array($values) ? $values : _array($values);
	$item 	= [];

	foreach ($keys as $i => $key){
		$v          = array_key_exists($i, $values) ? $values[$i] : null;
		$v          = $args['decode'] ? _value($v) : $v;
		$item[$key] = $v;
	}

	return $item;
}

/**
 * Insert a new value in an array. The $position can be multiple values:
 * - ":start" or ":prepend":
 * - ":end" or ":append":
 * - ":middle" or ":center":
 * - ":random" or "?":
 * - an integer:
 * - an array with multiple possible positions:
 *   
 * ```php
 * $a = 'Bob, Maria, Zac';
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ':start');
 * // ["John Doe", "Bob", "Maria", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ':end');
 * // ["Bob", "Maria", "Zac", "John Doe"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', 2);
 * // ["Bob", "Maria", "John Doe", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', '35%');
 * // ["Bob", "John Doe", "Maria", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', '?');
 * // ["Bob", "Maria", "Zac", "John Doe"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ':middle');
 * // ["Bob", "John Doe", "Maria", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ['Maria'=>'before']);
 * // ["Bob", "John Doe", "Maria", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ['Maria'=>'after']);
 * // ["Bob", "Maria", "John Doe", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ['Maria'=>'after', 'Bob'=>'after']);
 * // ["Bob", "Maria", "John Doe", "Zac"]
 * 
 * $v = ffto_arr_insert($a, 'John Doe', ['Bob'=>'after', 'Maria'=>'after']);
 * // ["Bob", "John Doe", "Maria", "Zac"]
 * 
 * $a = [
 * 	'bob-the-man'    => 'Bob',
 * 	'maria-is-magic' => 'Maria',
 * 	'zac-4evar'      => 'Zac',
 * ];
 * $v = ffto_arr_insert($a, ['$key'=>'super-john', '$value'=>'John Doe'], ['maria-is-magic'=>'after']);
 * // ["bob-the-man"=>"Bob", "maria-is-magic"=>"Maria", "super-john"=>"John Doe", "zac-4evar"=>"Zac"]
 * ```
 *
 * @param mixed $arr 
 * @param mixed $value 
 * @param string $position 
 * @return array
 */
function ffto_arr_insert ($arr, $value, $position=':end'){
	$arr    = is_array($arr) ? $arr : _array($arr);
	$key    = null;
	$is_obj = ffto_is_obj($arr);

	if (isset($value['$id'])){
		_warn('Keys "$id" should be replaced by "$key"');
		$value['$key'] = $value['$id'];
	}

	// special kind of $value with a string key (eg.: ['$key'=>'key', '$value'=>'value'])
	if (isset($value['$key'], $value['$value'])){
		$key   = $value['$key'];
		$value = $value['$value'];
	}else{
		$key = is_string($value) && $is_obj ? _slug($key) : count($arr);
	}
	
	// item that will be added
	$item = [$key => $value];
	
	if (empty($arr)){
		return $item;		
	}
	
	if ($position === ':start' || $position === ':prepend'){
		return $is_obj ? $item + $arr : array_merge($item, $arr);
	}else if ($position === ':end' || $position === ':append'){
		return $is_obj ? $arr + $item : array_merge($arr, $item);
	}
	
	$count  = count($arr);
	$idx    = count($arr);
	if ($position === ':middle' || $position === ':center'){
		$idx = floor($count / 2);
	}else if ($position === ':random' || $position === '?'){
		$idx = random_int(0, $count);
	}else if (ffto_is_str($position, '%')){
		$idx = floor(((float)$position / 100) * $count);
	}else if (is_numeric($position)){
		$idx = $position;
	}else if (is_array($position)){
		$keys = $is_obj ? array_keys($arr) : $arr;
		foreach ($position as $k => $v){
			$i = array_search($k, $keys);
			if ($i === false) continue;
			$idx = $i + ($v === 'after' ? 1 : 0);	// before OR after
			break;
		}
	}

	$a   = array_slice($arr, 0, $idx, true);
	$b   = array_slice($arr, $idx, null, true);
	$arr = $a + $item + $b;
	
	return $is_obj ? $arr : array_values($arr);
}

/**
 * Return an array without the removed items.
 *
 * ```php 
 * $items = [1, 10, 66, 999];
 * ffto_arr_remove($items, ['>'=>15]);
 * // [1, 10]
 * 
 * ffto_arr_remove($items, ['<'=>15]);
 * // [66, 999]
 * ```
 * @todo add ways of remove specific types, like ":numeric", ":not-numeric", ...
 * 
 * @param [aray] $arr
 * @param [mixed] $value
 * @param [obj] $args
 * 	- 'single' 			[$single]	
 * 	- 'preserve_keys' 	[false]		
 * 	- 'removed' 		[false]	
 * @param [bool] $single
 * @return mixed
 */
function ffto_arr_remove ($arr, $value, $args=null, $single=false){
	$args = _args($args, [
		'single'        => $single,
		'preserve_keys' => false,
		'removed'		=> false,
	], 'preserve_keys');

	$keys    = ffto_arr_find($arr, $value, ':key', $args['single']);
	$keys    = $args['single'] ? [$keys] : $keys;
	$is_list = !$args['preserve_keys'] && ffto_is_list($arr);
	$_arr    = [];
	$_removed= [];

	foreach ($arr as $i => $v){
		if (in_array($i, $keys)){
			$_removed[$i] = $v;
		}else{
			$_arr[$i] = $v;
		}
	}

	if ($args['removed']){
		return $args['single'] ? reset($_removed) : $_removed;
	}

	return $is_list ? array_values($_arr) : $_arr;
}

/**
 * Return a value, and looping through items if it's out of bounds.
 * 
 * ```php
 * $items = [1, 10, 66, 999];
 * 
 * ffto_arr_loop($items, 4);
 * // 1
 * 
 * ffto_arr_loop($items, 4);
 * // 66
 * 
 * ffto_arr_loop($items, 43);
 * // 999
 * ```
 *
 * @param [type] $arr
 * @param [type] $idx
 * @param [type] $fallback
 * @return mixed
 */
function ffto_arr_loop ($arr, $idx, $fallback=null){
	if (!is_array($arr) || empty($arr)) return $fallback;

	$_idx = is_numeric($idx) ? $idx % count($arr) : null;

	if (isset($arr[$idx]))							return $arr[$idx];
	else if (!is_null($_idx) && isset($arr[$_idx]))	return $arr[$_idx];
	else if (isset($arr[0]))						return $arr[0];
	else											return $fallback;
}

/**
 * Flatten an array
 * 
 * ```php
 * $items = [1, 10, [98, 66, 32], 66, [33, [true, 0, false, true]], 999];
 * 
 * ffto_arr_flatten($items);
 * // 1,10,98,66,32,66,33,true,0,false,true,999]
 * ```
 *
 * @param [type] $arr
 * @return array
 */
function ffto_arr_flatten ($arr, $args=null){
	if (!is_array($arr) || empty($arr)) return [];
	$_arr = [];
	array_walk_recursive($arr, function($v) use (&$_arr) { $_arr[] = $v; });
	return $_arr;
}

/**
 * Sort array with either a string, a series of string, an object, an array or a callback function
 * 
 * Every string orderby has this pattern:
 * 	{$prop_name | "$key" | "*"} [{$fields}] {ASC|DESC} {CASE_SENSITIVE|CASE_INSENSITIVE} {NULL_BEFORE|NULL_AFTER}
 * 
 * Here's some examples:
 * - "age DESC CASE_INSENSITIVE"
 * - "name ASC NULL_BEFORE"
 * - "$key DESC"
 * - "* ASC NULL_BEFORE CASE_SENSITIVE"
 * 
 * ```php
 * $people = [
 *     ['name' => 'John Doe', 'age' => 28, 'gender' => 'male', 'phone' => '555-1234'],
 *     ['name' => 'Jane Smith', 'age' => 32, 'gender' => 'female', 'phone' => '555-5678'],
 *     ['name' => 'Sam Johnson', 'age' => 24, 'gender' => 'male', 'phone' => '555-8765'],
 *     ['name' => 'Lisa Brown', 'gender' => 'female', 'phone' => '555-3456'],
 *     ['name' => 'Chris Green', 'age' => 35, 'gender' => 'two-spirit', 'phone' => '555-9876'],
 *     ['name' => 'Anna White', 'age' => 22, 'gender' => 'female', 'phone' => '555-5432'],
 *     ['name' => 'Paul Black', 'age' => 31, 'gender' => 'male', 'phone' => '555-6543'],
 *     ['name' => 'Emma Gray', 'gender' => 'female', 'phone' => '555-4321'],
 *     ['name' => 'Emma Gray 2', 'age' => 27, 'gender' => 'female', 'phone' => '555-4321'],
 *     ['name' => 'David Blue', 'age' => 40, 'gender' => 'male', 'phone' => '555-8761'],
 *     ['name' => 'Sophia Red', 'age' => 29, 'gender' => 'female', 'phone' => '555-2345'],
 *     ['name' => 'Finish', 'gender' => 'two-spirit', 'phone' => '555-2345'],
 *     ['name' => 'Pat', 'age' => 29, 'gender' => 'nonbinary', 'phone' => '555-2345'],
 *     ['name' => 'Julia', 'age' => 30, 'gender' => 'nonbinary', 'phone' => '555-2345'],
 * ];
 * 
 * ffto_arr_sort($people, 'name ASC');
 * // {"name":"Anna White","age":22,"gender":"female","phone":"555-5432"},
 * // {"name":"Chris Green","age":35,"gender":"two-spirit","phone":"555-9876"},
 * // {"name":"David Blue","age":40,"gender":"male","phone":"555-8761"},
 * // {"name":"Emma Gray","gender":"female","phone":"555-4321"},
 * // {"name":"Emma Gray 2","age":27,"gender":"female","phone":"555-4321"},
 * // {"name":"Finish","gender":"two-spirit","phone":"555-2345"},
 * // {"name":"Jane Smith","age":32,"gender":"female","phone":"555-5678"},
 * // {"name":"John Doe","age":28,"gender":"male","phone":"555-1234"},
 * // {"name":"Julia","age":30,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Lisa Brown","gender":"female","phone":"555-3456"},
 * // {"name":"Pat","age":29,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Paul Black","age":31,"gender":"male","phone":"555-6543"},
 * // {"name":"Sam Johnson","age":24,"gender":"male","phone":"555-8765"},
 * // {"name":"Sophia Red","age":29,"gender":"female","phone":"555-2345"}
 * 
 * ffto_arr_sort($people, 'age ASC NULL_BEFORE')
 * // {"name":"Lisa Brown","gender":"female","phone":"555-3456"},
 * // {"name":"Emma Gray","gender":"female","phone":"555-4321"},
 * // {"name":"Finish","gender":"two-spirit","phone":"555-2345"},
 * // {"name":"Anna White","age":22,"gender":"female","phone":"555-5432"},
 * // {"name":"Sam Johnson","age":24,"gender":"male","phone":"555-8765"},
 * // {"name":"Emma Gray 2","age":27,"gender":"female","phone":"555-4321"},
 * // {"name":"John Doe","age":28,"gender":"male","phone":"555-1234"},
 * // {"name":"Sophia Red","age":29,"gender":"female","phone":"555-2345"},
 * // {"name":"Pat","age":29,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Julia","age":30,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Paul Black","age":31,"gender":"male","phone":"555-6543"},
 * // {"name":"Jane Smith","age":32,"gender":"female","phone":"555-5678"},
 * // {"name":"Chris Green","age":35,"gender":"two-spirit","phone":"555-9876"},
 * // {"name":"David Blue","age":40,"gender":"male","phone":"555-8761"}
 * 
 * ffto_arr_sort($people, 'gender [male, nonbinary, female] ASC NULL_AFTER');
 * // {"name":"John Doe","age":28,"gender":"male","phone":"555-1234"},
 * // {"name":"Sam Johnson","age":24,"gender":"male","phone":"555-8765"},
 * // {"name":"Paul Black","age":31,"gender":"male","phone":"555-6543"},
 * // {"name":"David Blue","age":40,"gender":"male","phone":"555-8761"},
 * // {"name":"Pat","age":29,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Julia","age":30,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Jane Smith","age":32,"gender":"female","phone":"555-5678"},
 * // {"name":"Lisa Brown","gender":"female","phone":"555-3456"},
 * // {"name":"Anna White","age":22,"gender":"female","phone":"555-5432"},
 * // {"name":"Emma Gray","gender":"female","phone":"555-4321"},
 * // {"name":"Emma Gray 2","age":27,"gender":"female","phone":"555-4321"},
 * // {"name":"Sophia Red","age":29,"gender":"female","phone":"555-2345"},
 * // {"name":"Chris Green","age":35,"gender":"two-spirit","phone":"555-9876"},
 * // {"name":"Finish","gender":"two-spirit","phone":"555-2345"}
 * 
 * ffto_arr_sort($people, 'gender [$genders] DESC', ['case'=>false, 'fields'=>[
 * 	'genders' => ['female','tWO-spirit'],
 * ]]);
 * // {"name":"Chris Green","age":35,"gender":"two-spirit","phone":"555-9876"},
 * // {"name":"Finish","gender":"two-spirit","phone":"555-2345"},
 * // {"name":"Jane Smith","age":32,"gender":"female","phone":"555-5678"},
 * // {"name":"Lisa Brown","gender":"female","phone":"555-3456"},
 * // {"name":"Anna White","age":22,"gender":"female","phone":"555-5432"},
 * // {"name":"Emma Gray","gender":"female","phone":"555-4321"},
 * // {"name":"Emma Gray 2","age":27,"gender":"female","phone":"555-4321"},
 * // {"name":"Sophia Red","age":29,"gender":"female","phone":"555-2345"},
 * // {"name":"Pat","age":29,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"Julia","age":30,"gender":"nonbinary","phone":"555-2345"},
 * // {"name":"John Doe","age":28,"gender":"male","phone":"555-1234"},
 * // {"name":"Sam Johnson","age":24,"gender":"male","phone":"555-8765"},
 * // {"name":"Paul Black","age":31,"gender":"male","phone":"555-6543"},
 * // {"name":"David Blue","age":40,"gender":"male","phone":"555-8761"}
 * 
 * ffto_arr_sort($people, 'age DESC NULL_BEFORE, phone ASC');
 * // ...
 * 
 * ffto_arr_sort($people, ['key'=>'age', 'null'=>'before']);
 * // ...
 * ```
 * 
 * @since 2025-01-06
 *  
 * @param [type] $arr
 * @param [type] $orderby
 * @param [type] $args
 * @return array
 */
function ffto_arr_sort ($arr, $orderby, $args=null){
	if (is_bool($args)){
		$args = ['preserve_keys'=>$args];
	// Simple orderby of the items, not item properties
	}else if (ffto_is_like($args, '/ASC|DESC/i')){
		$args    = _args($args, ['order'=>$args]);
		$orderby = '*';
	}

	$args = _args($args, array(
		'order'         => 'ASC',     // [asc, desc]
		'case'          => true,      // [true, false]
		'null'          => 'AFTER',   // [before, after]
		'fields'        => [],        // Fields to use for comparaison if an orderby items has a variable (eg.: [$genders] => $args['fields']['gendres] => [...])
		'preserve_keys' => null,      // [object, array]
		// 'values'	=> [],				// Using this to compare the items with a specific order of items (if found) // TODO not sure it's needed since 'fields' is there
	), 'order');
	
	if (!is_array($arr)) return false;
	if (empty($arr)) 	 return [];

	$preserve_keys = $args['preserve_keys'];
	if ($preserve_keys === null){
		$preserve_keys = ffto_is_obj($arr);
	}

	// make a copy of the array
	$arr = array_slice($arr, 0, null, true);

	// Split all the orderby items (from a string)
	if (is_string($orderby)){
		$orderby = _match($orderby, RE_SORT_ITEMS, false, true);
	// Make sure it's an array of the $orderby is an object array
	}else if (ffto_is_obj($orderby)){
		$orderby = [$orderby];
	}

	// Normalizing the orderbys
	$_orderby = array();
	foreach ($orderby as $v){
		// String orderby
		if (is_string($v)){
			$vv     = _match($v, RE_SORT_ITEMS, true);
			$key    = $vv[0];
			$v      = $vv[1];
			$fields = _match($v, RE_SORT_FIELDS);                        // get the fields if they exists
			$v      = strtoupper(preg_replace(RE_SORT_FIELDS, '', $v));  // leftover: NULL_BEFORE, NULL_AFTER, CASE_SENSITIVE, CASE_INSENSITIVE, DESC, ASC

			$v = [
				'key'    => $key,
				'order'  => strpos($v, 'DESC') !== false ? 'DESC' : (strpos($v, 'ASC') !== false ? 'ASC' : $args['order']),
				'case'   => strpos($v, 'CASE_INSENSITIVE') !== false ? false : (strpos($v, 'CASE_SENSITIVE') !== false ? true : $args['case']),
				'null'   => strpos($v, 'NULL_BEFORE') !== false ? 'BEFORE' : (strpos($v, 'NULL_AFTER') !== false ? 'AFTER' : $args['null']),
				'fields' => $fields,
			];
		// Callback function
		}else if (ffto_is_callback($v)){
			$v = [
				'key'  => $v,      // will be called on every items (a and b)
				'case' => false,
			];
		}
		
		if (!is_array($v)) continue;

		$v = _args($v, [
			'order'  => $args['order'],
			'case'   => $args['case'],
			'null'   => $args['null'],
			'fields' => null,
		]);

		$v['order'] = is_string($v['order']) ? strtoupper($v['order']) : $v['order'];
		$v['null']  = is_string($v['null']) ? strtoupper($v['null']) : $v['null'];

		$fields = $v['fields'];
		// try matching a fields set in $args
		if (is_string($fields) && strpos($fields, '$') === 0){
			$fields      = substr($fields, 1);
			$v['fields'] = _get($args['fields'], $fields);
		}else if ($fields){
			$v['fields'] = _array($fields);
		}else{
			$v['fields'] = null;
		}

		// Make all the fields upper-case if the $args['case'] = false 
		if ($v['fields'] && !$v['case']){
			$v['fields'] = array_map(function ($vv){ return is_string($vv) ? strtoupper($vv) : $vv; }, $v['fields']);
		}

		$_orderby[] = $v;
	}
	
	// Sorting all the items
	uasort($arr, function ($a, $b) use ($arr, $args, $_orderby){
		$compare = null;

		foreach ($_orderby as $v){
			$key = $v['key'];	// ["$id", "*", or a specific value/path]
			$aa  = $a;
			$bb  = $b;
			
			// ":id" shouldn't be used anymore
			if ($key === ':id' || $key === '$id'){
				$key = '$key';
				_warn('Keys ":id" and "$id" should be replaced by "$key"');
			}

			// Compared values = $key, * or a property of $a and $b
			if ($key === '$key'){
				$aa = array_search($a, $arr);
				$bb = array_search($b, $arr);
			}else if ($key !== '*'){
				$aa = _get($a, $key);
				$bb = _get($b, $key);
			}
			
			// clean the strings
			if (is_string($aa)){
				$aa = ffto_str_strip_accents(strip_tags($aa));  // remove html tags, remove accents
				$aa = $v['case'] ? $aa : strtoupper($aa);       // no case = uppercase
			}
			if (is_string($bb)){
				$bb = ffto_str_strip_accents(strip_tags($bb));  // remove html tags, remove accents
				$bb = $v['case'] ? $bb : strtoupper($bb);       // no case = uppercase
			}

			// same exact value
			if ($aa === $bb) continue;
			
			// Search the value in the fields to get it's order (if it doenst exits, it's null)
			if (is_array($v['fields'])){
				$aa = array_search($aa, $v['fields']);
				$bb = array_search($bb, $v['fields']);
				$aa = $aa === false ? null : $aa;
				$bb = $bb === false ? null : $bb;
			}

			// one of the attribute is missing
			if (is_null($aa)){
				$compare = $v['null'] === 'AFTER' ? 1 : -1;
			}else if (is_null($bb)){
				$compare = $v['null'] === 'AFTER' ? -1 : 1;
			}else{
				// make sure they are real numeric value 
				if (is_numeric($aa)){
					$aa = (float)$aa;
					$bb = (float)$bb;
				}

				// convert boolean to numbers
				if (is_bool($aa)){
					$aa = $aa ? 1 : 0;
					$bb = $bb ? 1 : 0;
				}

				// make the comparaison of the value
				if ($aa > $bb) 		$compare = 1;
				else if ($aa < $bb) $compare = -1;

				// switch the order 
				if ($compare && $v['order'] === 'DESC'){
					$compare *= -1;
				}
			}

			// The comparaison value has been found
			if ($compare) break;
		}

		return $compare;
	});

	$arr = $preserve_keys ? $arr : array_values($arr);
	
	return $arr;
}

/**
 * Traverse through a multi-dimension array. Navigating a special key OR simpler array without special key.
 * 
 * @since 2025-01-08
 * 
 * ```php
 * $family = [
 * 	['name'=>'Bob', 'children' => [
 * 		['name'=>'Marie', 'children' => [
 * 			['name'=>'Finish'],
 * 			['name'=>'Julia'],
 * 			['name'=>'Pat'],
 * 		]],
 * 		['name'=>'David']
 * 	]],
 * 	['name'=>'Emma'],
 * 	['name'=>'Sarah', 'children'=>[
 * 		['name'=>'Simon'],
 * 		['name'=>'Juni', 'children'=>[
 * 			['name'=>'Sophie'],
 * 			['name'=>'Marc']
 * 		]],
 * 		['name'=>'John']
 * 	]]
 * ];
 * 
 * // Only the people with children
 * $v = ffto_arr_traverse($family, function ($v, $a){
 * 	$c = _get($v, 'children', []);
 * 	return $c ? $v : false;
 * });
 * // [
 * //     "name" => "Bob",
 * //     "children" => [
 * //         [
 * //             "name" => "Marie",
 * //             "children" => []
 * //         ]
 * //     ]
 * // ],
 * // [
 * //     "name" => "Sarah",
 * //     "children" => [
 * //         [
 * //             "name" => "Juni",
 * //             "children"  => []
 * //         ]
 * //     ]
 * // ]
 * 
 * $v = ffto_arr_traverse($family, function ($v, $a){
 * 	$prefix = str_repeat('&nbsp;&nbsp;', $a['depth']) . ($a['depth'] ? '- ' : '');
 * 	echo "{$prefix}{$v['name']}";
 * 
 * 	$c = _get($v, 'children', []);
 * 	if ($c = count($c)){
 * 		echo " ({$c} children)";
 * 	}
 * 	echo "<br>";
 * });
 * // Bob (2 children)
 * //   - Marie (3 children)
 * //     - Finish
 * //     - Julia
 * //     - Pat
 * //   - David
 * // Emma
 * // Sarah (3 children)
 * //   - Simon
 * //   - Juni (2 children)
 * //     - Sophie   
 * // 	  - Marc
 * //   - John
 * 
 * $ul = [
 * 	'a1',
 * 	[
 * 		'b1',
 * 		'b2',
 * 		[
 * 			'c1',
 * 			'c2',
 * 		],
 * 		'b3',
 * 		[
 * 			'c3'
 * 		]
 * 	],
 * 	'a2'
 * ];
 * 
 * $v = ffto_arr_traverse($ul, [
 * 	'key'           => false,
 * 	'pre_walk'      => function ($v, $a){ echo NL.$a['tab'].'<ul>'; },
 * 	'pre_callback'  => function ($v, $a){ echo NL.$a['tab'].'<li>'.$v; },
 * 	'post_callback' => function ($v, $a){ echo '</li>'; },
 * 	'post_walk'     => function ($v, $a){ echo NL.$a['tab'].'</ul>'; },
 * ]);
 * // <ul>
 * // 	<li>a1</li>
 * // 	<ul>
 * // 		<li>b1</li>
 * // 		<li>b2</li>
 * // 		<ul>
 * // 			<li>c1</li>
 * // 			<li>c2</li>
 * // 		</ul>
 * // 		<li>b3</li>
 * // 		<ul>
 * // 			<li>c3</li>
 * // 		</ul>
 * // 	</ul>
 * // 	<li>a2</li>
 * // </ul>
 * ``` 
 *
 * @param [type] $arr
 * @param [type] $args
 * 	- 'key' 			[false] 			(default) The children key to traverse. If set to false, it will find array list to traverse.
 * 	- 'callback' 		[$pre_callback]  	Callback to format/filter the children
 * 	- 'post_callback' 	[$post_callback]  	Callback done after the children have been traversed
 * @param [type] $pre_callback	Callback to format/filter the children
 * @param [type] $post_callback	Callback done after the children have been traversed
 * @return array
 */
function ffto_arr_traverse ($arr, $args=null, $pre_callback=null, $post_callback=null){
	if (ffto_is_callback($args)){
		$args = array(
			'pre_callback'  => $args,
			'post_callback' => $pre_callback,
		);
	}

	$args = _args($args, array(
		'key'           => 'children',       // [key, false = on any array, if in a list array]
		'pre_callback'  => $pre_callback,
		'post_callback' => $post_callback,
		'pre_walk'      => null,
		'post_walk'     => null,
	), 'key');

	if (isset($args['callback'])){
		$args['pre_callback'] = $args['callback'];
	}

	// TODO in post_callback, that would be where we do things like "orderby" ...

	$key       = $args['key'];
	$pre       = ffto_is_callback($args['pre_callback']) ? $args['pre_callback'] : null;
	$post      = ffto_is_callback($args['post_callback']) ? $args['post_callback'] : null;
	$pre_walk  = ffto_is_callback($args['pre_walk']) ? $args['pre_walk'] : null;
	$post_walk = ffto_is_callback($args['post_walk']) ? $args['post_walk'] : null;
	$_walk = function ($arr, $depth, $_walk) use ($key, $pre, $post, $pre_walk, $post_walk, $args){
		$_arr    = [];
		$is_list = ffto_is_list($arr);
		$index 	 = 0;
		$count 	 = count($arr);
		
		$_args = [
			'depth'   => $depth,
			'tab'	  => str_repeat(TAB, $depth),
		];

		_apply($pre_walk, $arr, $_args);

		foreach ($arr as $i => $v){
			$_args2 = [
				'key'     => $i,
				'index'   => $index,
				'depth'   => $depth,
				'tab'	  => str_repeat(TAB, $depth + 1),
				'isFirst' => $index === 0,
				'isLast'  => $index === $count-1,
				'parent'  => $depth === null ? null : $arr,
			];

			$index++;

			// There's no keys, so any list array are traversed
			if ($key === false){
				if ($is_list && ffto_is_list($v)){
					$v = $_walk($v, $depth + 1, $_walk);
				}else{
					$v = $pre ? _apply($pre, $v, $_args2) : $v;
					$v = $v && $post ? _apply($post, $v, $_args2) : $v;
				}
			}else{
				$v = $pre ? _apply($pre, $v, $_args2) : $v;
				if (!$v) continue;

				$children  = _get($v, $key);
				$children  = is_array($children) ? $_walk($children, $depth + 1, $_walk) : [];

				_set($v, $key, $children); // Always add the "children" key
				
				$v = $post ? _apply($post, $v, $_args2) : $v;
			}
			
			if (ffto_is($v)){
				$_arr[$i] = $v;				
			}
		}
		
		_apply($post_walk, $arr, $_args);

		return $is_list ? array_values($_arr) : $_arr;
	};

	$is_obj = ffto_is_obj($arr);
	$_arr   = $is_obj ? [$arr] : $arr;
	$_arr   = $_walk($_arr, 0, $_walk);
	$arr 	= $is_obj ? reset($_arr) : $_arr;

	return $arr;
}

/* =====================================================================================================================
Casting
===================================================================================================================== */
function ffto_arr_to_columns ($arr, $count=2, $args=null){
	if (!$count) return [];

	$args = _args($args, [
		'callback' => null,
	]);


	/*
	$values = array();
	$total  = 0;

	// use a callback function to decode how we can decide in which column the item would be. Generally by calculating the about of characters there is
	if (ffto_is_callback($callback)){
		$total = 0;
		foreach ($arr as $v){
			$values[]  = $total;
			$value     = ffto_apply($callback, array($v));
			$total 	  += $value;
		}
	}else{
		$values = array_keys($arr);
		$total  = count($arr);
	}

	$columns = array();
	$total   = $total / $count;
	foreach ($arr as $i=>$item){
		$value = floor($values[$i] / $total);
		$index = $value % $count;

		if (!isset($columns[$index])){
			$columns[$index] = array();
		}

		$columns[$index][] = $item;
	}

	return $columns;
	*/
}