<?php 
add_filter('config-args', function ($args, $has_config){
    if ($has_config) return $args;

    $args = to_args($args, array(
        '@routes' => '@theme/routes',
        // paths ---------------------------------------------------------------
        // 'upload_dir'	=> $upload_dir,				
		// 'data_dir'      => '@upload/data/',				
		// 'cache_dir'     => '@upload/cache/',				
		// 'env_dir'		=> '@upload/data-',
        // 'styles_dir'	=> '@theme/',
		// 'theme_dir'		=> str_replace(ABSPATH, '', STYLESHEETPATH),
        // '@block'        => '@theme/template-blocks',
        // '@parent_theme' => get_template_directory(),    // parent theme
        // '@child_theme'  => get_stylesheet_directory(),  // child theme
        // '@languages'    => '@parent_theme/languages/',  // child theme
    ));

    $folder  = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME);
    $folder  = $folder === '/' ? '' : $folder;
    $args['folder'] = $folder;
   
    $includes         = (array)get_value($args, 'includes', array());
    $args['includes'] = array_merge(array(
        "theme.php",
		"theme/**/*.php",
    ), $includes);
    
    $on_config          = (array)get_value($args, 'on_config', null);
    $args['on_config']  = array_merge(array('init_config'), $on_config);

    return $args;
});

function init_config ($args){
    $args = to_args($args, array(
        'sitename'          => 'Website',
        'scripts'           => 'app.js',
        'styles'            => 'style.css',
        'lang'              => 'en',
        'langs'             => false,
        'gzip'              => true,
        // lang
        'translation_save' => !is_live(),   // do not save translations strings on LIVE server
    ));
    
    $args['langs'] && set_langs($args['langs']);
    
    add_scripts($args['scripts'], ['defer'=>false]);
    add_styles($args['styles']);
    
    set_page(array(
        'sitename' => $args['sitename'],
    ));

    $args['gzip'] && ($_SERVER['REQUEST_METHOD'] === 'GET') && ob_start("ob_gzhandler");

    // nothing is dirty yet
    page_is_dirty(false);

    return $args;
}

// at the end of the code, output everything (errors, ...)
register_shutdown_function(function (){
    if (is_ajax()){
        if (($info = to_site_logs('return=ajax')) && !headers_sent()){
            header('Content-Time: '.$info['time']);
            header('Content-Logs: '.$info['logs']);
        }
        return;
    }

    sync_version();

    if (
        _global('output_logs') === false 
        || _config('output_logs') === false 
        || !is_request_php_file()
        || (!is_logged_in() && !is_dev())
        || is_ajax()
    ) return;
    
    $user_id = get_user_id();

    _site([
        '☺ User' => $user_id ? $user_id : '-',
    ], true);

    // need to not output anything if the content is EMPTY or JSON
	to_site_meta(true);
	to_site_logs(true);
});