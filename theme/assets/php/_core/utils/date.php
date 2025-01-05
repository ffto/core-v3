<?php 
/**
 * Date
 * 
 * @package Utils\Date
 * @author 	Maxime Lefrancois
 * @version 6.0
 **/

function to_date ($date='now', $args=null, $timezone=null){
	if ($args === ''){
		$args = ['format'=>'*'];
	}elseif ($args === true){
		$args = ['format'=>null]; // return an object
	}

	// make sure the default output timezone works
	if ($timezone === null){
		$timezone = _config('timezone');
	}

    $args = to_args($args, array(
		'time'           => true,                // if false, remove the time
		'input_format'   => false,
		'format'         => null,
		'input_timezone' => null,
		'timezone'       => $timezone,
		'locale'         => null,
		'update'         => null,                // based on a time
		'data'           => [],                  // replacing values in the format
	), 'format', 'to_date-args');

	// [ ] need to validate if this is ok, since 'timezone' also uses "_config('timezone')"
	if ($args['input_timezone'] === null){
		$args['input_timezone'] = _config('timezone');
	}
	if ($args['locale'] === null){
		$args['locale'] = _config('locale');
	}

	$format  = $args['format'];
	$formats = to_config_args('date_formats', is_array($format) ? $format : [], ':date');

	if (is_string($format) && isset($formats[$format])){
		$format = $formats[$format];
	}

	// time ago format is different
	if ($format === ':time-ago'){
		return to_time_ago($date, $args);
	}

	// quick shortcut for formats
	if ($format === ':utc' || $format === 'UTC'){
		$format           = 'Y-m-d H:i:s e';
		$args['locale']   = false;
		$args['timezone'] = 'UTC';
	}else if ($format === ':utc-sql' || $format === 'UTC_SQL'){
		$format           = 'Y-m-d H:i:s';
		$args['locale']   = false;
		$args['timezone'] = 'UTC';
	}else if ($format === ':date-utc'){
		$format           = 'Y-m-d';
		$args['locale']   = false;
		$args['timezone'] = 'UTC';
	}else if ($format === ':time-utc'){
		$format           = 'H:i:s';
		$args['locale']   = false;
		$args['timezone'] = 'UTC';
	}

	// input timezone ----------------------------------------------------------
	// [ ] make sure the filter to_date-timezone is for input_timezone, not all timezone types...
	$input_timezone = null;
	if (is_a($date, 'DateTime')){
		$input_timezone = $date->getTimezone();
	}else{
		$input_timezone = $args['input_timezone'] ? $args['input_timezone'] : null;
		$input_timezone = _filters('to_date-timezone', $input_timezone);
		$input_timezone = is_string($input_timezone) ? new DateTimeZone($input_timezone) : $input_timezone;
	}

    if (is_a($input_timezone, 'DateTime')){
		$input_timezone = $input_timezone->getTimezone();
	}else if (!is_a($input_timezone, 'DateTimeZone')){
		$input_timezone = null;
	}

	// get date ----------------------------------------------------------------
	$_has_time = function ($dt){
		if (!is_string($dt)) return false;

		$dt = date_parse($dt);
	
		return $dt['hour'] !== false 
			|| $dt['minute'] !== false 
			|| $dt['second'] !== false;
	};

    // Passing a date as an Array ['date'=>'string date', 'time' => true/false]
	$d = $date;

	if (($dt = _get($date, 'date')) && is_string($dt)){
		$tm = _get($date, 'time,has_time');

		// forced time to the date
		if (is_string($tm)){
			$dt   = date('Y-m-d', strtotime($dt));
			$dt   = "{$dt} {$tm}";
			$date = date('Y-m-d H:i:s', strtotime($dt));
		// remove the time
		}else if ($tm === false){
			$date = date('Y-m-d', strtotime($dt));
		// has time set in the date string
		}else if ($_has_time($dt)){
			$date = date('Y-m-d H:i:s', strtotime($dt));
		// just a simple date without time
		}else{
			$date = date('Y-m-d', strtotime($dt));
		}
	}

	// if the date is still a string, check if there's a time set in there
	$has_time = $_has_time($date);

	if (!$date){
		$date = null;
	}else if ($args['input_format']){
		$date = DateTime::createFromFormat($args['input_format'], $date, $input_timezone);
		$date = $date ? $date : null;
	}elseif (is_string($date) || is_int($date)){
		$date = is_int($date) ? date('Y-m-d H:i:s', $date) : $date;

		try{
			$date = new DateTime($date, $input_timezone);
		}catch (Exception $e){
			$date = null; 
		}
	}else if (!is_a($date, 'DateTime')){
		$date = null;
	}

	// invalide date, return null
	if (!is_a($date, 'DateTime')) return null;

    // remove the time
	if (!$args['time']){
		$has_time = false;
		$date->setTime(0,0,0);
	}	

	// add the "has_time" if not already set
	if (!property_exists($date, 'has_time')){
		$date->has_time = $has_time;
	}
	
	// update ------------------------------------------------------------------
	// timezone	
	if ($timezone = $args['timezone']){
		if (is_string($timezone)){
			$timezone = new DateTimeZone($timezone); 
		}else if (!is_a($timezone, 'DateTimeZone')){
			$timezone = null;
		}
		$timezone && $date->setTimezone($timezone);
	}	
	$timezone = is_a($timezone, 'DateTimeZone') ? $timezone : $input_timezone;

    // update the date (ex.: "+3days")
	if ($args['update']){
		$date = clone $date;
		$date->modify($args['update']);
	}

	if ($format){
		$now 			 = new DateTime('now', $timezone);
		$is_current_year = $date->format('Y') === $now->format('Y');
		
		// TODO
		// $has_time 		 = $date->format('H:i:s') !== '00:00:00';
		// $has_minutes 	 = $date->format('i')     !== '00';
		// else if ($has_time){
		// 	$format = _get($formats, 'has-time,*', $format);
		// }else if ($has_minutes){
		// 	$format = _get($formats, 'has-time,*', $format);
		// }

		if ($format === '*' || is_array($format)){
			$keys   = '*';
			$keys   = $is_current_year ? 'current-year,*' : $keys;
			$format = _get($formats, $keys, ':date-text');
		}

		if ($format === true){
			$format = ':timestamp';
		}

		// Fallback
		if (!is_string($format)){
			_warn('The Date Format couldn\'t be found');
			$format = '???';
		}
		
		$format = _filters('to_date-format', $format, $date, $args);
 
		if ($format === ':full')				$format = 'Y-m-d H:i:s';
		else if ($format === ':date')			$format = 'Y-m-d';
		else if ($format === ':date-compact')	$format = 'Ymd';
		else if ($format === ':date-text')		$format = 'F j, Y';
		else if ($format === ':time')			$format = 'H:i:s';
		else if ($format === ':time-text')		$format = 'g:i a';
		else if ($format === ':iso')			$format = DateTime::ATOM;
		else if ($format === ':atom')			$format = DateTime::ATOM;
		else if ($format === ':timestamp')		return $date->getTimestamp() + $date->getOffset();
		
		$format = _filters('to_date-format-post', $format, $date, $args);
	}

    if (is_string($format)){
		$_format = $format;
		$_date   = $date;

		// text strings are kept the same if added between double brackets
		$text = [];

		// escape the '{{ text }}' type of text
		$format = preg_replace_callback('/\{\{([^}]+)\}\}/', function ($m) use (&$text){
			$text[] = $m[1];
			return '@@@';
		}, $format);

		// escape the '\text' type of text
		$format = preg_replace_callback('/\\\([a-z_-]+)/', function ($m) use (&$text){
			$text[] = $m[1];
			return '@@@';
		}, $format);

		// escape the '"text"' type of text
		$format = preg_replace_callback('/\"([^"]+)"/', function ($m) use (&$text){
			$text[] = $m[1];
			return '@@@';
		}, $format);

		// escape the '`text`' type of text
		$format = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$text){
			$text[] = $m[1];
			return '@@@';
		}, $format);

		if ($args['locale']){
			// TODO lock some parts of the text

			/*
			// V1 (old)
			// -----------------------------------------------------------------
			// update the date() format to strftime() format
			$f      = $format;
			$format = preg_replace_callback('/(?:[%\\\][a-zA-Z])|([a-zA-Z])/', function ($m){
				return isset($m[1]) ? '{{' . strtr($m[1], [
					// Day - no strf eq : S
					'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 'S' => 'date:S',
					// Week - no date eq : %U, %W
					'W' => '%V', 
					// Month - no strf eq : n, t
					'F' => '%B', 'm' => '%m', 'M' => '%b',
					// Year - no strf eq : L; no date eq : %C, %g
					'o' => '%G', 'Y' => '%Y', 'y' => '%y',
					// Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
					'a' => 'lower:%p', 'A' => 'upper:%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S',
					'v' => 'date:v',
					'P' => 'date:P',
					'G' => '%k',
					// Timezone - no strf eq : e, I, P, Z
					'O' => '%z', 'T' => '%Z',
					'e' => 'date:e',
					// Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x 
					'U' => '%s'
				]) . '}}' : $m[0];
			}, $format);

			$d         = $date;
			$t         = date_default_timezone_get();
			$format    = trim($format);
			$timestamp = $d->getTimestamp() + $d->getOffset();

			setlocale(LC_TIME, $args['locale']); 
			date_default_timezone_set('UTC');
			$date = strftime($format, $timestamp);
			date_default_timezone_set($t);

			$date2 = IntlDateFormatter::formatObject($d, $f, $args['locale']);
			// $date2 = $date2->format($format);
			__err($d, $f, $date2);

			// $date = !mb_check_encoding($date, 'UTF-8') ? utf8_encode($date) : $date; // old version, utf8_encode is deprecated... is this really needed?
			$date = to_utf8($date);
			// p($date);
			//*/

			// [ ] Deal with modifiers for everything. Break the format into array like "H:m:s{lower:A} `time`" to ["H:m:s", ['lower', 'A'], ['raw','time']] and then go through the items

			// V2 
			// -----------------------------------------------------------------
			// convert ICU formatting to date() formatting 
			// (from https://www.php.net/manual/en/datetime.format.php to https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax)
			$symbols = [
				"'" => "''",			// double the single apostrophy, since IntlDateFormatter use the single one for wrapping text
				// Day
				'd' => 'dd',            // 01 to 31
				'D' => 'eee',           // Mon through Sun
				'j' => 'd',             // 1 to 31
				'l' => 'eeee',          // Sunday through Saturday
				'N' => 'e',             // 1 (for Monday) through 7 (for Sunday)
				// Week
				'W' => 'w',             // Example: 42 (the 42nd week in the year)
				// Month
				'M' => 'MMM',           // Jan through Dec
				'F' => 'MMMM',          // January through December
				'm' => 'MM',            // 01 through 12
				'n' => 'M',             // 1 through 12
				// Year
				'y' => 'yy',            // Examples: 99 or 03
				'Y' => 'yyyy',          // Examples: -0055, 0787, 1999, 2003, 10191
				// Time
				'a' => 'aa',  // am or pm
				// 'a' => '{{lower:aa}}',  // am or pm
				'A' => 'aa',            // AM or PM
				'h' => 'hh',            // 01 through 12
				'H' => 'HH',            // 00 through 23
				'g' => 'h',             // 1 through 12
				'G' => 'H',             // 0 through 23
				'i' => 'mm',            // 00 to 59
				's' => 'ss',            // 00 through 59
				'u' => 'S',             // Example: 654321
				'e' => 'V',             // Examples: UTC, GMT, Atlantic/Azores
			];

			$format    = strtr($format, $symbols);
			$format    = trim($format);
			$timestamp = $date->getTimestamp() + $date->getOffset();
			$date      = IntlDateFormatter::formatObject($date, $format, $args['locale']);			
			
			// __err($format, $_format, $date, $_date);

            // Replacing some variables, with some modifiers
			$date = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/', function ($str) use ($args, $timestamp){
				$str   = trim($str[1]);
				$pair  = explode(':', $str);
				$prefix= count($pair) > 1 ? $pair[0] : '';
				$str   = count($pair) > 1 ? $pair[1] : $str;
				if ($prefix === 'upper'){
					$str = strtoupper($str);
				}
                if ($prefix === 'lower'){
					$str = strtolower($str);
				}
                // use the default "date" function ignoring the locale
				if ($prefix === 'date'){
					$str = date($str, $timestamp);
				}
                // replace other variables
				if (isset($args['data'][$str])){
					$str = $args['data'][$str];
				}

				return $str;
			}, $date);			

			$date = stripslashes($date);
		}else{
			$date = $date->format($format);
		}

		// re-add the escaped text
		$date = preg_replace_callback('/@@@/', function ($m) use (&$text){
			return array_shift($text);
		}, $date);

		$date = _filters('to_date-value', $date, $args);
	}

	return $date;
}

