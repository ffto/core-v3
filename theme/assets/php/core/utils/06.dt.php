<?php 
/**
 * @package Utils\Date
 */

/**
 * Decode a date and return it with multiple formatting options. When setting the 'format' $args, you can use condiftional formating 
 * when the date is this current year or not. To do so: 
 * 
 * Formats:
 * - a string with the default PHP symbols (@link https://www.php.net/manual/en/datetime.format.php). Like: 'Y-m-d H:i:s'
 * - one of the code shortcut:
 * 	- :timestamp
 * 	- :utc-timezone 	= 'Y-m-d H:i:s e'
 * 	- :utc 				= 'Y-m-d H:i:s'
 * 	- :utc-date 		= 'Y-m-d'
 * 	- :utc-time 		= 'H:i:s'
 * 	- :time-ago			= no formats, return a special time-ago text
 * 	- :full 			= 'Y-m-d H:i:s';
 * 	- :date				= 'Y-m-d'
 * 	- :date-compact		= 'Ymd'
 * 	- :date-text		= 'F j, Y'
 * 	- :time				= 'H:i:s'
 * 	- :time-text		= 'g:i a'
 * 	- :atom				= 'Y-m-d\\TH:i:sP' 
 * 	- :atom-micro		= 'Y-m-d\TH:i:s.uP'
 *  - :micro			= 'Y-m-d H:i:s.u'
 *  - ::full			= 'Y-m-d H:i:s'; 	(won't be using a translated version)
 * 	- ::date			= 'Y-m-d' 			(won't be using a translated version)
 * 	- ::date-compact	= 'Ymd' 			(won't be using a translated version)
 *  - ::time			= 'H:i:s' 			(won't be using a translated version)
 *  
 * - An array with special keys (keys are separated by commas: ","):
 * - '*'            = default
 * - 'time'         = has time specified
 * - 'short-time'   = has a short-time (without minutes)
 * - 'current-year' = if the date is this current year, this format will be used
 *
 * ```php
 * ffto_to_date('now');
 * // <time datetime="2025-01-09T19:07:56Z">January 9, 2025 7:07 PM</time>
 * 
 * ffto_to_date('now', false);
 * // object(DateTime)["date"=>"2024-05-18 17:55:46.302883", "timezone_type"=>3, "timezone"=>"America/Toronto"]
 * 
 * ffto_to_date('now', true);
 * // [
 * //     "date" => [
 * //         "date" => "2025-01-09 19:06:45.970346",
 * //         "timezone_type" => 3,
 * //         "timezone" => "America\/Toronto"
 * //    ],
 * //     "has_time" => true,
 * //     "timezone" => "America\/Toronto",
 * //     "text" => "January 9, 2025 7:06 PM",
 * //     "html" => "<time datetime=\"2025-01-09T19:07:56Z\">January 9, 2025 7:07 PM</time>",
 * //     "utc" => "2025-01-09 19:06:45 America\/Toronto",
 * //     "season" => "winter",
 * //     "season_year" => 2024,
 * //     "time_of_day" => "evening",
 * //     "is_weekend" => false,
 * //     "is_today" => true,
 * //     "is_this_month" => true,
 * //     "is_this_year" => true,
 * //     "is_before_now" => true,
 * //     "is_after_now" => false,
 * //     "is_passed" => false,
 * //     "is_upcoming" => true,
 * //     "is_same_day" => true,
 * //     "is_same_month" => true,
 * //     "is_same_year" => true
 * // ]
 * 
 * ffto_to_date('now', 'time=0&format=:full', 'text');
 * // "2024-05-18 00:00:00"
 * 
 * ffto_to_date('now', 'update=+3days&format=:date');
 * // <time datetime="2025-01-12T19:03:12Z">2025-01-12</time>
 * 
 * ffto_to_date('2025-01-10', 'compare=2025-01-12&format=:time-ago', 'text');
 * // "2 days ago"
 * 
 * ffto_to_date('now -5days', [
 * 	'compare'  => 'now',
 * 	'format'   => ':time-ago',
 * 	'return'   => 'text',
 * 	'time_ago' => [
 * 		'day'  => ['jour', 'jours'],
 * 		'past' => 'il y a {time} {period}',
 * 	]
 * ]);
 * // "il y a 5 jours"
 * 
 * ffto_to_date('now', ['replace'=>['PM'=>'p.m.'], 'format'=>':time-text'])
 * // "6:25 p.m."
 * 
 * ffto_to_date('now', ['data'=>['name'=>'Bob.'], 'format'=>'`it is now:` H:i A {name}']);
 * // "it is now: 19:05 PM Bob."
 * 
 * ffto_to_date('18 may', ['format'=>[
 * 	'*'            => 'M, j Y',
 * 	'current-year' => 'M j',
 * ]]);
 * // "May 18"
 * 
 * ffto_to_date('18 may', [
 * 	'months' => [4=>'IT\'S GONNA BE MAY'],
 * 	'format' => 'F, d Y',
 * ], 'text');
 * // "IT'S GONNA BE MAY, 18 2025"
 * 
 * ffto_to_date('2020-01-30 3:40pm', ['format'=>[
 * 	'*'					=> 'F j, Y',
 * 	'time'         		=> 'F j, Y g:i a',
 * 	'current-year' 		=> 'F j',
 *  'current-year/time' => 'F j g:i a',
 * ]]);
 * // January 30, 2020 3:40 PM
 * 
 * ffto_to_date('2020-01-30 3:40pm', ['format'=>[
 * 	'*'					=> 'F j, Y',
 * 	'current-year' 		=> 'F j',
 * ]]);
 * // January 30, 2020
 * 
 * ffto_to_date('21st july', ['format'=>[
 * 	'*'					=> 'F j, Y',
 * 	'current-year' 		=> 'F j',
 * ]]);
 * // July 21
 * 
 * ffto_to_date('2024-03-12', [
 * 	'format'  => [
 * 		'*'        => 'F j g:i a `Bob`',
 * 		':snippet' => 'Y.m.d',
 * 	]
 * ]);
 * // March 12 12:00 AM Bob
 * 
 * _config(['date/formats' => [
 * 	':snippet' => 'Y.m.d',
 * ]]);
 * 
 * ffto_to_date('2024-03-12', ':snippet');
 * // 2024.03.12
 * ```
 * 
 * @todo add :month-short translation somehow, but making it simpler if possible, like either hardcoded OR in a translation system
 * @todo should there be a way to seperate the DATE and TIME, like to_daterange()
 * @todo add a bunch of default date templates
 * 
 * @param string $date 
 * @param mixed $args [format]
 * 	- 'time' 			[null] either false to remove the time, true to keep it, a string to force a time, null to do nothing
 * 	- 'type' 			[date] (date, strftime, ICU), different formatting structure (default to date)
 * 	- 'format' 			[$format] either a string, an array ['*' => '', 'current-year' => ''], null = DateTime object, true.
 *  - 'timezone' 		[config:date/timezone] (See @link https://www.php.net/manual/en/timezones.php)
 * 	- 'update' 			[null] update the date, either a string or a numeric value
 * 	- 'compare' 		[now] comparing date for time-ago
 * 	- 'input_format' 	[null] way of decoding the date
 * 	- 'input_timezone' 	[config:date/input_timezone] 
 * 	- 'lang' 			[config:date/lang] change the language of the formating
 * 	- 'replace' 		[null] replacing string match in the formated value
 * 	- 'data' 			[null] replacing vars in the format (eg. )
 * 	- 'months' 			[config:date/months] labels for the months (for full months and short version). either a normal array, or with with 'full' and 'short' keys
 * 	- 'weeks' 			[config:date/weeks] labels for the weeks (for full weeks and short version). either a normal array, or with with 'full' and 'short' keys
 * 	- 'weeks' 			[config:date/amp_pm] labels for the am/pm (eg. ['am'=>'a.m.', 'AM'=>'A.M.'])
 * 	- 'times_ago' 		[config:date/times_ago] labels for time-ago
 * 	- 'wrap' 			[false] return the format with a <time> wrapping the value
 * @param mixed $format 
 * 
 * @return mixed
 */
