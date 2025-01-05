<?php 
function is_post ($key=null){
	if ($key){
		return array_key_exists($key, $_POST);
	}else{
		return count($_POST) > 0;
	}
}

function in_query ($key){
	return isset($_GET[$key]);
}

function get_request ($key=null, $fallback=null, $data=null){
	$data = is_null($data) ? $_REQUEST : $data;

	if (is_array($key)){
		foreach ($key as $i=>$v){
			if (!isset($data[$i])) continue;
			$key[$i] = $data[$i];
		}
		$data = $key;
		$key  = null;
	}else if ($key && isset($data[$key])){
		$data = to_set($key, $data[$key]);
	}else if ($key){
		$data = array();
	}

	$values = array();
	foreach ($data as $i=>$value){
		$value 		= __get_request_value($value);
		$values[$i] = $value;
	}

	return $key ? (isset($values[$key]) ? $values[$key] : $fallback) : $values;
}

function __get_request_value ($value){
	if (is_array($value)){
		$value = arr_is_obj($value) ? $value : array_filter($value, '__get_request_filter'); // @info seem to be annoying if sending an empty string
		$value = array_map('__get_request_value', $value);
	}else{
		$value = rawurldecode($value);
		$value = to_value($value, ['slash'=>true]);
	}
	return $value;
}

function __get_request_filter ($v){
	return !is_string($v) || trim($v) !== '';
}

function _request ($key=null, $fallback=NULL){
	$value = get_request($key);
	$value = _filters('_request', $value, $key, $fallback);
	return is_null($value) ? $fallback : $value;
}
function _post ($key=null, $fallback=NULL){
	return get_request($key, $fallback, $_POST);
}
// function _file ($key=null, $fallback=NULL){
// 	return get_request($key, $fallback, $_FILES);
// }
function _query ($key=null, $fallback=NULL){
	return get_request($key, $fallback, $_GET);
}

// Json ------------------------------------------------------------------------
function to_json_api ($success=true, $message="", $data=array(), $echo=false){
	$status = null;
	
	if (is_string($success)){
		$data    	= $message;
		$message 	= $success;
		$success 	= true;
	}else if (is_array($success)){
		$echo 		= $echo === true ? $echo : $message;
		$data 		= $success;
		$message 	= isset($success['message']) ? $success['message'] : null;
		$status 	= isset($success['status']) ? $success['status'] : null;
		$success 	= isset($success['success']) ? $success['success'] : null;
	}else if (is_numeric($success)){
		$status  = $success;
		$success = null;
	}

	if (is_array($message)){
		$data    = $message;
		$message = null;
	}
	if (!is_array($data)){
		$data    = array();
	}

	if (!$success && $status){
		$success = $status >= 400 ? false : true;		
	}else if (!$status){
		$success= $success === null ? true : $success;
		$status = $success ? 200 : 403;
	}

	if (!$message){
		$message = $success ? 'Success' : 'Error';
	}

	// make sure there's defaults
	if (!isset($data['status'])){
		$data['status'] = $status;
	}
	if (!isset($data['success'])){
		$data['success'] = $success;
	}
	if (!isset($data['message'])){
		$data['message'] = $message;
	}

	// put the "success" and "message" before
	$json = array_merge(array(
		'success' => $data['success'],
		'status'  => $data['status'],
		'message' => $data['message']
	), $data);

	if ($echo === true){
		if ($info = to_site_logs('return=ajax')){
			header('Content-Time: '.$info['time']);
			header('Content-Logs: '.$info['logs']);
		}
		
		_global(['output_logs'=>false]);

		header('Content-Type: application/json');
        isset($json['status']) && http_response_code($json['status']);
		echo json_encode($json, JSON_PRETTY_PRINT);
		
		die();
	}

	return $json;
}

function send_json ($success=true, $message="", $data=array()){
	return to_json_api($success, $message, $data, true);
}
