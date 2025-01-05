<?php 
function arr_merge ($a, $b, $args=null){
	$args = _args($args, [
		'deep'      => true,
		'separator' => ' ',
	]);

	$a = is_array($a) ? $a : to_array($a, $args['separator'], true);
	$b = is_array($b) ? $b : to_array($b, $args['separator'], true);

	if ($args['deep']){
		$_merge = function ($a, $b, $_merge){
			$is_arr       = is_array($a) && is_array($b);
			$is_both_list = $is_arr && (arr_is_list($a) && arr_is_list($b));
			$is_both_obj  = $is_arr && (arr_is_obj($a) && arr_is_obj($b));

			// keep the same array
			if ($is_arr && empty($b)){
				return $a;
			}

			// merge objects
			if ($is_both_obj){
				foreach ($b as $i => $bb){
					$aa    = isset($a[$i]) ? $a[$i] : null;
					$a[$i] = $_merge($aa, $bb, $_merge);
				}
				return $a;
			// append similar array
			}else if ($is_both_list){
				return array_merge($a, $b);
			// replace value
			}else{
				return $b;
			}		
		};
		$a = $_merge($a, $b, $_merge);
	}else{
		$a = array_merge($a, $b);
	}

	return $a;
}

function arr_add ($a, $b, $args=null){
	_deprecated('"arr_add", replace with "arr_merge"');
	return arr_merge($a, $b, $args);
}

// function arr_index ($arr, $value){
// 	$arr = array_values($arr);
// 	return ;
// }

// insert a value in a object array (with string keys) before OR after a item (by using their keys)
function arr_insert ($arr, $value, $positions){
	$key = null;

	// special kind of $value with a string key (eg.: ['$id'=>'key', '$value'=>'value'])
	if (is_array($value) && isset($value['$id']) && isset($value['$value'])){
		$key   = $value['$id'];
		$value = $value['$value'];
	}else{
		$key = is_string($value) ? to_slug($key) : count($arr);
	}
	
	$positions = to_array($positions, function ($v, $i){
        if (is_numeric($i)){
            $i = $v;
            $v = 'after';
        }
        return [
            '$id'    => $i,
            '$value' => $v,
        ];
    });

	$add = [$key => $value];

	if (count($positions)){
		$keys  = array_keys($arr);
		$index = null;
		
		foreach ($positions as $k => $position){
			$i = array_search($k, $keys);
			if ($i === false) continue;
			$index = $i + ($position === 'before' ? 0 : 1);
			break;
		}
		
		if ($index !== null){
			$start  = array_slice($arr, 0, $index, true);
			$end    = array_slice($arr, $index, null, true);
			$arr 	= $start + $add + $end;
		}else{
			$arr = $arr + $add;
		}
	}else{
		$arr = $arr + $add;
	}

	return $arr;
}

/**
 * Array
 * 
 * @package Utils\Array
 * @author 	Maxime Lefrancois
 * @version 6.0
 **/

 /**
  * Goes through an array returns the results
  *
  * @param mixed $arr
  * @param boolean $callback
  * @param array $args
  * @return array|string
  */
