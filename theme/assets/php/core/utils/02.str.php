<?php 
/**
 * @package Utils\String
 */

/* =====================================================================================================================
Quick
===================================================================================================================== */
/**
 * Check if it's a valid string-like value, then convert it to a string, return false if not valid.
 *
 * @param mixed $v 
 * @return string
 */
function _str ($v){
	return (
		is_numeric($v)
		|| (is_string($v) && $v)
		|| (is_object($v) && method_exists($v, '__toString'))
	) ? (string)$v : false;
}

/**
 * Simple function to decode the lines of a string, used in {@see ffto_str_decode_pages} and {@see ffto_str_decode_table}.
 * Removes the extra empty lines and remmove the prefix tab/spaces.
 *
 * @param mixed $str 
 * @param mixed $separator 
 * @return array
 */
function _lines ($str, $separator=NL){
	$_lines = explode($separator, $str);
	$lines  = [];
	$prefix = null;
	foreach ($_lines as $i => $line){
		if (!$line) continue;

		// clean up the start of the line
		$prefix = $prefix ? $prefix : _match($line, '/^[\s\t]+/');
		$index  = strpos($line, $prefix) + strlen($prefix);
		$line   = substr($line, $index);
		
		$lines[] = $line;
	}

	return $lines;
}

/* =====================================================================================================================
Conditions
===================================================================================================================== */
/**
 * Check if the string (in it's simplest form) is empty
 *
 * @param mixed $str 
 * @return bool
 */
function ffto_is_empty_string ($str){
	if (!is_string($str)) return false;
	return !ffto_str_strip($str, 'emoji=0');
}

/* =====================================================================================================================
Casting
===================================================================================================================== */
/**
 * Convert a string to utf8 since `utf8_encode` is deprecated
 *
 * @param mixed $str 
 * @return string
 */
function ffto_to_utf8 ($str){
	if (!is_string($str)) return $str;
	if (!mb_check_encoding($str, 'UTF-8')){
		$str = mb_convert_encoding($str, 'UTF-8', mb_list_encodings());
	}
	return $str;
}

/**
 * Convert a filesize string to a number value
 * 
 * ```php
 * ffto_to_bytes('30kb');
 * // 30720
 * ```
 *
 * @param mixed $str 
 * @return int
 */
function ffto_to_bytes ($str){
	$units  = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	$number = substr($str, 0, -2);
	$suffix = strtoupper(substr($str,-2));
	
	//B or no suffix
	if (is_numeric(substr($suffix, 0, 1))) {
		return preg_replace('/[^\d]/', '', $str);
	}
	
	$exponent = array_flip($units);
	$exponent = isset($exponent[$suffix]) ? $exponent[$suffix] : null;
	if($exponent === null) {
		return null;
	}

	return $number * (1024 ** $exponent);	
}

/**
 * Convert a string time (eg.: 3sec) into a duraction in milliseconds
 *
 * 
 * ```php
 * ffto_to_duration('3sec');
 * // 3000
 * ```
 * 
 * @param mixed $str 
 * @return int
 */
function ffto_to_duration ($str){
	if (is_numeric($str)) return (float)$str;
	if (!is_string($str)) return 0;

	$time = (float)$str;

	if (strpos($str, 'min') !== false)			$time *= 60000;
	elseif (strpos($str, 'hour') !== false)		$time *= 3.6e+6;
	elseif (strpos($str, 'day') !== false)		$time *= 8.64e+7;
	elseif (strpos($str, 'week') !== false)		$time *= 6.048e+8;
	elseif (strpos($str, 'month') !== false)	$time *= 2.628e+9;
	elseif (strpos($str, 'year') !== false)		$time *= 3.154e+10;
	elseif (strpos($str, 'ms') !== false)		$time = $time;
	elseif (strpos($str, 's') !== false)		$time *= 1000;

	return is_nan($time) ? 0 : $time;
}

// [ ] Add "ffto_to_duration_string" and rename "ffto_to_duration" to "ffto_to_duration_time"


/**
 * Tries to get the single and plural verion of a string. This only works on English text.
 *
 * ```php
 * ffto_to_variants('Lorem');
 * // ['single'=>'Lorem', 'plural'=>'Lorems']
 * ```
 * 
 * @param mixed $str 
 * @param mixed $return 
 * 	- 'single': single value
 * 	- 'plural': plural value
 *  - null: return object with both single and plural
 * @return mixed
 */
function ffto_to_variants ($str, $return=null){
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
	
	return $return ? _get($variants, $return) : $variants;
}

/**
 * Generate dummy text
 * 
 * ```php
 * ffto_to_lorem();
 * // consequat tempus hac habitasse platea dictumst sagittis gravida
 * 
 * ffto_to_lorem(3, 10);
 * // tincidunt a integer facilisis lacinia
 * 
 * ffto_to_lorem('p');
 * // <p>eros nam imperdiet sem ullamcorper dignissim risus...</p>
 * // <p>nunc nec pulvinar quisque ut semper auctor tortor mollis est...</p>
 * // <p>vi...</p>
 * 
 * ffto_to_lorem('ul');
 * // <ul>
 * // <li>mattis volutpat justo...</li>
 * // <li>leo feugiat nibh tincidunt...</li>
 * // ...
 * // </ul>
 * ```
 * 
 * @todo finish the decorate text
 * @todo add a way to add MANY dummy things (eg.: a p, ul, blockquote, ...)
 * @todo add different types, like ":email", ":url", ":name", ":country", ":city", ":phone", ":postal_code", ":zip_code", ....
 * @todo rename "to_dummy_text" or something like that
 *
 * @param mixed $args 
 * 	- 'tag' [null] Wrap the text with a tag.
 * 	- 'min' [$min] Minimum words
 * 	- 'max' [$max] Maximum words
 * 	- 'group' [null] Groups the words to wrap with a tag
 * 	- 'decorate' [false] Add link, bold, italic, sup, sub and underline
 * 	shortcuts:
 * 	- 'p' paragraphs
 * 	- 'ol' ol list
 * 	- 'ul' list
 * 	- 'dl' data list
 * 	- 'table' table 
 * 	- 'long' long text (400 to 600 characters)
 * 	- 'medium' medium text (150 to 250 characters)
 * 	- 'short' short text (10 to 20 characters)
 * @param mixed $min 
 * @param mixed $max 
 * @return string
 */
