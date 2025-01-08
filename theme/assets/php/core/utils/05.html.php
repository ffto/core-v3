<?php 
/**
 * @package Utils\HTML
 */

/* =====================================================================================================================
Quick
===================================================================================================================== */
/**
 * Encode an attribute into a valid string, first it converts it with {@see _string()}
 * 
 * ```php
 * _attr(new DateTime());
 * // "2024-03-23 18:20:34 UTC"
 * 
 * _attr(['id'=>'house', 'class'=>'bob bob--dark']);
 * // '{"id":"house","class":"bob bob--dark"}'
 * ```
 *
 * @param mixed $attr
 * @param boolean $echo
 * @return string
 */
function _attr ($attr, $echo=false){
	$attr = _string($attr, 'pretty=0');
	$attr = htmlentities($attr);
	$attr = str_replace('&amp;', '&', $attr);	// keep the "&" the same
	return _echo($attr, $echo);
}

/**
 * Add a unit to a number.
 * 
 * ```php
 * _unit(44);
 * // 44px
 * 
 * _unit(44, '%');
 * // 44%
 * 
 * _unit('44em', '%');
 * // 44em
 * ```
 *
 * @param mixed $value
 * @param string $unit
 * @return string
 */
function _unit ($value, $unit='px'){
	if (ffto_is_not($value)) return '';

	$value = (string)$value;

	if (!preg_match('/((-?[0-9]+(\.[0-9]+)?)[^0-9.]+)/', $value)){
		$value .= $unit;
	}
	
	return $value;
}

/**
 * Check a css attribute and encode it properly
 *
 * ```php
 * _style('font-size', 45);
 * // "45px"
 * 
 * _style('background', 'https://project.com/');
 * // "url(https://project.com/)"
 * ```
 * 
 * @param string $name
 * @param mixed $value
 * @return string
 */
function _style ($name, $value){
	if (is_string($value) && strpos($value, 'http') === 0){
		$value = "url({$value})";
	}
	
	if (ffto_is_nbr($value) && in_array($name, [
		'top','right','bottom','left', 
		'height','width','min-height','max-height','min-width','max-width',
		'border-top-width', 'border-right-width','border-bottom-width','border-left-width','border-radius','border-spacing',
		'background-size', 'background-position',
		'margin-top','margin-right','margin-bottom','margin-left',
		'padding-top','padding-right','padding-bottom','padding-left',
		'font-size',
	])){
		$value = _unit($value);
	}

	return $value;
}

/**
 * Replace all aliases with their proper name,
 *
 * ```php
 * _alias('<div class="item &--item">John</div>', 'block', 'attrs');
 * // <div class="item block--item">John</div>
 * 
 * _alias('<div class="item &--item">John &--john</div>', 'block');
 * // <div class="item block--item">John block--john</div>
 * 
 * _alias('body.&--big{ color:red; }', 'parent', 'css');
 * // body.parent--big{ color:red; }
 * 
 * _alias('body.&--big.&&--super{ color:red; }', ['aliases' => [
 *     '&'  => 'child',
 *     '&&' => 'parent',
 * ]]);
 * // body.child--big.parent--super{ color:red; }
 * ``` 
 * 
 * @param mixed $str 
 * @param mixed $args 
 * 	- 'prefix' [&] alias prefix to replace with the new alias value
 * 	- 'alias' [''] the value
 * 	- 'aliases' [] more types of aliases to replace (eg.: '&&' would be a parent alias)
 * 	- 'skip' []	attributes to skip when the type is attrs 
 * 	- 'type' [$type] type of special parsing
 * 		- 'css' transform the selectors of CSS content
 * 		- 'attrs' transform only HTML attributes values
 * @param string $type type of parsing
 * @return string
 */
function _alias ($str, $args=null, $type=null){
	// there's times that the string passed is a function, so just fetch it's HTML content
	if (ffto_is_callback($str)){
		ob_start(); $str(); $str = ob_get_clean();
	}
	
	$args = _args($args, [
		'prefix'  => '&',
		'alias'   => '',
		'aliases' => [],      // other things to replace
		'skip'    => [],
		'type'    => $type,
	], 'alias');
	
	$aliases = ffto_to_pair($args['prefix'], $args['alias']);
	$aliases = array_merge($aliases, $args['aliases']);

	if (empty($aliases)) return $str;
	
	// order the aliases by their key lengths, when 2 are similar (eg.: "&" and "&&", the "&&" should be checked first)
	$keys = array_keys($aliases);
	array_multisort($keys, SORT_DESC, $aliases);

	$_replace = function ($text, $all=false) use ($aliases){
		foreach ($aliases as $i => $v){
			if ($all){
				$text = strtr($text, ["{$i}" => $v]);
			}else{
				$text = strtr($text, [
					"'{$i}"  => "'{$v}",	// start of a single-quoted text `'&--`
					"\"{$i}" => "\"{$v}",	// start of a double-quoted text `"&--`
					"{$i}'"  => "{$v}'",	// end of a single-quoted text `&--'`
					"{$i}\"" => "{$v}\"",	// end of a double-quoted text `&--"`
					" {$i}"  => " {$v}",	// with space before ` &--`
					"{$i} "  => "{$v} ",	// with space after `&-- `
					".{$i}"  => ".{$v}",	// class: `.&--` 
					"({$i}"  => "({$v}",	// in group: `(&--)`
					"{$i}{"  => "{$v}{",	// class prefix: `&--{`	
				]);
			}
		}
		return $text;
	};

	$type = $args['type'];
	if ($type === 'css'){
		// match all style blocks (eg.: "body{ ... }") and do a replace on the selector (eg.: body)
		$str = preg_replace_callback('/(?<selector>[^{]+)(?<style>{.+})/m', function ($m) use ($_replace){
			$selector = $_replace($m['selector'], true);
			$style 	  = $m['style'];
			return $selector.$style;
		}, $str);
	}else if ($type === 'attrs'){
		// skip the htmlentities (eg.: "&lt;", "&#039;")
		$str = preg_replace('/(?:\&((?:\#\d+)|(?:[a-z]+))\;)/i', KEY . '$1;', $str);

		// match all the [key="value"] attributes 
		$str = preg_replace_callback('/(((?<key>[a-z_-]+)=)(?<value>("[^"]*?")|(\'[^\']*?\'))|(("[^"\n]*?")|(\'[^\'\n]*?\')))/s', function ($m) use ($_replace, $args){
			$key   = $m['key'];
			$value = $m['value'];
			$attr  = $m[0];

			if (
				strpos($value, NL) !== false 		// there's a newline in the value, skip it then, we try to match only the single-line attributes
				|| in_array($key, $args['skip'])	// attribute to skip
			) return $attr;
			
			return $_replace($attr);
		}, $str);

		// re-add the htmlentities
		$str = str_replace(KEY, '&', $str);
	}else{
		$str = $_replace($str);
	}

	return $str;
}

/**
 * Shortcut to {@see ffto_to_html()}
 * @return mixed
 */
function __html (){
	$params = func_get_args();
	return call_user_func_array('ffto_to_html', $params);
}

/**
 * Print the results of calling {@see ffto_to_html()}
 * @return void
 */
