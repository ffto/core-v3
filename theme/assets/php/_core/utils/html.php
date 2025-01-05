<?php 
function attr ($attr, $echo=false){
	if (is_string($attr) || is_numeric($attr)){
		$attr = htmlentities($attr);
		$attr = str_replace('&amp;', '&', $attr);	// keep the "&" the same
	}
	if ($echo){
		echo $attr;
	}
	return $attr;
}

function default_unit ($value, $unit='px'){
    if (!$value) return '';

    $value = (string)$value;

    if (!preg_match('/((-?[0-9]+(\.[0-9]+)?)[^0-9.]+)/', $value)){
        $value .= $unit;
    }
    
    return $value;
}

function to_style ($styles, $args=null){
    $args = _args($args, [
        'array' => false,
        'vars'  => false,
    ], 'array');

	$styles = is_array($styles) ? $styles : array($styles);
	$values = array();
	foreach ($styles as $i=>$v){
        if (!is_numeric($i) && is($v)){
            $values[$i] = $v;
        // sub-array
        }else if (is_array($v)){
            $values = array_merge($values, $v);
        // string of css attribute
        }else if (is_string($v) && strpos($v, ':') !== false && strpos($v, ';') !== false){
            $v      = string_decode($v, 'style');
            $values = array_merge($values, $v);
        }
	}

    // transform the values as css vars()
    if ($args['vars']){
        $values = array_each($values, function ($v, $i){ return [
            '$id'    => "--{$i}",
            '$value' => strpos($v, 'http') !== false ? "url({$v})" : $v,
        ]; });
    }
    
    $values = array_each($values, function ($v) use ($args){
        return is($v) ? (is_string($v)?trim($v):$v) : null;
    }, ['parse'=>false]);
        
    if ($args['array']){
        return $values;
    }else if (count($values)){        
        $styles = [];
        foreach ($values as $i => $v){
            $styles[] = "{$i}:{$v};";
        }
        return implode(' ', $styles);        
    }

    return '';
}

function to_classnames ($classnames, $is_array=false){
	$classnames = is_array($classnames) ? $classnames : array($classnames);
	$values     = array();
	foreach ($classnames as $i=>$v){
        if (is_string($v)){
			$v = explode(' ', $v);
		}        
        if (is_array($v)){
            $values = array_merge($values, $v);
		}
	}

    $values = array_flatten($values);
	$values = array_filter($values);
	$values = array_unique($values);
    $values = array_values($values);

	return $is_array ? $values : implode(' ', $values);
}

function to_attributes ($attrs, $args=null){
    if ($args === true || $args === ':tag'){
        $args = array('return' => 'tag');
    }else if ($args === ':object'){
        $args = array('return' => 'object');
    }

    $args = to_args($args, array(
        'tag'    => 'div',
        'prefix' => ' ',
        'skip'   => array(),
        'return' => 'html',
    ), 'prefix');

    $skip   = to_array($args['skip']);
	$attrs  = is_array($attrs) ? $attrs : selector_to_attrs($attrs);
    $values = array();
	
    if (isset($attrs['tag'])){
        $args['tag'] = $attrs['tag'];
        unset($attrs['tag']);
    }

    foreach ($attrs as $i => $v){
        if ($i === 'style' || $i === 'styles'){
            $values['style'] = isset($values['style']) ? $values['style'] : array();
            $values['style'] = to_style(array($values['style'], $v), true);
        }else if ($i === 'class' || $i === 'classname' || $i === 'classnames'){
            // this has a special case, if the class is an array, it merge itself with the previous value, if it's a string, it replaces it
            if (is_array($v)){
                $old             = isset($values['class']) ? $values['class'] : [];
                $old             = is_array($old) ? $old : [$old];
                $v               = array_merge($old, $v);
                $v               = array_filter($v);
                $v               = array_unique($v);
                $values['class'] = $v;
            }else{
                $values['class'] = (string)$v;    
            }
        }else if ($i === 'data' && is_array($v)){
            foreach ($v as $ii => $vv){
                $ii = to_slug($ii);
                $values["data-{$ii}"] = $vv; //to_string($vv);
            }
        }else if (strpos($i, 'data-') === 0){
            $values[$i] = $v;
        }else if (is_string($v) && strpos($v, '="') !== false){
            // TODO replace this batch of code, it's breaking code (for ex: a data attribute with "[href="#terms"]" value)
            $v      = string_decode($v, 'attrs');
            $values = array_merge($values, $v);
        }else if (is_array($v)){
            $values = array_merge($values, $v);
        }else if (!is_numeric($i) && is($v)){
            $values[$i] = $v;
        }
	}

    $values = array_filter($values, function ($v) use ($skip){
        return is($v) && !in_array($v, $skip);
    });

    if (return_object($args['return'])){
        return $values;
    } 
    
    $html = '';
    if (count($values)){
        $attrs = [];
        foreach ($values as $i => $v){
            if ($v === true){
                $attrs[] = $i;
            }else{
                if (is_string($v)){
                    $v = trim($v);
                }else if ($i === 'style' && is_array($v)){
                    $v = to_string($v, 'join=; &pair=:');
                }else if ($i === 'class' && is_array($v)){
                    $v = implode(' ', $v);
                }else{
                    $v = json_encode($v);
                }
                $v       = attr($v);
                $attrs[] = "{$i}=\"{$v}\"";
            }
        }
        $html = count($attrs) ? $args['prefix'] . implode(' ', $attrs) : '';
    }

    if ($args['return'] === 'tag'){
        $tag    = $args['tag'];
        $open   = '<' . $tag . $html . '>';
        $close  = '</' . $tag . '>';
        $html   = ['open'=>$open, 'close'=>$close];
    }
    
    return $html;
}

