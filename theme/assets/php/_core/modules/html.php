<?php 
function _to_html_comment ($text, $single=false){
    if ($single){
        echo "\n\n\t<!-- " . str_pad(" {$text} ", 100, '=', STR_PAD_BOTH) . "-->\n\n";
    }else{
        echo "\n\n\t<!-- ==================================================================================================== -->".
            "\n\t<!-- ".str_pad($text, 100, ' ', STR_PAD_BOTH)." -->" .
            "\n\t<!-- ==================================================================================================== -->\n\n";
    }
}

// Tracking --------------------------------------------------------------------
function is_tracking (){
    return _config('enable_tracking', is_live());
}

function the_google_analytics ($code='', $args=null){
	$args = _args($args, array(
		'enable'    => is_tracking(),
		'pageview'  => true,
		'anonymize' => false,
	), 'enable');

	if ($code && !$args['enable']){
        _to_html_comment("Google Analytics Code(s): {$code}");
	}else if ($code){
        _to_html_comment("Global site tag (gtag.js) - Google Analytics");
		?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $code; ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			<?php if (!$args['pageview']): ?>
			gtag('config', '<?php echo $code; ?>', { 'send_page_view':false });
			<?php elseif ($args['anonymize']): ?>
			gtag('config', '<?php echo $code; ?>', { 'anonymize_ip': true });
			<?php else: ?>
			gtag('config', '<?php echo $code; ?>');
			<?php endif; ?>
		</script>
		<?php
        _to_html_comment("End Global site tag (gtag.js)");
	}
}

function the_google_tag_manager ($code, $args=null){
    $args = to_args($args, array(
		'enable'   => is_tracking(),
		'data'     => 'dataLayer',  // use different name when using multiple tags on one page
		'noscript' => false,
	), 'enable');

    if ($code && !$args['enable']){
        _to_html_comment("Google Tag Manager Code(s): {$code}");
    }else if ($code && !$args['noscript']){
        _to_html_comment("Google Tag Manager");
        ?>
        <script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='<?php echo $args['data']; ?>'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','<?php echo $args['data']; ?>','<?php echo $code; ?>');
        </script>
        <?php 
        _to_html_comment("End Google Tag Manager");
    }else if ($code){
        ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $code; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php 
    }    
}

function the_meta_pixel ($code='', $args=null){
	$args = to_args($args, array(
		'enable'   => is_tracking(),
		// 'pageview'  => true,
		// 'anonymize' => false,
	), 'enable');
	
	if ($code && !$args['enable']){
        _to_html_comment("Meta Pixel Code(s): {$code}");
	}else if ($code){
        _to_html_comment("Meta Pixel Code");
		?>
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo $code; ?>');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=<?php echo $code; ?>&ev=PageView&noscript=1"
        /></noscript>
        <?php
        _to_html_comment("End Meta Pixel Code");
	}
}

function the_tracking_code (){
    the_head_tracking_html([
		'google_analytics'        => 'option:theme_google_analytics',
		'google_tag_manager'      => 'option:theme_google_tag_manager',
		'meta_pixel'              => 'option:theme_meta_pixel',
		'additional_scripts'      => 'option:theme_scripts',
		'page_additional_scripts' => 'page:page_additional_scripts'
	]);
}

// Favicons --------------------------------------------------------------------
function the_favicons ($args=''){
    // generator: https://favicomatic.com/

	$args = to_args($args, array(
		'dir'     => '@assets/favicons/',
		'name'    => '',
		'color'   => '',
		'version' => '',
	), 'dir');

    $favicons = array(
        'tag=link&href=favicon.ico&rel=shortcut icon&type=image/ico',

        'tag=link&sizes=16x16&href=favicon-16x16.png&rel=icon&type=image/png',
        'tag=link&sizes=32x32&href=favicon-32x32.png&rel=icon&type=image/png',
        'tag=link&sizes=96x96&href=favicon-96x96.png&rel=icon&type=image/png',
        'tag=link&sizes=128x128&href=favicon-128.png&rel=icon&type=image/png',
        'tag=link&sizes=196x196&href=favicon-196x196.png&rel=icon&type=image/png',

        'tag=link&sizes=57x57&href=apple-touch-icon-57x57.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=60x60&href=apple-touch-icon-60x60.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=72x72&href=apple-touch-icon-72x72.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=76x76&href=apple-touch-icon-76x76.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=114x114&href=apple-touch-icon-114x114.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=120x120&href=apple-touch-icon-120x120.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=144x144&href=apple-touch-icon-144x144.png&rel=apple-touch-icon-precomposed',
        'tag=link&sizes=152x152&href=apple-touch-icon-152x152.png&rel=apple-touch-icon-precomposed',

        'tag=meta&href=mstile-70x70.png&name=msapplication-square70x70logo',
        'tag=meta&href=mstile-144x144.png&name=msapplication-TileImage',
        'tag=meta&href=mstile-150x150.png&name=msapplication-square150x150logo',
        'tag=meta&href=mstile-310x150.png&name=msapplication-wide310x150logo',
        'tag=meta&href=mstile-310x310.png&name=msapplication-square310x310logo',
    );

    $items = [];
    foreach ($favicons as $favicon){
        $favicon = string_decode($favicon, 'query');
        $path    = $args['dir'] . get_value($favicon, 'href,content');
        $url     = to_filepath($path, 'return=url&version=' . $args['version']);

        if ($url){
            if ($favicon['tag'] === 'meta'){
                $favicon['content'] = $url;                
            }else{
                $favicon['href'] = $url;
            }

            $items[] = to_html($favicon);
        }
    }

    if ($args['name']){
        $items[] = '<meta name="application-name" content="' . $args['name'] . '"/>';
    }
    if ($args['color']){
        $items[] = '<meta name="msapplication-TileColor" content="' . $args['color'] . '"/>';
    }

    $items = string_tab($items, 1);
    
    echo $items;
}

// Share -----------------------------------------------------------------------
function the_opengraph ($args=''){
	$args = to_args($args, array(
		'url'             => get_url(),
		'type'			  => 'website',
		'title'           => '',
		'description'     => '',
		'image'           => null,
		'image_facebook'  => null,
		'image_twitter'   => null,
		'facebook_app_id' => '',
		'twitter_card'    => 'summary_large_image',
		'twitter_handle'  => '',
	));
	$args = _filters('the_opengraph-args', $args);

    $title = strip_tags($args['title']);
    $title = attr($title);
    $desc  = strip_tags($args['description']);
    $desc  = attr($desc);
    
	$image 			= to_image($args['image'], true);
    $image_facebook = to_image($args['image_facebook'], true);
	$image_facebook = $image_facebook ? $image_facebook : $image;
	$image_twitter  = to_image($args['image_twitter'], true);
	$image_twitter  = $image_twitter ? $image_twitter : $image;
    $metas = array();

    if ($title){
        $metas[] = '<meta property="og:title" content="'.$title.'">';
        $metas[] = '<meta name="twitter:title" content="'.$title.'">';
    }
    if ($desc){
        $metas[] = '<meta name="description" content="'.$desc.'">';
        $metas[] = '<meta name="twitter:description" content="'.$desc.'">';
        $metas[] = '<meta property="og:description" content="'.$desc.'">';
    }
    if ($args['url']){
        $metas[] = '<meta property="og:url" content="'.attr($args['url']).'">';
    }
    if ($args['type']){
        $metas[] = '<meta property="og:type" content="'.attr($args['type']).'">';
    }
    if ($args['facebook_app_id']){
        $metas[] = '<meta property="fb:app_id" content="'.attr($args['facebook_app_id']).'">';
    }
    if ($image_facebook){
        $metas[] = '<meta property="og:image" content="'.$image_facebook['url'].'">';
        $metas[] = '<meta property="og:image:type" content="'.$image_facebook['type'].'">';
        $metas[] = '<meta property="og:image:width" content="'.$image_facebook['width'].'">';
        $metas[] = '<meta property="og:image:height" content="'.$image_facebook['height'].'">';
    }
    if ($image_twitter){
        $metas[] = '<meta name="twitter:card" content="'.$args['twitter_card'].'">';
        $metas[] = '<meta property="twitter:image" content="'.$image_twitter['url'].'">';
        $metas[] = '<meta property="twitter:image:src" content="'.$image_twitter['url'].'">';
        $metas[] = '<meta property="twitter:image:width" content="'.$image_twitter['width'].'">';
        $metas[] = '<meta property="twitter:image:height" content="'.$image_twitter['height'].'">';
    }
	
    echo NL . string_tab($metas, 1);
}

