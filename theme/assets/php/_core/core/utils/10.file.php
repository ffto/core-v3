<?php 
/* =====================================================================================================================
File
===================================================================================================================== */
/**
 * Retreive a bunch of extra data about a file: path, url, type, mime_type, title, filename, fullname, ext, date_created, date_modified, meta
 * 
 * @param string $path 
 * @param mixed $args 
 * 	- 'meta' [false] add the meta data (with `get_file_meta`)
 * 	- 'version' [false] add versioning to the file URL
 * @return array
 */
function get_file_info ($path, $args=null){
	if (!($path = get_path($path))) return;

	$args = _args($args, [
		'meta'	  => false,
		'version' => false,
	]);
	
	$url  = path_to_url($path);
	$url  = _version($url, $args['version']);
	$info = pathinfo($path);
	$mime = mime_content_type($path);
	$type = is_dir($path) ? 'directory' : $mime;

	// get the right mime-type 
	if (strpos($type, '/') !== false){
		$type = explode('/', $type);
		$type = $type[0];
	}

	$info = [
		'path'          => $path,
		'url'           => $url,
		'type'          => $type,
		'mime'          => $mime,
		'title'         => ucwords(str_replace(array('-', '.'), ' ', $info['filename'])),
		'filename'      => $info['filename'],
		'fullname'      => $info['basename'],
		'ext'           => isset($info['extension']) ? $info['extension'] : '',
		'date_created'  => filectime($path),
		'date_modified' => filemtime($path),
	];

	if ($args['meta']){
		$info['meta'] = get_file_meta($path, false);
	}

	return $info;
}

/**
 * Decode a file metadata. The metadata is a comment block which finish with a double star, like this:
 *  \/\*\* 
 * 	\* Key : Value
 *  \*\*\/
 *
 * @param mixed $path 
 * @param mixed $args 
 * 	- 'slugify' ['key'] will turn into slug the key and/or the value
 * @return array
 */
function get_file_meta ($path, $args=null){
	if (!($path = get_path($path))) return [];

	$content = file_get_contents($path);
	if (!preg_match('/\/\*\*((?:.|\n)+?)\*\*\//', $content, $match)) return [];

	if ($args === true){
		$args = ['slugify' => 'key,value'];
	}

	$args = _args($args, array(
		'slugify' => 'key',
	), 'slugify');

	// all meta pairs
	$lines = explode(NL, $match[1]);
	$_key   = null;
	$_value = null;
	
	// goes through all the lines and decode the "key: value", the extra text is appended to the previous meta value
	$meta = [];
	foreach ($lines as $line){
		if (!trim($line)) continue;

		// match a key/pair
		$pair = _match($line, '/\*\s(?<key>.+?):(?<value>.*)$/', ':group');
		if (isset($pair['key']) && isset($pair['value'])){
			// add the meta 
			if ($_key && $_value){
				$meta[$_key] = $_value;
			}

			$_key   = trim($pair['key']);
			$_value = trim($pair['value']);
		}else if ($_value !== null){
			$_value = $_value . ' ' . preg_replace('/\*[\s|\t]+/', ' ', $line);
			$_value = preg_replace('/\s{2,}/', ' ', $_value);
			$_value = trim($_value);
		}
	}
	// add the last key/value pair
	if ($_key && $_value){
		$meta[$_key] = $_value;
	}

	if ($slugify = $args['slugify']){
		$slugify  = _array($slugify);
		$_meta    = [];
		$is_key   = in_array('key', $slugify);
		$is_value = in_array('value', $slugify);
		foreach ($meta as $i => $v){
			$i = $is_key ? _slug($i, '_') : $i;
			$v = $is_value ? _slug($v, '_') : $v;
			$_meta[$i] = $v;
		}
		$meta = $_meta;
	}

	return $meta;
}

/* =====================================================================================================================
URL
===================================================================================================================== */
/**
 * Add a version variable to the URL.
 * 
 * ```php
 * _version('http://project.com');
 * // "http://project.com?ver=1.0.0"
 * 
 * $v = path_to_url(__FILE__);
 * $v = _version($v, true);
 * // "https://www.project.local.com/theme/functions.php?ver=1709867193"
 * ```
 *
 * @param string $url
 * @param mixed $version
 * @return string
 */
function _version ($url, $version=null){
	if (!is_str($url) || !$version) return $url;

	// by default it takes the current 
	if ($version === ':file'){
		$path    = url_to_path($url);
		$version = is_file($path) ? filemtime($path) : null;
	}

	$version = is_string($version) || is_numeric($version) ? $version : _config('version', '1.0.0');
	$version = 'ver=' . $version;

	// TODO deal with anchor "#"

	return strpos($url, '?') !== false ? $url.'&'.$version : $url.'?'.$version;
}

// TODO
function to_url (){

}

/* =====================================================================================================================
Conditions
===================================================================================================================== */
// TODO
function is_url ($v){

}

/* =====================================================================================================================
Casting
===================================================================================================================== */
/**
 * Switch the PATH to a url if it's a local path
 * 
 * ```php
 * path_to_url('bob/cool.gif');
 * // "https://www.project.com/bob/cool.gif"
 * 
 * path_to_url('@uploads/bob/cool.gif');
 * // "https://www.project.local.com/_env-local/uploads/bob/cool.gif"
 * ```
 * 
 * @param mixed $url 
 * @return string
 */
function path_to_url ($path){
	$_path = _global('$directories.paths.root');
	$_url  = _global('$directories.urls.root');
	$path  = to_path($path); // resolve the path
	
	// the root path is there, replace it with the URL root
	if (strpos($path, $_path) === 0){
		$url = str_replace($_path, $_url, $path);
	// it's a simple path
	}else{
		$url = $_url . preg_replace('/^\//', '', $path);
	}

	return $url;
}

/**
 * Switch the URL to a path if it's a local url
 * 
 * ```php
 * url_to_path('https://www.project.local.com/_env-local/uploads/bob/cool.gif?bob=1');
 * // "/Users/computer/Documents/Websites/_sites/project/_env-local/uploads/bob/cool.gif"
 * ```
 * 
 * @param mixed $url 
 * @return string
 */
function url_to_path ($url){
	$_url  = _global('$directories.urls.root');
	$_path = _global('$directories.paths.root');
	$url   = preg_replace('/[?#].+$/', '', $url);	// remove all any extra params (query or anchor)
	$path  = str_replace($_url, $_path, $url);
	return $path;	
}
