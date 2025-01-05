<?php 
/**
 * Emojis
 * 
 * @package Modules\Files
 * @author 	Maxime Lefrancois
 * @version 6.0
 **/
// -----------------------------------------------------------------------------
 
/**
 * Create a sitemap file with a list of pages
 *
 * Arguments:
 * ```php
 * [
 * 	'host'   => get_host(),		// host that will be appended to the pages
 * 	'path'   => '@env/sitemap.xml',		// path of the sitemap file
 * 	'expire' => '30days',				// days until the file will be refreshed
 * ]
 * ```
 * 
 * @param array $pages
 * @param array $args
 * @return void
 */
function set_sitemap ($pages, $args=array()){
	$args = to_args($args, array(
		'url'	 => '/sitemap',
		'host'   => get_host(),
		'path'   => '@env/sitemap.xml',
		'expire' => '30days',
	));
    
    $args = _filters('set_sitemap_args', $args);
    $path = parse_path($args['path']);

    if (file_expired($path, $args['expire'])){

		ob_start(); ?>
		<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?> 
		<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
		<?php foreach ($pages as $page): ?>
			<?php
			$page = is_array($page) ? $page : array('path'=>$page);
			$page = array_merge(array(
				'lastmod'	=> date('Y-m-d'),
				'frequency' => null,
				'priority'	=> 0,
			), $page);

			$url = clean_path("{$args['host']}/{$page['path']}");
			?>
			<url>
				<loc><?php echo $url; ?></loc>
				<lastmod><?php echo $page['lastmod']; ?></lastmod>
				<?php if ($page['frequency']): ?>
				<changefreq><?php echo $page['frequency']; ?></changefreq>
				<?php endif; ?>
				<?php if ($page['priority']): ?>
				<priority><?php echo $page['priority']; ?></priority>
				<?php endif; ?>
			</url>
		<?php endforeach; ?>
		</urlset>
		<?php
		$content = ob_get_clean();
		$content = trim($content);
		set_file($path, $content);
    }

	if ($args['url'] && is_url_path($args['url'])){
		file_output($path, [
			'mime' => 'application/xml',
		]);
	}

	return $path;
}

function csv_to_array ($path='', $args=array(), $format=false){
	if (!($path = to_filepath($path))) return [];

	$args = _args($args, [
		'separator' => ',',
		'utf8'      => false,     // encode in utf8
		'keys'      => true,
		'slug'		=> false,
		'format'    => $format,
	]);
	
	ini_set("auto_detect_line_endings", "1");
	ini_set("serialize_precision", "-1");
	
	$headers = null;  // CSV headers
	$items   = [];
	if (($file = fopen($path, 'r')) !== false){
					
		// faster than array_fuse()
		$combine = function ($keys, $values) use ($args){
			$r = array();
			foreach ($keys as $i => $k){
				if (array_key_exists($i, $values)){
					$k = $args['slug'] ? to_slug($k, $args['slug']) : $k;
					$r[$k] = $values[$i];
				}
			}
			return $r;
		};

		$items = array_query(function () use ($file, $args, &$headers){
			$row = fgetcsv($file, 0, $args['separator']);

			if (!$headers && $args['keys']){
				$headers = $row;
				return CONTINUE_LOOP;
			}else if (!$headers){
				$headers = array_each(count($row));
			}

			return $row;
		}, $args, function ($row) use (&$headers, $combine){
			return $combine($headers, $row);
		});	

		fclose($file);
	}

	return $items;
}

function error_log_to_array ($path='', $args=array(), $format=false){
	if (!($path = to_filepath($path))) return [];

	// TODO https://stackoverflow.com/questions/35693581/how-to-parse-php-error-log

	$args = _args($args, [
		'date_format' => '',
		// 'separator' => ',',
		// 'utf8'      => false,     // encode in utf8
		// 'keys'      => true,
		// 'slug'		=> false,
		// 'format'    => $format,
	]);

	$content = file_get_contents($path);
	$lines   = explode(PHP_EOL, $content);
	$logs 	 = [];
	$log 	 = null;

	$_add = function ($v) use (&$logs){
		if (!$v) return;

		$logs[] = $v;

		// TODO format $logs
	};

	foreach ($lines as $line){
		if ($line[0] === '['){
			$_add($log);
			$log = [];
			
			// all the main columns
			preg_match('/^(?:\[.+?]\s)+/', $line, $matches);
			$columns = $matches[0];
			$message = str_replace($columns, '', $line);

			preg_match_all('/(?:\[(.+?)]\s)/', $columns, $matches);
			foreach ($matches[1] as $i => $v){
				// It's a date
				if ($i === 0){
					$dt = to_date($v, [
						'input_format'   => $args['date_format'],
						'input_timezone' => 'UTC',
						'format'         => ':full',
						'timezone'       => 'America/Toronto'
					]);

					// $log['_date'] = $v;
					$log['date'] = $dt ? $dt : $v;
				// Others
				}else{
					// get the key
					$log[$i] = $v;
				}
			}

			$log['message'] = $message;
		}elseif ($log){
			$log['message'] = $log['message'] . $line;
		}
	}

	// add the last log
	$_add($log);

	return $logs;
}


