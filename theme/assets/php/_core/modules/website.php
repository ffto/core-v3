<?php 
// Routes ----------------------------------------------------------------------
define('ROUTES', 'website_routes');

function to_route ($path, $route=''){
    $path      = clean_path("/{$path}");
    $path      = str_replace(':', '$', $path);                                  // old way of specifying parameters. eg.: "/path/:id"
    $path      = preg_replace('/\[([a-zA-Z0-9-_?$.*]+?)\]/', '\$$1', $path);
    $path      = str_replace('.', '/', $path);                                  // simplify path, instead of deep files, we can simulate a path with "dots" separator, so: path.to.file.[id] = path/to/file/[id]

    $alias     = array();
    $route     = to_args($route);
    $params    = array();
    $template  = array();
    $classname = array();
    $is_exact  = true;
    $has_param = false;
    
    $match = '/^' . preg_replace_callback('/\/(\:?[^\/]+)/', function ($m) use (&$params, &$classname, &$is_exact, &$has_param, &$alias, &$template){
		$key 		 = $m[1];
		$is_param 	 = strpos($key, '$') !== false;
		$is_optional = strpos($key, '?') !== false;
		$is_list 	 = strpos($key, '...') !== false;
        $is_more     = strpos($key, '*') !== false;
        $key 		 = str_replace(array('$','?','...'), '', $key);
		$template[]  = $is_param ? "\$$key" : $key;
        
        if (!$is_param){
            $key = preg_replace('/([+-_])/', '\\\$1', $key);
			$key = str_replace('*', '.*', $key);

            if (!$is_more){
                $classname[] = $key;
            }else{
                $is_exact = false;
            }

            if (!$has_param){
                $alias[] = $key;
            }

			return '\/' . $key;
		}else{
            $has_param = true;
        }

		$params[] = array(
			'key'      => $key,
			'list'     => $is_list,
			'optional' => $is_optional,
		);

		$selector = '';
		
		if ($is_list){
			$selector = '(?:(\/.+)\/?)'; 
		}else{
			$selector = '(?:(\/[^\/]+)\/?)';
		}
		if ($is_optional){
			$selector .= '?';
		}

		return $selector;
	}, $path) . '$/';

    $route['url']       = '/' . implode('/', $template);
    $route['alias']     = $route['view'] === true ? '' : (isset($route['alias']) ? $route['alias'] : implode('-', $alias));
    $route['match']     = $match;
    $route['params']    = $params;

    return $route;
};

function set_routes ($args='', $routes=null){
    if ($routes === null){
		$routes = $args;
		$args   = array();
	}

	$args = to_args($args, array(
        'dir'   => null,
        'base'  => '',      // base to the URIs
        'meta'  => false,   // fetch meta values from the file
        'order' => null,    // order to check the routes
	));
    $args = _filters('set_routes_args', $args);

    // dynamic fetching of the paths defined in the files
    if ($routes === true){
        $dir    = $args['dir'] ? $args['dir'] : "@routes/{$args['base']}";
        $routes = [];

        // TODO missing the root / I think
        
        each_files('*', array(
            'dir'  => $dir,
            'deep' => true,
        ), function ($file) use (&$routes){
            if ($file['ext'] !== 'php') return;
            $path           = $file['relativepath'];
            $slug           = str_replace('.php', '', $path);
            $routes[$slug]  = $path;
        });

        // TODO maybe add special file names for ERRORs or something like that, a bit like: https://kit.svelte.dev/docs/routing
    }

    $all    = get_global(ROUTES, array());
    $routes	= is_array($routes) ? $routes : array($routes);
    foreach ($routes as $path => $route){
        $route = to_args($route, array_merge($args, array(
            'view' => null,
        ), $args), 'view');
        
        $path  = $route['base'] ? $route['base'] . $path : $path;
        $path  = clean_path("/{$path}");
        $route = to_route($path, $route);

        if ($route['view'] === true && $route['order'] === null){
            $route['order'] = INF;    
        }        

        // get the meta-data from the file
        if ($args['meta'] && $fullpath = to_filepath('@routes/' . $path)){
            $route['meta'] = to_file_meta($fullpath);
        }else{
            $route['meta'] = array();
        }

        $all[$path] = $route;        
    }

    $all = array_sort($all, 'order ASC NULL_BEFORE');

    page_is_dirty(true);
    set_global(ROUTES, $all);

    return $all;
}