// Data/List -------------------------------------------------------------------
function _to_data_list ($items, $args=null, $callback_value=null, $callback_item=null){
    $args = _args($args, array(
        'headers'     => null,              // forced headers?
        'format'      => null,
        'url'         => null,
        'page'        => 1,
        'pages'       => 0,
        'json'        => false,
        'interactive' => false,
        'get'         => 'json',
    ));

    $keys  = null;
    $items = array_each($items, function ($item, $index) use ($args, $callback_value, $callback_item, &$keys, &$slugs){
        $item = apply($args['format'], [$item, $index]);
        if ($item === false) return;

        $type   = _get($item, '$type', '');
        $class  = _get($item, '$class', '');
        $status = _get($item, '$status', '');
        $class  = array_filter([$class]);

        $attrs           = _get($item, '$attrs', []);
        $attrs['status'] = $status;

        $attrs  = to_attributes([$attrs, ['class'=>$class]]);
        $item   = _get($item, '$item', $item);

        // get the keys from the item
        if (!$keys){
            if (is_object($item) && method_exists($item, $args['get'])){
                $keys = $item->{$args['get']};
            }else{
                $keys = $item;
            }  

            $keys = array_each($keys, function ($v, $i){ 
                if ($i[0] == '$') return;

                return [
                    '$id'    => to_slug($i, '_'),
                    '$value' => $i,
                ];
            });
        }

        $row = [];
        foreach ($keys as $slug => $key){
            //if ($key[0] == '$') continue; // skip index with $ as a prefix
            
            $v = _get($item, $key);

            if (is_array($v) || is_object($v)){
                $v = '<pre>'.to_string($v).'</pre>';
                $v = str_replace('\\/', '/', $v);
            }

            $row[$key] = _apply($callback_value, $key, $v, [
                'slug'  => $slug,
                'value' => _get($item, $key),
            ]);
        }

        return _apply($callback_item, $row, [
            'type'  => $type,
            'index' => $index,
            'item'  => $item,
            'keys'  => $keys,
            'attrs' => $attrs,
            'json'  => $args['json'] ? the_json_data($item, false) : '',
        ]);
    });

    $pagination = '';
    if ($args['url']){ //} && $args['pages'] > 1){
        // TODO need the option to "show/hide" the pagination, when there's 1 page or none
        $pagination = '
            <div class="&--pagination">
                <button name="start" unstyled>«</button>
                <button name="previous" unstyled>‹</button>

                <span class="pages">
                    <input name="page" value="'.$args['page'].'" />/<span pages>'.$args['pages'].'</span>
                </span>

                <button name="next" unstyled>›</button>
                <button name="end" unstyled>»</button>
            </div>
        ';
    }

    return array(
        'keys'       => empty($keys) ? [] : $keys,
        'items'      => $items,
        'pagination' => $pagination,
    );
}    