function selector_to_attrs ($str, $defaults=null, $return_object=true){
    if (!$str && !$return_object){
        return null;
    }

    if (!is_string($str)){
        $selector = is_array($str) ? $str : array();
        $selector = array_merge(array(
            'tag'   => null,
            'attrs' => array(),
            'class' => array(),
            'style' => array(),
            'html'  => null,
        ), $selector);
    }else{
        // $selector = $args['alias'] ? str_replace('&', $args['alias'], $str) : $str;
        $selector = to_match($str, '/((?:\#[a-z][\w-]*)|(?:\.[\-a-z&][\w-]*)|(?:\[[^\]]+\])|(?:\{[^\]]+\})|(?:\=.+)|(?:[a-z][\w-]*))/i', 'multiple=1');

        // $selector = to_match($str, '/((?:\#[a-z][\w-]*)|(?:\.[\-a-z&][\w\-\{\}\s]*)|(?:\[[^\]]+\])|(?:\{[^\]]+\})|(?:\=.+)|(?:[a-z][\w-]*))/i', 'multiple=1');
        //eg.: div#id.classname.classname2[alt=image&title=title]{border:1px solid black}=the content

        $tag		= null;
        $attrs      = array();
        $style      = array();
        $classnames = array();
        $html 		= null;

        foreach ($selector as $v){
            $prefix = $v[0];
            
            // styles: {color:red, fontSize:14}
            if ($prefix === '{'){
                $v     = string_decode($v, 'css');
                $style = array_merge($style, $v);
            // attrs: [id=item, alt=image]
            }else if ($prefix === '['){
                $v     = string_decode($v, 'attributes');
                $attrs = array_merge($attrs, $v);
            // id: #itemId
            }else if ($prefix === '#'){
                $attrs['id'] = substr($v, 1);
            // classname: .class
            }else if ($prefix === '.'){
                $classnames[] = substr($v, 1);
            // html: =This is the html
            }else if ($prefix === '='){
                $html = substr($v, 1);
            // tag: div
            }else{
                $tag = $v;
            }
        }

        $selector = array(
            'tag'   => $tag,
            'attrs' => $attrs,
            'class' => $classnames,
            'style' => $style,
            'html'  => $html,
        );
    }

    // Make sure classnames are an array
    if (isset($selector['class']) && is_string($selector['class'])){
        $selector['class'] = explode(' ', $selector['class']);
    }

    if (!empty($defaults)){
        if (array_key_exists('class', $defaults)){
            $defaults['class'] = array_flatten($defaults['class']);
        }
        $selector = array_join([$defaults, $selector]);
    }

	return $selector;
}

function OLD__to_html ($tag, $attrs=null, $args='', $depth=0){
    // turn the tag into a selector item
    if (is_string($tag) && preg_match('/[^a-z_-]/', $tag)){
        $tag = selector_to_attrs($tag);
    }
    // tag is an html function
    if (is_callback($tag)){
        $html  = __content($tag);
        $args  = $attrs;
        $attrs = array('html'=>$html);
        $tag   = null;
    }
    // $attrs is an html function
    if ($html = __content($attrs)){
        $attrs = array('html'=>$html);
    }

    if (is_array($tag)){
        $attrs = array_join([$tag, $attrs], 'html');
        $tag   = null;
    }
    if ($args === true){
        $args = array('return' => 'object');
    }

    $el = _args($attrs, array(
		'tag'	=> 'div',
		'style'	=> array(),
		'class'	=> array(),
		'attrs' => array(),
		'html'	=> '',
	));


    $args = _args($args, [
        'exclude' => '',
        'alias'   => '',
        'tabs'    => true,
        'return'  => '', // [html, object OR true]
        'parser'  => null,
    ], 'alias');

    $tag       = $tag !== null ? $tag : (isset($attrs['tag']) ? $attrs['tag'] : null);
    $tag       = $tag === true ? 'div' : $tag;
    $attrs     = $el['attrs'];
    // $attrs2    = isset($attrs['attrs']) ? $attrs['attrs'] : [];
    // $attrs     = array_merge($attrs1, $attrs2);
    $html      = isset($el['html']) ? $el['html'] : '';
    $alias     = isset($el['alias']) ? $el['alias'] : $args['alias'];
    $data      = isset($el['data']) ? $el['data'] : null;
    $exclude   = to_array($args['exclude']);
    $is_object = return_object($args['return']);

    $alias_prefix = '&';
    if (is_array($alias)){
        $alias_prefix = array_keys($alias);
        $alias_prefix = reset($alias_prefix);
        $alias        = $alias[$alias_prefix];
    }

    $classnames = to_classnames([
        isset($attrs['classname']) ? $attrs['classname'] : null,
        isset($attrs['class']) ? $attrs['class'] : null,
        isset($el['classname']) ? $el['classname'] : null,
        isset($el['class']) ? $el['class'] : null,
    ], $is_object);

    $style = to_style([
        isset($attrs['style']) ? $attrs['style'] : null,
        isset($el['style']) ? $el['style'] : null,
    ], $is_object);

    if (isset($attrs['html'])){
        $html = $attrs['html'];
    }

    if (is_string($data)){
        $attrs['data'] = $data;
    }else if (is_array($data)){
        foreach ($data as $i => $v){
            $key         = "data-{$i}";
            $value       = to_string($v);
            $attrs[$key] = attr($value);
        }
    }

    // json data ---------------------------------------------------------------
    $json_setup   = isset($el['json/setup']) ? to_json_script($el['json/setup'], ['type'=>'json/setup']) : '';
    $json_props   = isset($el['json/props']) ? to_json_script($el['json/props'], ['type'=>'json/props']) : '';
    $json_data    = isset($el['json/data']) ? to_json_script($el['json/data'], ['type'=>'json/data']) : '';
    $json_strings = isset($el['json/strings']) ? to_json_script($el['json/strings'], ['type'=>'json/strings']) : '';

    unset($el['tag']);
    unset($el['style']);
    unset($el['class']);
    unset($el['classname']);
    unset($el['attrs']);
    unset($el['html']);
    unset($el['alias']);
    unset($el['data']);
    unset($el['json/setup']);
    unset($el['json/props']);
    unset($el['json/data']);
    unset($attrs['html']);
    unset($attrs['style']);
    unset($attrs['class']);
    unset($attrs['attrs']);
    unset($attrs['classname']);

    if ($is_object){
        $attrs = to_attributes([
            $el,
            $attrs, 
        ], 'return=object');    
    }else{
        $attrs = to_attributes([
            $el,
            $attrs, 
            !empty($classnames) ? ['class'=>$classnames] : null, 
            !empty($style) ? ['style'=>$style] : null
        ]);
    }

    // exclude some attributes
    if (count($exclude)){
        $attrs = array_filter($attrs, function ($v) use ($exclude){
            return !is_match($v, $exclude);
        });
    }

    // children ----------------------------------------------------------------
    $children = array();

    if ($json_setup){
        $children[] = $json_setup;
    }
    if ($json_props){
        $children[] = $json_props;
    }
    if ($json_strings){
        $children[] = $json_strings;
    }

    if (is_callback($html)){
        $children[] = _apply($html);
    }else if (arr_is_obj($html)){
		$children[] = to_html($html);
	}else if (is_array($html)){
        foreach ($html as $i=>$v){
            if (!is($v)) continue;
            if (is_string($v)){
                $children[] = $v;
            }else{
                $children[] = to_html($v, null, null, $depth+1);
            }
		}
	}else if (is($html)){
        $children[] = $html;
    }
    
    if ($json_data){
        $children[] = $json_data;
    }

    $children = array_filter($children);
    
    // html --------------------------------------------------------------------
    $html = (count($children) > 1 ? NL : '') . implode(NL, $children) . (count($children) > 1 ? NL : '');

    $args['parser'] && ($html = _filters('to_html-html', $html, $args['parser']));

    // output ------------------------------------------------------------------
    if ($is_object){
        $html = array(
            'tag'      => $tag,
            'attrs'    => $attrs,
            'style'    => $style,
            'class'    => $classnames,
            'html'     => $html,
            'children' => $children,
        );
    }else{
        // $html = (count($children) > 1 ? NL : '') . implode(NL, $children) . (count($children) > 1 ? NL : '');

        $is_void = in_array($tag, array('area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr'));
        if ($tag){
            if ($is_void && !$html){
                $html = "<{$tag}{$attrs} />";
            }else{
                $html = trim($html) ? $html : '';
                $html = "<{$tag}{$attrs}>{$html}</{$tag}>";
            }
        }

        if ($alias){
            $html = replace_alias($html, $alias, array(
                'prefix' => $alias_prefix,
            ));
        }
    }

    return $html;
}

