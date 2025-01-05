<?php 
function to_utf8 ($str){
	if (!is_string($str)) return $str;

	if (!mb_check_encoding($str, 'UTF-8')){
		$str = mb_convert_encoding($str, 'UTF-8', mb_list_encodings());
	}

	return $str;
}

function string_trim ($str){
	if (!is_string($str)) return $str;
	// better than trim(), since there's multibytes sometimes that won't trim (eg.: "\u00a0")
	$str = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $str); // remove the "zero width character"
    return $str ? preg_replace("/^(\s|\t)+|(\s|\t)+$/u", "", $str) : '';
}

function string_clean ($str, $remove_newline=false){
	$str = $remove_newline ? preg_replace("/(\n|\r|\t)/", ' ', $str) : $str;
	$str = string_trim($str); 
    $str = $str ? preg_replace('/ {2,}/', ' ', $str) : '';
    return $str;
}

function string_strip ($str){
	$str = preg_replace('/<!--(.|s)*?-->/', '', $str);					// remove comments
	$str = preg_replace('/\<script[\s|\S]+?\<\/script\>/', '', $str);	// remove scripts
	$str = preg_replace('/<br ?\/?>/', ' ', $str);
	$str = strip_tags($str);
	$str = string_clean($str);
	return $str;
}

function string_clean_html ($str, $args=null){ //$nbsp=false){
	$args = _args($args, [
		'attrs' => false,
		'style' => false,
		'script'=> false,
		'nbsp'  => false,
	], 'nbsp');

	if ($args['script']){
		$str = preg_replace('/\<script[\s|\S]+?\<\/script\>/', '', $str);
	}
	
	$str = preg_replace('/<!--(.|s)*?-->/', '', $str);         	// remove comments
	$str = preg_replace("/[\r\n]+/", "\n", $str);              	// remove multiple line breaks
	$str = preg_replace("/\<p\>[\s\r\t\n]*\<\/p\>/", '', $str); // remove empty <p>
	
	if ($args['nbsp']){
		$str = preg_replace("/\<p\>.*\&nbsp;.*<\/p\>/", '', $str);  // remove empty <p> with &nbsp;
	}

	if ($args['attrs']){
		$keep = explode(',', 'alt,colspan,controls,height,href,id,loop,muted,playsinline,preload,reversed,role,rowspan,scope,sizes,span,src,srclang,srcset,start,target,title,type,usemap,width');
		
		if (is_string($args['attrs'])){
			$attrs = to_array($args['attrs']);
			$keep  = array_merge($keep, $attrs);
		}
		
		$str = preg_replace_callback('/\<([a-z][a-z_-]*)(\s[^\>]+)\>/', function ($m) use ($args, $keep){
			$tag   = $m[1];
			$attrs = to_match($m[2], '/\s([a-z][a-z_-]*)="([^"]+)"/', true);
			$attrs = array_each($attrs, function ($v) use ($args, $keep){ 
				$key   = $v[0];
				$value = $v[1];

				if ($key === 'style' && $args['style']){
					$value = _apply($args['style'], $value);
				}else if (!in_array($key, $keep)){
					$value = '';
				}

				if (!$value) return;

				return $key . '="' . $value . '"';
			}, ' ');
			return '<' . $tag . ($attrs ? " {$attrs}" : '') . '>';
		}, $str);
	}
	
	return $str;
}

function string_clean_accents ($str, $charset='utf-8'){
	if (!is_string($str)) return '';
	// $str = htmlentities($str, ENT_QUOTES);
	// $str = preg_replace('/&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i', '$1', $str);

	$str = htmlentities($str, ENT_NOQUOTES, $charset);
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    $str = preg_replace('#&[^;]+;#', '', $str);
    
	return $str;
}

function string_clean_emoji ($str){
	$text = iconv('UTF-8', 'ISO-8859-15//IGNORE', $str);
    $text = preg_replace('/\s+/', ' ', $text);
    return iconv('ISO-8859-15', 'UTF-8', $text);
}

function string_tab ($str, $tab=0){
	if (is_int($str)){
		$tab = $str;
		$str = '';
	}

	$str = is_array($str) ? implode(NL, $str) : $str;

	if ($tab){
		$tab = str_repeat(TAB, $tab);
		$str = $tab . ($str ? str_replace(NL, NL . $tab, $str) : '');
	}
	
	return $str;
}

function string_split ($str, $separator, $limit=0){
	$values = [];
	if (is_callback($separator)){
		$values = _apply($separator, $str);
	}else if (is_regexp($separator)){
		$items = [];
		preg_replace_callback($separator, function ($m) use (&$items){ $items[] = $m[0]; }, $str);
		$values = $items;
	}else if (is_string($separator)){
		$values = explode($separator, $str, $limit ? $limit : PHP_INT_MAX);
	}
	return $limit ? array_slice($values, 0, $limit) : $values;
}