function array_each ($arr, $callback=true, $args=array(), $args2=null){
	// switch $arr and $callback
	if (is_array($callback) && !is_callable($callback)){
		$args     = $callback;
		$callback = true;
	}

	if ($args === ':words'){
		$args = array('separator'=>' ', 'join'=>' ');
		$arr  = is_string($arr) ? strip_tags($arr) : $arr;
	}else if (is_bool($args)){
		$args = array('single'=>$args);
	}elseif (is_callback($args)){
		$args = _args($args2, [
			'filter' => $args,
		]);
	}

	$args = _args($args, array(
		'single'    => false,
		'separator' => ',',
		'key'		=> false,	// TODO maybe no need for this, since 'return' can do it and make more sense
		'filter'	=> true,
		'parse'		=> true,
		'array'		=> false,
		'reduce'	=> false, 	// if an array is returned, it will be merge with the results
		// html 
		'trim'		=> true,
		'alias'		=> null,
		'wrap'		=> null,
		'join'      => false,
		// return
		'return'	=> false,
	), 'join');

	if ((is_int($arr) || is_float($arr))){
		$arr = $arr == 0 ? array() : range(1, floor($arr));
	}else{
		$arr = to_array($arr, array(
			'separator' => $args['separator'],
			'trim'      => $args['trim'],
			// 'filter'    => $args['filter'],
			'parse'     => $args['parse'],
			'return'	=> 'object',
		));
	}

	$index = 0;
	$items = array();
	$count = count($arr);
	$keys  = array_keys($arr);
	foreach ($arr as $key => $v){
		$i = $index++;  
		$v = $args['key'] ? _get($v, $args['key']) : $v;
		
		if (is_callable($callback)){
			$isFirst = $i === 0;
			$isLast  = $i === ($count-1);
			
			$data = [
				'index'     => $i,
				'length'    => $count,
				'isFirst'   => $isFirst,
				'isLast'    => $isLast,
				'isBetween' => !$isFirst && !$isLast,
				'previous'  => isset($keys[$i - 1]) ? $arr[$keys[$i-1]] : null,
				'next'      => isset($keys[$i + 1]) ? $arr[$keys[$i+1]] : null,
			];

			// TODO add a 3rd params with isFirst/isEnd/lenght/previous/next/... data
			$response = call_user_func_array($callback, [$v, $key, $data]);
		}else if ($callback === true){
			$response = $v;	
		}elseif (is_regexp($callback)){
			$response = to_match($v, $callback);
		}elseif (is_string($callback)){
			$data = is_array($v) || is_object($v) ? (is_object($v) ? clone $v : $v) : array('$value'=>$v);
			_set($data, '$id', $key);
			_set($data, '$index', $i);
			$response = string_replace($callback, $data);
		}

		// TODO add $key also as a possible $id
		if (is_array($response) && array_key_exists('$id', $response)){
			$key = $response['$id'];
			unset($response['$id']);
		}
				
		if (is_array($response) && array_key_exists('$value', $response)){
			$response = $response['$value'];
		}
		
		if ($args['return']){
			$response = _get($response, $args['return']);
		}

		if (isset($response) || !$args['filter']){
			if ($args['reduce']){
				$response = arr_is_list($response) ? $response : [$response];
				$items    = array_merge($items, $response);
			}else{
				$v = isset($response) ? $response : $v;
		
				// if the key is an array, then it's a deeper path
				if (is_array($key)){
					$items = _set($items, $key, $v);
				}else{
					$items[$key] = $v;
				}
			}
		}
	}

	if ($args['filter']){
		$items = array_filter($items, function ($v){ return $v !== null; });
	}

	if ($args['array']){
		$items = array_values($items);
	}

	// wrapping the results means all items needs to be joinded
	if ($args['wrap'] && !$args['join']){
		$args['join'] = NL;
	}


	if (is_string($args['join'])){
		$items = implode($args['join'], $items);
	}else if ($args['single']){
		$items = reset($items);
	}

	if (is_string($items)){
		if ($args['wrap']){
			$items = string_replace($args['wrap'], [
				'html'  => $items,
				'text'  => $items,
				'value' => $items,
			]);
		}
		if ($args['alias']){
			$items = replace_alias($items, $args['alias']);
		}
	}

	return $items;
}

function array_set_keys ($arr, $key=null){
	$arr  = is_array($arr) ? $arr : array();
	$items = array();

	foreach ($arr as $i => $v){
		if (is_string($key) && strpos($key, '{') !== false){
			$i = string_replace($key, $v);
		}else{
			$i = get_value($v, $key, $i);
		}
		$items[$i] = $v;
	}

	return $items;
}

// replace some keys with other ones, good to use when we have "alternative" keys
function array_replace_keys (&$arr, $replace){
	foreach ($replace as $from => $to){
		if (array_key_exists($from, $arr)){
			$arr[$to] = $arr[$from];
			unset($arr[$from]);
		}
	}
	return $arr;
}

function array_copy ($arr, $args=''){
	$args = to_args($args, [
		'exclude' => '',
	]);

	$exclude = to_array($args['exclude']);
	$items   = [];
	
	foreach ($arr as $i => $v){
		if (in_array($i, $exclude)) continue;
		$items[$i] = $v;
	}

	return $items;
}

// find an item in an array of items
function array_find ($arr, $search, $args=''){
	if (is_bool($args)){
		$args = ['single'=>$args];
	}

	$args = _args($args, array(
		'single' => false,
		'return' => false,
	));

	$matches = array();
	if (!empty($arr)){
		$count = is_array($search) ? count($search) : 0;
		$index = 0;
		
		foreach ($arr as $i => $item){
			// This is used when 
			if (!is_match([
				'$id'    => $i,
				'$value' => $item
			], $search)) continue;

			// if (!is_match($item, $search)) continue;

			// add the index
			if ($args['return']){
				set_value($item, '$index', $index);
				set_value($item, '$key', $i);
			}

			$matches[$i] = $item;
			$index++;

			if ($args['single']){
				break;
			}
		}

		if (count($matches)){
			if ($args['return'] === 'index'){
				$matches = array_keys($matches);
			}
		}
	}

	return $args['single'] ? reset($matches) : $matches;
}

function array_remove (&$arr, $value){
	if (!is_array($arr)) return false;
	
	$key     = array_search($value, $arr);
	$removed = false;

	if ($key !== false){
		$removed = true;

		// remove all instances of the $value
		while ($key !== false){
			array_splice($arr, $key, 1);
			$key = array_search($value, $arr);
		}
	}

	return $removed;
}

