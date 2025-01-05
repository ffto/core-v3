<?php 
/* =====================================================================================================================
Quick
===================================================================================================================== */
/**
 * Prepare a SQL value in the right format
 * 
 * ```php
 * _prepare([54,4]);
 * // (54,4)
 * 
 * _prepare('John');
 * // "'John'"
 * 
 * _prepare('John', 'like=start');
 * // "'John%'"
 * 
 * _prepare(new DateTime());
 * // '2024-01-31 12:00:19'
 * ```
 *
 * @param mixed $v 
 * @param string $args 
 * 	- 'wrap' [true] If $v is an array, it will be wrapped with parentheses 
 * 	- 'like' [false] The string is a like, so it needs '%' (start, end, true for both)
 * @return string
 */
function _prepare ($v, $args=''){
	$args = _args($args, array(
		'wrap' => true,
		'like' => false,
	));

	// turn the array into a "IN" kinda value
	if (is_array($v)){
		$v = array_map('_prepare', $v);
		$v = implode(',', $v);
		return $args['wrap'] ? "({$v})" : $v;
	}

	if (is_null($v)){
		$v = 'NULL';
	}else if (is_bool($v)){
		$v = $v ? 1 : 0;
	}else if (is_string($v)){
		$v = str_replace("'", "''", $v);
		// $value = addcslashes($value, "\000\n\r\\\032");	
		$v = addcslashes($v, "\000\\\032");	// TODO validate this is really needed

		if ($like = $args['like']){
			if ($like === 'start')		$v = "{$v}%";
			else if ($like === 'end') 	$v = "%{$v}";
			else 						$v = "%{$v}%";
		}

		$v = "'{$v}'";
	}else if (is_a($v, 'DateTime')){
		$v = "'" . $v->format('Y-m-d H:i:s') . "'";
	}

	return (string)$v;
}