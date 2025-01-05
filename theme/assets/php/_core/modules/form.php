<?php 
function set_form_values ($values=array()){
	set_global('form_values', $values);
}

function get_form_value ($name, $fallback=null){
	$values = get_global('form_values', _request());
	return _get($values, $name, $fallback);
}

function to_field_args ($type, $args='', $defaults=[], $merge=[]){
	$defaults = is_array($defaults) ? $defaults : [];
	$args     = to_args($args, array_merge($defaults, array(
		'type'		  => $type,
		'alias'		  => 'field',
		'attrs'       => array(),
		'class'       => array(),
		'style'       => null,
		'wrap'        => false,
		'title'       => null,
		'description' => null,
		'echo'        => false,
	)), 'value');
	$args = array_merge($args, $merge);
	$args = _filters("to_{$type}_field-args", $args);

	$args['attrs'] = to_attributes(array($args['attrs'], array(
		'class' => $args['class'],
		'style' => $args['style'],
	)), 'return=object');
	
	return $args;
}

function to_field ($html, $args, $alias=''){
	$args = _args($args, [
		'x'           => null,
		'alias'       => false,
		'title'       => '',
		'description' => '',
		'wrap'        => false,
		'beforeHtml'  => '',
		'afterHtml'   => '',
		'echo'        => false,
	]);

	$html = is_array($html) ? $html : array($html);
	$html = $alias ? replace_alias($html, $alias, 'prefix=@') : $html;

	if ($args['title']){
		$title = to_html('legend', [
			'class' => '@--title',
			'html'  => $args['title'],
		]);
		array_unshift($html, $title);
	}

	if ($args['description']){
		$desc = to_html('small', [
			'class' => '@--description',
			'html'  => $args['description'],
		]);
		array_push($html, $desc);
	}

	if ($args['beforeHtml']){
		array_unshift($html, $args['beforeHtml']);
	}
	if ($args['afterHtml']){
		array_push($html, $args['afterHtml']);
	}

	if ($args['wrap']){
		$wrap = selector_to_attrs($args['wrap'], array(
			'classname' => '@ is-' . $args['type'],
			'tag'       => 'fieldset',
			'x'			=> $args['x'],
		));
		$wrap['html'] = $html;
		$html = to_html($wrap);
	}else{
		$html = implode(NL, $html);
	}

	if ($args['alias']){
		$html = replace_alias($html, $args['alias'], 'prefix=@');
	}

    if ($args['echo']){
        echo $html;
    }

    return $html;
}