function array_random ($arr, $min=null, $max=null){
	if (!is_array($arr)){
		return false;
	}

	$is_array = true;
	if ($min === true){
		$is_array = false;
		$min      = 1;
	}else if ($min === null){
		$min      = count($arr);
	}

	if (is_null($max)) 			$max = $min;
	if ($max > count($arr)) 	$max = count($arr);
	if ($min > $max) 			$min = $max;

	srand();
	$count = rand($min, $max);

	if (!$count){
		return $is_array ? array() : NULL;
	}

	srand();
	$keys = (array)array_rand($arr, $count);
	shuffle($keys);

	$items 	= array();

	// keep the original keys
	foreach ($keys as $key){
		$items[$key] = $arr[$key];
	}

	if (count($items) == 1 && !$is_array){
		return current($items);
	}else if ($is_array){
		return array_values($items);
	}else{
		return $items;
	}
}

function array_pluck ($arr, $key, $fallback=NULL){
	if (empty($arr)){
		return $fallback;
	}else if ($key === true){
		return $arr;
	}

	$output       = array();
	$callback 	  = is_callback($key) ? $key : null;
	$keys         = !$callback ? to_array($key) : array();
	$is_single    = arr_is_obj($arr);
	$is_many_keys = $callback || is_array($key) || count($keys) > 1;

	if ($is_single){
		$arr = array($arr);
	}

	foreach ($arr as $item){
		$value = $is_many_keys ? array() : null;

		if ($callback){
			foreach ($item as $i => $v){
				if ($i = apply($callback, [$i, $v])){
					$value[$i] = $v;
				}
			}
		}else{
			foreach ($keys as $i=>$key){
				if (!array_key_exists($key, $item)) continue;

				$v = _get($item, $key);
				$k = is_numeric($i) ? $key : $i;

				if ($is_many_keys){
					$value[$k] = $v;
				}else{
					$value = $v;
				}
			}
		}		

		if (empty($value)){
			continue;
		}

		$output[] = $value;
	}

	$output = empty($output) ? $fallback : $output;

	return $is_single ? reset($output) : $output;
}

/**
 * Similar to array_merge, but keep it as an assosiative array (if there's numeric keys, it's not appended) 
 * Also has the option of mergin array/object
 */
function array_join ($arrs, $args='', $filter=null){
	if ($args === false){
		$args = array(
			'object' => false,
			'array'  => false,
		);
	}

	$args = to_args($args, array(
		'object' => true,    // merge all object OR specific keys
		'array'  => true,    // merge all arrays OR specific keys
		'unique' => true, 		
		'null'   => false,   // let null value overwrite,
		'filter' => $filter,
	), 'filter');

	$merge_object = is_truthy($args['object']) ? true : (is_string($args['object']) ? to_array($args['object']) : array());
	$merge_array  = is_truthy($args['array']) ? true : (is_string($args['array']) ? to_array($args['array']) : array());
	$base         = array_shift($arrs);
	$base 		  = is_array($base) ? $base : array();

	foreach ($arrs as $arr){
		if (!is_array($arr)) continue;
		
		foreach ($arr as $i => $v){
			$o = isset($base[$i]) ? $base[$i] : null;
			
			if ($args['filter'] === 'html'){
				if ($i === 'classnames' || $i === 'classname' || $i === 'class'){
					$i = 'class';
					$o = is_array($v) ? $o : array();
					$v = to_classnames([$o, $v], true);
					$o = null;
				}else if ($i === 'style'){
					$v = to_style($v, true);
					$o = null;
				}else if ($i === 'data'){
					$o = is_array($o) ? $o : array();
					$v = is_array($v) ? $v : array();
					$v = array_merge($o, $v);
					// p($i, $v, $o);
					// $v = to_attributes($v, 'return=object');
					// TODO
				}else if ($i === 'attrs'){
					$v = to_attributes($v, 'return=object');
					$o = null;
				}					
			}else if (is_callback($args['filter'])){
				$v = _apply($args['filter'], $v, $i);
			}

			$is_object = arr_is_obj($o) || arr_is_obj($v); // since empty objects are considered array, we need to do an "OR" here
			$is_array  = is_array($o) && is_array($v);

			if ($is_object && ($merge_object === true || in_array($i, $merge_object))){
				$base[$i] = array_join([$o, $v]);
			}else if ($is_array && ($merge_array === true || in_array($i, $merge_array))){
				$base[$i] = array_merge($o, $v);
				$base[$i] = $args['unique'] ? array_unique($base[$i]) : $base[$i];
			}else if ($args['null'] || !is_null($v)){
				// _js($base, $i);
				$base[$i] = $v;
			}
		}
	}

	return $base; 
}


/**
 * Similar to array_combine, but does not break when the count of $keys and $values aren't the same
 */
function array_fuse ($keys, $values, $args=''){
	$args = to_args($args, [
		'parse' => false,   // automatically format values 
		'json'  => false,   // if auto format 
	]);

	$keys 	= to_array($keys);
	$values	= to_array($values);
	$item 	= array();

	foreach ($keys as $i => $key){
		$v = array_key_exists($i, $values) ? $values[$i] : null;
		$v = $args['parse'] ? to_value($v, array('callback'=>$args['parse'], 'json'=>$args['json'])) : $v;
		$item[$key] = $v;
	}

	return $item;
}