function get_routes (){
    return get_global(ROUTES, array());
}

// Conditions ------------------------------------------------------------------
function is_password_protected ($page=null, $route=true){
    $is_protected = isset($page['protected']) ? $page['protected'] : false;
    return $is_protected || _config('protected');

	// $whitelist_domains  = get_page_meta('whitelist_domains', array());
	// $password_protected = get_page_meta('password_protected', false);

	// if ($password_protected && $password_protected !== true){
	// 	$date               = to_date($password_protected);
	// 	$now                = to_date('now');
	// 	$password_protected = $now <= $date;
	// }
	
	// return $password_protected && !is_logged_in() && (!$whitelisted || !is_domain($whitelist_domains));
}

function is_whitelisted ($url){
	// $whitelist 		= get_page_meta('whitelist', array());
	// $whitelist		= to_array($whitelist);
	// $is_whitelisted = false;

	// foreach ($whitelist as $whitelist_page){
	// 	$route = parse_route($whitelist_page);

	// 	if (preg_match($route['match'], $url)){
	// 		$is_whitelisted = true;
	// 		break;
	// 	}
	// }
	
	// return $is_whitelisted;
}

function is_coming_soon ($launch='2010-06-14', $timezone='America/New_York'){
	/*
	$now 	= strtotime('now');
	$active = new DateTime($launch, new DateTimeZone($timezone));
	$active = $active->format('U');
	return $now <= $active;
	*/
}

// Page ------------------------------------------------------------------------
define('PAGE', 'website_page');
define('PAGE_REFRESH', 'website_page_refresh');
define('PAGE_PARSING', 'website_page_parsing');

function set_page ($key='', $value=''){
    if ($key === true){
        $key = array('refresh' => true);
    }

	$page = get_global(PAGE, array(
        // 'context'   => null,
        'classname' => array(),
        'attrs'     => array(),
        'style'     => array(),
        'data'      => array(),
        'settings'  => array(),
    ));

	$set   = to_set($key, $value);
    $page  = array_join([$page, $set], array(
        'filter' => 'html',
        'object' => false,
        'array'  => false,
    ));

    page_is_dirty(true);
    set_global(PAGE, $page);

    return $page;
}

function page_is_dirty ($value=null){
    if (is_bool($value)){
        set_global(PAGE_REFRESH, $value);
    }
    return get_global(PAGE_REFRESH, true);
}

function can_parse_page ($value=null){
    if (is_bool($value)){
        set_global(PAGE_PARSING, $value);
    }
    return get_global(PAGE_PARSING, true);
}

