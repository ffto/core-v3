<?php 
function get_url ($path=true, $args=array()){
	if (is_array($path)){
		$args = $path;
		$path = true;
	}
	
	// TODO add the trailing slash

	$args = to_args($args, [
		'path'   => $path,
		'host'   => true,
		'port'   => false,
		'query'  => array(),
		'slash'	 => false, // add the end slash
		'anchor' => false,
	], 'path');

	$path = $args['path'];

	// first check for already valid URLs
	if (is_string($path)){
		if (string_is_email($path)){
			return to_url($path);
		}elseif (is_external_url($path)){
			return strpos($path, 'http') === 0 ? $path : "http://{$path}";		
		}else if (string_is_href($path, true)){
			// $path = preg_replace('/[#|?].+$/', '', $path); // remove the anchors and query
			$path = clean_path($path);
			return $path;
		}
	}	
	
	// clean the path
	$path   = $path === true ? preg_replace('/[#|?].+$/', '', $_SERVER["REQUEST_URI"]) : $path;
	$path   = $args['path'] = clean_path($path ? "/{$path}" : '/');
	$args   = _filters('get_url-args', $args);
	$path   = _filters('get_url-path', $args['path']);
	$path   = $args['slash'] ? preg_replace('/\/$/', '', $path) . '/' : $path;
	
	$host   = $args['host'] ? get_host($args) : '';
	$query  = is_truthy($args['query']) ? (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : array()) : $args['query'];
	$query  = is_array($query) ? http_build_query($query) : $query;
	$anchor = $args['anchor'] ? $args['anchor'] : '';

	// @note make sure there's a "/" between the host and path if both are there
	$url 	= $host . ($host && $path ? '/' : '') . $path . ($query ? "?{$query}" : '') . ($anchor ? "#{$anchor}" : '');
	
    // fix URL (remove the multiple "//")
    $url = preg_replace('/(?<!:)\/{2,}/', '/', $url);
    
	// $url	= preg_replace('/\/+/', '/', $url);
	// $url 	= str_replace(':/', '://', $url);

	return $url;
}

function to_clean_url ($path){
	$path = get_url($path);
	$path = preg_replace('/[#|?].+$/', '', $path);
	return $path;
}

function is_url_path ($path, $current=null){
	$path    = to_clean_url($path);
	$current = $current ? to_clean_url($current) : get_url();
	return $path === $current;
}

function add_redirect ($match, $replace, $status=301){
	$matches = to_match($_SERVER["REQUEST_URI"], $match);
	if (!$matches) return;

	$matches = (array)$matches;
	$replace = preg_replace_callback('/\$([0-9]+)/', function ($m) use ($matches){
		$index = (int)$m[1] - 1;
		return isset($matches[$index]) ? $matches[$index] : '';
	}, $replace);
	
	redirect($replace, $status);
}

function get_previous_url (){
	$referer  = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	$referer  = preg_replace('/\/$/', '', $referer);
	$previous = $referer && get_url() !== $referer ? $referer : get_host();
	return $previous;
}

function force_https ($https=true){
	$url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

	if (!is_https() && $https){
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: https://" . $url);
    	exit();
	}else if (is_https() && !$https){
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: http://" . $url);
    	exit();
	}
}

function redirect ($url, $status=0){
	if ($url === true){
		header("Refresh:0");
	}else if ($url === ':previous'){
		$url = _get($_SERVER, 'HTTP_REFERER', '/');
		// TODO deal with $url being the same as the referer, then go to homepage
		header("Location: {$url}", true, $status);
	}else{
		header("Location: {$url}", true, $status);
	}
	die();
}

// Try to check if the source URI is in a php file (so we can skip image request, ...)
function is_request_php_file (){
	$key 	= 'server:is-php-file';
    $is_php = _global($key);
	if ($is_php === null){
		$uri    = preg_replace('/\?.+$/', '', $_SERVER["REQUEST_URI"]);
		$ext    = pathinfo($uri, PATHINFO_EXTENSION);
		$is_php = !$ext || $ext === 'php';
		set_global($key, $is_php);
	}
	return $is_php;
}


// Validate --------------------------------------------------------------------
function is_external_url ($url, $host=null){
	// TODO test with no www. = with www., but other sub-domains is internal

	// if (is_string($url) && (strpos($url, 'http') === 0 || strpos($url, '//') === 0)){
	if (is_string($url) && preg_match('/^((http)|(\/\/))/', $url)){
		$host = $host ? $host : get_host();
		$host = preg_replace('/\/$/', '', $host);
		return strpos($url, $host) === false;
	}else if ($url && is_string($url) && !preg_match('/^[#\/]/', $url)){
		$host = parse_url($url, PHP_URL_HOST);
		return $host !== $_SERVER['SERVER_NAME'];
	}
	
	return false;
}

function is_same_server (){
	return isset($_SERVER['HTTP_REFERER']) 
		   && strpos($_SERVER['HTTP_REFERER'], get_host()) === 0;
}

function is_same_page ($host=false){
	$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	$url     = get_url(['slash'=>true]);
	if ($host){
		$host = is_string($host) ? $host : get_host();
		return $url === $referer && $url === $host;
	}else{
		return $url === $referer;
	}
}

function is_host ($url, $hosts=null){
	if ($hosts === null){
		$hosts = $url;
		$url   = $_SERVER['SERVER_NAME'];
	}

	$hosts = to_array($hosts);
	$value = preg_replace('/(https?:\/\/)|(www\.)|(\/.+)/', '', $url);
	
	foreach ($hosts as $host){
		if ($host && stripos($value, $host) !== false){
			return true;
		}
	}

	return false;
}

function is_crawler (){
	return isset($_REQUEST['_escaped_fragment_']);
}

