<?php
function add_asset ($type, $asset=null, $args=''){
    if ((!$asset && !$args) || is_array($type)){
        $args  = $asset;
        $asset = $type;
        $type  = 'link';
    }

    // if (!in($type, 'link,script,style')){
    //     return err("Type '{$type}' is invalid");
    // }

    $args = to_args($args, array(
        'name'    => null,
        'attrs'   => null,
        'footer'  => null,
        'preload' => false,
        'order'   => null,
    ));
    
    $asset = to_args($asset, [
        'name'    => $args['name'],
        'value'   => $asset,
        'attrs'   => null,
        'preload' => $args['preload'],
        'footer'  => $args['footer'],
        'order'   => $args['order'],
        'priority'=> 0,                 // used before the order, for putting "theme_settings" before any <scripts>
    ], 'value');

    // try to find a name for the file
    if (!$asset['name'] && is_string($asset['value'])){
        $slug          = string_is_url($asset['value']) ? pathinfo($asset['value'], PATHINFO_FILENAME) : preg_replace('/\..+$/', '', $asset['value']);
        $slug          = to_slug($slug);
        $asset['name'] = $slug;
    }

    $asset['attrs'] = to_args($asset['attrs'], $args['attrs']);
   
    // all the assets 
    if ($type){
        $assets_key = "assets_" . $type;
        $assets     = get_global($assets_key, array());
        $assets     = to_array($assets, 'return=object');
        $key        = $asset['name'] ? $asset['name'] : count($assets);

        // merge value data
        if (isset($assets[$key]['value']) && is_array($assets[$key]['value']) && is_array($asset['value'])){
            $asset['value'] = array_merge($assets[$key]['value'], $asset['value']);
        }

        $assets[$key] = $asset;
        set_global($assets_key, $assets);
    }

    return $asset;
}

function add_assets ($type, $assets=null, $args=null){
    if ((!$assets && !$args) || (is_array($type))){
        $args   = $assets;
        $assets = $type;
        $type   = 'link';
    }

    $args = to_args($args, array(
        'dir'    => null,
        'output' => false,
    ));

    // if there's a "dir", the assets are parsed and tries to the get the proper paths
    if (arr_is_list($assets) && $args['dir']){
        $all = array();
        foreach ($assets as $path){
            if (string_is_url($path)){
                $all[] = $path;
            }else{
                $paths = each_files([$path], [
                    'dir'    => $args['dir'],
                    'return' => 'url',
                ]);
                $all = array_merge($all, $paths);
            }
        }
        $assets = $all;
    }
    
    $assets = to_array($assets, 'object=0');
    
    foreach ($assets as $i => $asset){
        $assets[$i] = add_asset($type, $asset, $args);
    }

    if ($args['output']){
        the_assets($type);
    }

    return $assets;
}

function add_scripts ($scripts=null, $args=null){
    if (is_array($scripts) && $args===true){
        $args    = array();
        $scripts = array(
            'name'  => _config('theme_script', 'theme_settings'),
            'value' => $scripts,
            'order' => ':start',
            'footer'=> false,
        );
    }

    $args = to_args($args, array(
        'attrs'  => array(),
        'defer'  => _config('scripts_defer', true),
        'async'  => false,
        'module' => false,
        'dir'    => '@scripts',
        'output' => false,
    ));

    $args['attrs'] = to_args($args['attrs'], [
        'defer'  => $args['defer'],
        'async'  => $args['async'],
        'module' => $args['module'],
    ]);

    add_assets('script', $scripts, $args);
}

function _scripts (){
    $args = func_get_args();
    call_user_func_array('add_scripts', $args);
}

function add_styles ($styles=null, $args=''){
    if (is_array($styles) && $args === true){
        $args = array(
            'name'  => _config('theme_style', 'theme_style'),
            'order' => 'before',
            'footer'=> false,
        );
    }

    $args = to_args($args, array(
        'attrs'  => array(),
        'media'  => false,
        'dir'    => '@styles',
        'output' => false,
    ));

    $args['attrs'] = to_args($args['attrs'], [
        'media' => $args['media'],
    ]);

    add_assets('style', $styles, $args);
}

function _styles (){
    $args = func_get_args();
    call_user_func_array('add_styles', $args);
}

// TODO update this
// function add_strings ($strings){
//     add_assets('script', $strings, array(
//         'name'  => _config('theme_strings', 'theme_strings'),
//         'order' => 'before',
//         'footer'=> false,
//     ));
// }

function the_assets ($type, $args=''){
    $args = to_args($args, array(
        'valid' => null,
    ), 'valid');

    if (is_array($type)){
        $assets = add_assets(null, $type, $args);
        $type   = isset($args['type']) ? $args['type'] : null;
    }else{
        $assets_key = "assets_" . $type;
        $assets     = get_global($assets_key, array());
        $assets     = to_array($assets);
    }

    $in_footer  = _config('scripts_footer', false);

    // format
    $elements = array();
    $count    = count($assets);
    foreach ($assets as $i => $asset){
        $asset = to_args($asset, array(), 'value');
        $order = $asset['order'];

        if ($order === ':start'){
            $order = -INF;
        }elseif ($order === 'start'){
            $order = $count * -1;
        }elseif ($order === 'end'){
            $order = $count;
        }elseif ($order === ':end'){
            $order = INF;
        }else if (is_null($order)){
            $order = $i;
        }

        $asset['order']  = $order;
        $asset['footer'] = is_bool($asset['footer']) ? $asset['footer'] : $in_footer;
        
        $asset = to_asset($asset, array(
            'type'  => $type,
            'valid' => $args['valid'],
        ));
        
        if (!$asset) continue;
        $elements[] = $asset;
    };
    
    $elements = array_sort($elements, 'order ASC, $id ASC');
    
    content_start();
    foreach ($elements as $el){
        echo $el['html'] . NL;
    }
    $html = content_end(1);

    echo $html;
}
