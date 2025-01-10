<?php 
// simple translations here -
/* =====================================================================================================================
Quick
===================================================================================================================== */
function __t ($text){
	$a = func_get_args();
	return call_user_func_array('ffto_translate', $a);
}

function __tx ($ctx, $text){
	$a = func_get_args();
	$a = [
		$text, 
		isset($a[2]) ? $a[2] : null,
		isset($a[3]) ? $a[3] : null,
		$ctx,
	];
	return call_user_func_array('ffto_translate', $a);
}

function _t ($text){
	$a = func_get_args();
	$v = call_user_func_array('ffto_translate', $a);
	return _echo($v);
}

/* =====================================================================================================================
Methods
===================================================================================================================== */
function ffto_get_lang (){
	$lang = _config('lang');
	return $lang;
}

/**
 * Set the lang of the current site
 *
 * @todo maybe save the previous lang, so you can switch back to the previous one easily
 * @todo be able to decode the lang value, so if you passes "fr_CA" it saves "fr"
 * 
 * @param string $lang Language code
 * @return string
 */
function ffto_set_lang ($lang){
	_config('lang', $lang, true);
	return $lang;
}

/**
 * Return a string in the right language. There's multiple ways to show text:
 * - Simple text: ffto_translate('text');
 * - With a dynamic count (singular vs plural): ffto_translate(['singular text', 'plural text {count}', $count, 'no count text']);
 * - With a default translations (when there's multiple languages, defined in _config('languages')): ffto_translate('Text in EN | Text in FR');
 * 
 * ```php
 * ffto_translate('text');
 * // "text"
 * 
 * ffto_translate(['singular text', 'plural text {count}', 1, 'no count text']);
 * // "singular text"
 * 
 * ffto_translate(['singular text', 'plural text {count}', 5, 'no count text']);
 * // "plural text 5"
 * 
 * ffto_translate(['singular text', 'plural text {count}', 0, 'no count text']);
 * // "no count text"
 * 
 * _config('languages', 'en, fr');
 * ffto_translate('Text in EN | text in FR');
 * // "Text in EN"
 * 
 * ffto_set_lang('fr');
 * ffto_translate('Text in EN | text in FR');
 * // "text in FR"
 * ```
 * 
 * @param mixed $text 
 * @param mixed $data Data to use with the _replace() function
 * @param mixed $args 
 * 	- 'lang' 		[ffto_get_lang()] 		specify which language to use
 * 	- 'languages' 	[$config.languages] 	specify all the languages to use when using the "|" separator 
 * 	- 'domain' 		[$config.domain] 		which domain to use
 * 	- 'ctx' 		[$config.ctx] 			context of the text (great when there's the same text in the multiple context)
 * 	- 'count' 		[0] 					count to use for single, plural and empty text variants
 * 	- 'empty' 		[null] 					empty text to use if $args.count is 0 (fallback to single text)
 * 	- 'plural' 		[null] 					plural text to use if $args.count > 1
 * @return string
 */
