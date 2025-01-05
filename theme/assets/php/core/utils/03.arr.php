<?php 
/**
 * ffto_arr_find
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
 * $v = ffto_arr_insert($a, ['$id'=>'super-john', '$value'=>'John Doe'], ['maria-is-magic'=>'after']);
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

	// special kind of $value with a string key (eg.: ['$id'=>'key', '$value'=>'value'])
	if (isset($value['$id'], $value['$value'])){
		$key   = $value['$id'];
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
