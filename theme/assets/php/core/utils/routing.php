<?php
function get_routes ($dir='@routes', $args=null){
	$args = _args($args, [
		
	]);

	$key     = _slug($dir);
	$layouts = [];

	return ffto_cache_files($key, $dir, [
		'format' => function ($file) use (&$layouts){
			$path     = $file['path'];
			$basename = pathinfo($file['path'], PATHINFO_BASENAME);

			// it's a layout file
			if (strpos($basename, '+') === 0){
				$layouts[] = [
					'name'     => $file['name'],
					'filepath' => $file['filepath'],
					'dirpath'  => pathinfo($file['filepath'], PATHINFO_DIRNAME),
					'meta'     => $file['meta'],
				];
				return false;
			}

			// Deal with special meta
			// [x] skip/hidden
			// [x] layout = false
			$skip_file    = _get($file, 'meta/skip || meta/hidden');
			$skip_layouts = _get($file, 'meta/layout') === false;

			// Skip this file
			if ($skip_file) return false;

			// root path to this folder
			if ($basename === 'index.php'){
				$path = str_replace('index.php', '', $path);
			}

			// Decode the path to be a valid url
			$path = str_replace('.php', '', $path);
			$path = str_replace('...', '@@@', $path);
			$path = str_replace('.', '/', $path);
			$path = str_replace('@@@', '...', $path);

			// Check if there's a layouts matching
			$_layouts = [];
			if (!$skip_layouts){
				foreach ($layouts as $layout){
					if (strpos($file['filepath'], $layout['dirpath']) === 0){
						$key            = preg_replace('/^\+/', '', $layout['name']);
						$_layouts[$key] = $layout['filepath'];
					}
				}				
			}

			return [
				'name'     => $file['name'],
				'path'     => $path,
				'filepath' => $file['filepath'],
				'layouts'  => $_layouts,
				'meta'     => $file['meta'],
			];
		},
	]);
}