<?php
function ffto_to_route ($path, $args=null){
	// [x] Should I deal with "*" in the path
	// [ ] Add option to alter the path to match, maybe calling the meta key = route
	// [ ] Option to whitelist, blacklist, add "need login" ...
	// [ ] add option to limit to only GET/POST/PUT/PATCH/DELETE/OPTIONS/HEAD
	// [ ] Be able to deal with "server-event", "long-pooling", ....
	// [ ] Add default 404, layout, ...

	$content  = null;
	$filepath = null;
	if (is_string($args)){
		$filepath = ffto_parse_path($args);
		$args     = null;
	}else if (!is_array($args)){
		$content = $args;
		$args    = null;
	}

	// [ ] add type of supported method (put, push, get, ....)

	$args = _args($args, [
		'name'     => null,
		'filepath' => $filepath,
		'content'  => $content,    // a function of a value
		'method'   => null,
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

		$render = function ($values=null) use ($filepath){ 
			return ffto_include_file($filepath, $values, true); 
		};
	}else if ($content = $args['content']){		
		if (ffto_is_callback($content)){
			$render = function ($values) use ($content){
				return ffto_include_file($content, $values, true); 
			};
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

function ffto_to_routes ($dir=null, $args=null){
	if ($dir === true){
		$dir  = null;
		$args = true;
	}

	$args = _args($args, [
		
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

	// [ ] WHEN saving the routes, they would have a key like: "{method} {$path}", like "GET /my-path" or "POST /update-something", if valid for everything, then it's "/only-the-url"

	return $routes;
}

function ffto_set_routes ($routes, $args=null){
	// Get the default "dir" #routes
	if ($routes === true){
		$routes = ffto_to_routes(null, $args);
	}

	if (ffto_is_obj($routes)){
		$routes = [$routes];
	}

	$args = _args($args, [
		'key' => null,
	], 'key');

	if (!is_string($key = $args['key'])){
		$key = '$routes';
	}
	
	$_routes = _global($key, []);
	$_routes = array_merge($_routes, $routes);
	_global($key, $_routes, true);

	return $routes;
}

function ffto_add_route ($path, $args=null, $save=true){
	$route = ffto_to_route($path, $args);

	$args = _args($args, [
		'save' => $save,
	]);

	$save && ffto_set_routes($route, $save);

	return $route;
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
		'data'   => null,
		'routes' => $routes,
		'return' => null,
	], 'return');

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
		if ($return = $args['return']){
			$content          = $route['render']($values); // _var() can be used to get the $values
			$route['content'] = $content;
		}

		// TODO add $route['html'] for the output

		if (is_string($return)){
			$route = _get($route, $return);
		}

		// [ ] Deal with layouts, and types of return data (eg.: server-event, long-pooling, logs to save?)
		// [ ] Add the $values to _request() too, not _query, not _post, but _request includes the _var() too
		// [ ] Maybe here... should I try getting the data of the file?
		// [ ] When dealing with a current dir "./..." path, make sure it's based on the current FILE
	}

	return $route;
}
