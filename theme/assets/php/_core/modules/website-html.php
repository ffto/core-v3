<?php 
// header ----------------------------------------------------------------------
function the_page_title ($echo=true){
    $sitename       = _page('sitename', '');
    $title          = _page('title', '');
    $separator      = _page('title_separator', ' | ');
    $count          = _page('title_count', '');
    $template_count = _page('title_count_templace', ' ({{ count }}) ');
    $template       = _page('title_template', '{{ count }}{{ title }}{{ separator }}{{ sitename }}');
	
	// skip duplication of the name
	if ($title === $sitename){
		$title = '';
	}
	
	$site_count = $count !== '' ? string_replace($template_count, array('count'=>$count)) : '';
	$site_title = string_replace($template, array(
		'sitename'   => $sitename,
		'separator'  => $sitename && $title ? $separator : '',
		'title'		 => $title,
		'count'		 => $site_count,
	));

	$site_title = strip_tags($site_title);
	$site_title = trim($site_title);

    add_scripts(array(
        'page_sitename'       => $sitename,
        'page_title'          => $title,
        'page_count'          => $count,
        'page_separator'      => $separator,
        'page_template'       => $template,
        'page_template_count' => $template_count,
    ), true);

    set_page('page_title', $site_title);

    $site_title = "<title>{$site_title}</title>";

    if ($echo){
        echo $site_title;
    }

    return $site_title;
}