function to_utc_date ($date=null, $format='UTC'){
	$date = to_date(clone $date, array(
		'format'   => $format,
		'timezone' => 'UTC',
		'locale'   => false,
	));
	return $date;
}

function to_daterange ($start, $end=null, $args=''){
	if (!$start && !$end) return;

	if ($args === true){
		$args = array('return'=>'object');
	}

	$args = _args($args, array(
		'time'                   => true,
		'input_timezone'         => null,
		'timezone'               => null,
		'alias'                  => 'daterange',
		'template'				 => null,
		'format'				 => null,
		'time_format'			 => null,
		'return'                 => ''  			// HTML or OBJECT,
	), 'return', 'to_daterange-args');

	// templates
	// ------------------------------------
	// * 					: {{ start }} to {{ end }}
	// single 				: {{ start }}
	// upcoming 			: null
	// live					: null
	// passed				: null
	// same-month 			: *
	// same-year 			: *
	// diff-year 			: *
	// time 				: *
	// time-single  		: single, {{ start }}
	// datetime-join		: null
	//
	// date formats
	// ------------------------------------
	// *					: :date
	// start				: *
	// end					: *
	// same-month-start 	: start, *
	// same-month-end 		: end, *
	// same-year-start 		: start, *
	// same-year-end 		: end, *
	// diff-year-end 		: end, *
	// 
	// time formats
	// ------------------------------------
	// *					: :time	
	// start				: *
	// end					: *
	// short				: *
	// short-start			: *
	// short-end			: *	

	// all is in one object (eg.: ['start'=>['date'=>'99990011'], 'end'=>['date'=>'77772266']] )
	if (is_array($start) && !$end && isset($start['start']) && isset($start['end'])){
		$t 	   = _get($start, 'time,has_time');
		$end   = $start['end'];
		$start = $start['start'];
	
		// check if there is a specific time set (true, false, string)
		if ($t !== null){
			$start = ['date'=>$start, 'time'=>$t];
			$end   = ['date'=>$end, 'time'=>$t];
		}
	}


	// clean "$end" if it's empty
	$end = is_array($end) ? array_filter($end, function ($v){ 
		return is_defined($v);
	}) : $end;
	
	// decoding dates
	if (is_array($start) && isset($start['date']) && is_array($end) && isset($end['date'])){
		$start = array(
			'date'	=> $start['date'],
			'time'	=> _get($start, 'time_start,time_end,time,has_time'),
		);
		$end = array(
			'date'	=> $end['date'],
			'time'	=> _get($end, 'time_end,time_start,time,has_time'),
		);

	}else if (is_array($start) && isset($start['date'])){
		if (isset($start['time_end'])){
			$end = array(
				'date'	=> $start['date'],
				'time'	=> $start['time_end'],
			);
		}				
		$start = array(
			'date'	=> $start['date'],
			'time'	=> _get($start, 'time_start,time,has_time'),
		);
	}


	// TODO clean the previous versions

	// Passing "start"/"end" values from old daterange
	if ($dt = _get($start, 'start')){
		$start = $dt;
	}
	if ($dt = _get($end, 'end,start')){
		$end = $dt;
	}

    $now = to_date('now', false);
    $aaa = [
		'format'		 => false,
		'time'           => $args['time'],
		'timezone'		 => $args['timezone'],
		'input_timezone' => $args['input_timezone'],
	];
		
	$start = $start ? to_date($start, $aaa) : null;
	$end   = $end ? to_date($end, $aaa) : null;
	
	// invalid date
	if (!$start) return null;

	// conditions --------------------------------------------------------------
	$is_same_day     = false;
	$is_same_month   = false;
	$is_same_year    = false;
	$is_diff_year    = false;
	$is_same_time    = false;
	$is_diff_daytime = false;
	$is_passed       = false;
	$is_live         = false;
	$is_upcoming     = false;
	
	// make sure it's the end of the day
	$end2 = clone ($end ? $end : $start);
	($args['time'] === false) && $end2->setTime(23, 59, 59);

	// get the time progress (end - start)
	$progress = 0;
	if ($now >= $start && $now <= $end2){
		$is_live  = true;
		$rs 	  = $now->getTimestamp() - $start->getTimestamp();
		$re       = $end->getTimestamp() - $start->getTimestamp();
		$progress = $re ? $rs / $re : 0;
	}else if ($now > $end2){
		$is_passed = true;
		$progress  = 1;
	}else{
		$is_upcoming = true;
		$progress    = 0;
	}

	// conditions:date ---------------------------------------------------------
	if ($start && $end){
		$is_same_year    = $start->format('Y')                   === $end->format('Y');
		$is_diff_year    = !$is_same_year;
		$is_same_month   = $is_same_year && $start->format('n')  === $end->format('n');
		$is_same_day     = $is_same_month && $start->format('j') === $end->format('j');
		$is_same_time    = $start->format('H:i:s')               === $end->format('H:i:s');
		$is_diff_daytime = $is_same_day && !$is_same_time;
	}

	// conditions:time ---------------------------------------------------------
	$has_start_time      = false;
	$has_end_time        = false;
	$has_time            = false;
	$is_short_start_time = false;
	$is_short_end_time   = false;

	if ($args['time']){
		if ($start){
			$has_start_time      = $start->has_time;
			$is_short_start_time = $start->format('i') === '00';
		}
		if ($end){
			$has_end_time      = $end->has_time;
			$is_short_end_time = $end->format('i') === '00';
		}
		$has_time = $has_start_time || $has_end_time;
	}
	
	// templates ---------------------------------------------------------------
	$templates    = to_config_args('date_templates', $args['template'], '{{ start }} to {{ end }}');
	$date_formats = to_config_args('date_formats', $args['format'], ':date-text');
	$time_formats = to_config_args('time_formats', $args['time_format'], ':time-text');
	
	$to_html = function ($dt, $format){
		return to_date($dt, ['format'=>$format]);
	};

	$html       = '';
	$start_html = '';
	$end_html   = '';
	$template   = _get($templates, '*');
	if ($is_upcoming && ($tpl = _get($templates, 'upcoming'))){
		$html = $tpl;
	}else if ($is_live && ($tpl = _get($templates, 'live'))){
		$html = $tpl;
	}else if ($is_passed && ($tpl = _get($templates, 'passed'))){
		$html = $tpl;
	}else if ($is_diff_daytime){
		$s          = _get($date_formats, 'start,*');
		$e          = _get($date_formats, 'end,*');
		$start_html = $to_html($start, $s);
		$end_html   = $to_html($end, $e);
		
		if ($start_html === $end_html || $is_same_day){
			$end_html = '';
			$template = _get($templates, 'single', '{{ start }}');
		}
	}else if ($is_same_day || !$end){
		$end 	  	 = null;
		$s 			 = _get($date_formats, '*');
		$start_html  = $to_html($start, $s);
		$template    = _get($templates, 'single', '{{ start }}');		
	}else if ($is_same_month){
		$s 		     = _get($date_formats, 'same-month-start,start,*');
		$e 		     = _get($date_formats, 'same-month-end,end,*');
		$start_html  = $to_html($start, $s);
		$end_html    = $to_html($end, $e);
		$template 	 = _get($templates, 'same-month,*');
	}else if ($is_same_year){
		$s 		     = _get($date_formats, 'same-year-start,start,*');
		$e 		     = _get($date_formats, 'same-year-end,end,*');
		$start_html  = $to_html($start, $s);
		$end_html    = $to_html($end, $e);
		$template 	 = _get($templates, 'same-year,*');
	}else if ($is_diff_year){
		$s 		     = _get($date_formats, 'diff-year-start,start,*');
		$e 		     = _get($date_formats, 'diff-year-end,end,*');
		$start_html  = $to_html($start, $s);
		$end_html    = $to_html($end, $e);
		$template 	 = _get($templates, 'diff-year,*');
	}else{
		$f 			 = _get($date_formats, '*');
		$start_html  = $to_html($start, $f);
		$end_html    = $to_html($end, $f);
	}

	// diff-year-end

	$start_html = $start_html ? '<span class="&--start">'.$start_html.'</span>' : '';
	$end_html   = $end_html ? '<span class="&--end">'.$end_html.'</span>' : '';
	$date_html 	= $html ? $html : '<span class="&--date'.($end_html ? '' : ' is-single').'">' . string_replace($template, array(
		'start' => $start_html,
		'end'   => $end_html,
	)) . '</span>';

	// time --------------------------------------------------------------------
	$time_start_html = '';
	$time_end_html   = '';
	$template_time   = _get($templates, 'time,*', '{{ start }} - {{ end }}');
	if ($has_start_time && $has_end_time && !$is_same_time){
		$s               = _get($time_formats, $is_short_start_time ? 'short-start,short,start,*' : 'start,*');
		$e               = _get($time_formats, $is_short_end_time ? 'short-end,short,end,*' : 'end,*');
		$time_start_html = to_date($start, $s);
		$time_end_html   = to_date($end, $e);
	}elseif ($has_start_time || $has_end_time){
		$time            = $has_start_time ? $start : $end;
		$short           = $has_start_time ? $is_short_start_time : $is_short_end_time;
		$format          = _get($time_formats, $short ? 'short-start,short,start,*' : 'start,*');
		$time_start_html = to_date($time, $format);
		$template_time   = _get($templates, 'time-single,single', '{{ start }}');
	}

	$time_start_html = $time_start_html ? '<span class="&--time-start">'.$time_start_html.'</span>' : '';
	$time_end_html   = $time_end_html ? '<span class="&--time-end">'.$time_end_html.'</span>' : '';
	$time_html 		 = $time_start_html || $time_end_html ? '<span class="&--time'.($time_end_html ? '' : ' is-single').'">' . string_replace($template_time, array(
		'start' => $time_start_html,
		'end'   => $time_end_html,
	)) . '</span>' : '';
	
	$tpl  = _get($templates, 'datetime', '{{ date }} {{ time }}');
	$html = array_filter([$date_html, $time_html]);
	$html = count($html) > 1 ? string_replace($tpl, ['date'=>$date_html, 'time'=>$time_html]) : reset($html);
	$html = $html ? '<span class="&">' . $html . '</span>' : '';
	$html = replace_alias($html, $args['alias']);

	$data = array(
		'start'           => $start,
		'end'             => $end,
		'timezone'		  => $start ? $start->getTimezone()->getName() : null,
		'start_utc'       => $start ? to_utc_date($start) : null,
		'start_full'      => $start ? to_date($start, ':full') : null,
		'end_utc'         => $end ? to_utc_date($end) : null,
		'end_full'        => $end ? to_date($end, ':full') : null,
		'group'			  => $start ? to_date($start, 'Y-m-d') : null,
		'group_utc'		  => $start ? gmdate('Y-m-d', $start->getTimestamp()) : null,
		'has_time'        => $has_time,
		'has_start_time'  => $has_start_time,
		'has_end_time'    => $has_end_time,
		'is_live'         => $is_live,
		'is_passed'       => $is_passed,
		'is_upcoming'     => $is_upcoming,
		'is_same_time'    => $is_same_time,
		'is_diff_daytime' => $is_diff_daytime,
		'is_same_day'     => $is_same_day,
		'is_same_month'   => $is_same_month,
		'is_same_year'    => $is_same_year,
		'progress'		  => $progress,
		'start_time_html' => $start_html,
		'end_time_html'   => $start_html,
		'time_text'		  => trim(strip_tags($time_html)),
		'time_html'		  => $time_html,
		'start_html'      => $start_html,
		'end_html'        => $end_html,
		'date_text'		  => strip_tags($date_html),
		'date_html'		  => $date_html,
		'html'            => $html,
		'text'			  => trim(strip_tags($html)),
	);

	if (return_object($args['return'])){
		return $data;
	}else if ($args['return']){
		return isset($data[$args['return']]) ? $data[$args['return']] : null;
	}else{
		return $html;
	}
}