function array_loop ($arr, $index, $fallback=null){
	if (!is_array($arr) || empty($arr)){
		return $fallback;
	}

	$loop_index = is_numeric($index) ? $index % count($arr) : null;

	if (isset($arr[$index])){
		return $arr[$index];
	}else if (!is_null($loop_index) && isset($arr[$loop_index])){
		return $arr[$loop_index];
	}else if (isset($arr[0])){
		return $arr[0];
	}else{
		return $fallback;
	}
}

function array_flatten ($arr) {
	$items = array();
	array_walk_recursive($arr, function($v) use (&$items) { $items[] = $v; });
	return $items;
}

// need to review and document
function array_sort ($arr, $order_by=array(), $args=''){
	$args = to_args($args, array(
		'null'      => 'AFTER',
		'case'      => true,
		'order'     => 'ASC',
		'values'    => array(),
		'callbacks' => array(),	//
	));

	// make a copy of the array
	$arr = $arr ? array_slice($arr, 0, null, true) : array();
	if (empty($arr)){
		return array();
	}

	$RE_SORT_ITEMS  = '/(?:(\$?[a-z*][a-z_0-9\-\.]*)((?:\s?(?:(?:\[[^\]]+\])|(?:[^,]+)))*))(?:,|$)/i';
	$RE_SORT_FIELDS = '/\[([^\]]+)\]/';
	
	if (is_string($order_by)){
		$order_by = to_match($order_by, $RE_SORT_ITEMS, 'multiple=1&groups=0');
	}else if (!arr_is_list($order_by)){
		$order_by = [$order_by];
	}
	
	$all = array();
	foreach ($order_by as $i => $v){
		if (is_callback($v)){
			$v = array(
				'key'   => $v,
				'null'	=> $args['null'],
				'case'	=> false,
				'fields'=> array(),
				'order' => $args['order'],
			);
			// TODO add way to cache function returned value, so it's not asked all the time
		}else if (is_array($v)){
			$v['key']    = isset($v['key']) ? $v['key'] : $i;
			$v['null']   = strtoupper(isset($v['null']) ? $v['null'] : $args['null']);
			$v['case']   = isset($v['case']) ? $v['case'] : $args['case'];
			$v['fields'] = to_array(isset($v['fields']) ? $v['fields'] : array());
			$v['order']  = strtoupper(isset($v['order']) ? $v['order'] : $args['order']);
		}else if (is_string(($v))){
			$v = to_match($v, $RE_SORT_ITEMS, 'multiple=0&groups=1');
			$k = $v[0];
			$f = to_match($v[1], $RE_SORT_FIELDS);
			$v = strtoupper(preg_replace($RE_SORT_FIELDS, '', $v[1]));

			$v = array(
				'key'   => $k,
				'null'	=> strpos($v, 'NULL_BEFORE') !== false ? 'BEFORE' : (strpos($v, 'NULL_AFTER') !== false ? 'AFTER' : $args['null']),
				'case'	=> strpos($v, 'CASE_INSENSITIVE') !== false ? false : (strpos($v, 'CASE_SENSITIVE') !== false ? true : $args['case']),
				'fields'=> $f,
				'order' => strpos($v, 'DESC') !== false ? 'DESC' : (strpos($v, 'ASC') !== false ? 'ASC' : $args['order']),
			);
		}else{
			continue;
		}

		$all[] = $v;
	}
	$order_by = $all;


	uasort($arr, function ($a, $b) use ($order_by, $args, $arr){
		$compare = null;
		
		foreach ($order_by as $sort){
			$key    = $sort['key'];
			$valueA = $a;
			$valueB = $b;
			
			if ($key === '$id' || $key === ':id'){
				$valueA = array_search($a, $arr);
				$valueB = array_search($b, $arr);
			}else if ($key === '*'){
				$valueA = $a;
				$valueB = $b;
			}else{
				$valueA = get_value($a, $key);
				$valueB = get_value($b, $key);
			}

			if (is_string($key) && isset($args['values'][$key])){
				$values = $args['values'][$key];
				$valueA = array_search($valueA, $values);
				$valueB = array_search($valueB, $values);

				// add to end the values not found
				if ($valueA === false){
					$valueA = 1;
					$valueB = 0;
				}
			}

			if (isset($sort['fields']) && count($sort['fields'])){
				$valueA = array_search($valueA, $sort['fields']);
				$valueB = array_search($valueB, $sort['fields']);
			}

			// TODO maybe remove the accents also when NOT case insensitive
			if (is_string($valueA)){
				$valueA = strip_tags($valueA);
				$valueA = string_clean_accents($valueA);
				$valueA = $sort['case'] ? $valueA : strtolower($valueA);
			}
			if (is_string($valueB)){
				$valueB = strip_tags($valueB);
				$valueB = string_clean_accents($valueB);
				$valueB = $sort['case'] ? $valueB : strtolower($valueB);
			}

			// same exact value OR already found an order
			if ($valueA === $valueB || $compare !== null){
				continue;
			}
			// one of the attribute is missing
			if (is_null($valueA)){
				$compare = $sort['null'] === 'AFTER' ? 1 : -1;
			}else if (is_null($valueB)){
				$compare = $sort['null'] === 'AFTER' ? -1 : 1;
			}else{
				if (is_numeric($valueA)){
					$valueA = (float)$valueA;
					$valueB = (float)$valueB;
				}
				if (is_bool($valueA)){
					$valueA = $valueA ? 1 : 0;
					$valueB = $valueB ? 1 : 0;
				}
				if ($valueA > $valueB){
					$compare = 1;
				}else if ($valueA < $valueB){
					$compare = -1;
				}

				if ($compare && $sort['order'] === 'DESC'){
					$compare *= -1;
				}			
			}
		}

		return $compare;
	});

	$is_array = !arr_is_obj($arr);
	if ($is_array){
		$arr = array_values($arr);
	}

	return $arr;
}

