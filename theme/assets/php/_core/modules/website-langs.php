<?php
// Language -------------------------------------------------------------------- 
define('LANGS', 'website_langs');

function set_langs ($langs=array(), $args=''){
    $args = to_args($args, array(
		'query' => false,
    ), 'query');

    $data = get_global(LANGS, array(
        'default' => null,
        'current' => null,
        'langs'   => array()
    ));

    // load langs from the setting file
    $langs = is_array($langs) ? $langs : array();
    if ($langs2 = _setting('langs', array())){
        $langs = array_join(array($langs, $langs2));
    }

    foreach ($langs as $code => $lang){
        $lang = to_args($lang, array(
			'label'   => $lang,
			'active'  => true,
			'default' => false,
		), 'label');

        if (!$data['default'] || $lang['default']){
            $lang['default'] = true;
            $data['default'] = $code;
        }

        $data['langs'][$code] = $lang;
    }

    $code = null;
    if ($args['query']){
		$key  = is_string($args['query']) ? $args['query'] : 'lang';
        $code = _query($key);
        $args['query'] = $key;
	}else{
        $path = $_SERVER['REQUEST_URI'];
        $path = explode('/', $path);
        $code = isset($path[1]) ? $path[1] : '';
	}

    get_translations($code);
	
    $data['current'] = isset($data['langs'][$code]) ? $code : $data['default'];
    $data['codes']   = array_keys($data['langs']);
    $data['query']   = $args['query'];
    
    page_is_dirty(true);
    set_global(LANGS, $data);

    return $data['langs'];
}

function get_langs ($all=false){
    $data = get_global(LANGS, array(
        'langs'   => array(),
        'default' => null,
        'current' => null,
        'query'   => false,
    ));

    if ($all === null){
        return $data;
    }

    $langs = $all ? $data['langs'] : array_filter($data['langs'], function ($v){
        return $v['active'];
    });

    return $langs;
}

function set_lang ($code=true){
    $data = get_langs(null);
    $code = isset($data['langs'][$code]) ? $code : ($code === true ? $data['default'] : null);

    if ($code){
        get_translations($code);
        $data['current'] = $code;
        set_global(LANGS, $data);
    }
    
    return $data['current'];
}

function get_lang ($code=null, $info=false){
    $data = get_langs(null);
    
    if (!$code){
        $code = isset($data['current']) ? $data['current'] : _config('lang', 'global');
    }
    
	if ($code && $info && isset($data['langs'][$code])){
        return $data['langs'][$code];
    }else{
        return $code;
    }
}

function is_lang ($code){
	return get_lang() === $code;
}

function is_default_lang ($code=null){
    $data = get_langs(null);
    $code = $code ? $code : $data['current'];
    return $code === $data['default'];
}

function get_home_url ($code=null){
    $url = get_url('/', ['lang'=>$code]);
    $url = preg_replace('/\/$/', '', $url);
    return $url;
}

function get_site_url ($code=null){
    return get_url('/', ['lang'=>$code]);
}

function get_site_name (){
    return _page('sitename', '');
}

// add the "lang" to the get_url() calls
add_filter('get_url-args', function ($args){
    $data = get_langs(null);
    $lang = isset($args['lang']) && $args['lang'] ? $args['lang'] : $data['current'];
    
    if ($key = $data['query']){
        $args['query'][$key] = $lang !== $data['default'] ? $lang : null;
    }else{
        $re   = to_regexp('/' . $data['current'], false);
        $path = preg_replace("/^{$re}/" , '', $args['path']);
        $path = ($lang !== $data['default'] ? "/{$lang}/" : '') . $path;
        $path = clean_path($path);
        $args['path'] = $path;
    }
    
    return $args;
});

add_filter('to_link-args', function ($args){
    if (isset($args['lang'])){
        $args['url'] = to_args($args['url'], array(
            'lang' => $args['lang'],
        ));
    }
    return $args;
});

add_filter('the_languages-label', function ($label, $is_current){
    return __t('context=Languages', $label);
});

// Strings/Dictionary ----------------------------------------------------------
define('STRING_CONTEXT', 'string_context');
define('DICTIONARY_STRINGS', 'dictionary_strings');
define('DICTIONARY_TRANSLATIONS', 'dictionary_translations');

function get_dictionary_strings (){
    if (!($strings = get_global(DICTIONARY_STRINGS))){
        $strings = get_file('@data/strings.json');
        set_global(DICTIONARY_STRINGS, $strings);
    }
    return is_array($strings) ? $strings : array();
}

