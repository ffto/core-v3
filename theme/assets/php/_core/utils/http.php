<?php 
/**
 * Sometimes the "true/false" values needs to be returned as strings, instead of the 1/0 that http_build_query does
 *
 * @param mixed $data 
 * @param bool $normalize 
 * @return void
 */
function to_url_query ($data, $normalize=false){
    if (!is_array($data)){
        return $data;
    }

    if ($normalize){
        $data = array_map(function ($v){
            if ($v === true)        return 'true';
            else if ($v === false)  return 'false';
            else                    return $v;
        }, $data);
    }
    
    return http_build_query($data);
}

function http ($url, $args=''){
	if (is_callback($args)){
		$args = array('return'=>$args);
	}

	$args = to_args($args, array(
		'method'           => null,      // get, post, head, put, delete, ...
		'headers'          => array(),
		'body'             => array(),   // post data
        'normalize_body'   => false,
		'query'            => array(),
		'content_length'   => false,     // add the Content-Length to the header
		'base'             => null,      // add base to returned HTML content
		'user'             => null,
		'ssl'              => false,
		'timeout'          => false,
		'follow'           => false,
		'ping'             => false,
		'client_ip'        => false,
		'resolve_redirect' => true,      // try getting the content of the redirect
        'user_agent'       => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
		'return'           => false,     // code/status, content/body, json, headers, OBJECT(true)
		'cache'            => false,     // cache the results [true = session, string = file with expire datetime]
        'delay'            => _config('http_delay'),
	), 'return');

    // add query values to the URL
    if (!empty($args['query'])){
		$query = http_build_query($args['query']);
		$url  .= (strpos($url, '?') === false ? '?' : '&') . $query;
	}

    if (strpos($url, '//') === 0){
        $url = "https:{$url}";
    }

    if (!string_is_url($url)){
        return null;
    }

	$http_url  = to_http_url($url, true);
    $body      = is_array($args['body']) ? array_filter($args['body'], 'is_defined') : $args['body'];
    $body_query= to_url_query($body, $args['normalize_body']);
	$cache_key = 'url_' . md5($url . $body_query);
	$cache 	   = null;

    // auto <base>
    if ($args['base'] === null && (
        !$http_url['ext'] 
        || in_array($http_url['ext'], ['html','htm','php'])
    )){
        $args['base'] = true;
    }

	if ($args['cache'] === true){
		$cache = get_global($cache_key);
	}else if (is_string($args['cache'])){
		$cache = get_cache($cache_key);
	}

    if ($cache && !$args['ping']){
		$body    = null;
		$headers = array();
		$info    = array();
		extract($cache);
    }else{
        // add a little delay before doing the fetch
        if ($args['delay']){
            $ms  = to_duration($args['delay']);
            $sec = round($ms / 1000);
            sleep($sec);
        }

        $method = $args['method'] ? $args['method'] : (!empty($body) ? 'POST' : 'GET');
        $method = strtoupper($method);
		$method = in_array($method, array('GET','HEAD','POST','PUT','DELETE','CONNECT','OPTIONS','TRACE','PATCH')) ? $method : 'GET';
    
        $curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $http_url['url']);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);	

        if ($args['user_agent']){
            curl_setopt($curl, CURLOPT_USERAGENT, $args['user_agent']);
        }
        if ($args['timeout']){
            curl_setopt($curl, CURLOPT_TIMEOUT, $args['timeout']);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $args['timeout']);
        }
        if ($args['follow']){
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $args['follow']);
        }
        if ($args['ssl'] === false){
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
        if ($args['user']){
			$user = to_string($args['user'], ['pair'=>':', 'separator'=>'&']);
    		curl_setopt($curl, CURLOPT_USERPWD, $user);
		}
 
        // headers -------------------------------------------------------------
        $headers = to_string($args['headers'], 'pair=: ');
        if ($args['content_length'] !== false){
            if (is_numeric($args['content_length'])){
                $headers[] = 'Content-Length: ' . $args['content_length'];
            }else{
                $headers[] = 'Content-Length: ' . strlen($body_query);
            }
        }

        // forward IP address, using a custom HTTP HEADER value
        if ($args['client_ip']){
            $ip        = $args['client_ip'] === true ? to_ip_address() : $args['client_ip'];
            $headers[] = 'Clieny-IP: '.$ip;
        }

        if (count($headers)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }else{
            curl_setopt($curl, CURLOPT_HEADER, 0);
        }

        // body -------------------------------------------------------------
		if ($method !== 'GET'){
			if ($method === 'POST'){
				curl_setopt($curl, CURLOPT_POST, true);
			}else{
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			}

			// $body = is_array($args['body']) ? http_build_query($args['body']) : $args['body'];

			if ($body_query){
				curl_setopt($curl, CURLOPT_POSTFIELDS, $body_query);
			}
		}

        // fetch ---------------------------------------------------------------
        if ($args['ping']){
            curl_setopt($curl, CURLOPT_NOBODY, true);
            $headers = array();
            $body    = curl_exec($curl);
            $info    = curl_getinfo($curl);
        }else{
            // @source https://stackoverflow.com/a/41135574
            $headers = array();
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers){
                $len    = strlen($header);
                $header = explode(':', $header, 2);

                if (count($header) < 2) // ignore invalid headers
                return $len;

                $key 			= strtolower(trim($header[0]));
                $value 			= trim($header[1]);
                $headers[$key] 	= to_value($value);	

                return $len;
            });
    
            $body = curl_exec($curl);
            $info = curl_getinfo($curl);

            // resolve redirection
            if ($args['resolve_redirect'] && isset($info['redirect_url']) && $info['redirect_url']){
                return http($info['redirect_url'], $args);
            }else if (is_string($body) && strpos($body, 'application/json')){
                $json     = to_match($body, '/<script.+type\=\"application\/json\".+?\>(.+)<\/script>/', ':group');
                $json     = json_decode($json, true);
                $redirect = isset($json['redirectUrl']) ? $json['redirectUrl'] : null;

                if ($redirect){
                    return http($redirect, $args);
                }
            }

            if (curl_errno($curl)){
                $error = curl_error($curl);
                _err($error);
            }	
            
            curl_close($curl);

            // add the <base /> to the resulted html
            if ($body && $args['base'] && strpos($body, '<html') !== false){
                $base = '<base href="'.$http_url['domain'].'" />';
                $body = preg_replace('/(<head[^>]*>)/', "$1\n".$base, $body);
                // $body = '<base href="'.$http_url['domain'].'" />' . $body;
            }

            if ($args['cache'] !== false){
                $cache = array(
                    'url'     => $url,
                    'body'    => $body,
                    'headers' => $headers,
                    'info'    => $info,
                );

                if ($args['cache'] === true){
                    set_global($cache_key, $cache);	
                }else{
                    set_cache($cache_key, $cache, array(
                        'expire' => $args['cache']
                    ));
                }            
            }
        }
    }

    // try to decode the type of 
    if (!$args['return'] && isset($info['content_type']) && strpos($info['content_type'], 'application/json') !== false){
        $args['return'] = 'json';
    }

    $type = _get($headers, 'content-type'); // sometimes, the content-type has extra stuff like: "text/html; charset=UTF-8"
    $type = explode(';', $type);
    $type = reset($type);
    
    if (is_callback($args['return'])){
		$body = $args['return']($body, $headers, $info);
		return $body;
	}else if (return_object($args['return'])){
        if ($args['ping']){
            return array(
                'url'		=> $http_url['url'],
                'domain'	=> $http_url['domain'],
                'redirect'	=> $info['redirect_url'],
                'code'		=> $info['http_code'],
            );
        }else{ 
            $base = to_match($body, '/\<base.+href="(.+?)"/', ':group');
            $base = $base ? $base : $http_url['domain'];
            return array(
                'url'     => $http_url['url'],
                'domain'  => $http_url['domain'],
                'status'  => $info['http_code'],
                'base'    => $base,
                'type'    => $type,
                'info'    => $info,
                'headers' => $headers,
                'body'    => $body,
                // 'json'    => to_value($body, 'json=1'),
                'json'    => json_decode($body, true),
            );
        }
	}else if ($args['return'] === 'headers'){
		return $headers;
	}else if ($args['return'] === 'code' || $args['return'] === 'status'){
		return $info['http_code'];
	}else if ($args['return'] === 'redirect'){
		return $info['redirect_url'];
	}else if ($args['return'] === 'url'){
		return $info['redirect_url'] ? $info['redirect_url'] : $http_url['url'];
	}else if ($args['return'] === 'success'){
		return $info['http_code'] >= 200 && $info['http_code'] < 300;
	}else if ($args['return'] === 'json'){
		return to_value($body, 'json=1');
	}else{
		return $body;
	}
}

