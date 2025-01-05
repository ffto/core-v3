<?php
/**
 * Files
 * 
 * @package Utils\Files
 * @author 	Maxime Lefrancois
 * @version 6.0
 **/

function content_start (){
	ob_start();
}

function content_end ($tabs = 0){
	$content = ob_get_clean();

	if (function_exists('mb_convert_encoding')){
		$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
	}

	$content = trim($content);
	$content = string_tab($content, $tabs);
	
    return $content;
}

function file_expired ($path, $expire='1day'){
    $path = to_filepath($path);

	if ($path && ($time = filemtime($path))){
        $time = new DateTime('@' . $time);
        $time = $time->modify($expire);
        $now  = new DateTime('now');
        return $now > $time;
	}

	return true;
}

function file_move ($from, $to, $args=''){
	if ($args === true){
		$args = array('overwrite'=>true);
	}

	$args = to_args($args, array(
		'overwrite' => false,
	));

	$from = parse_path($from);
	$to   = parse_path($to);

	if (file_exists($to) && !$args['overwrite']){
		$info  = pathinfo($to);
		$dir   = $info['dirname'];
		$name  = $info['filename'];
		$ext   = isset($info['extension']) ? ".{$info['extension']}" : '';
		$index = 2;

		while (file_exists($to)){
			$to = "{$dir}/{$name} {$index}{$ext}";
			$index++;
		}

		set_directory($to);
	}

	rename($from, $to);
	chmod($to, 0755);

	return $to;
}

// 
function file_output ($path, $args=''){
	if (string_is_url($path)){
		$path = url_to_path($path);
	}

	if (!($path = to_filepath($path))) return;

	$args = _args($args, [
		'mime'     => mime_content_type($path),
		'download' => false,
	], 'download');

	if ($args['download']){
		$filename = pathinfo($path, PATHINFO_BASENAME);
		
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize($path));
		
		readfile($path);
		exit;
	}else{
		header('Content-Type:' . $args['mime']);
		echo file_get_contents($path);
	}

	die();
}

function parse_files ($dir, $callback=null, $all=true){
	$files = glob_deep("{$dir}/*.php");
	$items = [];

	foreach ($files as $i => $path){
		$file = to_file_meta($path);
		if (!$all && empty($file)) continue;

		$file['filename'] = pathinfo($path, PATHINFO_FILENAME);
		$file['slug']     = isset($file['slug']) ? $file['slug'] : $file['filename'];
		$file['path']     = $path;
		$file['name']     = isset($file['name']) && $file['name'] ? $file['name'] : to_slug($file['slug'], 'space-words');
		$items[]          = $file;
	}

	usort($items, function ($a, $b){
		return strcasecmp($a['name'], $b['name']);
	});

	foreach ($items as $file){
		_call($callback, $file);
	}
	
	return $items;
}

// TODO https://www.etutorialspoint.com/index.php/12-create-and-download-word-document-in-php (maybe convert content to .doc file)

function the_output ($data, $args='', $callback=null){
	$args = to_args($args, array(
		'dir'      => '@data',
		'type'     => 'json',      // [json, script, link, src, csv, csv_path]
		'filename' => 'output',    // 
		'name'     => 'output',    // var name
		'headers'  => false,       // Forced CSV headers
		'format'   => $callback,   // for formating the CSV rows
		'cache'    => true,
		'return'   => null,
	), 'type');
	
	// TODO use "callback" on all formats

	if ($args['type'] === 'json'){
		echo to_string($data);
	}else if ($args['type'] === 'script'){
		$json = to_string($data);
		$json = $args['name'] ? "var {$args['name']} = {$json};" : $json;
		echo '<script type="text/javascript">' . $json . '</script>';
	}else if ($args['type'] === 'src' || $args['type'] === 'link'){
		$filepath = parse_path("{$args['dir']}{$args['filename']}.js");
		$url 	  = path_to_url($filepath);
		$json     = to_string($data);

		if (!file_exists($filepath) || !$args['cache']){
			$json = ($args['name'] ? "var {$args['name']} = {$json};" : $json);
			set_file($filepath, $json);
		}
		
		if ($args['type'] === 'link'){
			echo '<script type="text/javascript" src="'.$url.'"></script>';
		}else{
			return $url;
		}
	}else if ($args['type'] === 'csv' || $args['type'] === 'csv_path'){
		$filepath = str_replace('.csv', '', "{$args['dir']}{$args['filename']}"); // @fix make sure there's note the extension twice
		$filepath = parse_path("{$filepath}.csv");
		$url 	  = path_to_url($filepath);
		
		
		if (!is_file($filepath) || !$args['cache']){
			set_file($filepath);
			$header = $args['headers'] ? to_array($args['headers']) : array_keys($data[0]);
			$file 	= fopen($filepath, 'w');

			fputcsv($file, $header);
			foreach ($data as $row){
				$row = apply($args['format'], [$row]);
				fputcsv($file, $row);
			}
			fclose($file);
		}
		
		if ($args['type'] === 'csv'){
			return $url;
		}else{
			return $filepath;
		}
	}

	return $data;
}

