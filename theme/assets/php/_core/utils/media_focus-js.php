<?php function the_image_focus_js (){ ?>
	<script>Web.Element(function ImageFocus (_){
		// TODO Features for ImageFocus
		// [ ] add tabindex to the thumbnail, make the video not tab-able, on click on the thumbnail, then be able to tab on the video

		this.$is   = false;
		this.items = [];

		// private functions -------------------------------------------------------
		function _val (el, prop, fallback){
			var v = _.dom.var(el, prop); // el.style.getPropertyValue(prop);
			return v === '' ? (fallback||0) : parseFloat(v);
		};

		function _calc (imageSize, imageRatio, boxSize, boxRatio, scale){
			var iSize  = (imageSize * scale);                                           // resize the image to the container box
			var bRatio = (iSize / boxSize);                                             // new box ratio, related to the new image size
			var ratio  = (imageRatio - boxRatio) * (bRatio / (bRatio - 1)) + boxRatio;
			
			// make sure it's between 0 and 1, so it's always contained on the box
			ratio = _.math.clamp(ratio, 0, 1);

			return isNaN(ratio) ? '' : ratio * 100 + '%';
		};

		// init -------------------------------------------------------------------- 
		this.init = function (){
			this.cache();
		};

		this.cache = function (forced){
			var ctx = this;

			_.dom.get.all('[focus]', function (el){
				var type   = el.getAttribute('focus') || 'object';
				var parent = type === 'background' ? el : el.parentNode;

				var item = {
					'type'   : type,
					'node'   : el,
					'src'	 : type === 'background' ? el.style.backgroundImage : el.getAttribute('src'),
					'parent' : parent,
					'focusX' : _val(el, '--focus-x', 0.5),
					'focusY' : _val(el, '--focus-y', 0.5),
					'focusW' : _val(el, '--focus-w', 1),
					'focusH' : _val(el, '--focus-h', 1),
					'originX': _val(el, '--origin-x', 0.5),
					'originY': _val(el, '--origin-y', 0.5),
					'imageW' : _val(el, '--media-width'),
					'imageH' : _val(el, '--media-height'),
					'width'  : 0,
					'height' : 0
				};

				ctx.items.push(item);

				_.dom.attrs(el, {
					'focus' 	: '',
					'focused' 	: true,
				});
			});

			this.render(forced);
		};

		this.render = function (forced){
			this.each(this.items, function (item){
				item.width  = item.parent.clientWidth;
				item.height = item.parent.clientHeight;

				var ratio 	  = Math.max(item.width / item.imageW, item.height / item.imageH);
				var cw 		  = (item.imageW * ratio) | 0;
				var ch 		  = (item.imageH * ratio) | 0;
				var overflowX = cw > item.width;
				var overflowY = ch > item.height;

				item.x = _calc(item.imageW, item.focusX, item.width, item.originX, ratio);
				item.y = _calc(item.imageH, item.focusY, item.height, item.originY, ratio);

				_.dom.style(item.node, {
					'--box-w': item.width,
					'--box-h': item.height,
				}, forced);

				_.dom.classnames(item.node, {
					'is-overflow-x' : overflowX,
					'is-overflow-y' : overflowY,
				});

				if (item.type === 'background'){
					_.dom.style(item.node, {
						'background-position-x': item.x,
						'background-position-y': item.y,
					}, forced);
				}else if (item.type === 'position'){
					var w  = item.imageW * item.focusW;
					var h  = item.imageH * item.focusH;
					var s  = Math.min(item.width / w, item.height / h);  // scale contain ratio
					var ww = item.imageW * s;
					var hh = item.imageH * s;
					var x  = ww * -item.focusX;
					var y  = hh * -item.focusY;

					// need to calculate the white-space possibility

					var dx = (item.width - ww*item.focusW) * item.originX;
					var dy = (item.height - hh*item.focusH) * item.originY;
					var t  = []; 
					
					x += dx;
					y += dy;

					t.push('translateX('+x+'px)');
					t.push('translateY('+y+'px)');
					t.push('scale('+s+')');
					
					_.dom.style(item.node, {
						'width'          : item.imageW,
						'transform'      : t.join(' '),
						'transformOrigin': 'top left',
					});
				}else{
					_.dom.style(item.node, {
						'object-position': item.x + ' ' + item.y,
					}, forced);
				}
			});
		};

		this.refresh = function (){
			this.cache(true);
		};

		// events ------------------------------------------------------------------
		this.onResize = function (){
			this.render(true);
		};

		this.onLoad = function (){
			this.render();
		};
	});</script> 
<?php } 