function http_ping ($url, $args=''){
    $args           = to_args($args, array('return'=>null), 'return');
    $args['return'] = $args['return'] === null ? 'success' : $args['return'];
    $args['ping']   = true;
    return http($url, $args);
}

function http_data ($url, $args=array()){
    $args = to_args($args, array(
        'cache'  => true,
        'return' => null,
	), 'return');
    
    $refresh = is_refresh('http_data');
    $cache   = 'http_data-' . md5($url);
    $data    = $args['cache'] ? get_cache($cache) : null;

    if ($data === null || !$cache || $refresh){
        $http = http($url, [
            'base'   => false,
            'return' => true,
        ]);

        // parse the data
        if ($http['body']){
            $doc  = get_dom($http['body'], 'type=html');
            $data = array(
                'url'    => $http['url'],
                'title'  => dom_get_node($doc, 'title', ':text'),
                'meta'   => null,
                'embed'  => null,
                'images' => null,
            );
            
            // base URL
            $base = dom_get_node($doc, 'base', 'href');
            $base = $base ? $base : $http['domain'];
                
            // meta values -----------------------------------------------------
            $data['meta'] = dom_get_nodes($doc, 'meta', function ($node){
                $key   = $node->getAttribute('property');
                $key   = $key ? $key : $key = $node->getAttribute('name');
                $value = $node->getAttribute('content');
                
                return $key ? [
                    '$id'    => $key,
                    '$value' => strip_tags($value),
                ] : false;
            });            

            // embed -----------------------------------------------------------
            $data['embed'] = dom_get_node($doc, ['xpath'=>'//link[(@type="application/json+oembed" or @type="text/xml+oembed" or @type="application/xml+oembed")]'], function ($node){
                $href  = $node->getAttribute('href');
                $href  = _filters('http_data-embed_href', $href);
                $type  = $node->getAttribute('type');

                if ($type === 'application/json+oembed'){
                    $embed = http($href, 'json');
                }else{
                    $xml   = http($href, 'return=body&base=');
                    $xml   = get_dom($xml, 'type=xml&node=oembed&debug=1');
                    $nodes = isset($xml->childNodes) ? $xml->childNodes : [];
                    $embed = [];

                    foreach ($nodes as $i => $node){
                        $key         = to_slug($node->nodeName, 'underscore');
                        $value       = strtr($node->textContent, ['<![CDATA['=>'', ']]>'=>'']);
                        $value       = to_value($value);
                        $embed[$key] = $value;
                    }
                }
                
                return $embed;
            });

            // Try getting the HD image for Youtube videos
            if (_get($data, 'embed.provider_name') === 'YouTube'){
                $thumb_hd = str_replace('hqdefault.jpg', 'maxresdefault.jpg', $data['embed']['thumbnail_url']);
                // replace the thumbnail with the HD version
                if (http_ping($thumb_hd)){
                    $data['embed']['thumbnail_url'] = $thumb_hd;
                }
            }

            // images --------------------------------------------------------------------------------------------------
            $images = dom_get_nodes($doc, 'img', function ($node) use ($base){
                $src    = $node->getAttribute('src');
                $width  = $node->getAttribute('width');
                $height = $node->getAttribute('height');
                $src    = to_url($src, ['host'=>$base, 'protocol'=>true]);
                return _filters('http_data-image_src', $src, $width, $height);
            });
    
            $images         = _filters('http_data-images', $images);
            $data['images'] = array_unique($images);
            $data['images'] = array_values($images);
    
            // add by default the image specified by the og:image
            if (isset($data['metas']['og:image'])){
                array_unshift($data['images'], $data['metas']['og:image']);
            } 
        }else{
            // The HTTP returned nothing
            $data = false;
        }

        set_cache($cache, $data, [
            'expire' => $args['cache']
        ]);
    }
    
	return $args['return'] ? get_value($data, $args['return']) : $data;
}