// Edit ------------------------------------------------------------------------
function edit_htaccess ($name, $content, $args=null){
	$args = _args($args, [
		'dir'     => '',
		'prepend' => false,
	], 'dir');

	$start   = "# BEGIN {$name}";
	$end     = "# END {$name}";
	
	$path 	 = to_filepath("{$args['dir']}.htaccess");
	$htaccess= get_file($path);
	
	// remove the old one
	$index_start = strpos($htaccess, $start);
	$index_end 	 = $index_start !== false ? strpos($htaccess, $end, $index_start) + strlen($end) : false;
	
	if ($index_start !== false){
		$htaccess = substr($htaccess, 0, $index_start) . substr($htaccess, $index_end);
	}

	// add the new one
	$content = preg_replace('/^(\s+|\t)/m', '', $content);
	$content = trim($content);
	$content = $start . NL . $content . NL . $end;

	$htaccess = trim($htaccess);

	if ($args['prepend']){
		$htaccess= $content . NL . NL . $htaccess;
	}else{
		$htaccess= $htaccess . $content;
	}
	
	file_put_contents($path, $htaccess);
}

// Cast ------------------------------------------------------------------------
define('JSON_CONTENT', 'file_json_content');
define('FILE_STATUS', 'file_status');

function set_json (){
	$args = func_get_args();
	$json = call_user_func_array('to_json_api', $args);

	set_status($json['status']);
	set_global(JSON_CONTENT, $json);
}

function set_status ($code=200){
	set_global(FILE_STATUS, $code);
}

function get_status (){
	return get_global(FILE_STATUS, 200);
}

function to_include_content ($path, $data=array(), $skip=false){
	content_start();

	if ($skip){
		extract($data, EXTR_SKIP);
	}else{
		extract($data);
	}

	$response = include($path);
	$html     = content_end();
	$html     = $html ? $html : '';

	// return a JSON message
	if (is_string($response)){
		$html = array('message'=>$response);
	// return a HTTP Status code
	}else if (is_bool($response)){
		$html     = array('success'=>$response);
	}else if (is_numeric($response) && $response >= 100){
		$html     = array('status'=>$response);
	// return data
	}else if (is_array($response)){
		$html = $response;
	}

	return $html;
}