// for radios, checkboxes, select
function to_field_options ($name, $args=null, $options=[]){
	$args = _args($args, [
		// 'group_id'         => $name, // using this because....?
		// 'name'             => $name,
		'type'                 => null,                                 // [radio, checkbox, option],
		'title'                => null,
		'multiple'             => false,                                //add a [] at end
		'default'              => false,
		'value'                => get_form_value($name),
		'value_key'            => null,
		'label_key'            => null,                                 // maybe format too, as a string thing....
		'template'			   => null,
		'options'              => $options,
		'placeholder'          => false,
		'placeholder_position' => null,
		'empty'				   => false,
	], null, 'to_field_options-args');
	
	$type   = $args['type'];
	$name   = $args['multiple'] ? "{$name}[]" : $name;
	$values = to_array($args['value']);

	// TODO add option of [0]

	$to_html = function (&$option, $item) use ($type){
		$input = '';
		$html  = '';
		if ($type === 'option'){
			$input = [
				'tag'      => 'option',
				'label'    => $option['label'],
				'value'    => $option['value'],
				'selected' => $option['selected'],
				'html'	   => $option['label'],
			];
			$input = _filters("to_field_options-option-input", $input, $item);
			$input = to_html($input);
			$html  = $input;
		}else if ($type){
			$input = [
				'tag'     => 'input',
				'type'    => $type,
				'class'	  => '@--input',
				'name'	  => $option['name'],
				'label'   => strip_tags($option['label'])	,
				'value'   => $option['value'],
				'checked' => $option['selected'],
			];
			$input = _filters("to_field_options-{$type}-input", $input, $item);
			$input = to_html($input);

			$label = to_html([
				'tag'	=> 'span',
				'class' => '@--text text',
				'html'	=> $option['label'],
			]);

			$html = to_html([
				'tag'	=> 'label',
				'class'	=> "@--option",
				'attrs' => ['pointer'=>true],
				'html'	=> [$input, $label],
			]);
		}

		$option['input'] = $input;
		$option['html']  = $html;

		return $html;
	};

	$selections = [];
	$options    = [];
	$html		= [];
	foreach ($args['options'] as $i => $item){
		$v = $item;

        if (is_object($v) || is_array($v)){
            $i = $args['value_key'] ? _get($v, $args['value_key'], $i) : $i;
            $i = _get($v, $args['value_key'], $i);

			if ($args['template']){
				$v = string_replace($args['template'], $v);
			}else{
				$v = _get($v, $args['label_key'], $v);
			}
        }
			
        $is_selected = empty($values) ? $args['default'] === $i : in_array($i, $values);
        if ($is_selected){
            $selections[$i] = $v;
        }

		$option = [
			'name'	   => $name,
			'value'    => $i,
			'label'    => $v,
			'selected' => $is_selected,
			'input'    => '',
        ];
		
		$html[]    = $to_html($option, $item);
		$options[] = $option;
	}

	$placeholder = is_string($args['placeholder']) ? [
		'name'     => $name,
		'label'    => $args['placeholder'],
		'selected' => false,
	] : $args['placeholder']; 

    if (empty($selections) && $placeholder){
        $placeholder['selected'] = true;
        $selections[] 			 = $placeholder['label'];
    }

	if ($placeholder){
		$to_html($placeholder, null);
	}

	// Only show 
	if ($type && (count($options) || $args['empty'])){
		// TODO placeholder position

		// options
		$html = implode(NL, $html);
		$html = to_field($html, $args);
	}else{
		$html = '';
	}

	// TODO
	// 'placeholder' => [
	// 	'label' => $args['placeholder'],
	// 	'name'  => $placeholder_name,
	// 	// 'input' => '<input group="'.$args['group_id'].'" group-placeholder label="'.attr($args['placeholder']).'" type="'.$type.'" name="'.$placeholder_name.'" value=""'.($is_placeholder?' checked':'').' />',
	// ],

	return [
        'name'        => $name,
        'title'       => $args['title'],
        'placeholder' => $placeholder,
        // 'group_id'    => $args['group_id'],
        // 'multiple'    => $args['multiple'],
        'items'       => $options,
        'selections'  => $selections,
		'html'		  => $html,
	];
}


// Fields ----------------------------------------------------------------------
function to_input_field ($type, $name, $args=null){
	$method = "to_{$type}_field";
	if (function_exists($method)){
		return call_user_func_array($method, [$name, $args]);
	}else{
		_warn("Input field type \"{$type}\" doesn't exists");
	}
}

function to_hidden_field ($name, $args=''){
    $args = to_field_args('text', $args, [
        'default' => null,
        'value'   => get_form_value($name),
    ]);

	if (!is($args['value'])){
		$args['value'] = $args['default'];
	}

    $html = to_html('input', [
        'type'  => 'hidden',
		'name'	=> $name,
        'value' => $args['value'],
        'attrs' => $args['attrs'],
    ]);

    return to_field($html, $args);
}

function to_text_field ($name, $args=''){
    $args = to_field_args('text', $args, [
        'type'        => 'text',
        'value'       => get_form_value($name),
        'default'     => null,
        'placeholder' => '',
		'required'    => false,
        'disabled'    => false,
    ]);

	if (!is($args['value'])){
		$args['value'] = $args['default'];
	}

	// TODO get the value on POST

    $html = to_html('input', [
        'name'        => $name,
        'type'        => $args['type'],
        'required'    => $args['required'],
        'disabled'    => $args['disabled'],
        'placeholder' => $args['placeholder'],
        'value'       => $args['value'],
		'class'		  => ['@--text', "is-{$args['type']}"],
		'attrs'		  => $args['attrs'],
    ]);

    return to_field($html, $args);
}

function to_textarea_field ($name, $args=''){
    $args = to_field_args('textarea', $args, [
        'value'       => get_form_value($name),
		'rows'		  => null,
        'placeholder' => '',
    ]);

	// TODO get the value on POST

    $html = to_html('textarea', [
        'name'        => $name,
        'placeholder' => $args['placeholder'],
		'rows'		  => $args['rows'],
		'class'		  => ['@--text', "is-textarea"],
		'attrs'		  => $args['attrs'],
        'html'        => $args['value'],
	], ['tabs' => false]);

    return to_field($html, $args);
}