/*
// THIS CODE BREAKS a site
function __new__array_traverse ($arr, $args=null, $callbacks=null){
	if (is_callback($args)){
		$args = ['format'=>$args];
	}

	$args = _args($args, array(
		'key'    => 'children',	// [false = everything, true = only arrays, string/array of keys]
		'format' => $callbacks,
	), 'key');

	$keys = $args['key'] === true ? true : ($args['key'] ? to_array($args['key']) : false);
	$pre  = is_array($args['format']) ? $args['format'][0] : $args['format'];
	$post = is_array($args['format']) ? $args['format'][1] : null;

	$format = function ($v, $i, $parent, $depth, $w) use ($keys, $pre, $post){
		$a = array(
			'parent' => $parent,
			'depth'  => $depth,
		);

		$v = apply($pre, [$v, $i, $a]);

		// go through each properties
		if (is_array($v)){
			// $v = $w($v, $i, $depth+1, $w);
		}
		
		$v = apply($post, [$v, $i, $a]);

		return $v;
	};

	$walk = function ($value, $index=null, $depth=0, $w) use ($keys, $format){
		// deal with children stuff
		if (arr_is_list($value)){
			$items = [];
			foreach ($value as $i => $v){
				/*
				if (
					$keys === false                                   // everything
					||($keys === true && arr_is_list($v))     // only arrays
					||(is_array($keys) && in_array($i, $keys, true))  // specific keys
				){
					$v = $format($v, $i, null, $depth, $w);
					if (!isset($v)) continue;
				}
				$items[$i] = $v;
				* /

				if (
					($keys === true)
					|| ($keys === false && !is_array($v))
					// || (is_array($keys) && !in_array($i, $keys, true)) 
				){
					$v = $format($v, $i, null, $depth, $w);
					if (!isset($v)) continue;
				}

				$items[$i] = $v;
			}
			return $items;


			// deal with the children items
			// $items = [];
			// foreach ($value as $i => $v){
			// 	$item = $format($v, $i, $value, $depth, $w);
			// 	if (isset($item)) $items[$i] = $item;
			// }
			// $value = $items;
		}else{
			$value = $format($value, $index, null, $depth, $w);
		}

		return $value;
	};

	return $walk($arr, null, 0, $walk);
}
*/


function array_traverse ($arr, $args='', $callback=null, $post_callback=null){
	if (is_callback($args)){
		$args = array(
			'callback'      => $args,
			'post_callback' => $callback,
		);
	}
	
	$args = _args($args, array(
		'key'           => 'children',
		'order'			=> null,
		'callback'      => $callback,
		'post_callback' => $post_callback,
	), 'key');

	$keys 	  = to_array($args['key']);
	$filtered = array();
	foreach ($arr as $i=>$item){
		$response = __array_traverse__call($item, $i, null, 0, $args);
		if (isset($response)){
			$filtered[$i] = $response;
		}
	}

	return $filtered;
}

function __array_traverse__call ($item, $index, $parent, $depth=0, $args=array()){
	$a = array(
		'index' => $index,
		'parent'=> $parent,
		'depth' => $depth, 
	);

	if (is_callback($args['callback'])){
		$response = $args['callback']($item, $a);
		$item 	  = isset($response) ? $response : $item;
	}

	// find the keys (if false, then all arrays)
	// $keys = [];
	// if ($args['key']){
	// 	$keys[] = $args['key'];
	// }else if (is_array($item)){
	// 	foreach ($item as $i => $v){
	// 		$keys[] = $i;
	// 	}
	// }
	// __err($item);


	$children = get_value($item, $args['key'], array());

	if (!empty($children) && $args['order']){
		// $children = array_sort($children, $args['order']);
	}

	$filtered = array();

	if (is_array($children) && !empty($children)){
		foreach ($children as $i=>$child){
			$response = __array_traverse__call($child, $i, $item, $depth + 1, $args);

			if (isset($response)){
				$filtered[$i] = $response;
			}
		}
	}

	set_value($item, $args['key'], $filtered);

	if (is_callback($args['post_callback'])){
		$response = $args['post_callback']($item, $a);

		if (isset($response)){
			$item = $response;
		}
	}

	return $item;
}