function to_content ($content, $args='', $data=null, $wrap_data=false){
	if (is_bool($args)){
		$args = array('echo'=>$args);
	}

	if ($args === ':simple'){
		$args = array(
			'file' => false,
			'url'  => false,
		);
	}

	$args = to_args($args, array(
		'file'               => null,    // try decoding $content as a filepath (null or true)
		'url'                => null,    // try decoding $content as a url (null or true)
		'callable'           => false,
		'alias'              => '',
		'data'               => $data,
		'wrap_callback_data' => $wrap_data,	 // wrap all data under 1 variable for functions
		'skip'               => true,
		'echo'               => false,
	));

	$check_file = $args['file'] === null || $args['file'];
	$check_url  = $args['url'] === null || $args['url'];
	$check_text = !is_truthy($args['file']) && !is_truthy($args['url']);
	
	if ($content){
		set_global(JSON_CONTENT, null);
		set_status(200);
		
		$data 	  = empty($args['data']) ? array() : $args['data'];
		$response = null;
		$html 	  = null;

		if (is_callback($content, !$args['callable'])){
			if ($args['wrap_callback_data']){
				$data = [$data];
			}

			content_start();
			$response = call_user_func_array($content, $data);
			$html     = content_end();
		}else if ($check_file && ($path = to_filepath($content))){
			set_global(VIEW_DATA, $data); // Uses the "html" function _var() for this
			$html     = to_include_content($path, $data, $args['skip']);
		}else if ($check_url && string_is_url($content)){
            $html = to_http($content, ['cache'=>true]);
		}else if ($check_text && is_string($content)){
			$html = string_replace($content, $data);
		}else if (!is_string($content)){
			$html = $content;
		}

		if ($json = get_global(JSON_CONTENT)){
			$content = $json;
		}else if (is_array($response)){
			$content = $response;
		}else{
			$content = $html ? $html : ($response ? $response : '');
		}
	}else{
		$content = '';
	}

    if (is_string($content)){
        $content = $args['alias'] ? replace_alias($content, $args['alias']) : $content;

        if ($args['echo']){
            echo $content;
        }
    }
	
	return $content;
}

function __content ($content, $data=null, $wrap_data=false){
	return to_content($content, ':simple', $data, $wrap_data);
}

function to_file_meta ($path, $args=null){
	if (!($path = to_filepath($path))) return;

	$content = file_get_contents($path);
	if (!preg_match('/\/\*\*(?:.|\n)+?\*\*\//', $content, $match)) return [];

	// TODO 
	$args = to_args($args, array(
		'name_slug'  => '_',
		'value_slug' => false,
		// 'slug' => false,
		// 'lowercase' => 'name',
	));

	preg_match_all('/^(\s*\*\s*)(.+?):(.*)$/m', $match[0], $match);

	$count = isset($match[0]) ? count($match[0]) : 0;
	$meta  = array();

	for ($i=0; $i<$count; $i++){
		$name  = trim($match[2][$i]);
		$value = trim($match[3][$i]);
		
		if ($args['name_slug']){
			$name = to_slug($name, $args['name_slug']);
		}
		if ($args['value_slug']){
			$name = to_slug($name, $args['value_slug']);
		}

		// if ($args['lowercase'] === true || $args['lowercase'] === 'name'){
		// 	$name = strtolower($name);
		// }
		// if ($args['lowercase'] === true || $args['lowercase'] === 'value'){
		// 	$value = strtolower($value);
		// }

		$value 		 = to_value($value);
		$meta[$name] = $value;
	}

	return $meta;
}

function to_file_type (){
	return _deprecated('Use "to_fileinfo" instead of "to_mime_type"');
}

/** 
 * Return the file type info according to the extension. Similar to the mime-type, but simpler
 */