// Get all the json files in a folder and return as an array
function files_to_array ($path='', $args=null, $format=true){
	if ($args === true){
		$args = ['data_file'=>true];
	}

	$args = _args($args, [
		'keys'      => true,
		'format'    => $format,
		'data_file' => false,
	]);

	$files = glob_deep($path);
	$sort  = array_map(function ($v){ return pathinfo($v, PATHINFO_FILENAME); }, $files );
	array_multisort($sort, SORT_NUMERIC, SORT_ASC, $files); // always sort by the filename "numeric" (way faster than usort)

	$items = array_query($files, $args, function ($file) use ($args){
		return $args['data_file'] ? get_data($file) : get_file($file);
	});	

	return $items;

}

/*
function __old_csv_to_array ($path='', $args=array(), $callback=null){
	$path = to_filepath($path);
	if (!$path) return false;

	ini_set("auto_detect_line_endings", "1");
	ini_set("serialize_precision", "-1");

	if ($args && is_callable($args)){
		$args = array('format'=>$args);
	}

	$args = _args($args, [
		'separator' => ',',
		'headers'   => true,        // the headers represent the title of the column, TODO OR pass an array 
		'utf8'      => false,       // encode in utf8
		'pivot'     => false,       // pivot make the header the rows
		'format'    => true,   		// [true, callback] true = to_value, callback to format the $row
		'filter'    => $callback,   // will check if it's a valid row
		'sort'		=> null,
		'index'     => null,        // add a index attribute
		'key'       => null,        // key to use for the array keys 
		'page'		=> false,
		'limit'		=> false,
		'random'	=> false,
		'tree'      => false,
		'return'	=> null,		// [null, object]
	]);

	$format = $args['format'] === true ? 'to_values' : $args['format'];
	$limit  = is_int($args['limit']) ? $args['limit'] : false;
	$page 	= is_int($args['page']) && is_int($limit) ? $args['page'] : 0;
	$from 	= is_int($page) ? $page * $limit : false;
	$to 	= is_int($page) ? $from + $limit : false;
	$tree 	= $args['tree'] ? to_array($args['tree']) : null;
    
	if (($file = fopen($path, 'r')) !== false){
		$index   = 0;		// always augment
		$count 	 = 0;		// only augment when the row is a valid value
		$headers = NULL;
		$rows    = array();

		// faster than array_fuse()
		$to_row = function ($keys, $values){
			$r = array();
			foreach ($keys as $i => $k){
				if (array_key_exists($i, $values)){
					$r[$k] = $values[$i];
				}
			}
			return $r;
		};
		
		while (($row = fgetcsv($file, 0, $args['separator'])) !== false){
			$row = $args['utf8'] ? array_map("utf8_encode", $row) : $row;

			// first row is the header
			if (!$headers && $args['headers']){
				$headers = $args['headers'] === true ? $row : to_array($args['headers']);
				continue;
			// created headers of numbers
			}else if (!$headers){
				$headers = array_each(count($row));
			}
		
			// pivot: headers are the rows -------------------------------------
			if ($args['pivot']){
				$row = _apply($format, $row, $index);
				foreach ($row as $i => $value){
					$key 		= $headers[$i];
					$rows[$key] = isset($rows[$key]) ? $rows[$key] : [];

					if ($value !== null){
						$rows[$key][] = $value;
					}
				}
				continue;
			}

			// keep augmenting the ID, even when $format callback return null, that way we know the right row if we want to come back
			$idx = $index++; 

			// is out of bounds
			$is_overflow = $limit !== false && ($count < $from || $count >= $to);

			// skip overflowing rows, only if format isn't set
			if (!$is_overflow || $args['filter']){
				$key = $idx; // the key might change
				$row = $to_row($headers, $row);
				$pre = $row;
				
				// automaticaly add the index as a attribute/property
				if ($args['index']){
					$k 		 = is_string($args['index']) ? $args['index'] : 'index';
					$row[$k] = $idx;
				}
				
				$row = _apply($args['filter'], $row, $index);
				if ($row === BREAK_LOOP){
					break;
				}
				
				$row = $row ? _apply($format, $row, $index) : false;

				// skip the rest of the row is empty
				if ($row === false){
					continue;
				}
			
				if (!$is_overflow){
					// change the array key to one of the row property
					if ($args['key']){
						$k = $args['key'];
						if (isset($row[$k]))		$key = $row[$k];
						else if (isset($pre[$k]))	$key = $pre[$k];	// if the $row has changed, check the pre object
					}

					$rows[$key] = $row;
				}	
			}

			$count++;
		}

		fclose($file);

		// random
		if ($args['random']){
			$rows = array_random($rows, $args['random']);
		}

		if ($sort = $args['sort']){
			if ($sort === ':reverse'){
				$rows = array_reverse($rows);
			}else{
				$rows = array_sort($row, $sort);
			}
		}

		// tree
		if ($tree){
			$items = array();
			
			foreach ($rows as $key => $row){
				$target= &$items;

				foreach ($tree as $i){
					if (!isset($row[$i])) continue;

					$v 			= $row[$i];
					$target[$v] = isset($target[$v]) ? $target[$v] : array();
					$target     = &$target[$v];

					// TODO have a way to change the grouping with a function maybe...

					unset($row[$i]);
				}

				$target[] = $row;
			}

			$rows = $items;
		}
	}
	
	if (return_object($args['return'])){
		return [
			'page'  => $page,
			'pages' => $args['limit'] ? ceil($count / $args['limit']) : 0,
			'count' => $count,
			'keys'  => $headers,
			'items' => $rows,
		];
	}

	return $rows;
}

function _csv_to_array ($path='', $args=array(), $format=false){
	if (!($path = to_filepath($path))) return false;

	ini_set("auto_detect_line_endings", "1");
	ini_set("serialize_precision", "-1");

	if ($args && is_callable($args)){
		$args = array('format'=>$args);
	}

	$args = _args($args, [
		// csv stuff
		'separator' => ',',
		'utf8'      => false,       // encode in utf8

		// 
		'keys'   	=> true,        // the keys (headers) of the items
		'key'		=> null,		// change the "key" of the item on the items (instead of )
		'index'     => null,        // add an index key to the items (the row # by default if true)
		'format'    => $format,   	// [true = to_value(), callback], if format returns NULL, skip it
		'filter'    => false,   	// will check if it's a valid row
		// pagination
		'page'		=> false,
		'limit'		=> false,

		// output
		'random'	=> false,		// 1 or many random items
		'sort'		=> null,		// sort after
		'pivot'     => false,       // pivot make the header the rows
		'tree'      => false,
		'return'	=> null,		// [null, object]
	]);

	// pagination 
	$limit  = is_int($args['limit']) && $args['limit'] ? $args['limit'] : false;
	$page 	= is_int($args['page']) && is_int($limit) ? $args['page'] : 1;
	$page   = is_int($page) && $page < 1 ? 1 : $page;		// make sure the page can't be under 1
	$from 	= is_int($page) ? ($page-1) * $limit : false;
	$to 	= is_int($page) ? $from + $limit : false;
    
	if (($file = fopen($path, 'r')) !== false){
		$headers= null;		// CSV headers
		
		
		// faster than array_fuse()
		$combine = function ($keys, $values){
			$r = array();
			foreach ($keys as $i => $k){
				if (array_key_exists($i, $values)){
					$r[$k] = $values[$i];
				}
			}
			return $r;
		};
		
		$index  = 0;		// always augment
		$count  = 0;		// only augment when the row is a valid value
		$keys   = null;		// name of all the keys in an item
		$items  = [];
		while (($row = fgetcsv($file, 0, $args['separator'])) !== false){
			$row = $args['utf8'] ? array_map("utf8_encode", $row) : $row;

			// need to get the default CSV headers to combine the rows to their data
			if (!$headers && $args['keys']){
				$headers = $row;
				continue;
			}else if (!$headers){
				$headers = array_each(count($row));
			}

			// $item = parse_list_items($row, $keys, $items, $index, $count, $args, function ($row) use ($combine, $headers){
			// 	return $combine($headers, $row);
			// });

			$i 			 = $index++; 	// keep augmenting the ID, even when $format callback return null, that way we know the right row if we want to come back
			$key  		 = $count;
			$is_skipped  = $limit && ($count < $from || $count >= $to); // if there's a pagination, we can skip some parsing

			if ($filter || !$is_skipped){
				$row   = $combine($headers, $row);
				$item  = $row;
				
				// automaticaly add an index property/key
				if ($args['index']){
					$k        = is_string($args['index']) ? $args['index'] : 'index';
					$item[$k] = $i;
				}

				// validate the 
				$valid = $filter ? is_match($item, $filter) : true;

				// the keys have already been defined, so we can skip the rest. The filter needs to be done before "format", since it makes it faster
				if (!$valid && $keys){
					continue;
				}

				// specific keys to fetch
				if ($args['keys']){
					$item = array_pluck($item, $args['keys']);
				}

				// reformat the item
				if ($args['format']){
					$item = to_values($item);
					$item = apply($args['format'], [$item, $i], ['fallback'=>$item]);
				}

				// fetch the keys
				if ($item && !$keys){
					$keys = array_keys($item);
				}

				// we need the keys before stopping this parsing
				if ($valid === BREAK_LOOP){
					break;
				}else if (!$valid){
					continue;
				}

				// skip the rest of the row is empty
				if ($item === false) continue;


				// update the key used
				if ($k = $args['key']){
					if (isset($item[$k]))		$key = $item[$k];
					else if (isset($row[$k]))	$key = $row[$k];	// if the $row has changed, check the row instead
				}
			}

			// add the item to the list
			if ($item && !$is_skipped){
				$items[$key] = $item;
			}

			$count++;
		}

		fclose($file);

		// TODO pivot...
		// TODO re-add random, sort, tree
	}
	
	if (return_object($args['return'])){
		return [
			'page'  => $page,
			'pages' => $args['limit'] ? ceil($count / $args['limit']) : 0,
			'count' => $count,
			'keys'  => $keys,
			'items' => $items,
		];
	}

	return $items;
}


function __files_to_array ($path='', $args=null, $format=true){
	$args = _args($args, [
		'keys'   	=> null,        // the headers represent the title of the column, TODO OR pass an array 
		'format'    => $format,     // [true = to_value(), callback], if format returns NULL, skip it
		'filter'    => false,   	// [true = remove empty, callback] will check if it's a valid row
		// 'sort'		=> null,	// TODO
		// 'index'     => null,     // add a index attribute
		// 'key'       => null,     // key to use for the array keys 
		'page'		=> false,	
		'limit'		=> false,
		'random'	=> false,
		'paths'		=> null, 		// update paths in file
		// 'tree'      => false,
		'return'	=> null,		// [null, object]
	]);

	$files = glob_deep($path);
	$sort  = array_map(function ($v){ return pathinfo($v, PATHINFO_FILENAME); }, $files );
	array_multisort($sort, SORT_NUMERIC, SORT_ASC, $files); // always sort by the filename "numeric" (way faster than usort)

	// pagination 
	$limit  = is_int($args['limit']) && $args['limit'] ? $args['limit'] : false;
	$page 	= is_int($args['page']) && is_int($limit) ? $args['page'] : 1;
	$page   = is_int($page) && $page < 1 ? 1 : $page;		// make sure the page can't be under 1
	$from 	= is_int($page) ? ($page-1) * $limit : false;
	$to 	= is_int($page) ? $from + $limit : false;

	$filter = !empty($args['filter']) ? $args['filter'] : null;
	$keys   = null;
	$items  = [];
	$count  = 0;
	foreach ($files as $i => $file){
		if (!filesize($file)) continue; // empty file
		
		$is_overflow = $limit && ($count < $from || $count >= $to); // if pagination and the item is out of bounds
		$increase	 = true;
		
		// get the file content (only if necessary)
		if ($filter || !$is_overflow){
			$file = get_file($file, ['paths'=>$args['paths']]);

			// get specific keys
			if ($args['keys']){
				$file = array_pluck($file, $args['keys']);
			}
			// re-format the file
			if ($args['format']){
				$file = to_values($file);
				$file = apply($args['format'], [$file, $i], ['fallback'=>$file]);
			}
			// fetch the keys
			if ($file && !$keys){
				$keys = array_keys($file);
			}
			// filter the file
			if ($filter && !is_match($file, $filter)){
				$file = null;
			}
		
			// should you increase the count
			$increase = !!$file;
		}else{
			$file = null;
		}

		if (!$is_overflow && $file){
			$items[$count] = $file;
		}

		if ($increase){
			$count++;
		}
	}

	if (return_object($args['return'])){
		return [
			'page'  => $page,
			'pages' => $args['limit'] ? ceil($count / $args['limit']) : 0,
			'count' => $count,
			'keys'  => $keys,
			'items' => $items,
		];
	}

	return $items;
}
*/