function string_truncate ($str, $args=''){
	if (is_numeric($args)){
        $args = array('limit'=>(int)$args);
	}

	$args = to_args($args, array(
		'type'		 => 'letter', 	// [letter, words]
		'limit'		 => 50,
		'ellipsis'	 => '…',
		'keep_words' => false,		// keep full words in letter mode
		'tag'		 => false,		// wrap with a tag (span by default)
	));

	$str  = $str ? $str : ''; // make sure it's a string (if it's null)	
	$str  = preg_replace('/<br\s?\/?>/', ' ', $str);    
	$str  = strip_tags($str);
    $str  = string_clean($str, true);
    $str  = string_trim($str, true);
	$text = $str;

	if ($args['type'] === 'words' || $args['type'] === 'word'){
		$words 	= explode(' ', $text);
		$text	= implode(' ', array_splice($words, 0, $args['limit']));
	}else{
		$text		= substr($text, 0, $args['limit']+1);
		$last_space	= strrpos($text, ' ');
		$last_space	= $last_space === false ? strpos($text, ' ') : $last_space;
		$last_space	= $last_space === false ? strlen($text) : $last_space;
		$text		= $args['keep_words'] ? substr($text, 0, $last_space) : substr($text, 0, $args['limit']);
	}

	if ($text != $str){
		$text = string_trim($text) . $args['ellipsis'];
	}

	if ($text === false){
		$text = '';
	}

	if ($args['tag']){
		$tag   = $args['tag'] === true ? 'span' : $args['tag'];
		$title = to_attr($str);
		$text  = '<' . $tag . ' title="' . $title . '">' . $text . '</' . $tag . '>';
	}

	return $text;
}

function string_highlight_text ($str, $search, $args='', $return=null){
    $args = _args($args, array(
        'ellipsis' => '…',
        'before'   => 30,
        'max'      => 200,
        'tag'      => 'strong',
		'return'   => $return,
	), 'max');

    $str = string_strip($str);
	$str = html_entity_decode($str);
    $str = string_clean($str, true);
	
    $index = $search ? mb_stripos($str, $search) : false;
	$full  = $str;
	
    if ($index !== false){
        $before_index  = $index > $args['before'] ? $index - $args['before'] : 0;
        $before_length = $index - $before_index;
        $search_index  = $index;
        $search_length = mb_strlen($search);

        $after_index   = $index + $search_length;
        $after_length  = $args['max'] - ($after_index - $before_index);
		$after_ellipsis= $after_length > mb_strlen($str) ? false : true;
        
		// TODO bug with "$after_length" when the $args[max] is less than the $args[before] text + length of the $search text

		$tag_before = $args['tag'] ? "<{$args['tag']}>" : '';
		$tag_after  = $args['tag'] ? "</{$args['tag']}>" : '';

		// full version
		$full = mb_substr($str, 0, $index) .
			$tag_before . mb_substr($str, $index, $search_length) . $tag_after .
			mb_substr($str, $after_index);

        $str = ($before_index ? $args['ellipsis'] : '') . 
			mb_substr($str, $before_index, $before_length) .
			$tag_before . (mb_substr($str, $search_index, $search_length)) . $tag_after .
            mb_substr($str, $after_index, $after_length) . ($after_ellipsis ? $args['ellipsis'] : '');
    }else{
        $length = mb_strlen($str);
        $str    = mb_substr($str, 0, $args['max']) . ($length > $args['max'] && $args['ellipsis'] ? $args['ellipsis'] : '');
    }

	if (return_object($args['return'])){
		return [
			'excerpt' => $str,
			'full'    => $full,
		];
	}else if ($args['return'] === 'full'){
		return $full;
	}else{
		return $str;
	}
}