function ffto_to_lorem ($args=null, $min=null, $max=null){
	if (is_numeric($args)){
		$max  = $min;
		$min  = $args;
		$args = null;
	}
	
	// shortcuts
	if ($args === 'p'){
		$args = ['tag'=>'p', 'min'=>150, 'max'=>200, 'group'=>[60,90], 'decorate'=>true];
	}else if ($args === 'ol'){
		$args = ['tag'=>'ol', 'min'=>60, 'max'=>100, 'group'=>[5,10]];
	}else if ($args === 'ul'){
		$args = ['tag'=>'ul', 'min'=>60, 'max'=>100, 'group'=>[5,10]];
	}else if ($args === 'dl'){
		$args = ['tag'=>'dl', 'min'=>30, 'max'=>50, 'group'=>[4,6]];		
	}else if ($args === 'table'){
		$args = ['tag'=>'table', 'min'=>100, 'max'=>150, 'group'=>[4,6]];		
	}else if ($args === 'long'){
		$args = ['min'=>400, 'max'=>600];
	}else if ($args === 'medium'){
		$args = ['min'=>150, 'max'=>250];
	}else if ($args === 'short'){
		$args = ['min'=>10, 'max'=>20];
	}

	$args = _args($args, [
		'tag'      => null,
		'min'      => 4,
		'max'      => 10,
		'group'    => null,
		'decorate' => false,
	], 'tag');

	// either a array that return words between those 2 values
	$min   = is_numeric($min) ? $min : $args['min'];
	$max   = is_numeric($max) ? $max : $args['max'];
	$max   = $max < $min ? $min : $max;
	$count = random_int($min, $max);

	$lorem = [
		'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum', 
		'dictum', 'mi', 'non', 'egestas', 'nulla', 'in', 'lacus', 'sed', 'sapien', 'placerat', 'malesuada', 'at', 
		'erat', 'etiam', 'id', 'velit', 'finibus', 'viverra', 'maecenas', 'mattis', 'volutpat', 'justo', 'vitae', 
		'vestibulum', 'metus', 'lobortis', 'mauris', 'luctus', 'leo', 'feugiat', 'nibh', 'tincidunt', 'a', 'integer', 
		'facilisis', 'lacinia', 'ligula', 'ac', 'suspendisse', 'eleifend', 'nunc', 'nec', 'pulvinar', 'quisque', 'ut', 
		'semper', 'auctor', 'tortor', 'mollis', 'est', 'tempor', 'scelerisque', 'venenatis', 'quis', 'ultrices', 
		'tellus', 'nisi', 'phasellus', 'aliquam', 'molestie', 'purus', 'convallis', 'cursus', 'ex', 'massa', 'fusce', 
		'felis', 'fringilla', 'faucibus', 'varius', 'ante', 'primis', 'orci', 'et', 'posuere', 'cubilia', 'curae', 'proin', 
		'ultricies', 'hendrerit', 'ornare', 'augue', 'pharetra', 'dapibus', 'nullam', 'sollicitudin', 'euismod', 'eget', 'pretium', 
		'vulputate', 'urna', 'arcu', 'porttitor', 'quam', 'condimentum', 'consequat', 'tempus', 'hac', 'habitasse', 'platea', 
		'dictumst', 'sagittis', 'gravida', 'eu', 'commodo', 'dui', 'lectus', 'vivamus', 'libero', 'vel', 'maximus', 'pellentesque', 
		'efficitur', 'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora', 'torquent', 'per', 'conubia', 'nostra', 'inceptos', 
		'himenaeos', 'fermentum', 'turpis', 'donec', 'magna', 'porta', 'enim', 'curabitur', 'odio', 'rhoncus', 'blandit', 'potenti', 
		'sodales', 'accumsan', 'congue', 'neque', 'duis', 'bibendum', 'laoreet', 'elementum', 'suscipit', 'diam', 'vehicula', 'eros',
		'nam', 'imperdiet', 'sem', 'ullamcorper', 'dignissim', 'risus', 'aliquet', 'habitant', 'morbi', 'tristique', 'senectus', 
		'netus', 'fames', 'nisl', 'iaculis', 'cras', 'aenean'
	];

	// get random 'lorem ipsum'
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

	// decorate
	$decorate = $args['decorate'];

	// TODO add decorations (see this https://stackoverflow.com/questions/20633310/how-to-get-random-text-from-lorem-ipsum-in-php)
	$_decorate = function ($text) use ($decorate){
		if (!$decorate) return $text;

		// TODO add extra stuff
		// $words = explode(' ', $text);
		// $count = count($words);
		// $min   = random_int(0, floor($count/4));
		// $max   = random_int($min, floor($count/3));
		
		// p($min, $max);
		// $tag   = _random(['a','strong','em','u']);
		// $text  = array_slice($words, 0, $min) + ["<{$tag}>"] + array_slice($words, $min, $max-$min) + ["</{$tag}>"] + array_slice($words, $max);
		// p($text);

		return $text;
	};
	
	// wrap text with a tag
	$_tag = function ($tag, $text, $nl=false) use ($_decorate){
		$nl   = $nl ? NL : '';
		$text = $_decorate($text);
		return '<'.$tag.'>'.$nl.$text.$nl.'</'.$tag.'>';
	};

	// group the text into a bunch a tags and a parent tag 
	$_rows = function ($parent, $tags, &$groups, $max=null) use ($_tag){
		$tags  = _array($tags);
		$rows  = [];
		$index = 0;

		while ($groups){
			// break the loop
			if ($max && $index >= $max) break;

			$row = [];
			foreach ($tags as $tag){
				$t     = array_shift($groups);
				$t     = $_tag($tag, $t);
				$row[] = $t;
			}
			$rows[] = implode('', $row);
			$index++;
		}
		
		if ($parent){
			$rows = implode(NL, $rows);
			$rows = $_tag($parent, $rows);
		}

		return $rows;
	};

	$text = '';
	if ($tag = $args['tag']){
		// break the content into a bunch of words
		if ($group = $args['group']){
			$min    = _get($group, 0);
			$max    = _get($group, 1, $max);
			$groups = [];

			while (count($words)){
				$count    = random_int($min, $max);
				$group    = array_splice($words, 0, $count);
				$group    = implode(' ', $group);
				$groups[] = $group;
			}
		}else{
			$groups = implode(' ', $words);
			$groups = [$groups];
		}

		// ol/ul
		if ($tag === 'ol' || $tag === 'ul'){
			$text = $_rows($tag, 'li', $groups);
		// table
		}else if ($tag === 'table'){
			$head = $_rows('tr', 'th,th,th,th,th', $groups, 1);
			$body = [];

			while ($groups){
				if (count($groups) < 5) break;
				$body[] = $_rows('tr', 'td,td,td,td,td', $groups, 1);
			}
			
			$head = '<thead>'.NL.$head.NL.'</thead>';
			$body = '<tbody>'.NL.implode(NL, $body).NL.'</tbody>';
			$text = '<table>'.NL.$head.NL.$body.NL.'</table>';
		// dl
		}else if ($tag === 'dl'){
			$max  = floor(count($groups) / 2);
			$text = $_rows('dl', 'dt,dd', $groups, $max);
		// other tags
		}else{
			$text = $_rows(false, $tag, $groups);
			$text = implode(NL, $text);
		}
	}else{
		$text = implode(' ', $words);
		$text = $_decorate($text);
	}

	return $text;
}

