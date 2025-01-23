<?php 
/**
 * @package Utils\Function
 */

/* =====================================================================================================================
Quick
===================================================================================================================== */
/**
 * Quick function for calling ob_start().
 * @return void
 */
function _start (){
	ob_start();
}

/**
 * Quick function for calling ob_get_clean(). Then it does some transforming and then return the content.
 * @return string
 */
function _end (){
	$content = ob_get_clean();
	$content = function_exists('mb_convert_encoding') ? mb_convert_encoding($content, 'UTF-8', 'UTF-8') : $content;
	$content = trim($content);
	// $content = ffto_string_tab($content, $tabs);	
    return $content;
}

/**
 * Shortcut to {@see ffto_set_meta()} and {@see ffto_to_var()}
 * @return mixed
 */
function _var (){
	$params = func_get_args();
	return call_user_func_array('ffto_get_var', $params);
}

/* =====================================================================================================================
Functions
===================================================================================================================== */
/**
 * Set variables to be accessible by  {@see ffto_include_content} and {@see ffto_include_file}
 *
 * @param mixed $key 
 * @param mixed $value 
 * @param mixed $reset 
 * @return void
 */
function ffto_set_var ($key, $value=null, $args=null){
	$args = _args($args, [
		'attrs' => true,
		'reset' => null,
	], 'reset');

	
	$pair = ffto_to_pair($key, $value);
	$vars = $args['reset'] ? [] : _global('$view_vars', []);

	if ($args['attrs']){
		$vars = ffto_to_attrs([$vars, $pair]);
	}else{
		$vars = array_merge($vars, $pair);
	}

	_global('$view_vars', $vars, true);

	return $vars;
}

/**
 * Get a specific variable or all of them (set in {@see ffto_include_content} and {@see ffto_include_file}).
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_var ($key=null, $fallback=null){	
	$vars = _global('$view_vars', []);
	if ($key){
		return array_key_exists($key, $vars) ? $vars[$key] : $fallback;
    }
	return $vars;
}

/**
 * Try getting content from a function, either the outputed html OR a return value/html.
 *
 * ```php
 * $v = ffto_include_content(function (){ ?>Bob<?php });
 * // "Bob"
 * 
 * $v = ffto_include_content(function (){ return [0,9,555]; });
 * // [0, 9, 555]
 * ```
 * 
 * @param mixed $callback 
 * @param mixed $vars 
 * @param mixed $return 	Force the returned value
 * @return mixed
 */
function ffto_include_content ($callback, $vars=null, $return=null){
	$has_vars = $vars !== null;
	$old_vars = $has_vars ? ffto_get_var() : null;

	// Make sure the vars are set
	$has_vars && ffto_set_var($vars, null, ['attrs'=>false]);
		
	// Make sure the vars is an array
	if (!ffto_is_list($vars)){
		$vars = array_filter([$vars]);
	}
	
	_start();
	$response = call_user_func_array($callback, $vars);
	$html     = _end();

	// Make sure the old vars are set back again
	$has_vars && ffto_set_var($old_vars, null, true);

	if ($return === 'html'){
		return $html;
	}else if ($return === 'response'){
		return $response;
	}else{
		return isset($response) ? $response : $html;
	}
}

/**
 * Call a function only once
 *
 * @param string $key 
 * @param function $callback 
 * @return void
 */
function ffto_once ($key, $callback=null){
	if ($callback === null){
		$callback = $key;
		$key      = is_string($key) ? $key : 'callback_' . spl_object_id($key);
	}

	$key = '$once/'.$key;
	if (_global($key)) return false;
	_global($key, true, true);
	_call($callback);
	return true;
}