function ffto_to_date ($date=null, $args=null, $return=null){
	// return NOW as a DateTime object
	if ($date === true){
		$date   = 'now';
		$format = null;
	}

	// Return a full object
	if ($args === true){
		$args = ['return'=>true];
	// No formatting
	}else if ($args === false){
		$format = ['format'=>false];
	}
	
	$date = $date ? $date : 'now';
	$args = _args($args, [
		'time'           => null,                             // either false to remove the time, true to keep it, a string to force a time
		'type' 	 		 => _config('date/type', 'date'), 	  // [null/date, strftime, ICU]
		'format'         => '*',						  // either a string, an array ['*' => '', 'current-year' => ''], null = DateTime object, true = timestamp, :object will be eveything with data
		'timezone'       => _config('date/timezone'),		  // list: https://www.php.net/manual/en/timezones.php
		'update'         => null,                             // update the date, either a string or a numeric value
		'compare'		 => 'now',						      // comparing date for time-ago AND when returning an object (is_passed and is_upcoming)
		'input_format'   => null,                             // way of decoding the date
		'input_timezone' => _config('date/input_timezone'),
		'lang'           => _config('date/lang'),             // change the language of the formating
		'replace'        => null,                             // replacing values in the format
		'data'           => null,                             // replacing vars in the format
		'months'         => _config('date/months'),           // labels for the months (for full months and short version)
		'weeks'          => _config('date/weeks'),            // labels for the weeks (for full weeks and short version)
		'am_pm'          => _config('date/am_pm'), 		  	  // labels for AM/PM
		'time_ago'       => _config('date/time_ago'),    	  // labels for time-ago
		'wrap'			 => true,							  // return the format with a <time> wrapping the value
		'attr_format'	 => null,                          	  // when wrapping the date with a <time> tag, the datetime attribute needs a format
		'return'		 => $return,						  // return object OR a specific property		
	], 'format', 'ffto_to_date/args');
	
	// decode array like this: ['date'=>'', 'time'=>'']
	$time = $args['time'];
	if (is_array($date)){
		$time = _get($date, 'time || has_time', $time);
		$date = _get($date, 'date');
	}

	// check if there's a time specified in the $date string
	if ($time === null && is_string($date)){
		if (strpos($date, 'now') === 0){
			$time = true;
		}else if (strpos($date, 'today') === 0){
			$time = false;
		}else{
			$dt   = date_parse($date);
			$time = $dt['hour'] !== false || $dt['minute'] !== false || $dt['second'] !== false;
		}
	}

	$input_format   = $args['input_format'];
	$input_timezone = _format($args['input_timezone']);
	$input_timezone = is_string($input_timezone) ? new DateTimeZone($input_timezone) : $input_timezone;

	// timezone
	if ($timezone = $args['timezone']){
		$timezone       = _format($args['timezone']);
		$timezone       = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
		$input_timezone = $input_timezone ? $input_timezone : $timezone;
	}

	// date creation
	if (!$date){
		$date = null;
	}else if (is_a($date, 'WC_DateTime')){
		// Special case where the date is a WooCommerce DateTime
		$date = $date->getOffsetTimestamp(); // TODO needs to be tested
	}else if (is_string($date) && $input_format){
		$date = DateTime::createFromFormat($input_format, $date, $input_timezone);
		$date = $date ? $date : null;
	}elseif (is_string($date) || is_numeric($date)){
		// TODO test the is_float, is_int VS is_numeric...
		$date = is_float($date) || is_int($date) ? date('Y-m-d H:i:s', (int)$date) : $date;
		
		try{
			$date = new DateTime($date, $input_timezone);
		}catch (Exception $e){
			$date = null; 
		}
	}

	
	// no date could be decoded
	if (!$date) return false;	

	// make sure the timezone is set
	if ($timezone){
		$date->setTimezone($timezone);
	}

	// remove the time
	if (ffto_is_falsy($time) || $time === ':start'){
		$date->setTime(0,0,0);
	// end of the day
	}else if ($time === ':end'){
		$date->setTime(23,59,59);
	// set the time as a string
	}else if (is_string($time)){
		$time 			 = date('H:i:s', strtotime($time));
		list($h, $m, $s) = array_map(function ($v){ return (int)$v; }, explode(':', $time));
		$date->setTime($h,$m,$s);
	}

	// update the date
	if ($update = $args['update']){
		$date   = clone $date;
		$update = is_numeric($update) ? $update . 'seconds' : $update;
		$date->modify($update);
	}

	// formatting
	$now             = new DateTime('now', $timezone);
	$has_time 		 = !!$time;
	$has_short_time  = $date->format('i') === '00';
	$is_current_year = $date->format('Y') === $now->format('Y');
	
	$format = ffto_to_conditional_format($date, $args['format'], [
		'current-year' => $is_current_year,
		'short-time'   => $has_short_time,
		'time'         => $has_time,
	], 'date/formats');

	// Default string values (readable text versions). If "*" hasn't been set, then it's the default too
	if ($format === true || $format === '*'){
		$format = $has_time ? ':datetime-text' : ':date-text';
	}
	
	$format = _filters('ffto_to_date/format', $format, $date, $args);
	$value  = $date;
	$utc    = new DateTimeZone('UTC');

	if ($format === ':timestamp'){
		$value = $date->getTimestamp() + $date->getOffset();
		return $value;
	}else if ($format === ':utc-timezone'){
		$date->setTimezone($utc);
		$value = $date->format('Y-m-d H:i:s e');
	}else if ($format === ':utc'){
		$date->setTimezone($utc);
		$value = $date->format('Y-m-d H:i:s');
	}else if ($format === ':utc-date'){
		$date->setTimezone($utc);
		$value = $date->format('Y-m-d');
	}else if ($format === ':utc-time'){
		$date->setTimezone($utc);
		$value = $date->format('H:i:s');
	}else if ($format === ':atom' || $format === ':iso'){
		$value = $date->format(DateTime::ATOM);
	}else if ($format === ':atom-micro' || $format === ':iso-micro'){
		$value = $date->format('Y-m-d\TH:i:s.uP');
	}else if ($format === ':micro'){
		$value = $date->format('Y-m-d H:i:s.u');	
	}else if ($format === '::full'){
		$value = $date->format('Y-m-d H:i:s');
	}else if ($format === '::date'){
		$value = $date->format('Y-m-d');
	}else if ($format === '::date-compact'){
		$value = $date->format('Ymd');
	}else if ($format === '::time'){
		$value = $date->format('H:i:s');
	}else if ($format === ':time-ago'){
		// TODO translating these strings, maybe __t() would be perfect, something like __t('second', 'ctx=date')
		$_time_ago = _args($args['time_ago']);
		$time_ago  = array_merge([
			'second' => __tx('time-ago', 'second'),
			'minute' => __tx('time-ago', 'minute'),
			'hour'   => __tx('time-ago', 'hour'),
			'day'    => __tx('time-ago', 'day'),
			'week'   => __tx('time-ago', 'week'),
			'month'  => __tx('time-ago', 'month'),
			'year'   => __tx('time-ago', 'year'),
			'decade' => __tx('time-ago', 'decade'),
			'past'   => __tx('time-ago/template', '{time} {period} ago'),
			'future' => __tx('time-ago/template', 'in {time} {period}'),
		], $_time_ago);

		$compare   = ffto_to_date($args['compare'], ['format'=>false, 'timezone'=>$date->getTimezone()]);
		$lengths   = array(60,60,24,7,4.35,12,10);
		$past      = $time_ago['past'];
		$future    = $time_ago['future'];
		$time_ago  = array_values($time_ago);
		$is_past   = $compare > $date;
		$diff      = $is_past ? $compare->getTimestamp() - $date->getTimestamp() : $date->getTimestamp() - $compare->getTimestamp();
		
		for ($i = 0; $diff >= $lengths[$i] && $i < count($lengths)-1; $i++) {
			$diff /= $lengths[$i];
		}

		$diff   = floor($diff);
		$single = _get($time_ago, "{$i}/0 || {$i}");
		$plural = _get($time_ago, "{$i}/1", "{$single}s");
		$many   = $diff != 1;
		$label  = $many ? $plural : $single;

		$value = _replace($is_past ? $past : $future, [
			'time'   => $diff,
			'period' => $label,
		]);		
	}else if (is_string($format)){
		// TODO have way to translate these shortcuts

		if ($format === ':full')				$format = __tx('date-format', 'Y-m-d H:i:s');	// fr: 
		else if ($format === ':date')			$format = __tx('date-format', 'Y-m-d');			// fr: 
		else if ($format === ':date-compact')	$format = __tx('date-format', 'Ymd');			// fr: 
		else if ($format === ':time')			$format = __tx('time-format', 'H:i:s');			// fr: 
		else if ($format === ':text' && $time)	$format = __tx('date-format', 'F j, Y g:i a');	// fr: 'j F Y G \h i'
		else if ($format === ':text')			$format = __tx('date-format', 'F j, Y');		// fr: 'j F Y'
		else if ($format === ':date-text')		$format = __tx('date-format', 'F j, Y');		// fr: 'j F Y'
		else if ($format === ':datetime-text')	$format = __tx('date-format', 'F j, Y g:i a');	// fr: 'j F Y G \h i'
		else if ($format === ':date-numeric')	$format = __tx('date-format', 'm.d.Y');			// fr: 'd.m.Y'
		else if ($format === ':time-text')		$format = __tx('time-format', 'g:i a');			// fr: 'G \h i'

		$format       = trim($format);
		$months_full  = _get($args, 'months/full || months');
		$months_short = _get($args, 'months/short');
		$weeks_full   = _get($args, 'weeks/full || weeks');
		$weeks_short  = _get($args, 'weeks/short');
		$am_pm        = _get($args, 'am_pm');
		$lang         = $args['lang'];
		$vars         = [];

		// encode the value returned, so it can be retrieved in the $vars array easily
		$_encode = function ($v) use (&$vars){
			$key    = count($vars);
			$vars[] = $v;
			return "<<$key>>";
		};

		// fix the single quote not decoded properly with IntlDateFormatter
		$format = preg_replace_callback('/\\\'/', function ($m) use ($_encode){ return $_encode($m[0]); }, $format);

		// escape the '\text' type of text
		$format = preg_replace_callback('/\\\([a-z_-]+)/', function ($m) use ($_encode){ return $_encode($m[1]); }, $format);

		// escape the '`text`' type of text
		$format = preg_replace_callback('/`([^`]+)`/', function ($m) use ($_encode){ return $_encode($m[1]); }, $format);

		// escape the vars
		$format = preg_replace_callback('/\{{1,2}\s*(.+?)\s*\}{1,2}/', function ($m) use ($_encode){ return $_encode('{'.$m[1].'}'); }, $format);

		// type (default -> ICU)
		$symbols = [];
		if ($args['type'] === 'strftime'){
			// TODO
		// transfrom the default format symbols to ICU 
		}else if (!$args['type'] || $args['type'] === 'date'){
			$is_en = $lang ? preg_match('/^en/i', $lang) : true; // is English, used in some cases for the format values

			$symbols = [
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
				'a' => 'aa',  			// am or pm
				// 'a' => '{lower:aa}', // am or pm
				'A' => 'aa',            // AM or PM
				'h' => 'hh',            // 01 through 12
				'H' => 'HH',            // 00 through 23
				'g' => 'h',             // 1 through 12
				'G' => 'H',             // 0 through 23
				'i' => 'mm',            // 00 to 59
				's' => 'ss',            // 00 through 59
				'u' => 'S',             // Example: 654321
				'e' => 'V',             // Examples: UTC, GMT, Atlantic/Azores
				'P' => '{date:P}',		// Difference to Greenwich time
				'S' => $is_en ? '{date:S}' : '', // Example: "st, nd, rd or th", only available in E
			];
		}

		$format = $symbols ? strtr($format, $symbols) : $format;

		// encode some symbols since ICU doesn't understand them (or if we use alternate dictionaries)
		$format = strtr($format, [
			'aa'   => $am_pm ? '{date:am_pm_upper}' : '{upper:aa}',  // have the am/pm to be lowercase
			'a'    => $am_pm ? '{date:am_pm_lower}' : '{lower:aa}',   // have the am/pm to be lowercase
			'MMMM' => $months_full ? '{date:month_full}' : 'MMMM',
			'MMM'  => $months_short ? '{date:month_short}' : 'MMM',
			'eeee' => $weeks_full ? '{date:week_full}' : 'eeee',
			'eee'  => $weeks_short ? '{date:week_short}' : 'eee',
		]);
		
		// decode the encoded symbols // TODO review this, might be a bit complex for nothing
		$format = preg_replace_callback('/\{{1,2}\s*(.+?)\s*\}{1,2}/', function ($m) use ($_encode, $date, $lang, $months_full, $months_short, $weeks_full, $weeks_short, $am_pm){
			$value  = trim($m[1]);
			$pair   = explode(':', $value);
			$prefix = count($pair) > 1 ? $pair[0] : '';
			$value  = count($pair) > 1 ? $pair[1] : $value;

			if ($prefix === 'upper'){
				$value = IntlDateFormatter::formatObject($date, $value, $lang);	
				$value = strtoupper($value);
			}else if ($prefix === 'lower'){
				$value = IntlDateFormatter::formatObject($date, $value, $lang);	
				$value = strtolower($value);
			}else if ($prefix === 'date' && $value === 'am_pm_upper'){
				$value = $date->format('A');
				$value = _get($am_pm, $value, $value);
			}else if ($prefix === 'date' && $value === 'am_pm_lower'){
				$value = $date->format('a');
				$value = _get($am_pm, $value, $value);
			}else if ($prefix === 'date' && $value === 'month_full'){
				$i     = $date->format('n') - 1;
				$value = $date->format('F');
				$value = _get($months_full, $i, $value);
			}else if ($prefix === 'date' && $value === 'month_short'){
				$i     = $date->format('n') - 1;
				$value = $date->format('M');
				$value = _get($months_short, $i, $value);
			}else if ($prefix === 'date' && $value === 'week_full'){
				$i     = $date->format('N') - 1;
				$value = $date->format('l');
				$value = _get($weeks_full, $i, $value);
			}else if ($prefix === 'date' && $value === 'week_short'){
				$i     = $date->format('N') - 1;
				$value = $date->format('D');
				$value = _get($weeks_short, $i, $value);
			}else if ($prefix === 'date'){
				$value = $date->format($value);
			}

			return $_encode($value);
		}, $format);
		
		$format = IntlDateFormatter::formatObject($date, $format, $lang);	

		// re-add the variables
		$value = preg_replace_callback('/\<\<(\d+)\>\>/', function ($m) use (&$vars){ 
			$key   = $m[1];
			$value = $vars[$key];
			return $value;
		}, $format);

		// replace string values
		$value = $args['replace'] ? strtr($value, $args['replace']) : $value;

		// replace variables
		$value = $args['data'] ? _replace($value, $args['data']) : $value;

		// apply global filter
		$value = _filters('ffto_to_date/value', $value, $date, $args);
	}

	// html format -------------------------------------------------------------
	$timestamp = $date->getTimestamp();
	$format    = $args['attr_format'] ? $args['attr_format'] : ($has_time ? ':datetime' : ':date');
	
	if ($format === ':time')			$format = 'H:i:s';
	else if ($format === ':date')		$format = 'Y-m-d';
	else if ($format === ':datetime')	$format = 'Y-m-d\TH:i:s';

	$datetime  = date($format, $timestamp);
	$text      = is_string($value) ? $value : $date->format($format);
	$html 	   = $text;
	
	if ($wrap = $args['wrap']){
		// [ ] add custom classnames, like '&--today' if using ':today' attrs, ...

		$html = __html('time', $wrap, [
			'datetime' => "{$datetime}Z",
			'html'     => $text
		]);
	}

	if ($return = $args['return']){
		$month       = $date->format('n');
		$season      = null;
		$year        = $date->format('Y');
		$compare     = ffto_to_date($args['compare'], ['format'=>false, 'timezone'=>$date->getTimezone()], null);
		$time_of_day = null;

		switch ($month){
			case 12: case 1: case 2: 	$season = 'winter'; $year--; break; // since it can be december of the previous year, the year value is the previous too 
			case 3: case 4: case 5: 	$season = 'spring'; break;
			case 6: case 7: case 8: 	$season = 'summer'; break;
			case 9: case 10: case 11: 	$season = 'fall'; break;
		}

		// time of day (morning 12am-12pm, afternoon 12pm-5pm, evening 5pm-9pm, night 9pm-12am)
		if ($time){
			$_time = $date->format('H:m:s');

			if ($_time > '21:00:00') 		$time_of_day = 'night';
			else if ($_time > '17:00:00') 	$time_of_day = 'evening';
			else if ($_time > '12:00:00') 	$time_of_day = 'afternoon';
			else 							$time_of_day = 'morning';
		}

		// [ ] Deal with "is_before_now", since there might be a tiny microsecond different between $date and $now creation

		$obj = [
			'date'          => $date,
			'has_time'      => !!$time,
			'timezone'      => $date->getTimezone()->getName(),
			'text'			=> $text,
			'html'			=> $html,
			'utc'           => date('Y-m-d H:i:s e', $date->getTimestamp()),
			'season'		=> $season,
			'season_year'	=> $year,
			'time_of_day'   => $time_of_day,
			'is_weekend'	=> $date->format('N') >= 6,
			'is_today'      => $date->format('Y-m-d') === $now->format('Y-m-d'),
			'is_this_month' => $date->format('Y-m') === $now->format('Y-m'),
			'is_this_year'  => $is_current_year,
			'is_before_now' => $date < $now,
			'is_after_now'  => $date > $now,
			'is_passed'     => $date > $compare,
			'is_upcoming'   => $date < $compare,
			'is_same_day' 	=> $date->format('Y-m-d') === $compare->format('Y-m-d'),	// with the compare value
			'is_same_month' => $date->format('Y-m') === $compare->format('Y-m'),		// with the compare value
			'is_same_year'  => $date->format('Y') === $compare->format('Y'),			// with the compare value
		];

		$obj = _filters('ffto_to_date/obj', $obj, $date, $args);

		if (ffto_return_object($return)){
			return $obj;
		}else{
			return _get($obj, $return);
		}
	}

	return is_string($value) ? $html : $value;
}

