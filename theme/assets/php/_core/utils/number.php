<?php 
function math_less_than ($value, $values, $equal=false){
	foreach ($values as $v){
		if (($equal && $value <= $v) || (!$equal && $value < $v)){
			continue;
		}
		return false;
	}
	return true;
}

function math_more_than ($value, $values, $equal=false){
	foreach ($values as $v){
		if (($equal && $value >= $v) || (!$equal && $value > $v)){
			continue;
		}
		return false;
	}
	return true;
}

function math_clamp ($value, $min, $max=null){
	if ($max === null){
		$max = $min;
		$min = 0;
	}
	return max($min, min($max, $value));
}

function math_is_between ($value, $min, $max){
	/*
	if (min > max){
		min = [max, min = max][0];
	}
	*/
	return $value >= $min && $value <= $max;
}

function calculate ($nbr, $units=array(), $args=''){
	if (is_numeric($units)){
		$units = array('%' => $units);
	}	
	if (!isset($units['%'])){
		$units['%'] = 1;
	}

	if (is_numeric($args)){
		$args = array('offset' => $args);
	}

	$args = to_args($args, array(
		'vars'   => null,
		'offset' => 0,
	));

	$is_floor = false;
	$is_round = false;
	$is_ceil  = false;
	$is_half  = false;

	if (is_string($nbr)){
		$is_floor = strpos($nbr, '!floor') !== false;
		$is_round = strpos($nbr, '!round') !== false;
		$is_ceil  = strpos($nbr, '!ceil') !== false;			
		$is_half  = strpos($nbr, '!half') !== false;	// add half a pixel
	}

	// if there's variable to replace (eg.: (2n + 45))
	// if (utils.is.str(nbr) && args.vars){
	// 	var i;
	// 	var re = [];
	// 	for (i in args.vars) re.push(i);
		
	// 	re = new RegExp('(\-?\\d+(?:\\.\\d+)?)?(' + re.join('|') + ')', 'g');

	// 	nbr = nbr.replace(re, function (m, $1, $2){
	// 		var m = parseFloat($1) || 1;
	// 		var n = args.vars[$2];
	// 		return n  * m;
	// 	});
	// }

	if (!isset($nbr)){
		$nbr = 0;
	}else if (is_numeric($nbr)){
		$nbr = (float)$nbr;
	}else if ($nbr === '?' || $nbr === 'random'){
		$nbr = random() * $units['%'];
	}else if (is_string($nbr) && !preg_match('/^[^0-9+-.%]$/', $nbr)){
		$nbr = (float)$nbr / 100 * $units['%'];
	}else if (is_string($nbr)){
		// // random numbers (the previous ifs are shortcuts when the string is simpler, makes things faster)
		// nbr = nbr.replace(/\?/g, function (){
		// 	return Math.random() * units['%'];
		// });

		// nbr = nbr.replace(CONSTS.RE.NUMBER_WITH_UNIT, function (m, $1, $2){
		// 	var n = parseFloat($1);

		// 	if ($2 in units){
		// 		n = (n / 100) * units[$2]; 
		// 	}else{
		// 		n = m;
		// 	}

		// 	return n;
		// });
	}

	if (is_string($nbr)){
		$nbr = preg_replace('/[^0-9\+\-\*\/\.\(\)]/', '', $nbr);

		try{ 
			eval('$nbr = ' . $nbr . ';'); 
		}catch (Exception $e){}
	}
	
	if ($args['offset']){
		$nbr += $args['offset'];
	}

	if ($is_floor) $nbr = floor($nbr);
	if ($is_round) $nbr = round($nbr);
	if ($is_ceil)  $nbr = ceil($nbr);
	if ($is_half)  $nbr = floor($nbr) + 0.5; 	// half decimals, good to graphic when we need half a pixel
	
	return $nbr;
}

// Number ----------------------------------------------------------------------
define('COUNTERS', 'counters');

function to_human_filesize ($size, $decimals=2, $units=null){
    $units 	= $units ? $units : array('b','kb','MB', 'GB', 'TB', 'PB');
	$index	= floor(log($size,1024));
	$total  = pow(1024, $index);
    return isset($units[$index]) ? ($total ? round($size/$total, $decimals) : 0).' '.$units[$index] : '-';
}

function to_romanic_number ($nbr, $upcase = true){
    $table 	= array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
	$return = '';
	
	while($nbr > 0){
        foreach($table as $rom=>$arb){
            if($nbr >= $arb){
                $nbr 	-= $arb;
                $return .= $rom;
                break;
            }
        }
    }

    return $return;
}

function to_number_value ($nbr, $args=''){
	$args = to_args($args, array(
		'prefix'            => '',
		'suffix'            => '',
		'decimals'          => 2,
		'force_decimals'    => false,
		'decimal_separator' => '.',
		'group_separator'   => ',',
		'zero_value'        => null,
	));

	if (!$nbr && $args['zero_value'] !== null){
		$nbr 	  = $args['zero_value'];
	}else{
		$nbr      = (float)$nbr;
		$decimals = fmod($nbr, 1);
		$nbr      = number_format($nbr, $decimals || $args['force_decimals'], $args['decimal_separator'], $args['group_separator']);
		$nbr	  = $args['prefix'] . $nbr . $args['suffix'];
	}

	return $nbr;
}

function to_human_time ($ms){	
	if ($ms < 60){
		$ms   = ceil($ms);
		$time = "{$ms} sec";
	}else{
		$mins  = floor(($ms%3600)/60);
		$hours = floor(($ms%86400)/3600);
		$days  = floor(($ms%2592000)/86400);
		
		$time = implode(' ', array_filter([
			$days ? "{$days} day".($days>1?'s':'') : null,
			$hours ? "{$hours} hour".($hours>1?'s':'') : null,
			$mins ? "{$mins} min".($mins>1?'s':'') : null,
		]));
	}

	return $time;
}

function to_counter ($key='*', $args=''){
	$args = to_args($args, array(
		'start'  => 0,
		'prefix' => '',
	), 'prefix');

	$counters 		= get_global(COUNTERS, array());
	$count    		= isset($counters[$key]) ? $counters[$key] : $args['start'];
	$counters[$key] = $count + 1;
	
	set_global(COUNTERS, $counters);

	return $args['prefix'] ? $args['prefix'] . $count : $count;
}