function _html (){
	$params = func_get_args();
	echo call_user_func_array('ffto_to_html', $params);
}

/**
 * Shortcut to {@see ffto_to_item()}
 * @return mixed
 */
function __item (){
	$params = func_get_args();
	return call_user_func_array('ffto_to_item', $params);
}

/**
 * Print the results of calling {@see ffto_to_item()}
 * @return void
 */
function _item (){
	$params = func_get_args();
	echo call_user_func_array('ffto_to_item', $params);
}

/**
 * Shortcut to {@see ffto_set_meta()} and {@see ffto_get_meta()}
 * @return mixed
 */
function _meta ($key=null, $value=null, $save=false){
	if (is_array($key) || $save){
		ffto_set_meta($key, $value);
	}else{
		return ffto_get_meta($key, $value);
	}
}

/**
 * Shortcut to {@see ffto_to_tag()}
 * @return string
 */
function __tag (){
	$params = func_get_args();
	return call_user_func_array('ffto_to_tag', $params);
}

/**
 * Echo {@see ffto_to_tag()}
 * @return void
 */
function _tag (){
	$params = func_get_args();
	echo call_user_func_array('ffto_to_tag', $params);
}

/**
 * Shortcut to {@see ffto_to_link()}
 * @return string
 */
function __link (){
	$params = func_get_args();
	return call_user_func_array('ffto_to_link', $params);
}

/**
 * Echo {@see ffto_to_link()}
 * @return void
 */
function _link (){
	$params = func_get_args();
	echo call_user_func_array('ffto_to_link', $params);
}

/* =====================================================================================================================
Validations
===================================================================================================================== */
/**
 * Check if it has html tags
 * @param mixed $str 
 * @return bool
 */
function ffto_is_html ($str){
	return is_string($str) && preg_match("/<[^<]+>/", $str) !== false;
}

/**
 * Check if it's an email
 * @param mixed $str 
 * @return bool
 */
function ffto_is_email ($str){
	return is_string($str) && preg_match('/^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/', $str);
}

/**
 * Check if the string is a path
 *
 * @param mixed $str 
 * @return bool
 */
function ffto_is_path ($str){
	return is_string($str) && (
		file_exists($str) 
		|| preg_match('/^\.*\/[a-zA-Z_\-\s0-9\.\/]+$/', $str)
	);
}

/**
 * Check if the string is an url
 *
 * // [ ] Add a way to check if it's an image url (checking the ext: png,jpg,jpeg,gif,ico,bmp,svg,svgz,tiff)	
 * 
 * ```php
 * ffto_is_url('http://bob.com', 'local');
 * // false
 * 
 * ffto_is_url('http://bob.com', 'external');
 * // true
 * 
 * ffto_is_url('/bob/com');
 * // false
 * 
 * ffto_is_url('/bob/com', true);
 * // true
 * ```
 * 
 * @param mixed $str 
 * @param bool $type 
 * 	- true: Also consider path-like string (with a '/' at the start)
 *  - 'external': check for external URL
 *  - 'local': check for local URL
 *  - 'anchor': anchor type, with a # prefix
 *  - 'scheme': with a scheme prefix (eg.: 'mailto:')
 *  - 'prefix': either an anchor or scheme
 * @return bool
 */
function ffto_is_url ($str, $type=false){
	if (($str = _str($str)) === false) return false;

	// absolute url, add the right http prefix 
	if (strpos($str, '//') === 0){
		$str = (ffto_is_https() ? 'https' : 'http') . $str;
	}

	$is_url = strpos($str, 'http') === 0;

	// check either if it's external or internal
	if ($type === 'external' || $type === 'local'){
		$host     = ffto_get_host(false);
		$str 	  = str_replace('www.', '', $str);		// always remove "www" to deal with both options
		$host 	  = str_replace('www.', '', $host);
		$is_local = strpos($str, $host) === 0;

		return $is_url && (
			($type === 'local' && $is_local) 
			|| ($type === 'external' && !$is_local) 
		);
	}

	// check for other types to consider
	if (!$is_url){
		$is_anchor = strpos($str, '#') === 0;
		$is_scheme = preg_match('/^[a-z-]\:/', $str);
		
		// with a "#" prefix
		if ($type === 'anchor'){
			$is_url = $is_anchor;
		}
		// with a diff scheme like 'mailto:'
		if ($type === 'scheme'){
			$is_url = $is_scheme;
		}
		// either a anchor or scheme
		if ($type === 'prefix'){
			$is_url = $is_anchor || $is_scheme;
		}		
		// check for file path too
		if ($type === true){
			$is_url = strpos($str, '/') === 0;
		}
	}

	return $is_url;
}

function ffto_is_current_url ($url, $args=null){
	if (!$url || !ffto_is_url($url)) return false;

	// $args = _args($args, []);

	$home    = function_exists('ffto_get_home_url') ? ffto_get_home_url() : ffto_get_host('slash=0');
	$url     = ffto_to_url($url, ['slash'=>false, 'query'=>false]);
	$current = ffto_to_url(true, ['slash'=>false, 'query'=>false]);

	$is_current  = $current === $url;
	$is_home     = $current === $home;
	$is_ancestor = !$is_home && strpos($current, $url) === 0 && strlen($current) > strlen($url);

	if ($is_ancestor && $url !== $home){
		return 'ancestor';
	}else if ($is_home){
		return 'home';
	}else{
		return $is_current;
	}
}

/**
 * Check if the string is an href code
 *
 * @param mixed $str 
 * @param bool $or_path when check if it's an URL, consider path too
 * @return bool
 */
function ffto_is_href ($str, $or_path=true){
	return is_string($str) && (
		ffto_is_url($str, $or_path) 
		|| preg_match('/^(#|([a-z][a-z_-]*\:))/', $str)
	);
}

/* =====================================================================================================================
Casting
===================================================================================================================== */
/**
 * Decode $classnames to return a 1 level array
 * 
 * ```php
 * ffto_to_classnames(['box', ['box--red','box--big']], true);
 * // "box box--red box--big"
 * ```
 *
 * @param string|array $classnames
 * @param mixed $args
 *  - 'join' [false] join the classnames together, either true (for ' ') or a string for a different separator
 * @return mixed
 */
function ffto_to_classnames ($classnames, $args=null){
	$args = _args($args, [
		'join' => false,
	], 'join');

	$classnames = is_array($classnames) ? $classnames : [$classnames];
	$values     = [];
	
	foreach ($classnames as $i=>$v){
		$v = is_string($v) ? explode(' ', $v) : $v;
		if (is_array($v)){
			$values = array_merge($values, $v);
		}
	}

	// $values = ffto_array_flatten($values); // TODO check if this is really necessary
	$values = array_filter($values);
	$values = array_unique($values);
	$values = array_values($values);

	if ($args['join']){
		$values = implode(' ', $values);
	}

	return $values;
}

