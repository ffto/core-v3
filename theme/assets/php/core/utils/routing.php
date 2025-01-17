<?php
function ffto_to_route ($path, $args=null){

}

function ffto_set_routes (){

}

function ffto_get_routes ($dir='@routes', $args=null){
	$args = _args($args, [		
	]);

	$key     = _slug($dir);
	$layouts = [];           // when a file starts with a "+", it's a layout file

	$routes = ffto_cache_files($key, $dir, [
		'format' => function ($file) use (&$layouts){
			$path     = $file['path'];
			$basename = pathinfo($file['path'], PATHINFO_BASENAME);

			// it's a layout file
			if (strpos($basename, '+') === 0){
				$layouts[] = [
					'name'     => $file['name'],
					'filepath' => $file['filepath'],
					'dirpath'  => pathinfo($file['filepath'], PATHINFO_DIRNAME),
					'meta'     => $file['meta'],
				];
				return false;
			}

			// [ ] Should I deal with "*" in the path
			// [ ] Add option to alter the path to match, maybe calling the meta key = route
			// [ ] Option to whitelist, blacklist, add "need login" ...

			// Deal with special meta
			// [x] skip/hidden
			// [x] layout = false
			$skip_file    = _get($file, 'meta/skip || meta/hidden');
			$skip_layouts = _get($file, 'meta/layout') === false;

			// Skip this file
			if ($skip_file) return false;

			// root path to this folder
			if ($basename === 'index.php'){
				$path = str_replace('/index.php', '', $path);
				$path = $path ? $path : '/';
			}

			// Decode the path to be a valid url
			$path = str_replace('.php', '', $path);
			$path = str_replace('...', '@@@', $path);
			$path = str_replace('.', '/', $path);
			$path = str_replace('@@@', '...', $path);

			// Create a RegExp for mathing the URL
			$params = [];
			$match  = '/^' . preg_replace_callback('/\/([^\/]+)?/', function ($m) use (&$params){
				$m 	 = isset($m[1]) ? $m[1] : '';
				$var = $m ? _match($m, '/(\[(?<key>.+?)(?<list>\.{3})?(?<optional>\?)?\])/', true) : null;

				// There's no variable, return the text with an escaped slash
				if (!$var){
					return '\/' . $m;
				}

				$key         = $var['key'];	// the keys can only be 
				$is_list     = !!_get($var, 'list');
				$is_optional = !!_get($var, 'optional');

				// TODO Maybe the keys could have a default value from the meta data

				$params[] = [
					'key'  => $key,
					'list' => $is_list,
				];

				return '(?:\/'.($is_list ? '(.+)' : '([^\/]+)?').')' . ($is_optional ? '?' : '');
			}, $path) . '$/';

			// Check if there's a layouts matching
			$_layouts = [];
			if (!$skip_layouts){
				foreach ($layouts as $layout){
					// Match layouts by their directory
					if (strpos($file['filepath'], $layout['dirpath']) !== 0) continue;
					$key            = preg_replace('/^\+/', '', $layout['name']);
					$_layouts[$key] = $layout['filepath'];
				}				
			}

			return [
				'name'     => $file['name'],
				'path'     => $path,
				'match'    => $match,
				'params'   => $params,
				'filepath' => $file['filepath'],
				'meta'     => $file['meta'],
				'layouts'  => $_layouts,
			];
		},
	]);

	return $routes;
}

function ffto_get_route ($path, $args=null, $routes=null){
	$route = null;
	foreach ($routes as $r){
		// will match the last one
		// should match the last items (since the one with vars are before)

		if (!preg_match($r['match'], $path)) continue;
		$route = $r;
	}

	// Return it all
	if ($route){
		$match  = _match($path, $route['match'], true);		
		$values = [];
		foreach ($route['params'] as $i => $param){
			$key   = $param['key'];
			$value = isset($match[$i]) ? $match[$i] : null;
			
			// Check if there's a default in the meta (with a "$" prefix)
			if ($value === null){
				$value = _get($route, 'meta/$'.$key);
			}

			if ($param['list']){
				$value = explode('/', $value);
				$value = array_map(function ($v){ return _value($v); }, $value);
			}else{
				$value = _value($value);
			}
			
			$values[$key] = $value;
		}

		$route['values'] = $values;

		// [ ] Maybe here... should I try getting the data of the file?
		// [ ] When dealing with a current dir "./..." path, make sure it's based on the current FILE
	}

	return $route;
}