function to_dates ($dates, $args=null){
	$args = _args($args, [
		'join'            => null,
		'empty'           => null,
		'label'           => true,
		'template_single' => null,
		'template_many'   => null,
		'return'          => '',
	], 'return');
	
	$label = null;

	// Object type, special from the ACF Field: "group_hidden-dates.json"
	if (has($dates, 'date.type')){
		$type       = _get($dates, 'date.type', 'single');
		$start      = _get($dates, 'date.start');
		$end        = _get($dates, 'date.end');
		$label      = _get($dates, 'more.label');
		$has_time   = _get($dates, 'time.has_time', false);
		$time_start = $has_time ? _get($dates, 'time.start') : null;
		$time_end   = $has_time ? _get($dates, 'time.end') : null;
		$multi      = _get($dates, 'dates.items', []);

		$items = [];
		if ($type === 'single' && $start){
			$items[] = [
				'date'       => $start,
				'time_start' => $time_start,
				'time_end'   => $time_end,
			];
		}else if ($type === 'range' && $start){
			$items[] = [
				'start' => [
					'date' => $start,
					'time' => $time_start,
				],
				'end' => [
					'date' => $end,
					'time' => $time_end,
				],
			];
		}else if ($type === 'multi'){
			// TODO maybe remove time
			$items = array_map(function ($v) use ($has_time){
				return $has_time ? $v : $v['date'];
			}, $multi);
		}else if ($time_start){
			$items[] = [
				'date'       => null,
				'time_start' => $time_start,
				'time_end'   => $time_end,
			];
		}

		$dates = $items;
	}else if (!arr_is_list($dates)){
		$dates = [$dates];
	}

	$dates = empty($dates) ? [] : $dates;
	
	// transform to daterange
	$all = [];

	// update the template in case there's 1 entry OR many entries
	$template = _get($args, 'template', []);
	if (count($dates) > 1){
		$args['template'] = arr_merge($template, $args['template_many']);
	}else{
		$args['template'] = arr_merge($template, $args['template_single']);
	}

	// TODO Add a filter to deal with the $args, based on the dates AND the count, ...

	foreach ($dates as $i => $date){
		$dt = to_daterange($date, null, $args);
		// _js($dt);

		if ($dt){
			$all[] = $dt;
		}
	}
	$dates = $all;
	
	if (return_object($args['return'])){
		$first = reset($dates);
		$last  = end($dates);
		$range = to_daterange($first, $last, true);
		$range = $range ? $range : [];
		
		$range['dates'] = empty($dates) ? [] : $dates;
		$range['label'] = $label;
		
		return $range;
	}

	if ($args['label'] && $label){
		$dates = [$label];
	}

	if (empty($dates) && $args['empty']){
		$dates = [$args['empty']];
	}

	if (is_string($args['join'])){
		$dates = implode($args['join'], $dates);
	}

	return $dates;
}

function to_time_ago ($time, $args=''){
	$args = to_args($args, array(
		'compare'	=> 'now',
		'template'	=> '{{ diff }} {{ period }} ago',
	));
	
	$time       = to_date($time);
	$timezone 	= $time->getTimezone();
	$time 		= to_date($time, 'format=timestamp');
	// $now        = to_date($args['compare'], ['format'=>'timestamp']); //, 'input_timezone'=>$timezone]);	// the "now" needs to be the same timezone
	$now        = to_date($args['compare'], ['format'=>'timestamp', 'input_timezone'=>$timezone]);	// the "now" needs to be the same timezone

	$periods    = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths    = array(60,60,24,7,4.35,12,10);

	$difference = $now - $time;
	
	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}
 
	$difference = round($difference);
 
	if ($difference != 1) {
		$periods[$j].= "s";
	}

	$text = string_replace($args['template'], array(
		'diff'   => $difference,
		'period' => $periods[$j],
	));

	return $text;
}

// Validate --------------------------------------------------------------------
function date_is_passed ($date, $now='now'){
	$date = to_date($date);
	$now  = to_date($now, ['input_timezone'=>$date]);
	return $now > $date;
}