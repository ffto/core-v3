<?php 
function to_repeater_field ($name, $fields, $args=''){
	add_script_helper('to_repeater_field_script');
	
	$args = to_field_args('repeater', $args, [
        'value' => get_form_value($name),
        'item'  => array(),
        'echo'  => false,
    ]);

	if (isset($args['values'])){
		$args['value'] = $args['values'];
	}

	foreach ($fields as $i => $field){
		$fields[$i] = _args($field, [
			'title' => '',
			'type'  => 'text'
		], 'title');
	}

	// template ----------------------------------------------------------------
	$template = '<template>' . __to_repeater_field($name, $fields, '#', $args['item']) . '</template>';	
	
	// hidden ------------------------------------------------------------------
	$empty = to_html('input', [
		'type' => 'hidden',
		'name' => $name,
	]);

	// rows --------------------------------------------------------------------
	$rows = to_array($args['value']);

	foreach ($rows as $i => $value){
		$rows[$i] = __to_repeater_field($name, $fields, $i, $args['item'], $value);
	}
	$rows = to_html('div', array(
		'class' => '@-list',
		'empty' => count($rows) == 0,
		'html'  => implode(NL, $rows),
		'x-el'  => 'list',
	));;

	// footer ------------------------------------------------------------------
	$footer = '<footer class="@-footer">' . to_html('action', [
		'class' => '@-button is-new',
		'html'  => '+',
		'name'  => 'add',
	]) . '</footer>';
	
	// html --------------------------------------------------------------------
	$html = to_html('div', array(
		'x'     => 'repeater',
		'class' => '@',
		'html'  => array($template, $empty, $rows, $footer),
	));

	return to_field($html, $args, '@--repeater');
}

function __to_repeater_field ($name, $items, $index, $attrs=array(), $values=array()){
	$fields = array();

	foreach ($items as $i => $field){
		$callback = "to_{$field['type']}_field";
		if (!is_callable($callback)) continue;

		$fields[] = _apply($callback, "{$name}[{$index}][{$i}]", array(
			'title'=> get_value($field, 'title,label'),
			'value'=> isset($values[$i]) ? $values[$i] : null,			
			'wrap' => true,
		));
	}
		
	$pre = '<div class="@-options is-before">
		<action name="up" type="button" class="@-button is-up">▲</action>
		<span class="@-counter"></span>
		<action name="down" type="button" class="@-button is-down">▼</action>
	</div>';
	
	$fields = '<div class="@-fields">' . implode(NL, $fields) . '</div>';

	$post = '<div class="@-options is-after">		
		<action name="add" class="@-button is-add">+</action>
		<action name="remove" class="@-button is-remove">✕</action>
	</div>';

	$fields = to_html('div', array(
		'class' => '@-item',
		'attrs' => $attrs,
		'html'  => [$pre, $fields, $post],
		'x-row' => '[' . $index . ']',
	));

	return $fields;
}