function to_checkbox_field ($name, $args=''){
	$args = to_field_args('checkbox', $args, [
        'label'   => null,
        'default' => true,
        'value'   => get_form_value($name, false),
    ]);

	if ($args['value'] === 'on'){
		$args['value'] = true;
	}

	$html = to_html('input', [
        'type'    => 'checkbox',
        'name'    => $name,
        'value'   => $args['default'] === true ? '' : $args['default'],
        'checked' => $args['default'] === $args['value'],
        'class'   => ['@--checkbox'],
    ]);

	if ($args['label']){
		$html = to_html('label', [
            'class' => '@--toggle',
            'html'  => $html . ' <span class="@--label">' . $args['label'] . '</span>',
        ]);
    }

	return to_field($html, $args);
}


function to_color_field ($name, $args=''){
    $args = to_field_args('color', $args, [
        'value' => get_form_value($name),
    ]);

    $html = to_html('input', [
        'type'  => 'color',
		'name'	=> $name,
        'value' => $args['value'],
        'attrs' => $args['attrs'],
    ]);

    return to_field($html, $args);
}



function _to_choices_field ($type, $name, $args='', $options=array()){
	$args = to_field_args($type, $args, [
		'label'    => null,
		'default'  => true,
		'value'    => get_form_value($name, false),
		'template' => '',
		'multiple' => false,
		'options'  => $options,
    ]);

	$values = is_array($args['value']) ? $args['value'] : [$args['value']];
	$inputs = array_each($args['options'], function ($v, $i) use ($type, $name, $values, $args){
		$label = $v;
		$value = $i;

		if (is_array($v) || is_object($v)){
			$label = get_value($v, $args['label_key']);
			$value = get_value($v, $args['value_key']);
		}else{
			$v = array(
				'label' => $label,
				'value' => $value,
			);
		}

        $selected = in_array($value, $values);
		$label    = $args['template'] ? to_string($v, $args['template']) : $label;
		
		$input = to_html('input', [
			'type'    => $type,
			'value'	  => $value,
			'name'    => $name . ($args['multiple'] ? '[]' : ''),
			'checked' => $selected,
			'class'   => ["@--{$type}"],
		]);
		
		$input = to_html('label', [
			'class' => '@--toggle',
			'html'  => $input . ' <span class="@--label">' . $label . '</span>',
		]);

        return $input;
    });

	$html = implode(NL, $inputs);

	return to_field($html, $args);
}

// [ ] add option to auto select the first one
// [ ] add option unselect the radio by clicking the current selected one again
function to_radios_field ($name, $args='', $options=array()){
	return _to_choices_field('radio', $name, $args, $options);
}

function to_checkboxes_field ($name, $args='', $options=array()){
	$args = to_field_args('checkbox', $args, null, [
		'x'		   => 'Checkboxes',
		'type'     => 'checkbox',
		'multiple' => true,
	]);
	$options = to_field_options($name, $args, $options);	

	add_script_helper('to_checkboxes_field__js');

	return $options['html'];
}

function to_checkboxes_field__js (){ ?>
	<script>Web.Element(function Checkboxes (_){
		var CLASSES = {
			CHECKED : 'is-checked',
			ALL 	: 'all-checked',
		};

		this.init = function (data){
			this.render();
		};

		this.render = function (){
			var $inputs = this.$el.find('input');
			var count   = 0;
	
			$inputs.each(true, function (v){
				var value = v.value();
				var label = v.closest('label');

				if (value){
					label.addClass(CLASSES.CHECKED);
					count++;
				}else{
					label.removeClass(CLASSES.CHECKED);
				}
			});

			if (count === $inputs.length){
				this.$el.addClass(CLASSES.ALL);
			}else{
				this.$el.removeClass(CLASSES.ALL);
			}
		};

		this.tag_input_onChange = function (e){
			this.render();
		};

		this.tag_input_onChangeValue = function (e){
			this.render();
		};
	})</script>
<?php }