function replace_alias ($str, $alias='', $args=null){
	if (!$alias) return $str;

    $args = _args($args, [
        'prefix' => '&',
        'others' => array(),
		'type'	 => 'html',
        'skip'   => array('value', 'placeholder'),
    ], 'type');
    
	$aliases = to_set($args['prefix'], $alias);
    $aliases = array_merge($aliases, $args['others']);

	// get string from a function
	if (is_callback($str)){
		ob_start(); $str(); $str = ob_get_clean();
	}

	$replace = function ($text, $all=false) use ($aliases){
		foreach ($aliases as $i => $v){
			if ($all){
				$text = strtr($text, ["{$i}" => $v]);
			}else{
				$text = strtr($text, [
					"'{$i}"  => "'{$v}",	
					"\"{$i}" => "\"{$v}",
					" {$i}"  => " {$v}",
					"{$i} "  => "{$v} ",
					// "{$i}:"  => "{$v}:",
					".{$i}"  => ".{$v}",
					"({$i}"  => "({$v}",
					"{$i}{"  => "{$v}{",
				]);
			}
		}
		return $text;
	};

	if ($args['type'] === 'html'){
		// skip the html attributes
		$str = preg_replace('/(?:\&((?:\#\d+)|(?:[a-z]+))\;)/i', KEY . '$1;', $str);
		
		$str = preg_replace_callback('/(?:(?:([a-z_-]+)=)((?:"[^"]*?")|(?:\'[^\']*?\'))|((?:"[^"\n]*?")|(?:\'[^\'\n]*?\')))/s', function ($matches) use ($replace, $args){
			$name  = $matches[1];
			$value = $matches[2];
			$text  = $matches[0];

			if (strpos($value, NL) !== false) return $text;
			
			// skip some named attribute
			if (in_array($name, $args['skip'])){
				return $text;
			}

			return $replace($text);
		}, $str);

		$str = str_replace(KEY, '&', $str);
	}else if ($args['type'] === 'css'){
		$str = preg_replace_callback('/([^{]+)({.+})/m', function ($m) use ($replace){
			$selector = $replace($m[1], true);
			return "{$selector} {$m[2]}";
		}, $str);
	}else{
		$str = $replace($str);
	}

	return $str;
}

function prepare_sql ($value, $args=''){
	$args = to_args($args, array(
		'like' => false,
		'wrap' => true,
	));

	if (is_array($value)){
		$values = array_map('prepare_sql', $value);
		$values = implode(',', $values);
		return $args['wrap'] ? "({$values})" : $values;
	}

	if (is_null($value)){
		$value = 'NULL';
	}else if (is_bool($value)){
		$value = $value ? 1 : 0;
	}else if (is_string($value)){
		$value = str_replace("'", "''", $value);
		// $value = addcslashes($value, "\000\n\r\\\032");	
		$value = addcslashes($value, "\000\\\032");	// TODO validate this is really needed
		$value = $args['like'] ? "%{$value}%" : $value;
		$value = "'{$value}'";
	}else if (is_a($value, 'DateTime')){
		$value = $value->format('Y-m-d H:i:s');
		$value = "'{$value}'";
	}

	return $value;
}

// Validate --------------------------------------------------------------------
function string_is_empty ($str){
	if (!is_string($str)) return false;
	return !string_trim(strip_tags($str));
}

function string_is_email ($str){
	return is_string($str) && preg_match('/^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/', $str);
}

function string_is_html ($str){
	return is_string($str) && preg_match("/<[^<]+>/", $str) !== false;
}

function string_is_url ($str, $has_scheme=true, $include_path=false){
	return is_string($str) 
		&& (
			($has_scheme && strpos($str, 'http') === 0 || strpos($str, '//') === 0)	// either "http..." or "//..."
			|| (!$has_scheme && strpos($str, '/') === 0)							// start with "/"
			|| ($include_path && string_is_path($str))							// is path
		);
}

function string_is_path ($str){
	return (
		!is_string($str) ||
		!preg_match('/^[a-z_\-\s0-9\.\/]+$/', $str)
	) ? false : true;
}

function string_is_href ($str, $has_scheme=false){
	return (!is_string($str) || (
		!string_is_url($str, $has_scheme) &&
		!preg_match('/^(#|([a-z][a-z_-]*\:))/', $str)
	)) ? false : true;
}

function string_is_json ($string) {
	if (function_exists('json_last_error')){
		$string = json_decode($string, true);
		return json_last_error() == JSON_ERROR_NONE ? true : false;
	}else{
		return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
	}
}

// Cast ------------------------------------------------------------------------
function to_slug ($text, $args=null){
	$args = _args($args, array(
		'format'   => '',
		'fallback' => '',
	), 'format');

	if (!$text) return null;

	$text = str_replace(["’",'"',"'"], '', $text);
	$text = string_clean_accents($text);
	$text = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	$text = preg_replace_callback('/[A-Z]+/', function ($m){ return " " . strtolower($m[0]); }, $text);

	// replace non letter or digits by -
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
	// trim
	$text = trim($text, '-');
	// transliterate
	$text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	// lowercase
	$text = strtolower($text);
	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);

	// other types of format
	//$str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
	$f = $args['format'];
	if ($f === 'space' || $f === ' '){
		$text = str_replace('-', ' ', $text);
	}else if ($f === 'space-words' || $f === 'A ' || $f === ' A' || $f === ' A '){
		$text = ucwords(str_replace('-', ' ', $text));
	}else if ($f === 'underscore' || $f === '_'){
		$text = str_replace('-', '_', $text);
	}else if ($f === 'slash' || $f === '/'){
		$text = str_replace('-', '/', $text);
	}else if ($f === 'camel' || $f === 'words' || $f === 'A'){
		$text = str_replace(' ', '', ucwords(str_replace('-', ' ', $text)));

		if ($args['format'] === 'camel') {
			$text[0] = strtolower($text[0]);
		}
	}
	
	return empty($text) ? $args['fallback'] : $text;
}