function ffto_translate ($text, $data=null, $args=null, $ctx=null){
	$domain = _config('domain');
	$ctx 	= _global('ctx', $ctx);		// for when we set a "ctx" in a view for example, maybe these can be done by default (for each view)
	$single = null;
	$plural = null;
	$empty  = null;
	$count  = null;

	// Array option = multiple version with a count
	if (is_array($text)){
		$single = _get($text, '0 || single');
		$plural = _get($text, '1 || plural');
		$count  = _get($text, '2 || count');
		$empty  = _get($text, '3 || empty', $single);   // extra type of text (when 0)
		$domain = _get($text, 'domain', $domain);
		$ctx    = _get($text, 'ctx', $ctx);
		$text 	= null;
	}else{
		$single = $text;
	}

	if (is_string($data)){
		$domain = $data;
		$data   = [];
	}

	$args = _args($args, [
		'lang'		=> ffto_get_lang(),
		'languages' => _config('languages'),	// order of languages when using the separator " | "
		'domain'    => $domain,
		'ctx'       => $ctx,
		'empty'		=> $empty,
		'plural'	=> $plural,
		'count'     => $count,
	], 'domain');

	// split en/fr/... lang with "|" but not "\|" which escape the |
	$text  = null;
	$count = isset($args['count']) ? $args['count'] : null;

	// select the right type of text
	if ($count === 0){
		$text = $args['empty'];
	}else if ($count >= 2){
		$text = $args['plural'];
	}
	
	if (is_array($data) || $count !== null){
		$data = is_array($data) ? $data : [];
		$data = array_merge(['count'=>$count], $data);
	}	

	$text = ffto_is($text) ? $text : $single;	// fallback to $single
	$text = ffto_is($text) ? $text : '';		// make sure it's a string
	
	// split 
	$texts = preg_split('/\s+\|\s+/', $text);  // splits with " | " (there's a need of space before and after, one or more)
	$text  = $texts[0];
	$lang  = $args['lang'];
	
	// check for a translation
	$translation = ffto_to_translation($text, [
		'lang'   => $lang,
		'domain' => $domain,
		'ctx'    => $ctx,
	]);

	// no translation, check if there's a fallback (with the split text using " | ")
	if (!ffto_is($translation)){
		$languages 	 = _array($args['languages']);
		$idx 		 = array_search($lang, $languages);
		$translation = $idx !== false ? _get($texts, $idx) : null;
	}
	
	$text = ffto_is($translation) ? $translation : $text;
	$text = $data ? _replace($text, $data) : $text; // add the data variables

	// remove '\' since it's there for escaping (in ffto_to_translation(), the "\" are duplicated, so they are kept)
	$text = preg_replace('/\\\{2}/', KEY, $text);
	$text = str_replace('\\', '', $text);
	$text = str_replace(KEY, '\\', $text);

	// [ ] args.type = for knowing if it's HTML or something like that, so we know if we use a wysiwyg editor [html, nl2br, attr, list, ...]
	// [ ] args.parent = when using list, it;s the parent of this item
	// [ ] args.label = for when saving to the string translation, it's used to explain it
	// [ ] args.desc = for when saving to the string translation, it's used to explain it
	// [ ] maybe replace with args.save[label=>'', 'desc'=>'', parent=>'']
	// [ ] Add filters at the end "ffto_translate/return"

	return $text;
}

// NOT FINISHED
function ffto_to_translation ($text, $args=null){
	$args = _args($args, [
		'domain' => null,
		'ctx'    => null,
		'lang'   => null,
		'save'   => null,
	]);

	$translations = ffto_get_translations($args['lang']);
	$key          = ($args['ctx'] ? "{$args['ctx']} | " : '') . $text;  // TODO maybe the text will be using strip_tags() or something like that

	if (isset($translations[$key])){		
		$text = $translations[$key];
		$text = str_replace('\\', '\\\\', $text);
	}

	return $text;
}

// NOT FINISHED
function ffto_get_translations ($lang=null){
	$lang       = $lang ? $lang : ffto_get_lang();
	$key        = '$dictionary/'.$lang;
	$dictionary = _global($key, []);
	return $dictionary;
}

// NOT FINISHED
function ffto_set_translations ($translations, $lang=null){
	$lang       = $lang ? $lang : ffto_get_lang();
	$key        = '$dictionary/'.$lang;
	$dictionary = _global($key, []);
	$dictionary = array_merge($dictionary, $translations);
	_global($key, $dictionary, true);
}

/* =====================================================================================================================
Default translations
===================================================================================================================== */
ffto_set_translations([
	'date-format | F j, Y g:i a'              => 'j F Y G \h i',
	'date-format | F j, Y'                    => 'j F ',
	'date-format | m.d.Y'                     => 'd.m.Y',
	'date-format/month-start | F j'           => 'j',
	'date-format/month-end | j, Y'            => 'j F Y',
	'date-format/year-start | F j'            => 'j F',
	'date-format/year-end | F j, Y'           => 'j F Y',
	'time-format | g:i a'                     => 'G \h i',
	'time-format/short | g a'                 => 'G \h',
	'time-format/meridiem | g:i'              => 'G \h i',
	'time-format/short,meridiem | g'          => 'G \h',
	'date-template | {start} to {end}'        => '{start} au {end}',
	'date-template/time | {start} to {end}'   => '{start} à {end}',
	'time-ago | second'                       => 'seconde',
	'time-ago | minute'                       => 'minute',
	'time-ago | hour'                         => 'heure',
	'time-ago | day'                          => 'jour',
	'time-ago | week'                         => 'semaine',
	'time-ago | month'                        => 'mois',
	'time-ago | year'                         => 'an',
	'time-ago | decade'                       => 'décennie',
	'time-ago/template | {time} {period} ago' => 'il y a {time} {period}',
	'time-ago/template | in {time} {period}'  => 'dans {time} {period}',
], 'fr');