function http_meta ($url, $args=array()){
    $args = to_args($args, array(
        'cache' => true, 
    ), 'cache');

    $refresh   = is_refresh('http_meta');
    $cache_key = 'http_meta-' . md5($url);
    $meta      = $args['cache'] ? get_cache($cache_key) : null;

    if ($meta === null || !$args['cache'] || $refresh){
        $meta = http_data($url, [
            'return' => 'meta',
            'cache'  => false,
        ]);

        $meta = $meta ? $meta : false;

        set_cache($cache_key, $meta, [
            'expire' => $args['cache'],
        ]);
    }
    
    return $meta;
}

function download_url ($url, $args=null, $save=null){
    if (!string_is_url($url)) return;

    $args = _args($args, [
        'dir'    => '@upload/',
        'name'   => true,
        'save'   => $save,
        'return' => null,
    ], 'name');

    $info = pathinfo($url);
    $name = isset($info['filename']) ? $info['filename'] : md5($url);
    $ext  = isset($info['extension']) ? $info['extension'] : '';
    $ext  = preg_replace('/[?#].+/', '', $ext);
    $dir  = isset($info['dirname']) ? substr(md5($info['dirname']), 0, 5) : '';
    
    $filename = is_string($args['name']) ? $args['name'] : '{{ dir }}/{{ name }}.{{ ext }}';
    $filename = string_replace($filename, array(
        'dir'  => $dir,
        'name' => $name,
        'ext'  => $ext,
    ));
    
    $filepath = parse_path($args['dir'] . $filename);

    if (!is_file($filepath)){
        $data = http($url, 'base=0');

        if (is_callback($args['save'])){
            $filepath = _apply($args['save'], $data, [
                'filepath' => $filepath,
                'ext'      => $ext,
            ]);
        }else{
            set_file($filepath, $data);
        }
    }

    if ($args['return'] === 'url'){
        $filepath = path_to_url($filepath);
    }

    return $filepath;
}

