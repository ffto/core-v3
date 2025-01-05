<?php 
/**
 * @package Utils\File
 */

/* =====================================================================================================================
File
===================================================================================================================== */
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
function ffto_get_file_meta ($path, $args=null){
	if (!($path = ffto_get_path($path))) return [];

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
	$_meta = [];
	foreach ($lines as $line){
		if (!trim($line)) continue;

		// match a key/pair
		$pair = _match($line, '/\*\s(?<key>.+?):(?<value>.*)$/', ':group');
		if (isset($pair['key']) && isset($pair['value'])){
			// add the meta 
			if ($_key && $_value){
				$_meta[$_key] = $_value;
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
		$_meta[$_key] = $_value;
	}

	// should key/value be sluggified
	$slugify  = _array($args['slugify']);
	$is_key   = in_array('key', $slugify);
	$is_value = in_array('value', $slugify);
	
	$meta = [];
	foreach ($_meta as $i => $v){
		$i        = $is_key ? _slug($i, '_') : $i;
		$v        = $is_value ? _slug($v, '_') : $v;
		$meta[$i] = _value($v);
	}	

	return $meta;
}

/**
 * Recursivly delete a file or directory and it's children.
 *
 * @param mixed $path 
 * @return bool
 */
function ffto_remove_file ($path){
	$path = ffto_get_path($path);
	
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
 * Move a file to another path, add a numeric value if the filename destination already exists
 *
 * @param string $from
 * @param string $to
 * @param mixed $args
 * 	- 'overwrite' [false] should the file overwrite if the path already exists
 * 	- 'copy' [' {index}'] the copy name template
 * @return string
 */
function ffto_move_file ($from, $to, $args=''){
	if ($args === true){
		$args = ['overwrite'=>true];
	}

	$args = _args($args, [
		'overwrite' => false,
		'copy'		=> ' {index}',
	]);

	$from = ffto_get_path($from);
	if (!$from) return false;

	$to = ffto_to_path($to);
	if (file_exists($to) && !$args['overwrite']){
		$info  = pathinfo($to);
		$dir   = $info['dirname'];
		$name  = $info['filename'];
		$ext   = isset($info['extension']) ? ".{$info['extension']}" : '';
		$index = 2;

		while (file_exists($to)){
			$copy = _replace($args['copy'], ['index'=>$index]);
			$to   = "{$dir}/{$name}{$copy}{$ext}";
			$index++;
		}

		ffto_set_directory($dir);
	}

	rename($from, $to);
	chmod($to, 0755);

	return $to;
}

/**
 * Include a file and return either it's content, or special values in case a value is returned at the end of the file (with "return");
 *
 * @param mixed $path 
 * @param array $vars Variables to pass so the file ca access it
 * @return mixed
 */
function ffto_include_file ($path, $vars=[]){
	_start();

	$old_vars = ffto_get_var();

	extract($vars);		 // the variables will be available at the root of the inluded file
	ffto_set_var($vars); // the variables will be available with the use of {@see ffto_get_var()} and it's shortcut {@see _var()}
	
	$response = include($path);
	$content  = _end();
	$content  = $content ? $content : '';

	ffto_set_var($old_vars, null, true);

	// return a JSON message
	if (is_string($response)){
		$content = ['message'=>$response];
	// return a HTTP Success value
	}else if (is_bool($response)){
		$content = ['success'=>$response];	
	// return a HTTP Status code
	}else if (is_numeric($response) && $response >= 100){
		$content = ['status'=>$response];
	// return data
	}else if (is_array($response)){
		$content = $response;
	}

	return $content;
}

/**
 * Parse and cache files in the @cache/files folder so they can be accessed faster.
 *
 * @param string $key
 * @param string $path
 * @param mixed $args
 * 	- 'format' [null] format the cache file data
 * 	- 'callback' [null] call a function on each cached files
 * @return array
 */
function ffto_cache_files ($key, $path, $args=null){
	$args = _args($args, [
		'format'   => null,
		'callback' => null,
		'refresh'  => false,
	], 'callback');

	$cache_path = "@cache/files/{$key}.json";
	$files      = ffto_get_file($cache_path);

	if ($files === null || $args['refresh'] || ffto_is_new_version() || ffto_is_dev()){
		$files = ffto_get_files($path, ['meta'=>true, 'format'=>function ($v) use ($args){
			$file = array_merge([
				'name' => $v['name'],
				'path' => $v['path'],
			], $v['meta']);
			return _apply($args['format'], $file);
		}]);
		ffto_set_file($cache_path, $files);
	}

	$files = is_array($files) ? $files : [];
	foreach ($files as $file){
		_call($args['callback'], $file);
	}
	
	return $files;
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
	if (!ffto_is_str($url) || !$version) return $url;

	// by default it takes the current 
	if ($version === ':file'){
		$path    = ffto_url_to_path($url);
		$version = is_file($path) ? filemtime($path) : null;
	}

	$version = is_string($version) || is_numeric($version) ? $version : _config('version', '1.0.0');
	$version = 'ver=' . $version;

	// TODO deal with anchor "#"

	return strpos($url, '?') !== false ? $url.'&'.$version : $url.'?'.$version;
}

/* =====================================================================================================================
Conditions
===================================================================================================================== */

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
function ffto_path_to_url ($path, $version=false){
	$_path = _global('$directories/paths/root');
	$_url  = _global('$directories/urls/root');
	$path  = ffto_to_path($path); // resolve the path
	
	// the root path is there, replace it with the URL root
	if (strpos($path, $_path) === 0){
		$url = str_replace($_path, $_url, $path);
	// it's a simple path
	}else{
		$url = $_url . preg_replace('/^\//', '', $path);
	}

	$url = _version($url, $version);

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
function ffto_url_to_path ($url){
	$_url  = _global('$directories/urls/root');
	$_path = _global('$directories/paths/root');
	$url   = preg_replace('/[?#].+$/', '', $url);	// remove all any extra params (query or anchor)
	$path  = str_replace($_url, $_path, $url);
	return $path;	
}