// TODO add "ffto_to_lorem_group", to create something like a repeater
// TODO add "to_dummy_content", so it creates multiple types (eg.: a string, an int, an object with X stuff in it)

/* =====================================================================================================================
Functions
===================================================================================================================== */
/**
 * An upgraded version of trim(). Deals with other hidden characters. For example, multibytes that won't trim = "\u00a0", and zero width characters.
 * Will also simplify the multi-spaces
 * 
 * ```php
 * ffto_str_trim(' asdf   sdf asdf');
 * // "asdf   sdf asdf"
 * 
 * ffto_str_trim(' asdf   sdf asdf', true);
 * // "asdf sdf asdf"
 * ```
 *
 * @param string $str 
 * @param mixed $args 
 * 	- 'newlines' [true] remove newlines
 * 	- 'spaces' [true] remove multi-spaces
 * @return string
 */
function ffto_str_trim ($str, $args=null){
	if (($str = _str($str)) === false) return '';

	$args = _args($args, [
		'newlines' => true,
		'spaces'   => true,
	]);

	$str = preg_replace('/\x{00A0}/u', " ", $str);	// weird space character, replace by a real space

	// remove newlines
	if ($args['newlines']){
		$str = preg_replace("/(\n|\r|\t)/", ' ', $str);
	}
	// simplify spaces
	if ($args['spaces']){
		$str = preg_replace('/ {2,}/', ' ', $str);
	}

	$str = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $str);  // zero width characters
	$str = preg_replace('/^(\s|\t)+|(\s|\t)+$/u', '', $str);          // space/tab before and space/tab after
	
    return $str;
}

/**
 * Similar to implode() but also has the option of an alternate $args.last_glue for the last item and an $args.empty in case the list is empty.
 * 
 * ```php
 * ffto_str_join('Marie, Bob, Joane', ' - ');
 * // "Marie - Bob - Joane"
 * 
 * ffto_str_join('Marie, Bob, Joane', ', ', ' and ');
 * // "Marie, Bob and Joane"
 * 
 * ffto_str_join([], 'empty=No one');
 * // "No one"
 * 
 * $people = [
 *  ['name' => 'John Doe', 'age' => 28, 'gender' => 'male', 'phone' => '555-1234'],
 *  ['name' => 'Jane Smith', 'age' => 32, 'gender' => 'female', 'phone' => '555-5678'],
 *  ['name' => 'Sam Johnson', 'age' => 24, 'gender' => 'male', 'phone' => '555-8765'],
 * 	['name' => 'Lisa Brown', 'age' => 29, 'gender' => 'female', 'phone' => '555-3456'],
 *  ['name' => 'Chris Green', 'age' => 35, 'gender' => 'male', 'phone' => '555-9876'],
 * 	['name' => 'Anna White', 'age' => 22, 'gender' => 'female', 'phone' => '555-5432'],
 * 	['name' => 'Paul Black', 'age' => 31, 'gender' => 'male', 'phone' => '555-6543'],
 * 	['name' => 'Emma Gray', 'age' => 27, 'gender' => 'female', 'phone' => '555-4321'],
 * 	['name' => 'David Blue', 'age' => 40, 'gender' => 'male', 'phone' => '555-8761'],
 * 	['name' => 'Sophia Red', 'age' => 29, 'gender' => 'female', 'phone' => '555-2345']
 * ];
 * 
 * ffto_str_join($people, [
 * 	'get'    => 'name',
 * 	'last'   => ' and ',
 * 	'before' => '[',
 * 	'after'  => ']'
 * ]);
 * 
 * // "[John Doe, Jane Smith, Sam Johnson, Lisa Brown, Chris Green, Anna White, Paul Black, Emma Gray, David Blue and Sophia Red]"
 * ```
 * 
 * @todo maybe use a template instead of before and after, and add a simple set of vars (like $count)
 *
 * @param array $items 
 * @param mixed $args 
 * 	- 'get' 	[null] Return a specific value from a array/object 
 *  - 'glue' 	[', '] Default glue to use between items
 *  - 'last' 	[$last_glue]
 *  - 'before'  [null]
 *  - 'after' 	[null]
 *  - 'empty' 	[null]
 * @param string $last_glue 
 * @return string
 */