function array_process ($arr, $args='', $callback=null){
	if (empty($arr)){
		return array();
	}

	$args = _args($args, array(
		'keys'     => '',			// when passing a string as an item, only the values are passed with a separator, and the keys are the columns
		'separator' => ';',			// separator for items that are strings
		'props'    => array(),		// default properties (with their data), that aren't meta values
		'defaults' => '',			// default values 
		'format'   => null,			// formating the items
		'parse'	   => true,			// parse values
		'single'   => false,		// return only 1 item 
	));

	if (arr_is_obj($arr)){
		$arr    		= array($arr);
		$args['single'] = true;
	}

	$props 	  = is_array($args['props']) ? $args['props'] : array();
	$keys     = to_array($args['keys'], ['separator'=>$args['separator']]);
	$defaults = to_args($args['defaults']);
	$defaults = array_merge($props, $defaults);

	foreach ($arr as $i => $item){		
		if (is_string($item)){
			$item = to_array($item, ['separator'=>$args['separator']]);
			$item = array_each($keys, function ($k, $i) use ($item){ return [
				'$id'	 => $k,
				'$value' => isset($item[$i]) ? $item[$i] : false,
			]; });
		}
		
		$item = array_merge($defaults, $item);
		$item = $args['parse'] ? to_values($item, ['json'=>false]) : $item;
		$item = _apply($args['format'], $item);
		$meta = [];
		
        foreach ($item as $key => $value){
            if (array_key_exists($key, $props)) continue;
            $meta[$key] = $value;
        }

		$item 		= _apply($callback, $item, $meta);
		$arr[$i] 	= $item;
	}

	return $args['single'] ? reset($arr) : $arr;
}

function array_glue ($arr, $args=null){ 
	$args = _args($args, [
		'separator' => ', ',
		'wrap'		=> 'span',
	], 'separator');
	
	$str = array();
	foreach ($arr as $i => $v){
		$is_last = $i == (count($arr)-1);

		if (!$is_last){
			$v = $v . $args['separator'];
		}
		
		if ($args['wrap']) $v = to_html($args['wrap'], [
			'html' => $v,
			'class' => [
				$i === 0 ? 'is-first' : '',
				$i === $is_last ? 'is-last' : '',
			]
		]);

		$str[] = $v;
	}
	return implode(' ', $str);

}

function array_query ($source, $args=null, $format_row=null){
	$args = _args($args, [
		'keys'   	=> true,        // the keys (headers) of the items
		'key'		=> null,		// change the "key" of the item on the items (instead of )
		'index'     => null,        // add an index key to the items (the row # by default if true)
		'format'    => false,   	// [true = to_value(), callback], if format returns NULL, skip it
		'filter'    => false,   	// will check if it's a valid row
		// pagination
		'page'		=> false,
		'limit'		=> false,
		// output // TODO
		// 'random'	=> false,		// 1 or many random items
		// 'sort'		=> null,		// sort after
		// 'pivot'     => false,       // pivot make the header the rows
		// 'tree'      => false,
		'return'	=> null,		// [null, object]
	]);

	// pagination 
	$filter = !empty($args['filter']) ? $args['filter'] : null;
	$limit  = is_int($args['limit']) && $args['limit'] ? $args['limit'] : false;
	$page 	= is_int($args['page']) && is_int($limit) ? $args['page'] : 1;
	$page   = is_int($page) && $page < 1 ? 1 : $page;		// make sure the page can't be under 1
	$from 	= is_int($page) ? ($page-1) * $limit : false;
	$to 	= is_int($page) ? $from + $limit : false;

	// data
	$index  = 0;		// always augment
	$count  = 0;		// only augment when the row is a valid value
	$keys   = null;		// name of all the keys in an item
	$items  = [];

	if (is_array($source)){
		reset($source);
	}

	$next = function () use (&$source){
		if (is_callable($source)){
			return $source();
		}else if (is_array($source)){
			$item = current($source);
			next($source);
			return $item;
		}
		return false;
	};

	while ($row = $next()){
		if ($row === CONTINUE_LOOP){
			continue;
		}else if ($row === BREAK_LOOP){
			continue;
		}

		$i 			 = $index++; 	// keep augmenting the ID, even when $format callback return null, that way we know the right row if we want to come back
		$key  		 = $count;
		$is_skipped  = $limit && ($count < $from || $count >= $to); // if there's a pagination, we can skip some parsing
		$item		 = null;
		
		if ($filter || !$is_skipped){
			$row   = $format_row ? $format_row($row) : $row;
			$item  = $row;
			
			// automaticaly add an index property/key
			if ($args['index']){
				$k        = is_string($args['index']) ? $args['index'] : 'index';
				$item[$k] = $i;
			}

			// validate the 
			$valid = $filter ? is_match($item, $filter) : true;

			// the keys have already been defined, so we can skip the rest. The filter needs to be done before "format", since it makes it faster
			if (!$valid && $keys){
				continue;
			}

			// specific keys to fetch
			if ($args['keys']){
				$item = array_pluck($item, $args['keys']);
			}

			// reformat the item
			if ($args['format']){
				$item = to_values($item);
				$item = apply($args['format'], [$item, $i], ['fallback'=>$item]);
			}

			// fetch the keys
			if ($item && !$keys){
				$keys = array_keys($item);
			}

			// we need the keys before stopping this parsing
			if ($valid === BREAK_LOOP){
				break;
			}else if (!$valid){
				continue;
			}

			// skip the rest of the row is empty
			if ($item === false) continue;


			// update the key used
			if ($k = $args['key']){
				if (isset($item[$k]))		$key = $item[$k];
				else if (isset($row[$k]))	$key = $row[$k];	// if the $row has changed, check the row instead
			}
		}

		// add the item to the list
		if ($item && !$is_skipped){
			$items[$key] = $item;
		}

		$count++;
	}

	if (return_object($args['return'])){
		return [
			'page'  => $page,
			'pages' => $args['limit'] ? ceil($count / $args['limit']) : 0,
			'count' => $count,
			'keys'  => $keys,
			'items' => $items,
		];
	}

	return $items;
}