/**
 * Decode and parse CSS styles
 * 
 * ```php
 * ffto_to_style([
 *     'color' => 'red',
 *     ['font-size'=>14],
 *     'border:1px solid black; border-radius:3px;',
 * ], true);
 * // "color:red; background:blue; font-size:14; border:1px solid black; border-radius:3px;"
 * 
 * ffto_to_style('color:red; name:bob;', 'vars=1&join=1');
 * // "--color:red; --name:bob;"
 * ```
 *
 * @param mixed $style
 * @param mixed $args
 *  - 'join' [false] join every style togethere 
 *  - 'vars' [false] turn the style into var()
 * @return mixed
 */
function ffto_to_style ($style, $args=null){
	$args = _args($args, [
		'join' => false,
		'vars' => false,    // transform to var()
	], 'join');

	$style  = is_array($style) ? $style : [$style];
	$values = [];
	
	// decode the styles
	foreach ($style as $i => $v){
		// it's a key=>value pair
		if (!is_numeric($i) && ffto_is($v)){
			$values[$i] = $v;
		// sub-array
		}else if (is_array($v)){
			$values = array_merge($values, $v);
		// string of css attribute
		}else if (ffto_is_str($v, ':')){
			$v      = ffto_str_decode($v, ':css');
			$values = array_merge($values, $v);
		}
	}

	$style = [];
	foreach ($values as $i => $v){
		if (!ffto_is_not($v)) continue; // remove unecessary empty value

		$i = $args['vars'] ? "--{$i}" : $i;
		$v = _style($i, $v);

		if ($args['join']){
			$style[] = $i.':'.$v.';';
		}else{
			$style[$i] = $v;
		}
	};

	if ($args['join']){
		$style = implode(' ', $style);
	}

	return $style;
}

/**
 * Decode all types of html attributes (style, class, data and other)
 *
 * ```php
 * ffto_to_attrs([
 *     'id'        => 'house',
 *     'class'     => 'bob bob--dark',
 *     'style'     => 'color:red;',
 *     'data-name' => 'John',
 *     'data-age'  => 40,
 * ], true);
 * // 'id="house" class="bob bob--dark" style="color:red;" data-name="John" data-age="40"'
 * 
 * ffto_to_attrs(['id'=>'house', 'class'=>'bob bob--dark'], 'section');
 * // ['open'=>'<section id="house" class="bob bob--dark">', 'close'=>'</section>']
 * 
 * ffto_to_attrs([
 *     'name' => 'John',
 *     'var'  => [
 *         'font'  => 123,
 *         'color' => 'red',
 *     ],
 * ], true);
 * // 'name="John" style="--font:123; --color:red;"'
 * ```
 * 
 * @param mixed $attrs
 * @param mixed $args
 *  - 'tag' [false] wrap the attributes with a tag and return an array with the open and the close tags
 *  - 'join' [false] return the attributes as a string 
 *  - 'skip' [false] skip some attributes
 *  - 'vars' [$vars] 
 * @param array $vars Variables to replace in the attribute values
 * @return mixed
 */
function ffto_to_attrs ($attrs, $args=null, $vars=null){
	if ($args === true){
		$args = ['join'=>true];
	}

	$args = _args($args, array(
		'tag'  => false,   // return a tag version, open and close tags
		'join' => false,   // join everything as a string
		'skip' => [],      // skip attributes
		'vars' => $vars,   // variables to replace strings
	), 'tag');

	$skip  = _array($args['skip']);
	$attrs = is_array($attrs) ? $attrs : [$attrs];
	$vars  = $args['vars'];

	// decode and organize all the attributes
	$_attrs = [
		'style'	=> [],
		'class' => [],
		'data'	=> [],
	];

	foreach ($attrs as $i => $v){
		if (
			in_array($i, $skip)
			|| $v === null
		) continue;

		if ($i === '$vars'){
			$vars = $v;
			continue;
		}

		// style
		if (in_array($i, ['style','styles'])){
			$_attrs['style'] = ffto_to_style([$_attrs['style'], $v]);
		// css var()
		}else if ($i === 'var' && is_array($v)){
			foreach ($v as $ii => $vv){
				$ii                   = "--{$ii}";
				$_attrs['style'][$ii] = $vv;
			}
		// classnames
		}else if (in_array($i, ['class','classname','classnames'])){
			// special case, if the value is an array, merge it. If it's a string, it will replace the full classnames
			if (is_array($v)){
				$_attrs['class'] = ffto_to_classnames([$_attrs['class'], $v]);
			}else{
				$_attrs['class'] = ffto_to_classnames($v);
			}
		// data array
		}else if ($i === 'data' && is_array($v)){            
			$_attrs['data'] = array_merge($_attrs['data'], $v);
		// data single value
		}else if ($i === 'data'){
			$values['data'] = $v;
		// get all the data together
		}else if (strpos($i, 'data-') === 0){
			$i                  = str_replace('data-', '', $i);
			$_attrs['data'][$i] = $v;
		// try decoding the attributes string
		}else if (is_numeric($i) && is_string($v)){
			$v      = ffto_decode_attrs($v);
			$_attrs = ffto_to_attrs([$_attrs, $v]);
		// merge the array
		}else if (is_numeric($i) || $i === 'attrs'){
			$v = ffto_to_attrs($v);

			foreach ($v as $ii => $vv){
				if (in_array($ii, ['style','class','data'])){
					$_attrs[$ii] = array_merge($_attrs[$ii], $vv);
				}else{
					$_attrs[$ii] = $vv;
				}
			}
		// pair of key=>value
		}elseif (!is_numeric($i)){
			$_attrs[$i] = $v;
		}
	}
	
	// remove empty values and set vars (for strings only)
	$filtered = [];
	foreach ($_attrs as $i => $v){
		if (!ffto_is_defined($v)) continue;

		// Replace the vars
		if ($vars && is_string($v)){
			$v = _replace($v, $vars, function ($v){ return _attr($v); });
		}

		$filtered[$i] = $v;
	}
	$_attrs = $filtered;

	if (!$args['join'] && !$args['tag']){
		return $_attrs;
	}

	$tag = $args['tag'];
	if (isset($attrs['tag'])){
		$tag = $attrs['tag'];
		unset($attrs['tag']);
	}

	// TODO fix the orders: ID, CLASS, STYLE, DATA, ...

	$attrs = [];
	foreach ($_attrs as $i => $v){
		if (ffto_is_not($v)) continue; // remove unecessary empty value

		// remove attrbute starting with ':' since this can be used for special attributes
		if (is_string($i) && strpos($i, ':') === 0) continue;

		if ($i === 'style'){
			$style = [];
			foreach ($v as $ii => $vv){
				$style[] = "{$ii}:{$vv};";
			}
			$v = implode(' ', $style);
		}else if ($i === 'class'){
			$v = implode(' ', $v);
		}else if ($i === 'data'){
			foreach ($v as $ii => $vv){
				$vv      = _attr($vv);
				$attrs[] = "data-{$ii}=\"{$vv}\"";
			}
			continue;
		// only show the attribute
		}else if ($v === true){
			$attrs[] = $i;
			continue;
		}else{
			$v = _attr($v);
		}
		
		$attrs[] = $i.'="'.$v.'"';
	};

	$attrs = implode(' ', $attrs);
	if ($args['join']){
		return $attrs;
	}

	$tag   = $tag ? $tag : 'div';
	$open  = "<{$tag} {$attrs}>";
	$close = "</{$tag}>";

	return [
		'open'  => $open,
		'close' => $close
	];
}