function ffto_str_join ($strs, $args=null, $last_glue=null){
	$strs  = _array($strs);
	$count = count($strs);

	$args = _args($args, [
		'get'    => null,
		'glue'   => ', ',
		'last'   => $last_glue,
		'before' => null,
		'after'  => null,
		'empty'  => null,
	], 'glue');

	if ($get = $args['get']){
		$strs = _each($strs, ['return'=>$get]);
	}

	$str = null;
	if (!$count && $args['empty']){
		$str = $args['empty'];
	}else if (($last = $args['last']) && $count > 1){
		$_last = array_pop($strs);
		$str   = implode($args['glue'], $strs);
		$str   = "{$str}{$last}{$_last}";
	}else{
		$str = implode($args['glue'], $strs);
	}

	// add before and after strings
	if ($count){
		if ($v = $args['before']) 	$str = "{$v}{$str}";
		if ($v = $args['after']) 	$str = "{$str}{$v}";
	}

	return $str;
}

/**
 * Function that strip clean a string by returning a string at it's simplest. Removes comments, script tags, html tags, <br> are replaced with spaces.
 *
 * ```php
 * ffto_str_strip('<!-- cool --> asdf 🤣 <b>sdf</b> asdf');
 * // "asdf sdf asdf"
 * 
 * ffto_str_strip('<!-- cool --> asdf 🤣 <b>sdf</b> asdf', 'emoji=0');
 * // "asdf 🤣 sdf asdf"
 * ```
 * @param string $str 
 * @param mixed $args 
 * 	- 'comments' [true] remove html comments 
 * 	- 'scripts' [true] remove html <script>...</script>
 * 	- 'tags' [true] remove html tags and simplify <br> to simple space
 * 	- 'emoji' [false] remove emoji
 * @return string
 */
function ffto_str_strip ($str, $args=null){
	if (($str = _str($str)) === false) return '';

	$args = _args($args, [
		'comments' => true,
		'scripts'  => true,
		'tags'     => true,
		'emoji'    => false,
	]);

	// remove comments
	if ($args['comments']){
		$str = preg_replace('/<!--(.|s)*?-->/', '', $str);                 
	}

	// remove scripts
	if ($args['scripts']){
		$str = preg_replace('/\<script[\s|\S]+?\<\/script\>/', '', $str);
	}
	
	// br are replaced and remove other tags
	if ($args['tags']){
		$str = preg_replace('/<br ?\/?>/', ' ', $str);  // remove <br>
		$str = strip_tags($str);                        // remove html tags
	}
	
	if ($args['emoji']){
		$str = iconv('UTF-8', 'ISO-8859-15//IGNORE', $str);
		$str = preg_replace('/\s+/', ' ', $str);
		$str = iconv('ISO-8859-15', 'UTF-8', $str);
	}

	// trim the rest 	
	$str = ffto_str_trim($str, true); 

	return $str;
}

/**
 * Replace all accents with their none-accent version
 *
 * ```php
 * ffto_str_strip_accents('C\'étais un hiver à mourir.');
 * // "C'etais un hiver a mourir."
 * ```
 * @param mixed $str 
 * @param string $charset 
 * @return string
 */
function ffto_str_strip_accents ($str, $charset='utf-8'){
	if (($str = _str($str)) === false) return '';
	$str = htmlentities($str, ENT_NOQUOTES, $charset);
    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
    $str = preg_replace('#&[^;]+;#', '', $str);    
	return $str;
}

/**
 * Clean HTML found by removing a bunch of stuff.
 *
 * ```php
 * str_clean_html('<!-- comment --> Cool <b width="100" data-size="4" style="color:red;"></b>', 'attrs=1&data=1');
 * // "Cool <b width="100" data-size="4"></b>"
 * ```
 * 
 * @param mixed $str 
 * @param mixed $args 
 * 	- 'script' [false] remove <script> tags
 * 	- 'comments' [true] remove html comments
 * 	- 'p_empty' [true] remove empty <p>
 * 	- 'p_nbsp' [false] remove empty <p> that looks empty because they have &nbsp; inside
 * 	- 'attrs' [false] remove attributes (some are keeped by default), if this is a string, you can keep more attributes
 * 	- 'style' [false] keep the 'style' attribute, a function can be used to filter the style value
 * 	- 'data' [false] keep the 'data-*" attributes
 * @return string
 */