function to_repeater_field_script (){ ?>
	<script>Web.Element(function Repeater (utils){
		this.is     = 'repeater';
		this.$alias = 'field--repeater';

		this.$style = {
			'&'												: '--repeater-button-size:35px',
			'&-list'                                   		: 'counter-reset: x-repeater;',
			'&-item'                                   		: 'counter-increment: x-repeater; border:1px solid rgba(black, 0.1); display:flex; position: relative;',
			'&-item:not(:first-child)'                 		: 'border-top:none;',
			'&-options'                                		: 'background-color:rgba(black,0.05); display:flex; flex:0 0 auto; flex-direction:column;',
			'&-button'                                 		: 'cursor: pointer; display:inline-flex; align-items:center; justify-content:center;',
			'&-button:is(.is-add, .is-new)'            		: 'font-size:1.5em;',
			'&-button:is(.is-add, .is-new, .is-remove)'		: 'height:var(--repeater-button-size); width:var(--repeater-button-size); display:flex; align-items:center; justify-content:center; background-color:lightgray; border-radius:50%;',
			'&-footer'								   		: 'display:flex; align-items:center; justify-content:center;',
			// before options --------------------------------------------------
			'&-options.is-before'                           : 'padding:.5em; justify-content:center; width:2.5em;',
			'&-options.is-before action:not(:hover)'        : 'opacity:0.1;',
			'&-counter::before'                             : 'display:block; content:counter(x-repeater); padding:0.5em 0; text-align:center;',
			'&-item:first-child &-button.is-up' 			: 'visibility:hidden;',
			'&-item:last-child &-button.is-down'			: 'visibility:hidden;',
			// fields ---------------------------------------------------------- 
			'&-fields'                     					: 'padding:1em; flex:1 1 auto;',
			'&-fields fieldset:first-child'					: 'margin-top:0;',
			'&-fields fieldset:last-child' 					: 'margin-bottom:0;',
			// after options ---------------------------------------------------
			'&--options.is-after'        					: 'justify-content:center;',
			'&-button.is-add'            					: 'transform:translateX(50%) translateY(-50%); position:absolute; top:0; right:50%;',
			'&-button.is-remove'         					: 'position:absolute; top:50%; right:0; transform:translateX(50%) translateY(-50%);',
			'&-item:not(:hover) &-button'					: 'opacity:0;',
			// footer ----------------------------------------------------------
			'&-list:not([empty])'           				: 'margin-top:calc(var(--repeater-button-size) * 0.5 - 1px);',
			'&-list:not([empty]) ~ &-footer'				: 'transform:translateY(-50%);',
			// states ----------------------------------------------------------
			'body.has-shift-on &-button.is-remove'			: 'background:red;',
		};

		this.$once = function (){
			var self   = this;
			this.shift = false;

			// [ ] When pressing shift, we should remove the "drag-select" effect after clicking
			utils.dom.event(window, {
				'keydown': function (e){ e.key.shift && _update(true); },
				'keyup'  : function (e){ !e.key.shift && _update(false); },
			}, {'ctx':this});

			function _update (shift){
				self.shift = shift;

				if (shift){
					utils.dom.addClass('body', 'has-shift-on');
				}else{
					utils.dom.removeClass('body', 'has-shift-on');
				}
			}
		}; 

		// private --------------------------------------------------------------
		function _row (node){
			return utils.dom.get(node, {'closest':'[x-row]'});
		}

		// init ----------------------------------------------------------------
		this.init = function (){
			this.render();
		};

		this.render = function (){
			utils.dom.attrs(this.els.list, {
				'empty' : this.els.list.children.length ? false : true,
			});
			return this;
		};

		this.update = function (){
			var self = this;
			utils.dom.els(self.els.list.children, function (el, i){
				var from = utils.dom.attrs(el, 'x-row');
				var to   = '[' + i + ']';
				
				if (from === to) return;
				
				utils.dom.attrs(el, 'x-row', to);

				utils.dom.get('[name]', {'ctx':el, 'all':true}, function (field){
					var name = utils.dom.attrs(field, 'name').replace(from, to);
					utils.dom.attrs(field, 'name', name);
				});
			});
			return this;
		};

		this.transition = function (callback){
			var items = utils.dom.get.all(':scope > [x-row]', {'ctx':this.els.list}, function (el){
				var bounds = utils.dom.bounds(el);
				return {
					'el' : el,
					'x'  : bounds.vbox.x,
					'y'  : bounds.vbox.y,
				};
			});
			
			this.apply(callback);

			items.forEach(function (item){
				var bounds = utils.dom.bounds(item.el);
				var dx 	   = item.x - bounds.vbox.x;
				var dy 	   = item.y - bounds.vbox.y;

				utils.dom.style(item.el, {
					'translateX' : dx,
					'translateY' : dy,
					'transition' : '',
				});
			});

			this.wait(0, function (){
				items.forEach(function (item){
					utils.dom.style(item.el, {
						'translateX' : '',
						'translateY' : '',
						'transition' : 'transform 0.2s',
					});
				});
			});
		};

		// events ----------------------------------------------------------
		this.add_onClick = function (e){
			var row    = this.template(null, null, {'type':'frag'});
			var target = _row(e.target);
			var index  = target ? utils.dom.index(target) : 'append';
			
			this.transition(function (){
				row = utils.dom.add(row, index, this.els.list);
				this.update().scan(row).render();
			});
		};

		this.remove_onClick = function (e){
			if (!this.$static.shift && !confirm('Are you sure you want to delete this entry?')) return;
			var row = _row(e.target);

			this.transition(function (){
				utils.dom.remove(row);
				this.update().scan(true).render();
			});
		};

		this.up_onClick = function (e){
			var row   = _row(e.target);
			var index = utils.dom.index(row);

			this.transition(function (){
				utils.dom.add(row, index - 1, this.els.list);
				this.update();
			});
		};

		this.down_onClick = function (e){
			var row   = _row(e.target);
			var index = utils.dom.index(row);
			
			this.transition(function (){
				utils.dom.add(row, index + 1, this.els.list);
				this.update();
			});
		};
	});</script>
<?php  }