/**
 * Decode a date range and return the formated html OR an array with a ton of data about those dates.
 * 
 * When using $args.wrap, special attributes can be used:
 * - ":dates": Adds classname indicating if it has 1, 2 or no dates
 * - ":times": Adds classname indicating if it has 1, 2 or no times
 * - ":all": Adds all special attributes
 * 
 * ```php
 * ffto_to_daterange('2024-12-05', '2025-10-05 10pm');
 * // <div class="daterange">
 * // 	<span class="daterange__dates"><time class="daterange__date-start" datetime="2024-12-05Z">December 5, 2024</time> to <time class="daterange__date-end" datetime="2025-10-06Z">October 5, 2025</time></span>, <span class="daterange__times"><time class="daterange__time-end" datetime="02:00:00Z">10 PM</time></span>
 * // </div>
 * 
 * ffto_to_daterange('2024-12-05', '2025-10-05 10pm', null, 'text');
 * // "December 5, 2024 to October 5, 2025, 10 PM"
 * 
 * ffto_to_daterange('2024-12-05', '2025-10-05 10pm', true);
 * // Returned object with tons of data (eg. has_time, is_live, is_passed, text, end_utc, ...)
 * // ["timezone"=>"America/Toronto", "start"=>...]
 * 
 * ffto_to_daterange('2024-12-05', '2025-10-05 10pm', 'Y.m.d', 'text');
 * // "2024.12.05 to 2025.10.05, 10 PM"
 * 
 * ffto_to_daterange('2024-12-05', '2025-10-05 10pm', 'time_format=`time ->` h:i', 'text');
 * // "December 5, 2024 to October 5, 2025, time -> 10:00"
 * 
 * ffto_to_daterange([
 * 	'start' => '2024-12-05',
 * 	'end'   => '2025-10-05 10pm',
 * 	'time'  => false,
 * ], null, 'return=text');
 * // "December 5, 2024 - October 5, 2025"
 * ```
 * @todo add a bunch of default date templates for all formats
 * @todo maybe be able to set the lang here
 * 
 * @param string|array|DateTime $start	Start date time
 * @param string|array|DateTime $end 	End date time
 * @param array $args 
 * 	- 'time'			[null] true/false will add/remove the time from the dates
 * 	- 'format'			[null] format the date
 * 	- 'time_format'		[null] true/null will show with default time format, false will not show the time at all (if, for example, the time is already in the date format)
 * 	- 'group_format'	['Y-m-d'] a special attribute to identify grouped items (eg.: all daterange in the same day, then we use `arr_to_group()`)
 * 	- 'template'		[$config.date/templates] join templates for dates and times
 * 	- 'timezone'		[null] 
 * 	- 'input_timezone'	[null] 
 * 	- 'alias'			[daterange] 
 * 	- 'wrap'			[true] can use special "attrs", like ':dates' to show extra classnames, ...
 * 	- 'return'			[null] return object OR a specific property	
 * 
 * @return mixed
 */
