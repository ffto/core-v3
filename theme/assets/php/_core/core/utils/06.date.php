<?php 
/**
 * Decode a date and return it with multiple formatting options.
 *
 * @param string $date 
 * @param mixed $args 
 * 	- 'time' [null] either false to remove the time, true to keep it, a string to force a time
 * 	- 'format' [null] either a string, an array [* => '', current-year => ''], null = DateTime object, true for a bunch for format 
 * 		- :timestamp
 * 		- :utc-timezone 	= 'Y-m-d H:i:s e'
 * 		- :utc 				= 'Y-m-d H:i:s'
 * 		- :utc-date 		= 'Y-m-d'
 * 		- :utc-time 		= 'H:i:s'
 * 		- :time-ago
 * 		- :full 			= 'Y-m-d H:i:s';
 * 		- :date				= 'Y-m-d'
 * 		- :date-compact		= 'Ymd'
 * 		- :date-text		= 'F j, Y'
 * 		- :time				= 'H:i:s'
 * 		- :time-text		= 'g:i a'
 * 		- :atom				= 'Y-m-d\\TH:i:sP'
 * 		- :w3c				= 'Y-m-d\\TH:i:sP'
 * 
 * 	- 'timezone' [config:date/timezone] 
 * 	- 'update' [null] update the date, either a string or a numeric value
 * 	- 'compare' [now] comparing date for time-ago
 * 	- 'input_format' [null] way of decoding the date
 * 	- 'input_timezone' [config:date/input_timezone] 
 * 	- 'lang' [config:date/lang] change the language of the formating
 * 	- 'data' [null] replacing values in the format
 * 	- 'months' [config:date/months] labels for the months (for full months and short version). either a normal array, or with with 'full' and 'short' keys
 * 	- 'weeks' [config:date/weeks] labels for the weeks (for full weeks and short version). either a normal array, or with with 'full' and 'short' keys
 * 	- 'periods' [date/periods] labels for time-ago
 * 	- 'wrap' [false] return the format with a <time> wrapping the value
 * 
 * @param mixed $timezone 
 * @return mixed
 */