function set_dictionary_strings ($strings=array(), $save=null){
    $strings = is_array($strings) ? $strings : array();
    $current = get_dictionary_strings();

    if (empty($strings) && empty($current)) return;
    
    foreach ($strings as $ctx => $items){
        $current[$ctx] = isset($current[$ctx]) ? $current[$ctx] : array();
        // $current[$ctx] = array_join([$current[$ctx], $items]);
        $current[$ctx] = array_merge($current[$ctx], $items);
    }
    set_global(DICTIONARY_STRINGS, $current);
    
    if ($save){
        foreach ($current as $ctx => $items){
            $current[$ctx] = array_sort($items, 'order ASC');;
        }
        ksort($current, SORT_STRING | SORT_NATURAL | SORT_FLAG_CASE);
        set_file('@data/strings.json', $current);
    }
}

function add_dictionary_string ($key, $string){
    $strings        = get_dictionary_strings();
    $ctx            = $string['context'] ? $string['context'] : '*';
    $string['data'] = is_array($string['data']) ? array_values(array_keys($string['data'])) : null;

    unset($string['lang']);
    unset($string['context']);
    unset($string['save']);
    
    $strings[$ctx] = isset($strings[$ctx]) ? $strings[$ctx] : array();
    
    // TODO add comparing of data properly
    
    // skip if same stuff
    $old = isset($strings[$ctx][$key]) ? $strings[$ctx][$key] : null;

    if (
        $old
        && $old['order'] == $string['order']
        && $old['parent'] == $string['parent']
        && $old['type'] == $string['type']
        && $old['label'] == $string['label']
        && $old['fallback'] == $string['fallback']
        && is_match($old['data'], $string['data'])
    ) return;

    $strings[$ctx][$key] = $string;

    set_dictionary_strings($strings);
}

function get_translations ($lang=null){
    $lang= $lang ? $lang : get_lang();
    $key = DICTIONARY_TRANSLATIONS . '_' . $lang;
    if (!($translations = get_global($key))){
        $translations = get_file("@env/translations-{$lang}.json", array('fallback'=>array()));
        set_global($key, $translations);
    }
    return $translations;
}

function set_translations ($strings, $ctx=null, $lang=null){
    $ctx        = $ctx ? $ctx : '*';
    $lang       = $lang ? $lang : get_lang();
    $key        = DICTIONARY_TRANSLATIONS . '_' . $lang;
    $translations = get_translations($lang);
    
    foreach ($strings as $key => &$text){
        $text = encode_paths($text);
        $text = _filters('set_translations-text', $text, $key, $ctx, $lang);
    }

    $all = isset($translations[$ctx]) ? $translations[$ctx] : array();
    $all = array_merge($all, $strings);
    
    // $all = array_filter($all, function ($v){ return (string)$v !== ''; });

    $all = array_filter($all);
    $translations[$ctx] = $all;


    set_file("@env/translations-{$lang}.json", $translations);
    set_global($key, $translations);
}

function set_context ($ctx){
    set_global(STRING_CONTEXT, $ctx);
}

function get_context (){
    return get_global(STRING_CONTEXT);
}

// Translate -------------------------------------------------------------------
define('CONFIG_TRANSLATION_SAVE', 'translation_save');
define('CONFIG_TRANSLATION_OVERWRITE', 'translation_overwrite');