function ffto_str_clean_html ($str, $args=null){
	if (($str = _str($str)) === false) return '';

	if ($args === true){
		$args = [
			'script'   => true,
			'comments' => true,
			'p_empty'  => true,
			'p_nbsp'   => true,
			'attrs'    => true,
		];
	}

	$args = _args($args, [
		'script'   => false,
		'comments' => true,
		'p_empty'  => true,
		'p_nbsp'   => false,
		'attrs'    => false,
		'style'    => false,	// function for formatting the 'style' attribute
		'data'     => false,	// keep the "data-*" attributes
	]);
	
	// remove <script> tags
	if ($args['script']){
		$str = preg_replace('/\<script[\s|\S]+?\<\/script\>/', '', $str);
	}
	
	// remove comments
	if ($args['comments']){
		$str = preg_replace('/<!--(.|s)*?-->/', '', $str); 	
	}

	// remove multiple line breaks
	$str = preg_replace("/[\r\n]+/", "\n", $str);

	if ($args['p_empty']){
		$str = preg_replace("/\<p.*?\>[\s\r\t\n]*\<\/p\>/", '', $str); // remove empty <p>
	}
		
	if ($args['p_nbsp']){
		$str = preg_replace("/\<p.*?\>.*\&nbsp;.*<\/p\>/", '', $str);  // remove empty <p> with &nbsp; inthem
	}
	
	if ($attrs = $args['attrs']){
		$keep = explode(',', 'alt,colspan,controls,width,height,href,id,loop,muted,playsinline,preload,reversed,role,rowspan,scope,sizes,span,src,srclang,srcset,start,target,title,type,usemap');
		
		// add more attrs to keep
		if (is_string($attrs) || is_array($attrs)){
			$attrs = _array($attrs);
			$keep  = array_merge($keep, $attrs);
		}		
		
		$str = preg_replace_callback('/\<([a-z][a-z_-]*)(\s[^\>]+)\>/', function ($m) use ($args, $keep){
			$tag   = $m[1];
			$attrs = $m[2];
			$attrs = _match($attrs, '/\s([a-z][a-z_-]*)="([^"]+)"/', true, true);
			$attrs = _each($attrs, function ($v) use ($args, $keep){ 
				$i = $v[0];
				$v = $v[1];

				// if it's "style", a function can be used to filter some values out, if it's true, just keep the style
				if ($args['style'] && $i === 'style'){
					$v = _apply($args['style'], $v);
				}else if ($args['data'] && strpos($i, 'data') === 0){
					$v = $v;
				}else if (!in_array($i, $keep)){
					$v = '';
				}

				if (is_not($v)){
					return '';
				}

				return $i.'="'.$v.'"';
			}, ' ');

			$attrs = $attrs ? " {$attrs}" : '';
			$tag   = "<{$tag}{$attrs}>";

			return $tag;
		}, $str);
	}

	return $str;
}

/**
 * Separator a string into multiple pieces
 *
 * ```php
 * ffto_str_separate('asdf sdf asdf');
 * // ["asdf", "sdf", "asdf"]
 * 
 * ffto_str_separate('asdf sdf asdf', 2);
 * ["as","df"," s","df"," a","sd","f"]
 * ```
 * 
 * @param mixed $str 
 * @param string $separator 
 * @param int $limit 
 * @return array
 */
function ffto_str_separate ($str, $separator=' ', $limit=0){
	if (($str = _str($str)) === false) return [];

	$v = [];

	if (ffto_is_callback($separator)){
		$v = _apply($separator, $str);
	}else if (ffto_is_regexp($separator)){
		// TODO change this to split on a match, or something like that. Since the current version can be done with `_match($re, null, true)`	
		// preg_replace_callback($separator, function ($m) use (&$v){ $v[] = $m[0]; }, $str);		
	}else if (is_int($separator)){
		$v = str_split($str, $separator);
	}else if (is_string($separator)){
		$v = explode($separator, $str, $limit ? $limit : PHP_INT_MAX);
	}

	return $limit ? array_slice($v, 0, $limit) : $v;
}

/**
 * Truncate a string, either to a limit of letters, letters (but keep the last word) or words.
 * 
 * ```php
 * ffto_str_truncate('Lorem ipsum dolor, sit amet consectetur adipisicing elit. Ipsum dolor magni, reiciendis');
 * // "Lorem ipsum dolor, sit amet consectetur adipisicin…"
 * 
 * ffto_str_truncate('Lorem ipsum dolor, sit amet consectetur adipisicing elit. Ipsum dolor magni, reiciendis', 'limit=10&type=word');
 * // "Lorem ipsum dolor, sit amet consectetur adipisicing elit. Ipsum dolor…"
 * ```
 *
 * @param string $str
 * @param array $args
 * 	- 'type' [letter] truncate by letters/words
 * 		- 'letter': count the letters
 * 		- 'word': count the words
 * 		- 'letter-word': count the letters but keep the last word intact
 * 	- 'limit' [50] count of items to keep
 * 	- 'ellipsis' […] add this suffix if it cuts the text
 * 	- 'wrap' [false] wrap the truncated text with a html tag, with `__html()` 
 * @return string
 */
function ffto_str_truncate ($str, $args=''){
	if (($str = _str($str)) === false) return '';

	if (is_numeric($args)){
        $args = ['limit'=>$args];
	}

	$args = _args($args, array(
		'type'     => 'letter',   // [letter, letter-word, word]
		'limit'    => 50,
		'ellipsis' => '…',
		'wrap'     => false,      // wrap with a tag (span by default)
	));
	
	$str   = ffto_str_strip($str, true);
	$_str  = $str;
	$type  = $args['type'];
	$limit = $args['limit'];

	if ($type === 'word'){
		$str = explode(' ', $str);
		$str = implode(' ', array_splice($str, 0, $limit));
	}else{
		$str = substr($str, 0, $limit + 1);
		
		if ($type === 'letter-word'){
			$last = strrpos($str, ' ');
			$last = $last === false ? strpos($str, ' ') : $last;
			$last = $last === false ? strlen($str) : $last;
			$str  = substr($str, 0, $last);
		}else{
			$str = substr($str, 0, $limit);
		}
	}

	if ($_str != $str){
		$str = ffto_str_trim($str) . $args['ellipsis'];
	}

	if ($args['wrap']){
		$str = __html('span', $args['wrap'], [
			'title' => $_str,
			'html'	=> $str,
		]);
	}

	return $str;
}