function ffto_to_daterange ($start, $end=null, $args=null, $return=null){
	if (!$start && !$end) return;

	// return object if $args = true
	if ($args === true){
		$args = ['return'=>'object'];
	}

	$args = _args($args, [
		'time'           => null,                        
		'format'         => null,						 
		'time_format'    => null,
		'group_format'	 => 'Y-m-d',
		'template'       => _config('date/templates'),
		'timezone'       => null,
		'input_timezone' => null,
		'alias'          => 'daterange',
		'wrap'           => true,
		'return'         => $return,
	], 'format', 'ffto_to_daterange/args');

	// decoding dates --------------------------------------------------------------------------------------------------
	// formats: 
	// - $start = ['start'=>'2021-04-01', 'end'=>'2021-05-25', 'time'=>false]
	// - $start = '2021-04-01', $end = '2021-05-25'
	// - $start = new DateTime('now'), $end = null
		
	// case: ['date'=>['start'=>..., 'end'=>...], 'time'=>['start'=>..., 'end'=>...]]
	if (!$end && ffto_has($start, 'date')){
		$_start      = _get($start, 'date/start');
		$_end        = _get($start, 'date/end');
		$_time_start = _get($start, 'time/start');
		$_time_end   = _get($start, 'time/end');
		$start       = ['date'=>$_start, 'time'=>$_time_start];
		$end         = ['date'=>$_end, 'time'=>$_time_end];
	// case: $start value is an array with both 'start' and 'end' values (and $end is null)
	}else if (
		!$end && 
		($_start = _get($start, 'start')) &&
		($_end = _get($start, 'end'))
	){
		$_time = _get($start, 'time || has_time');
		$start = _args($_start, ['date'=>null, 'time'=>$_time], 'date');
		$end   = _args($_end, ['date'=>null, 'time'=>$_time], 'date');
	}
		
	// $start and $end might have different version of specifiying it's time
	$start = [
		'date' => _get($start, 'date', $start),
		'time' => _get($start, 'time_start || has_time || time'),
	];
	$end = [
		'date' => _get($end, 'date', $end),
		'time' => _get($end, 'time_end || has_time || time'),
	];

	$_args = [
		'time'           => $args['time'],
		'timezone'       => $args['timezone'],
		'input_timezone' => $args['input_timezone'],
		'format'         => false,
		'return'         => true
	];

	$start = ffto_to_date($start, $_args);
	$end   = ffto_to_date($end, $_args);
	
	// invalid date
	if (!$start) return null;

	// reverse start/end if in the wrong order
	if ($start && $end && $start['date'] > $end['date']){
		$start = [$end, $end = $start][0];
	}

	// conditions ------------------------------------------------------------------------------------------------------
	$_now   = ffto_to_date('now', ['format'=>false, 'timezone'=>$args['timezone']], null);
	$_start = $start['date'];
	$_end   = clone ($end ? $end['date'] : $start['date']);
	
	// make sure it's the end of the day (if TIME = false)
	ffto_is_falsy($args['time']) && $_end->setTime(23, 59, 59);

	$is_passed       = false;
	$is_live         = false;
	$is_upcoming     = false;
	$time_since      = 0;
	$time_until 	 = 0;
	$time_passed 	 = 0; 	
	$progress		 = 0; 
	
	// check of the dates are upcoming/currently live/passed and get the time since/until/passed
	if ($_now >= $_start && $_now <= $_end){
		$is_live  		= true;
		$ratio_start 	= $_now->getTimestamp() - $_start->getTimestamp();
		$ratio_end      = $_end->getTimestamp() - $_start->getTimestamp();
		$time_passed 	= $_now->getTimestamp() - $_start->getTimestamp();
		$progress 		= $ratio_end ? $ratio_start / $ratio_end : 0;
	}else if ($_now > $_end){
		$is_passed  = true;
		$time_since = $_now->getTimestamp() - $_end->getTimestamp();
	}else{
		$is_upcoming = true;
		$time_until	 = $_start->getTimestamp() - $_now->getTimestamp();
	}

	// conditions:date ---------------------------------------------------------
	$_end			 = $end ? $end['date'] : null;
	$is_same_year    = false;
	$is_same_month   = false;
	$is_same_day     = false;
	$is_same_time    = false;
	$is_same_daytime = false; // same day AND same time

	if ($start && $end){
		$is_same_year    = $_start->format('Y') === $_end->format('Y');
		$is_same_month   = $_start->format('Yn') === $_end->format('Yn');
		$is_same_day     = $_start->format('Ynj') === $_end->format('Ynj');
		$is_same_time    = $_start->format('H:i:s') === $_end->format('H:i:s');
		$is_same_daytime = $is_same_day && $is_same_time;
	}

	// conditions:time ---------------------------------------------------------
	$has_start_time      = false;
	$has_end_time        = false;
	$has_time            = false;
	$is_short_start_time = false;
	$is_short_end_time   = false;

	$has_start_time      = $start['has_time'];
	$is_short_start_time = $_start->format('i') === '00';

	if ($end){
		$has_end_time      = $end['has_time'];
		$is_short_end_time = $_end->format('i') === '00';	
	}

	$has_time         = $has_start_time || $has_end_time;
	$is_same_meridiem = $has_start_time && $has_end_time ? $_start->format('a') === $_end->format('a') : false;

	// html ------------------------------------------------------------------------------------------------------------
	$alias     = $args['alias'];
	$templates = _get($args, 'template || templates', []);

	if (is_array($templates)){
		$templates = _args($templates, [
			'*'        => __tx('date-template', '{start} to {end}'),          // fr: '{start} au {end}'
			'time'     => __tx('date-template/time', '{start} to {end}'),     // fr: '{start} à {end}'
			'single'   => __tx('date-template/single', '{start}'),            // fr: '{start}'
			'datetime' => __tx('date-template/datetime', '{date}, {time}'),   // fr: '{date}, {time}'
		], '*');
	}
	
	// html:date ---------------------------------------------------------------
	$format = $args['format'] ? $args['format'] : [];
	if (is_array($format)){
		$format = _args($format, [
			'*'                => ':text',                              // fr: 'j F Y'
			'same-month-start' => __tx('date-format', 'F j'),      // fr: 'j'
			'same-month-end'   => __tx('date-format', 'j, Y'),     // fr: 'j F Y'
			'same-year-start'  => __tx('date-format', 'F j'),      // fr: 'j F'
			'same-year-end'    => __tx('date-format', 'F j, Y'),   // fr: 'j F Y'
		], '*');
	}

	$start_html = null;
	$end_html   = null;
	$date_html  = null;

	if ($format !== false){
		$start_format = ffto_to_conditional_format($_start, $format, [
			'start'            => true,
			'same-month-start' => $is_same_month,
			'same-year-start'  => $is_same_year,
			'same-daytime'	   => $is_same_daytime,
			'diff-month-start' => !$is_same_month,
			'diff-year-start'  => !$is_same_year,
			'time'			   => $has_start_time,
			'time-start'	   => $has_start_time,
			'short-time-start' => $is_short_start_time,
		], 'date/formats');

		$end_format = $_end ? ffto_to_conditional_format($_end, $format, [
			'end'            => true,
			'same-month-end' => $is_same_month,
			'same-year-end'  => $is_same_year,
			'same-daytime'   => $is_same_daytime,
			'diff-month-end' => !$is_same_month,
			'diff-year-end'  => !$is_same_year,
			'end-time'       => $has_end_time,
			'short-end-time' => $is_short_end_time,
			'time'           => $has_end_time,
			'time-end'       => $has_end_time,
			'short-time-end' => $is_short_end_time,
		], 'date/formats') : null;

		$start_html = ffto_to_date($_start, [
			'format' => $start_format,
			'wrap'   => ".{$alias}__date-start"
		]);
		$end_html = $_end ? ffto_to_date($_end, [
			'format' => $end_format,
			'wrap'   => ".{$alias}__date-end"
		]) : null;

		// inverse the dates, if the end exists but not the start
		$s_html = $start_html;
		$e_html = $end_html;
		if (!$s_html && $e_html){
			$s_html = [$e_html, $e_html = $s_html][0];
		}
				
		$date_template = ffto_to_conditional_format(['start'=>$_start, 'end'=>$_end], $templates, [
			'single'     => !$e_html,
			'passed'     => $is_passed,
			'live'       => $is_live,
			'upcoming'   => $is_upcoming,
			'same-day'   => $is_same_day,
			'same-month' => $is_same_month,
			'same-year'  => $is_same_year,
		]);
		
		$date_html = $date_template ? _replace($date_template, [
			'start' => $s_html,
			'end'   => $e_html,
		]) : '';

		$date_html = $date_html ? '<span class="'.$alias.'__dates">'.$date_html.'</span>' : '';	
	}	

	// html:time ---------------------------------------------------------------
	$time_format = $args['time_format'] === null || $args['time_format'] === true ? [] : $args['time_format'];
	if (is_array($time_format)){
		$time_format = _args($time_format, [
			'*'                          => ':time-text',                                         // fr: 'G \h i'
			'short'                      => __tx('time-format/short-text', 'g a'),                // fr: 'G \h'
			'same-meridiem-start'        => __tx('time-format/same-meridiem-start', 'g:i'),       // fr: 'G \h i'
			'short, same-meridiem-start' => __tx('time-format/short,same-meridiem-start', 'g'),   // fr: 'G \h'
		]);
	}

	$start_time_html = '';
	$end_time_html   = '';
	$time_html 		 = '';

	if ($time_format){
		$start_time_format = $has_start_time ? ffto_to_conditional_format($_start, $time_format, [
			'start'               => true,
			'short'               => $is_short_start_time,
			'short-start'         => $is_short_start_time,
			'same-meridiem'       => $is_same_meridiem,
			'same-meridiem-start' => $is_same_meridiem,
		], 'date/time_formats') : null;

		$end_time_format = $has_end_time ? ffto_to_conditional_format($_end, $time_format, [
			'end'               => true,
			'short'             => $is_short_end_time,
			'short-end'         => $is_short_end_time,
			'same-meridiem'     => $is_same_meridiem,
			'same-meridiem-end' => $is_same_meridiem,
		], 'date/time_formats') : null;
		
		$start_time_html = $start_time_format ? ffto_to_date($_start, [
			'format'      => $start_time_format,
			'wrap'        => ".{$alias}__time-start",
			'attr_format' => ':time'
		]) : '';

		$end_time_html = $end_time_format ? ffto_to_date($_end, [
			'format'      => $end_time_format,
			'wrap'        => ".{$alias}__time-end",
			'attr_format' => ':time'
		]) : '';

		// inverse the times, if the end exists but not the start
		$s_html = $start_time_html;
		$e_html = $end_time_html;
		if (!$s_html && $e_html){
			$s_html = [$e_html, $e_html = $s_html][0];
		}
					
		$time_template = ffto_to_conditional_format(['start'=>$_start, 'end'=>$_end], $templates, [
			'time'        => true,
			'single'      => !$e_html,
			'time-single' => !$e_html,
		]);
			
		$time_html = $time_format ? _replace($time_template, [
			'start' => $s_html,
			'end'   => $e_html,
		]) : '';
			
		$time_html = $time_html ? '<span class="'.$alias.'__times">'.$time_html.'</span>' : '';	
	}

	// html:datetime -----------------------------------------------------------
	$single_html = null;
	if ((!!$date_html) !== (!!$time_html)){
		$single_html = $date_html ? $date_html : $time_html;
	}

	// deal with showing the TIME or not
	$html_template = ffto_to_conditional_format([
		'start' => $_start,
		'end'   => $_end,
		'date'  => $date_html,
		'time'  => $time_html,
	], $templates, [
		'single'   => !!$single_html,
		'datetime' => $date_html && $time_html,
	]);
	
	$html = $html_template ? _replace($html_template, [
		'start' => $single_html ? $single_html : $date_html,
		'end'   => $time_html,
		'date'  => $date_html,
		'time'  => $time_html,
	]) : '';
		
	// wrap/custom attributes
	if ($wrap = $args['wrap']){
		// [ ] add more custom attributes: &--passed, &--live, &--upcoming, ...

		// custom attributes
		$_dates = _get($wrap, ':dates || :all');
		$_times = _get($wrap, ':times || :all');
		$class  = ['&'];
		
		if ($_dates){
			$class[] = ($start_html || $end_html ? '&--has-dates' : null);
			if ($start_html && $end_html) $class[] = '&--2-dates';
			if ($start_html || $end_html) $class[] = '&--1-date';
			else 						  $class[] = '&--no-dates';
		}

		if ($_times){
			$class[] = ($start_time_html || $end_time_html ? '&--has-times' : null);
			if ($start_time_html && $end_time_html) $class[] = '&--2-times';
			if ($start_time_html || $end_time_html) $class[] = '&--1-time';
			else 						  			$class[] = '&--no-times';
		}

		$html = __html($wrap, [
			'alias' => $alias,
			'html'  => $html,
			'class' => $class,
		]);
	}

	// return ----------------------------------------------------------------------------------------------------------
	if ($return = $args['return']){
		$obj = [
			'timezone'            => $_start ? $_start->getTimezone()->getName() : null,
			'start'               => $_start,
			'start_full'          => $_start ? ffto_to_date($_start, 'format=:full&wrap=0') : null,
			'start_utc'           => $_start ? ffto_to_date($_start, 'format=:utc&wrap=0') : null,
			'start_html'          => $start_html,
			'start_text'          => trim(strip_tags($start_html)),
			'start_time_html'     => $start_time_html,
			'start_time_text'     => trim(strip_tags($start_time_html)),
			'end'                 => $_end,
			'end_full'            => $_end ? ffto_to_date($_end, 'format=:full&wrap=0') : null,
			'end_utc'             => $_end ? ffto_to_date($_end, 'format=:utc&wrap=0') : null,
			'end_html'            => $end_html,
			'end_text'            => trim(strip_tags($end_html)),
			'end_time_html'       => $end_time_html,
			'end_time_text'       => trim(strip_tags($end_time_html)),
			'date_html'           => $date_html,
			'date_text'           => trim(strip_tags($date_html)),
			'time_html'           => $time_html,
			'time_text'           => trim(strip_tags($time_html)),
			'html'                => $html,
			'text'                => trim(strip_tags($html)),
			'group'               => $_start ? ffto_to_date($start, $args['group_format']) : null,
			'group_utc'           => $_start ? ffto_to_date($start, ['format'=>$args['group_format'], 'timezone'=>'utc']) : null,
			'has_time'            => $has_time,
			'has_start_time'      => $has_start_time,
			'has_end_time'        => $has_end_time,
			'is_start_time_short' => $is_short_start_time,
			'is_end_time_short'   => $is_short_end_time,
			'is_same_meridiem'    => $is_same_meridiem,
			'is_same_day'         => $is_same_day,
			'is_same_month'       => $is_same_month,
			'is_same_year'        => $is_same_year,
			'is_live'             => $is_live,
			'is_passed'           => $is_passed,
			'is_upcoming'         => $is_upcoming,
			'is_same_time'        => $is_same_time,
			'time_until'          => $time_until,
			'time_passed'         => $time_passed,
			'time_since'          => $time_since,
			'progress'            => $progress,
		];

		$obj = _filters('ffto_to_daterange/obj', $obj, $_start, $_end, $args);

		/*
		$obj = [
			'is_today'      => $date->format('Y-m-d') === $now->format('Y-m-d'),
			'is_this_month' => $date->format('Y-m') === $now->format('Y-m'),
			'is_this_year'  => $is_current_year,
			'is_before_now' => $date < $now,
			'is_after_now'  => $date > $now,
		];
		*/

		if (ffto_return_object($return)){
			return $obj;
		}else{
			return _get($obj, $return);
		}
	}

	return is_string($html) && $return !== false ? $html : [
		'start' => $_start,
		'end'   => $_end,
	];
}