function the_data_table ($items, $args=null, $format_callback=null){
    // [x] add pagination
    // [ ] add sorting by columns options
    // [x] add styling of specific column
    // [x] add interactivness for hovering the COL and ROW, add blue styleing
    // [ ] add simple search/filters

    if (is_callback($args)){
        $args = array('format'=>$args);
    }

    $args = _args($args, array(
        'alias'       => 'data-table',
        'format'      => $format_callback,
        'title'       => null,
        'footer'      => '',
        'style'       => '',                // extra style
        'form'        => null,              // add a form to the caption, so things can be filtered
        'interactive' => true,              // add the JS for interactivity
        'url'         => null,
        'page'        => 1,                 // [specific page | true for auto page]
        'pages'       => 0,
        'echo'        => true,
    ));
    
    the_css($args['alias'], '
        &                   { font:12px monospace; color:black; margin:2rem 0; }
        & table             { font:inherit; color:currentColor; margin:0; border:1px solid #ccc; width:100%; min-width:max-content; }
        & tr                { border-bottom:1px solid #efefef; vertical-align:top; text-align:left; }
        & thead tr          { background:#efefef; border:1px solid #ccc; border-radius:3px 3px 0 0; position:sticky; top:0; }
        & th                { border:1px solid #ccc; padding-right:2em; }
        & td                { border:none; padding:0.5em 2em 0.5em 0.5em; max-width:500px; overflow:hidden; }
        & pre               { background:#f4f4f4; font:10px "Courier New"; max-width:100%; overflow:auto; margin:0; padding:1em; border-radius:3px; box-shadow:inset 0 0 0 1px rgba(0,0,0,0.1); }
        & caption           { text-align:left; }
        & .is-highlight     { background:#efefef; }
        & .is-selected      { background:#d7dbff; }
        &--pagination input { width:3em; text-align:center; }
        &.is-loading        { opacity:0.5; pointer-events:none; }
    ' . $args['style']);
    
    // TODO header caption would have the search/filter functionality (with AJAX posibility)
    $data = _to_data_list($items, $args, function ($i, $v, $a){
        return '<td col="'.$a['slug'].'">'.$v.'</td>';
    }, function ($row, $a){
        return '<tr'.($a['type']?' row="'.$a['type'].'"':'').$a['attrs'].'>' . implode(NL, $row) . NL . $a['json'] . '</tr>';
    });
    
    // head
    $caption = [];
    if ($form = __content($args['form'])) $caption[] = $form;
    if ($pages = $data['pagination'])     $caption[] = $pages;
    $caption = !empty($caption) ? '<caption><div class="&--caption">'.implode(NL, $caption).'</div></caption>' : '';

    $count    = count($data['keys']);
    $colgroup = '<colgroup>'.array_each($count, '<col />', 'join=').'</colgroup>';
    $thead    = '<thead><tr>'.to_string($data['keys'], '<th col="{{ $id }}">{{ $value }}</th>', '').'</tr></thead>';
    $tbody    = '<tbody>'.implode(NL, $data['items']).'</tbody>';
    $tfoot    = $args['footer'] ? '<tfoot><tr><th colspan="'.$count.'">'.$args['footer'].'</th></tr></tfoot>' : '';
    $html     = '<table>'.$caption.$colgroup.$thead.$tfoot.$tbody.'</table>';
    
    $html = to_html('div', [
        'x'     => 'data-table',
        'alias' => $args['alias'],
        'class' => '&',
        'html'  => $html,
        'data'  => $args['interactive'] ? array(
            'url'   => $args['url'],
            'page'  => $args['page'],
            'pages' => $args['pages'],
        ) : null
    ]);

    $args['interactive'] && add_script_helper('the_data_table_script');

    if ($args['echo']){
        echo $html;
    }

    return $html;
}

function the_data_table_script (){
    ?>
    <script>Web.Element(function DataTable (_){
        this.$is = false;

        var CLASSES = {
            HIGHLIGHT : 'is-highlight',
            SELECTED  : 'is-selected',
        };

        // init ----------------------------------------------------------------


        // private functions ---------------------------------------------------
        this._e = function (e){
            var el    = e.$el    = _.dom.closest(e.original.target, '[x]');
            var table = e.$table = _.dom.get('table', {'ctx':el});
            var tbody = e.$tbody = _.dom.get('tbody', {'ctx':el});
            var page  = e.$page  = _.dom.get('[name="page"]', {'ctx':el});
            var pages = e.$pages = _.dom.get('[pages]', {'ctx':el});
            var cols  = e.cols   = _.dom.get.all('colgroup col', {'ctx':el});
            var data  = e.data   = _.dom.data(el);
            return e;
        };

        this._go = function (e, action){
            e = this._e(e);

            if (!e.data.url) return;

            var page = e.data.page || 0;
            if (action === 'start'){
                page = 1;
            }else if (action === 'previous'){
                page--;
            }else if (action === 'next'){
                page++;
            }else if (action === 'end'){
                page = e.data.pages;
            }else if (action === 'search'){
                // return to the start
                page = 1;
            }else if (_.is.nbr(action)){
                page = action;
            }

            page = _.math.clamp(page, 1, e.data.pages);
            if (page == e.data.page && action !== 'search') return;

            var search = e.data.search || {};
            search.page = page;
            
            this.http(e.data.url, search, {
                'loading' : e.$el,
                'selector': 'tbody',
            }, function (html, s, a){
                e.data.page   = page;
                e.$page.value = page;
                _.dom.add(html, 'replace', e.$tbody);
                
                // see if there's another pages
                var pages = _.dom.get('[pages]', {'ctx':a.document}, function (n){ return +n.textContent; });
                if (_.is.nbr(pages)){
                    e.data.pages         = pages;
                    e.$pages.textContent = pages;
                }

                // update URL
                var url = _.toUrl.query(search);
                window.history.pushState(null, null, url);
            });
        };

        // events --------------------------------------------------------------
        this.tag_tr_onMouse = function (e){
            if (e.isStart){
                _.dom.addClass(e.target, CLASSES.HIGHLIGHT);
            }else if (e.isEnd){
                _.dom.removeClass(e.target, CLASSES.HIGHLIGHT);
            }
        };

        this.tag_td_onMouse = function (e){
            var e = this._e(e);
            var i = _.dom.index(e.target);
            
            if (e.isStart){
                _.dom.addClass(e.cols[i], CLASSES.HIGHLIGHT);
            }else if (e.isEnd){
                _.dom.removeClass(e.cols[i], CLASSES.HIGHLIGHT);
            }
        };

        this.tag_tr_onClick = function (e){
            var e   = this._e(e);
            var old = e.$el._row;
                        
            _.dom.addClass(e.target, CLASSES.SELECTED);
            old && _.dom.removeClass(old, CLASSES.SELECTED);

            e.$el._row = e.target === old ? null : e.target;
        };

        // pages
        this.start_onClick = function (e){
            this._go(e, 'start');
        };

        this.previous_onClick = function (e){
            this._go(e, 'previous');
        };

        this.next_onClick = function (e){
            this._go(e, 'next');
        };

        this.end_onClick = function (e){
            this._go(e, 'end');
        };

        this.onSubmit = function (e){
            e = this._e(e);
            if (!e.$el) return;

            e.prevent();
            e.data.search = _.dom.values(e.original.values);
            this._go(e, 'search');
        };

        this.page_onKeypress = function (e){
            if (e.key.value !== 'Enter') return;
            var page = _.dom.value(e.target);
            this._go(e, page);
        }
    });</script>
    <?php
}

function the_data_list ($items, $args=null, $format_callback=null){
    if (is_callback($args)){
        $args = array('format'=>$args);
    }

    $args = _args($args, array(
        'alias'       => 'data-list',
        'format'      => $format_callback,
        'key'         => null,              // key to show for items when they are closed
        'style'       => '',                // extra style
        'interactive' => true,              // add the JS for interactivity
        'echo'        => true,
    ));
    
    the_css($args['alias'], '
        &                               { font:14px monospace; color:black; margin:2rem 0; }
        & ul                            { list-style:none; }
        & li                            { position:relative; padding-left:1.5em; margin-bottom:0; }
        /* toggle ----------------------------------------------------------- */
        &--toggle                       { position:absolute; top:0; left:0; cursor:pointer; }
        &--toggle::before               { content:"▶"; }
        & li.is-open &--toggle::before  { content:"▼"; }
        
        &--title                        { display:inline-block; cursor:pointer; margin-right:.5em; vertical-align:middle; }
        &--closed                       { color:#cfcfcf; }
        & li.is-open &--closed          { display:none; }
        /* list ------------------------------------------------------------- */
        & dl                            { margin:0.5em 0 0.5em 0.25em; padding-left:1em; border-left:1px dashed #cfcfcf; }
        & dt                            { float:left; margin-right:.5em; clear:left; }
        & dd                            { color:#1A01CC; font-weight:bold; }
        & dd.is-string                  { color:#087500; font-weight:normal; }
        & dd.is-html                    { color:inherit; font-weight:normal; }
        & li:not(.is-open) dl           { display:none; }
        
        // & dl                    { display:grid; grid-template-areas:"key value"; grid-template-columns:1fr 5fr; }
    ' . $args['style']);

    
    $data = _to_data_list($items, $args, function ($i, $v, $a){
        $v    = to_value($v);
        $type = gettype($v);
        $type = $type === 'string' && $v != strip_tags($v) ? 'html' : $type;

        $v = $type === 'string' ? '"'.$v.'"' : $v;
        $v = is($v) ? $v : '&nbsp;';
        return '<dt>"'.$i.'":</dt><dd class="is-'.$type.'">'.$v.'</dd>';
    }, function ($row, $a) use ($args){
        $toggle = '<span action="toggle" class="&--toggle"></span>';
        $title  = _get($a['item'], $args['key'], null);
        $title  = $title ? '<strong action="toggle" class="&--title">'.$title.'</strong>' : '';
        $dl     = '{ <dl>' . implode(NL, $row) . NL . '</dl><span class="&--closed">...</span> }';
        $li     = '<li'.($a['type']?' type="'.$a['type'].'"':'').' class="is-open">' . $toggle . $title . $dl . $a['json'] . '</li>';
        return $li;
    });
    
    $html = '<ul>' . implode(NL, $data['items']) . '</ul>';

    $html = to_html('div', [
        'x'     => 'data-list',
        'alias' => $args['alias'],
        'class' => '&',
        'html'  => $html,
        'data'  => $args['interactive'] ? array(
            'url'   => $args['url'],
            'page'  => $args['page'],
            'pages' => $args['pages'],
        ) : null
    ]);


    $args['interactive'] && add_script_helper('the_data_list_script');

    if ($args['echo']){
        echo $html;
    }

    return $html;
}

function the_data_list_script (){
    ?>
    <script>Web.Element(function DataList (_){
        this.$is       = false;
        this.$delegate = true;

        var CLASSES = {
            HIGHLIGHT : 'is-highlight',
            SELECTED  : 'is-selected',
        };

        // private functions ---------------------------------------------------
        this._e = function (e){
            var el   = e.$el  = this.$el(e.target);
            var data = e.data = _.dom.data(el);
            return e;
        };

        this._go = function (e, action){
            e = this._e(e);

            if (!e.data.url) return;

            var page = e.data.page || 0;
            if (action === 'start'){
                page = 0;
            }else if (action === 'previous'){
                page--;
            }else if (action === 'next'){
                page++;
            }else if (action === 'end'){
                page = e.data.pages - 1;
            }

            page = _.math.clamp(page, 0, e.data.pages - 1);

            if (page == e.data.page) return;

            this.http(e.data.url, {'page':page}, {
                'loading' : e.$el,
                'selector': 'tbody',
            }, function (html){
                e.data.page       = page;
                e.$page.innerHTML = page + 1;
                _.dom.add(html, 'replace', e.$tbody);
            });
        };

        // events --------------------------------------------------------------
        this.action_toggle_onClick = function (e){
            var e  = this._e(e);
            var li = _.dom.closest(e.target, 'li');
            _.dom.toggleClass(li, 'is-open');
        };

        // pages
        this.start_onClick = function (e){
            this._go(e, 'start');
        };

        this.previous_onClick = function (e){
            this._go(e, 'previous');
        };

        this.next_onClick = function (e){
            this._go(e, 'next');
        };

        this.end_onClick = function (e){
            this._go(e, 'end');
        };
    });</script>
    <?php
}

function the_data_timeline ($items, $args=null){
    $args = _args($args, array(

    ));
}

// Elements --------------------------------------------------------------------
function the_dialog ($args=null, $content=null){
    $args = _args($args, [
        'alias'   => 'item-dialog',
        'trigger' => '',
        'load'    => false,           // [true, iframe] load from a URL
        'api_url' => false,
        'open'    => false,
        'actions' => null,
        'echo'    => true,
    ]);

    the_css($args['alias'], '
        &               { position:fixed; bottom:0; min-width:500px; width:50vw; height:80vh; padding:0; flex-direction:column; z-index:999; }
        &[open]         { display:flex; }
        &--header       { flex:0 0 auto; padding:.5em; background:black; color:white; width:auto; }
        &--close        { color:inherit; }
        &--main         { flex:1 1 auto; overflow:hidden; }
        &--body         { padding:1em; overflow:auto; height:100%; }
        &--iframe       { width:100%; height:100%; border:none; background:transparent; }
    ');

    if ($args['load'] === 'iframe'){
        $args['load'] = ['iframe'=>true];
    }
    
    $body = '';
    if (_get($args['load'], 'iframe') === true){
        $body = '<iframe x-el="iframe" src="" class="&--iframe"></iframe>';
    }else if ($args['load']){
        $body = '<div x-el="body" class="&--body"></div>';
    }else{
        $body = __content($content);
    }

    $header = to_html('header', [
        'class' => '&--header',
        'html'  => [
            '<button type="button" name="close" unstyled class="&--close">✕</button>'
        ]
    ]);

    $main = to_html('div', [
        'class' => '&--main',
        'html'  => $body,
    ]);

    $html = to_html('dialog', [
        'x'     => 'dialog',
        'alias' => $args['alias'],
        'class' => '&',
        'open'  => $args['open'],
        'html'  => [$header, $main],
        'data'  => [
            'load'    => $args['load'],
            'trigger' => $args['trigger'],
            'api_url' => $args['api_url'],
            'actions' => $args['actions'],
        ]
    ]);

    add_script_helper('the_dialog__js');

    return e($html, $args['echo']);
}

function send_dialog_message ($msg, $data=null, $close=false){
    $data = $data ? json_encode($data) : null;
    $msg  = json_encode([
        'action' => $msg,
        'data'   => $data,
    ]);

    ?>
    <script>window.parent.postMessage(<?php echo $msg; ?>, '*');</script>
    <?php 

    $close && close_dialog();
}

function close_dialog (){
    ?>
    <script>window.parent.postMessage({'action':'close'}, '*');</script>
    <?php 
}

function the_dialog__js (){ ?>
    <script>Web.Element(function Dialog(_){
        this.data = {
            'actions' : {},
        };

        this._trigger = null;
        this.actions = [];

        // init ----------------------------------------------------------------
        this.init = function (data){
            if (!data.trigger){
                // TODO error
            }

            this.on('body', 'tap', data.trigger, trigger_onTap);
            
            this.actions = data.actions || {};
        };

        // events --------------------------------------------------------------
        function trigger_onTap (e){
            e.prevent();

            // save the trigger element
            this._trigger = e.target;

            var load = this.data.load;
            if (load){
                var url = e.target.getAttribute('href');
                if (load.api){
                    url = load.api + '?url=' + encodeURIComponent(url);
                }

                if (load.iframe){
                    this.els.iframe.setAttribute('src', url);
                    this.open();
                }else{
                    this.http(url, null, {
                        type    : 'text',
                        selector: load.selector
                    }, function (v, s){
                        if (!s){
                            // TODO show error message if the content is empty
                        }
                        
                        this.els.body.innerHTML = v;
                        this.open();
                    });
                }
            }
        };

        this.close_onClick = function (){
            this.close();            
        };

        // receive a message from the iframe
        this.window_onMessage = function (e){
            if (!this.els.iframe || e.original.source !== this.els.iframe.contentWindow) return;

            var msg    = e.original.data || '';
            var action = msg.action;
            var data   = msg.data ? JSON.parse(msg.data || {}) : null;
            
            if (action in this.actions){
                var name     = this.actions[action];
                var callback = window[name];
                this.apply(callback, [{
                    'trigger': this._trigger,
                    'data'   : data,
                }, _]);
            }else if (action === 'close'){
                this.close();
            }
        };

        // methods -------------------------------------------------------------
        this.open = function (){
            this.el.setAttribute('open', '');
        };

        this.close = function (){
            if (this.els.iframe){
                this.els.iframe.setAttribute('src', '');
            }

            this.el.removeAttribute('open');
        };
    });</script>
<?php }

function the_accordion (){
    // TODO use "details" and "summary" tags
    // https://css-tricks.com/how-to-animate-the-details-element/
}

function the_cookie_bar ($content, $args=null, $code=''){
    $args = _args($args, [
        'alias'  => 'item-cookie-bar',
        'key'    => 'cookie_bar',
        'expire' => '30days',
        'accept' => __t('Accept'),
        'reject' => __t('Reject'),
        'close'  => false,
        'inject' => true,
        'code'   => true,            // should the code always added OR only on accept
        // TODO add options
    ]);

    // the code will be added if the user has accepted the cookies
    if (is_array($content)){
        $code    = $content[1];
        $content = $content[0];
    }

    $code  = $code ? __content($code) : '';
    $value = cookie_bar_answer($args['key']);

    // if the cookie bar window has been interacted with, we replace it with an empty <script> tag
    $html  = '<script x="cookie-bar" data-value="'.to_string($value).'"></script>';
    $alias = $args['alias']; //'item-cookie-bar';

    
    if ($value === null && $content){
        $close = $args['close'] === true ? '✕' : $args['close'];
        if ($close){
            $close = '<button unstyled type="button" name="close" class="&--close">'.$close.'</button>';
        }else{
            $close = '';
        }


        the_css($alias, '
            &  { position:fixed; bottom:0; left:0; width:100%; background:white; color:black; padding:1em; z-index:10; }
        ');

        $html = to_html('div', [
            'x'     => 'cookie-bar',
            'alias' => $alias,
            'class' => '&',
            'data'  => [
                'key'    => $args['key'],
                'expire' => $args['expire'],
            ],
            'html'  => ['
                <div class="&--main">'.$content.'</div>
                <div class="&--foot">
                    <button unstyled type="button" name="accept" class="&--yes">'.$args['accept'].'</button>
                    <button unstyled type="button" name="reject" class="&--no">'.$args['reject'].'</button>
                </div>'.$close.'
            ', $code ? '<template>'.$code.'</template>' : null],
        ]);
    }else if ($args['code'] && $value === true){
        echo $code;
    }

    echo $html;

    add_script_helper('the_cookie_bar__js');

    /*
    
    // WP listing of all the other cookies
    function list_enqueued_scripts() {
        global $wp_scripts;

        $content_url = get_site_url();
        $externals   = [];
        foreach ($wp_scripts->registered as $id => $v){
            $src = $v->src;
            
            if (
                $src === false
                || preg_match('/^(prototype|scriptaculous|gform|acf)/', $id)
                || strpos($src, '/wp-includes') !== false
                || strpos($src, '/wp-admin') !== false
            ) continue;
        
            $externals[] = [
                'id'     => $id,
                'url'    => $src,
                'assest' => $v
            ];
        }

        _js($externals);
    }
    add_action('wp_enqueue_scripts', 'list_enqueued_scripts', 100);

    */
}

function the_cookie_bar__js (){ ?>
    <script>Web.Element(function CookieBar (_){
        this.$singleton = true;

        this.$style = {
            '\&[hide]' : 'display:none;',
        };

        this.data = {
            'key'    : 'cookie_bar',
            'expire' : '30days',
            'value'  : null,
        };

        // this.init = function (){
        //     _js(this);
        // };

        // events --------------------------------------------------------------
        this.accept_onClick = function (e){
            e.prevent();
            
            // TODO deal with inline <script> that's inside the code <template>
            
            // add the code
            this.template(null, null, {'type':'after', 'target':this.el});       

            this.save(true);     
        };

        this.reject_onClick = function (e){
            e.prevent();
            this.save(false);
        };

        this.close_onClick = function (){
            this.close();
        };
        
        // methods -------------------------------------------------------------
        this.save = function (value){
            this.data.value = value;
            _.cookie(this.data.key, value, this.data.expire);
            this.close();
            // this.transition(this.$el, {
            //     'props'    : 'opacity',
            //     'duration' : 200,
            //     'calculate': function (){
            //         this.state('hide', true);
            //         // this.data.value = value;
            //     }
            // });
        };

        this.value = function (){
            return this.data.value;
        };

        this.close = function (){
            this.transition(this.$el, {
                'props'    : 'opacity',
                'duration' : 200,
                'calculate': function (){
                    this.state('hide', true);
                }
            });
        };
    });</script>
<?php }

function cookie_bar_answer ($key='cookie_bar'){
    return get_cookie($key);
}

// Other -----------------------------------------------------------------------
function the_menu ($items, $args=array(), $depth=0){
    $args = _args($args, array(
        'url'      => get_url(),
        // 'wrap'     => 'nav',
        'nav'          => false,   // could be nav
        'wrap_submenu' => false,
		// menu
        'tag'      => 'menu',
		'alias'    => 'menu',
		'attrs'    => array(),
        'class'    => '',
        // item
		'tag_item' => 'li',
        'label_url'=> null,       // [null, #] 
		'default'  => [],
        // other
        'prepend'  => null,
        'append'   => null,
		'return'   => null,     // html, object
		'echo'     => true,
	), 'alias', 'the_menu-args');

    // TODO add order of items maybe, for @route items?
    
    // default wrap tags
    if ($args['nav'] === true){
        $args['nav'] = 'nav';
    }

    $current_url   = to_clean_url($args['url']);
    $has_current   = false;
    $has_ancestor  = false;
    $item_defaults = _args($args['default']);
    $html          = array();

    $items = _filters('the_menu-items', $items, $args);
    
    if (!empty($args['prepend'])){
        $prepend = is_array($args['prepend']) ? $args['prepend'] : [$args['prepend']];
        $items   = array_merge($prepend, $items);
    }
    if (!empty($args['append'])){
        $append = is_array($args['append']) ? $args['append'] : [$args['append']];
        $items  = array_merge($items, $append);
    }
    
	foreach ($items as $key => $item){
        if ($item === false) continue;

        if (is_string($key) && strpos($key, '##') === 0){
            $label = _get($item, 'label,title,name');
            unset($item['label']);
            unset($item['title']);
            unset($item['name']);

            $item = [
                'url'      => false,
                'label'    => $label ? $label : substr($key, 2),
                'children' => $item,
            ];
        }

        $item = _args($item, array_merge([
			'tag'        => $args['tag_item'],
			'label'      => '',
			'url'        => $key,
			'target'     => null,
			'class'      => null,
            // 'style'      => null, // TODO
            'attrs'      => [],
			'match'      => null,         // regexp match to make something selected
			'active'     => true,
            'button'     => false,        // turn the link into a <button> instead
            'spacer'     => false,        // turn the link into a spacer
            'is_current' => null,
			'children'   => [],
		], $item_defaults), 'label', 'the_menu-item');

        if (!$item['active']) continue;

        // get the children first
        $children = $item['children'];
        
        if (is_callback($children)){
            $children = __content($children, [$item]);
        }

		$children = is_array($children) && count($children) ? the_menu($children, [
			'wrap_submenu' => $args['wrap_submenu'],
			'alias'        => '@--submenu',
			'echo'         => false,
			'return'       => true,
		], $depth + 1) : null;
        
        $label           = _get($item, 'label,title,name');
        $url             = $item['url'] ? $item['url'] : null;
        $url             = $url ? get_url($url) : $args['label_url'];
		$url             = _filters('the_menu-url', $url, $item, $args);

		$is_current      = $current_url === to_clean_url($url);
		$is_current_path = $url && strpos($current_url, $url) === 0;
        $is_match        = $item['match'] && is_match($url, $item['match']);
        
        // forced the "current" selection
        if (is_bool($item['is_current'])){
            $is_current      = $item['is_current'];
            $is_current_path = false;
        }

        // Match everything below the $url path
        if (!$is_match && is_truthy($item['match'])){
            $is_match = $is_current_path;
        }

		// make sure the main URL "/" isn't selected under other pages
        if ($url === '/' && $current_url !== $url){
			$is_current_path = false;
		}

		$is_parent 	 = $children && $children['is_parent'];
		$is_ancestor = $children && $children['is_ancestor'];
		
        if ($is_current){
			$has_current = true;
		}
		if ($is_parent || $is_ancestor){
			$has_ancestor = true;
		}

		$classnames = [
            ($depth ? '@--subitem' : '@--item'),
            ($children ? 'has-children' : ''),
			("is-depth-{$depth}"),
		];

		if ($is_current) 		$classnames[] = 'is-current';
		else if ($is_parent)	$classnames[] = 'is-current-parent';
		else if ($is_ancestor)	$classnames[] = 'is-current-ancestor';

		if ($is_current_path || $is_match || $is_current || $is_parent || $is_ancestor){
			$classnames[] = 'is-current-path';
		}

        $link = null;
        
        // button
        if ($item['button']){
            $link = __html('button', [
                'html'  => $label,
                'attrs' => $item['attrs']
            ]);
        }else if ($item['spacer']){
            $link         = '';
            $classnames[] = 'is-spacer';
        // link and label
        }else{
            if ($is_current){
                $item['attrs']['aria-current'] = 'page';
            }

            // make sure the label is accessible by tabs (if there's children)
            if (!$url && $children){
                $item['attrs']['tabindex'] = 0;
            }

            $link = __link($label, $url, [
                'target'=> $item['target'],
                'attrs' => $item['attrs'],
                'class' => [
                    'link',
                    ($url ? '@--link' : '@--label'), 
                    ($url === '#' ? 'is-label' : ''),
                    ($is_current || $is_match ? 'is-current-link' : ''),
                    // (!empty($item['class']) ? "{$item['class']}-link" : ''),
                ],
            ]);
        }

        if (!empty($item['class'])){
            $classnames[] = $item['class']; 
        }

        $html[] = to_html($item['tag'], [
			'class' => $classnames,
			'html'  => $link . ($children ? ' ' . $children['html'] : ''),
		]);
    }

    // TODO maybe change the ALIAS char (like in to_image)
    $html = to_html($args['tag'], [
        'class'	=> [
            $depth == 1 ? '@--submenu' : '@',
            "is-depth-{$depth}",
            $args['class'],
        ],
        'attrs' => $args['attrs'],
        'html'	=> implode(NL, $html)
    ]);	

    if (!$depth){
        // add a wrapper
        if ($args['nav']){
            $html = to_html($args['nav'], [
                'class' => '@--nav',
                'html'  => $html,
            ]);
        }
        
        $html = replace_alias($html, $args['alias'], 'prefix=@');

        if ($args['echo']){
            echo $html;
        }
    }else{
        $wrap = $args['wrap_submenu'];

        if (is_numeric($wrap) && $depth !== $wrap){
            $wrap = false;
        }
        if (is_array($wrap) && array_key_exists($depth, $wrap)){
            $wrap = $wrap[$depth];
            // TODO make the wrapper a possible function OR css selector
        }

        if ($wrap){
            $html = '<div class="@--wrap is-depth-'.$depth.'">'.$html.'</div>';

            // it's a double wrap
            if ($wrap === ':double'){
                $html = '<div class="@--outside is-depth-'.$depth.'">'.$html.'</div>';
            }
        }
    }

	if (return_object($args['return'])){
		return array(
			'is_parent'	  => $has_current,
			'is_ancestor' => $has_current || $has_ancestor,
			'html'		  => $html,
		);
	}else{
		return $html;
	}
}

function the_languages ($args=null, $langs=null){
    $langs = $langs ? $langs : (function_exists('get_langs') ? get_langs() : []);
	if (empty($langs)) return;

	$args = to_args($args, array(
        'container' => 'nav',
        'heading'   => null,
		'list'      => 'menu',
		'item'      => 'li',
		'full'      => true,            // full version of the name (or short)
        'uppercase' => false,           // turn into uppercase
        'current'   => false,           // show current lang
        'home'      => false,           // force it to the home page URL     
		'separator' => false,           // [before, center, after]
		'inactive'	=> false,           
		'alias'		=> 'languages',
        'return'    => null,            // [links, objects, html]
		'echo'      => true,
	));

    $current_code = function_exists('get_lang') ? get_lang() : null;
    
    $separator        = '';
    $separator_prefix = '';
    $separator_suffix = '';
    if (is_array($args['separator'])){
        $separator        = get_value($args['separator'], 0);
        $separator_prefix = get_value($args['separator'], 1);
        $separator_suffix = get_value($args['separator'], 2);
    }else{
        $separator = $args['separator'];
    }

    // default separator
	$separator = $separator === true ? '|' : $separator;

	$count   = 0;
	$current = null;
	$html    = array();
    $items   = [];
	foreach ($langs as $code => $lang){
		$is_current = $current_code === $code;
        $is_active  = isset($lang['active']) ? $lang['active'] : true;
        $label      = get_value($lang, 'translated_name,name,label', $code);
		$label      = $args['full'] ? $label : ucwords($code);
        $label      = $args['uppercase'] ? strtoupper($label) : $label;
		$label      = _filters('the_languages-label', $label, $is_current);

        if ($is_current){
            $current = $lang;
        }

		if (
            (!$args['current'] && $is_current) || 
			(!$args['inactive'] && !$is_active)
        ){
            continue;
        }
        
		// separator
		if ($count && $separator){
            $sep 	= $args['item'] ? to_html($args['item'], ['class'=>'&--separator', 'html'=>$separator]) : $separator;
            $sep 	= _filters('the_languages-separator', $sep, $separator);
			$html[] = $sep; 
		}
        
        $url = null;
        if ($args['home'] && function_exists('get_home_url')){
            $url = get_home_url($code);
        }else if (isset($lang['url'])){
            $url = $lang['url'];
        }else{
            $url = get_url(true, ['lang'=>$code]);
        }
            
        // object item
        $item = [
            'label'      => $label,
            'url'        => $url,
            'is_current' => $is_current,
        ];
        $items[] = $item;

        // link item
		$link = to_link($label, $url, [
            'translate' => false,
            'class'     => ['&--link', ($is_current ? 'is-current-link' : '')]
        ]);

		$item = $args['item'] ? to_html($args['item'], [
            'class' => ['&--item', ($is_current ? 'is-current' : '')],
            'html'  => $link
        ]) : $link;

        $item = _filters('the_languages-item', $item, $link, [
			'label'      => $label,
			'url'        => $url,
			'is_current' => $is_current
		]);

		$html[] = $item;
		$count++;
	}

	$html = string_tab($html, 1);

    // TODO
    // $separator_prefix = get_value($args['separator'], 1);
    // $separator_suffix = get_value($args['separator'], 2);


	if ($args['list']){
		$html = to_html($args['list'], [
			'class' => '&--list',
			'html'	=> $html,
		]);
	}

    if ($args['heading']){
        $heading = string_replace($args['heading'], [
            'current' => $current,
        ]);

        $html = to_html('div', [
			'class' => '&--heading',
			'html'	=> $heading,
		]) . $html;
    }

    if ($args['container']){
		$html = to_html($args['container'], [
			'class' => '&',
			'html'	=> $html,
		]);
	}	
	
	$html = replace_alias($html, $args['alias']);

    if (return_object($args['return'])){
        return $items;
    }else if ($args['echo']){
		echo $html;
	}

	return $html;
}

function the_content_annotated ($html, $args=null){
    $args = _args($args, [
        'counter'        => 'annotation',
        'marker'         => false,
        'format_counter' => null,
        'footnote'       => true,
        'interactive'    => true,
    ], 'counter');

    if (
        strpos($html, 'annotation-tooltip') === false 
        || !($dom = get_dom($html))
    ) return $html;

    $_footnote = function ($id, $marker, $text, $_text) use ($args){
        $key         = 'footnotes-' . $args['counter'];
        $footnotes   = _global($key, []);
        $footnotes[] = [
            'id'     => $id,
            'marker' => $marker,
            'text'   => $text,
            'full'   => $_text,
        ];
        set_global($key, $footnotes);
    };
    
    $_annotation = function ($text) use ($_footnote, $args){
        $index   = to_counter($args['counter']);
        $id      = $args['counter'] . '-' . $index;
        $counter = $index + 1;
        $marker  = _filters('the_content_annotated-marker', $counter);
        $_text   = _filters('the_content_annotated-text', $text, $marker, $counter);
    
        $tooltip = '<span id="'.$id.'" class="annotation" annotation="'.($args['interactive']?'interactive':'').'">
            <span class="&--inner" annotation-inner>
                <sup class="&--marker">'.$marker.'</sup>
                <span annotation-tooltip class="&--tooltip"><span annotation-text class="&--text">'.$_text.'</span></span>
            </span>
        </span>';

        $tooltip = replace_alias($tooltip, 'annotation');
    
        if ($args['footnote']){
            $_footnote($id, $marker, $text, $_text);
        }
    
        return $tooltip;
    };

    dom_get_nodes($dom, '.annotation-tooltip', function ($v) use ($_annotation){
        $html       = dom_get_attr($v, 'html');
        $annotation = $_annotation($html, true);
        dom_replace_node($v, $annotation);
    });

    // get only the <body> html of the $dom
    $html = dom_get_node($dom, 'body', 'html');

    the_css('annotation', '
        &                   { display:inline-block; position:relative; top:-0.4em; }
        &--inner            { display:inline-block; position:relative; cursor:default; }
        &--marker           { font:inherit; font-size:0.6em; vertical-align:baseline; top:0; padding:0.5em 0.3em; margin:-0.5em -0.3em; }
        &--tooltip          { position:absolute; top:0; left:0; pointer-events:none; width:400px; max-width:calc(100vw - (var(--annotation-padding, 0px) * 2)); z-index:100; }
        &--text             { pointer-events:all; display:none; word-break:normal; overflow-wrap:anywhere; padding:0.25em 0.5em; max-height:50vh; min-width:100px; max-width:100%; position:relative; z-index:100; border:var(--annotation-border, 1px solid black); background:var(--annotation-bg, white); color:var(--annotation-fg, black); overflow-y:auto; }
        &:hover &--text, 
        &--text.is-over     { display:inline-block; }

        // debug ---------------------------------------------------------------
        // &--text { display:inline-block; left:2em; opacity:0.5 }
    ', ['once'=>true]);
	
    // TODO add interactive to the "main" annotation also
    $args['interactive'] && add_script_helper('the_content_annotated__js');

	return $html;
}

function the_content_footnotes ($args=null, $echo=true){
    $args = _args($args, [
        'alias'   => 'footnotes',
        'counter' => 'annotation',
        'return'  => 'html',
        'echo'    => $echo,
    ], 'counter');

    $key         = 'footnotes-' . $args['counter'];
    $footnotes   = _global($key, []);

    if ($args['return'] === 'html'){
        $footnotes = array_each($footnotes, '<li class="&--item" annotation-target="#{{ id }}" footnote marker="{{ marker }}">{{ full }}</li>', [
            'alias'=> $args['alias'],
            'wrap' => '<ul class="&">{{ value }}</ul>',
        ]);
        e($footnotes, $args['echo']);
    }

    return $footnotes;
}

// [ ] onScroll = hide, make this faster
function the_content_annotated__js (){ ?>
    <script>Web.Element(true, function Annotations (_){
        var CLASSES = {
            OVER          : 'is-over',
            DISABLE_CLICKS: 'has-disabled-clicks',
        };

        var TIMEOUTS = {
            HIDE : 350,
            CLICK: 350,
        };

        this.$style = {
            '[annotation="interactive"] [annotation-tooltip]': 'visibility:hidden; position:fixed; top:0; left:0; max-width:100vw;',    // on iPhone, the tooltip shows for a brief second, this solves that issue
            '\&--container .has-disabled-clicks a'           : 'pointer-events:none;',
        };

        // properties ----------------------------------------------------------
        this.tooltip = null;

        // init ----------------------------------------------------------------
        this.init = function (){
            this.$container = this.$$('<div class="\&--container"></div>').appendTo('body');
        };

        // Events --------------------------------------------------------------
        this._show = function (e){
            var data = _.cache(e.target, 'annotation');

            if (!data){
                var $target = _.$$(e.target);
                
                data         = {};
                data.inner   = $target.find('[annotation-inner]').cache('annotation', data);
                data.text    = $target.find('[annotation-text]').cache('annotation', data);
                data.tooltip = $target.find('[annotation-tooltip]').cache('annotation', data).appendTo(this.$container);

                $target.cache('annotation', data);
            }

            var $text   = data.text;
            var $tooltip= data.tooltip;
            var iBounds = data.inner.bounds().vbox;
            var padding = _.dom.toPx('var(--annotation-padding, 0)', {'element':e.target});
            var wBounds = _.dom.bounds(window);
            var screen  = _.toBounds(wBounds, {'padding':padding});

            $tooltip.style({
                'top'     : iBounds.top,
                'left'    : iBounds.left,
                'maxWidth': screen.w,
                'position': 'fixed',
            });

            $text.style({
                'transform' : '',
            }).addClass(CLASSES.OVER);

            // move the text to the right position
            var tBounds = $text.bounds().vbox;
            var x       = tBounds.left < screen.left ? -tBounds.left : (tBounds.right > screen.right ? screen.right - tBounds.right : 0);
            var y       = tBounds.top < screen.top ? -tBounds.top : (tBounds.bottom > screen.bottom ? screen.bottom - tBounds.bottom : 0);
            
            $text.style({
                'translateX' : x,
                'translateY' : y,                
            });

            this._set(data);
        };

        this._set = function (data){
            if (data === this.tooltip) return;
            
            if (this.tooltip){
                _.cancel(this.tooltip.timeout);
                _.dom.removeClass(this.tooltip.text, CLASSES.OVER);
                this.tooltip = null;
            }

            if (data){
                data.text.addClass(CLASSES.DISABLE_CLICKS);

                // use "wait()" (insead of the addClass($class, $duration) since it's buggy)
                this.wait(TIMEOUTS.CLICK, function (){ 
                    data.text.removeClass(CLASSES.DISABLE_CLICKS);
                });

                this.tooltip = data;
            }
        };

        // Events --------------------------------------------------------------
        this.attr_annotationText_onMouseenter = function (e){
            var data = _.cache(e.target, 'annotation');
            data && _.cancel(data.timeout);            
        };

        this.attr_annotationText_onMouseleave = function (e){
            var data = _.cache(e.target, 'annotation');
            data && this._set(false);
        };

        this.annotation_interactive_onMouseenter = function (e){
            this._show(e);
        };

        this.annotation_interactive_onClick = function (e){
            this._show(e);
        };

        this.annotation_interactive_onMouseleave = function (e){
            var data = _.cache(e.target, 'annotation');
            if (!data) return;

            _.cancel(data.timeout);

            data.timeout = this.wait(TIMEOUTS.HIDE, function (){ 
                this._set(false); 
            });
        };

        this.attr_footnote_onClick = function (e){
            // var selector = e.target.getAttribute('annotation-target');
            // var target   = _.dom.get(selector);
            // var isLink   = _.dom.closest(e.original.target, 'a');
            
            // if (!target || isLink) return;

            // _.dom.scrollTo(target, {
            //     'y'       : true,
            //     'offset'  : _.dom.toPx('-3em'),
            //     'duration': 300,
            // });
        };

        // Remove the open tooltips if the screen scrolls
        this.onScroll = function (e){
            if (!this.tooltip) return;
            this._set(false);
        };
    });</script>
<?php }

// Website head ----------------------------------------------------------------
// try decoding info of the site for the <head> meta info
function to_site_head_data ($data=null){
    $data = array_each($data, function ($v, $i){
        if (is_string($v) && strpos($v, ':') !== false){
            $vv      = to_array($v, '||');
            $value   = null;
            $page_id = get_the_ID();

            foreach ($vv as $v){
                list($key, $name) = explode(':', $v);
                
                // front page data
                if ($key === 'front_page' && !is_front_page()){
                    continue;
                }


                if (in_array($key, ['entry', 'class'])){
                    if (class_exists('PostClass')){
                        $entry = PostClass::get($page_id);
                        $value = $entry->{$name};
                    }else if (class_exists('Entry')){
                        $entry = Entry::get($page_id);
                        $value = $entry->{$name};
                    }
                }else if ($name === 'site_title'){
                    $value = get_bloginfo('name');
                }else if ($key === 'page' && $name === 'excerpt'){
                    $value = to_post_excerpt($page_id, ['fallback'=>false]);
                    $value = $value ? $value : null;
                }else if ($key === 'page' && ($name === 'image' || $name === 'thumb' || $name === 'thumbnail')){
                    $value = get_the_post_thumbnail_url($page_id, 'large');
                    $value = $value ? $value : null;
                }else if ($key === 'page' && $name === 'title'){
                    $value = get_the_title($page_id);
                }else if ($key === 'page'){
                    $value = _field($name, $page_id);
                }else if (in_array($key, ['option', 'options'])){
                    $value = _option($name);
                }else if (in_array($key, ['lang', 'langs'])){
                    $value = _option(true, $name);
                }else{
                    $value = _field($name, $key);
                } 

                if ($value !== null){
                    break;
                }
            }

            return is_null($value) ? false : $value;
        }

        return __content($v);
    }, ['filter'=>false]);

    return $data;
}

function the_head_tracking_html ($args=null){
    $args = _args($args, [
        'google_analytics'        => _config('google_analytics'),
        'google_tag_manager'      => _config('google_tag_manager'),
        'meta_pixel'              => _config('meta_pixel'),
        'additional_scripts'      => _config('additional_scripts'),
        'page_additional_scripts' => _config('page_additional_scripts'),
    ]); 
    $args = to_site_head_data($args);

    $code  = $args['google_analytics'] ? $args['google_analytics'] : _setting('google_analytics');
    $codes = $code ? to_array($code) : array();
    foreach ($codes as $code) the_google_analytics($code);

    $code  = $args['google_tag_manager'] ? $args['google_tag_manager'] : _setting('google_tag_manager');
    $code  = $code ? $code : _setting('google_tag_manager');
    $codes = $code ? to_array($code) : array();
    foreach ($codes as $code) the_google_tag_manager($code);
    
    $code  = $args['meta_pixel'] ? $args['meta_pixel'] : _setting('meta_pixel');
    $code  = $code ? $code : _setting('meta_pixel');
    $codes = $code ? to_array($code) : array();
    foreach ($codes as $code) the_meta_pixel($code);

    if ($args['additional_scripts'] || $args['page_additional_scripts']){
        _to_html_comment("Start additional scripts", true);

        $scripts = [];
        if ($args['additional_scripts'])        $scripts[] = string_tab($args['additional_scripts'], 1);
        if ($args['page_additional_scripts'])   $scripts[] = string_tab($args['page_additional_scripts'], 1);
        $scripts = implode(NL, $scripts);

        if (is_tracking()){
            echo $scripts;
        }else{
            $scripts = str_replace(['<!--','-->'], '', $scripts);
            echo TAB . '<!--' . NL . $scripts . NL . TAB . '-->';
        }

        _to_html_comment("End additional scripts", true);
    }    
}

add_action('wp_head', function (){
    $data = array(
        // 'google_analytics'        => _config('google_analytics'),
        // 'google_tag_manager'      => _config('google_tag_manager'),
        // 'meta_pixel'              => _config('meta_pixel'),
        // 'additional_scripts'      => _config('additional_scripts'),
        // 'page_additional_scripts' => _config('page_additional_scripts'),
        'share_title'             => _config('share_title'),
        'share_description'       => _config('share_description'),      
        'share_message'           => _config('share_message'),          // alternative to "share_description"
        'share_image'             => _config('share_image'),
        'share_image_facebook'    => _config('share_image_facebook'),
        'share_image_twitter'     => _config('share_image_twitter'),
    );
    $data = _filters('wp_head_share-data', $data);

    // TODO Add custom share for specific page, so share_description could have page_share_description, share_image, then page_share_image, .... / change "share_current_page"
    
    // alternative version
    if (isset($data['share_message'])){
        $data['share_description'] = $data['share_message'];
    }

    $data = to_site_head_data($data);
    
    // TODO do not grow the image bigger if it's not 1600x900
    $image = array(
        'return' => 'path',
        // 'focus'  => true,
        // 'size'   => array(
        //     // 'width'  => _config('share_image_width', 1600),
        //     // 'height' => _config('share_image_height', 900),
        //     // 'crop'   => ':focus',
        //     // 'focus'  => true
        // )
    );

    // TODO Have the image be the featured_image, if not set go to the theme share image
    
    // TODO add more wayt to set title and description (using the_excerpt, or the page content, image_id ...)
    if (_config('share_current_page', true)){
        $data['share_title']       = $data['share_title'] ? $data['share_title'] : true;
        $data['share_description'] = $data['share_description'] ? $data['share_description'] : true;
    }

    if ($data['share_title'] === true){
        $data['share_title'] = wp_title( '|', false, 'right') . get_bloginfo('name');
    }
    if ($data['share_description'] === true){
        // TODO be able to get the cached "excerpt"...
        // BUG The ACF blocks are being re-generated all the time
        $data['share_description'] = to_post_excerpt(null, 'limit=160');
    }

    // Opengraph
    the_opengraph([
        'image'          => $data['share_image'] ? the_image($data['share_image'], $image, false) : null,
        'image_facebook' => $data['share_image_facebook'] ? the_image($data['share_image_facebook'], $image, false) : null,
        'image_twitter'  => $data['share_image_twitter'] ? the_image($data['share_image_twitter'], $image, false) : null,
        'title'          => $data['share_title'],
        'description'    => $data['share_description'],
    ]);

    the_head_tracking_html($data);
});

add_action('the_head-meta', function (){
    the_head_tracking_html();

    // favicons
    $favicons = _config('favicons');
    if (is_string($favicons) || is_array(($favicons))){
        the_favicons($favicons);
    }elseif ($favicons){
        the_favicons();
    }

    // Opengraph
    $title = _page('page_title', _page('title', ''));
    the_opengraph(array(
        'title'          => $title,
        'description'    => _page('description', ''),
        'image'          => _page('image'),
        'image_facebook' => _page('image_facebook'),
        'image_twitter'  => _page('image_twitter'),
    ));
});
