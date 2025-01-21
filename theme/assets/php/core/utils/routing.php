<?php
function ffto_to_route ($path, $args=null){
	// [x] Should I deal with "*" in the path
	// [ ] Add option to alter the path to match, maybe calling the meta key = route
	// [ ] Option to whitelist, blacklist, add "need login" ...
	// [ ] add option to limit to only GET/POST/PUT/PATCH/DELETE/OPTIONS/HEAD
	// [ ] Be able to deal with "server-event", "long-pooling", ....
	// [ ] Add default 404, layout, ...

	$args = _args($args, [
		'name'	   => null,
		'filepath' => null,
		'content'  => null,   // a function of a value
		'meta'     => [],
		'layouts'  => null
	]);

	// Decode the path to be a valid url
	$path = str_replace('.php', '', $path);		// Remove the php extension
	$path = str_replace('...', '@@@', $path);	// Encode the "..." to something else, since we replace "." to "/"
	$path = str_replace('.', '/', $path);		// Replace the path separator "." to "/"
	$path = str_replace('@@@', '...', $path);	// Re-add the "..."
	$path = str_replace('{', '[', $path);		// Alternative to "{" => "["
	$path = str_replace('}', ']', $path);		// Alternative to "}" => "]"
	$path = str_replace('*', '.+', $path);		// Anytime there's a start, it means ".+"

	// Create a RegExp for mathing the URL
	$params = [];
	$match  = '/^' . preg_replace_callback('/\/([^\/]+)?/', function ($m) use (&$params){
		$m   = isset($m[1]) ? $m[1] : '';
		$var = $m ? _match($m, '/(\[(?<key>.+?)(?<list>\.{3})?(?<optional>\?)?\])/', true) : null;
		$key = $var ? $var['key'] : $m;
		$key = preg_replace('/([+-_])/', '\\\$1', $key);

		// There's no variable, return the text with an escaped slash
		if (!$var){
			return '\/' . $m;
		}

		$is_list     = !!_get($var, 'list');
		$is_optional = !!_get($var, 'optional');

		$params[] = [
			'key'  => $key,
			'list' => $is_list,
		];

		return '(?:\/'.($is_list ? '(.+)' : '([^\/]+)?').')' . ($is_optional ? '?' : '');
	}, $path) . '$/';

	$meta    = $args['meta'];
	$layouts = $args['layouts'];
	$render  = null;
	if ($filepath = ffto_get_path($args['filepath'])){
		// If the meta hasn't been fetched already, try getting it
		if (!$meta){
			$meta = ffto_get_file_meta($filepath);
		}

		$render = function () use ($filepath, $meta){ return ffto_include_file($filepath, $meta); };
	}else if ($content = $args['content']){		
		if (ffto_is_callback($content)){
			$render = $content;
		}else{
			$render = function () use ($content){ return $content; };
		}
	}

	// [ ] Deal with layout coming from $meta AND the name

	return [
		'name'     => $args['name'] ? $args['name'] : _get($meta, 'name'),
		'path'     => $path,
		'match'    => $match,
		'params'   => $params,
		'filepath' => $filepath,
		'render'   => $render,
		'meta'     => $meta ? $meta : [],
		'layouts'  => $layouts ? $layouts : [],
	];
}

function ffto_get_routes ($dir=null, $args=null){
	if ($dir === true){
		$dir  = null;
		$args = true;
	}

	if (is_bool($args)){
		$args = ['save'=>$args];
	}

	$args = _args($args, [
		'save'	=> false,
	]);

	$dir     = $dir ? $dir : '@routes';
	$key     = _slug($dir);
	$layouts = [];                       // when a file starts with a "+", it's a layout file

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

			return ffto_to_route($path, [
				'filepath' => $file['filepath'],
				'meta'     => $file['meta'],
				'layouts'  => $_layouts
			]);
		},
	]);

	// Save the routes to a global key
	if ($args['save']){
		$_routes = _global('$routes', []);
		$_routes = array_merge($_routes, $routes);
		_global('$routes', $_routes, true);
	}

	return $routes;
}

function ffto_set_routes (){

}

function ffto_add_route ($path, $args=null){
	// Routes can be a filepath, a function, just content
}

function ffto_get_route ($path=null, $args=null, $routes=null){
	if (is_bool($path)){
		$args = $path;
		$path = null;
	}

	// Default path to the current URL
	if (!$path){
		$path = ffto_to_url(null, 'slash=0&return=path');
	}

	$args = _args($args, [
		'routes' => $routes,
		'render' => false,
	], 'render');

	$routes = $args['routes'] ? $args['routes'] : _global('$routes', []);
	$route  = null;
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

		// Render the route and get the resulting html
		if ($args['render']){
			$content          = $route['render']();
			$route['content'] = $content;
		}

		// [ ] Maybe here... should I try getting the data of the file?
		// [ ] When dealing with a current dir "./..." path, make sure it's based on the current FILE
	}

	return $route;
}