function to_select_field ($name, $args='', $options=array()){
	$args = to_field_args('select', $args, [
        'type'        => 'text',
        'value'       => get_form_value($name),
		'options'	  => $options,
		'label_key'	  => 'label',
		'value_key'	  => 'value',
		'template'	  => '',	
        'placeholder' => '',
		'styled'	  => false,
		'knob'		  => '▾',
    ]);
	$args = _filters('to_select_field-args', $args);

	$options = array_each($args['options'], function ($v, $i) use ($args){
		$label = $args['label_key'] === '$value' ? $i : $v;
		$value = $args['value_key'] === '$label' ? $v : $i;

		if (is_array($v) || is_object($v)){
			$label = get_value($v, $args['label_key']);
			$value = get_value($v, $args['value_key']);
		}else{
			$v = array(
				'label' => $label,
				'value' => $value,
			);
		}

		// skipped values/label
		if ($value === null || $label === null){
			return null;
		}

        $selected = $args['value'] !== null && $args['value'] == $value;
		$option   = '<option value="'.$value.'"'.($selected ? ' selected' : '').'>'.$label.'</option>';
		$option   = $args['template'] ? to_string($v, $args['template']) : $option;

        return $option;
    });

	if ($args['placeholder']){
        $placeholder = '<option value="">'.$args['placeholder'].'</option>';
        array_unshift($options, $placeholder);
    }

	$html = to_html('select', [
        'name'     => $name,
        // 'required' => $args['required'],
        // 'disabled' => $args['disabled'],
        'class'    => '@',
        'attrs'    => $args['attrs'],
		'html'     => implode(NL, $options),
		'x-el'	   => $args['styled'] ? "input" : '',
    ]);

	if ($args['styled']){
		add_script_helper('to_select_field_js');

		$html = to_html('div', [
			'x'	   => 'dropdown',
			'class'=> ['@--wrap'],
			'html' => [
				$html, 
				'<span class="@--value" x-el="label"></span>',
				'<span class="@--knob">'.$args['knob'].'</span>',
			],
		]);
	}

    return to_field($html, $args, '@-dropdown');
}

function to_select_field_js (){
	?>
	<script>Web.Element(function Dropdown (utils){
			this.$alias  = 'field-dropdown';
			
			this.$style = {
				'\&--wrap'       : 'position:relative; display:inline-flex;',
				'\&--wrap select': 'cursor:pointer; position:absolute; top:0; left:0; width:100%; height:100%; color:transparent; background:transparent; border:none;',
				'\&--knob'       : 'pointer-events:none;',
			};

			this.init = function (){
				this.render();
			};

			this.render = function (){
				var input = this.els.input;
				var label = input.options[input.selectedIndex].innerText;
				this.els.label.innerText = label;
			};

			this.input_onChange = function (e){
				this.render();
			}
			this.input_onChangeValue = function (e){
				this.render();
			}
		});
	</script>
	<?php
}

function to_footer_field ($labels=null, $args=null){
	$labels = _args($labels, [
		'label'   => 'Save',
		'loading' => 'Saving...',
		'saved'   => 'Saved!',
	], 'label');

	$args = _args($args, [
		'attrs'  => '',
		'before' => null,
		'after'  => null,
	]);
	
	// TODO ADD error "when-error" maybe
	$inside = [];

	$inside[] = to_html('button', [
		'html' => [
			'<span class="when-ready">'.$labels['label'].'</span>',
			'<span class="when-loading" hidden>'.$labels['loading'].'</span>',
			'<span class="when-saved" hidden>'.$labels['saved'].'</span>',
		]
	]);

	if ($before = __content($args['before'])){
		array_unshift($inside, $before);
	}
	if ($after = __content($args['after'])){
		array_push($inside, $after);
	};

	$html = to_html('footer', [
		// 'class' => '',
		// 'attrs' => $args['attrs'],
		'html'  => $inside,
    ]);

	return $html;
	/*
	return to_field($html, $args, '@-footer');
	*/
}


// Uploads ---------------------------------------------------------------------
function sync_upload_files ($args=''){
	if (empty($_FILES)) return;

	$args = to_args($args, [
		'dir'       => '@upload',
		'overwrite' => false,   // if false, will try to add a number suffix
	], 'dir');

	$uploads  = get_flatten_file_uploads();
	$response = array();

	foreach ($uploads as $upload){
		$filepath = upload_file($upload, array(
			'dir'       => $args['dir'],
			'overwrite' => $args['overwrite'],
		));

		if ($filepath){
			set_value($_POST, $upload['path'], $filepath);
			set_value($_REQUEST, $upload['path'], $filepath);

			$path = $upload['path'];
			$path = $path[0] . '[' . implode('][', array_slice($path, 1)) . ']';
			$response[] = array(
				'path'	   => $path,
				'filepath' => $filepath,
			);
		}
	}

	return $response;
}