function to_url ($url=null, $args=null, $query=null){
	if (is_array($url)){
		$args = $url;
		$url  = null;
	}

	// TODO add the trailing slash

	$args = _args($args, array(
		'scheme' => false,    // for "string_is_href()" 
		'https'  => null,
		'host'   => null,
		'path'   => null,
		'query'  => $query,
		'anchor' => null,
		'slash'  => true,	// always add the slash at the end
	), 'path');

	// passing the query object by the last parameter will automatically clear all previous query items
	$clear_query = !!$query;
	
	// if (string_is_href($url, $args['scheme'])){
	// 	return $url;
	// }

	if (string_is_email($url)){
		return "mailto:{$url}";
	}

	// TODO deal with other types of protocol (tel:, sms: )
	
	if ($url === null){
		$scheme = _get($_SERVER, 'REQUEST_SCHEME,HTTP_X_FORWARDED_PROTO', 'http');
		$host   = _get($_SERVER, 'HTTP_HOST,SERVER_NAME');
		$path 	= preg_replace('/\?.+$/', '', $_SERVER['REQUEST_URI']);
		$query  = $_SERVER['QUERY_STRING'];
		$url    = $scheme . '://' . $host . $path . ($query ? "?{$query}" : '');
	}
	
	$url = parse_url($url);
	$url = array_merge([
		'scheme' => 'https',
		'host'   => '',
		'path'   => '',
		'query'  => '',
		'anchor' => '',
	], $url);
	parse_str($url['query'], $query);

	if ($args['https'] !== null){
		$url['scheme'] = $args['https'] ? 'https' : 'http';
	}
	if ($args['host'] !== null){
		$url['host'] = $args['host'];
	}
	if ($args['path'] !== null){
		$url['path'] = $args['path'];
	}
	if ($args['query'] !== null){
		if ($clear_query){
			$query = [];
		}

		if (is_string($args['query'])){
			parse_str($args['query'], $query);
		}else if (is_array($args['query'])){
			$query = array_merge($query, $args['query']);
		}

		$query = array_filter($query, function ($v){
			return $v !== false && $v !== null && $v !== '';
		});

		$url['query'] = http_build_query($query);
	}

	
	if ($args['anchor'] !== null){
		$url['anchor'] = $args['anchor'];
	}
	
	$host   = preg_replace('/^\/|\/$/', '', trim($url['host']));
	$path   = preg_replace('/^\/|\/$/', '', trim($url['path']));
	$path   = $path ? "/{$path}" . ($args['slash']?'/':'') : '/';
	$query  = $url['query'] ? '?'.preg_replace('/^\?/', '', $url['query']) : '';
	$anchor = $url['anchor'] ? '#'.preg_replace('/^#/', '', $url['anchor']) : '';
	$url    = $url['scheme'] . '://' . $host . $path . $query . $anchor;

	return $url;
}

function _to_url ($url, $args=''){
	if (!$url || empty($url) || !is_string($url)) return '';

	$args = to_args($args, array(
		'https'    => null,
		'host'     => '',
		'protocol' => false,
	));

	if (string_is_href($url, $args['protocol'])){
		return $url;
	}

	if (string_is_email($url)){
		return "mailto:{$url}";
	}

	if (strpos($url, 'http') === false){
		if ($args['host']){
			$url = preg_replace('/\/$/', '', $args['host']) . '/' . $url;
		}else{
			$url = ($args['https'] ? 'https://' : 'http://') . $url;
		}
	}

	// fix issue with multiple slashes
	$url = preg_replace('/\/+/', '/', $url);
	$url = str_replace(':/', '://', $url);

	return $url;
}

function to_filesize ($str){
	$units  = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	$number = substr($str, 0, -2);
	$suffix = strtoupper(substr($str,-2));
	
	//B or no suffix
	if(is_numeric(substr($suffix, 0, 1))) {
		return preg_replace('/[^\d]/', '', $str);
	}
	
	$exponent = array_flip($units);
	$exponent = isset($exponent[$suffix]) ? $exponent[$suffix] : null;
	if($exponent === null) {
		return null;
	}

	return $number * (1024 ** $exponent);	
}