function to_mime_type ($path, $args=null){
	if (!is_string($path)) return false;

	$args = to_args($args, array(
		'fallback' => '',
		'return'   => false
	), 'return');

	$types = array(
		'txt'  => 'text/plain',
		'htm'  => 'text/html',
		'html' => 'text/html',
		'php'  => 'text/php',
		'css'  => 'text/style',
		'js'   => 'text/javascript',
		'json' => 'text/json',
		'xml'  => 'text/xml',
		'swf'  => 'application/flash',
		// images
		'png'  => 'image/png',
		'jpe'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg'  => 'image/jpeg',
		'gif'  => 'image/gif',
		'bmp'  => 'image/bmp',
		'ico'  => 'image/icon',
		'tiff' => 'image/tiff',
		'tif'  => 'image/tiff',
		'svg'  => 'image/svg',
		'svgz' => 'image/svg',
		// archives
		'zip' => 'archive/zip',
		'rar' => 'archive/rar',
		'exe' => 'archive/exe',
		'msi' => 'archive/msi',
		'cab' => 'archive/cab',
		// audio
		'mp3'  => 'audio/mp3',
		// video
		'mp4'  => 'video/mp4',
		'webm' => 'video/webm',
		'ogv'  => 'video/ogg',
		'qt'   => 'video/quicktime',
		'mov'  => 'video/quicktime',
		'flv'  => 'video/flv',
		// fonts
		'ttf'   => 'font/ttf',
		'otf'   => 'font/otf',
		'woff'  => 'font/woff',
		'woff2' => 'font/woff2',
		'eot'   => 'font/eot',
		'sfnt'  => 'font/sfnt',
		// adobe
		'pdf' => 'application/pdf',
		'psd' => 'image/vnd.adobe.photoshop',
		'ai'  => 'application/postscript',
		'eps' => 'application/postscript',
		'ps'  => 'application/postscript',
		// ms office
		'doc' => 'application/msword',
		'rtf' => 'application/rtf',
		'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',
		// open office
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		// embed provider source
		'youtube' => 'embed/video',
		'vimeo'   => 'embed/video',
	);

	$path = preg_replace('/(\?|\#).+/', '', $path);
	$ext  = preg_replace('/^.+\./', '', $path);
	$ext  = strtolower($ext);
	$type = isset($types[$ext]) ? $types[$ext] : $args['fallback'];

	if ($args['return']){
		$pair    = explode('/', $type);
		$type    = $pair[0];
		$subtype = isset($pair[1]) ? $pair[1] : '';

		if ($args['return'] === 'type'){
			return $type;
		}else if ($args['return'] === 'subtype'){
			return $subtype;
		}else{
			return array(
				'type'    => $type,
				'subtype' => $subtype,
			);
		}
	}

	return $type;
}