function get_flatten_file_uploads (){
	// Step 1, fixed the files
	// https://stackoverflow.com/questions/7464893/processing-multi-dimensional-files-array
    $walker = function ($arr, $fileInfokey, callable $walker) {
        $ret = array();
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $ret[$k] = $walker($v, $fileInfokey, $walker);
            } else {
                $ret[$k][$fileInfokey] = $v;
            }
        }
        return $ret;
    };

    $files = array();
    foreach ($_FILES as $name => $values) {
        // init for array_merge
        if (!isset($files[$name])) {
            $files[$name] = array();
        }
        if (!is_array($values['error'])) {
            // normal syntax
            $files[$name] = $values;
        } else {
            // html array feature
            foreach ($values as $fileInfoKey => $subArray) {
                $files[$name] = array_replace_recursive($files[$name], $walker($subArray, $fileInfoKey, $walker));
            }
        }
    }

	// Step 2, flatten the files
	$flatten = function ($items, $callback, $path=array()){
		$uploads = array();
		foreach ($items as $i => $item){
			$p = array_merge($path, (array)$i);

			if (isset($item['tmp_name'])){
				$item['path'] = $p;
				$uploads[] = $item;
			}else{
				$u = $callback($item, $callback, $p);
				$uploads = array_merge($uploads, $u);
			}
		}
		return $uploads;
	};

	$flat = $flatten($files, $flatten);

    return $flat;
}

function upload_file ($file, $args=''){
	$args = to_args($args, [
		'dir'       => '@upload',
		'name'      => '',
		'accept'	=> null,		// filetype accepted
		'overwrite' => false,		// if false, will try to add a number suffix
		'return'	=> null,		// [error, object, url, null OR path]
	]);

	$error     = false;
	$filename  = null;
	$fileinfo  = null;
	$filepath  = $file;
	$is_upload = false;

	if (is_array($file) && isset($file['tmp_name'])){
		$filepath  = $file['tmp_name'];
		$filename  = isset($file['name']) ? $file['name'] : '';
		$is_upload = true;
	}

	if (!$filepath || !file_exists($filepath)){
		$error = $filepath ? "File \"{$filepath}\" doesn't exists" : "File doesn't exists";
	}
	
	if (!$error){
		$filename = $filename ? $filename : basename($filepath);
		$fileinfo = pathinfo($filename);

		$ext 	  = $fileinfo['extension'];
		$mimetype = mime_content_type($filepath);
		$accepts  = $args['accept'] ? to_array($args['accept']) : array();
	
		// filetype validations
		if (count($accepts)){
			$valid = false;
			foreach ($accepts as $accept){
				if (strpos($mimetype, $accept) === 0 || $accept === ".{$ext}"){
					$valid = true;
					break;
				}
			}
			
			// validations
			if (!$valid){
				$error = 'File accepts only "' . implode(', ', $accepts) . '" types'; 
			}
		}

		// max filesize validations
		if (!$error){
			// TODO
		}
	}
	
	$path = null;
	if (!$error){
		$is_base64 = strpos($filepath, 'data:image') === 0;
		$is_url    = strpos($filepath, 'http') === 0;
		$is_local  = !$is_base64 && !$is_url && file_exists($filepath);
		
		// TODO deal with download, localfile, base64 files
		if ($is_base64){
			// TODO
		}else if ($is_url){
			// TODO
		}else if ($is_local){
			// TODO
		}

		// add multi-part uploads
		// https://code.tutsplus.com/tutorials/how-to-upload-a-file-in-php-with-example--cms-31763

		$dir  	  = set_directory($args['dir']);
		$filename = $args['name'] ? $args['name'] : $fileinfo['filename'];
		$from     = $filepath;
		$to       = "{$dir}{$filename}.{$fileinfo['extension']}";
	
		file_move($from, $to, [
			'overwrite' => $args['overwrite'],
		]);

		$path = $to;
	}

	if ($args['return'] === 'error'){
		return $error ? $error : false;
	}else if ($args['return'] === 'url'){
		// TODO 
	}else if (return_object($args['return'])){
		// TODO 
	}else{
		return $path;
	}

	/*
	array(5) {
		["name"]=>
		string(11) "seasons.csv"
		["type"]=>
		string(8) "text/csv"
		["tmp_name"]=>
		string(36) "/Applications/MAMP/tmp/php/phphxBlW3"
		["error"]=>
		float(0)
		["size"]=>
		float(154)
	  }
	*/
}