/**
 * Transform an array to a <script> tag. 
 * 
 * ```php
 * ffto_to_json_script(['min'=>1, 'max'=>45], 'json/data');
 * // <script type="json/data">{"min": 1, "max": 45}</script>
 * 
 * ftto_to_json_script(true, ['min'=>1, 'max'=>45]);
 * // <script type="text/javascript">
 * // var min = 1;
 * // var max = 45;
 * // </script>
 * 
 * ffto_to_json_script('range', ['min'=>1, 'max'=>45]);
 * // <script type="text/javascript">var range = {"min": 1, "max": 45};</script> * 
 * ```
 *
 * @param mixed $global Make the data global with "var". If 'true', all values with use their key as the global name, if a string, will make all values available under one global value.
 * @param mixed $data 
 * @param mixed $args 
 * 	- 'type' ['text/javascript'] the script 'type' tag
 * @return string
 */
function ffto_to_script ($global, $data=null, $args=null){
	if (is_array($global)){
		$args   = $data;
		$data   = $global;
		$global = null;
	}

	$args = _args($args, [
		'tag'  => true,
		'type' => 'text/javascript',
	], 'type');

	if (is_array($data)){		
		$data = _each($data, function ($v, $i) use ($global){
			$i = preg_replace_callback('/-(.)/', function ($m){ return strtoupper($m[1]); }, $i);

			if ($global === true){
				$v = "var {$i} = " . json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ';';
				return $v;
			}else{
				return [
					'$key'   => $i,
					'$value' => $v,
				];
			}
		});

		if ($global === true){
			$data = NL . implode(NL, $data) . NL;
		}else{
			$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$data = is_string($global) ? "var {$global} = {$data};" : $data;
		}
	}

	if ($args['tag']){
		$html = __html($args['tag'], [
			'tag'  => 'script',
			'type' => $args['type'],
			'html' => $data,
		]);
	}else{
		$html = $data;
	}

	return $html;
}

/**
 * Decode all the attributes passed and return valid html.
 *
 * ```php
 * ffto_to_html([
 *     'id'    => 'house',
 *     'class' => 'bob bob--dark',
 *     'attrs' => [
 *         'class' => 'is-deep',
 *     ],
 *     'data-children' => 45,
 *     'section.is-house',
 *     'html' => [
 *         'tag'  => 'em',
 *         'html' => 'Cool',
 *     ],
 *     'json/data' => [
 *         'min' => 1,
 *         'max' => 45,
 *     ],
 * ]);	
 * // <section class="bob bob--dark is-deep" data-children="45" id="house">
 * // 	<em>Cool</em>
 * // 	<script type="json/data">{
 * // 	    "min": 1,
 * // 	    "max": 45
 * // 	}</script>
 * // </section>
 * 
 * ffto_to_html([
 *     'alias' => 'item-block',
 *     'class' => '&__parent',
 *     'html' => [
 *         'tag'  => 'em',
 *         'html' => 'Cool',
 *         'class'=> '&__item'
 *     ],
 * ]);
 * // <div class="item-block__parent"><em class="item-block__item">Cool</em></div>
 * ```
 * 
 * @return string
 */