/* function the_image_focus_js (){ ?>
	<script>Web.Element(function ImageFocus (utils){
		// TODO Features for ImageFocus
		// [ ] add tabindex to the thumbnail, make the video not tab-able, on click on the thumbnail, then be able to tab on the video

		this.$is   = false;
		this.items = [];

		// private functions -------------------------------------------------------
		function _val (el, prop, fallback){
			var v = el.style.getPropertyValue(prop);
			return v === '' ? (fallback||0) : parseFloat(v);
		};

		function _calc (imageSize, imageRatio, boxSize, boxRatio, scale){
			var iSize  = (imageSize * scale);                                           // resize the image to the container box
			var bRatio = (iSize / boxSize);                                             // new box ratio, related to the new image size
			var ratio  = (imageRatio - boxRatio) * (bRatio / (bRatio - 1)) + boxRatio;
			
			// make sure it's between 0 and 1, so it's always contained on the box
			ratio = _.math.clamp(ratio, 0, 1);

			return isNaN(ratio) ? '' : ratio * 100 + '%';
		};

		// init -------------------------------------------------------------------- 
		this.init = function (){
			this.cache();
		};

		this.cache = function (forced){
			var ctx = this;

			utils.dom.get.all('[focus]', function (el){
				var type   = el.getAttribute('focus') || 'object';
				var parent = type === 'background' ? el : el.parentNode;

				var item = {
					'type'   : type,
					'node'   : el,
					'src'	 : type === 'background' ? el.style.backgroundImage : el.getAttribute('src'),
					'parent' : parent,
					'focusX' : _val(el, '--focus-x', 0.5),
					'focusY' : _val(el, '--focus-y', 0.5),
					'focusW' : _val(el, '--focus-w', 1),
					'focusH' : _val(el, '--focus-h', 1),
					'originX': _val(el, '--origin-x', 0.5),
					'originY': _val(el, '--origin-y', 0.5),
					'imageW' : _val(el, '--media-width'),
					'imageH' : _val(el, '--media-height'),
					'width'  : 0,
					'height' : 0
				};

				ctx.items.push(item);

				utils.dom.attrs(el, {
					'focus' 	: '',
					'focused' 	: true,
				});
			});

			this.render(forced);
		};

		this.render = function (forced){
			this.each(this.items, function (item){
				item.width  = item.parent.clientWidth;
				item.height = item.parent.clientHeight;

				var w 	      = item.imageW * item.focusW;
				var h 	      = item.imageH * item.focusH;
				var cover 	  = Math.max(item.width / w, item.height / h);
				var contain   = Math.min(item.width / w, item.height / h);
				// var ratio  = Math.max(item.width / item.imageW, item.height / item.imageH);

				_js(item);
				// 'focusW' : _val(el, '--focus-w', 1),
				// 	'focusH' : _val(el, '--focus-h', 1),

				var coverW 	  = (item.imageW * cover) | 0;
				var coverH 	  = (item.imageH * cover) | 0;
				var overflowX = coverW > item.width;
				var overflowY = coverH > item.height;

				item.x = _calc(item.imageW, item.focusX, item.width, item.originX, cover);
				item.y = _calc(item.imageH, item.focusY, item.height, item.originY, cover);

				utils.dom.style(item.node, {
					'--box-w': item.width,
					'--box-h': item.height,
				}, forced);

				utils.dom.classnames(item.node, {
					'is-overflow-x' : overflowX,
					'is-overflow-y' : overflowY,
				});

				if (item.type === 'background'){
					utils.dom.style(item.node, {
						'background-position-x': item.x,
						'background-position-y': item.y,
					}, forced);
				}else if (item.type === 'position'){
					// _js(item);
				}else{
					utils.dom.style(item.node, {
						'object-position': item.x + ' ' + item.y,
					}, forced);
				}
			});
		};

		this.refresh = function (){
			this.cache(true);
		};

		// events ------------------------------------------------------------------
		this.onResize = function (){
			this.render(true);
		};

		this.onLoad = function (){
			this.render();
		};
	});</script> 
<?php } */