function to_richtext ($text, $args=''){
	$args = to_args($args, array(
		'email'	 => true,
		'url'	 => true,
		'br'	 => false,
		'p'		 => false,
		'target' => '_blank',
	));

	// TODO rest of the args
	// TODO filter the links to figure out "target=_blank" automatically 
	
	if ($args['email']){
		$text = preg_replace('/([a-zA-Z0-9_\-\.]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]{2,5})/', '<a href="mailto:$1" target="' . $args['target'] . '" rel="noopener noreferrer">$1</a>', $text);
	}
	if ($args['url']){
		$text = preg_replace('/((?:http|ftp|https):\/\/[^\s]+)/', '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);
	}
	if ($args['br']){
		$text = nl2br($text);
	}
	if ($args['p']){
	}
	

	return $text;
}

function to_text ($str){
	$str = html_entity_decode($str);
	$str = preg_replace('/<br\s?\/?>/', ' ', $str);
	$str = strip_tags($str);
	$str = string_clean($str, true);
	return $str;
}

function to_minify ($str, $type=null){
	if ($type === 'css'){
		// @source https://github.com/GaryJones/Simple-PHP-CSS-Minification/blob/master/minify.php

		// Normalize whitespace
		$css = preg_replace( '/\s+/', ' ', $str );
		// Remove spaces before and after comment
		$css = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css );
		// Remove comment blocks, everything between /* and */, unless
		// preserved with /*! ... */ or /** ... */
		$css = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $css );
		// Remove ; before }
		$css = preg_replace( '/;(?=\s*})/', '', $css );
		// Remove space after , : ; { } */ >
		$css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );

		// TODO buggy! part, breaks the media-query
		// Remove space before , ; { } ( ) >
		// $css = preg_replace( '/ (,|;|\{|}|\(|\)|>)/', '$1', $css );

		// Strips leading 0 on decimal values (converts 0.5px into .5px)
		$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
		// Strips units if value is 0 (converts 0px to 0)
		$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
		// Converts all zeros value into short-hand
		$css = preg_replace( '/0 0 0 0/', '0', $css );
		// Shortern 6-character hex color codes to 3-character where possible
		$css = preg_replace( '/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css );

		return trim($css);
	}else if ($type === 'js'){
		// @source: https://wphave.com/minify-compress-javascript-files-php/

		// Remove a tab
		$js = str_replace("\t", " ", $str);
		// Remove comments with "// "
		$js = preg_replace('/\n(\s+)?\/\/[^\n]*/', "", $js);	
		$js = preg_replace('/\/\/[^\n]*/', "", $js);				// @info added code, to remove the single comments that aren't at start of line
		// Remove other comments
		$js = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $js);
		$js = preg_replace("/\/\*[^\/]*\*\//", "", $js);
		$js = preg_replace("/\/\*\*((\r\n|\n) \*[^\n]*)+(\r\n|\n) \*\//", "", $js);		
		// Remove a carriage return
		$js = str_replace("\r", "", $js);
		$js = preg_replace("/{\s*\n/", "{ ", $js);

			// @info not sure this always works
			$js = preg_replace("/\n\s*};\s*/", " }; ", $js);
			$js = preg_replace("/\n\s*};\s\n*/", " }; ", $js);
			$js = preg_replace("/([^\n]this\.)/", "\n$1", $js);
			$js = preg_replace("/\n\s*};\s*\n/", "}; ", $js);

		// Remove whitespaces
		$js = preg_replace("/\s+\n/", "\n", $js);	
		$js = preg_replace("/\n\s+/", "\n ", $js);
		$js = preg_replace("/ +/", " ", $js);

		// @info added code, to make it on 1 line
		$js = preg_replace("/\n/", " ", $js);
		$js = trim($js);
		
		return $js;
	}else if ($type === 'html'){
		// TODO
	}else{
		// TODO
	}

	return $str;
}