// TODO change the code to NOT call get_page(), or at least not erase the default PAGE
function to_page ($path='', $args=''){
    $args = to_args($args, array(
        'dir'           => '@routes',
        'password_view' => '@theme/password',
        'layout'        => '@theme/layout',
        'values'        => [],
        'lang'          => null,
        'context'       => null,
    ));
    $args = _filters('to_page_args', $args);

    $old_ctx            = get_context();
    $old_lang           = get_lang();
    $args['context'] && set_context($args['context']);
    $args['lang'] &&    set_lang($args['lang']);

    $langs = get_langs(null);
    $lang  = get_lang();
    
    if (!$path){
        $folder = _config('folder');
        $folder = $folder ? $folder : '';
        $path   = preg_replace('/\?.*/', '', $_SERVER["REQUEST_URI"]);
        $path   = str_replace($folder, '', $path);
    }

    $path  = preg_replace('/^\/|\/$/', '', $path);
    $slugs = explode('/', $path);
    $slugs = array_filter($slugs, 'is');

    // remove the lang from the URLs
    if ($lang === reset($slugs) && !$langs['query']){
        array_shift($slugs);
    }

    // find route --------------------------------------------------------------
    $path   = '/' . implode('/', $slugs);
    $routes = get_routes();
    $page   = get_global(PAGE, array());
   
    $route  = null;
    $values = array();
    
	foreach ($routes as $r){
        if (!preg_match($r['match'], $path, $matches)) continue;

		$values = array();
        foreach ($r['params'] as $i => $param){
            $key   = $param['key'];
            $value = isset($matches[$i + 1]) ? $matches[$i + 1] : null;
            $value = $value ? preg_replace('/^\//', '', $value) : $value;

			if ($param['list']){
				$value = $value ? explode('/', $value) : array();
				$value = array_filter($value);
				$value = to_values($value);
			}else{
				$value = to_value($value);
			}

			$values[$key] = $value;
		}
		
        $route  = $r;
        $values = $values;

        break;
	}
        
    $route = to_args($route ? $route : array(), array(
		'dir'       => null,    // path to the template
		'base'      => '',      // base to the URIs
        'view'      => true,
        'url'       => null, 
		'layout'    => null,    // layout file to use
		'alias'     => '',      // base classname prefix
        'page'      => array(), // page defaults
		'error'     => '404',   // error file to use IF the template isn't found
		'redirect'  => false,   // redirect URL if the template isn't found
        'classname' => null,
        'on_match'  => null,
        'config'    => false,   // change the "config" when matching this route
		'values'    => array(),
    ));

    // setup
    if (!empty($route['config'])){
        config($route['config']);
    }

    $route = _apply($route['on_match'], $route, $page);

    // paths
    $dir    = $route['dir'] ? $route['dir'] . '/' : $args['dir'] . '/' . $route['base'];
    $view   = $route['view'];
    $view   = $view === true ? $view = str_replace($route['base'], '', $path) : $view;
    $values = array_merge($route['values'], $values, $args['values']);

    // attrs 
    $alias = $route['alias'] ? $route['alias'] : null;
    $alias = $alias ? $alias : (count($slugs) ? implode('-', $slugs) : 'home');

    $title = count($slugs) ? ucfirst(implode(' ', explode('-', $alias))) : '';
    $title = isset($page['title']) && !$title ? $page['title'] : $title;
    $title = urldecode($title);
    
    $page = array_merge($route['page'], array(
        'status'    => 200,
        'path'      => $path,
        'lang'      => $lang ? $lang : '',
        'alias'     => $alias,
        'title'     => $title,
        'route'     => $route['url'] ? $route['url'] : null,
        'layout'    => $route['layout'] !== null ? $route['layout'] : $args['layout'],
        'template'  => '',
        'html'      => null,
        'json'      => null,
        'redirect'  => false,
        'protected' => isset($route['protected']) ? $route['protected'] : false,
        'config'    => $route['config'],
        'values'    => $values,
    ));

    // get the html
    $config   = _config();
    $filepath = is_string($view) ? to_filepath($dir . $view) : $view;

    // TODO Add permission for types of user to access a page
    if (
        !is_logged_in() 
        && is_password_protected($page, $route)
    ){
        if ($protected = to_filepath($args['password_view'])){
            $filepath       = $protected;
            $page['layout'] = false;
        }

        $page['protected'] = true;
	}

    // get the html
    $page['template'] = is_string($filepath) ? str_replace($config['aliases']['@theme'], '', $filepath) : null;
    set_page($page);
    
    // add current directory, for the @dir alias
    $dir = $filepath ? pathinfo($filepath, PATHINFO_DIRNAME) . '/' : './';
    config([
        'dir' => $dir
    ]);

    $view = $filepath ? to_content($filepath, [
        'file' => true,
        'url'  => false,
        'data' => $values,
    ]) : null;

    if (!$view && $route['redirect']){
        $view             = null;
        $page['status']   = 301;
        $page['redirect'] = $route['redirect'];
    }else if (!$view && $route['error']){
        $filepath = clean_path($dir . $route['error']);
        $filepath = to_filepath($filepath);
        $view     = $filepath ? to_content($filepath, [
            'file' => true,
            'url'  => false,
            'data' => $values,
        ]) : $view;
    }

    // done blocking the page parsing
    $page = get_page();
    $page = _filters('to_page-data', $page);    
    
    $classnames = to_classnames(array(
        $route['classname'],
        isset($page['classname']) ? $page['classname'] : null,
        isset($page['class']) ? $page['class'] : null,
    ));

    $page['attrs'] = to_attributes(array(
        $page['attrs'], 
        'classname' => $classnames,
        'style'     => isset($page['style']) ? $page['style'] : null,
        'data'      => isset($page['data']) ? $page['data'] : null,
    ), 'return=object');
    
    unset($page['classname']);
    unset($page['class']);
    unset($page['style']);
    unset($page['data']);

    $page['status']   = get_status();
    $page['filepath'] = $filepath;
    $page['html']     = is_string($view) ? $view : null;
    $page['json']     = is_array($view) ? $view : null;

    // return the lang to the previous version
    $args['lang'] &&    set_lang($old_lang);
    $args['context'] && set_context($old_ctx);

    return $page;
}