function to_file_ext ($path, $args=null){
	$args = _args($args, [
		'pathinfo' => true,
	], 'pathinfo');

	$path = parse_path($path);
	$ext  = $args['pathinfo'] ? pathinfo($path, PATHINFO_EXTENSION) : '';

	if (!$ext && is_file($path)){
		$mime = mime_content_type($path);

		// @source https://stackoverflow.com/questions/16511021/convert-mime-type-to-file-extension-php
		$mime_map = [
			'video/3gpp2'                                                               => '3g2',
			'video/3gp'                                                                 => '3gp',
			'video/3gpp'                                                                => '3gp',
			'application/x-compressed'                                                  => '7zip',
			'audio/x-acc'                                                               => 'aac',
			'audio/ac3'                                                                 => 'ac3',
			'application/postscript'                                                    => 'ai',
			'audio/x-aiff'                                                              => 'aif',
			'audio/aiff'                                                                => 'aif',
			'audio/x-au'                                                                => 'au',
			'video/x-msvideo'                                                           => 'avi',
			'video/msvideo'                                                             => 'avi',
			'video/avi'                                                                 => 'avi',
			'application/x-troff-msvideo'                                               => 'avi',
			'application/macbinary'                                                     => 'bin',
			'application/mac-binary'                                                    => 'bin',
			'application/x-binary'                                                      => 'bin',
			'application/x-macbinary'                                                   => 'bin',
			'image/bmp'                                                                 => 'bmp',
			'image/x-bmp'                                                               => 'bmp',
			'image/x-bitmap'                                                            => 'bmp',
			'image/x-xbitmap'                                                           => 'bmp',
			'image/x-win-bitmap'                                                        => 'bmp',
			'image/x-windows-bmp'                                                       => 'bmp',
			'image/ms-bmp'                                                              => 'bmp',
			'image/x-ms-bmp'                                                            => 'bmp',
			'application/bmp'                                                           => 'bmp',
			'application/x-bmp'                                                         => 'bmp',
			'application/x-win-bitmap'                                                  => 'bmp',
			'application/cdr'                                                           => 'cdr',
			'application/coreldraw'                                                     => 'cdr',
			'application/x-cdr'                                                         => 'cdr',
			'application/x-coreldraw'                                                   => 'cdr',
			'image/cdr'                                                                 => 'cdr',
			'image/x-cdr'                                                               => 'cdr',
			'zz-application/zz-winassoc-cdr'                                            => 'cdr',
			'application/mac-compactpro'                                                => 'cpt',
			'application/pkix-crl'                                                      => 'crl',
			'application/pkcs-crl'                                                      => 'crl',
			'application/x-x509-ca-cert'                                                => 'crt',
			'application/pkix-cert'                                                     => 'crt',
			'text/css'                                                                  => 'css',
			'text/x-comma-separated-values'                                             => 'csv',
			'text/comma-separated-values'                                               => 'csv',
			'application/vnd.msexcel'                                                   => 'csv',
			'application/x-director'                                                    => 'dcr',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
			'application/x-dvi'                                                         => 'dvi',
			'message/rfc822'                                                            => 'eml',
			'application/x-msdownload'                                                  => 'exe',
			'video/x-f4v'                                                               => 'f4v',
			'audio/x-flac'                                                              => 'flac',
			'video/x-flv'                                                               => 'flv',
			'image/gif'                                                                 => 'gif',
			'application/gpg-keys'                                                      => 'gpg',
			'application/x-gtar'                                                        => 'gtar',
			'application/x-gzip'                                                        => 'gzip',
			'application/mac-binhex40'                                                  => 'hqx',
			'application/mac-binhex'                                                    => 'hqx',
			'application/x-binhex40'                                                    => 'hqx',
			'application/x-mac-binhex40'                                                => 'hqx',
			'text/html'                                                                 => 'html',
			'image/x-icon'                                                              => 'ico',
			'image/x-ico'                                                               => 'ico',
			'image/vnd.microsoft.icon'                                                  => 'ico',
			'text/calendar'                                                             => 'ics',
			'application/java-archive'                                                  => 'jar',
			'application/x-java-application'                                            => 'jar',
			'application/x-jar'                                                         => 'jar',
			'image/jp2'                                                                 => 'jp2',
			'video/mj2'                                                                 => 'jp2',
			'image/jpx'                                                                 => 'jp2',
			'image/jpm'                                                                 => 'jp2',
			'image/jpeg'                                                                => 'jpeg',
			'image/pjpeg'                                                               => 'jpeg',
			'application/x-javascript'                                                  => 'js',
			'application/json'                                                          => 'json',
			'text/json'                                                                 => 'json',
			'application/vnd.google-earth.kml+xml'                                      => 'kml',
			'application/vnd.google-earth.kmz'                                          => 'kmz',
			'text/x-log'                                                                => 'log',
			'audio/x-m4a'                                                               => 'm4a',
			'audio/mp4'                                                                 => 'm4a',
			'application/vnd.mpegurl'                                                   => 'm4u',
			'audio/midi'                                                                => 'mid',
			'application/vnd.mif'                                                       => 'mif',
			'video/quicktime'                                                           => 'mov',
			'video/x-sgi-movie'                                                         => 'movie',
			'audio/mpeg'                                                                => 'mp3',
			'audio/mpg'                                                                 => 'mp3',
			'audio/mpeg3'                                                               => 'mp3',
			'audio/mp3'                                                                 => 'mp3',
			'video/mp4'                                                                 => 'mp4',
			'video/mpeg'                                                                => 'mpeg',
			'application/oda'                                                           => 'oda',
			'audio/ogg'                                                                 => 'ogg',
			'video/ogg'                                                                 => 'ogg',
			'application/ogg'                                                           => 'ogg',
			'font/otf'                                                                  => 'otf',
			'application/x-pkcs10'                                                      => 'p10',
			'application/pkcs10'                                                        => 'p10',
			'application/x-pkcs12'                                                      => 'p12',
			'application/x-pkcs7-signature'                                             => 'p7a',
			'application/pkcs7-mime'                                                    => 'p7c',
			'application/x-pkcs7-mime'                                                  => 'p7c',
			'application/x-pkcs7-certreqresp'                                           => 'p7r',
			'application/pkcs7-signature'                                               => 'p7s',
			'application/pdf'                                                           => 'pdf',
			'application/octet-stream'                                                  => 'pdf',
			'application/x-x509-user-cert'                                              => 'pem',
			'application/x-pem-file'                                                    => 'pem',
			'application/pgp'                                                           => 'pgp',
			'application/x-httpd-php'                                                   => 'php',
			'application/php'                                                           => 'php',
			'application/x-php'                                                         => 'php',
			'text/php'                                                                  => 'php',
			'text/x-php'                                                                => 'php',
			'application/x-httpd-php-source'                                            => 'php',
			'image/png'                                                                 => 'png',
			'image/x-png'                                                               => 'png',
			'application/powerpoint'                                                    => 'ppt',
			'application/vnd.ms-powerpoint'                                             => 'ppt',
			'application/vnd.ms-office'                                                 => 'ppt',
			'application/msword'                                                        => 'doc',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
			'application/x-photoshop'                                                   => 'psd',
			'image/vnd.adobe.photoshop'                                                 => 'psd',
			'audio/x-realaudio'                                                         => 'ra',
			'audio/x-pn-realaudio'                                                      => 'ram',
			'application/x-rar'                                                         => 'rar',
			'application/rar'                                                           => 'rar',
			'application/x-rar-compressed'                                              => 'rar',
			'audio/x-pn-realaudio-plugin'                                               => 'rpm',
			'application/x-pkcs7'                                                       => 'rsa',
			'text/rtf'                                                                  => 'rtf',
			'text/richtext'                                                             => 'rtx',
			'video/vnd.rn-realvideo'                                                    => 'rv',
			'application/x-stuffit'                                                     => 'sit',
			'application/smil'                                                          => 'smil',
			'text/srt'                                                                  => 'srt',
			'image/svg+xml'                                                             => 'svg',
			'application/x-shockwave-flash'                                             => 'swf',
			'application/x-tar'                                                         => 'tar',
			'application/x-gzip-compressed'                                             => 'tgz',
			'image/tiff'                                                                => 'tiff',
			'font/ttf'                                                                  => 'ttf',
			'text/plain'                                                                => 'txt',
			'text/x-vcard'                                                              => 'vcf',
			'application/videolan'                                                      => 'vlc',
			'text/vtt'                                                                  => 'vtt',
			'audio/x-wav'                                                               => 'wav',
			'audio/wave'                                                                => 'wav',
			'audio/wav'                                                                 => 'wav',
			'application/wbxml'                                                         => 'wbxml',
			'video/webm'                                                                => 'webm',
			'image/webp'                                                                => 'webp',
			'audio/x-ms-wma'                                                            => 'wma',
			'application/wmlc'                                                          => 'wmlc',
			'video/x-ms-wmv'                                                            => 'wmv',
			'video/x-ms-asf'                                                            => 'wmv',
			'font/woff'                                                                 => 'woff',
			'font/woff2'                                                                => 'woff2',
			'application/xhtml+xml'                                                     => 'xhtml',
			'application/excel'                                                         => 'xl',
			'application/msexcel'                                                       => 'xls',
			'application/x-msexcel'                                                     => 'xls',
			'application/x-ms-excel'                                                    => 'xls',
			'application/x-excel'                                                       => 'xls',
			'application/x-dos_ms_excel'                                                => 'xls',
			'application/xls'                                                           => 'xls',
			'application/x-xls'                                                         => 'xls',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
			'application/vnd.ms-excel'                                                  => 'xlsx',
			'application/xml'                                                           => 'xml',
			'text/xml'                                                                  => 'xml',
			'text/xsl'                                                                  => 'xsl',
			'application/xspf+xml'                                                      => 'xspf',
			'application/x-compress'                                                    => 'z',
			'application/x-zip'                                                         => 'zip',
			'application/zip'                                                           => 'zip',
			'application/x-zip-compressed'                                              => 'zip',
			'application/s-compressed'                                                  => 'zip',
			'multipart/x-zip'                                                           => 'zip',
			'text/x-scriptzsh'                                                          => 'zsh',
		];

		$ext = isset($mime_map[$mime]) ? $mime_map[$mime] : '';
	}
	
	return $ext;
}