function to_variants ($str, $return=null){
	// @info only english variants
	$rules = array(
		// plural to single
		'ss'	=> ['type'=>'single', 'replace'=>'sses'],
		'os'	=> ['type'=>'plural', 'replace'=>'o'],
		'xes'	=> ['type'=>'plural', 'replace'=>'x'],
		'oes'	=> ['type'=>'plural', 'replace'=>'o'],
		'ies'	=> ['type'=>'plural', 'replace'=>'y'],
		'ves'	=> ['type'=>'plural', 'replace'=>'f'],
		's'		=> ['type'=>'plural', 'replace'=>''],
		// single to plural
		'ie'	=> ['type'=>'single', 'replace'=>'ies'],
		'y' 	=> ['type'=>'single', 'replace'=>'ies'],
		'x' 	=> ['type'=>'single', 'replace'=>'xes'],
		'f' 	=> ['type'=>'single', 'replace'=>'ves'],
		''		=> ['type'=>'single', 'replace'=>'s'],
	);

	$variants = null;
	foreach ($rules as $i => $v){
		$i = '/(.+)('.$i.')$/';
		
		if (preg_match($i, $str, $m)){
			$alt = is_string($v['replace']) ? $m[1] . $v['replace'] : $m[0];

			if ($v['type'] === 'plural'){
				$variants = [
					'single' => $alt,
					'plural' => $str,
				];
			}else{
				$variants = [
					'single' => $str,
					'plural' => $alt,
				];
			}

			break;
		}
	}
	/*
	$variants = array();
	$length   = strlen($str);
	foreach ($rules as $from => $to){
		$index = strrpos($str, $from);
		$at_end= $index === ($length - strlen($from));

		if (!$at_end){
			continue;
		}

		// skip
		if ($to === false){
			continue;
		}

		foreach ((array)$to as $t){
			$variants[] = substr($str, 0, $index) . $t;
		}
		
		break;
	}

	if (array_search($str, $variants) == false){
		array_unshift($variants, $str);
	}
	*/

	return $return ? _get($variants, $return) : $variants;
}

function to_lorem ($count=null, $args=null){
	$lorem = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum', 'dictum', 'mi', 'non', 'egestas', 'nulla', 'in', 'lacus', 'sed', 'sapien', 'placerat', 'malesuada', 'at', 'erat', 'etiam', 'id', 'velit', 'finibus', 'viverra', 'maecenas', 'mattis', 'volutpat', 'justo', 'vitae', 'vestibulum', 'metus', 'lobortis', 'mauris', 'luctus', 'leo', 'feugiat', 'nibh', 'tincidunt', 'a', 'integer', 'facilisis', 'lacinia', 'ligula', 'ac', 'suspendisse', 'eleifend', 'nunc', 'nec', 'pulvinar', 'quisque', 'ut', 'semper', 'auctor', 'tortor', 'mollis', 'est', 'tempor', 'scelerisque', 'venenatis', 'quis', 'ultrices', 'tellus', 'nisi', 'phasellus', 'aliquam', 'molestie', 'purus', 'convallis', 'cursus', 'ex', 'massa', 'fusce', 'felis', 'fringilla', 'faucibus', 'varius', 'ante', 'primis', 'orci', 'et', 'posuere', 'cubilia', 'curae', 'proin', 'ultricies', 'hendrerit', 'ornare', 'augue', 'pharetra', 'dapibus', 'nullam', 'sollicitudin', 'euismod', 'eget', 'pretium', 'vulputate', 'urna', 'arcu', 'porttitor', 'quam', 'condimentum', 'consequat', 'tempus', 'hac', 'habitasse', 'platea', 'dictumst', 'sagittis', 'gravida', 'eu', 'commodo', 'dui', 'lectus', 'vivamus', 'libero', 'vel', 'maximus', 'pellentesque', 'efficitur', 'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora', 'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'himenaeos', 'fermentum', 'turpis', 'donec', 'magna', 'porta', 'enim', 'curabitur', 'odio', 'rhoncus', 'blandit', 'potenti', 'sodales', 'accumsan', 'congue', 'neque', 'duis', 'bibendum', 'laoreet', 'elementum', 'suscipit', 'diam', 'vehicula', 'eros', 'nam', 'imperdiet', 'sem', 'ullamcorper', 'dignissim', 'risus', 'aliquet', 'habitant', 'morbi', 'tristique', 'senectus', 'netus', 'fames', 'nisl', 'iaculis', 'cras', 'aenean'];

	if (is_array($count)){
		$count = random_int($count[0], $count[1]);	
	}else if (!$count){
		$count = random_int(3, 10);
	}

	$args = _args($args, array(
		'paragraphs' => 0,
	));
	
	// TODO add paragraphs (see this https://stackoverflow.com/questions/20633310/how-to-get-random-text-from-lorem-ipsum-in-php)

	$index = random_int(0, count($lorem) - 1);
	$words = array();
	while ($count>0){
		if (!array_key_exists($index, $lorem)){
			$index = 0;
		}

		$words[] = $lorem[$index];
		
		$index++;
		$count--;
	}

	$text = implode(' ', $words);

	return $text;
}

function to_duration ($str){
	if (is_numeric($str)) return (float)$str;
	if (!is_string($str)) return 0;

	$time = (float)$str;
	if (strpos($str, 'min') !== false){
		$time *= 60000;
	}else if (strpos($str, 'hour') !== false){
		$time *= 3.6e+6;
	}else if (strpos($str, 'day') !== false){
		$time *= 8.64e+7;
	}else if (strpos($str, 'week') !== false){
		$time *= 6.048e+8;
	}else if (strpos($str, 'month') !== false){
		$time *= 2.628e+9;
	}else if (strpos($str, 'year') !== false){
		$time *= 3.154e+10;
	}else if (strpos($str, 'ms') !== false){
		$time = $time;
	}else if (strpos($str, 's') !== false){
		$time *= 1000;
	}

	return is_nan($time) ? 0 : $time;
}