// Embeds ----------------------------------------------------------------------
define('CUSTOM_EMBEDS', 'ffto-custom-embeds');

function http_embed ($url, $args=array()){
    if (!is_string($url)) return;

    $args = to_args($args, array(
        'cache' => true, 
    ), 'cache');

    $url        = _filters('http_embed-url', $url);
    $refresh    = is_refresh('http_embed');
    $cache_key  = 'http_embed-' . md5($url);
    $embed      = $args['cache'] ? get_cache($cache_key) : null;

    if ($embed === null || !$args['cache'] || $refresh){
        $embed = null;

        // filter --------------------------------------------------------------
        $url = apply_filters('http_embed-url', $url);
        if (is_array($url)){
            $embed = $url;
        }

        // check the custom embeds ---------------------------------------------
        if ($embed === null){
            $all_custom = get_global(CUSTOM_EMBEDS, []);
            foreach ($all_custom as $custom){
                if (
                    ($custom['is_regexp'] && !preg_match($custom['match'], $url))
                    || (!$custom['is_regexp'] && strpos($url, $custom['match']) === false)
                ) continue;

                $embed = apply($custom['callback'], [$url]);
            }
        }

        // try the normal way of getting the embed -----------------------------
        if ($embed === null){
            $embed = http_data($url, [
                'return' => 'embed',
                'cache'  => false,
            ]);
        }

        $embed = $embed ? $embed : false;    
        set_cache($cache_key, $embed, [
            'expire' => $args['cache'],
        ]);
    }

    if ($embed && !isset($embed['url'])){
        $embed['url'] = $url;
    }
    
    $embed = apply_filters('http_embed-embed', $embed, $url);

    if (isset($embed['provider_name'])){
        $provider = strtolower($embed['provider_name']);
        $embed    = apply_filters("http_embed-{$provider}_embed", $embed, $url);
    }

    return $embed;
}

function add_http_custom_embed ($match, $callback){
    $custom = get_global(CUSTOM_EMBEDS, []);
    $custom[] = [
        'match'     => $match,
        'is_regexp' => is_regexp($match),
        'callback'  => $callback,
    ]; 
    set_global(CUSTOM_EMBEDS, $custom);
}