function ffto_to_dates ($dates, $args=null, $return=null){
	$args = _args($args, [
		'join'     => null,
		'empty'    => null,
		'label'    => true,
		// 'template' => null,
		'return'   => null,
		// 'template_single' => null,
		// 'template_many'   => null,
	], 'return');

	$label    = null;
	$has_time = null;
	
	// Object type, can be a special from the ACF Field: "group_hidden-dates.json". Or something like:
	// [
	//		'date'=>['start'=>'...', 'end'=>'...'],
	//		'time'=>['start'=>'...', 'end'=>'...'],
	//		'label'=>null
	// ]
	if (ffto_has($dates, 'date')){
		$type  = _get($dates, 'date/type || type', null);
		$start = _get($dates, 'date/start || date_start || start');
		$end   = _get($dates, 'date/end || date_end || end');

		if (!$type){
			$type = $start && $end ? 'range' : 'single';
		}

		$label      = _get($dates, 'more/label || label');
		$has_time   = _get($dates, 'time/has_time || has_time || time', false);
		$time_start = $has_time ? _get($dates, 'time/start || time_start') : null;
		$time_end   = $has_time ? _get($dates, 'time/end || time_end') : null;
		
		$dates = [];
		if ($type === 'single' && $start){
			$dates[] = [
				'date'       => $start,
				'time_start' => $time_start,
				'time_end'   => $time_end,
			];
		}else if ($type === 'range' && $start){
			$dates[] = [
				'start' => ['date'=>$start, 'time'=>$time_start],
				'end'   => ['date'=>$end, 'time'=>$time_end],
			];
		}else if ($type === 'multi'){
			$dates = _get($dates, 'dates/items || items || dates', []);			
		}else if ($time_start){
			$dates[] = [
				'date'       => null,
				'time_start' => $time_start,
				'time_end'   => $time_end,
			];
		}		
	}else if (!ffto_is_list($dates)){
		$dates = [$dates];
	}

	$dates = empty($dates) ? [] : $dates;
	
	// Parse and get the dates
	$start  = null;
	$end    = null;
	$_dates = [];
	foreach ($dates as $dt){
		$dt = ffto_to_daterange($dt, null, $args, true);
		if (!$dt) continue;

		$s            = _get($dt, 'start');
		$e            = _get($dt, 'end', $s);
		$start        = max($start, $s);
		$end          = max($end, $e);
		$key          = $s->format('Y-m-d H:i:s');
		$_dates[$key] = $dt;
	}
	
	// order the dates
	ksort($_dates);
	$_dates = array_values($_dates);
	
	// format/text version

	return $_dates;
}

/*
Time/date patterns in EN:

Times:
4 pm (single time)
4 pm, 5 pm and 7 pm (separate times)
4 to 5 pm (time range: remove the first am/pm if both are the same)
10 am to 5 pm (time range)

Multiple dates:
August 3 to 4, 2024 (date range, same month, same year)
August 3, 4 and 7, 2024 (multiple dates, same month and year)
August 3 to September 4, 2024 (date range, different months, same year)
August 3, 2024 to September 5, 2025 (date range, different months and years) 
August 3, 2024, November 27, 2024, and September 5, 2025 (multiple/distinct dates, different months and years) 

Putting dates and times together:
August 3 to 4, 2024, 4pm
August 3, 4 and 7, 2024, 4pm
August 3 to September 4, 2024, 4 pm, 5 pm and 7 pm
August 3, 2024 and September 5, 2024, 4pm 
*/