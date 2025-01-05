<?php 
/**
 * Data
 * 
 * @package Utils\Data
 * @author 	Maxime Lefrancois
 * @version 6.0
 **/

function set_data ($key, $value, $args=''){
	if ($args === true){
		$args = array('memory'=>true);
	}

	$args = to_args($args, array(
		'type'   => 'file',                         // [file, session, cookie, memory]
		'dir'    => '@data',
		'memory' => _config('data_memory'),         // save to memory to speed up the next access
		'ext'	 => 'json',
		'expire' => false,							// 
	), 'type');
    
	$filepath   = "{$args['dir']}/{$key}" . ($args['ext'] ? ".{$args['ext']}" : '');
	$memory_key = "memory:{$key}";

	// remove data
	if ($args['expire'] === null || $value === null){
		$data = null;
	}else{
		$expire 	= to_date($args['expire'], 'UTC');
		$is_object  = is_object($value);
		$data       = array(
			'timestamp'	 => gmdate('Y-m-d H:i:s'),
			'serialized' => $is_object,
			'expire'     => $expire,
			'value'      => $is_object ? serialize($value) : $value,
		);
	}
	
	if ($args['type'] === 'file')			set_file($filepath, $data);
	elseif ($args['type'] === 'cookie') 	set_cookie($key, $data, null);
	elseif ($args['type'] === 'memory')		set_global($key, $data);
	elseif ($args['type'] === 'session')	set_session($key, $data);
    
    if ($args['type'] !== 'memory' && $args['memory']){
        set_global($memory_key, $value);
	}
}

function get_data ($key, $args='', $fallback=null){
	if ($args === true){
		$args = array('memory'=>true);
	}

	$args = to_args($args, array(
		'type'     => 'file',                   // [file, session, cookie, memory]
		'dir'      => '@data',
		'memory'   => _config('data_memory'),   // save to memory to speed up the next access
		'ext'      => 'json',
		'clean'	   => false,
		'fallback' => $fallback,
	));

	$data       = null;
	$value      = null;
	$memory_key = "memory:{$key}";

	// if (!$args['type']){
	// 	$args['type'] = 'file';
	// }

	if ($args['memory'] && ($value = get_global($memory_key))){
		return $value;
	}

	$filepath = '';
	if ($args['type'] === 'file'){
		$filepath = to_filepath([
			"{$args['dir']}/{$key}.{$args['ext']}",
			"{$args['dir']}/{$key}",
			$key,
		]);
	}

	if ($args['type'] === 'file')           $data = get_file($filepath);
	elseif ($args['type'] === 'cookie')     $data = get_cookie($key);
	elseif ($args['type'] === 'memory')     $data = get_global($key);
	elseif ($args['type'] === 'session')    $data = get_session($key);

	// check expire date	
	if ($data && isset($data['expire']) && date_is_passed($data['expire'])){
        $data = null;

		if ($args['clean']){
			if ($args['type'] === 'file')			remove_file($filepath);
			elseif ($args['type'] === 'cookie')		remove_cookie($key);
			elseif ($args['type'] === 'memory')		set_global($key, null);
			elseif ($args['type'] === 'session')	set_session($key, null);
		}
	}

	if ($data){
		$value = $data['value'];
        $value = $data['serialized'] ? unserialize($value) : $value;
        $value = $value === null ? $args['fallback'] : $value;

		if ($args['type'] !== 'memory' && $args['memory']){
			set_global($memory_key, $value);
		}
	}else{
		$value = $args['fallback'];
	}

	return $value;
}

function set_env_data ($key, $value, $args=null){
	$args        = to_args($args);
	$args['dir'] = '@env';
	return set_data($key, $value, $args);
}

function get_env_data ($key, $args=null, $fallback=null){
	$args        = to_args($args);
	$args['dir'] = '@env';
	return get_data($key, $args, $fallback);
}

function set_cache ($key, $value, $args=null){
	$args     		= to_args($args);
	$args['dir']    = '@cache';
	$args['memory'] = true;
	return set_data($key, $value, $args);
}

function get_cache ($key, $args=null, $fallback=null){
	$args     		= to_args($args);
	$args['dir']    = '@cache';
	$args['memory'] = true;
	return get_data($key, $args, $fallback);
}

// Nonce -----------------------------------------------------------------------
// @source http://stackoverflow.com/questions/4145531/how-to-create-and-use-nonces
function get_nonce ($key){
    $id    = get_session_id();
	$key   = $key.$id;
	$nonce = hash('sha512', string_random());
	set_session($key, $nonce);
	return $nonce;
}

function verify_nonce ($key, $cnonce, $clear=true){
	if (_config('nonce') === false) return true;
    
    $id    = get_session_id();
    $key   = $key.$id;
    $nonce = get_session($key);

	if ($clear){
		set_session($key, null);
	}

	return $cnonce && $nonce === $cnonce;
}

// Notifications ---------------------------------------------------------------
function add_notification ($msg){
	$notifications   = get_session('notifications', array());
	$notifications[] = $msg;
	set_session('notifications', $notifications);
}

function get_notifications (){
	$notifications = get_session('notifications', array());
	set_session('notifications', null);
	return $notifications;
}

function add_warning ($msg=''){
	$warnings 	= get_global('warnings', array());
	$warnings[] = $msg;
	set_global('warnings', $warnings);
}

function get_warnings (){
	$warnings = get_global('warnings', array());
	return $warnings;
}
