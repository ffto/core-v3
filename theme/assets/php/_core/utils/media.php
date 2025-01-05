<?php 
// [ ] add volume controle
// [ ] stop other videos when playing a new one
// [ ] Add ways to remove the controls on Youtube/... add ways to pass parameters
// [ ] be able to get the sizes of the videos, maybe with a plugin..., read the tagID3
// [ ] add different args for types (video, audio, youtube, vimeo, ...)
// [ ] add type-source : youtube, native, vimeo, ...
// [ ] todo : lazy loading
// [ ] Add aspect-ratio maybe....
// [ ] rename `to_asset` maybe, instead of `to_media`...

function to_media ($item, $args=null){
	$args = to_args($args, [
		'alias'          => 'media-player',
		'width'          => null,
		'height'         => null,
		'interactive'    => true,             // add auto-interactivness
		'responsive'     => true,             // auto-responsive 
		'thumbnail'      => true,             // add thumbnail
		'thumbnail_attrs'=> null,
		'thumbnail_html' => null,			  // html to add to the Thumbnail
		'caption'        => '',               // add caption
		'wrap'           => false,
		'wrap_attrs'	 => false,
		'poster_attrs'	 => false,
		'template'		 => false, 			 // wrap the media into a <template>, so it can be used in a different situation
		'html'           => null,
		'attrs'          => array(),
		'style'          => array(),
		'class'          => array(),
		'media'          => null,             // media extra attrs
		'media_html'     => null,			  // add extra html after the media (in the inner)
		'embed'          => true,			  // fetch embed files
		'embed_cache'	 => true,
		'cover'          => false,
		'start'          => null,
		'controls'       => true,             // Could be custom HTML too
		'mute'           => false,
		'loop'           => false,
		'autoplay'       => false,
		'autofocus'      => false,
		'overlay'		 => false,			  // add a colored overlay on top of the video
		'alt'            => null,			  // alt text for the Thumbnail
		'lazy'           => null,             // [null = true] if thumbnail
		'preload'        => null,
		'single'         => true,             // play 1 video at a time // [ ] maybe make this false by default
		'return'         => null,
	], 'thumbnail');

	// shortcut ----------------------------------------------------------------
	if (isset($args['auto'])) $args['autoplay'] = $args['auto'];

	if ($args['preload'] === true) 			$args['preload'] = 'auto';
	else if ($args['preload'] === false) 	$args['preload'] = 'none';
	else if ($args['preload'] === null) 	$args['preload'] = 'metadata';

	if ($args['cover']){
		$args['autoplay']   = true;
		$args['controls']   = false;
		$args['thumbnail']  = false;
		$args['responsive'] = false;
	}
	
	// info --------------------------------------------------------------------
	$url = null;

	// Check if the source is a local file
	if (($info = to_mime_type($item, true)) && $info['type']){
		$url    = $item;
		$path   = string_is_url($item) && !is_external_url($item) ? url_to_path($item) : $item;
		$width  = 0;
		$height = 0;
		$mime 	= '';

		// Find the URL of the path and the mime type
		if ($path = to_filepath($path)){
			$url  = path_to_url($path);
			$mime = mime_content_type($path);
			// TODO find a way to get the width/height of the video (if it's a video) (maybe: https://github.com/JamesHeinrich/getID3)
		}
				
		$item = array(
			'type'   => $info['type'],
			'path'	 => $path,
			'src'    => $url,
			'width'  => null,
			'height' => null,
			'mime'   => $mime,
		);
	// URL embeds
	}else if ($args['embed'] && ($embed = http_embed($item, ['cache'=>$args['embed_cache']]))){
		$item = $embed;
	}

	// try getting the URL of the media
	$url = _get($item, 'url', $url);
	
	// TODO manage image too in there, not just video/audio/embed

	// args by type (image,video,audio) ----------------------------------------
	$type     = isset($item['type']) ? $item['type'] : 'rich';
	$args     = _filters('to_media-args', $args, $item, $type);
	$args     = _filters("to_media-{$type}-args", $args, $item, $type);
	$is_video = $type === 'video';
	$is_audio = $type === 'audio';
	
	// Merge $args for specific types. either if the type is set in $args['types'] OR if the special type key is the args (eg.: $args[':video'])
	$type_key = ":{$type}";
	if (isset($args['types'][$type])){
		$args = array_merge($args, $args['types'][$type]);
	}else if (isset($args[$type_key])){
		$args = array_merge($args, $args[$type_key]);
	}

	$is_interactive = $args['interactive'];
	$alias          = $args['alias'];

	if ($type === 'image'){
		// TODO test this properly, if it works well or not
		return to_image($item, $args);
	}

	// sizing ------------------------------------------------------------------
	$width         = $args['width'] ? $args['width'] : _get($item, 'width');
	$height        = $args['height'] ? $args['height'] : _get($item, 'height');
	$width_px      = $width !== null ? "{$width}px" : '';
	$height_px     = $height !== null ? "{$height}px" : '';
	$width         = (int)$width;
	$height        = (int)$height;
	$aspect_ratio  = $is_video ? to_media_aspect_ratio([$width, $height]) : null;
	$is_responsive = $aspect_ratio && $args['responsive'];
	$is_fixed 	   = $is_video && !$is_responsive && !$args['cover'];
	
	$has_template    = !!$args['template'];
	$is_auto_template= $has_template && $args['template'] === true;
	// $custom_template = $has_template && $args['template'] !== true;	
	
	// html: thumbnail ---------------------------------------------------------
	$thumbnail        = null;
	$thumbnail_html   = null;
	$simple_thumbnail = null;

	if (string_is_url($args['thumbnail'])){
		$thumbnail = $args['thumbnail'];
	}else if (string_is_html($args['thumbnail'])){
		$thumbnail_html = $args['thumbnail'];
	}else if ($args['thumbnail']){
		$thumbnail = _get($item, 'thumbnail,thumbnail_url');

		if (is_array($args['thumbnail'])){
			$thumbnail = array_merge(array(
				'url'  => false,
				'path' => $thumbnail,
			), $args['thumbnail']);

			// alternative switch
			if ($thumbnail['url'] && !$thumbnail['path']){
				$thumbnail['path'] = $thumbnail['url'];
				$thumbnail['url']  = false;
			}
		}
	}
	
	// alt (aria-label) for the thumbnail
	$alt = $args['alt'];
	if (!$alt && $is_interactive){
		$alt = string_replace('Play {{ name }}', [
			'name' => _get($item, 'title', 'video'),
		]);
	}
		
	if ($is_video && $is_interactive && ($thumbnail_html || $thumbnail)){
		$thumbnail_attrs = selector_to_attrs($args['thumbnail_attrs'], [
			'x-el'       => $is_interactive ? 'thumbnail' : '',
			'role'       => !$has_template ? 'button' : '',
			'tabindex'   => '0',
			'width'      => '100%',
			'height'     => '100%',
			'aria-label' => $alt,
		]);

		// TODO clean this up
		$simple_thumbnail = '';

		if ($thumbnail_html){
			// TODO 
			$thumbnail = to_html("div.{$alias}--thumbnail", [
				// 'alias'    => "",
				'html'     => [$thumbnail_html, $args['thumbnail_html']],
				'attrs'    => $thumbnail_attrs,		
			]);
		}else if ($thumbnail){
			$thumbnail = to_image($thumbnail, [
				'return'   => 'background',
				'alias'    => "{$alias}--thumbnail",
				'html'     => $args['thumbnail_html'],
				'edit'     => _get($args, 'edit'),
				'download' => _get($args, 'download'),
				'attrs'    => $thumbnail_attrs,		
			]);
		}
	}else{
		$thumbnail = '';
	}

	// html:media --------------------------------------------------------------
	$media = _get($item, 'html');
	if ($media){

		// Make the video responsize, remove the fixed width/height on the iframe
		if ($is_responsive && strpos($media, '<iframe') !== false){
			$media = preg_replace('/(width|height)=\"[^\"]+\"/', '', $media);
			$media = str_replace('<iframe', '<iframe width="100%" height="100%"', $media);
		}

		// make sure the class is there
		$media = str_replace('<iframe', '<iframe class="@--source"', $media);

		// TODO add way of controlling the mute/volume/controls/ ....
	}else{
		$src  = $url = _get($item, 'src', '');
		$mime = _get($item, 'mime', '');

		// start the video/audio as a certain time
		if ($args['start']){
			$src .= "#t={$args['start']}";
		}

		$attrs = array(
			'x-el'        => $is_interactive ? 'player' : '',
			'class'	 	  => ['@--source'],
			'controls'    => !$args['controls'] ? null : true,
			'muted'       => $args['autoplay'] || $args['mute'] ? 'true' : null,
			'loop'        => $args['autoplay'] || $args['loop'] ? 'true' : null,
			'autoplay'    => $args['autoplay'],
			'preload'     => $args['preload'],
			'playsinline' => true,
			'width'		  => '100%',
			'height'	  => '100%',
			'src'         => $src,
		);
		
		$item['provider_name'] = 'native';
		if ($type === 'video'){
			$attrs['src'] = "{$src}#t=0.01";
			$media = to_html('video', $attrs, $args['media']); 
		}else if ($type === 'audio'){
			$media = to_html('audio', $attrs, $args['media']); 
		}
	}

	// invalid -----------------------------------------------------------------
	if (!$media){
		return _warn('Invalid Media data', $item);
	}

	// Html --------------------------------------------------------------------
	$poster = '';

	// wrap the media in a block
	$html = $is_video ? to_html('div', array(
		'x-el'  => $is_interactive ? 'media' : '',
		'class' => ['@--media'],
		'html'	=> $media,
	)) : $media;

	//inner: wrap 
	$wrap  = is_string($args['wrap']) ? $args['wrap'] : 'div';
	$inner = $html;

	if ($thumbnail || $is_responsive || $args['wrap'] || $args['media_html']){
		$wrap_attrs = selector_to_attrs($args['wrap_attrs'], [
			'tag'	=> 'div',
			'class' => ['@--wrap','@--inner'],
			'html'  => [$html, $thumbnail, $args['media_html']],
			'style'	=> [
				'width'  => $is_fixed ? $width_px : '',
				'height' => $is_fixed ? $height_px : '',
			]
		]);
		$inner = to_html($wrap, $wrap_attrs);	

		if ($has_template){
			$poster = selector_to_attrs($args['poster_attrs'], [
				'tag'	=> 'div',
				'class' => ['@--wrap','@--inner'],
				'html'  => [$thumbnail],
				'style'	=> [
					'width'  => $is_fixed ? $width_px : '',
					'height' => $is_fixed ? $height_px : '',
				]
			]);
			$poster = to_html($wrap, $poster);
		}
		
		// $poster = $has_template ? to_html($wrap, [
		// 	'tag'	=> 'div',
		// 	'class' => ['@--wrap','@--inner'],
		// 	'html'  => [$thumbnail],
		// 	'style'	=> [
		// 		'width'  => $is_fixed ? $width_px : '',
		// 		'height' => $is_fixed ? $height_px : '',
		// 	]
		// ]) : '';	
	}

	// extra html (eg.: controls) ----------------------------------------------
	// TODO add default controls (if interactive)
	$extra_html   = $args['html'];
	$has_controls = false;
	if (is_string($args['controls'])){
		$has_controls = true;
		$controls     = $args['controls'];

		if ($controls === ':audio'){
			$controls = '
				<div class="@--controls">
					<div class="@--toggle">
						{{ play }} {{ pause }}
					</div>
					<div class="@--info">
						{{ progress }}
						{{ time }} {{ duration }}
					</div>
				</div>
			';
		}else if ($controls === ':video'){
			// TODO
		}
		
		$controls = string_replace($controls, [
			'play'     => '<button unstyled name="play" class="@--button @--play when-paused"><span>⏵</span></button>',
			'pause'    => '<button unstyled name="pause" class="@--button @--pause when-playing" hidden><span>⏸</span></button>',
			'progress' => '<div x-el="progress" class="@--progress"></div>',
			'time'     => '<div x-el="time" class="@--time">0:00</div>',
			'duration' => '<div x-el="duration" class="@--duration">0:00</div>',
			// TODO add more stuff here
		]);

		$extra_html .= $controls;

		$args['controls'] = null;
	}

	//outer: wrap 
	$outer = $extra_html ? to_html('div', array(
		'class' => '@--outer',
		'html'  => [$inner, $extra_html],
	)) : $inner;


	// caption -----------------------------------------------------------------
	$caption = $args['caption'] ? to_html('figcaption', array(
		'class'=> '@--caption',
		'html' => $args['caption'],
	)) : '';

	// html: output ------------------------------------------------------------
	$classnames = [
		'@', 
		$is_responsive ? 'is-responsive' : '', 
		"is-{$type}", 
		$args['class'],
	];

	$style                   = $args['style'];
	$style['--media-spacer'] = $aspect_ratio ? $aspect_ratio['percent'] : null;

	if ($is_fixed && !isset($style['width'])){
		$style['width'] = $width_px;
	}

	if ($args['overlay']){
		$overlay = _args($args['overlay'], [
			'color'   => true,
			'opacity' => 0.2,
		], 'color');

		if ($overlay['color'] === true){
			$overlay['color'] = 'black';
		}

		$style['--media-overlay'] 		  = $overlay['color'];
		$style['--media-overlay-opacity'] = $overlay['opacity'];

		$classnames[] = 'has-overlay';
	}

	if ($args['cover']){
		$classnames[] = 'is-cover';
	}
	if ($thumbnail){
		$classnames[] = 'has-thumbnail';
	}
	if ($aspect_ratio && $aspect_ratio['orientation']){
		$classnames[] = 'is-'.$aspect_ratio['orientation'];
	}
	
	if (isset($style[':size'])){
		$style['--media-width']  = $width;
		$style['--media-height'] = $height;
		unset($style[':size']);
	}

	if (isset($style[':aspect-ratio'])){
		$style = $aspect_ratio ? array_merge($style, $aspect_ratio['style']) : $style;
		unset($style[':aspect-ratio']);
	}

	// lazy loading
	$lazy = $args['lazy'] || ($args['lazy'] === null && $thumbnail);

	$html = to_html('figure', array(
		'x'     => $is_interactive ? 'media-player' : '',
		'attrs' => $args['attrs'],
		'role'	=> 'none',
		'alias' => ['@'=>$alias],
		'class' => $classnames,
		'style' => $style,
		'html'  => [$outer, $caption],
		'data'  => $is_interactive ? [
			'type'		=> $type,
			'thumbnail' => $thumbnail ? 'true' : 'false',
			'provider'  => isset($item['provider_name']) ? strtolower($item['provider_name']) : '',
			'single'	=> $args['single'],
			'autofocus'	=> $args['autofocus'],
			'autoplay'	=> $args['autoplay'],
			'loop'		=> $args['loop'],
			'controls'	=> $args['controls'],
			'lazy'		=> $lazy,
		] : null,
	));

	// Show a poster with a <template> inside, so the real media can be used elsewhere
	if ($poster){
		$template = $is_auto_template ? $html : __content($args['template'], [$item, $args]);

		$html = to_html('figure', [
			'x'		=> true,	// with 'x', the template will be kept there
			'attrs' => $args['attrs'],
			'role'	=> 'none',
			'alias' => ['@'=>$alias],
			'class' => [$classnames, '@--poster'],
			'style' => $style,
			'html'  => [$poster, $caption, '<template>'.$template.'</template>'],
		]);
	}
	
	// Default CSS
	$is_video && the_css($alias, "
		& 							 	{ position:relative; }		
		&--wrap						 	{ position:relative; }
		&--wrap::before				 	{ display:block; content:''; top:0; left:0; width:100%; padding-bottom:var(--media-spacer); }
		&--media					 	{ position:absolute; top:0; left:0; width:100%; height:100%; z-index:1; }
		&--media > *				 	{ vertical-align:top; width:100%; height:100%; }
		&--thumbnail				 	{ position:absolute; top:0; left:0; width:100%; height:100%; background-size:cover; background-position:center; z-index:2; }
		&.is-cover 					 	{ width:100%; height:100%; pointer-events:none; }
		&.is-cover &--media > *			{ object-fit:cover; }
		&.has-overlay &--media::after	{ content:''; position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; background:var(--media-overlay); opacity:var(--media-overlay-opacity); z-index:3; }
	", ['once'=>'media-video']);

	$is_audio && $has_controls && the_css($alias, "
		&--source			{ display:none; }
		&--button			{ border:1px solid; padding:0 0.75em; }
		&--progress 		{ background-color:lightgray; height:10px; }
		&--progress::before { background-color:black; content:''; display:block; height:100%; width:var(--progress, 0); }
	", ['once'=>'media-audio']);

	$is_interactive && add_script_helper('to_media_player_js');

	$return = $args['return'];
	if (return_object($return)){
		return array(
			'url'          => $url,
			'width'        => $width,
			'height'       => $height,
			'aspect_ratio' => $aspect_ratio,
			'media'        => $media,
			'thumbnail'    => $thumbnail,
			'caption'      => $caption,
			'html'         => $html,
		);
	}else if ($return === 'simple_thumbnail'){
		return $simple_thumbnail;
	}else if ($return === 'thumbnail'){
		return $thumbnail;
	}

	return $html;
}

// image ---------------------------------------------------------------------------------------------------------------
function to_image ($path, $args=null, $is_object=false){
	if ($args === true){
		$args      = '';
		$is_object = true;
	}
	if (is_array($path)){
		$args = to_args($path, $args);
		$path = null;
	}

    $args = to_args($args, array(
		'path'				=> null,
		'dir'	  			=> '@images',
		'alias'				=> 'image',
		'mini_class'		=> 'img',
		// size / image type ---------------------------------------------------
		'edit'              => '',      // edit more in details (change extension, and more)
		'edit_dir' 	        => null,    
        'size'				=> null,	// size : 300x200 / 300xauto / name for a specific size
        'resize_gif'        => false,   // since gif is generally animated
		'ratio'				=> null,    // change the width or height
		'natural_width'		=> null,
		'natural_height'	=> null,
		'width'				=> null,	// [number / true]
		'height'			=> null,    // [number / true]
		'orientation'		=> null,
		// wrap & link ---------------------------------------------------------
		'id'				=> null,
		'attrs'				=> false,
		'style'				=> false,
		'data'				=> false,
		'class'				=> false,
		'wrap'              => false,	 
        'link'              => false,
        'target'            => false,
		'image'				=> false,
		'alt'				=> true,
		'figure'			=> '',		
		'caption'			=> '',		// used with figure
		'html'			    => false,	// [html in the center of the background]
		'hidden'			=> false,
		//svg ------------------------------------------------------------------
		'inline'			=> false, 	// inline svg
		'svg'				=> null,	// edit svg with css selector, OR fill, stroke, color 
		'fill'				=> false,	// shortcut for editing svg, changing the fill color
		'stroke'			=> false,	// shortcut for editing svg, changing the stroke color
		// other ---------------------------------------------------------------
		'aspect_ratio'		=> false,  	// add automatic aspect-ratio (or specified), for normal image, it will wrap it in a <div>
		'pinterest'			=> null,	// remove the "pin" button that shows up
		'focus'				=> null,	// [x and y] on the image,
		'origin'			=> null,	// default [0.5, 0.5]
		'presentation'		=> false,
		'lazy'				=> 'loading',	// [true, loading]
		'lazy_color'		=> false,	// background color while the stuff is loading
		'version'			=> null,
		'return'			=> null,   // [path, url, img, bg, background, object]
		'object'			=> $is_object,
		'empty'				=> false,
		'data'				=> null, 	// data to return if it's an object
		'template'			=> null, 	// add a <template> of the image, good for use with Javascript
		// download ------------------------------------------------------------
		'download'			=> false,	// If it's an external URL, download it
		'download_dir'		=> _config('download_dir', '@upload/images/'),
		// filters -------------------------------------------------------------
		'filter'			=> null, // callback function to edit the html/class/attrs, AFTER the size of the image have been found
		'filter_attrs'		=> null, 
	), 'return');
	
	if (return_object($args['return'])){
		$args['object'] = true;
	}

	if ($args['path']){
		$path = $args['path'];
	}
	if (isset($args['type']) && $args['return'] === null){
		$args['return'] = $args['type'];
	}
	if (isset($args['url'])){
		$args['link'] = $args['url'];
	}

	$args = _filters('to_image-args', $args);
	$path = to_filepath($path, [
		'url' => true,
		'dir' => $args['dir']
	]);

	if ($args['focus']){
		add_script_helper('the_image_focus_js');
	}
	
    if (!$path && !$args['empty']) return '';

	// local/downloading -------------------------------------------------------
	$original_path = $path;

	$is_url = string_is_url($path);

	// try finding the path from the local URL
	if ($is_url && !is_external_url($path)){
		$p = url_to_path($path);
		
		if (is_file($p)){
			$path   = $p;
			$is_url = false;
		}
	}

	if ($is_url && is_external_url($path) && $args['download']){
		$path = download_url($path, [
			'dir'  => $args['download_dir'],
			'name' => $args['download'],
		], function ($image, $data) use ($args){
			$image = imagecreatefromstring($image);
			return image_edit($image, [
				'name'   => $data['filepath'],
				'ext'	 => $data['ext'],
				'width'  => _config('image_download_max_width', 0),
				'height' => _config('image_download_max_height', 0),
			]);
		});

		$is_url = false;
	}

	// edit / sizes ------------------------------------------------------------
	$is_svg         = !$is_url && preg_match('/\.svg$/', $path);
	$is_gif         = !$is_url && preg_match('/\.gif$/', $path);
	$image_type     = null;
	$natural_width  = null;
	$natural_height = null;
	$svg            = null;

    if ($is_svg){
        $svg    = get_dom($path, 'svg');
        $vbox   = dom_get_attr($svg, 'viewBox');
        $size   = str_replace(',', ' ', $vbox);        // make sure it's all separated by spaces
        $size   = explode(' ', $size);
        $width  = 0;
        $height = 0;

		if (count($size) === 4){
        	list($x, $y, $width, $height) = $size;
		}

        $natural_width  = (int)$width;
        $natural_height = (int)$height;
        $image_type     = 'image/svg+xml';
        
        // update colors to svg
        $edit = to_args($args['svg']);

		if ($args['fill']){
			$edit['fill'] = $args['fill'];
		}
		if ($args['stroke']){
			$edit['stroke'] = $args['stroke'];
		}
		
        foreach ($edit as $i => $v){
            if (is_callback($v)){
                dom_get_nodes($svg, $i, $v);
			// updating specific properties (eg.: stroke, fill) on all nodes
            }else if (is_string($i)){
                dom_get_nodes($svg, "[{$i}]", function ($node) use ($i, $v){ 
                    $node->setAttribute($i, $v);
                });
            }
        }

        $html = dom_get_attr($svg, 'html');

        // remove empty groups
		$html = preg_replace('/\<g\>\n\<\/g\>/', '', $html);
		// replace the spaces/carriage returns and tabs
		$html = preg_replace('/\t|\n/', ' ', $html);
		$html = preg_replace('/\s+/', ' ', $html);

        $svg = array(
			'viewbox' => $vbox,
			'dom'     => $svg,
			'html'    => $html,
			'width'   => $width,
			'height'  => $height,
		);
    }else if (!$is_url && $path){
        $size           = getimagesize($path);
        $natural_width  = (float)$size[0];
        $natural_height = (float)$size[1];
        $image_type     = $size['mime'];
    }else{
        // $image_type = exif_imagetype($path);
    }

	$natural_width  = $args['natural_width'] ? $args['natural_width'] : $natural_width;
	$natural_height = $args['natural_height'] ? $args['natural_height'] : $natural_height;

	// focus -------------------------------------------------------------------
	$focus = null;
	
	if ($args['focus']){
		$focus = $args['focus'] === true ? '50%,50%' : $args['focus'];
		$focus = to_array($focus);
		
		if (isset($focus[0]) && isset($focus[1])){
			if (count($focus) === 4){
				$focus = [
					calculate($focus[0]),
					calculate($focus[1]),
					calculate($focus[2]),
					calculate($focus[3]),
				];
			}else{
				$focus = [
					calculate($focus[0]),
					calculate($focus[1]),
				];
			}
		}else{
			$focus = null;
		}		
	}

    // edit --------------------------------------------------------------------
	if ($args['edit'] || $args['size'] && (!$is_url && !$is_svg && ($args['resize_gif'] || !$is_gif))){
		$edit = $args['edit'];

		// check if the edit is pre-defined
		$edits  = _config('image_edits', []);
		$is_key = (is_string($edit) || is_numeric($edit));
		
		if (is_callback($edit)){
			$edit = ['callback'=>$edit];
		}else if ($is_key && array_key_exists($edit, $edits)){
			$edit = $edits[$edit];
		}
		$edit = _args($edit);
				
		// check if the size is pre-defined
		$size   = _filters('to_image-size', $args['size'], $args);
		$sizes  = _config('image_sizes', array());
		$is_key = (is_string($size) || is_numeric($size));
		if ($is_key && array_key_exists($size, $sizes)){
			$size = $sizes[$size];
		}

		// check for size pattern
		if ($size){
			$s = array();

			if (is_string($size)){
				$s = explode('x', $size);
				$s = array(
					'width'  => isset($s[0]) ? (float)$s[0] : 0,
					'height' => isset($s[1]) ? (float)$s[1] : 0,
					'crop'	 => isset($s[2]) ? $s[2] : false,
					'ratio'	 => isset($s[3]) ? (float)$s[3] : 1,
				);
			}else if (is_numeric($size)){
				$s['width']  = (float)$size;
				$s['height'] = (float)$size;
				$s['crop']	 = true;
			}else if (is_array($size)){
				$s = $size;
			}
			
			$edit = _args($edit, $s);
		}

		// add the focus values to the crop, and remove the "focus" style from the $attrs['style'] 
		if (isset($edit['crop']) && $edit['crop'] === ':focus' && $focus){
			$args['focus']= false;
			$edit['crop'] = $focus;
			// $focus 	  = null;
		}

		$edit['dir'] = isset($edit['dir']) ? $edit['dir'] : $args['edit_dir'];

		if (($cachepath = image_edit($path, $edit)) && is_file($cachepath) && is_file($path)){
			$path = $cachepath;
			$size = getimagesize($path);
			if ($size){
				$natural_width  = (float)$size[0];
				$natural_height = (float)$size[1];
				$image_type     = $size['mime'];
			}
		}
	}

    // data --------------------------------------------------------------------
	$url    = $is_url ? $path : ($path ? to_version(path_to_url($path)) : '');
	$alt    = $args['alt'] === true ? pathinfo($path, PATHINFO_FILENAME) : $args['alt'];
	$alt    = is_string($alt) ? strip_tags($alt) : $alt;
	$return = $args['return'];

	if ($return === 'path'){
		return $path;
	}else if ($return === 'src' || $return === 'url'){
		return $url;
	}else if ($return === 'img'){
		return '<img src="'.$url.'" />';
	}

	// size
	$final_width  = $natural_width;
	$final_height = $natural_height;
	$width        = '';
	$height       = '';
	$orientation  = null;

	if ($args['width'] || $args['height'] || $args['ratio']){
		$width  = $args['width'] === true ? $natural_width : ($args['width'] ? $args['width'] : null);
		$height = $args['height'] === true ? $natural_height : (is_numeric($args['height']) ? $args['height'] : null);

		if ($args['ratio']){
			$ratio = (float)$args['ratio'];

			if (is_float($height)){
				$height *= $ratio;
				// $final_height = $height;
			}else{
				$width  	 = (is_float($width) ? $width : $natural_width) * $ratio;	
				$final_width = $width;
			}
		}

		if (is_float($height)){
			$final_height = $height;
			$final_width  = is_float($width) ? $width : $natural_width * ($height / $natural_height);
		}else if ($natural_width){
			$final_width  = is_float($width) ? $width : $natural_width;
			$final_height = is_float($height) ? $height : (is_float($width) ? $natural_height * ($width / $natural_width) : $natural_width);
		}
	}

	// aspect ratio
	$aspect_ratio = to_media_aspect_ratio($args['aspect_ratio'], $natural_width, $natural_height);
	$orientation  = $args['orientation'] !== null ? $args['orientation'] : $aspect_ratio['orientation'];
	$orientation  = $aspect_ratio['orientation'];
	
	$data = array(
		'url'            => $url,
		'path'           => $path,
		'alt'            => $alt,
		'width'          => $final_width,
		'height'         => $final_height,
		'natural_width'  => $natural_width,
		'natural_height' => $natural_height,
		'type'           => $image_type,
		'svg'            => $svg,
		'caption'        => $args['caption'] ? $args['caption'] : '',
		'orientation'    => $orientation,
		'aspect_ratio'   => $aspect_ratio,
		'focus'			 => $focus,
		'data'			 => $args['data'],
	);
	
	// formating the $args with the $data 
	// $args = _apply($args['filter'], $args, $data);
	$args = _filters('to_image-post_args', $args, $data);
	$args = _apply($args['filter'], $args, $data);

	// attrs -------------------------------------------------------------------
	$is_background = $args['return'] === 'background' || $args['return'] === 'bg';
	$style         = to_style($args['style'], true);

	if (isset($style[':size'])){
		$style['--media-width']  = $natural_width;
		$style['--media-height'] = $natural_height;
		unset($style[':size']);
	}

	if (isset($style[':aspect-ratio'])){
		$style 				  = array_merge($style, $aspect_ratio['style']);
		$args['aspect_ratio'] = false;
		unset($style[':aspect-ratio']);
	}
	
	$attrs = selector_to_attrs($args['attrs'], array(
		'id'	=> $args['id'],
		'style' => $style,
		'class' => array(
			'@',
			$is_background ? 'is-background' : '',
			$orientation ? "is-{$orientation}" : null,
			$focus ? 'has-focus' : '',
			$args['class'],
		)
	));

	if ($args['focus'] && $focus){
		// add_script_helper('the_image_focus_js');
		
		$origin = to_array($args['origin']);
		$fx 	= $focus[0] * 100 . '%';
		$fy 	= $focus[1] * 100 . '%';
		$focus  = [
			'--focus-x'             => $focus[0],
			'--focus-y'             => $focus[1],
			'--focus-w'             => isset($focus[2]) ? $focus[2] : null,
			'--focus-h'             => isset($focus[3]) ? $focus[3] : null,
			'--origin-x'            => isset($origin[0]) ? calculate($origin[0]) : 0.5,
			'--origin-y'            => isset($origin[1]) ? calculate($origin[1]) : 0.5,
			'--media-width'         => $natural_width,
			'--media-height' 		=> $natural_height,
			'object-position' 		=> $is_background ? '' : "{$fx} {$fy}",
			'background-position-x' => $is_background ? $fx : '',
			'background-position-y' => $is_background ? $fy : ''
		];

		// add classname
		// $classnames[] = 'has-focus';
		// TODO add focus box option		
	}

	if ($args['hidden']){
		$attrs['hidden'] = is_string($args['hidden']) ? $args['hidden'] : true;
	}

	if ($args['data']){
		$a 			   = _get($attrs, 'data');
		$attrs['data'] = arr_merge($a, $args['data']);
	}

	$attrs = _filters('to_image-attrs', $attrs, $args);
	$attrs = _apply($args['filter_attrs'], $attrs, $data, $args);

	// attrs:link / wrap / image -----------------------------------------------
	$link   = null;
	$figure = null;
	$wrap   = null;
	$image  = null;

	if ($args['link']){
		$link = array(
			'tag'   => 'a',
			'attrs' => array(),
			'class' => array('@--link'),
		);

		if (string_is_url($args['link'])){
			$href 		  = to_url($args['link']);
			$link['href'] = $href;
		}else{
			$link = selector_to_attrs($args['link'], $link);
		}

		// auto target
		// $link_url    = isset($link['href']) ? $link['href'] : (isset($link['attrs']['href']) ? $link['attrs']['href'] : '');
		// $link_target = isset($link['target']) ? $link['target'] : (isset($link['attrs']['target']) ? $link['attrs']['target'] : '');
		// $link_aria   = isset($link['aria-label']) ? $link['aria-label'] : (isset($link['attrs']['aria-label']) ? $link['attrs']['aria-label'] : '');

		$link_url    = _get($link, 'href, attrs.href', '');
		$link_target = _get($link, 'target, attrs.target', $args['target']);
		$link_aria   = _get($link, 'aria-label, attrs.aria-label', '');

		if (!$link_target && $link_url){
			$link['attrs']['target'] = is_external_url($link_url) ? '_blank' : '';
		}else{
			$link['attrs']['target'] = $link_target;
		}

		// aria label
		if ($link_aria){
			$link['attrs']['aria-label'] = to_url_aria_label($link_url, $link_aria);
		}

		$link = array_join([$attrs, $link]);
		$link = _filters('to_image-link', $link, $args);
		$attrs= null;
	}
	
	if ($args['figure'] || ($args['caption'] && $args['figure'] !== false)){
		$figure = array(
			'tag'   => 'figure',
			'attrs' => ['role'=>'none'],
			'class' => array('@--figure')
		);
		$figure = selector_to_attrs($args['figure'], $figure);
		$figure = array_join([$attrs, $figure]);
		$figure = _filters('to_image-figure', $figure, $args);
		$attrs= null;
	}

	if ($args['wrap']){
		$wrap = array(
			'tag'   => 'div',
			'class' => array('@--wrap')
		);
		$wrap = selector_to_attrs($args['wrap'], $wrap);
		$wrap = array_join([$attrs, $wrap]);
		$wrap = _filters('to_image-wrap', $wrap, $args);
		$attrs= null;
	}
	
	if ($args['image']){
		$image = selector_to_attrs($args['image']);
		$image = array_join([$attrs, $image]);
	}else{
		$image = selector_to_attrs($attrs);
	}
	
	if ($args['focus'] && $focus){
		$image['style'] 		 = array_merge($image['style'], $focus);
		$image['attrs']['focus'] = $is_background ? 'background' : 'image';
	}

	// presentation
	if ($args['presentation']){
		$alt 					= '';
		$image['attrs']['role'] = 'presentation';
	}

	$image['class'][] = '@--source';
	$image['class'][] = '@--media';
	$image['class'][] = $args['mini_class'];
	$image     		  = _filters('to_image-image', $image, $args);

	// html --------------------------------------------------------------------
	if ($is_background){
		$image['tag']             = 'div';
		$image['class'][]         = '@--background';
		$image['style']['width']  = default_unit($width);
		$image['style']['height'] = default_unit($height);

		// if the alt if specified
		if (is_string($args['alt'])){
			$image['role']       = "img";
			$image['aria-label'] = $alt;
		}

		// background image
		if ($args['lazy'] === true){
			$image['attrs']['lazy-bg']          = $url;
			$image['style']['background-color'] = $args['lazy_color'];
		}else{
			$image['style']['background-image'] = $url ? "url('$url')" : '';
		}

		// _js($args['lazy'], $url);

		// inner html 
		$html = [];
		if (is_truthy($args['html'])){
			$html[] = '<img src="'.$url.'" class="@--alt-source" role="none" />';
		}else if ($args['html']){
			$html[] = $args['html'];
		}

		if (is_truthy($args['aspect_ratio'])){
			$html[] = '<div style="padding-bottom:'.$aspect_ratio['padding'].'"></div>';
		}
		$image['html'] = $html;

		// TODO option to have the focus dynamicly here
	}else if ($is_svg && $args['inline']){
		$image['tag']						   = 'svg';
		$image['width']						   = $natural_width;
		$image['attrs']['role']    			   = 'img';
		$image['attrs']['xmlns']               = "http://www.w3.org/2000/svg";
		$image['attrs']['xmlns:xlink']         = "http://www.w3.org/1999/xlink";
		$image['attrs']['preserveAspectRatio'] = "none";
		$image['attrs']['viewBox']             = $svg['viewbox'];
		$image['style']['width']               = default_unit($width);
		$image['style']['height']              = default_unit($height);

		if ($alt){
			$titleID = to_counter('svg_title', 'svg_title_');
			$image['attrs']['aria-labelledby'] = $titleID;
			$svg['html'] = '<title id="'.$titleID.'">'.$alt.'</title>' . $svg['html'];
		}

		$image['html'] = $svg['html'];
	}else if ($path){
		$image['tag']             = 'img';
		$image['attrs']['alt']    = is_string($alt) ? "{$alt} " : $alt;
		$image['attrs']['width']  = $args['width'] !== false ? $width : '';
		$image['attrs']['height'] = $args['height'] !== false ? $height : '';
		$image['attrs']['nopin']  = $args['pinterest'] === false ? 'nopin' : '';

		// TODO change this code, it's convoluted, the width/height thing ....
		// Maybe should be more "width = true or value, then SHOW", by default it's null

		// TODO check if lazy loading should only be on certain type of image (none SVG for example, or if the image is already super small)
		if ($args['lazy'] === 'loading'){
			$image['attrs']['loading'] = 'lazy';
			$image['attrs']['lazy']    = true;
			$image['attrs']['src']     = $url;

			// javascript loading script
			if (!isset($image['attrs']['onload'])){
				// for the lazy load to work properly (especially for item that comes in from the left or right side)
				$image['attrs']['width']  = $args['width'] !== false ? ($width ? $width : $natural_width) : '';
				$image['attrs']['height'] = $args['height'] !== false ? ($height ? $height : $natural_height) : ''; // TODO need to rethink this, so, if we resize, we don't HAVE TO show the height
				$image['attrs']['onload'] = 'this.removeAttribute("lazy"); this.removeAttribute("onload")';
			}
		}else if ($args['lazy']){
			// uses a temporary SVG fix for the image intrinsic size
			$image['attrs']['src']              = is_truthy($args['aspect_ratio']) ? $aspect_ratio['placeholder_src'] : null;
			$image['attrs']['lazy-src']         = $url;
			$image['style']['background-color'] = $args['lazy_color'];

			// TODO maybe add dynamic JAVASCRIPT for auto load
		}else{
			$image['attrs']['src'] = $url;
		}
	}else{
		// empty html
		$image['tag']  	  = 'div';
		$image['html'] 	  = $args['html'] ? $args['html'] : '';
		$image['class'][] = 'is-empty';
	}
	
	$source = $image;

	if ($wrap){
		if ($aspect_ratio['x'] && $args['aspect_ratio']){
			$image['style']['position']      = 'absolute';
			$image['style']['width']         = '100%';
			$image['style']['height']        = '100%';
			$image['style']['object-fit']    = 'cover';
			$wrap['style']['position']		 = 'relative';
			$wrap['style']['padding-bottom'] = $aspect_ratio['padding'];
		}

		// adding aspect-ratio to the wrap (doesnt work on the image itself)
		// if ($args['lazy'] && $ratio_y && $natural_width && $natural_height){
		// 	$wrap['style']['aspect-ratio'] = $ratio_y;
		// }

		$wrap['html'] = $image;
		$image 		  = $wrap;
	}

	if ($figure || $args['template']){
		$caption = null;

		// the caption html has already <figcaption in it // TODO rewrite this properly
		if (isset($args['caption']) && strpos($args['caption'], '<figcaption') !== false){
			$caption = $args['caption'];
		}else{
			$caption     = is_array($args['caption']) ? $args['caption'] : ($args['caption'] ? array('html'=>$args['caption']) : false);
			$has_caption = isset($caption['html']) && trim($caption['html']);
			$caption = $has_caption ? selector_to_attrs($caption, array(
				'tag'	=> 'figcaption',
				'class' => array('@--caption'),
			)) : '';
			
			$caption = $caption ? _filters('to_image-caption', $caption, $args) : '';
		}

		$html = [$image, $caption];
		
		if ($tmpl = $args['template']){
			// Need the "x" attribute so the <template> isn't fetched by a parent "Web.Element"
			$figure['x'] = true;
			
			// add the template version
			if ($tmpl === true){
				// Default template is a <figure> that includes the image and the caption (if defined)

				$cpt  					  = $has_caption ? '<figcaption>' . $caption['html'] . '</figcaption>' : '';
				$tmpl                     = $source;
				$tmpl['attrs']['loading'] = '';
				$tmpl['attrs']['lazy']    = '';
				$tmpl['attrs']['onload']  = '';
				
				$tmpl = __html('figure', [
					'style' => $figure['style'],
					'html'  => [$tmpl, $cpt]
				]);				
			}else{
				$tmpl = __content($tmpl, [$original_path, $args, $image]);
			}
			
			$html[] = '<template>' . $tmpl . '</template>';
		}

		$figure['html'] = $html;
		$image 		  	= $figure;
	}

	if ($link){
		$link['html'] = $image;
		$image 		  = $link;
	}

	$image = to_html($image);
	$image = $data['html'] = replace_alias($image, $args['alias'], 'prefix=@');

	if ($is_object || $args['object'] || return_object($args['return'])){
		$source         = to_html($source);
		$source         = replace_alias($source, $args['alias'], 'prefix=@');
		$data['source'] = $source;
		return $data;
	}else{
		return $image;
	}
}

function to_icon ($path, $args=null){
	$args = _args($args, [
		'alias'		=> 'icon',
		'mini_class'=> 'icn',
		'lazy'		=> false,
		'pinterest' => false,
	]);

	// add the filename for the classname
	if (is_string($path)){
		$classnames = _get($args, 'class', []);
		$args['class'] = to_classnames([$classnames, [
			'icn-' . pathinfo($path, PATHINFO_FILENAME),
		]]);
	}

	return to_image($path, $args);
}

// TODO image_edit
// [ ] add limit sizing for file MB and file Sizes
// [ ] add way of only "delete" the generated image with "?refresh=", to be able to first delete all generated image in the page, then we can re-generate them once
// [ ] add pre-made effect, like "transparent" for white bg, others...
// [ ] add a focus option for cropping position
/**
 * @param [type] $path
 * @param string $args
 * @return void
 */
function image_edit ($path, $args=''){
	$args = _args($args, array(
		'dir'         => _config('image_edit_dir'),
		'ext'         => null,
		'name'        => '',
		'fit'         => 'contain',
		'width'       => 0,
		'height'      => 0,
		'ratio'       => 1,                                    // width/height multiplier
		'crop'        => false,
		'transparent' => false,	// use a color and everything lighter will be transparent
		'colorize'    => false, // colorize everything
		'inverse'     => false,	// inverse transparency
		'pre_callback'=> null,
		'callback'    => null,                                 // custom edits
		'quality'     => _config('image_edit_quality', 1),
		'lowersize'   => _config('image_edit_lowest', true),   // return the lower size of the file (resized VS original)
		'cache'       => _config('image_edit_cache', true),
		'return'      => false,
	), null, 'image_edit-args');	
	
	$path		 = url_to_path($path);
	$is_resource = is_resource($path);
	$source 	 = $is_resource ? $path : null;
	$path        = $is_resource ? null : to_filepath($path, ['dir'=>$args['dir']]);
	
	if (array_key_exists('size', $args)){
		_err('image_edit(), Use "fit" instead of "size"');
	}
	
	if (!$path && !$source) return;

	// auto-quality change, for colorize image (using only 1 color)
	if ($args['colorize'] && $args['quality'] === null){
		$args['quality'] = 0.3;
	}

	if (is_refresh('image_edit', true)){
		$args['cache'] = false;
	}

	$is_delete = is_refresh('image_delete', true);
	$pathinfo  = $path ? pathinfo($path) : array('filename'=>'', 'dirname'=>'', 'extension'=>'jpg');
	$ext       = $args['ext'] ? $args['ext'] : $pathinfo['extension'];
	$width     = $args['width'] * $args['ratio'];
	$height    = $args['height'] * $args['ratio'];
	$filename  = $args['name'];

	$code_color       = $args['colorize'] ? ($args['colorize']===true?'colorized':"c_{$args['colorize']}") : '';
	$code_color		  = str_replace('#', '', $code_color);
	$code_transparent = $args['transparent'] ? ($args['transparent']===true?'transparent':"t_{$args['transparent']}") : '';
	$code_transparent = str_replace('#', '', $code_transparent);
	$code_edit        = $args['callback'] ? 'edited' : '';
	$is_resized		  = $width || $height;

	if (!$filename){
		$same_ext = $args['ext'] === $pathinfo['extension'];

		if (!$width && !$height && !$same_ext){
			$filename = _config('image_edit_convert_name', '{{name}}'.
				($code_color 		? "-{$code_color}" : '').
				($code_transparent 	? "-{$code_transparent}" : '').
				($code_edit 		? "-edit" : '').
			'.{{ext}}');
		}else{
			$filename = _config('image_edit_resize_name', '{{name}}-{{width}}x{{height}}'.
				($args['crop'] 			? '{{crop}}' : '').
				($code_color 			? "-{$code_color}" : '').
				($code_transparent 		? "-{$code_transparent}" : '').
				($code_edit 			? "-edit" : '').
				($args['quality'] !== 1 ? '-{{quality}}q' : '').
			'.{{ext}}');
		}
	}else{
		$filename = parse_path($filename);
	}

	$crop     = $args['crop'];
	$crop_key = '';

	if ($crop){
		// eg: 0.5x0.95
		if (is_string($crop) && strpos($crop, 'x') !== false){
			$crop = explode('x', $crop);
			$crop = array((float)$crop[0], (float)$crop[1]); 
		// eg: top / left / bottom right / center-top / ...
		}else if (is_string($crop)){
			$crop = strtolower($crop);
			$x    = 0.5;
			$y 	  = 0.5;

			if (strpos($crop, 'top') !== false){
				$y = 0;
			}else if (strpos($crop, 'bottom') !== false){
				$y = 1;
			}

			if (strpos($crop, 'left') !== false){
				$x = 0;
			}else if (strpos($crop, 'right') !== false){
				$x = 1;
			}

			$crop = array($x, $y);
		}
		
		if (!is_array($crop)){
			$crop 	  = array(0.5, 0.5);
			$crop_key = 'c';
		}else{
			$crop_key = 'c' . floor($crop[0] * 100) . 'x' . floor($crop[1] * 100);
		}
	}else{
		$crop = false;
	}

	// change the ext if set to transparent
	if ($args['transparent']){
		$ext = 'png';
	}

	$dir 	   = $args['dir'] ? $args['dir'] : $pathinfo['dirname'];
	$dir 	   = parse_path($dir, null, true);
	$cachedata = [
		'dir'         => $pathinfo['dirname'],
		'name'        => $pathinfo['filename'],
		'ext'         => $ext,
		'width'       => $width,
		'height'      => $height,
		'size'        => $is_resized ? "_{$width}x{$height}" : '',
		'crop'        => $crop_key,
		'color'       => $code_color,
		'transparent' => $code_transparent,
		'edit'        => $code_edit,
		'quality'     => round($args['quality'] * 100),
	];

	$filename  = string_replace($filename, $cachedata);
	$cachepath = _filters('image_edit-cachepath', $dir.$filename, [
		'filename' => $filename,
		'dir'      => $dir,
		'data'     => $cachedata
	]);
	
	if (!is_file($cachepath) || !$args['cache']){
		if ($path){
			$fileinfo = getimagesize($path);
			$webp 	  = _config('webp');	// webp function imagecreatefromwebp() breaks if the webp is an animated webp, there's no way to catch the error
			$webp	  = $webp ? 'image/webp' : 'image/webp:disabled';

			try{
				switch ($fileinfo['mime']){
					case 'image/jpeg': 	$source = imagecreatefromjpeg($path); break;
					case 'image/gif':  	$source = imagecreatefromgif($path); break;
					case 'image/png':  	$source = imagecreatefrompng($path); break;
					case $webp: 		$source = imagecreatefromwebp($path); break;
				}
			}catch(Exception $e){
				$source = null;
			}
		}
	}else if (!is_file($cachepath)){
		$cachepath = null;
	}
	
	if ($cachepath !== $path && $is_delete && is_file($cachepath)){
		unlink($cachepath);
		return null;
	}

	if ($source){
		$source_width  = imagesx($source);
		$source_height = imagesy($source);
	
		// callback ------------------------------------------------------------
		if (is_callback($args['pre_callback'])){
			$response = call_user_func_array($args['pre_callback'], array($source, $source_width, $source_height, $ext, $path));
			if ($response){
				$source_width  = imagesx($response);
				$source_height = imagesy($response);
				$source        = $response;
			}
		}

		// resize --------------------------------------------------------------
		$ratio_w = $width && $source_width > $width ? $width / $source_width  : 1;
		$ratio_h = $height && $source_height > $height ? $height / $source_height : 1;
		$x 		 = 0;
		$y 		 = 0;
		$w 		 = 0;
		$h 		 = 0;
		$image_w = 0;
		$image_h = 0;

		if ($crop){
			$r 		 = max($ratio_w, $ratio_h);
			$w 		 = $r * $source_width;
			$h 		 = $r * $source_height;			
			$x 		 = ($width - $w) * $crop[0];
			$y 		 = ($height - $h) * $crop[1];
			$image_w = $width;
			$image_h = $height;

			// TODO review this code to make the crop position be in the middle of the container
		}else{
			switch(strtolower($args['fit'])){
				case 'contain':
					$ratio 	= min($ratio_w, $ratio_h);
					$w 		= floor($ratio * $source_width);
					$h		= floor($ratio * $source_height);
					break;
				case 'cover':
					$ratio 	= max($ratio_w, $ratio_h);
					$w 		= floor($ratio * $source_width);
					$h		= floor($ratio * $source_height);
					break;
				case 'width':
					$w 	= floor($ratio_w * $source_width);
					$h	= floor($ratio_w * $source_height);
					break;
				case 'height':
					$w 	= floor($ratio_h * $source_width);
					$h	= floor($ratio_h * $source_height);
					break;
			}

			$image_w = $w;
			$image_h = $h;
		}
		
		$image_w = (int)$image_w;
		$image_h = (int)$image_h;

		if ($image_w < 1) $image_w = 1;
		if ($image_h < 1) $image_h = 1;

		$image = imagecreatetruecolor($image_w, $image_h);

		if ($image){
			$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);

			// make sure they are without decimals
			$x = (int)$x;
			$y = (int)$y;
			$w = (int)$w;
			$h = (int)$h;

			// resize the image
			imagefill($image, 0, 0, $transparent);
			imagesavealpha($image, true);
			imagecopyresampled($image, $source, $x, $y, 0, 0, $w, $h, $source_width, $source_height);

			// TODO resize the sizes if the image is smaller than what is requested

			// other effect: https://aishack.in/tutorials/image-convolution-examples/
			// https://stackoverflow.com/questions/7245710/php-gd-better-gaussian-blur


			// callback ------------------------------------------------------------
			if (is_callback($args['callback'])){
				$response = call_user_func_array($args['callback'], array($image, $source_width, $source_height, $ext, $path));
				if ($response){
					$source_width  = imagesx($response);
					$source_height = imagesy($response);
					$image         = $response;
				}
			}

			// transparent & colorize ----------------------------------------------
			if ($args['transparent'] || $args['colorize']){
				// imagefilter($image, IMG_FILTER_GRAYSCALE);
				// imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
				// imagefilter($image, IMG_FILTER_CONTRAST, -50);

				$transparent       = is_string($args['transparent']) ? $args['transparent'] : '#222';
				$transparent       = to_color($transparent, true);
				$transparent_total = (255 - $transparent[0]) + (255 - $transparent[1]) + (255 - $transparent[2]);
				$colorize          = $args['colorize'] ? (is_string($args['colorize']) ? $args['colorize'] : 'black') : false;
				$colorize          = $colorize ? to_color($colorize, true) : $colorize;
				$inverse           = $args['inverse'];

				$edit_image = imagecreatetruecolor($w, $h);
				$color      = imagecolorallocatealpha($image, 0, 0, 0, 127); // fill the box with transparen color
				imagefill($edit_image, 0, 0, $color);
				imagesavealpha($edit_image, true);

				for ($x=$w; $x--;){
					for ($y=$h; $y--;){
						$index          = imagecolorat($image, $x, $y);
						$color          = imagecolorsforindex($image, $index);
						$red            = $color['red'];
						$green          = $color['green'];
						$blue           = $color['blue'];
						$alpha          = $color['alpha'];
						$is_transparent = $red >= $transparent[0] && $green >= $transparent[1] && $blue >= $transparent[2];

						// TODO add options of multiple "opacity/transparency" by type of image
						
						if ($is_transparent){
							$ratio = $transparent_total ? (($red - $transparent[0]) + ($green - $transparent[1]) + ($blue - $transparent[2])) / $transparent_total : 1;
							$ratio = $ratio > .5 ? $ratio : 0;
							$alpha = ceil(127 * $ratio);
						}
						
						if ($inverse){
							$alpha = 127 - $alpha;
						}
						
						if ($colorize){
							$red   = $colorize[0];
							$blue  = $colorize[1];
							$green = $colorize[2];
						}

						$color = imagecolorallocatealpha($edit_image, $red, $green, $blue, $alpha);
						imagesetpixel($edit_image, $x, $y, $color);
					}
				}

				if ($colorize){
					// imagefilter($edit_image, IMG_FILTER_COLORIZE, $colorize[0], $colorize[1], $colorize[2]);
				}

				$image = $edit_image;
			}

			// save
			$quality = is_numeric($args['quality']) ? $args['quality'] : 1;
			$quality = $quality > 1 ? 1 : ($quality < 0 ? 0 : $quality);
			
			// if ($args['ext'] === 'webp' && !function_exists('imagewebp')){
			// 	$args['ext'] = $ext;
			// }

			// $ext = $args['ext'] ? $args['ext'] : $ext;
			set_directory($cachepath);

			switch($ext){
				case "jpg":
				case "jpeg":
					imagejpeg($image, $cachepath, $quality * 100);
					break;
				case "gif":
					imagegif($image, $cachepath);
					break;
				case "png":
					$quality = 9 - round(9 * $quality);
					imagepng($image, $cachepath, $quality);
					break;
				case "webp":
					imagewebp($image, $cachepath, $quality * 100);
					break;
			}

			imagedestroy($image);
			imagedestroy($source);
			
			$cachepath = realpath($cachepath);
		}
	}

	// TODO add _warn() if the image is too big to resize
	
	$is_edited = $crop || $args['callback'] || $args['transparent'] || $args['colorize'];
	if (!$is_resource && (!$cachepath || (!$is_edited && $args['lowersize'] && is_file($cachepath) && filesize($cachepath) > filesize($path)))){
		$diff = filesize($cachepath) - filesize($path);
		$cachepath = $diff > 20000 ? $path : $cachepath; // @fix only update when the diffrence is at least 20kb
	}
	

	$url = path_to_url($cachepath);

	if (return_object($args['return'])){
		return array(
			'src'  => $url,
			'path' => $cachepath,
		);
	}else if ($args['return'] === 'src' || $args['return'] === 'url'){
		return $url;
	}else{
		return $cachepath;
	}
}

// TODO
function the_image_lazy_script (){

}

// video/audio ---------------------------------------------------------------------------------------------------------

// embed ---------------------------------------------------------------------------------------------------------------

// Cast ----------------------------------------------------------------------------------------------------------------
// $aspect_ratio = number = then it's the Y ratio
// $aspect_ratio = array = then it's [width, height]
function to_media_aspect_ratio ($aspect_ratio, $width=0, $height=0){
	// aspect ratio
	$ratio_x = 0;
	$ratio_y = 0;

	if (is_numeric($aspect_ratio)){
		$ratio_y = (float)$aspect_ratio;
		$ratio_x = 1 / $ratio_y;
	}else if (is_array($aspect_ratio)){
		$width   = $aspect_ratio[0];
		$height  = $aspect_ratio[1];
		$ratio_x = $width ? ($height / $width) : 0;
		$ratio_y = $height ? ($width / $height) : 0;
	}else if ($width && $height){
		$ratio_x = ($height / $width);
		$ratio_y = ($width / $height);	
	}

	$ratio_cover   = max($ratio_x, $ratio_y);
	$ratio_contain = min($ratio_x, $ratio_y);
	$percent       = $ratio_x ? ($ratio_x * 100 . '%'): 0;
	$padding       = "var(--media-aspect-ratio, {$percent})";

	// make sure the width/height exists for the placeholder
	$width       = $width ? $width : round($ratio_x * 100);
	$height      = $height ? $height : round($ratio_y * 100);
	$placeholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='".$width."px' height='".$height."px'%3E%3C/svg%3E";

	// orientation
	$orientation = null;
	if ($width > $height){
		$orientation = 'landscape';
	}else if ($width < $height){
		$orientation = 'portrait';
	}else if ($width && $height){
		$orientation = 'square';
	}

	return array(
		'x'               => $ratio_x,
		'y'               => $ratio_y,
		'cover'           => $ratio_cover,
		'contain'         => $ratio_contain,
		'percent'         => $percent,
		'padding'         => $padding,
		'placeholder_src' => $placeholder,
		'orientation'	  => $orientation,
		'style'			  => [
			'--aspect-ratio-x'             => $ratio_x,
			'--aspect-ratio-y'             => $ratio_y,
			'--aspect-ratio-cover'         => $ratio_cover,
			'--aspect-ratio-contain'       => $ratio_contain,
			'--aspect-ratio-contain-width' => $ratio_y < $ratio_x ? $ratio_y : 1,
			'--aspect-ratio-padding'       => $percent,
		]
	);
}
	