function get_page ($key=false, $fallback=null){
    $is_dirty    = page_is_dirty();
    $is_parsable = can_parse_page();
    $page        = get_global(PAGE, array());
    $is_forced   = $key === true && !isset($page['status']); 

    if (($is_dirty && $is_parsable) || $is_forced){
        can_parse_page(false);
        $page = to_page(null, $page);
        $page = set_global(PAGE, $page);
        can_parse_page(true);
        page_is_dirty(false);
    }
    
    if (is_string($key)){
        return isset($page[$key]) ? $page[$key] : $fallback;
    }else{
        return $page;
    }
}

function is_page ($args=''){
	$args = to_args($args, array(
        'alias'    => '',
        'template' => '',
	), 'alias');

	$page     = get_page();
	$is_valid = true;

	if ($args['alias'] && $page['alias'] !== $args['alias'])            $is_valid = false;
	if ($args['template'] && $page['template'] !== $args['template'])   $is_valid = false;

	return $is_valid;
}

function the_page ($page=null, $args=''){
    $args = to_args($args, [
        'classname' => 'page',
    ]);

    if (array_key_exists('logout', $_GET)){
        logout(':previous');
    }

    if ($page){
        $page = to_args($page);
        set_page($page);
    }

    $args = _filters('the_page_args', $args);
    $page = get_page(true);
    can_parse_page(false);

    if ($page['redirect']){
        _global(['output_logs'=>false]);
        redirect($page['redirect'], $page['status']);
    }else if ($page['json']){
        _global(['output_logs'=>false]);
        to_json_api($page['json'], true);
    }else if (is_string($page['html'])){
        $layout       = to_filepath($page['layout']);
        $root         = get_value($page, 'classname', $args['classname']);
        $alias        = $page['alias'] ? "{$root}-{$page['alias']}" : $root;
        $classnames   = isset($page['attrs']['class']) ? $page['attrs']['class'] : array();
        $classnames   = to_classnames([$root, $alias, $classnames]);
        $attrs        = to_attributes(array($page['attrs'], 'class'=>$classnames));
        $attrs        = replace_alias($attrs, $alias);
        
        $page['meta'] = to_file_meta($page['filepath']);
        $html         = ($page['html'] ? $page['html'] : '') . NL; // add a new-line to look beeter as an output
        $html         = _filters('the_page-html', $html, $page);
        $html         = replace_alias($html, $alias);

        if ($layout){
            $logs = _global('output_logs');
            ($logs === null) && _global(['output_logs'=>true]);

            $html = to_content($layout, [
                'file' => true,
                'skip' => false,
                'data' => array(
                    'page'          => $page,
                    'page_lang'     => $page['lang'],
                    'page_sitename' => _config('sitename'),
                    'page_title'    => $page['title'],
                    'page_attrs'    => $attrs,
                    'page_html'     => $html,
                    'body_open'     => "<body{$attrs}>",
                    'body_close'    => "</body>"
                ),
            ]);
        }else{
            _global(['output_logs'=>false]);
        }
        
        echo $html;
    }else{
        // nothing
    }
}

function get_api ($path, $data=null, $args=''){
    $data    = $data ? _args($data) : null;
    $content = null;

    if (string_is_url($path, true)){
        // TODO
        // $content = http($path, $args);
    }else{
        $old_page = get_global(PAGE);
        $page     = to_page($path, ['values'=>$data]);
        
        // reset the other pages?

        if ($page['redirect']){
            $content = $page['redirect'];
        }else if ($page['json']){
            $content = $page['json'];
        }else if ($page['html']){
            $layout = to_filepath($page['layout']);
            // TODO maybe no need for the layout

            // $content = $layout ? to_content($layout, [
            //     'file' => true,
            //     'data' => array('page' => $page),
            //     'alias'=> $page['alias'],
            // ]) : $page['html'];
        }

        set_global(PAGE, $old_page);
    }

    return $content;
}

function _page ($key=null, $fallback=null){
    if (is_array($key)){
        set_page($key);
        return;
    }
    return get_page($key, $fallback);
}

// TODO test if this work properly
function get_page_classname (){
    return _page('alias');
}

add_filter('_request', function ($value, $key, $fallback){
    $values = _page('values');
    $value  = is_string($key) && isset($values[$key]) ? $values[$key] : $value;
    return $value;
});