// Parse/Decode ----------------------------------------------------------------
function string_decode ($str, $args=''){
	if ($args === 'style' || $args === 'css'){
		$args = array(
			'join'   => ';',
			'pair'   => ':',
			'prefix' => '{',
			'suffix' => '}',
			'parse'  => false,
		);
	}else if ($args === 'attributes' || $args === 'attrs'){
		$args = array(
			'join'     => '/([a-z][a-z_0-9-]*(?:\=(\"?[^"]*\"?))?)/',
			// 'join'     => '/([a-z][a-z_0-9-]*(?:\=((\"[^"]*\")|(\\\'[^\']*\\\')))?)/',
			'pair'     => '=',
			'prefix'   => '[',
			'suffix'   => ']',
			'fallback' => true,
		);
	}else if ($args === 'query'){
		$args = array(
			'join'     => '&',
			'pair'     => '=',
			'prefix'   => '[',
			'suffix'   => ']',
			'fallback' => true,
		);
	}

    $args = to_args($args, array(
		'join'     => ';',
		'pair'     => ':',
		'prefix'   => '',
		'suffix'   => '',
		'fallback' => null,
		'parse'    => true,
    ));

	if (isset($args['separator'])){
		$args['join'] = $args['separator'];
	}
	
	$str  = str_replace(array($args['prefix'], $args['suffix']), '', $str);
	$str  = string_split($str, $args['join']);
	
	$list = array();
	foreach ($str as $item){
		$pair 		= string_split($item, $args['pair'], 2);
		$key 		= trim($pair[0]);
		$value 		= isset($pair[1]) ? $pair[1] : $args['fallback'];
        $list[$key] = $args['parse'] ? to_value($value) : $value;
	}

	return $list;
}

function string_to_action ($str, $args=null){
	$args = to_args($args, array(
		'type'   => '.',
		'filters'=> ':',
	));

	$str = str_replace(NL, ' ', $str); // remove line break
	$re  = '/([a-z][a-z0_9_-]*)(?:\\'.$args['type'].'([a-z0-9\_\-]+))?(?:\((.+)\))?/i';
	preg_match($re, $str, $matches);

	$name   = '';
	$type   = '';
	$params = '';
	if (count($matches)){
		$str  = str_replace($matches[0], '', $str);
		$name = isset($matches[1]) ? $matches[1] : null;
		$type = isset($matches[2]) ? $matches[2] : null;		
		$params=isset($matches[3]) ? to_values($matches[3]) : array();
	}
	
	// TODO add $filters

	return array(
		'name'   => $name,
		'type'   => $type,
		'params' => $params,
		'filters'=> array(),
	);
}

function string_decode_table ($args=null, $str=null){
	if ($str == null){
		$str  = $args;
		$args = "\t";
	}

	$args = to_args($args, array(
		'row'       => "\n",
		'separator' => "\t",
	), 'separator');

	$rows   = array_each($str, 'string_trim', array('separator'=>$args['row']));
	$items 	= array();
	$header = null;
	
	foreach ($rows as $i=>$row){
		if (strpos($row, $args['separator']) === false) continue;
		
		$row = to_array($row, array(
			'separator' => $args['separator'],
			'filter'    => 'string_trim',
			'parse'		=> true,
		));

		if (!$header){
			$header = $row;
		}else{
			$row 	 = array_fuse($header, $row);
			$items[] = $row;
		}

	}
	
	return $items;
}

