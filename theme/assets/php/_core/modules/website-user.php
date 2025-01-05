<?php 
// Login/logout ----------------------------------------------------------------
define('CURRENT_USER', 'WEBSITE_USER');

function to_user_hash ($user_id=null){
    $user_id = $user_id ? $user_id : 'user';
    $user_id = strtolower(trim((string)$user_id));
    $hash    = to_ip_address() . '|' . $user_id . '|' . _config('user_salt', '1234567890');
    $hash    = md5($hash);
	return $hash . ':' . $user_id;
}

function to_password_hash ($password, $user_id=null){
    if (!$password) return null;
    $user_id  = $user_id ? $user_id : 'user';
    $password = trim($password);
    $hash     = $user_id . '|' . $password . '|' . _config('password_salt', '0987654321');
    $hash     = md5($hash);
	return $hash;
}

function login ($user_id=null, $args=''){
	$args = to_args($args, array(
        'password' => null,
        'redirect' => null,
        'expire'   => '1 week',
    ), 'password');

    $passwords = _config('passwords');
    $username  = _config('username', true);
    $passwords = $passwords ? $passwords : ['user'=>_config('password')];
    $password  = to_password_hash($args['password'], $username ? $user_id : null);

    $user = false;
    foreach ($passwords as $i => $v){
        $pwd = to_password_hash($v, $username ? $i : null);
        if ($pwd === $password){
            $password = $v;
            $user     = $i;
            break;
        }
    }

    $user = _filters('login-user', $user, $args['password']);
    if (!$user){
        remove_cookie(CURRENT_USER);
        return false;
    }

	$hash = to_user_hash($user);
    set_cookie(CURRENT_USER, $hash, $args['expire']);

	if ($args['redirect']){
		redirect($args['redirect']);
		die();
	}

    return true;
}

function logout ($args=''){
    $args = to_args($args, array(
        'redirect' => null,
    ), 'redirect');

    remove_cookie(CURRENT_USER);

	if ($args['redirect']){
		redirect($args['redirect']);
		die();
	}
}

function get_user_id (){
	$cookie  = get_cookie(CURRENT_USER, '');
	$pair    = explode(':', $cookie);
	$user_id = isset($pair[1]) ? $pair[1] : null;
    $hash    = $user_id ? to_user_hash($user_id) : null;

    if ($cookie === $hash){
        return $user_id;
    }else{
        !headers_sent() && logout();
    }

    return false;
}

function is_logged_in ($user_id=null){
    $username = _config('username', true);
    $id       = get_user_id();
    $user_id  = $user_id ? $user_id : 'user';

    if (is($id)){
        return $username ? (is($user_id) ? $user_id == $id : true) : true;
    }

    return false;
}