/**
 * Search and select a part of the text, and truncate the text.
 *
 * ```php
 * ffto_str_highlight('Lorem ipsum dolor sit amet <strong>consectetur</strong> été adipisicing elit. Fuga, nisi cum sunt modi perspiciatis necessitatibus', 'nisi cum', 100);
 * // '…it. Fuga, <strong>nisi cum</strong> sunt modi perspiciatis necessitatibus'
 * 
 * ffto_str_highlight('Lorem ipsum dolor sit amet <strong>consectetur</strong> été adipisicing elit. Fuga, nisi cum sunt modi perspiciatis necessitatibus', '/é[^ ]+/');
 * // …nsectetur <strong>été</strong> adipisicing elit. Fuga, nisi cum sunt modi perspiciatis necessitatibus
 * ```
 * 
 * @param mixed $str 
 * @param mixed $search 
 * @param mixed $args 
 * 	- 'tag' ['strong'] Tag to wrap the highlighted text
 * 	- 'ellipsis' ['…'] Ellipsis added before and after the truncated text
 * 	- 'before' [10] How many characters before the found text
 * 	- 'limit' [200] How many characters after the found text
 * 	- 'return' [$return] return either full and experpt, or both
 * @param mixed $return 
 * @return string
 */
function ffto_str_highlight ($str, $search, $args=null, $return=null){
	$args = _args($args, array(
        'tag'      => 'strong',
        'ellipsis' => '…',
        'before'   => 10,
        'limit'    => 200,
		'return'   => $return,
	), 'limit');

	$str = html_entity_decode($str);
	$str = ffto_str_strip($str);

	// find the search text
	$index  = null;
	$length = null;

	// find the search text with a regexp
	if (ffto_is_regexp($search)){
		$search = _match($str, $search);
	}
	
	if ($search){
		$index  = mb_stripos($str, $search);
		$length = mb_strlen($search);
	}

	$excerpt = $str;
	// highlight the text
	if ($index !== false){
		$full_length = mb_strlen($str);
		
		$start  = $index - $args['before'];
		$end    = $index + $length + $args['limit'];
		$before = $start > 0 ? $args['ellipsis'] : '';
		$after  = $end < $full_length ? $args['ellipsis'] : '';

		$start   = max($start, 0);
		$end     = max($end, $full_length);
		$excerpt = $before . mb_substr($str, $start) . $after;

		// replace the searched text with a tag
		if ($args['tag']){
			$replace = __html('strong', $args['tag'], ['html'=>$search]);
			$index   = mb_stripos($excerpt, $search);
			$length  = mb_strlen($search);
			$excerpt = ($index ? mb_substr($excerpt, 0, $index) : '') . $replace . mb_substr($excerpt, $index + $length);
		}
	// normal truncate
	}else{
		$excerpt = ffto_str_truncate($str, [
			'limit'    => $args['limit'],
			'ellipsis' => $args['ellipsis'],
		]);
	}


	if (ffto_return_object($args['return'])){
		return [
			'excerpt' => $excerpt,
			'full'    => $str,
		];
	}else if ($args['return'] === 'full'){
		return $str;
	}else{
		return $excerpt;
	}
}

/* =====================================================================================================================
Decode
===================================================================================================================== */
/**
 * Decode a string depending on a match pattern OR with a separator and a pair separator.
 * 
 * ```php
 * ffto_str_decode("color=red&font-size=14");
 * // ["color"=>"red", "font-size"=>14]
 * 
 * ffto_str_decode("{ color:red; font-size: 14px; }", ':css');
 * // ["color"=>"red", "font-size"=>"14px"]
 * 
 * ffto_str_decode('[color="red"][font-size="14em"]', ':attrs');
 * // ["color"=>"red", "font-size"=>"14em"]
 * ```
 * 
 * @param mixed $str 
 * @param mixed $args 
 * 	- 'prefix' [] prefix to the string to remove
 * 	- 'suffix' [] suffix to the string to remove
 * 	- 'separator' ['&'] item separator
 * 	- 'pair' ['='] pair separator
 * 	- 'match' [false] a match regexp OR callback
 * 	- 'value' [true] convert to value
 * @return array
 */
function ffto_str_decode ($str, $args=null){
	if (($str = _str($str)) === false) return [];

	if ($args === ':css'){
		$args = [
			'separator' => ';',
			'pair'      => ':',
			'prefix'    => '{',
			'suffix'    => '}',
			'decode'     => false,
		];
	}elseif ($args === ':attrs'){
		$args = ['match'=>'/(?<key>[a-z][a-z_0-9-]*?)\=(?<value>(?:\'[^\']+?\')|(?:"[^"]+?"))/'];
	}
	
	$args = _args($args, array(
		'prefix'    => '',
		'suffix'    => '',
		'separator' => '&',
		'pair'      => '=',
		'match'     => false,
		'value'     => true,	// convert to value
	), 'match');
	
	// remove the prefix/suffix
	$str = str_replace([
		$args['prefix'], 
		$args['suffix']
	], '', $str);

	// break appart the values into pairs
	$_arr = [];
	if (ffto_is_regexp($args['match'])){
		$_arr = _match($str, $args['match'], true, true);
	}elseif (ffto_is_callback($args['match'])){
		$_arr = _call($args['match'], $str);
	}elseif ($args['separator']){
		$_arr = ffto_str_separate($str, $args['separator']);
	}

	$arr = [];
	foreach ($_arr as $i => $v){
		$k = null;

		if (is_array($v) && isset($v['key']) && isset($v['value'])){
			$k = $v['key'];
			$v = $v['value'];
		}else if (is_array($v) && isset($v[0]) && isset($v[1])){
			$k = $v[0];
			$v = $v[1];
		}else if (is_string($v)){
			$v    = ffto_str_trim($v);
			$pair = explode($args['pair'], $v);
			$k 	  = isset($pair[1]) ? trim($pair[0]) : null;
			$v 	  = isset($pair[1]) ? trim($pair[1]) : null;
		}

		if (!$k) continue;

		$arr[$k] = $args['value'] ? _value($v) : $v;
	}

	return $arr;		
}