function the_head ($args=array(), $view=null){
    if ($args === false){
		$args = array('tag'=>$args);
	}

    $args = to_args($args, array(
		'tag'     => 'head',
		'styles'  => null,
		'scripts' => null,
		'view'    => $view ? $view : '@theme/head.php',
	), 'view');

    $version = _config('version', '1.0');

    if ($args['tag']) echo NL . '<'.$args['tag'].'>';
    
    do_action('the_head-start');
    
    // metas -------------------------------------------------------------------
    $url              = get_url();
    $title            = the_page_title(false);
	$base             = _page('base', false);
	$base             = $base === true ? get_host('', 'port=1') : $base;
	$charset          = _page('charset', 'utf-8');
    // $viewport         = _page('viewport', 'width=device-width, user-scalable=no');
    $viewport         = _page('viewport', 'width=device-width');
	$handheld         = _page('handheld', true);
	$mobile           = _page('mobile', 320);
    $indexed          = _page('indexed', is_live());
	$description      = _page('description', '');
    $description      = strip_tags($description);
	
	$metas = array();

	if (!$indexed)	$metas[] = '<meta name="robots" content="noindex,follow" />';
	if ($base)		$metas[] = '<base href="' . $base . '" />';
	if ($charset) 	$metas[] = '<meta charset="' . $charset . '" />';
	if ($handheld) 	$metas[] = '<meta name="HandheldFriendly" charset="True" />';
	if ($mobile)	$metas[] = '<meta name="MobileOptimized" charset="' . $mobile . '" />';
	if ($viewport)  $metas[] = '<meta name="viewport" content="'.$viewport.'" />';
	
	$metas[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
	$metas[] = '<meta name="format-detection" content="telephone=no" />';

    if ($title){
	    $metas[] = $title;
    }
    if ($description){
	    $metas[] = '<meta name="description" content="' . $description . '" />';
    }

    $metas = string_tab($metas, 1);
    echo $metas;

    do_action('the_head-meta');

	// scripts -----------------------------------------------------------------
    $settings = _page('theme_settings', array());
    $settings = array_merge(array(
        'version'      => $version,
        'is_dev'       => is_dev(),
        'is_debug'     => is_debug(),
        'is_logged_in' => is_logged_in(),
        // urls ----------------------------------------------------------------
        'url'       => get_url(), //$args['urls']['root'],
        'home_url'  => get_home_url(),
        // assets --------------------------------------------------------------
        'folder'    => _config('folder'),
        'images'    => _config('urls.images'),
        'scripts'   => _config('urls.scripts'),
        'styles'    => _config('urls.styles'),
        // other ---------------------------------------------------------------
        'cookie_prefix' => _config('cookie_prefix'),
    ), $settings);
    
    add_scripts($settings, true);
    
    $strings = get_string(null);
    add_scripts($strings, array(
        'name'  => 'theme_strings',
        'order' => 'before',
        'footer'=> false,
    ));

    if ($args['scripts'] !== null){
        add_scripts($args['scripts'], [
            'dir' => '@scripts'
        ]);
    }else{
        the_assets('script', function ($asset){ return !$asset['footer']; });
    }

    do_action('the_head-scripts');

	// styles ------------------------------------------------------------------
    if ($args['styles'] !== null){
        the_assets($args['styles'], [
            'dir' => '@styles'
        ]);
    }else{
        the_assets('style');
    }

    do_action('the_head-styles');

    if ($args['view']){
		echo NL . to_content($args['view'], 'file=1&alias=head') . NL . NL;
	}

    do_action('the_head-end');

    if ($args['tag']) echo '</'.$args['tag'].'>' . NL;
}

// Will add the custom CSS to the top of the page
add_filter('the_css-html', function ($html, $css, $slug){
    add_styles([
        'value' => $css,
        'name'  => $slug,
        'order' => 'start',
    ]);
    return '';
});

// footer ----------------------------------------------------------------------
function the_foot ($args='', $view=null){
    if ($args === false){
		$args = array('tag'=>$args);
	}

    $args = to_args($args, array(
		'tag'  => 'div',
		'view' => $view ? $view : '@theme/foot.php',
	), 'view');

    if ($args['tag']) echo NL . '<'.$args['tag'].'>';
    
    do_action('the_foot-start');

    // scripts -----------------------------------------------------------------
    the_assets('script', function ($asset){
        return !!$asset['footer'];
    });

    do_action('the_foot-scripts');

    // view --------------------------------------------------------------------
    if ($args['view']){
        echo to_content($args['view'], 'file=1&alias=foot');
	}

    // debug -------------------------------------------------------------------
    if (is_debug()){
        ?>
        <div class="debug--notification">Debugging</div>
        <?php 
    }
    if (is_logged_in() || is_dev()){
        // to_stats(true);
        // to_logs(true);

        $show_columns     = false;
        $show_breakpoints = false;
        
        if ($show_columns)      echo '<div debug="columns" class="debug--columns"></div>';
        if ($show_breakpoints)  echo '<div debug="breakpoints" class="debug--breakpoints"></div>';        
	}

    do_action('the_foot-end');

    if ($args['tag']) echo '</'.$args['tag'].'>' . NL;
}

// password --------------------------------------------------------------------
function the_password_form ($args=null, $echo=true){
    $args = _args($args, array(
        'alias'     => 'form-password',
        'username'  => false,           // should we consider the username
        'redirect'  => false,
        'echo'      => $echo,
    ), 'redirect');

    $username     = '';
    $is_protected = _config('password') || _config('passwords');
    $is_valid     = is_logged_in() ? true : null;
    if (!is_logged_in() && is_post()){
        $username = $args['username'] ? _post('username') : null;
        $password = _post('password');
        $is_valid = login($username, $password);
        $args['redirect'] && $is_valid && redirect($args['redirect']);
    }

    $html = $is_protected && !$is_valid ? to_html(function () use ($is_valid, $username, $args){ 
        ?>
        <form class="&" method="POST">
            <div class="&--inputs">
                <?php if ($args['username']): ?>
                    <input type="text" name="username" value="<?php echo $username; ?>" />
                <?php endif; ?>
                <input type="password" name="password" value="" />
            </div>
            <footer class="&--footer">
                <button type="submit">go</button>
            </footer>        
            <?php if ($is_valid === false): ?>
            <div class="&--error"><?php _t('Wrong password'); ?></div>
            <?php endif; ?>
        </form>
        <?php 
    }, ['alias'=>$args['alias']]) : '';

    if ($args['echo']){
        echo $html;
    }

    return $html;
}

// filters ---------------------------------------------------------------------
add_filter('the_menu-items', function ($items, $args){
    $all = array();

    foreach ($items as $path => $item){
        // the path is a @route that should be fetched
        if (strpos($path, '@') === 0){
            // TODO add "@admin" for only the admin routes

            $item = _args($item, [
                'dir'          => $path,
                'deep'         => true,
                // 'exclude_path' => '/api/',
                'tree'         => true,
                'meta'         => true,
            ]);

            $files = each_files('*', $item, function ($file){ 
                if (
                    ($file['is_file'] && $file['ext'] !== 'php') ||
                    _get($file, 'meta.menu') === false
                ){
                    return false;
                }
                
                return array(
                    'title' => $file['title'],
                    'url'   => $file['type'] === 'directory' ? false : $file['route'],
                );
            });

            $all = array_merge($all, $files);
        }else{
            $all = array_merge($all, array(
                $path => $item,
            ));
        }
    }

    return $all;
});

add_filter('the_page-html', function ($html, $page){
    if (class_exists('Parsedown') && isset($page['meta']['parser']) && $page['meta']['parser'] === 'markdown'){
		$markdown = new Parsedown();
		$html     = $markdown->text($html);
	}
    return $html;
});