function ffto_to_html (){
	$items = func_get_args();
	$attrs = [
		'tag'   => 'div',
		'alias' => '',
	];
	
	// go through all and merge attributes together
	foreach ($items as $item){
		// Only string, array and object can be used, no numeric only OR boolean OR invalid are permitted
		if (is_numeric($item) || (
			!is_string($item)
			&& !is_object($item)
			&& !is_array($item)
		)) continue;

		if ($item === ':object'){
			$item = ['$return'=>'object'];
		}
		
		$vars  = _get($item, '$vars');
		$attrs = ffto_to_attrs([$attrs, $item], null, $vars);
	}

	// return the value if the key extists and remove it from the $attrs
	$_get = function ($key, $fallback=null) use (&$attrs){
		$value = $fallback;
		if (isset($attrs[$key])){
			$value = $attrs[$key];
			unset($attrs[$key]);
		}
		return $value;
	};

	$tag       = $_get('tag');
	$html      = $_get('html');
	$alias     = $_get('alias');
	$json_data = $_get('json/data');

	$vars      = $_get('$vars', []);
	$depth     = $_get('$depth', 0);
	$return    = $_get('$return');

	// children nodes ----------------------------------------------------------
	$_children = ffto_is_list($html) ? $html : [$html];
	$children  = [];
	foreach ($_children as $v){
		if (ffto_is_not($v)) continue;
		
		if (is_string($v) && array_key_exists($v, $vars)){
			$children[] = $vars[$v];
        }else if (ffto_is_callback($v)){
			$children[] = _call($v, $vars, $depth);
        }else if (is_array($v)){
			$children[] = __html($v, [
				'$vars'   => $vars,
				'$depth'  => $depth + 1,
				'$return' => $return,
			]);
        }else{
			$children[] = $v;
        }
    }
	
	$children = array_filter($children, function ($v){ return ffto_is_defined($v); });
	
	// return object -----------------------------------------------------------
	if (ffto_return_object($return)){
        return array_filter([
			'tag'       => $tag,
			'alias'     => $alias,
			'html'      => $children,
			'attrs'     => $attrs,
			'json/data' => $json_data,
		], function ($v){ 
			return ffto_is_defined($v); 
		});
    }

	// json/data ---------------------------------------------------------------
	$json = $json_data ? ffto_to_script($json_data, 'json/data') : '';
	if ($json){
		$tabs 		= NL . str_repeat(TAB, $depth + 1);
		$json 		= preg_replace('/\n/', $tabs, $json);
		$children[] = $json;
	}
	
	// html --------------------------------------------------------------------
	$has_children = !in_array($tag, ['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
	$html         = '';

	// p($depth, $children);
	if ($has_children){
		$join = NL . str_repeat(TAB, $depth + 1);
		$end  = NL . str_repeat(TAB, $depth);
		$html = implode($join, $children);

		// wrapping the children
		if (count($children) > 1){
			$html = $join . $html . $end;
		}
	}

	// TODO
	// $args['parser'] && ($html = _filters('ffto_to_html/html', $html, $args['parser']));

	$attrs    = $tag ? ffto_to_attrs($attrs, true) : null;
	$attrs    = $attrs ? " {$attrs}" : '';
	$has_tabs = $has_children && (strpos(NL, $html) || strlen($html) > 30);

	if ($tag && $has_tabs){
		$html = str_replace(NL, NL.TAB, $html);
		$html = "<{$tag}{$attrs}>".NL.TAB.$html.NL."</{$tag}>";
	}else if ($tag && $has_children){
		$html = "<{$tag}{$attrs}>{$html}</{$tag}>";
	}else if ($tag){
		$html = "<{$tag}{$attrs} />";
	}
	
	$html = $alias ? _alias($html, $alias, 'attrs') : $html;
        
	return $html;
}

/**
 * ffto_to_css
 *
 * ```php
 * ffto_to_css('
 *     body    { color:red; name:bob; }
 *     &       { display:box; }
 *     &--item { margin:1em; }
 * ', 'alias=block&where=1');
 * // <style type="text/css">
 * // :where(body){ color:red; name:bob; }
 * // :where(.block){ display:box; }
 * // :where(.block--item){ margin:1em; }
 * // </style>
 * 
 * to_css('body{ color:red; name:bob; }', 'once=1');
 * // <style type="text/css">body{ color:red; name:bob; }</style>
 * to_css('body{ color:red; name:bob; }', 'once=1');
 * // ''
 * ```
 * 
 * @param mixed $css 
 * @param mixed $args 
 * 	- 'alias' [null] 
 * 	- 'where' [false] wrap all selectors with ":where()"
 * 	- 'once' [false] output only once.
 * 	- 'vars' [null] variables to use with {@see _replace()}
 * @return void
 */
function ffto_to_css ($css, $args=null){
	$args = _args($args, [
		'tag'	=> true,
		'alias' => null,
		'where' => false,
		'once'  => false,
		'vars'  => null,
	], 'alias');

	// transform an array of styles into a big string
    if (is_array($css)){
        $_style = [];
        foreach ($css as $i => $v){
            $v        = ffto_to_style($v, true);
			$_style[] = $i . '{ ' . $v . ' }';
        }
        $css = implode(NL, $_style);
    }

	$css = $args['vars'] ? _replace($css, $args['vars'], '$') : $css;		// replace variables (matching with '$', like '$value')
	$css = $args['alias'] ? _alias($css, '.'.$args['alias'], 'css') : $css;	// add alias 

	if ($args['once']){
		$once_key = is_string($args['once']) ? $args['once'] : md5($css);
		$once_key = 'to_css-' . $once_key;

		// you've already outputted the CSS, skip the rest
		if (_did($once_key, true)) return;
	}

	if ($args['where']){
		$css = preg_replace_callback('/(.+)({.+?})/m', function ($m){
            $selector = trim($m[1]);
            $styles   = $m[2];
            $selector = explode(',', $selector);

            // match the selector and put the "::before/after" after the :where() wrapper
            $selector = array_map(function ($s){
                return preg_replace('/(.+)(:{2}(?:before|after))|(.+)/', ':where($1$3)$2', $s);
            }, $selector);

            $selector = implode(', ', $selector);
            return $selector.$styles;
        }, $css);
	}

	$css = preg_replace('/^[\s\t]*\/\/.+/m', '', $css);   // remove sinlge line comments
    $css = preg_replace('/^\t+/m', '', $css);             // remove the extra tabs
    $css = implode(NL, array_filter(explode(NL, $css)));  // remove the empty lines

	if ($args['tag']){
		$html = __html($args['tag'], [
			'tag'    => 'style',
			'type'   => 'text/css',
			'inline' => true,
			'html'   => $css,
		]);
	}else{
		$html = $css;
	}

	$html = _filters('ffto_to_css/html', $html, $css, $args);

	return $html;
}


/* =====================================================================================================================
Functions
===================================================================================================================== */
/**
 * Decode a CSS selector to array
 * 
 * ```php
 * ffto_decode_attrs('#person.is-big[title="John"]{color:red} > Text', [
 *     'class' => ['button'],
 *     'html'  => 'Default HTML',
 * ]);
 * // ["attrs"=>["id"=>"person", "title"=>"John"], "class"=>["button", "is-big"], "style"=>["color"=>"red"], "html"=>"Text"]
 * ```
 *
 * @param mixed $attrs 
 * @param mixed $defaults Default attributes 
 * @return array
 */
function ffto_decode_attrs ($attrs, $defaults=null){
	$data = [
		'tag'   => null,
		'class' => null,
		'style' => null,
		'html'  => null,
	];

	// Decode CSS selector string
	if (is_string($attrs)){
		// match text values like: .value | #value | [key="value"] | {key:value} | > value (html)
		$attrs = _match($attrs, '/((?:\#[a-z][\w-]*)|(?:\.[\-a-z&][\w-]*)|(?:\[[^\]]+\])|(?:\{[^\]]+?\})|(?:\>.+)|(?:[a-z][\w-]*))/i', false, true);

		foreach ($attrs as $v){
			$prefix = $v[0];
			
			// styles: {color:red, fontSize:14}
			if ($prefix === '{'){
				$v = ffto_str_decode($v, ':css');
				_set($data, 'style', [':merge'=>$v]);
			// attrs: [id=item, alt=image]
			}else if ($prefix === '['){
				$v    = ffto_str_decode($v, ':attrs');
				$data = array_merge($data, $v);
			// id: #itemId
			}else if ($prefix === '#'){
				$v = substr($v, 1);
				$data['id'] = $v;
			// classname: .class
			}else if ($prefix === '.'){
				$v = substr($v, 1);
				_set($data, 'class[]', $v);
			// html: > This is the html
			}else if ($prefix === '>'){
				$v 			  = substr($v, 1);
				$v            = trim($v);
				$data['html'] = $v;
			// tag: div
			}else{
				$data['tag'] = $v;
			}
		}
	}else if (is_array($attrs)){
		$data = array_merge($data, $attrs);
	}

	// add defaults
	if (is_array($defaults)){
		foreach ($defaults as $i => $v){
			$old = isset($data[$i]) ? $data[$i] : null;
			if (is_array($old) && is_array($v)){
				$data[$i] = array_merge($v, $old);
			}else if ($old === null){
				$data[$i] = $v;
			}
		}
	}

	// make sure the classnames are unique and valid
	if ($data['class']){
		$data['class'] = ffto_to_classnames($data['class']);
	}

	// remove null/empty values
	$_data = [];
	foreach ($data as $i => $v){
		if (ffto_is_not($v)) continue;
		$_data[$i] = $v;
	}

	// TODO maybe put all ATTRS on the same level?

	return $_data;
}

/* =====================================================================================================================
Elements
===================================================================================================================== */
/**
 * Wrap a value with a tag.
 * 
 * ```php
 * ffto_to_tag(45, 'span', '{value}%');
 * // "<span>45%</span>"
 * ```
 *
 * @param mixed $html 
 * @param mixed $tag 
 * @param mixed $args (template)
 * 	- 'tag' [$tag] Tag selector to wrap the $html
 * 	- 'empty' [false] Let empty $html pass or not
 * 	- 'template' [null] Template to use with the html, uses the "value" variable
 * @return void
 */
function ffto_to_tag ($html, $tag=null, $args=null){
	if (is_array($tag) || is_bool($tag)){
		$args = $tag;
		$tag  = 'div';
	}

	if (is_bool($args)){
		$args = ['empty'=>$args];
	}

	$args = _args($args, array(
		'tag'	   => $tag,
		'empty'    => false,
		'template' => null,
	), 'template');
	
	// nothing to show
	if (!$html && !$args['empty']) return;

	$html = $args['template'] ? _replace($args['template'], ['value'=>$html]) : $html; 
	$html = __html($args['tag'], ['html'=>$html]);

	return $html;
}

/**
 * Return the aria-label to a link, if it's external it will append a suffix.
 * 
 * ```php
 * ffto_to_aria_label('https://bob.com/cool', 'Super cool');
 * // "Super cool (opens a new tab)
 * ```
 *
 * @param mixed $url
 * 	- 'url' [] the url to compare check if it's external
 * 	- 'label' [$label] label to use
 * 	- 'aria-label' [''] alternate label to use
 * 	- 'target' [null] the link target, if "_blank" it will add the suffix
 * 	- 'suffix' ['(opens a new tab)'] suffix text to add if it's an external link
 * @param string $label
 * @return string
 */
function ffto_to_aria_label ($url, $label=''){
	$item = _args($url, [
		'url'        => '',
		'label'      => $label,
		'aria-label' => '',
		'target'	 => null,
		'suffix'	 => '(opens a new tab)',
	], 'url');

	$_label = $item['label'];
	$label  = $item['aria-label'] ? $item['aria-label'] : $item['label'];

	if ($item['target'] === '_blank' || ffto_is_url($item['url'], 'external')){
		$label = trim($label . ' ' . $item['suffix']);
	}
	
    return $label !== $_label ? $label : '';
}

/**
 * Decode a link and return a validate one. Automatically adds the aria-label if needed and target="_blank" is it's an external URL
 * 
 * ```php
 * ffto_to_link('https://www.project.local.com/cool', 'Click', 'wrap=1');
 * // <a href="https://www.project.local.com/cool/"><span>Click</span></a>
 * 
 * ffto_to_link('bob.com', 'Click');
 * // <a href="https://bob.com/" target="_blank" aria-label="Click (opens a new tab)">Click</a>
 * 
 * ffto_to_link(null, 'Click');
 * // <span>Click</span>
 * 
 * ffto_to_link('test/bob', 'Click');
 * <a href="https://www.project.local.com/test/bob/">Click</a>
 * 
 * ffto_to_link('http://google.com', null, 'short_url=1');
 * // <a href="http://google.com/" target="_blank" aria-label="google.com (opens a new tab)">google.com</a>
 * ```
 * 
 * @todo maybe add a "open in popup window" maybe
 *
 * @param mixed $link 
 * @param mixed $label 
 * @param mixed $args 
 * 	- 'label' [$label] alternate label (if not set by $link or $label)
 * 	- 'text' [span] if the URL is null, then the link is turned into a normal text wrapped in a tag
 * 	- 'wrap' [false] wrap the label with a tag (default to <span>)
 * 	- 'target' [null] specify the target of the link
 * 	- 'aria-label' [null] add a different aria-label to the label
 * 	- 'class' [null] 
 * 	- 'style' [null] 
 * 	- 'attrs' [null] 
 * 	- 'search' [null] add anchor to link for searching text fragment in the page
 * 	- 'short_url' [false] simplify the url for the label (false, true OR 'domain')
 * 
 * @return string
 */
function ffto_to_link ($link, $label=null, $args=null){
	if ((is_array($link) || is_object($link)) && $args === null){
		$args  = $label;
		$label = null;
	}else{
		$link = [
			'url'   => $link,
			'label' => $label,
		];
	}

	// shortcut to make the link open in a new tab
	if ($args === true){
		$args = ['target'=>'_blank'];
	}

	$args = _args($args, [
		'alias'      => 'lnk',    // since there's a higher probability of .link being used, .lnk seems more unique
		'label'      => $label,   // alternate label
		'text'       => 'span',   // if the URL is null, then the link is turned into a normal text wrapped in a tag
		'wrap'       => false,    // wrap the label with a tag (default to <span>)
		'target'     => null,
		'aria-label' => null,
		'class'      => null,
		'style'      => null,
		'attrs'      => null,
		'search'     => null,     // add anchor to link for searching text fragment in the page
		'short_url'  => false,    // simplify the url for the label
	], 'label', 'ffto_to_link/args');
	
	// in case a link passed is an object (eg.: a Wordpress WP_Post, we can decode the proper url/label array)
	$link 	= _filters('ffto_to_link/link', $link, $args);
	$url    = _get($link, 'url || href || src || 0');
	$label  = _get($link, 'label || title || name || 1', $args['label']);
	$target = _get($link, 'target', $args['target']);
	$search = _get($link, 'search', $args['search']);
	$url 	= $url ? ffto_to_url($url, ['search'=>$search]) : null;

	// empty link
	if (!$url && !$label){
		return '';
	}

	if (!ffto_is_url($url, 'prefix')){
		$url = '';
	}else if (!$target && ffto_is_url($url, 'external')){
		$target = '_blank';
	}

	// remove the scheme and www subdomain for the text label
	if (!$label && $args['short_url']){
		$label = preg_replace('/^https?\:\/\/(www\.)?|\/$/', '', $url);

		// only keep the domain
		if ($args['short_url'] === 'domain'){
			$label = preg_replace('/\/.+/', '', $label);
		}
	}else if (!$label){
		$label = $url;
	}

	if (ffto_is_url($label)){
		$label = preg_replace('/[\?\#].+/', '', $label); // remove label query string
	}

	// aria label
	$aria_label = _get($link, 'aria-label', $args['aria-label']);
	$aria_label = ffto_to_aria_label([
		'url'        => $url,
		'label'      => $label,
		'aria-label' => $aria_label,
		'target'     => $target,
	]);
	
    $label = _filters('ffto_to_link/label', $label, $url, $args);
	$url   = _filters('ffto_to_link/url', $url, $label, $args);
	
    // add a little <wrap> on the label
    if ($args['wrap'] && $label){
		$label = __html('span', $args['wrap'], ['html'=>$label]);
    }
	
	// html --------------------------------------------------------------------	
	unset($link['url'],$link['href'],$link['src'],$link[0]);
	unset($link['label'],$link['title'],$link['name'],$link[1]);
	unset($link['target'],$link['search']);

	// Add BEM style css: link, link--current, link--ancestor, link--home (only is currently on homepage)
	$current_url = ffto_is_current_url($url);
	$classnames  = ffto_to_classnames([
		$args['alias'],
		$args['class'],
        is_string($current_url) ? "{$args['alias']}--{$current_url}" : null,
        $current_url === true ? "{$args['alias']}--current" : null,
	]);
	
	$tag  = $url ? 'a' : $args['text'];
	$html = __html($tag, $link, [
		'href'       => $url,
		'html'       => $label,
		'target'     => $target,
		'aria-label' => $aria_label,
		'class'      => $classnames,
		'style'      => $args['style'],
		'attrs'      => $args['attrs'],
	]);

	return $html;
}

/**
 * Get/decode a path or content to a valid asset, either a <style>, <script> or <link>
 * 
 * ```php
 * ffto_to_asset('@styles/style.css', 'defer=1');
 * // <link defer rel="preload" href="https://www.project.local.com/theme/assets/style/dist/style.css?ver=1.0.0" as="style" onload="this.onload=null;this.rel='stylesheet'" />
 * 
 * ffto_to_asset('@scripts/app.js', 'async=1');
 * // <script async type="text/javascript" src="https://www.project.local.com/theme/assets/js/dist/app.js?ver=1.0.0"></script>
 * 
 * ffto_to_asset('@styles/style.css', 'inline=1', 'main');
 * // <style id="main-inline">	@charset "UTF-8";...
 * 
 * to_asset('@scripts/app.js', 'inline=1');
 * // <script type="text/javascript">var STATIC = {};...
 * 
 * ffto_to_asset(['test'=>'Cool'], 'js');
 * // <script type="text/javascript">var test = "Cool";</script>
 * 
 * ffto_to_asset('.test{ color:blue; }', 'css');
 * // <style>.test{ color:blue; }</style>
 * 
 * ffto_to_asset('@fonts/fa6/fa-solid-900.ttf');
 * // <link href="https://www.project.local.com/theme/assets/fonts/fa6/fa-solid-900.ttf?ver=1.0.0" type="font/ttf" />
 * ```
 *
 * @param mixed $asset 
 * @param mixed $args 
 * 	- 'name' [$name] 
 * 	- 'type' ['link'] 
 * 	- 'inline' [false] return the inline content of a file
 * 	- 'async' [null] 
 * 	- 'defer' [null] 
 * 	- 'preload' [null] 
 * 	- 'module' [null] using a JS module
 * 	- 'media' [null] add a media value for CSS
 * 	- 'attrs' [false] 
 * 	- 'version' [config:version]
 * 
 * @param mixed $name 
 * @return string
 */
function ffto_to_asset ($asset, $args=null, $name=null){
	$args = _args($args, array(
		'name'    => $name,
		'type'    => 'link',
		'inline'  => false,	//	return the inline content of a file
		'async'   => null,
		'defer'   => null,
		'preload' => null,
		'module'  => null,	// js
		'media'   => null,	// css
		'attrs'   => false,
        'version' => _config('version'),
    ), 'type');
    
	$asset  = ffto_get_path($asset) ? ffto_path_to_url($asset) : $asset;
	$url 	= null;
	$type   = $args['type'];
	$inline = $args['inline'];
	
	// TODO get data from a file, like a JSON file
	// TODO deal with callback content maybe? 
	// TODO deal with URL but inexistant file

	if (ffto_is_url($asset)){
		$ext = pathinfo($asset, PATHINFO_EXTENSION);
		if ($ext === 'js') 			$type = 'script';
		else if ($ext === 'css')	$type = 'style';
		
		// fetch the content
		if (ffto_is_url($asset, 'local') && $args['inline']){
			$path   = ffto_url_to_path($asset);
			$asset  = ffto_get_file($path);
			$inline = true;
		}else{
			$url = $asset;
		}
	}else if ($type === 'css' || $type === 'style'){
		$asset = ffto_to_css($asset, 'tag=0');
	}else if ($type === 'js' || $type === 'css'){
		$asset = ffto_to_script(true, $asset, 'tag=0');
	}

	// change the type to the proper one
	$type = ffto_to_key($type, [
		'style'  => 'css',
		'script' => 'js',
	], $type);

	// html --------------------------------------------------------------------
	$el = _array($args['attrs']);

	if ($args['name']){
		$el['id'] = $args['name'] . ($inline ? '-inline' : '');
	}

	if ($url){
		$ext   = preg_replace('/[?|#].+/', '', $url);
		$ext   = pathinfo($url, PATHINFO_EXTENSION);
		$_type = ffto_to_key($ext, [
			'script' => 'js',
			'style'  => 'css',
			'font'   => 'ttf,woff,woff2',
			'image'  => 'jpg,jpeg,png,gif,webp',
			'audio'  => 'mp3,ogg,wav',
			'video'  => 'mp4,webm,ogg,mov',
		]);

		$url = ffto_to_url($url, ['version'=>$args['version']]);

		// [ ] deal with other asset type to preload (font, image, track, fetch) - https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/preload
		$el['tag']   = 'link';
		$el['async'] = !!$args['async'];
		$el['defer'] = !!$args['defer'];

		if ($type === 'script'){
			$el['tag']  = 'script';
			$el['type'] = $args['module'] ? 'module' : 'text/javascript';
			$el['src']	= $url;
		}else if ($type === 'style'){
			$el['href']  = $url;
			$el['media'] = $args['media'];
			$el['rel']   = 'stylesheet';

			// special code for css defer
            if ($args['defer'] && !$args['preload']){
                $el['rel']    = 'preload';
                $el['as']     = 'style';
                $el['onload'] = "this.onload=null;this.rel='stylesheet'";
            }
		}else{
			$el['href'] = $url;
			$el['type'] = $_type ? "{$_type}/{$ext}" : null;
		}

		if ($args['preload']){
			$el['href']        = $url;
			$el['src']         = null;
			$el['tag']         = 'link';
			$el['rel']         = 'preload';
			$el['as']          = $_type;
			$el['crossorigin'] = true;
		}
	}else if ($type === 'style'){
		$el['tag']   = 'style';
		$el['media'] = $args['media'];
		$el['html']  = $asset;
	}else if ($type === 'script'){
		$el['tag']  = 'script';
		$el['type'] = 'text/javascript';
		$el['html'] = $asset;
	}else{
		$el['tag'] = 'link';
	}

	// [ ] deal with skipping the output if it's invalid

	return __html($el, $args['attrs']);
}

function ffto_to_list ($list, $args=null, $format=null){
	/*
	$tree = [
		['name'=>'Bob', 'children' => [
			['name'=>'Marie', 'children' => [
				['name'=>'Finish'],
				['name'=>'Julia'],
				['name'=>'Pat'],
			]],
			['name'=>'David']
		]],
		['name'=>'Emma'],
		['name'=>'Sarah', 'children'=>[
			['name'=>'Simon'],
			['name'=>'Juni', 'children'=>[
				['name'=>'Sophie'],
				['name'=>'Marc']
			]],
			['name'=>'John']
		]]
	];

	$ul = [
		'a1',
		[
			'b1',
			'b2',
			[
				'c1',
				'c2',
			],
			'b3',
			[
				'c3'
			]
		],
		'a2'
	];

	$v = ffto_arr_traverse($tree, function ($v, $a){
		if ($a['isFirst']){
			echo '<ul>';
		}
		
		echo '<li>';
		// echo $v;
		echo $v['name'];
	}, function ($v, $a){
		echo '</li>';

		if ($a['isLast']){
			echo '</ul>';
		}
	});
	*/
}

// _skip_link, to_metas, to_pill, to_dates (check other sites for common patterns), accordion and overlay would be their own file
// to_table (simple table)

/* =====================================================================================================================
View
===================================================================================================================== */
/**
 * Set meta values to the site. This is heavily used with {@see ffto_to_view}.
 *
 * @param mixed $key 
 * @param mixed $value 
 * @param mixed $reset 
 * @return array
 */
function ffto_set_meta ($key, $value=null, $reset=null){
	$pair = ffto_to_pair($key, $value);
	$meta = $reset ? [] : _global('$meta', []);
	$meta = ffto_to_attrs([$meta, $pair]);
	_global('$meta', $meta, true);
	return $meta;
}

/**
 * Return either 1 meta value of the current viwe/page or all of the meta values.
 *
 * @param mixed $key 
 * @param mixed $fallback 
 * @return mixed
 */
function ffto_get_meta ($key=null, $fallback=null){
	$meta = _global('$meta', []);
	
    if ($key){
        return array_key_exists($key, $meta) ? $meta[$key] : $fallback;
    }

	return $meta;
}

/**
 * Fetch the content of a file and output it with a tag around it, it's also possible to have a second wrapper. The targeted
 * file can use the {@see ffto_set_meta()} function to manipulate the output. When using aliases, you can use "&" (current view) 
 * and "&&" (parent view). If "&&" is used and there's no parents, it won't show.
 *
 * ```php
 * ffto_to_view(function (){ ?>456<?php }, null, 'block');
 * // '<div class="view-block">456</div>'
 * 
 * ffto_to_view(function (){ ?>456<?php }, null, 'alias=block&wrap=1');
 * // '<div class="view-block__wrap"><div class="view-block">456</div></div>'
 * ```
 * 
 * @param mixed $path 
 * @param string $vars Variables to pass to the included file
 * @param string $args 
 * 	- 'tag' [div] default html tag/attributes to wrap the html
 * 	- 'wrap' [false] extra external wrapper to add
 * 	- 'prefix' ['view-'] prefix to use before the alias
 * 	- 'alias' [$path] automatically slugify the filename (if not overwritten)  
 * 	- 'anchor' [false] add an <a> anchor right before the element
 * 	- 'empty' [false] allow empty html to still be shown
 * 	- 'id' [null] specify the ID
 * 	- 'attrs' [null] specify extra attributes
 * 	- 'class' [null] add classnames
 * 	- 'style' [null] add style
 * 	- 'data' [null] add data attributes
 * 	- 'hidden' [false] add [hidden] attribute
 * 	- 'return' [null] return a specific thing
 * @return string
 */
function ffto_to_view ($path, $vars=null, $args=null){
	$fn = ffto_is_callback($path) ? $path : null;

	if (
		!($path = ffto_get_path($path)) 
		&& !$fn
	) return false;

	if ($fn){
		$path = '';
	}

	$alias = '';
	if ($path){
		$alias = $path ? pathinfo($path, PATHINFO_FILENAME) : '';
		$alias = preg_replace('/^[^a-z0-9]+/i', '', $alias);	// remove prefix text that isn't a letter or number
		$alias = _slug($alias);
	}
	
	$vars = _args($vars);
	$args = _args($args, [
		'tag'    => 'div',
		'wrap'   => false,
		'prefix' => 'view-',  // alias prefix
		'alias'  => $alias,
		'anchor' => false,    // will add an anchor before the html IF the current view has no <tag>
		'empty'  => false,    // allow empty html, so the tag/wrap will still show
		// 'ctx'	 => null,	  // TODO maybe add "ctx" for string functions, when using "__t()" it would check the current ctx
		'id'     => null,
        'attrs'  => null,
		'class'  => null,
        'style'  => null,
        'data'   => null,
		'hidden' => false,    // add [hidden] attribute
		'return' => null,
	], 'alias');
	
	$tag = ffto_to_attrs([
		$args['tag'],
		'id' 	=> $args['id'],
		'attrs' => $args['attrs'],
		'class' => $args['class'],
		'style' => $args['style'],
		'data'  => $args['data'],
		'hidden'=> $args['hidden'],
	]);

	// [ ] Add a way to use a function OR custom html instead of a view (good for using the extra args like: wrap, meta, empty, anchor, ...)
	// [ ] maybe add info about "I'm in a view that's in another view"...

	// meta data will be merged with "tag"
	$old_meta = ffto_get_meta();
	
	$meta = [
		'alias' => $args['alias'],
		'prefix'=> $args['prefix'],
		'empty' => $args['empty'],
		'wrap'  => $args['wrap'],
		'anchor'=> $args['anchor'],
	];

	// content -----------------------------------------------------------------
	if ($path){
		$_meta = ffto_get_file_meta($path);   // get the file comment meta and add them first
		$meta  = array_merge($meta, $_meta);
		ffto_set_meta($meta, null, true);

		$content = ffto_include_file($path, $vars);
	}else if ($fn){
		ffto_set_meta($meta, null, true);

		$content = ffto_include_content($fn, $vars);
	}

	$html = is_string($content) ? $content : null;
	$json = is_array($content) ? $content : null;
	$meta = ffto_get_meta();
	
	// reset meta/var to their previous values
	ffto_set_meta($old_meta, null, true);
	
	// html --------------------------------------------------------------------
	if (!$json && ($html || $meta['empty'])){
		$_alias  = _get($meta, 'alias');
		$alias 	 = $_alias === true ? $alias : $_alias;
		$wrap 	 = $meta['wrap'];
		$prefix  = $meta['prefix'] ? $meta['prefix'] : '';
		$anchor  = $meta['anchor'];
		unset($meta['alias'], $meta['wrap'], $meta['prefix'], $meta['anchor'], $meta['empty']);

		$html = ffto_to_html([
			'class'=> $alias ? ['&'] : null,
		], $tag, $meta, [
			'alias' => false,
			'html'  => $html
		]);

		// add the wrap if set
		if (ffto_is_callback($wrap)){
			$html = _apply($wrap, $html, $vars);
		}else if ($wrap){
			$html = ffto_to_html(['class'=>['&__wrap']], $wrap, [
				'alias' => false,
				'html'  => $html
			]);
		}

		// add anchor, to add an anchor as an ID, just use the "id" attribute
		if ($anchor){
			$html = '<a id="'.$anchor.'"></a>' . $html;
		}

		// deal with parent alias
		$parent  = isset($old_meta['alias']) ? $old_meta['alias'] : 'parent';
		$aliases = [
			'&&' => $prefix.$parent,
			'&'  => $prefix.$alias,
		];

		$html = _alias($html, ['aliases'=>$aliases]);
	}

	$return = $args['return'];
	if (ffto_return_object($return)){
		return array(
			'html'    => $html,
			'json'    => $json,
			'meta'    => $meta,
			'content' => $content,
		);
	}else if ($return === 'meta'){
		return $meta;
	}else if ($return === 'json'){
		return is_array($json) ? $json : [];
	}else if ($return === 'html'){
		return $html ? $html : '';
	}

	return $json ? $json : $html;
}

/**
 * Call the {@see ffto_to_view} from the @items folder. Make it's faster to output a view. All the alias will be using the `config:item_alias` template.
 *
 * @param mixed $slug 
 * @param mixed $vars 
 * @param mixed $args Same args as those in {@see ffto_to_view}
 * @return mixed
 */
function ffto_to_item ($slug, $vars=null, $args=null){
	$template = _config('item_alias', 'item-{slug}');
	$alias 	  = preg_replace('/^[^a-z0-9]+/i', '', $slug);
	$alias 	  = _replace($template, ['slug'=>$alias]);

	$args = _args([
		'prefix' => false,
		'dir'    => '@items',
		'alias'  => $alias,
	]);
	
	$path = ffto_get_path([
		"{$args['dir']}/{$slug}",
		"{$args['dir']}/{$slug}.php",
		"{$slug}",
		"{$slug}.php",
	]);

	return $path ? ffto_to_view($path, $vars, $args) : false;
}