/**
 * Decode a string to output the action, style, params and optional filters. Good for encoding 
 * a function call. The patterns is : $alias > $name.$style($params) :$filters...
 * 
 * ```php
 * str_decode_action('grow(+4)');
 * // {"name"=>"grow", "style"=>"", "params"=>[4], "filters"=>[]}
 * 
 * str_decode_action('grow.small :lower(first, 4) :nvl');
 * // {"name":"grow","style":"small","params":[],"filters":{"lower":["first",4],"nvl":[]}
 * 
 * // str_decode_action('the-key > grow.small :lower(first, 4) :nvl');
 * // {"alias":'the-key', "name":"grow","style":"small","params":[],"filters":{"lower":["first",4],"nvl":[]}
 * ```
 *
 * @param string $str
 * @param array $args
 * 	- 'alias' ['>'] alias separator
 * 	- 'style' ['.'] style separator
 * 	- 'filters' [':'] filters separator
 * 	- 'ctx' [null] context to replace $this value in the params
 * 	- 'ctxs' [null] other context to be replaced in the params
 * @return array
 */
function ffto_str_decode_action ($str, $args=null){
	$args = _args($args, array(
		'ctx'  => null,
		'ctxs' => null,
	));

	/*
	// 	_match($str, "/^(?:(?<alias>[a-z0-9-_]+)\s*\\{$_a}\s*)?(?<name>[a-z][a-z0_9_-]*)(?:\\{$_s}(?<style>[a-z0-9\_\-]+))?(?:\((?<params>.*?)\))?\s*(?<filters>\\{$_f}.+)?".($_e?"(?<end>(?:{$_e}))":'')."/", 'group=1&match=1');
	$alias      = "(?:(?<alias>[a-z0-9-_]+)\s*\\{$_a}\s*)?";
	$name 		= "(?<name>[a-z][a-z0_9_-]*)";
	$style 		= "(?:\\{$_s}(?<style>[a-z0-9\_\-]+))?";
	$params 	= "(?:\((?<params>.*?)\))?\s*";
	$filters	= $_e ? "(?<filters>\\{$_f}.+?)?" : "(?<filters>\\{$_f}.+)?";
	$end 		= $_e ? "(?<end>(?:{$_e}))" : '';
	// $re  		= _match($str, "/^{$alias}{$name}{$style}{$params}{$filters}{$end}/", 'group=1&match=1');
	*/
	// $item = _match($str, '/((?<alias>\slug)\s*>\s*)?(?<name>\slug)?(?:\((?<params>.+?)\)\s*)?(?<filters>\:.+?)?(?<end>(,|\{|\}|$))/', 'group=1&match=1');

	$match = '';
	if (is_array($str)){
		$item = $str;
	}else{
		$str   = str_replace(NL, ' ', $str); // remove line break
		$item  = _match($str, "/^((?<alias>\slug)\s*>\s*)?(?<name>\slug)?(?:\.(?<style>\slug))?(?:\((?<params>.+?)\)\s*)?(?<filters>\:.+?)/", 'group=1&match=1');
		$match = $item['match'];
		$item  = $item['value'];
	}

	$alias  = _get($item, 'alias');
	$name   = _get($item, 'name');
	$style  = _get($item, 'style');
	$params = _get($item, 'params');
	$params = _values($params, ['ctx'=>$args['ctx'], 'ctxs'=>$args['ctxs']]);

	$filters = _get($item, 'filters', '');
	$filters = _match($filters, "/\:(?<name>[a-z0-9\_\-]+)(?:\((?<params>.+)\))?/m", true, true);
	$filters = _each($filters, ['filter'=>false, 'format'=>function ($v) use ($args){
		return [
			'$key'   => $v['name'],
			'$value' => _values($v['params'], ['ctx'=>$args['ctx'], 'ctxs'=>$args['ctxs']]),
		];
	}]);
	
	return [
		'alias'   => $alias,
		'name'    => $name,
		'style'   => $style,
		'params'  => $params,
		'filters' => $filters,
		'$match'  => $match, // text matched
	];
}

/**
 * Decode a string of pages with indent as the depth indicator. If the indent text isn't specified, it will try to decode it
 *
 * ```php
 * ffto_str_decode_pages("
 * 	About
 * 	- Team
 * 	- History
 * 	School
 * 	- Registration
 * 	-- For Adults
 * 	-- For Teens
 * 	Gallery
 * 	- Exhibitions
 * 	- About the Gallery
 * ", 'children=0');
 * // 
 * 
 * ffto_str_decode_pages("
 * 	About
 * 		Team
 * 		History
 * 	School
 * 		Registration
 * 			For Adults
 * 		For Teens
 * 	Gallery
 * "); * 
 * ```
 * 
 * @param mixed $str 
 * @param mixed $args 
 * 	- 'indent' [null] indent characters. Will try to decode it by default, if it's a TAB, 4 spaces or a dash '-'
 * 	- 'format' [$format] formating the page
 * 	- 'children' [null] the page key for the children, leave empty to simplify the page hierarchy
 * @param mixed $format 
 * @return array
 */