function translate ($args=null, $text=null, $data=null, $fallback=null){
    if (is_array($text) || $text === null || !is_args($args)){ //} (is_string($args) && !preg_match('/[a-z]\=[a-z0-9]/i', $args))){
        $fallback = $data;
        $data     = $text;
        $text     = $args;
        $args     = array();
    }
    if (is_callback($data) || is_string($data)){
        $fallback = $data;
        $data     = null;
    }
    $args = to_args($args, array(
		'context'  => null,                                 // add a special context (for organizing OR differentiate similar text)
		'lang'     => get_lang(),
		'order'    => to_counter('translate'),
		'parent'   => false,
		'type'     => null,
		'attr'     => false,                                // escape the text for an html attribute
		'html'     => null,                                 // accept html in the translation
		'nl2br'    => null,                                 // convert line-breaks to <br>
		'label'    => null,
		'fallback' => $fallback,
		'data'     => $data,                                // data to parse the text string
		'save'     => _config(CONFIG_TRANSLATION_SAVE, true),   // save the key/fallback in the strings
	), 'context');
    
    $args       = _filters('translate-args', $args);
    $fallback   = is_string($text) ? ($args['fallback'] !== null ? to_content($args['fallback'], 'file=0&url=0') : null) : null;

    // try finding the proper context
    if ($args['context'] === null){
        $ctx = get_meta('context');
        $ctx = isset($ctx) ? $ctx : get_page('context');
        $ctx = isset($ctx) ? $ctx : get_context();
        $args['context'] = $ctx;
    }
    if ($args['html'] === null){
        $args['html'] = (is_string($text) && !!preg_match('/\<[^\>]+\>/', $text)) || (is_string($fallback) && !!preg_match('/\<[^\>]+\>/', $fallback));
    }
    if ($args['nl2br'] === null && !$args['html']){
        $args['nl2br'] = (is_string($text) && strpos($text, NL) !== false) || (is_string($fallback) && strpos($fallback, NL));
    }

    $lang = $args['lang'] ? $args['lang'] : '*';
    $type = $args['type'] ? $args['type'] : ($args['html'] ? 'html' : ($args['nl2br'] ? 'textarea' : 'text'));

	if (is_string($text)){
        $ctx          = $args['context'] ? $args['context'] : '*';
        $key          = strip_tags($text);
        $key          = string_clean($key, true);
        $label        = strip_tags($args['label'] ? $args['label'] : $text);
        $translations = get_translations($lang);
     
        if (isset($translations[$ctx][$key]) && !is_null($translations[$ctx][$key])){
            $text = $translations[$ctx][$key];
            $text = decode_paths($text);
            $text = _filters('translate-text', $text, $key, $ctx, $lang);
        }else{
            $text = is_string($fallback) ? $fallback : $text;
            unset($translations[$ctx][$text]);
        }

        if ($args['save']){
            // TODO add code here to NOT resave the string if it's the same as the previous one (eg.: _config('translation_overwrite') )
            // $overwrite = _config(CONFIG_TRANSLATION_OVERWRITE, true);
            // TODO 
            
            $string             = $args;
            $string['type']     = $type;
            $string['label']    = $label;
            $string['fallback'] = $fallback;

            $trace               = to_backtrack(2);
            $string['file']      = $trace ? "{$trace['path']} [line {$trace['line']}]" : '';
            $string['timestamp'] = gmdate('Y-m-d H:i:s e');

            unset($string['attr']);
            unset($string['html']);
            unset($string['nl2br']);
            add_dictionary_string($key, $string);
        }
	// is a fixed list of translations
	}else if (isset($text[$lang])){
		$text = $text[$lang];
	}else{
		$text = '';
	}

    if (is_string($text)){
        $text = string_replace($text, $args['data']);

        if (!$args['html'] && $type !== 'html'){
            // clean the text to keep the "<" if it's not an html tag
            $text = preg_replace('/<[^a-z\/]/', KEY, $text);
            $text = strip_tags($text);
            $text = string_clean($text);
            $text = str_replace(KEY, '&lt;', $text);
            // add <br> line break
            $text = $args['nl2br'] ? nl2br($text) : $text;
        }
        
        if ($args['attr']){
            $text = to_attr($text);
        }
    }

	return $text;
}

function translate_list ($args=null, $name=null, $children=null){
    if (is_array($name) || (is_string($args) && !preg_match('/[a-z]\=[a-z0-9]/i', $args))){
        $children = $name;
        $name     = $args;
        $args     = array();
    }

    $args = to_args($args, array(
		'context'     => null,                               // add a special context (for organizing OR differentiate similar text)
        'single'      => false,
		'lang'		  => get_lang(),   
		'save'        => _config(CONFIG_TRANSLATION_SAVE, true), // save the key/fallback in the strings
	), 'context');

    $single = $args['single'];
    unset($args['single']);

    $args['type']     = 'list';
    $args['children'] = array_keys($children);
    
    $list_key  = $name;
    $values    = translate($args, $list_key);
    $values    = is_array($values) ? $values : array();

    if ($args['save']){
        array_each($children, function ($child, $i) use ($list_key, $args){
            $child = to_args($child, array(
                'fallback' => $i,
                'context'  => $args['context'],
                'lang'     => $args['lang'],
                'data'     => ['index' => 0],
                'parent'   => $list_key,
            ), 'label');
            return translate($child, "{$list_key}:{$i}");
        });
    }

    foreach ($values as $i => $v){
        foreach ($v as $ii => $vv){
            $child  = isset($children[$ii]) ? $children[$ii] : array();
            $child  = to_args($v, array(
                'context'  => $args['context'],
                'lang'     => $args['lang'],
                'data'     => ['index' => $i],
                'save'     => false,
            ), 'label');
            $v[$ii] = translate($child, $vv);
        }
        $values[$i] = $single ? reset($v) : $v;
    }

    return $values;
}

function __t (){
    $args = func_get_args();
    return call_user_func_array('translate', $args);
}

function _t (){
    $args = func_get_args();
    echo call_user_func_array('translate', $args);
}

// TODO maybe turn "translate_label" to true (if null) when adding Languages
add_filter('ffto-translate', function ($label, $args){
    $translate = isset($args['translate']) ? $args['translate'] : _config('translate_label', false);
    return $translate ? __t($label) : $label;
});

// add_filter('to_link-label', function ($label, $url, $args){
//     $translate = isset($args['translate']) ? $args['translate'] : true;
//     return $translate ? __t($label) : $label;
// });

register_shutdown_function(function (){
	set_dictionary_strings(null, true);
});