// Validate --------------------------------------------------------------------
function arr_is_obj ($arr){
	if (!is_array($arr) || empty($arr)) return false;
	foreach ($arr as $i => $v){
		if (is_string($i)) return true;
	}
	return false;
}

function arr_is_list ($arr){
	return is_array($arr) && !arr_is_obj($arr);
}

function array_is_object ($arr){
	_deprecated('"array_is_object" replace with "arr_is_obj"');
	return arr_is_obj($arr);
}

// Casting ---------------------------------------------------------------------
function array_to_unique ($arr, $args){
	$args = to_args($args, array(
		'keys'	=> '',
	), 'keys');

	$keys      = to_array($args['keys']);
	$list      = array();
	$item_keys = array();
	foreach ($arr as $i => $item){
		$item_key = array();
		foreach ($keys as $k){
			$item_key[] = get_value($item, $k);
		}
		$item_key = implode('_', $item_key);

		if (!in_array($item_key, $item_keys)){
			$list[$i] 	 = $item;
			$item_keys[] = $item_key;
		}
	}

	return $list;
}

function array_to_columns ($arr, $count=2, $callback=null){
	if (!$count) return [];

	$values = array();
	$total  = 0;

	// use a callback function to decode how we can decide in which column the item would be. Generally by calculating the about of characters there is
	if (is_callback($callback)){
		$total = 0;
		foreach ($arr as $v){
			$values[]  = $total;
			$value     = apply($callback, array($v));
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
}

function array_to_group ($arr, $key, $args=null){
	if (is_callback($args)){
		$args = array('create'=>$args);
	}else if ($args === true){
		$args = array('return'=>'pack');
	}

	$args = _args($args, array(
		'create'        => null,
		'format'        => null,
		'group_key'     => 'id',
		'child_key'     => 'children',
		'index_key'		=> 'index',
		'null_key'      => -1,
		'skip_ids'      => array(),
		'sort'          => false,
		'sort_children' => false,
		'return'        => '',
	));

	if (empty($arr)){
		return array();
	}

	$groups       = array();
	$pack_groups  = array(); // when the group_id change, then we change packs
	$last_pack_id = null;
	$current_pack = null;

	$skip_ids	  = to_array($args['skip_ids']);
	$group_key    = $args['group_key'];
	$child_key    = $args['child_key'];

	foreach ($arr as $i=>$item){

		// add the index to the item (if it's an object/array)
		if ($args['index_key']){
			$item = _set($item, $args['index_key'], $i);
		}

		$group     = null;
		$group_ids = _get($item, $key);

		if (!arr_is_list($group_ids)){
			$group_ids = array($group_ids);
		}
		
		foreach ($group_ids as $ii => $group_id){
			$group = null;
			
			// the group_id returns an object with the ID inside, use this for the group
			if (arr_is_obj($group_id)){
				$group    = $group_id;
				$group_id = _get($group_id, 'id,$id');
				unset($group['$id']);
			}

			$group_id = $group_id === null ? $args['null_key'] : $group_id;
			$group    = isset($groups[$group_id]) ? $groups[$group_id] : $group;
			
			if ($group_id === false){
				continue;
			}

			if (in_array($group_id, $skip_ids)){
				continue;
			}

			// create group --------------------------------------------------------
			if (!$group){
				$group = is_callback($args['create']) ? $args['create']($item, $group_id, $ii) : array();
			}

			// re-format the item to be added as a children
			$item = _apply($args['format'], $item, $group);

			// make sure the "group_key" and "child_key" keys are set
			if (!isset($group[$group_key])){
				$group[$group_key] = $group_id;
			}

			if (!isset($group[$child_key])){
				$group[$child_key] = array();
			}

			// if it's a clone of the main item
			$item = $ii ? clone $item : $item;
			_set($item, '_index', $i);
			_set($item, '_clone', $ii > 0);

			$group[$child_key][]  = $item;
			$groups[$group_id] 	  = $group;

			// pack groups ---------------------------------------------------------
			// (group only when the previous item is the same)

			// current pack is finished
			if ($group_id !== $last_pack_id && $current_pack){
				$pack_groups[] = $current_pack;
				$current_pack  = null;
			}

			if (!$current_pack){
				$current_pack = is_callback($args['create']) ? $args['create']($item, $group_id, $ii) : null;

				if ($current_pack === null){
					$current_pack             = $group;
					$current_pack['children'] = [];
				}

				// make sure the "group_key" and "child_key" keys are set
				if (!isset($current_pack[$group_key])){
					$current_pack[$group_key] = $group_id;
				}
				if (!isset($current_pack[$child_key])){
					$current_pack[$child_key] = array();
				}
			}

			$last_pack_id 				= $group_id;
			$current_pack[$child_key][] = $item;
		}
	}

	if ($current_pack){
		$pack_groups[] = $current_pack;
	}

	if ($args['return'] === 'array'){
		$groups = array_values($groups);
	}else if ($args['return'] === 'pack'){
		$groups = $pack_groups;
	}

	if ($args['sort']){
		$groups = array_sort($groups, $args['sort']);
	}
	if ($args['sort_children']){
		foreach ($groups as &$group){
			$group['children'] = array_sort($group['children'], $args['sort_children']);
		}
	}

	return $groups;
}

function array_to_tree ($arr, $args='', $callback=null){
	if (!is_array($arr)){
		return array();
	}

	$args = to_args($args, array(
		'item_key'		=> 'id',
		'parent_key'	=> 'parent',
		'depth_key'		=> 'depth',
		'children'		=> 'children',	// attribute name
		'key_separator' => false,		// the item_id is the path of the parent/child
		'root'			=> null,		// start at a specific root id
		'filter'		=> null,		// filter out some items
		'format'		=> $callback,	// 1 or 2 function
		'flatten'		=> false,
	));

	$parents  = array();
	$children = array();
	$flatten  = array();

	$format_before = $args['format'];
	$format_after  = null;

	if (is_array($format_before)){
		$format_after  = isset($format_before[1]) ? $format_before[1] : null;
		$format_before = isset($format_before[0]) ? $format_before[0] : null;
	}

	foreach ($arr as $i=>$item){
		if (is_callable($args['filter'])){
			$response = $args['filter']($item, $i);
			if (!$response){
				continue;
			}
		}

		$item_id   = $args['item_key'] === '$index' ? trim($i) : get_value($item, $args['item_key']);
		$parent_id = null;

		if ($args['key_separator']){
			$full_path = explode($args['key_separator'], $item_id);
			array_pop($full_path);
			
			// make sure the parents exists
			$path = [];
			// $parent = $parents;
			foreach ($full_path as $i => $p){
				$path[] 	= $p;
				$parent_id 	= implode($args['key_separator'], $path);
				$parent_id  = trim($parent_id);

				if ($i){
					$children[$parent_id] = isset($children[$parent_id]) ? $children[$parent_id] : array();
				}else{
					$parents[$parent_id] = isset($parents[$parent_id]) ? $parents[$parent_id] : array();
				}
			}
		}else{
			$parent_id = get_value($item, $args['parent_key']);
		}

		if ($parent_id == $args['root']){
			$parents[$item_id] = $item;
		}else{
			if (!isset($children[$parent_id])){
				$children[$parent_id] = array();
			}
			$children[$parent_id][$item_id] = $item;
		}
	}

	$response = __array_to_tree__link($parents, $children, $flatten, $args, 0, $format_before, $format_after);

	return $args['flatten'] ? $flatten : $response;
}

function __array_to_tree__link ($parents, $children, &$flatten, $args=array(), $depth=0, $format_before=null, $format_after=null){
	$is_object = false;

	foreach ($parents as $i => $item){
		if (!is_numeric($i)){
			$is_object = true;
		}

		// the item_id is based of the item before being formated
		$item_id       = $args['item_key'] === '$index' ? trim($i) : get_value($item, $args['item_key']);
		$item_children = array_key_exists($item_id, $children) ? $children[$item_id] : null;
		$data 		   = ['key'=>$item_id, 'index'=>$i, 'depth'=>$depth, 'children'=>$item_children ? $item_children : array()];
		// $item          = apply($format_before, [$item, $data], ['null'=>true]); // why does it return null ....??
		$item          = apply($format_before, [$item, $data]);
		
		if (!$item){
			$parents[$i] = null;
			continue;
		}
		
		if ($args['flatten']){
			set_value($item, $args['depth_key'], $depth);
			array_push($flatten, $item);
		}

		if ($item_children){
			$item_children = __array_to_tree__link($item_children, $children, $flatten, $args, $depth + 1, $format_before, $format_after);
		}

		if (!$args['flatten']){
			set_value($item, $args['children'], $item_children ? $item_children : array());
		}

		$data['children'] = $item_children ? $item_children : array();
		$item = apply($format_after, [$item, $data]);

		$parents[$i] = $item;
	}

	$parents = array_filter($parents);
	
	// convert to clean array
	if (!$is_object){
		$parents = array_values($parents);
	}

	return $parents;
}