function string_decode_structure ($structure, $args=array(), $data=array()){
	/*
	example: 

		string_decode_structure('
			name .callback(1, 2) :filter(true),
			age,
			gender,
			children{
				height,
				width
			}
		');

	*/

	if (is_array($structure)) return $structure;

	$structure = $structure === true ? '*' : $structure;

	$args = to_args($args, array(
		'separator'	     => ',',
		'separator_down' => '{',
		'separator_up'	 => '}',
		'group_start'	 => '(',
		'group_end'		 => ')',
		'format'	     => true,	// true : auto decoding, or a function
		'data'			 => $data,
	));

	$str 	  = preg_replace('/\t|\n/', '', $structure);
	$pos      = 0;
	$last_pos = strlen($str);
	$indexes  = array();
	$limit 	  = 500;
	$depth 	  = 0;
	$items 	  = array();

	while($pos < $last_pos && $limit > 0){
		$limit--;

		$next_sep 		= strpos($str, $args['separator'], $pos);
		$next_down 		= strpos($str, $args['separator_down'], $pos);
		$next_up 		= strpos($str, $args['separator_up'], $pos);
		$next_group 	= strpos($str, $args['group_start'], $pos);
		$next_group_end = strpos($str, $args['group_end'], $pos);
		$next_pos 		= 0;
		$move 			= null;

		// make sure the next_* are not false (put them to the end if FALSE)
		$next_sep	= $next_sep === false ? $last_pos : $next_sep;
		$next_down 	= $next_down === false ? $last_pos : $next_down;
		$next_up 	= $next_up === false ? $last_pos : $next_up;

		// group of data
		if ($next_group !== false && $next_group_end !== false && math_less_than($next_group, array($next_sep, $next_up, $next_down))){
			$next_sep 	= strpos($str, $args['separator'], $next_group_end);
			$next_down 	= strpos($str, $args['separator_down'], $next_group_end);
			$next_up 	= strpos($str, $args['separator_up'], $next_group_end);

			$next_sep	= $next_sep === false ? $last_pos : $next_sep;
			$next_down 	= $next_down === false ? $last_pos : $next_down;
			$next_up 	= $next_up === false ? $last_pos : $next_up;
		}

		// find the nearest next_*
		if (math_less_than($next_down, array($next_sep, $next_up))){
			$move 		= 'down';
			$next_pos 	= $next_down;
		}elseif (math_less_than($next_up, array($next_sep, $next_down))){
			$move 		= 'up';
			$next_pos 	= $next_up;
		}else{
			$next_pos 	= $next_sep;
		}

		// get the value
		$value = substr($str, $pos, $next_pos - $pos);

		// find the parent to add this item too
		$parent =& $items;
		foreach ($indexes as $index){
			$parent =& $parent[$index]['children'];
		}
		$index = count($parent);

		// add the items to the current $parent
		if ($value){
			// create the item, then format it if format() function exists
			$item = array();

			if ($args['format'] === true){
				// old format
				// auto decoding = {key}:{action({params}) | {filter({params})}
				// $matches       = to_match($value, '/^([^:|(]+)(?:\:([^(]+)(?:\(([^)]+)?\))?)?(?:\s*\|([^(]+)(?:\(([^)]+)?\))?)?/', 'multiple=1');

				// TODO add option to have multiple filters
				// auto decoding = {key}.{action({params}) :{filter({params})}

				//_js($value, '/^([^\.|(]+)(?:\.([^(]+)(?:\(([^)]+)?\))?)?(?:\s*\:([^(]+)(?:\(([^)]+)?\))?)?/');

				$matches       = to_match(trim($value), '/^([^\.#(]+)(?:\.([^#(]+))?(?:\#([^(]+))?(?:\(([^)]+)?\))?(?:\s*\:([^(]+)(?:\(([^)]+)?\))?)?/');
				$key           = trim($matches[0]);
				$type          = isset($matches[1]) && $matches[1] ? trim($matches[1]) : null;
				$id     	   = isset($matches[2]) ? trim($matches[2]) : null;
				$params        = isset($matches[3]) ? to_values($matches[3]) : array();
				$filter        = isset($matches[4]) ? trim($matches[4]) : null;
				$filter_params = isset($matches[5]) ? to_values($matches[5]) : array();
				
				if ($args['data']){
					$action_params = __string_decode_structure__params($params, $args['data']);
					$filter_params = __string_decode_structure__params($filter_params, $args['data']);
				}

				$item = array(
					'key'    => $key,
					'type'   => $type,
					'id'     => $id,
					'params' => $params,
					// TODO options to have multiple filters
					'filter'        => $filter,
					'filter_params' => $filter_params,
				);
			}else if (is_callback($args['format'])){
				$item = $args['format']($value, $depth);
			}else{
				$item['value'] = $value;
			}

			$item['children'] = array();
			$parent[] = $item;
		}

		if ($move === 'down'){
			$indexes[] = $index;
			$depth++;
		}else if ($move === 'up'){
			array_pop($indexes);
			$depth--;
		}

		// move the pointer to the next separator
		$pos = $next_pos + 1;

		unset($item);
		unset($parent);
	}

	// update the keys 
	$items = array_traverse($items, null, function ($item){
		$item['children'] = array_set_keys($item['children'], 'key');
		return $item;
	});
	$items = array_set_keys($items, 'key');

	return $items;
}

function __string_decode_structure__params ($params, $data){
	if (!is_array($data)){
		return $params;
	}

	foreach ($params as $i => $v){
		if ($v[0] !== '$') continue;

		$v = substr($v, 1);
		$v = isset($data[$v]) ? $data[$v] : null;
		
		$params[$i] = $v;
	}

	return $params;
}