function to_html ($tag, $attrs=null, $args=null, $depth=0){
    // if the tag starts with ":", it's a variable in "replace"
    if (is_string($tag) && strpos($tag, ':') === 0){
        $tag = ['html'=>$tag];
    // turn the tag into a selector item
    }else if (is_string($tag) && preg_match('/[^a-z_-]/', $tag)){
        $tag = selector_to_attrs($tag);
    // tag is a callback that will return the HTML
    }else if (is_callback($tag)){
        $tag = ['html'=>__content($tag)];
    }else if (!is_array($tag)){
        $tag = ['tag'=>$tag];
    }
    
    if (is_string($attrs) && strpos($attrs, ':') === 0){
        $attrs = ['html'=>$attrs];
    // $attrs is a callback that will return
    }else if (is_callback($attrs)){
        $attrs = ['html'=>__content($attrs)];
    // get all the attributes into an array (if it's a CSS )
    }else if (is_string($attrs) && strpos($attrs, '="') !== false){
        $attrs = to_attributes($attrs, ':object');
    // it's html
    }else if (!is_array($attrs)){
        $attrs = ['html'=>$attrs];
    }

    // args --------------------------------------------------------------------
    if ($args === true){
        $args = ['return'=>true];
    }

    $args = _args($args, [
        'exclude' => '',    // exclude some attributes
        'alias'   => '',    //
        'tabs'    => true,
        'parser'  => null,
        'replace' => [],
        'return'  => '',    // [html, object OR true]
    ], 'alias');
    
    $is_object = return_object($args['return']);

    // helper functions --------------------------------------------------------
    // simplify the attrs (by moving the $attrs children to the root)
    $simplify = function ($v){
        $a = isset($v['attrs']) ? $v['attrs'] : [];
        $v = array_join([$v, $a]);
        unset($v['attrs']);
        return $v;
    };
    $get = function ($v, $k, $f=null){
        return array_key_exists($k, $v) ? $v[$k] : $f;
    };
    $remove = function ($v, $keys=null){
        $keys = to_array($keys);
        foreach ($keys as $k){
            unset($v[$k]);
        }
        return $v;
    };
    
    // attributes --------------------------------------------------------------
    // merge them both together
    $tag     = $simplify($tag);
    $attrs   = $simplify($attrs);
    $attrs   = array_join([$tag, $attrs], 'html');

    $tag    = $get($attrs, 'tag', 'div');
    $tag    = $tag === true ? 'div' : $tag;
    $tag    = $tag ? strtolower($tag) : '';
    $class1 = $get($attrs, 'class', []);
    $class2 = $get($attrs, 'classname', []);
    $class3 = $get($attrs, 'classnames', []);
    $style  = $get($attrs, 'style', []);
    $data   = $get($attrs, 'data', []);
    $html   = $get($attrs, 'html', '');
    $alias  = $get($attrs, 'alias', $args['alias']);

    // alias, if the alias is an array, we can change the prefix (instead of "&"). 
    // For example, using "['@'=>'alias-name']" will search for "@" to replace
    $alias_prefix = '&';
    if (is_array($alias)){
        $alias_prefix = array_key_first($alias);
        $alias        = $alias[$alias_prefix];
    }

    $el = [
        'tag'          => $tag,
        'class'        => to_classnames([$class1, $class2, $class3], $is_object),
        'style'        => to_style($style, $is_object),
        'attrs'        => [],
        'data'         => [],
        'html'         => '',
        'json/props'   => $get($attrs, 'json/props'),
        'json/data'    => $get($attrs, 'json/data'),
        'json/setup'   => $get($attrs, 'json/setup'),
        'json/strings' => $get($attrs, 'json/strings'),
    ];

    // remove already parsed attributes
    $attrs = $remove($attrs, 'tag,class,classname,classnames,style,html,data,alias,json/props,json/data,json/setup,json/strings');

    // data attributes ---------------------------------------------------------
    if ($is_object){
        foreach ($attrs as $i => $v){
            if (strpos($i, 'data-') !== 0) continue;
            unset($attrs[$i]);
            $i        = str_replace('data-', '', to_slug($i, 'camel'));
            $data[$i] = to_value($v);
        }
        $el['data'] = $data;
    // merge the data to the $attrs
    }else{
        foreach ($data as $i => $v){
            $i                  = to_slug($i);
            $attrs["data-{$i}"] = to_string($v);
        }
    }

    // attributes --------------------------------------------------------------
    $el['attrs'] = $attrs;

    // TODO
    // // remove some tags
    // if ($exclude = $args['exclude'] && !empty($exclude)){
    //     $exclude = to_array($exclude);
    //     $attrs   = array_filter($attrs, function ($v, $i){
    //         
    //     });
    // }

    // html/children -----------------------------------------------------------
    $children = [];
    
    // top json data
    if ($el['json/setup'])   $children[] = to_json_script($el['json/setup'], ['type'=>'json/setup']);
    if ($el['json/props'])   $children[] = to_json_script($el['json/props'], ['type'=>'json/props']);
    if ($el['json/strings']) $children[] = to_json_script($el['json/setup'], ['type'=>'json/strings']);
    
    $html    = arr_is_list($html) ? $html : [$html];
    $replace = $args['replace'];
    foreach ($html as $i => $v){
        if (!is($v)) continue;

        if (is_string($v) && array_key_exists($v, $replace)){
            $children[] = $replace[$v];
        }else if (is_callback($v)){
            $children[] = _apply($v);
        }else if (arr_is_obj($v)){
            $children[] = to_html($v, null, ['replace'=>$replace], $depth + 1);
        }else if (is_array($v)){
            $children[] = to_html(null, ['html'=>$v], ['replace'=>$replace], $depth + 1);
        }else{
            $children[] = $v;
        }
    }

    // bottom json data
    if ($el['json/data']) $children[] = to_json_script($el['json/data'], ['type'=>'json/data']);

    // remove null childs
    $children = array_filter($children);
    
    // add tabs
    $join = NL . ($depth ? str_repeat(TAB, $depth) : '');
    $end  = NL . ($depth>1 ? str_repeat(TAB, $depth-1) : '');
    $html = (count($children) > 1 ? $join : '') . implode($join, $children) . (count($children) > 1 ? $end : '');

    // parser can re-format the html (good for Markdown parsing)
    $args['parser'] && ($html = _filters('to_html-html', $html, $args['parser']));

    $el['children'] = $children;
    $el['html']     = $html;

    // output ------------------------------------------------------------------
    if ($is_object){
        return $el;
    }
    
    $is_void = in_array($tag, ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
    
    $attrs['class'] = to_classnames($el['class']);
    $attrs['style'] = to_style($el['style']);
    $attrs          = to_attributes($attrs);
    
    if ($tag){
        if ($is_void && !$html){
            $html = "<{$tag}{$attrs} />";
        }else{
            $html = trim($html) ? $html : '';
            $html = "<{$tag}{$attrs}>{$html}</{$tag}>";
        }
    }

    if ($alias){
        $html = replace_alias($html, $alias, ['prefix'=>$alias_prefix]);
    }

    return $html;
}

function __html (){
    $html = call_user_func_array('to_html', func_get_args());
    return $html;
}

function _html (){
    $html = call_user_func_array('to_html', func_get_args());
    echo $html;
    return $html;
}

function the_css ($alias='', $css=null, $args=null){
    if ($css === null){
        $args  = $css;
        $css   = $alias;
        $alias = null;
    }
    
    if (is_bool($args)){
        $args = array('echo'=>$args);
    }

    $args = _args($args, array(
        'where'   => false,   // transform all selector into ":where(selector)"
        'vars'    => null,
        'once'    => false,   // for only showing this once
        'filters' => true,    // 
        'echo'    => true,
    ), 'echo', 'the_css-args');
    $slug = $alias ? to_slug($alias) : '';

    // only showing this CSS once
    if ($args['once'] && $slug || is_string($args['once'])){
        $key  = is_string($args['once']) ? $args['once'] : $slug;
        $once = "the_css-{$key}";
        if (_global($once)) return;
        set_global($once, true);
    }

    if (is_array($css)){
        $html = array();
        foreach ($css as $selector => $styles){
            $styles = to_style($styles);
            $html[] = "{$selector}{ {$styles} }";
        }
        $css = implode(NL, $html);
    }

    if (!empty($args['vars'])){
        $css = string_replace($css, $args['vars'], ['match'=>'$']);
    }

    if ($alias){
        $css = replace_alias($css, ".{$alias}", 'css');
    }

    // Adding :where() to the css
    if ($args['where']){
        $css = preg_replace_callback('/(.+)({.+?})/m', function ($m){
            $selector = trim($m[1]);
            $selector = explode(',', $selector);

            // match the selector and put the "::before/after" after the :where() wrapper
            $selector = array_map(function ($s){
                return preg_replace('/(.+)(:{2}(?:before|after))|(.+)/', ':where($1$3)$2', $s);
            }, $selector);

            $selector = implode(', ', $selector);
            $styles   = $m[2];
            return "$selector $styles";
        }, $css);
    }

    $css = preg_replace('/^[\s\t]*\/\/.+/m', '', $css);   // remove sinlge line comments
    $css = preg_replace('/^\t+/m', '', $css);             // remove the extra tabs
    $css = implode(NL, array_filter(explode(NL, $css)));  // remove the empty lines

    $html = '<style type="text/css" custom="content-css" '.($slug?' id="'.$slug.'-css"':'').'>'.NL.$css.NL.'</style>';
    $html = $args['filters'] ? _filters('the_css-html', $html, $css, $slug, $args) : $html;
    
    return e($html, $args['echo']);
}

// Elements --------------------------------------------------------------------
function to_wrap ($html, $tag='div', $args=null){
    if (is_bool($args)){
        $args = array('empty'=>$args);
    }else if (is_string($args) && !is_args($args)){
        $args = array('template'=>$args);
    }

    $args = to_args($args, array(
        'empty'    => false,
        'template' => null,
    ), 'template');

	if (!$html && !$args['empty']) return;
    $html         = $args['template'] ? to_string($html, array('template' => $args['template'])) : $html; 
	$data 		  = selector_to_attrs($tag, array('tag'=>'div'));
	$data['html'] = $html ? $html : $data['html'];
    
    return to_html($data);
}

function _wrap (){
    $args = func_get_args();
    $html = call_user_func_array('to_wrap', $args);

    return e($html);
}

function __wrap (){
    $args = func_get_args();
    return call_user_func_array('to_wrap', $args);
}

function to_url_aria_label ($url, $aria_label='', $default_label='', $target=null){
    $aria_label = $aria_label ? $aria_label : $default_label;
    
    if (is_external_url($url) || $target === '_blank'){
        $aria_label .= ' ' . _filters('get_link_aria_label-external-link', '(open in new window)');
    }

    // if it's the same thing, remove the aria-label
    if ($aria_label === $default_label){
        $aria_label = '';
    }

    return $aria_label;
}

function to_link ($link, $url=true, $args=''){
    if (is_array($url)){
        $args = $url;
        $url  = true;
    }
    
    $link = _filters('to_link-link', $link);
    if (is_array($link)){
        $link = to_args($link, [
            'label'  => null,
            'url'    => $url,
            'target' => null,
        ], 'label');
    }else{
        $link = [
            'label'  => $link,
            'url'    => $url,
            'target' => null,
        ];    
    }

    if ($args === true){
        $args = array('target' => '_blank');
    }

    $args = to_args($args, array(
		'tag'            => 'a',
		'tag_text'       => 'span',
        'label'          => null,       // overwrite the "link" label, good when passing an object to $link
        'default_label'  => null,
		'attrs'          => array(),
		'class'          => array(),
		'style'          => array(),
        'url'            => array(),
        'wrap'           => false,      // wrap the label
        'target'         => null,
        'short_url'      => false,     // remove the "https://www"
		'label_fallback' => '',        // default label if not set
	), null, 'to_link-args');
    
    $label  = $args['label'] ? $args['label'] : (string)_get($link, 'label,title,name,0');
    $label  = $label ? $label : $args['default_label'];
    $url    = _get($link, 'url,href,src,1', $url === true ? $label : $url);

    $target = $args['target'] ? $args['target'] : _get($link, 'target');
    $html   = array_copy($link, ['exclude'=>'label,title,url,href,src,target,0,1']);

    if (string_is_url($label)){
        $url = is_string($url) ? $url : $label;
    }

    $label = $label ? (string)$label : $args['label_fallback'];
    $url   = $url ? get_url($url, $args['url']) : '';

    if (!$label && !$url){
		return '';
	}

    // auto target
    if ($target === null && is_external_url($url)){
        $target = '_blank';
    }

    // remove the prefix for the text label
	if ($args['short_url']){
		$label = preg_replace('/^https?\:\/\/(www\.)?|\/$/', '', $label);
	}
    
    // aria label
    $aria_label = get_value($args['attrs'], 'aria-label', $label);
    $aria_label = to_url_aria_label($url, $aria_label, $label, $target);

    $label = apply_filters('ffto-translate', $label, $args);
    $label = apply_filters('to_link-label', $label, $url, $args);
	$url   = apply_filters('to_link-url', $url, $label, $args);

    // add a little <wrap> on the label
    if ($args['wrap'] && $label){
        $label = '<span>'.$label.'</span>';
    }

    $html = array_join([$html, $args['attrs'], [
        'tag'        => $url ? $args['tag'] : $args['tag_text'],
        'html'       => $label,
        'href'       => $url ? $url : false,
        'target'     => $target ? $target : false,
        'aria-label' => $aria_label,
        'class'      => $args['class'],
        'style'      => $args['style'],
    ]]);

    $html = _filters('to_link-html', $html, $args);
    $html = to_html($html);
    
    return $html;
}

/**
 * Shortcut to to_link and echo the resulting html.
 *
 * @return void
 */
function _link (){
    $params = func_get_args();
    $html   = call_user_func_array('to_link', $params);
    echo $html;
    return $html;
}

/**
 * Shortcut to to_link. Simply return the html.
 *
 * @return void
 */
function __link (){
    $params = func_get_args();
    return call_user_func_array('to_link', $params);
}

function the_skip_link ($args=null){
    $args = _args($args, [
        'target' => '#main',
        'alias'  => 'skip-to-content',
    ]);

    $args['alias'] && the_css($args['alias'], '
        :where(&)   { background:var(--skip-bg, white); border:1px solid var(--skip-color, black); color:var(--skip-color, black); position:absolute; top:0; left:0; margin:1em; padding:1em 1.5em; display:inline-block; z-index:9999; transform:translateY(-200%); transition:transform 0.3s; }
        &:focus     { transform:translateY(0%); },
    ');

    echo to_html('a', [
        'href'  => $args['target'],
        'class' => $args['alias'],
        'html'  => __t('Skip to content')
    ]);
}

// TODO remove this, to use "to_field_options()" in modules/form instead
function to_options ($label, $options, $args=null){
    $args = _args($args, [
        'name'             => to_slug($label, '_'),
        'group_id'         => to_slug($label, '_'),
        'values'           => [],
        'multiple'         => false,
        'placeholder'      => false,
        'placeholder_name' => false,
        'default'          => false,
        'value_key'        => null,
        'label_key'        => null,
	]);

    $type        = $args['multiple'] ? 'checkbox' : 'radio';
    $name        = $args['multiple'] ? "{$args['name']}[]" : $args['name'];
    $values      = to_array($args['values']);
    $selections  = [];
    $options = array_each($options, function ($v, $i) use ($args, $type, $name, $values, &$selections){
        if (is_object($v) || is_array($v)){
            $i = _get($v, $args['value_key'], $i);
            $v = _get($v, $args['label_key'], $v);
        }

        $is_selected = empty($values) ? $args['default'] === $i : in_array($i, $values);
        if ($is_selected){
            $selections[$i] = $v;
        }

        return [
            'value'    => $i,
            'label'    => $v,
            'selected' => $is_selected,
            'input'    => '<input group="'.$args['group_id'].'" label="'.attr($v).'" type="'.$type.'" name="'.$name.'" value="'.$i.'"'.($is_selected?' checked':'').' />',
        ];
    });

    $is_placeholder = false;
    if (empty($selections) && $args['placeholder']){
        $is_placeholder = true;
        $selections[] = $args['placeholder'];
    }

    $placeholder_name = $args['placeholder_name'] ? $args['placeholder_name'] : $args['name']; 

    return [
        'name'        => $args['name'],
        'group_id'    => $args['group_id'],
        'label'       => $label,
        'placeholder' => $args['placeholder'] ? [
            'label' => $args['placeholder'],
            'name'  => $placeholder_name,
            'input' => '<input group="'.$args['group_id'].'" group-placeholder label="'.attr($args['placeholder']).'" type="'.$type.'" name="'.$placeholder_name.'" value=""'.($is_placeholder?' checked':'').' />',
        ] : null,
        'multiple'    => $args['multiple'],
        'options'     => $options,
        'selections'  => $selections,
	];
}

// Assets ----------------------------------------------------------------------
function to_preload_asset ($href='', $args=null){
    $args = to_args($args, array(
		// 'host'			=> '',
		'type'			=> null,
		'as'			=> null,
        'defer'         => false,   // for css
		'crossorigin'	=> null,
        'version'       => null,
        'attrs'         => array(),
	));

    // version 
    $href = to_version($href, $args['version']);

    // type
    $type = $args['type'];
    if ($type === null){
        $mime = to_mime_type($href, true);    
        if ($mime['subtype'] === 'style')           $type = 'style';
        elseif ($mime['subtype'] === 'javascript')  $type = 'script';
        elseif ($mime['subtype'] === 'html')        $type = 'document';
        elseif ($mime['type'] === 'font')           $type = 'font';
        elseif ($mime['type'] === 'audio')          $type = 'audio';
        elseif ($mime['type'] === 'json')           $type = 'fetch';
        elseif ($mime['type'] === 'image')          $type = 'image';
        elseif ($mime['type'] === 'video')          $type = 'video';

        // auto-detect
        if (strpos($href, 'fonts.googleapis.com')){
            $type = 'style';
        }

        $type = _filters('to_preload_asset-type', $type, $mime, $href);
    }

    $crossorigin = $args['crossorigin'];
    if ($crossorigin === null && (
        $type === 'font'
    )){
        $crossorigin = true;
    }

    if (!is_external_url($href)){
        // TODO maybe remove the host in it?
    }

    $link = array(
        'tag'		  => 'link',
        'rel'         => 'preload',
        'href'        => $href,
        'as'          => $type,
        'crossorigin' => $crossorigin,
        'onload'	  => $type === 'style' && $args['defer'] ? "this.onload=null;this.rel='stylesheet'" : '',
    );

    $html = to_html($link);
    $html = _filters('to_preload_asset-html', $html, $link);

    return $html;
}

function to_asset ($asset, $args=''){
    $args = to_args($args, array(
        'type'    => 'link',
        'valid'   => null,
        'minify'  => _config('asset_minify'),
        'version' => _config('version'),
    ));
    
    $asset = to_args($asset, array(
        'type'  => $args['type'],
        'inline'=> false,
        'name'  => '',
        'attrs' => null,
        'value' => null,
    ), 'value');
    
    $url     = null;
    $data    = null;
    $version = $args['version'];
    $inline  = $asset['inline'];
    $type    = $asset['type'];

    $url = _get($asset, 'url,src,href');
    if ($url !== null){
        $asset['value'] = $url;
    }

    if (!$type && is_string($asset['value'])){
        $ext = pathinfo($asset['value'], PATHINFO_EXTENSION);
        if ($ext === 'css')     $type = 'style';
        else if ($ext == 'js')  $type = 'script';
    }

    $el = $asset['attrs'];

    $to_url = function ($v, $dir='') use (&$inline, $version){
        if (string_is_url($v, true)){
            return $v;
        }else if ($url = to_filepath($v, array('dir'=>$dir, 'return'=>($inline?'path':'url'), 'version'=>$version))){
            return $url;
        }else if (string_is_path($v)){
            return null;
        }
        return false;
    };

    
    $value = $asset['value'];
    $dir   = $type === 'script' ? '@scripts' : ($type === 'style' ? '@styles' : '');

    // TODO rewrite this function

    if (is_callback($value)){
        $asset['inline'] = $inline = true;
        $asset['footer'] = true;
        $data = __content($value);
    }else if (($url = $to_url($value, $dir)) === false){
        $data = $value;
    }
    

    $is_valid = ($data || $url) && apply($args['valid'], array($asset), array('fallback'=>true));
    if (!$is_valid) return;

    if ($type === 'script'){
        $el['tag']  = 'script';
        $el['type'] = 'text/javascript';
        
        $defer   = isset($el['defer']) ? $el['defer'] : false;
        $async   = isset($el['async']) ? $el['async'] : false;
        $module  = isset($el['module']) ? $el['module'] : false;
        $preload = isset($el['preload']) ? $el['preload'] : false;
        unset($el['defer']);
        unset($el['async']);
        unset($el['module']);
        unset($el['preload']);
        
        if ($asset['name']){
            $el['id'] = $asset['name'] . ($inline ? '-inline' : '') . '-js';
        }
            
        if ($url && $preload){
            $el['tag']         = 'link';
            $el['href']        = $url;
            $el['rel']         = 'preload';
            $el['as']          = 'script';
            $el['crossorigin'] = true;
        }else if ($inline && $url){
            $el['html'] = get_file($url, array('minify'=>$args['minify']));
        }else if ($url){
            $el['src']    = $url;
            $el['defer']  = !!$defer;
            $el['async']  = !!$async;
            $el['type']   = $module ? 'module' : null;
        }else if (is_array($data) && $asset['name']){
            $el['html'] = "var {$asset['name']} = " . to_string($data, true) . ';';
        }else if (is_array($data)){
            $el['html'] = array_each($data, function ($v, $i){
                return "window['{$i}'] = " . to_string($v, true) . ';';
            }, array('join' => NL));
        }else if ($data){
            $el['html'] = $data;
        }else{
            $el = null;
        }
    }else if ($type === 'style'){
        $el['tag'] = 'link';

        $defer   = isset($el['defer']) ? $el['defer'] : false;
        $media   = isset($el['media']) ? $el['media'] : false;
        $preload = isset($el['preload']) ? $el['preload'] : false;
        unset($el['defer']);
        unset($el['media']);
        unset($el['preload']);

        if ($asset['name']){
            $el['id'] = $asset['name'] . ($inline ? '-inline' : '') . ($defer ? '-defer' : '') . '-css';
        }

        if ($url && $preload){
            $el['href']        = $url;
            $el['rel']         = 'preload';
            $el['as']          = 'style';
            $el['crossorigin'] = true;
        }else if ($inline && $url){
            $el['tag'] = 'style';
            $el['html'] = get_file($url, array('minify'=>$args['minify']));
        }else if ($url){
            $el['rel']  = 'stylesheet';
            $el['media']= $media;
            $el['href'] = $url;

            if ($defer){
                $el['rel']    = 'preload';
                $el['as']     = 'style';
                $el['onload'] = 'this.onload=null;this.rel=\'stylesheet\'';
            }
        }else if ($data){
            $el['tag'] = 'style';
            $prefix    = $asset['name'] ? $asset['name'] : 'html';
            
            if (is_array($data)){
                $selectors = array();
                
                foreach ($data as $ii => $vv){
                    if (is_array($vv)){
                        $ii = "{$prefix} {$ii}";
                    }else{
                        $vv = array($ii => $vv);
                        $ii = $prefix;
                    }

                    $selectors[$ii] = isset($selectors[$ii]) ? $selectors[$ii] : array();
                    $selectors[$ii] = array_merge($selectors[$ii], $vv);
                }
                
                $el['html'] = array_each($selectors, function ($v, $i){
                    return $i . "{ " . to_style($v) . ' }';
                }, ['join'=>NL]);
            }else if ($data){
                $el['html'] = to_string($data);
            }
        }else{
            $el = null;
        }
    }else{
        // type links
    }

    return $el ? array_merge($asset, array(
        'name'    => get_value($asset, 'name'),
        'url'     => get_value($el, 'src,href'),
        'content' => get_value($el, 'html'),
        'html'    => to_html($el),
    )) : null;
}

// Element: View ---------------------------------------------------------------
define('VIEW_DATA', 'view_data');

function set_meta ($data='', $reset=false){
    if ($data === false){
        $data = array('tag'=>false);
    }

	$data = to_args($data, null, 'tag');
	$meta = $reset ? array() : get_global('meta', array());
	$meta = array_merge($meta, $data);
    // $meta = array_join([$meta, $data], ['null'=>true, 'filter'=>'html']);

	// alias / context / classname / attrs ...

	// make sure the alias doesnt override another item
	if (!isset($data['alias'])){
		// unset($meta['alias']);
	}

	set_global('meta', $meta);
	
	return $meta;
}

function get_meta ($key='', $fallback=null){
	$meta = get_global('meta', array());

    if ($key){
        return array_key_exists($key, $meta) ? $meta[$key] : $fallback;
    }

	return $meta;
}

function _meta (){
    $args = func_get_args();
    return call_user_func_array('set_meta', $args);
}

// function _var ($name=null, $fallback=null, $call=true){
function _var ($name=null, $fallback=null, $call=false){
    $data = get_global(VIEW_DATA, []);

    if ($name === null){
        return $data;
    }

    $data = isset($data[$name]) ? $data[$name] : $fallback; 
    $data = $call && is_callable($data) ? __content($data) : $data;

    return $data;
}

// [ ] Have a way to have vars from a parent with "&", but DO NOT touch it until it's back to the parent. Maybe using "\&" ... or some other code
function the_view ($path, $data='', $args=''){
	if (!($filepath = to_filepath($path))){
        return false;
    }

    if ($args === false){
        $args = array('echo'=>false);
    }else if ($args === true){
        $args = array('echo'=>false, 'return'=>true);
    }

    $data  = to_args($data);
    $alias = pathinfo($path, PATHINFO_FILENAME);
    $alias = to_slug($alias);
	
	$args = to_args($args, array(
        'tag'    => true,     // if string it's the tag
        'wrap'   => false,
        'alias'  => '',
        'context'=> '',
        'empty'  => false,
        'meta'   => true,
        'id'     => null,
        'anchor' => null,   // will add an anchor before the html IF the current view has no <tag>
        'class'  => null,
        'style'  => null,
        'data'   => array(),
        'attrs'  => null,
        'hidden' => false,
        'echo'   => true,
        'return' => null    // [html, meta, true (all)]
	), 'return');
    
    // fix external class
    $args['class'] = array_map(function ($c){
        return is_string($c) ? str_replace('&', KEY, $c) : $c;
    }, (array)$args['class']);

    // @add context (for strings), add "anchor" link option

    $old_meta = get_meta();

    set_meta(array(
        'alias'   => $args['alias'],
        'wrap'    => $args['wrap'], // ? selector_to_attrs($args['wrap']) : null,
        'context' => $args['context'],
    ), true);

    $old_in_view = get_global('in-view', false);
    $old_data    = get_global(VIEW_DATA);

    set_global('in-view', true);
    set_global(VIEW_DATA, $data);
    $response = to_content($filepath, array(
        'file' => true,
        'data' => $data
    ), false);
    set_global(VIEW_DATA, $old_data);

    $html = is_string($response) ? $response : null;
    $json = is_array($response) ? $response : null;
    
    is_array($args['meta']) && set_meta($args['meta']);
    $meta  = get_meta();

    $format= isset($meta['format']) ? $meta['format'] : null;
    $alias = $meta['alias'] === true ? $alias : $meta['alias']; // using true = the default alias, without any prefix (in the_item())
    $wrap  = $meta['wrap'];
    $empty = isset($meta['empty']) ? $meta['empty'] : null;
    
    unset($meta['context']);
    unset($meta['format']);
    unset($meta['alias']);
    unset($meta['wrap']);
    unset($meta['empty']);

    // html
    if (!$json){
        if ($args['tag'] && ($html || $args['empty'] || $empty)){
            $el = array_merge(array(
                'tag'   => is_string($args['tag']) ? $args['tag'] : 'div',
                'attrs' => array(),
                'class' => array(),
                'style' => array(),
                'data'  => array(),
                'id'    => $args['anchor'] ? $args['anchor'] : $args['id'],
                'hidden'=> $args['hidden'],
                'html'  => $html
            ), $meta);

            $data = is_array($el['data']) ? $el['data'] : array();
            $data = array_merge($data, $args['data']);
            
            $classnames = to_classnames(array(
                (array)$args['class'], 
                (array)$el['class'], 
                $alias,
            ), true);
            
            $style = to_style(array(
                (array)$args['style'], 
                (array)$el['style'],
            ));
            
            $el['class'] = $classnames;
            $el['data']  = $data;
            
            // $attrs1      = string_decode($args['attrs'], 'attrs');
            // $attrs2      = string_decode($el['attrs'], 'attrs');
            // $el['attrs'] = array_merge($attrs1, $attrs2);
            $el['style'] = $style;
            $el['attrs'] = array_merge((array)$args['attrs'], (array)$el['attrs']);
            $el          = apply($format, [$el]);
            $html        = to_html($el, null, ['skip'=>'context']);
            
            // wrap the element
            if (is_callback($wrap)){
                $html = __content($wrap, [$html]);
            }else if ($wrap){
                // TODO add "meta" wrap
                $wrap = selector_to_attrs($wrap, array('tag'=>'div'));
                
                // add the alias classnames
                $wrap['class'] = array_merge(
                    $wrap['class'], 
                    array_map(function ($v){ return "{$v}--wrap"; }, $classnames)
                );

                $wrap['html'] = $html;

                $html = to_html($wrap);
            }
        }

        if (is_string($html)){
            $html = str_replace('&&', KEY, $html); // for parents alias
            $html = $alias ? replace_alias($html, $alias) : $html;
            $html = str_replace(KEY, '&', $html);
        }else{
            $html = '';
        }

        // add anchor before the HTML (if there's no tag)
        $anchor = _get($meta, 'anchor');
        $anchor = $anchor !== null ? $anchor : $args['anchor'];
        if (isset($meta['tag']) && $meta['tag'] === false && $anchor){
            $html = '<a id="'.$anchor.'"></a>' . $html;
        }

        // output --------------------------------------------------------------
        if ($args['echo']){
            echo $html . NL;
        }
    }
    
    set_meta($old_meta, true);
    set_global('in-view', $old_in_view);

    if ($args['return'] === 'html'){
		return $html ? $html : '';
	}else if ($args['return'] === 'meta'){
		return $meta;
    }else if ($args['return'] === 'json'){
        return is_array($json) ? $json : array();
    }else if (return_object($args['return'])){
        return array(
			'html' => $html,
            'json' => $json,
			'meta' => $meta,
			'data' => $response,
		);
    }else{
        return $json ? $json : $html;
	}
}

function the_item ($slug, $data=null, $args=''){
    $alias = _config('alias_template', 'item-{{ slug }}');
    $alias = string_replace($alias, array(
        'slug' => preg_replace('/^_/', '', $slug)
    ));

    if ($args === false){
        $args = array('echo'=>false);
    }else if ($args === true){
        $args = array('echo'=>false, 'return'=>true);
    }

    $args = to_args($args, array(
		'dir'	=> '@items',
		'alias'	=> $alias,
	));

    $filepath = to_filepath([
		"{$args['dir']}/{$slug}",
		"{$args['dir']}/{$slug}.php",
		"{$slug}",
		"{$slug}.php",
	]);

    return the_view($filepath, $data, $args);
}

function _item (){
    $args = func_get_args();
    return call_user_func_array('the_item', $args);
}

function __item (){
    $args = func_get_args();
    
    ob_start();
    call_user_func_array('the_item', $args);
    $html = ob_get_clean();

    return $html;
}

// Return the class prefix (alias) of the current context
function __class ($classname){
    $prefix = '';
    if (_global('in-view')){
        $prefix = get_meta('alias');
    }else if (function_exists('get_page_classname')){
        $prefix = get_page_classname();
    }
    return $prefix ? str_replace('&', $prefix, $classname) : $classname;
}

// Scripts ---------------------------------------------------------------------
function to_json_script ($name, $props, $args=''){
	if (is_array($name)){
		$args  = $props;
		$props = $name;
		$name  = '';
	}

	if (empty($props)) return '';
	
	$args = to_args($args, [
		'type' => 'text/javascript',
	]);
	
	$prefix = $name ? "window.{$name} = " : '';
	$suffix = $name ? ";" : '';

    // make sure all props are camelCase, to work properly in Javascript
    $props  = array_each($props, function ($v, $i){
        return [
            // TODO to_slug here isn't good, since it replaces "_" underscore with camelCase ... shouldn't
            // '$id'    => to_slug($i, 'camel'),
            '$id'    => $i, //, 'camel'),
            '$value' => $v,
        ];
    });

	$html   = '<script type="'.$args['type'].'">' . $prefix . json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . $suffix . '</script>';
	
	return $html;
}

function the_json_setup ($props){
	echo to_json_script($props, ['type'=>'json/setup']);
}

function the_json_props ($props){
	echo to_json_script($props, ['type'=>'json/props']);
}

function the_json_data ($props, $echo=true){
    $json = to_json_script($props, ['type'=>'json/data']);
	if ($echo) echo $json;
    return $json;
}