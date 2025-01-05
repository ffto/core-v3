<?php function to_media_player_js (){ ?>
	<script>Web.Element(function MediaPlayer (utils){
		this.$alias  = 'media-player';
		this.$style = {
			'\&--thumbnail'       						: 'cursor:pointer; transition:opacity 0.2s;',
			// fullscreen ------------------------------------------------------
			'\&[fullscreen] \&--outer'					: 'display:flex; flex-direction:column; height:100%;',
			'\&[fullscreen] \&--inner'					: 'margin-top:auto; margin-bottom:auto; height:100%;',
			'\&[fullscreen]	\&--media'					: 'height:100%;',
			// state: controls -------------------------------------------------
			'\&[status="playing"] .when-paused'			: 'display:none;',
			'\&:not([status="playing"]) .when-playing'	: 'display:none;',
			'\&[mute] .when-unmuted'					: 'display:none;',
			'\&:not([mute]) .when-muted'				: 'display:none;',
			'\&[fullscreen] .when-isnt-fullscreen'		: 'display:none;',
			'\&:not([fullscreen]) .when-is-fullscreen'	: 'display:none;',
			// state -----------------------------------------------------------
			'\&.has-no-controls'						: 'cursor:pointer;',
			'\&:not(.has-thumbnail) \&--thumbnail'		: 'opacity:0; pointer-events:none;',
		};

		// consts --------------------------------------------------------------
		var CLASSES = {
			HAS_THUMBNAIL  : 'has-thumbnail',
			HAS_NO_CONTROLS: 'has-no-controls',
			IS_LOADING 	   : 'is-loading',
		};
		var STATUS = {
			READY  : 'ready',
			PLAYING: 'playing',
			PAUSED : 'paused',
			ENDED  : 'ended',
		};
		var SOURCES = {
			YOUTUBE   : 'youtube',
			VIMEO     : 'vimeo',
			SOUNDCLOUD: 'soundcloud',
			NATIVE    : 'native',
		};

		// Proxy ---------------------------------------------------------------
		var youtube_api   = false;
		var youtube_queue = [];

		var YoutubePlayer = function (ctx, iframe, ticker){
			youtube_queue.push(this);

			// variables -------------------------------------------------------
			var self       = this;
			var player     = null;
			var currentSrc = '';
			var isMounted  = false;

			// init ------------------------------------------------------------
			this.init = function (){
				if (window.YT && window.YT.Player){
					this.mount();
				}else if (!youtube_api){
					youtube_api = true;
					utils.require('//www.youtube.com/iframe_api');
				}
			};

			// events ----------------------------------------------------------
			function onReady (e){
				player = e.target;
				// utils.dom.remove(iframe);	// remove the old iframe
				ctx.ready(true);
			};

			function onStateChange (e){
				// YT.PlayerState.PLAYING = 1
				// YT.PlayerState.PAUSED = 2
				// YT.PlayerState.ENDED = 0
				
				if (e.data == YT.PlayerState.PLAYING){	// 1
					ticker.start();
					ctx.status(STATUS.PLAYING);
				}else if (e.data == YT.PlayerState.PAUSED){
					ticker.stop();
					ctx.status(STATUS.PAUSED, 1500); // when seeking, youtube pauses the video
				}else if (e.data == YT.PlayerState.ENDED){
					ticker.stop();
					ctx.status(STATUS.ENDED);
				}
			};
			
			// methods ---------------------------------------------------------
			this.mount = function (){
				// it's been mounted already, iOS tries many times
				if (isMounted) return;
				isMounted = true;

				var url = currentSrc = iframe.getAttribute('src') || '';
				var id  = url.match(/((?:v=([^&?]+))|(?:youtu\.be\/([^&?]+)))|(?:\/embed\/([^\/&?]+))/);
				var id  = id[4] || id[3] || id[2];

				var wrap = document.createElement('div');
				utils.dom.add(wrap, 'after', iframe);
				utils.dom.remove(iframe);

				new YT.Player(wrap, {
					'videoId'	: id,			
					'playerVars': {
						'enablejsapi'    : 1,
						'showinfo'       : 0,
						'modestbranding' : 1,
						'playsinline'    : 1,
						'mute'           : ctx.data.autoplay || ctx.data.mute ? 1 : 0,
						'autoplay'       : ctx.data.autoplay ? 1 : 0,
						'controls'       : ctx.data.controls ? 1 : 0,
						'iv_load_policy' : 3,
						'origin'         : location.origin,
						'widget_referrer': location.href,
						'wmode'          : "opaque",
						'allow'          : 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
					},
					'events'	: {
						'onStateChange'	: onStateChange,
						'onReady' 		: onReady,
					},
				});
			};

			this.play = function (){
				player.playVideo();
			};

			this.pause = function (){
				player.pauseVideo();
			};

			this.stop = function (){
				player.stopVideo();
                player.currentTime = 0;
			};

			this.seek = function (v){
				player.seekTo(v);
			};

			this.mute = function (v){
				if (!player) return;

				if (v === false) 	player.unMute();
		        else 				player.mute();
			};

			this.time = function (){
				return player.getCurrentTime() || 0;
			};

			this.duration = function (){
				return player.getDuration();
			};

			this.source = function (){
				return currentSrc;
			};

			this.init();
		};

		var VimeoPlayer = function (ctx, iframe, ticker){
			// variables -------------------------------------------------------
			var self      = this;
			var player    = null;
			var time      = null;
			var duration  = null;
			var isMounted = false;
			var currentSrc= '';

			// init ------------------------------------------------------------
			this.init = function (){
				utils.require('//player.vimeo.com/api/player.js', {'ctx':this, 'callback':this.mount});
			};

			// events ----------------------------------------------------------
			function onReady (){
				player.getDuration().then(function (s){
					duration = s;
					ctx.ready(true);
				});
			};
			function onPlay (){
				ticker.start();
				ctx.status(STATUS.PLAYING);
			};
			function onPause (){
				ticker.stop();
				ctx.status(STATUS.PAUSED);
			};
			function onEnd (){
				ticker.stop();
				ctx.status(STATUS.ENDED);
			};
			function onProgress (e){
				time = e.seconds;
			};

			// methods ---------------------------------------------------------
			this.mount = function (callback){
				function _done (){ ctx.apply(callback); };
				
				// it's been mounted already, iOS tries many times
				if (isMounted) return _done();
				isMounted = true;
				
				var url = currentSrc = iframe.getAttribute('src') || '';
				var id  = url.match(/(?:video\/([^\/?#]+))(?:\?h\=([^&]+))?/);  			// player.vimeo.com/video/{{ id }}?h={{ hash }}
				id  = id ? id : url.match(/(?:vimeo\.com\/([^\/?#]+)(?:\/([^\/\?#]+))?)/);	// vimeo.com/{{ id }}/{{ hash }}
				
				var hash= id[2] || '';
				id = id[1] || '';
				
				player = new Vimeo.Player(iframe, {'id':id});
				player.on('loaded', function (){ onReady.call(self); _done(); });
				player.on('play', onPlay.bind(this));
				player.on('pause', onPause.bind(this));
				player.on('ended', onEnd.bind(this));
				player.on('timeupdate', onProgress.bind(this)); // get first data onLoad (for the duration)...

				// @fix, sometimes the "loaded" event doesn't trigger
				// player.on('loaded', onReady.bind(this));
                // player.on('play', onPlay.bind(this));
                // player.on('pause', onPause.bind(this));
                // player.on('ended', onEnd.bind(this));
			};

			this.play = function (){
				player && player.play();
			};

			this.pause = function (){
				player && player.pause();
			};

			this.stop = function (){
				if (player){
					player.pause();
					player.setCurrentTime(0);
				}
			};

			this.seek = function (v){
				player.setCurrentTime(v);
			};

			this.mute = function (v){
				_warn('TODO Vimeo muting');

				if (v === false){

				}else{
					
				}
			};

			this.time = function (){
            	return time || 0;
			};

			this.duration = function (){
				return duration || 0;
			};

			this.source = function (){
				return currentSrc;
			};

			this.init();
		};

		var SoundCloudPlayer = function (ctx, iframe, ticker){
			// variables -------------------------------------------------------
			var self      = this;
			var player    = null;
			var time      = null;
			var duration  = null;
			var isMounted = false;
			var currentSrc= '';

			// init ------------------------------------------------------------
			this.init = function (){
				utils.require('//w.soundcloud.com/player/api.js', {'ctx':this, 'callback':this.mount});
			};

			// events ----------------------------------------------------------
			function onReady (e){
				player.getDuration(function (s){
					duration = s / 1000;
					ctx.ready(true);
				});
				// ctx.ready(true);
			};
			function onPlay (){
				ticker.start();
				ctx.status(STATUS.PLAYING);
			};
			function onPause (){
				ticker.stop();
				ctx.status(STATUS.PAUSED);
			};
			function onEnd (){
				ticker.stop();
				ctx.status(STATUS.ENDED);
			};
			function onProgress (e){
				time = (e.currentPosition / 1000) | 0;
			};

			// methods ---------------------------------------------------------
			this.mount = function (callback){
				// it's been mounted already, iOS tries many times
				if (isMounted) return _done();
				isMounted = true;
				
				function _done (){ 
					ctx.apply(callback); 
				};
								
				var url = currentSrc = iframe.getAttribute('src') || '';
				
				player = SC.Widget(iframe);
				player.bind(SC.Widget.Events.READY, onReady.bind(this));
				player.bind(SC.Widget.Events.PLAY, onPlay.bind(this));
				player.bind(SC.Widget.Events.PAUSE, onPause.bind(this));
				player.bind(SC.Widget.Events.FINISH, onEnd.bind(this));
				player.bind(SC.Widget.Events.PLAY_PROGRESS, onProgress.bind(this));
			};

			this.play = function (){
				player && player.play();
				
			};

			this.pause = function (){
				player && player.pause();
			};

			this.stop = function (){
				if (player){
					player.pause();
					player.seekTo(0);
				}
			};

			this.seek = function (v){
				player.seekTo(v * 1000);
			};

			this.mute = function (v){
				_warn('TODO SoundCloud');
			};

			// this.volume = function (v){
			// 	// 0 - 100
			// 	player.setVolume(v);
			// };

			this.time = function (){
            	return time || 0;
			};

			this.duration = function (){
				return duration || 0;
			};

			this.source = function (){
				return currentSrc;
			};

			this.init();
		};

		var NativePlayer = function (ctx, el, ticker){
			// variables -------------------------------------------------------
			var self      = this;
			var player    = el;
			var isReady   = false;
			var isLoading = false;

			// init ------------------------------------------------------------
			this.init = function (){
				utils.dom.on(player, 'play', onPlay);
				utils.dom.on(player, 'pause', onPause);
				utils.dom.on(player, 'seeked', onSeeked);
				utils.dom.on(player, 'ended', onEnd);
				
				if (!player.hasAttribute('controls')){
					utils.dom.on(player, 'click', onClick);
				}
				
				if (player.readyState == 0){
					utils.dom.on(player, 'progress loadeddata', {'ctx':this}, onReady);

					(player.preload == 'none') && utils.dom.on(ctx.el, 'visible mousemove mouseenter touchstart', {'once':true, 'ctx':this}, function (e){
						this.load();
					});
				}else{
					// @fix need a mini delay for things to work properly
					setTimeout(function (){
						isReady = true;
						ctx.ready(true);
					}, 10);
				}
			};

			// events ----------------------------------------------------------
			function onReady (e){
				if (isReady) return;

				if (e.type === 'loadeddata' || e.target.readyState !== 0){
					isReady   = true;
					isLoading = false;
					utils.dom.removeClass(ctx.el, CLASSES.IS_LOADING);
					ctx.ready(true);
				}
			}
			function onPlay (){
				ticker.start();
				ctx.status(STATUS.PLAYING);
			}
			function onSeeked (){
				// this.play();
			}
			function onPause (){
				ticker.stop();
				ctx.status(STATUS.PAUSED);
			}
			function onEnd (){
				ticker.stop();
				ctx.status(STATUS.ENDED);
			}
			function onClick (){
				if (ctx.data.autoplay || ctx.data.loop){
					_info('The video is set to autoplay or loop');
					return;
				}
				if (ctx._status === STATUS.PLAYING){
					ctx.pause();
				}else{
					ctx.play();
				}
			}

			// methods ---------------------------------------------------------			
			this.load = function (){
				if (isLoading || isReady) return;
				isLoading = true;
				player.load();
				utils.dom.addClass(ctx.el, CLASSES.IS_LOADING);
			};

			this.play = function (){
				player && player.play();
			};

			this.pause = function (){
				player && player.pause();
			};

			this.stop = function (){
				if (player){
					player.pause();
					player.currentTime = 0;
				}
			};

			this.seek = function (v){
				player.currentTime = v || 0;
			};

			this.mute = function (v){
				if (v === false){
					player.muted = false;
				}else{
					player.muted = true;
				}
			}

			this.time = function (){
            	return player.currentTime || 0;
			};

			this.duration = function (){
				return player.duration || 0;
			};

			this.source = function (){
				return player.currentSrc;
			};

			this.init();
		};

		// Helpers -------------------------------------------------------------
		var Fullscreen = function (ctx){
			var fullscreenEnabled;
			var fullscreenElement;
			var requestFullscreen;
			var exitFullscreen;
			var fullscreenchange;
			
			var video = ctx.el;
			var iOS   = false;

			if ('fullscreenEnabled' in document){
				fullscreenEnabled = 'fullscreenEnabled';
				fullscreenElement = 'fullscreenElement';
				requestFullscreen = 'requestFullscreen';
				exitFullscreen    = 'exitFullscreen';
				fullscreenchange  = 'fullscreenchange';
			}else if ('webkitFullscreenEnabled' in document){
				fullscreenEnabled = 'webkitFullscreenEnabled';
				fullscreenElement = 'webkitFullscreenElement';
				requestFullscreen = 'webkitRequestFullScreen';
				exitFullscreen    = 'webkitExitFullscreen';			
				fullscreenchange  = 'webkitfullscreenchange';
			}else if ('mozFullscreenEnabled' in document){
				fullscreenEnabled = 'mozFullScreenEnabled';
				fullscreenElement = 'mozFullScreenElement';
				requestFullscreen = 'mozRequestFullScreen';
				exitFullscreen    = 'mozCancelFullScreen';
				fullscreenchange  = 'mozfullscreenchange';
			}else if ('msFullscreenEnabled' in document){
				fullscreenEnabled = 'msFullscreenEnabled';
				fullscreenElement = 'msFullscreenElement';
				requestFullscreen = 'msRequestFullscreen';
				exitFullscreen    = 'msExitFullscreen';
				fullscreenchange  = 'MSFullscreenChange';
			}else{
				// probably iOS
				video 			  = utils.dom.get('video', {'ctx':ctx.el});
				iOS 			  = true;
				fullscreenEnabled = true;
				fullscreenElement = 'webkitCurrentFullScreenElement';
				requestFullscreen = 'webkitEnterFullscreen';
				exitFullscreen    = 'webkitCancelFullScreen';
				fullscreenchange  = 'webkitfullscreenchange';

				// not supported
				if (!video || !video[requestFullscreen]){
					return function (){ console.warn('Fullscreen isn\'t supported'); };
				}
			}

			if (!fullscreenEnabled) return false;

			var isFullscreen = false;
			var el           = ctx.el;
			var ticker 		 = iOS ? utils.fn.tick({'now':false, 'wait':300}, _change) : null;
			
			utils.dom.event(document, fullscreenchange, _change);

			var self = function (v){
				v = v === false ? false : true;
				if (v) 	_open();
				else	_close();
			};

			function _change (){
				// iOS event for fullscreen change doenst work, we're using a ticker instead
				if (iOS){
					if (!video.webkitDisplayingFullscreen){
						_close();
						return false;
					}
				}else{
					!document[fullscreenElement] && _close();
				}
			}

			function _open (){
				if (isFullscreen) return;
		
				video[requestFullscreen]();
				el.setAttribute('fullscreen', '');

				ticker && ticker.start();

				self.open = isFullscreen = true;

				ctx.trigger('fullscreen-open');
			}
			
			function _close (){
				if (!isFullscreen) return;

				document[fullscreenElement] && document[exitFullscreen]();
				el.removeAttribute('fullscreen');

				self.open = isFullscreen = false;

				ctx.trigger('fullscreen-close');
			}

			return self;
		};

		// Youtube -------------------------------------------------------------
		var old = window.onYouTubeIframeAPIReady;
		window.onYouTubeIframeAPIReady = function (){
			// make sure we don't skip other references to "onYouTubeIframeAPIReady"
			old && old();
			for (var i in youtube_queue) youtube_queue[i].mount();
		};

		// private functions ---------------------------------------------------
		this._active = function (){
			return !!(this.player && this._status);
		};

		// init ---------------------------------------------------------------- 
		this._muted         = false;
		this.__status       = null;
		this._isReady       = false;
		this._readyCallback = null;

		this.init = function (data){
			var iframe = this.els.iframe = utils.dom.get('iframe, video, audio', {'ctx':this.el});
			var ticker = utils.fn.tick({'ctx':this, 'wait':300, 'now':false}, ticker_onTick);

			if (this.data.provider === SOURCES.YOUTUBE){
				this.player = new YoutubePlayer(this, iframe, ticker);
			}else if (this.data.provider === SOURCES.VIMEO){
				this.player = new VimeoPlayer(this, iframe, ticker);
			}else if (this.data.provider === SOURCES.SOUNDCLOUD){
				this.player = new SoundCloudPlayer(this, iframe, ticker);
			}else if (this.data.provider === SOURCES.NATIVE){
				this.player = new NativePlayer(this, iframe, ticker);
			}else{
				// other external types of custom players
			}

			if (data.autoplay || data.mute){
				this._muted = true; // do not trigger the event on init
				this.mute(true);
			}

			// TODO add more type of interaction, dynamic...
			// TODO check if using "hidden" would simplify this code. Might actually not be necessary
			// this.els.media && (this.els.media.style.display = '');
						
			this.fullscreen = this.data.type === 'video' ? new Fullscreen(this) : function (){};

			this.thumb(true);
		};

		this.render = function (){
			if (!this.player) return;
				
			var time     = this.player.time();
			var duration = this.player.duration();
			var ratio 	 = time / duration;

			time     = utils.nbr.toTime(time);
			duration = utils.nbr.toTime(duration);

			if (this.els.time){
				this.els.time.innerHTML = time;
			}
			if (this.els.duration){
				this.els.duration.innerHTML = duration;
			}
			
			var r = ratio || 0;
			!this._isSeeking && utils.dom.style(this.el, {
				'--progress': r * 100 + '%',
				'--ratio'   : r,
			});
			this._ratio = r;

			this.renderSeek();
		};

		this.renderSeek = function (){
			var rx = '';
			var rw = '';

			if (utils.is.nbr(this._ratioSeek)){
				var r1 = this._ratio;
				var r2 = this._ratioSeek;
				rx = Math.min(r1, r2);
				rw = Math.max(r1, r2) - rx;
				rx = rx * 100 + '%';
				rw = rw * 100 + '%';
			}

			utils.dom.style(this.el, {
				'--progress-over-x'    : rx,
				'--progress-over-width': rw,
			});
		}

		this.status = function (status, delay){
			if (
				status === this._status 
				|| status === this.__status
			){
				clearTimeout(this.timeout);
				this._status = status;
				this.__status = status;
				return;	
			}

			if (status === STATUS.PLAYING){
				this.trigger('play');
			}else if (status === STATUS.PAUSED){
				this.trigger('pause');
			}else if (status === STATUS.ENDED){
				this.trigger('stop');
			}

			// extra failproof. iOS triggers the same status many times
			this.__status = status;

			if (status === STATUS.READY){
				this.els.iframe = utils.dom.get('iframe, video, audio', {'ctx':this.el});
				
				// The status changeds before the video was ready
				if (!this._status){
					this._status = status;
					this.data.thumbnail && this.thumb(true);
					this.render();
				}else{
					return;
				}
			}
					

			if (status === STATUS.PLAYING){
				// pause the currently playing video
				if (this.data.single && this.$static.playing && this.$static.playing !== this){
					this.$static.playing.pause();
				}

				this.watching(true, 1500);
				this.focus(true);

				this.$static.playing = this;

				
			}else if (status === STATUS.PAUSED || status === STATUS.ENDED){
				// @info mini fix, the pause triggers the play automatically if there's no "pause" in between
				this.wait(300, function (){
					this.watching(false);
					this.focus(false);
					this.render();
				});
			}
			
			// delay for showing the thumbnail when pausing
			clearTimeout(this.timeout);
			this.timeout = this.wait(delay || 0, function (){
				if (status === STATUS.PLAYING){
					this.thumb(false);
				}else if (status === STATUS.PAUSED || status === STATUS.ENDED){
					this.thumb(true);					
				}

				this._status = status;
				this.el.setAttribute('status', status);
			});
		};

		this.thumb = function (show, focus){
			if (show){
				utils.dom.addClass(this.el, CLASSES.HAS_THUMBNAIL);
				this.els.iframe.setAttribute('tabindex', '-1');

				if (this.els.thumbnail){
					this.data.thumbnail = true;
				}
			}else{
				this.data.thumbnail = false;
				utils.dom.removeClass(this.el, CLASSES.HAS_THUMBNAIL);
			}
		};

		this.focus = function (focus){
			// focus on the iframe 
			if (focus){
				this.els.thumbnail && this.els.thumbnail.setAttribute('tabindex', '-1');
				this.els.iframe.setAttribute('tabindex', '0');
				this.data.autofocus && this.els.iframe.focus();
			// focus on the thumbnail (if it exists)
			}else if (this.els.thumbnail && this.els.thumbnail.getAttribute('tabindex') != '0'){
				this.els.iframe.setAttribute('tabindex', '-1');
				this.els.thumbnail.setAttribute('tabindex', '0');
				this.data.autofocus && this.els.thumbnail.focus();
			}
		};

		this.ready = function (v){
			this.player.load && this.player.load();

			if (utils.is.fn(v)){
				this._readyCallback = v;
			}else if (utils.is.bool(v)){
				this._isReady = v;
				this.status(STATUS.READY);
			}

			if (this._isReady){
				utils.dom.removeClass(this.el, CLASSES.IS_LOADING);
				this.apply(this._readyCallback);
			}else{
				utils.dom.addClass(this.el, CLASSES.IS_LOADING);
			}
		};

		// used for distraction free player (eg. when [watching] is there, hide the controls)
		this.watching = function (v, timeout){
			// TODO remove "watching" when tabbing or using the keyboard, to make it more accessible

			if (utils.is.bool(v)){
				if (v === this.isWatching){
					return v;
				}

				var self = this;
				function _set (){
					if (self._status !== STATUS.PLAYING){
						v = false;
					}

					if (v)	self.el.setAttribute('watching', '');
					else	self.el.removeAttribute('watching');

					self.isWatching = !!v;
				}

				clearTimeout(this.moveTimeout);
				if (timeout){
					this.moveTimeout = this.wait(timeout, _set);
				}else{
					_set();
				}
			}
			return this.isWatching;
		};

		// events --------------------------------------------------------------
		function ticker_onTick (e){
			this.render();

			// trigger
			this.trigger('tick', {
				'time'    : this.time(),
				'duration': this.duration(),
				'progress': (this.time() / this.duration()) || 0,
			});
		};
		
		this.play_onTap 		= function (e){ this.play(); };
		this.pause_onTap 		= function (e){ this.pause(); };
		this.stop_onTap 		= function (){ this.stop(); };
		this.togglePlay_onTap 	= function (){ (this._status === STATUS.PLAYING) ? this.pause() : this.play(); };

		this.mute_onTap 		= function (){ this.mute(); };
		this.unmute_onTap 		= function (){ this.mute(false); };
		this.toggleMute_onTap 	= function (){ this.mute(!this._muted); };

		this.toggleFullscreen_onTap = function (){ 
			this.fullscreen(!this.fullscreen.open);
		};

		this.progress_onCursor = function (e){
			if (this._isDragging || e.type === 'cursor-leave' || e.type === 'cursor-click'){
				this._ratioSeek = false;
			}else{
				this._ratioSeek = e.ratioX;
			}
			this.renderSeek();			
		};

		this.progress_onClickDrag = function (e){
			utils.dom.style(this.el, {
				'--ratio'   : e.ratioX,
				'--progress': e.ratioX * 100 + '%',
			});

			if (e.isEnd){
				this.watching(true, 1500);
				this.ratio(e.ratioX);
				this._isSeeking  = false;
				this._isDragging = false;
			}else{
				this.watching(false);
				this._isSeeking  = true;
				this._ratio      = e.ratioX;
				this._isDragging = true;
			}
		};

		this.thumbnail_onTap = function (){
			this.thumb(false);
			this.play();
		};

		this.onMousemove = function (e){
			if (this._status !== STATUS.PLAYING) return;
			this.watching(false);
			this.watching(true, 1500);
		};

		// method --------------------------------------------------------------
		this.play = function (){
			// make sure it's mounted
			this.ready(function (){
				this.status(STATUS.PLAYING);
				this.player.play();
				// this.trigger('play');
			});
		};

		this.pause = function (){
			if (!this._active()) return;
			this.status(STATUS.PAUSED);
			this.player.pause();
			// this.trigger('pause');
		};

		this.stop = function (){
			if (!this._active()) return;
			this.focus(false);
			this.status(STATUS.ENDED);
			this.player.stop();
			// this.trigger('stop');
		};

		this.mute = function (v){
			v = (v === false ? false : true);

			this.player.mute(v);
			
			if (v){
				this.el.setAttribute('mute', '');				
			}else{
				this.el.removeAttribute('mute');
			}
			
			// trigger event
			if (this._muted != v){
				if (v)	this.trigger('mute');
				else 	this.trigger('unmute');
			}

			this._muted = v;
		};

		this.ratio = function (v){
			this.ready(function (){
				this.seek(v * this.duration());			
			});
		};

		this.seek = function (v){
			this.ready(function (){
				this.player.seek(v);				
				// this.play(); // automatically play when seeking
			});
		};

		this.time = function (){
			return this.player.time();
		};

		this.duration = function (){
			return this.player.duration();
		};

		this.source = function (){
			return this.player.source && this.player.source();
		};
	});</script> 
<?php }