function to_date ($date='now', $args=null, $timezone=null){
	if (is_string($date) && strpos($date, ':') === true){
		$args = $date;
		$date = 'now';
	}
	
	$args = _args($args, [
		'time'           => null,                             // either false to remove the time, true to keep it, a string to force a time
		'format'         => null,                             // either a string, an array [* => '', current-year => ''], null = DateTime object, true = timestamp, :object will be eveything with data
		'timezone'       => _config('date/timezone'),
		'update'         => null,                             // update the date, either a string or a numeric value
		'compare'		 => 'now',						      // comparing date for time-ago
		'input_format'   => null,                            // way of decoding the date
		'input_timezone' => _config('date/input_timezone'),
		'lang'           => _config('date/lang'),             // change the language of the formating
		'data'           => null,                             // replacing values in the format
		'months'         => _config('date/months'),           // labels for the months (for full months and short version)
		'weeks'          => _config('date/weeks'),            // labels for the weeks (for full weeks and short version)
		'periods'        => _config('date/periods'),          // labels for time-ago
		// 'am_pm'          => null,                             // labels for AM/PM
		'wrap'			 => false,							  // return the format with a <time> wrapping the value
		// 'proxy'			 => false, 	// should the date returned (if object) be the proxy version
		// 'formatting' => null  // date, strftime, ICU (default to a ICU variant) // TODO dealing with the other formatting types
	], 'format', 'to_date-args');

	// decode array like this: ['date'=>'', 'time'=>'']
	$time = $args['time'];
	if (is_array($date)){
		$time = _get($date, 'time, has_time', $time);
		$date = _get($date, 'date');
	}

	// check if there's a time specified in the $date string
	if ($time === null && is_string($date)){
		$dt   = date_parse($date);
		$time = $dt['hour'] !== false || $dt['minute'] !== false || $dt['second'] !== false;
	}

	$input_format   = $args['input_format'];
	$input_timezone = _format($args['input_timezone']);
	$input_timezone = is_string($input_timezone) ? new DateTimeZone($input_timezone) : $input_timezone;

	// date creation
	if (!$date){
		$date = null;
	}else if (is_string($date) && $input_format){
		$date = DateTime::createFromFormat($input_format, $date, $input_timezone);
		$date = $date ? $date : null;
	}elseif (is_string($date) || is_numeric($date)){
		$date = is_int($date) ? date('Y-m-d H:i:s', $date) : $date;

		try{
			$date = new DateTime($date, $input_timezone);
		}catch (Exception $e){
			$date = null; 
		}
	}

	// no date could be decoded
	if (!$date) return false;

	// change the timezone	
	if ($timezone = $args['timezone']){
		$timezone = _format($args['timezone']);
		$timezone = is_string($timezone) ? new DateTimeZone($timezone) : $timezone;
		$timezone && $date->setTimezone($timezone);
	}
	$timezone = $date->getTimezone();

	// remove the time
	if ($time === false || $time === ':start'){
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
	$is_current_year = $date->format('Y') === $now->format('Y');
	$format          = _get($args, ($is_current_year ? 'format.current-year, format.*, format' : 'format.*, format'));
	$format 		 = _filters('to_date-format', $format, $date, $args);

	// return specific formats
	if (return_object($format)){
		$text   = $time ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
		$month  = $date->format('n');
		$season = null;

		switch ($month){
			case 12: case 1: case 2: 	$season = 'winter'; break;
			case 3: case 4: case 5: 	$season = 'spring';break;
			case 6: case 7: case 8: 	$season = 'summer'; break;
			case 9: case 10: case 11: 	$season = 'fall'; break;
		}

		return [
			'date'          => $date,
			'has_time'      => !!$time,
			'timezone'      => $date->getTimezone()->getName(),
			'text'			=> $text,
			'html'			=> '<time datetime="'.$text.'">'.$text.'</time>',
			'utc'           => date('Y-m-d H:i:s e', $date->getTimestamp()),
			'season'		=> $season,
			'is_passed'     => $now > $date,
			'is_upcoming'   => $now < $date,
			'is_today'      => $date->format('Y-m-d') === $now->format('Y-m-d'),
			'is_this_month' => $date->format('Y-m') === $now->format('Y-m'),
			'is_this_year'  => $is_current_year,
		];
	}else if ($format === ':timestamp'){
		return $date->getTimestamp() + $date->getOffset();
	}else if ($format === ':utc-timezone'){
		return $date->format('Y-m-d H:i:s e');
	}else if ($format === ':utc'){
		return $date->format('Y-m-d H:i:s');
	}else if ($format === ':utc-date'){
		return $date->format('Y-m-d');
	}else if ($format === ':utc-time'){
		return $date->format('H:i:s');
	}else if ($format === ':time-ago'){
		$periods = _args($args['periods'], [
			'second' => 'second',
			'minute' => 'minute',
			'hour'   => 'hour',
			'day'    => 'day',
			'week'   => 'week',
			'month'  => 'month',
			'year'   => 'year',
			'decade' => 'decade',
			'past'   => '{time} {period} ago',
			'future' => 'in {time} {period}',
		]);

		$compare = to_date($args['compare'], ['timezone'=>$date->getTimezone()]);
		$lengths = array(60,60,24,7,4.35,12,10);
		$past    = $periods['past'];
		$future  = $periods['future'];
		$periods = array_values($periods);
		$is_past = $date < $compare;
		$diff    = $is_past ? $compare->getTimestamp() - $date->getTimestamp() : $date->getTimestamp() - $compare->getTimestamp();
	
		for($i = 0; $diff >= $lengths[$i] && $i < count($lengths)-1; $i++) {
			$diff /= $lengths[$i];
		}	 
		$diff = round($diff);
	 
		if ($diff != 1) {
			$periods[$i] .= "s"; // add an "s" // TODO use a to_variant instead OR to_plural or something like that
		}
	
		return _replace($is_past ? $past : $future, [
			'time'   => $diff,
			'period' => $periods[$i],
		]);
	}else if (!$format){
		return $date;
	}

	if ($format === ':full')				$format = 'Y-m-d H:i:s';
	else if ($format === ':date')			$format = 'Y-m-d';
	else if ($format === ':date-compact')	$format = 'Ymd';
	else if ($format === ':date-text')		$format = 'F j, Y';
	else if ($format === ':time')			$format = 'H:i:s';
	else if ($format === ':time-text')		$format = 'g:i a';
	else if ($format === ':atom')			$format = DateTime::ATOM;
	else if ($format === ':w3c')			$format = DateTime::W3C;

	$text = $time ? $date->format('Y-m-d H:i:s') : $date->format('Y-m-d');
	$wrap = $args['wrap'] ? ['datetime'=>$text] : null;

	if (is_string($format)){
		$format       = trim($format);
		$months_full  = _get($args, 'months.full, months');
		$months_short = _get($args, 'months.short');
		$weeks_full   = _get($args, 'weeks.full, weeks');
		$weeks_short  = _get($args, 'weeks.short');
		$lang         = $args['lang'];
		$vars         = [];

		// fix the single quote not decoded properly with IntlDateFormatter
		$format = preg_replace_callback('/\\\'/', function ($m) use (&$vars){
			$vars[] = $m[0];
			return '@@@';
		}, $format);

		// escape the '\text' type of text
		$format = preg_replace_callback('/\\\([a-z_-]+)/', function ($m) use (&$vars){
			$vars[] = $m[1];
			return '@@@';
		}, $format);

		// escape the '`text`' type of text
		$format = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$vars){
			$vars[] = $m[1];
			return '@@@';
		}, $format);

		$format = strtr($format, [
			'aa'   => '{upper:aa}',                                   // have the am/pm to be lowercase
			'a'    => '{lower:aa}',                                   // have the am/pm to be lowercase
			'MMMM' => $months_full ? '{date:full_month}' : 'MMMM',
			'MMM'  => $months_short ? '{date:short_month}' : 'MMM',
			'eeee' => $weeks_full ? '{date:full_week}' : 'eeee',
			'eee'  => $weeks_short ? '{date:short_week}' : 'eee',
		]);

		$format = preg_replace_callback('/\{{1,2}\s*(.+?)\s*\}{1,2}/', function ($m) use (&$vars, $date, $lang, $months_full, $months_short, $weeks_full, $weeks_short, $args){
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
			}else if ($prefix === 'date' && $value === 'full_month'){
				$i     = $date->format('n') - 1;				
				$value = _get($months_full, $i);
			}else if ($prefix === 'date' && $value === 'short_month'){
				$i     = $date->format('n') - 1;
				$value = _get($months_short, $i);
			}else if ($prefix === 'date' && $value === 'full_week'){
				$i     = $date->format('N') - 1;
				$value = _get($weeks_full, $i);
			}else if ($prefix === 'date' && $value === 'short_week'){
				$i     = $date->format('N') - 1;
				$value = _get($weeks_short, $i);
			// get a value from the normal date->format function
			}else if ($prefix === 'date'){
				$value = $date->format('N') - 1;
			// replace other variables
			}else{
				$value = _get($args['data'], $value);
			}

			$vars[] = $value;

			return '@@@';
		}, $format);

		/*
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
			// 'a' => '{lower:aa}',  // am or pm
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
		$format = strtr($format, $symbols);
		*/
		
		$format = IntlDateFormatter::formatObject($date, $format, $lang);	
		$date   = preg_replace_callback('/@@@/', function ($m) use (&$vars){
			return array_shift($vars);
		}, $format);

		$date = _filters('to_date-value', $date, $args);
	}

	// TODO replace this with "_html()" instead
	if (is_string($date) && $wrap){
		$date = '<time datetime="'.$wrap['datetime'].'">'.$date.'</time>';
	}

	return $date;
}