add_http_custom_embed('vimeo.com/event/', function ($url){
    $url  = str_replace('/embed', '', $url) . '/embed'; // ??? why this, I can't remember
    $data = http_data($url);

    return [
        "type"          => "video",
        "provider_name" => "Vimeo",
        "provider_url"  => "https://vimeo.com/",
        "title"			=> _get($data, 'title'),
        "width"			=> 720,
        "height"		=> 405,
        "html"			=> '<iframe src="'.$url.'" width="720" height="405" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>',
    ];
});

add_filter('http_embed-url', function ($url){
    // transform Youtube embed url to the normal Youtube url
    if (strpos($url, 'youtube.com/embed/') !== false){
        $id  = to_match($url, '/youtube.com\/embed\/([^\/]+)/', ':group');
        $url = "https://www.youtube.com/watch?v={$id}";
    }
    return $url;
});

// allow="autoplay"
add_filter('http_data-embed_href', function ($url){
    // Vimeo - HD quality of image, by adding the "&width=" query argument
    if (strpos($url, 'vimeo.com') !== false){
        $url .= "&width=1280";
    }
    return $url;
});

add_filter('http_embed-soundcloud_embed', function ($embed, $url){
    // We need "autoplay" for playing the embeds with the oEmbed API
    $embed['type'] = 'audio';
    $embed['html'] = str_replace('<iframe ', '<iframe allow="autoplay" ', $embed['html']);
    return $embed;
});

// Not working properly....
// add_http_custom_embed('on.soundcloud.com', function ($url){
//     $data = http($url, ['return'=>true]);
//     p($data);
// });


// Casting ---------------------------------------------------------------------
function to_http_url ($url, $return=false){
    $url = trim($url);
    
    // add the http prefix
    if (strpos($url, 'http') === false){
		$url = 'http://'.$url;  
	}

	$url    = parse_url($url);
	$scheme = isset($url['scheme']) ? $url['scheme'] : 'http';
	$host   = isset($url['host']) ? $url['host'] : '';
	$path   = isset($url['path']) ? $url['path'] : '/';
	$query  = isset($url['query']) ? "?{$url['query']}" : false;

    // clean/standerize the url
	$url = "{$scheme}://{$host}{$path}";
	$url = urldecode($url);
	$url = str_replace(' ', '%20', $url);
    
    // reparse
    $url    = string_clean_accents($url) . $query;
    $_url   = parse_url($url);
    $domain = "{$_url['scheme']}://{$_url['host']}";

    if (return_object($return)){
        return array(
			'url'	    => $url,
			'domain'    => $domain,
            'path'      => $_url['path'],
            'ext'       => pathinfo($_url['path'], PATHINFO_EXTENSION),
		);
	}else if ($return === 'domain'){
		return $domain;
	}else if ($return === 'path'){
		return $_url['path'];
	}else{
		return $url;
	}


    /*
    p($url, $_url);
    
    $url_parts	= parse_url($url);
	$query 		= isset($url_parts['query']) ? $url_parts['query'] : false;
	$url 		= "{$url_parts['scheme']}://{$url_parts['host']}{$url_parts['path']}";
	$url 		= urldecode($url);
	$url 		= str_replace(' ', '%20', $url);

	$url_parts	= parse_url($url);
	$domain 	= "{$url_parts['scheme']}://{$url_parts['host']}";
    $path       = $url_parts['path'];
	// $directory 	= dirname($url).'/';
	$url 		= string_clean_accents($url) . ($query ? "?{$query}" : '');

	if (return_object($return)){
        $path = str_replace($domain, '', $url);
        
		return array(
			'url'	    => $url,
			'domain'    => $domain,
            'path'      => $path,
            'ext'       => pathinfo($path, PATHINFO_EXTENSION),
			// 'directory' => $directory,
		);
	}else if ($return === 'domain'){
		return $domain;
	}else if ($return === 'directory'){
		return $directory;
	}else{
		return $url;
	}
    */
}

function to_embed_type ($provider, $fallback='embed'){
    $provider = strtolower($provider);

    $providers = [
        'youtube' => 'video',
        'vimeo'   => 'video',
    ];

    return isset($providers[$provider]) ? $providers[$provider] : $fallback;
}