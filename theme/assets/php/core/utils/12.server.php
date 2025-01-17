<?php
/**
 * @package Utils\Server
 */

// [ ] add a folder "+cron" so a bunch of files will be for doing cron jobs, use meta values comment

/**
 * Save a value in the cookies
 *
 * @param mixed $key 
 * @param mixed $value 
 * @param string $expire 
 * @return void
 */
function ffto_set_cookie ($key, $value, $expire='1 week'){
	$key 	= _config('cookie_prefix', '') . $key;
	$expire = strtotime($expire);

	if ($value === null || $value === false){
		unset($_COOKIE[$key]);
		setcookie($key, '', -1, '/');
	}else{
		$value  	   = _string($value, false);
		$expire		   = !$expire ? 0 : $expire;
		$_COOKIE[$key] = $value;
		setcookie($key, $value, $expire, '/');
	}
}

/**
 * Retreive a cookie value
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_cookie ($key, $fallback=null){
	$key   = _config('cookie_prefix', '') . $key;
	$value = isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;

	if ($value !== null){
		$value = stripslashes($value);
		$value = _value($value, true);
	}
	return $value === null ? $fallback : $value;
}

/**
 * Retrieve the session ID. if not initialized, it will be created. By default, the sessions are using cookies instead, set with `config:session_cookie`.
 *
 * @return string
 */
function ffto_get_session_id (){
	$is_cookie  = _config('session_cookie', false);
	$session_id = null;

	if ($is_cookie){
		$key 		= 'session';
		$expire 	= _config('session_lifetime');
		$session_id = ffto_get_cookie($key);
        $session_id = $session_id ? $session_id : _random(':uuid');
		ffto_set_cookie($key, $session_id, $expire);
	}else{
		!session_id() && session_start();
		$session_id = session_id();
	}

	return $session_id;
}

/**
 * Set a session value
 *
 * @param mixed $key 
 * @param mixed $value 
 * @return mixed
 */
function ffto_set_session ($key, $value){
	ffto_get_session_id();

	$is_cookie = _config('session_cookie', false);
	if ($is_cookie){
		$key    = "session_{$key}";
		$expire = _config('session_lifetime');
		ffto_set_cookie($key, $value, $expire);
	}else{
		if ($value === null){
			unset($_SESSION[$key]);
		}else{
			$_SESSION[$key] = $value;
		}
	}

	return $value;
}

/**
 * Retreive a session value
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_session ($key, $fallback=null){
	ffto_get_session_id();
	$is_cookie = _config('session_cookie', false);

	if ($is_cookie){
		$key   = "session_{$key}";
		$value = ffto_get_cookie($key, $fallback);
	}else{
		$value = isset($_SESSION[$key]) ? $_SESSION[$key] : $fallback;
	}

	return $value;
}

/**
 * Add a basic cron-job (will be triggered by the )
 *
 * @param mixed $name 
 * @param mixed $time 
 * @param mixed $callback 
 * @param bool $refresh 
 * @return void
 */
function ffto_add_cron_job ($name, $time, $callback=null, $refresh=false){
	if (is_callable($time)){
		$refresh  = $callback;
		$callback = $time;
		$time     = $name;
	}

	$filename = _slug($name);
	$filepath = ffto_to_path('@env/cron/'.$filename);
	$now      = microtime(true);
	$modified = is_file($filepath) ? filemtime($filepath) : 0;
	$expire   = strtotime("+{$time}", $modified);

	if ($now > $expire || $refresh){
		_apply($callback);

		$expire = 'next update: ' . ffto_to_date($now, "update={$time}&format=:utc");
		
		ffto_set_file($filepath, $expire);
	}
}

// TODO
function ffto_fetch ($url){
	
}

/* =====================================================================================================================
Hooks: Init
===================================================================================================================== */
add_action('site/init', function ($set){
    $cookie_default = _config('domain');
    $cookie_default = preg_replace('/[^a-z0-9_-]/i', '_', $cookie_default);
    $cookie_default = strtolower($cookie_default) . '_';    
    
	ffto_config_add_option('cookie_prefix', null, $cookie_default);
	ffto_config_add_option('session_cookie', null, true);
	ffto_config_add_option('session_lifetime', null, '30 minutes');
		
    // 'hash'             => '',           // coded hash to add security to the site
    // 'nonce'            => true,    

	ffto_init_cron_files();
});

function ffto_init_cron_files (){
	ffto_cache_files('cron', '@theme/+cron', [
		'format'=>function ($file){
			return isset($file['meta']['duration']) ? $file: false;
		},
		'callback'=>function ($file){
			ffto_add_cron_job($file['name'], $file['meta']['duration'], function () use ($file){
				try{
					include $file['filepath'];
				}catch (Exception $e){
					// TODO log in the error that something went wrong
				}
			});
		}
	]);
}