function ffto_str_decode_pages ($str, $args=null, $format=null){
	$args = _args($args, [
		'indent'   => null,
		'format'   => $format,
		'children' => null,
	], 'indent');

	$lines        = _lines($str);
	$indent       = $args['indent'] ? ffto_to_regexp($args['indent'], false) : null;
	$children_key = ffto_is_truthy($args['children']) ? 'children' : ($args['children'] ? $args['children'] : null);
	$depths       = [];
	
	// find the parent and depth to each lines
	foreach ($lines as $i => $line){
		// try figuring out the indent (if not already set)
		if (!$indent){
			if (preg_match('/^\-+/m', $line))			$args['indent'] = '-';
			else if (preg_match('/^\s{4}/m', $line))	$args['indent'] = '    ';
			else if (preg_match('/^\t/m', $line))		$args['indent'] = TAB;

			if ($args['indent']){
				$indent = ffto_to_regexp($args['indent'], false);
			}
		}

		// calculate the depth with the indent character(s), could be a tab, 4 spaces, a couple of '-'
		$depth  = 0;
		$parent = 0;
		
		if ($indent){
			$re     = "/^({$indent}+)/";
			$depth  = _match($line, $re);
			$depth  = $depth ? $depth : '';
			$depth  = substr_count($depth, $args['indent']);
			$parent = $depth - 1;
			$line   = preg_replace($re, '', $line);
		}

		$item = [
			'title'  => trim($line),
			'depth'  => $depth,
			'parent' => $depth && isset($depths[$parent]) ? $depths[$parent] : -1,	// set the proper parent, base on the previous item
		];
				
		$items[$i]      = $item;
		$depths[$depth] = $i;
	}

	$_walk = function ($items, $parent_id, $_walk) use ($args, $children_key){
		$pages = [];

		foreach ($items as $i => $item){
			if ($item['parent'] !== $parent_id) continue;

			$title 	  = _apply($args['format'], $item['title']);
			$children = $_walk($items, $i, $_walk);

			// either we want to return a children key, or the formated title returns an array, then the page will be an array with the children key
			if ($args['children'] || is_array($title)){
				$page     = is_array($title) ? $title : ['title'=>$title];
				$k        = $children_key ? $children_key : 'children';
				$page[$k] = $children;
				$pages[]  = $page;
			// the title is the key, the children is the children
			}else if ($children){
				$pages[$title] = $children;
			// no children, so add the title as a single children
			}else{
				$pages[] = $title;
			}
		}

		return $pages;
	};
	
	$pages = $_walk($items, -1, $_walk);

	return $pages;
}

/**
 * Decode a string to a table format.
 *
 * ```php
 * ffto_str_decode_table("
 * 	Page	| Count		| Note
 * 	About	| 4			| Needs to be added
 * 	Contact | 1		 	| Delete
 * 	Info	| 3			| -
 * ");
 * ```
 * 
 * @todo Maybe add the row index in the item, ...
 * 
 * @param mixed $str 
 * @param mixed $args 
 * 	- 'headers' [true] Use headers as keys for the item (true = the first row is the headers, string/array = specified keys)
 * 	- 'separator' ['|'] Columns separator
 * @return array
 */
function ffto_str_decode_table ($str, $args=null){
	$args = _args($args, array(
		'headers'	=> true,
		'separator' => '|',
	), 'separator');

	$lines   = _lines($str);
	$headers = null;

	if ($args['headers'] === true){
		$headers = null;
	}else if ($args['headers']){
		$headers = _array($args['headers']);
	}

	$items = [];
	foreach ($lines as $i => $line){
		// decode the cells
		$cells = explode($args['separator'], $line);
		$cells = array_map('trim', $cells);
		
		if ($headers === null){
			$headers = $cells;
			continue;
		}
		
		$cells = _values($cells);
		$item  = $headers ? ffto_arr_fuse($headers, $cells) : $cells;

		$items[] = $item;
	}
	
	return $items;
}

/**
 * Decode a string graph (similar to GraphQL). Helpful for querying a database through ajax calls.
 * 
 * ```php
 * // simple query
 * str_decode_graph("name, gender, c > children{ name }");
 * 
 * // added a context (ctx) to replace $this with
 * str_decode_graph('name($this), gender, c > children{ name }', [
 * 	'ctx' => new DateTime(),
 * ]);
 * 
 * // More complex graph query
 * str_decode_graph("{
 * 	my-name > name('John') :filter(true),
 * 	their-age > age,
 * 	children{
 * 		height :min(45),
 * 		width,
 * 		colors{
 * 			bg(blue),
 * 			fg,
 * 		},
 * 	}
 * 	gender
 * }");
 * ```
 *
 * @param mixed $str 
 * @param mixed $args 
 * @return array
 */
function ffto_str_decode_graph ($str, $args=null){
	if (is_array($str)) return $str;

	$str    = preg_replace('/\t|\n/', '', $str);
	$str2   = _match($str, '/^\{(.+)\}$/'); // remove the "maybe" first and last '{' and '}'
	$str    = $str2 ? $str2 : $str;

	$limit  = 200; // failsafe 
	$depth  = 0;
	$depths = [];
	$items  = [];

	while($str && $limit > 0){
		$limit--;

		// match: "alias > name(params) :filters"
		$item = _match($str, '/((?<alias>\slug)\s*>\s*)?(?<name>\slug)?(?:\((?<params>.+?)\))?\s*(?<filters>\:.+?)?(?<end>(,|\{|\}|$))/', 'group=1&match=1');
		if (!$item['match']) break;

		$match = $item['match'];
		$item  = $item['value'];
		$end   = _get($item, 'end');

		// decode the item to get the name, alias, params and filters
		$item = ffto_str_decode_action($item, $args);
		
		// an action item has been found, so add it
		if ($item['name']){
			$item['parent_id'] = $depth && isset($depths[$depth - 1]) ? $depths[$depth - 1] : -1;
			$items[]           = $item;
		}

		// go up/down the depth
		if ($end === '{')		$depth++;
		else if ($end === '}')	$depth--;

		// save the latest item depth
		$depths[$depth] = count($items);
		
		// move to the next action item
		$cut = strlen($match);
		$str = trim($str);
		$str = substr($str, $cut);
	}

	$_walk = function ($items, $_walk, $parent_id=-1){
		$_items = [];

		foreach ($items as $i => $item){
			if ($item['parent_id'] !== $parent_id) continue;

			$_items[] = [
				'alias'    => $item['alias'],
				'name'     => $item['name'],
				'params'   => $item['params'],
				'filters'  => $item['filters'],
				'children' => $_walk($items, $_walk, $i),
			];
		}

		return $_items;
	};

	$items = $_walk($items, $_walk);
	return $items;
}