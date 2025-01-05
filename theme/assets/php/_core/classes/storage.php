<?php
/**
 * TODO add timezone to the Storage
 * TODO add filters
 * TODO add caching of the fetched object AND have a way to skip the caching, like getting a fresh new instance from the database
 * TODO add a Password field, that will be encoded with nonce maybe
 * TODO deal properly with Storage::setup and instance
 * TODO add code for exports to JSON for the default exort, and remove some info (like passwords and username for example)
 * TODO add way to increment the slug (not have duplicates)
 * TODO add way to have multiple storage, and that the Model can be setup to either of the storage
 * TODO add postgress support
 * TODO make JSON file format be able to save all in 1 file (for mini databases for example)
 * 
 * TODO add a automatic find method when calling Model->update() for isMethodName or setMethodName (in camelCase)
 * TODO add simple way to edit META data, but only some single values
 * 
 * TODO add Password type to the columns/fields
 */

class Storage{
	const TYPES_SQLITE            = 'sqlite';
	const TYPES_MYSQL             = 'mysql';
	const TYPES_POSTGRES          = 'postgres';
	const TYPES_JSON              = 'json';
	// const TYPES_CSV               = 'csv'; // TODO

	const ACTIONS_CREATE          = 'create';
	const ACTIONS_INSERT          = 'insert';
	const ACTIONS_UPDATE          = 'update';
	const ACTIONS_DELETE          = 'delete';

	const JOINS_AND               = 'AND';
	const JOINS_OR                = 'OR';

	const COLUMNS_PRIMARY_KEY     = 'primarykey';
	const COLUMNS_STRING          = 'string';
	const COLUMNS_TEXT            = 'text';
	const COLUMNS_NUMBER          = 'number';       // alias for "int" OR "float"
	const COLUMNS_INT             = 'int';
	const COLUMNS_FLOAT           = 'float';
	const COLUMNS_BOOLEAN         = 'boolean';
	const COLUMNS_DATE            = 'date';
	const COMPARE_EQUAL           = '=';
	const COMPARE_NOT_EQUAL       = '<>';
	const COMPARE_IN              = 'IN';
	const COMPARE_NOT_IN          = 'NOT IN';
	const COMPARE_MORE_THAN       = '>';
	const COMPARE_EQUAL_MORE_THAN = '>=';
	const COMPARE_LESS_THAN       = '<';
	const COMPARE_EQUAL_LESS_THAN = '<=';
	const COMPARE_IS_NULL         = 'IS NULL';
	const COMPARE_IS_NOT_NULL     = 'IS NOT NULL';
	const COMPARE_LIKE            = 'LIKE';

	// static variables --------------------------------------------------------
	static $_instance = null;
	
	// variables ---------------------------------------------------------------
	private $_models   = null;
	private $_path     = '';     // postgres 
	private $_multiple = true;
	
	public $type	 = null;
	public $database = null;
	public $name 	 = null;
	public $prefix   = '';
	public $query    = null;
    public $debug    = false;

	// constructor -------------------------------------------------------------
	static function instance ($args=array(), $debug=false){
        self::$_instance = self::$_instance ? self::$_instance : new self($args, $debug);
        return self::$_instance;
	}

	public function __construct ($args=array(), $debug=false){
		$args = to_args($args, array(
			'type'   	=> self::TYPES_MYSQL,
			'dir'    	=> '@env/storage/',
			'models' 	=> '@theme/models/*.php',   // include models dir
			'prefix' 	=> '',                      // table name prefix
			// mysql
			'name'      => 'database',          	// database name
			'user'      => null,
			'password'  => null,
			'host'      => 'localhost',
			'socket'    => '',
			'port'      => null,                	// '3306',
			'charset'   => 'utf8',
			'collation' => 'utf8_general_ci',
			// json
			'multiple'  => true,   					// multiple files for all the json entries
			// other
			'default'   => true,   					// default storage for Query and Model
			'debug'		=> $debug,
			'auto'		=> false,
		));

		$this->debug  = $args['debug'];
		$this->type   = $args['type'];
		$this->prefix = $args['prefix'];
		$this->name   = to_slug($args['name'], '_');

		if ($this->type === self::TYPES_MYSQL){
			$dns = array(
				'host'    => $args['host'],
				'charset' => $args['charset'],
				'port'    => $args['port'] ? $args['port'] : 3306,
			);
            // add socket only if specified
			if ($args['socket']){
				$dns['socket'] = $args['socket'];
			}

			$args['user'] 	  = $args['user'] === null ? 'root' : $args['user'];
			$args['password'] = $args['password'] === null ? 'root' : $args['password'];
			
			$this->database = $this->_database('mysql', array(
				'dns'        => $dns,
				'user'       => $args['user'],
				'pwd'        => $args['password'],
				'dbname'     => $this->name,
				'create_sql' => "CREATE DATABASE {$this->name} CHARACTER SET {$args['charset']} COLLATE {$args['collation']}"
			));
		}else if ($this->type === self::TYPES_POSTGRES){
			$dns = array(
				'host'   => $args['host'],
				'port'   => $args['port'] ? $args['port'] : 5432,
			);
            // add socket only if specified
			if ($args['socket']){
				$dns['socket'] = $args['socket'];
			}

			$args['user'] = $args['user'] === null ? 'postgres' : $args['user'];

			$this->database = $this->_database('pgsql', array(
				'dns'        => $dns,
				'user'       => $args['user'],
				'pwd'        => $args['password'],
				'dbname'     => $this->name,
				'create_sql' => "CREATE DATABASE {$this->name}"
			));
		}else if ($this->type === self::TYPES_SQLITE){
			$dir  = set_directory($args['dir']);
			$path = $dir . str_replace('.db', '', $this->name) . '.db';
			
            $this->_path = realpath($path);
			$this->database = $this->_database('sqlite', array(
				'dns'        => $path,
				'create_sql' => "CREATE DATABASE {$this->name}"
			));
		}else if ($this->type === self::TYPES_JSON){
			if ($args['multiple']){
				$path = set_directory($args['dir'].$this->name);
			}else{
				$path = parse_path("{$args['dir']}{$this->name}.json");
				$path = is_file($path) ? $path : set_file($path);
			}
			$this->_multiple = $args['multiple'];
			$this->database  = $path;
		}else{
			throw new Exception("The Storage type '{$this->type}' isn't supported");
		}

		if ($args['default']){
			Model::$_storage = $this;
			Query::$_storage = $this;
		}

		// load all the models
		if ($args['models']){
			include_files($args['models']);
		}

		if ($args['auto'] && empty($this->tables())){
			$this->generate(true);
		}
	}

	// static functions  -------------------------------------------------------
	
	// private function --------------------------------------------------------
	public function _log ($msg, $sql=null, $time=null, $debug=false){
		if (!$debug && !$this->debug) return;

		$sql   = $sql ? "\n\t" . str_replace("\n", "\n\t", $sql) . "\n\n" : null;
		$color = "#5fc75c";

		if ($time){
			// slow query are shown in red
			$slow_query = _config('storage_slow_query', 0.1);
			if ($slow_query && (float)$time >= $slow_query){
				$color = 'red';
			}

			$time = str_pad($time, 10, ' ', STR_PAD_LEFT);
			$msg  = "[{$time}] {$msg}";
		}

		$type = $this->debug;

		if ($this->debug === 'error'){
			__err($msg . NL . $sql);
		}else{
			call_user_func_array('__js', [
				[
					'prefix' => 'Storage',
					'color'  => $color,
					'file'   => false,
				],
				$msg,
				$sql,
			]);
		}
	}

	private function _database ($type, $args=array()){
		$args = array_merge(array(
			'dns'        => array(),
			'user'       => null,
			'pwd'        => null,
			'dbname'     => '',
			'create_sql' => '',
		), $args);

		$dns 		  = (is_array($args['dns']) ? http_build_query($args['dns'], '', ';') : $args['dns']);
		$dns_root     = $type . ':' . $dns;
		$dns_database = $type . ':' . $dns . ($args['dbname'] ? ';dbname=' . $args['dbname'] . ';' : '');
		$database	  = null;
		$options 	  = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		);

		try{
			$database = new PDO($dns_database, $args['user'], $args['pwd'], $options);
		}catch(Exception $e){
			// use the root connection (no database selected)
			$database = new PDO($dns_root, $args['user'], $args['pwd'], $options);

			// create the database
			$args['create_sql'] && $database->exec($args['create_sql']);

			// get the database with the newly created database
			$database = new PDO($dns_database, $args['user'], $args['pwd'], $options);
		}

		return $database;
	}

	// function: types ---------------------------------------------------------
	public function isMysql (){ return $this->type === self::TYPES_MYSQL; }

	public function isSqlite (){ return $this->type === self::TYPES_SQLITE; }

	public function isPostgres (){ return $this->type === self::TYPES_POSTGRES; }

	public function isSql (){ return $this->isMysql() || $this->isSqlite() || $this->isPostgres(); }

	public function isJson (){ return $this->type === self::TYPES_JSON; }

	// function: prepare -------------------------------------------------------
	public function prepare ($value, $args=null){
		return prepare_sql($value, $args);
	}

	public function toWhere ($where, $join=null){
		if (!$where) return null;

		// Make sure it's the right structure
		if (is_array($where) && isset($where[0]) && !is_array($where[0])){
			$where = [$where];
		}

		$join     = is_null($join) ? self::JOINS_AND : $join;
		$prepared = array();

		foreach ($where as $i => $v){
			$w = [
				'key'     => _get($v, 'key'),
				'compare' => _get($v, 'compare', '='),
				'value'   => _get($v, 'value', $v),
				'raw' 	  => _get($v, 'raw', false),
				'join'	  => $join,
			];

			// everything is in the $v
			if (!is_numeric($i)){
				$w['key'] = $i;
			}else if (arr_is_list($v)){
				$w['key']     = $v[0];
				$w['compare'] = isset($v[2]) ? $v[1] : '=';
				$w['value']   = isset($v[2]) ? $v[2] : $v[1];
			}

			if (is_string($w['compare'])){
				$w['compare'] = trim(strtoupper($w['compare']));
			}

			// Update the "compare" for an array value
			if (is_array($w['value']) && $w['compare'] === '='){
				$w['compare'] = self::COMPARE_IN;
			}

			if (!$w['raw'] && $w['compare'] === self::COMPARE_IN){
				$w['value'] = to_array($w['value']);
			}

			// if ($key = _get($v, 'key')) 		$w['key'] = $key;
			// if ($compare = _get($v, 'compare')) $w['compare'] = $compare;
			// if ($value = _get($v, 'value')) 	$w['value'] = $value;

			// if (is_string($v)){
			// 	$w['value'] = $v;
			// }
			// _p($w);

			$prepared[] = $w;
		}

		/*
		// ex.: ['valueA'=>1, 'valueB'=>2]
		if (arr_is_obj($where)){
			$tmp = array();
			foreach ($where as $i => $v){
				$tmp[] = array($i, '=', $v);
			}
			$where = $tmp;
        // TODO test and document this part...
		// ex.: 'valueA = 1, valueB = 2' OR (...?)
		}elseif (!is_array($where) || (is_array($where) && !is_array($where[0]))){
			$where = array($where);
		}

		// TODO add a way to CHANGE for OR join
		$prepared = array();
		foreach ($where as $i => $w){
			if (!$w) continue;

			if (is_array($w)){
				$key 		= $w[0];
				$value 		= isset($w[2]) ? $w[2] : $w[1];
				$compare 	= strtoupper(isset($w[2]) ? $w[1] : '=');

				// fix for "IN" when the $op
				if (is_array($value) && $compare === '='){
					$compare = 'IN';
				}

				$w = array(
					'key'		=> $key,
					'compare'	=> $compare,
					'value'		=> $value,
				);
			}else{
				$w = array(
					'key'		=> null,
					'compare'	=> null,
					'value'		=> $w,
				);
			}

			// special case, make sure it's an array with "IN"
			if ($w['compare'] === self::COMPARE_IN){
				$w['value'] = to_array($w['value']);
			}

			$w['join'] = $join;
			$prepared[$i] = $w;
		}
		*/

		return $prepared;
	}

	public function toSqlWhere ($where){
		$query = array();
		
		foreach ($where as $i => $w){
			$join 		= $i ? " {$w['join']} " : '';
			$key 		= $w['key'] ? "`{$w['key']}` " : '';	// TODO need to test with other databases
			$compare 	= $w['key'] ? "{$w['compare']} " : '';
			$value 		= $w['key'] && !$w['raw'] ? $this->prepare($w['value']) : $w['value'];
			// _p($w);
			// _p([$join, $key, $compare, $value]);
			$query[] 	= "{$join}({$key}{$compare}{$value})";
		}

		return implode(NL.TAB, $query);
	}

	public function toJsonTable ($table, $data=null){
		$is_multiple = $this->_multiple;
		$path        = $is_multiple ? "{$this->database}{$table}.json" : $this->database;

		// get
		if ($data === null){
			if ($is_multiple){
				$data = get_file($path, null, array());				
			}else{
				$all  = get_file($path, null, array());
				$data = isset($all[$table]) ? $all[$table] : array();
			}

			return array_merge(array(
				'count'   => 0,
				'next_id' => 1,
				'entries' => array()
			), $data);
		// set
		}else{
			if ($is_multiple){
				set_file($path, $data);
			}else{
				$all  		 = get_file($path, null, array());
				$all[$table] = $data;
				set_file($path, $all);
			}
		}
	}

	public function toJsonEntries ($entries, $where, $return_index=false){
        if (!$where) return $entries;

		$items = array();
		foreach ($entries as $index => $entry){
			$is_match = true;

			foreach ($where as $w){
				if (!$is_match && $w['join'] === self::JOINS_AND) continue;

				$value = isset($w['key']) && isset($entry[$w['key']]) ? $entry[$w['key']] : null;

				// function callback
				if (is_callable($w['value'])){
					// TODO
				}else if ($w['compare'] === self::COMPARE_EQUAL){
					$is_match = $value == $w['value'];
				}else if ($w['compare'] === self::COMPARE_NOT_EQUAL){
					$is_match = $value != $w['value'];
				}else if ($w['compare'] === self::COMPARE_IN){
					$is_match = in_array($value, $w['value']);
				}else if ($w['compare'] === self::COMPARE_NOT_IN){
					$is_match = !in_array($value, $w['value']);
				}else if ($w['compare'] === self::COMPARE_MORE_THAN){
					$is_match = $value > $w['value'];
				}else if ($w['compare'] === self::COMPARE_EQUAL_MORE_THAN){
					$is_match = $value >= $w['value'];
				}else if ($w['compare'] === self::COMPARE_LESS_THAN){
					$is_match = $value < $w['value'];
				}else if ($w['compare'] === self::COMPARE_EQUAL_LESS_THAN){
					$is_match = $value <= $w['value'];
				}else if ($w['compare'] === self::COMPARE_IS_NULL){
					$is_match = is_null($value);
				}else if ($w['compare'] === self::COMPARE_IS_NOT_NULL){
					$is_match = !is_null($value);
				}else if ($w['compare'] === self::COMPARE_LIKE){
					$match = strtr($w['value'], array(
                        '%' => '.*',
                        '_' => '.'
                    ));
					$is_match = preg_match("/^{$match}$/", $value);
				}
			}

			if ($is_match){
				$items[] = $return_index ? $index : $entry;
			}
		}

		return $items;
	}

	// function: actions -------------------------------------------------------
	public function models (){
		if (!$this->_models){
			$classes = get_declared_classes();
			$models  = array();

			foreach ($classes as $i=>$class){
				if (
                    !is_subclass_of($class, 'Model') ||
                    !isset($class::$table)
                ) continue;

				$key  = strtolower($class);
				$info = array(
					'class' => $class,
					'table' => $class::$table,
					'props' => $class::props(),
				);

				$models[$key] = $info;
			}

			$this->_models = $models;
		}

		return $this->_models;
	}

	public function model ($name, $args=null, $data=array()){
        $args = to_args($args, array(
            'return' => false,
            'data'   => $data
        ), 'return');

		$models = $this->models();
		$name   = string_replace($name, $args['data']);
		$name 	= strtolower($name);

		// found the perfect name
        $model = isset($models[$name]) ? $models[$name] : null;
		
        // go through all other models to find the right one (by making all names lowercase)
        if (!$model){
            $name = strtolower($name);
            foreach ($models as $i => $v){
                if (strtolower($i) === $name){
                    $model = $v;
                    break;
                }
            }
        }

        if ($key = $args['return']){
            return isset($model[$key]) ? $model[$key] : null;
        }

		return $model;
	}

	public function generate ($alter=false){
		$models  = $this->models();
		$tables  = $alter ? $this->tables(true) : array();
		$queries = array();

		foreach ($models as $name => $model){
			$table_name = $this->prefix.$model['table'];
			$props      = $model['props'];
			$query      = array();
		
			if ($this->isSql()){
				$primary_key = null;
				$is_alter 	 = isset($tables[$table_name]);

				foreach ($props as $name => $prop){
					if ($is_alter && in_array($name, $tables[$table_name]['columns'])) continue;
					
					$column 	= null;
					$type 		= $prop['column_type'];
					$not_null 	= $prop['nullable'] ? '' : ' NOT NULL';

					if ($type === self::COLUMNS_PRIMARY_KEY && $this->isSqlite()){
						$column 	 = "INTEGER PRIMARY KEY";
					}else if ($type === self::COLUMNS_PRIMARY_KEY && $this->isPostgres()){
						$column 	 = "SERIAL";
					}else if ($type === self::COLUMNS_PRIMARY_KEY){
						$column 	 = "INT AUTO_INCREMENT{$not_null}";
						$primary_key = $name;
					}else if ($type === self::COLUMNS_STRING){
						$column 	 = "VARCHAR({$prop['maxlength']}){$not_null}";
					}else if ($type === self::COLUMNS_TEXT){
						$column 	 = "TEXT{$not_null}";
					}else if ($type === self::COLUMNS_INT){
						$column 	 = "INT{$not_null}";
					}else if ($type === self::COLUMNS_FLOAT){
						$column 	 = "FLOAT{$not_null}";
					}else if ($type ===  self::COLUMNS_BOOLEAN && $this->isPostgres()){
						$column 	 = "SMALLINT{$not_null}";
					}else if ($type ===  self::COLUMNS_BOOLEAN){
						$column 	 = "TINYINT{$not_null}";
					}else if ($type ===  self::COLUMNS_DATE && $this->isPostgres()){
						$column 	 = "TIMESTAMP{$not_null}";
					}else if ($type ===  self::COLUMNS_DATE){
						$column 	 = "DATETIME{$not_null}";
					}else{
						continue; // Unknown type
					}

					$query[] = "`{$name}` {$column}";
				}

				if ($is_alter){
					if (!empty($query)){
						if ($this->isSqlite()){
							$query = to_string($query, 'ALTER TABLE '.$table_name.' ADD COLUMN {{ $value }}', '; ');
						}else{
							$query = to_string($query, 'ADD COLUMN {{ $value }}', ', ');
							$query = "ALTER TABLE {$table_name} {$query}";
						}
                        // TODO add "after" to the condition to put the column in the right order
					}
				}else{
					if ($primary_key){
						$query[] = "PRIMARY KEY ({$primary_key})";
					}
					$query = implode(', ', $query);
					$query = "CREATE TABLE IF NOT EXISTS {$table_name} ({$query})";
				}
			}else if ($this->isJson()){
				$query = array(
					'type'	=> self::ACTIONS_CREATE,
					'table'	=> $table_name,
				);
			}

			if (!empty($query)){
				$queries[] = $query;
			}
		}

		if (count($queries)){
			$this->exec($queries);
		}
	}

	public function exec ($query, $data=[], $debug=false){
		if (is_string($query) && !empty($data)){
			// replace SQL variable (eg. ":age" to the value "age" in $data)
			foreach ($data as $key=>$value){
				$value = $this->prepare($value, 'wrap=0');
				$query = str_replace(":{$key}", $value, $query);
			}
		}


		if ($this->isSql()){
			$query   = is_array($query) ? implode(";\n", $query) : $query;
			$last_id = null;
			
			__time('storage_exec');

			if ($query){
				$this->database->exec($query);

				try{
					// @info postgres breaks if it's not an insert
					$last_id = $this->database->lastInsertId();
				}catch (Exception $e){
					$last_id = null;
				}
			}

			$time = __time('storage_exec');
			$this->_log('SQL Exec', $query, $time, $debug);

			return is_numeric($last_id) ? (int)$last_id : $last_id;
		}else if ($this->isJson()){
			$queries = !isset($query[0]) ? array($query) : $query;
			$last_id = null;

			foreach ($queries as $query){
				$table = $this->toJsonTable($query['table']);

				if ($query['type'] === self::ACTIONS_CREATE){
					// reset values
					$table['count']		= 0;
					$table['next_id'] 	= 1;
					$table['entries'] 	= array();					
				}else if ($query['type'] === self::ACTIONS_INSERT){
					// Multiple inserts 
					foreach ($query['data'] as $entry){
						$last_id 			= $table['next_id']++;
						$entry['id'] 		= $last_id;
						$table['entries'][] = $entry;
					}
				}else if ($query['type'] === self::ACTIONS_UPDATE){
					$indexes = $this->toJsonEntries($table['entries'], $query['where'], true);
					
					foreach ($indexes as $index){
						$table['entries'][$index] = array_merge($table['entries'][$index], $query['data']);
					}
				}else if ($query['type'] === self::ACTIONS_DELETE){
					$indexes = $this->toJsonEntries($table['entries'], $query['where'], true);
					rsort($indexes, SORT_NUMERIC);

					foreach($indexes as $index){
						array_splice($table['entries'], $index, 1);
					}
				}

				$table['count'] = count($table['entries']);

				$this->toJsonTable($query['table'], $table);
			}

			return $last_id;
		}
	}

	public function query ($query, $debug=false){
		__time('storage_query');

		try{
			$value = $this->database->query($query);
			$time  = __time('storage_query');
		}catch(Exception $e){
			_err($e->getMessage(), $query);
			die();	
		}
		
		$this->_log('SQL Query', $query, $time, $debug);

		return $value;
	}

	public function create ($refresh=true){
		if ($this->isMysql()){
			$refresh && $this->database->exec("DROP DATABASE IF EXISTS {$this->name}");
			$this->database->exec("CREATE DATABASE IF NOT EXISTS {$this->name}");
			$this->database->exec("USE {$this->name}");
		}else{
			// TODO
			// TODO postgres can't deleted it's own database if currently connected to it
		}
	}

	public function insert ($table, $values){
		$table = $this->prefix . $table;

		if (empty($values)){
            _warn('insert() skipped, $values is empty');
			return null;
		}

		$values = arr_is_list($values) ? $values : array($values);

		if ($this->isSql()){
			$column_names = array();
			$column_rows  = array();

			foreach ($values as $i => $v){
				$row = [];

				foreach ($v as $ii => $vv){
					// skip the primary ID (postgress doens't like null values)
					// TODO make this a bit better, discover that the value is a primary ID, do not rely on it's name
					if ($ii == 'id') continue;

					$column_names[$ii] = true;
					$row[$ii]   	   = $this->prepare($vv);
				}

				$column_rows[] = '(' . implode(', ', $row) . ')';
			}

			$column_names = array_keys($column_names);
			$column_names = array_map(function ($v){ return "`{$v}`"; }, $column_names);
			$column_names = implode(', ', $column_names);
			$column_rows  = implode(','.NL.TAB, $column_rows);
			$query 		  = "INSERT INTO {$table}\n\t({$column_names})\nVALUES\n\t{$column_rows}";
			
			return $this->exec($query);
		}else if ($this->isJson()){
			$query = array(
				'type'	=> self::ACTIONS_INSERT,
				'table'	=> $table,
				'data'	=> $values,
			);
			return $this->exec($query);
		}
	}

	public function update ($table, $id, $values=array(), $where=array()){
		$table = $this->prefix . $table;

		if (is_array($id)){
			$where  = $values;
			$values = $id;
			$id     = null;
		}

		if (empty($values)){
			_warn('update() skipped, $values is empty');
			return $this;
		}

		if ($id){
			$where['id'] = $id;
		}

		if ($this->isSql()){
			$column_values = array();
			foreach ($values as $i => $v){
				$column_values[] = "\t`{$i}` = " . $this->prepare($v);
			}

			$column_where = array();
			foreach ($where as $i => $v){
				$column_where[] = "\t`{$i}` = " . $this->prepare($v);
			}

			$column_values = implode(','.NL, $column_values);
			$column_where  = implode(','.NL, $column_where);
			$query 		   = "UPDATE {$table} SET\n{$column_values}\nWHERE\n{$column_where}";
			
			return $this->exec($query);
		}else if ($this->isJson()){
			$query = array(
				'type'	=> self::ACTIONS_UPDATE,
				'table'	=> $table,
				'where'	=> $this->toWhere($where),
				'data'	=> $values,
			);
			return $this->exec($query);
		}
	}

	public function delete ($table, $where){
		$table = $this->prefix . $table;
		$where = $this->toWhere($where);

		if ($this->isSql()){
			$where = $this->toSqlWhere($where);
			$query = "DELETE FROM {$table}\nWHERE {$where}";
			$this->exec($query);
		}else if ($this->isJson()){
			$query = array(
				'type'	=> self::ACTIONS_DELETE,
				'table' => $table,
				'where'	=> $where,
			);
			$this->exec($query);
		}
	}

	public function export ($args=''){
		$args = to_args($args, array(
			'dir'         => '@env/exports/',
			'filename'    => '',
			'tables'      => null,
			'timeout'     => 3000,
			'max_insert'  => 100,
			'drop_tables' => true,
		));

		$dir  		= set_directory($args['dir']);
		$dir  		= realpath($dir);
		$date 		= date('Ymd-His');
		$filename 	= $args['filename'] ? $args['filename'] : "{$this->type}.{$this->name}.{$date}";
		
		if ($this->isMysql()){
			// @source https://github.com/ttodua/useful-php-scripts/blob/master/my-sql-export%20(backup)%20database.php
			$filepath = "{$dir}/{$filename}.sql";
			$db         = $this->database;
			$all_tables = $this->tables();
			$tables     = $all_tables;
            set_time_limit($args['timeout']); 

			if ($args['tables']){
				$tables = to_array($args['tables']);
				$tables = array_intersect($tables, $all_tables);
			}

			$content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `".$this->name."`\r\n--\r\n\r\n\r\n";

			foreach($tables as $table){
				if (empty($table)) continue;
				
				// create ------------------------------------------------------
				$content .= "\n\n# Table: {$table}\n# --------------------------------------- \n\n";   
				$create = $db->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM);
				
				if ($args['drop_tables']){
					$content .= "DROP TABLE IF EXISTS `{$table}`;";
					$content .= "\n\n{$create[1]};";
				}else{
					$content .= str_ireplace('CREATE TABLE `' , 'CREATE TABLE IF NOT EXISTS `', $create[1]) . ";";
				}

				// content -----------------------------------------------------
				$rows      = $db->query('SELECT * FROM `'.$table.'`');
				$col_count = $rows->columnCount();
				$row_count = $rows->rowCount();
				$max       = $args['max_insert'];
				$columns   = null;

				if ($row_count > 0){
					$content .= "\n\nLOCK TABLES `{$table}` WRITE;";
					$content .= "\n/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;";
					$content .= "\n";

					for ($i=0, $counter=0; $i<$col_count; $i++, $counter=0){
						while ($row = $rows->fetch()){ 
							if (!$columns){
								$columns = array_keys($row);
								$columns = array_map(function ($c){ return "`$c`"; }, $columns);
								$columns = implode(', ', $columns);
							}

							// add the INSERT every $max (eg.:100) rows
							if ($counter % $max === 0 || $counter === 0){
								$content .= "\nINSERT INTO {$table} ({$columns}) VALUES";
							}

							$isLast = (($counter+1)%$max==0 && $counter!=0) || $counter+1==$row_count;

							$values = array_map(function ($r){
								$r = to_value($r);

								if (!isset($r) || $r === ''){
									$r = 'NULL';
								}else if (is_string($r)){
									$r = '"'. str_replace("\n","\\n", addslashes($r)) . '"'; 
								}
			
								return $r;
							}, array_values($row));
							
							$content .= "\n\t(" . implode(',', $values) . ")" . ($isLast ? ';' : ',');
							$counter++;
						}
					} 

					$content .= "\n\n/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;";
					$content .= "\nUNLOCK TABLES;";
				}

				$content .="\n\n";
			}

			$content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";

			return set_file($filepath, $content);
		}else if ($this->isPostgres()){
			return err("Postgress export isn't available yet");
		}else if ($this->isSqlite()){
			$from_path  = realpath($this->_path);
			$filepath 	= "{$dir}/{$filename}.db";
			copy($from_path, $filepath);
			return $filepath;
		}else if ($this->isJson()){
			$all_tables = $this->tables(true);
			$filepath 	= "{$dir}/{$filename}.json";
			$content 	= array();

			// TODO update with the $this->_json function

			foreach ($all_tables as $name => $table){
				$content[$name] = get_file($table['filepath']);
			}

            // TODO zip files maybe instead

			set_json_file($filepath, $content);
			return $filepath;
		}
	}

    public function import ($content){
        // TODO
    }

	public function tables ($args=''){
		if (is_bool($args)){
			$args = array('data'=>$args);
		}

		$args = to_args($args, array(
			'data' => false,
		));

		$response = array();
		if ($this->isMysql()){
			$tables = $this->sql('SHOW TABLES', true);
			
			foreach ($tables as $i => $table){
				$name = array_values($table);
				$name = $name[0];

				if ($args['data']){
					$columns 		 = $this->sql("SHOW columns FROM {$name}", true);
					$columns 		 = array_pluck($columns, 'Field');
					$response[$name] = array('columns'=>$columns);
				}else{
					$response[] = $name;
				}
			}
		}else if ($this->isPostgres()){
			$tables = $this->sql("SELECT * FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema'", true);
			
			foreach ($tables as $i => $table){
				$name = $table['tablename'];

				if ($args['data']){
					$columns 		 = $this->sql("SELECT * FROM information_schema.columns WHERE table_schema = '{$table['schemaname']}' AND table_name = '{$name}'", true);
					$columns 		 = array_pluck($columns, 'column_name');
					$response[$name] = array('columns'=>$columns);
				}else{
					$response[] = $name;
				}
			}
		}else if ($this->isSqlite()){
			$tables = $this->sql('SELECT name FROM sqlite_master WHERE type="table"', true);
			
			foreach ($tables as $i => $table){
				$name = $table['name'];
				
				if ($args['data']){
					$columns 		 = $this->sql("PRAGMA table_info('{$name}');", true);
					$columns 		 = array_pluck($columns, 'name');
                    $response[$name] = array('columns'=>$columns);
				}else{
					$response[] = $name;
				}
			}
		}else if ($this->isJson()){
			$models = $this->models();

			foreach ($models as $i => $model){
				$name = $this->prefix . $model['table'];
				
				if ($args['data']){
					$response[$name] = array(
						'columns'  => array_keys($model['props'])
					);
				}else{
					$response[] = $name;
				}
			}
		}
	
		return $response;
	}

	// select ------------------------------------------------------------------
	public function sql ($sql, $data=array(), $fetch=false){
		if (is_bool($data)){
			$fetch = $data;
			$data  = array();
		}
		$query = new Query($this);
		return $query->sql($sql, $data, $fetch);
	}

	public function find ($table, $columns=null){
		$query = new Query($this);
		return $query->find($table, $columns);
	}
}

class Query{
	// static variables --------------------------------------------------------
	static $debug     = false;
	static $_instance = null;
	static $_storage  = null;

	// variables ---------------------------------------------------------------
	public $db        = null;
	public $_model    = null;
	public $_sql      = null;
	public $_table    = null;
	public $_columns  = null;
	public $_where    = array();
	public $_order    = null;
	public $_limit    = null;
	public $rows      = null;
	public $countRows = 0;
	public $foundRows = 0;

	// constructor -------------------------------------------------------------
	static function instance (){
		self::$_instance = self::$_instance ? self::$_instance : new self();
		return self::$_instance;
	}

	public function __construct ($storage=null){
		$this->storage = $storage ? $storage : self::$_storage;
	}

	// public function ---------------------------------------------------------
	// $native sql for complex stuff
	public function sql ($sql, $data=array(), $fetch=false){
		if ($data === true){
			$fetch = true;
			$data  = array();
		}

        // replace SQL variable (eg. ":age" to the value "age" in $data)
		foreach ($data as $key=>$value){
			$value = $this->storage->prepare($value, 'wrap=0');
			$sql   = str_replace(":{$key}", $value, $sql);
		}

		$this->_sql = $sql;

		return $fetch ? $this->all() : $this;
	}

	public function find ($table, $columns=null){
		$this->_table = $this->storage->prefix . $table;
		$this->columns($columns);
		return $this;
	}

	public function columns ($columns){
		if ($columns){
			$this->_columns = is_string($columns) ? array_map('trim', explode(',', $columns)) : $columns;
		}
		return $this;
	}

	public function model ($model){
		$this->_model = $model;
		return $this;
	}

	public function where ($where=array(), $join="AND"){
		if (!$where) return $this;
		$where        = $this->storage->toWhere($where, $join);
		$where        = isset($where) ? $where : array();
		$this->_where = array_merge($this->_where, $where);
		return $this;
	}

	public function order ($order){
		$this->_order = $order;
		return $this;
	}

	public function limit ($offset, $count=null){
		if (is_null($count)){
			$count  = $offset;
			$offset = 0;
		}
		$this->_limit = array($offset, $count);
		return $this;
	}

	// TODO
	public function group (){
	}

	public function all ($index=null, $single=false, $debug=false){
		$entries = array();

		if ($this->storage->isSql()){
			$sql = null;

			if ($this->_sql){
				$sql = $this->_sql;
			}else{
				$columns = $this->_columns ? implode(', ', $this->_columns) : '*';
				$sql   	 = array("SELECT {$columns}");
				
				if ($this->_table){
					$sql[] = "FROM {$this->_table}";
				}
				if ($this->_where){
					$where = $this->storage->toSqlWhere($this->_where);
					$sql[] = "WHERE {$where}";
				}
				if ($this->_order){
					$sql[] = "ORDER BY {$this->_order}";
				}
				if ($this->_limit){
					$sql[] = "LIMIT {$this->_limit[0]}, {$this->_limit[1]}";
				}

				$sql = implode(NL, $sql);
			}

			$query = $this->storage->query($sql, $debug);
			while ($entry = $query->fetch()){
				if ($this->_model){
					$entry = new $this->_model($entry);
					$entry->isDirty(false);
				}

				$entries[] = $entry;

				// only the first item
				if ($single){
					break;
				}
			}

			$this->countRows = count($entries);

			if (strpos($sql, 'LIMIT') !== false){
				if ($this->storage->isMysql()){
					$sql = 'SELECT FOUND_ROWS() AS nbr';
				}else{
					$sql = preg_replace('/LIMIT\s+\d+\s*(,\s\d+)/', '', $sql);
					$sql = "SELECT COUNT() AS nbr FROM ($sql)";
				}

				$count = $this->storage->query($sql)->fetch();
				$this->foundRows = (int)$count['nbr'];
			}			
		}else if ($this->storage->isJson()){
			$table 	 = $this->storage->toJsonTable($this->_table);
			$where 	 = isset($this->_where) ? $this->_where : null;
			$entries = $this->storage->toJsonEntries($table['entries'], $where);

			if ($this->_order){
				$entries = array_sort($entries, $this->_order);
			}

			if ($this->_limit){
				$entries = array_slice($entries, $this->_limit[0], $this->_limit[1]);
			}

			if ($this->_model || isset($this->_columns)){
				$items = array();

				foreach ($entries as $entry){
					if (isset($this->_columns)){
						$entry = array_pluck($entry, $this->_columns);
					}

					if ($this->_model){
						$entry = new $this->_model($entry);
						$entry->isDirty(false);
					}

					$items[] = $entry;
				}

				$entries = $items;
			}
		}

		$this->query = null;

		if ($index){
			$entries = array_set_keys($entries, $index);
		}

		$this->rows = $entries;

		return $entries;
	}

	public function one ($index=null, $debug=false){
		$entry = $this->all($index, true, $debug);
		return count($entry) ? $entry[0] : null;
	}
}

class Model{
	const PROPS_PRIMARY_KEY  = 'primarykey';
	const PROPS_STRING       = 'string';
	const PROPS_TEXT         = 'text';
	const PROPS_NUMBER       = 'number'; 		// alias for "int" OR "float"
	const PROPS_INT          = 'int';
	const PROPS_FLOAT        = 'float';
	const PROPS_BOOLEAN      = 'boolean';
	const PROPS_DATETIME     = 'datetime';
	const PROPS_DATE         = 'date';
	const PROPS_TIME         = 'time';

	const PROPS_ARRAY 		 = 'array';
	const PROPS_IMAGE 		 = 'image';
	const PROPS_JSON         = 'json';
	const PROPS_LIST         = 'list';
	const PROPS_RANDOM 		 = 'random';
	const PROPS_SLUG 		 = 'slug';
	const PROPS_RELATIONSHIP = 'relationship';

	// TODO add Enum props

	const VALUE_SKIP  		 = '► ⚀ ⚁ ⚂ ⚃ ⚄ ⚅ ◄'; // RANDOM-ish


	// const PARENT_ITEM 		 = 'PARENT_ITEM';
	
	// TODO deal with "is_active" to filter

	// static variables --------------------------------------------------------
	static $_storage = null;
	static $props    = array();
	static $strict 	 = false;
	// static $exports  = null;
	static $images   = null;
	static $data 	 = true; 	// set to false if the function data() shouldn't be used

	// properties --------------------------------------------------------------
	public  $_model         = null;
	public  $_values        = array();	// values get/set from the database
	public  $_cache 	    = array();  // values from methods that are cached
	private $_relationships = array();
	private $_dirty         = false;

	// static private ----------------------------------------------------------
	static function _cache ($key, $value=null){
        $class = get_called_class();
		$key   = "Model:{$class}:{$key}";
        if ($value === null){
            return get_global($key);
        }else{
            set_global($key, $value);
        }
		return $value;
	}

	static function _relationshipQuery ($query, $self=null){
		if (!$query) return null;

		$class_to = null;
		$query    = str_replace(NL, ' ', $query);
		$query	  = to_array($query, 'AND', function ($v) use (&$class_from, &$class_to, $self){
			// if the $self is specified, it's the "from" model class, replace those with this
			if ($self){
				$v = str_replace($self, 'this', $v);
			}
			
			$q = $v;
			// eg.: [ model | this ].[ col_name ] [ = | LINK | IN | NOT IN | <> ] [ model | this | {{ var }} ].[ col_name ] 
			// eg.: [ model | this ].[ col_name ] [ = | LINK | IN | NOT IN | <> ] [ value ] 
			$v = to_match($v, '/(?:((?:\{\{\s*)?[a-z][a-z0-9_]*(?:\s*\}\})?)\.)([a-z][a-z0-9_]*)\s*(=|LIKE|IN|\<\>)\s*(?:((?:\{\{\s*)?[a-z][a-z0-9_]*(?:\s*\}\})?)\.)?(\"?[a-z][a-z0-9_]*\"?)/i');

			// error
			if (!$v){
				err('Relationship Query "'.$q.'" isn\'t valid');
				return;
			}

			$v = array(
				'from'     => $v[0],
				'from_key' => $v[1],
				'compare'  => $v[2],
				'to'       => $v[3],
				'to_key'   => $v[3] ? $v[4] : '',
				'to_value' => !$v[3] ? to_value($v[4]) : null,
			);

			// fix "from" and "to", making sure "this" to be in the "from"
			if ($v['from'] !== 'this'){
				$v['to']     = array($v['from'], $v['from'] = $v['to'])[0];
				$v['to_key'] = array($v['from_key'], $v['from_key'] = $v['to_key'])[0];
			}

			if ($v['to'] && $v['to'] !== 'this'){
				$class_to = $v['to'];
			}

			return $v;
		});

		return array(
			'class' => $class_to,
			'query' => $query
		);
	}

	static function _decodeRelationship ($single, $proxy=null, $model=null){
		// aaa:Relationship($proxy, $model)
		if (!is_bool($single)){
			$model  = $proxy;
			$proxy  = $single;
			$single = false;
		}

		// aaa:Relationship($model)
		if (!$model){
			$model = $proxy;
			$proxy = null; 
		}

		$proxy = self::_relationshipQuery($proxy);
		$model = self::_relationshipQuery($model, $proxy ? $proxy['class'] : null);

		// @info the "model_from" could be a variable from $this->{{column_name}}, like "this.source_id = {{ source_type }}.id"
		// switch models
		// if ($proxy && $model && $proxy['model_to'] !== $model['model_from']){
		// 	$model['model_from'] = array($model['model_to'], $model['model_to'] = $model['model_from'])[0];
		// }

		return array(
			'is_single' => $single,
			'proxy'     => $proxy,
			'model'     => $model,
		);
	}

	static function _prop ($name, $data=null){
		$prop = string_to_action($name, ['type'=>':']);		
		$type = $prop['type'] = strtolower($prop['type'] ? $prop['type'] : 'String');
		$prop = to_args($prop, array(
			'name'		  => $prop['name'],
			'type'		  => $type,
			'column_type' => $type,
			'lifecycle'   => null,
			'default'     => null,
			'nullable'    => true,
		), 'default');

		$params = $prop['params'];
		$data   = to_args($data);

		$val = function ($key, $i, $fallback=null) use ($prop, $params){
			if (isset($prop[$key])){
				return $prop[$key];
			}else if ($i !== null && array_key_exists($i, $params)){
				return $params[$i];
			}else{
				return $fallback;
			}
		};
		
		$prop['once']    = false;
		$prop['auto']	 = false;
		$prop['default'] = $val('default', 0);

		if ($type === self::PROPS_PRIMARY_KEY){
			$prop['once']		 = true;
			$prop['auto'] 		 = true;
			$prop['nullable'] 	 = true;
		}else if ($type === self::PROPS_STRING){
			$prop['maxlength']   = $val('maxlength', 1, 255);
		}else if ($type === self::PROPS_NUMBER || $type === self::PROPS_INT || $type === self::PROPS_FLOAT){
			$prop['default']	 = is_numeric($prop['default']) ? (float)$prop['default'] : null;
			$prop['auto']  		 = !!$val('auto', 1, false);

			if ($type === self::PROPS_NUMBER){
				$prop['decimals']  	 = !!$val('decimals', 2, false);
				$prop['column_type'] = $prop['decimals'] ? self::PROPS_FLOAT : self::PROPS_INT;
			}else{
				$prop['decimals']  	 = $type === self::PROPS_FLOAT;
			}
		}else if ($prop['type'] === self::PROPS_DATETIME){
			$prop['auto']   	 = $val('auto', 1, false);
			$prop['column_type'] = 'date';
		}else if ($prop['type'] === self::PROPS_DATE){
			$prop['auto']   	 = $val('auto', 1, false);
			$prop['column_type'] = 'date';
		}else if ($prop['type'] === self::PROPS_RANDOM){
			$prop['maxlength'] 	 = 32;
			$prop['column_type'] = 'string';
		}else if ($prop['type'] === self::PROPS_RELATIONSHIP){
			$single       = _get($params, 0, false);
			$proxy        = _get($params, 1);
			$model        = _get($params, 2);
			$relationship = self::_decodeRelationship($single, $proxy, $model);

			// decode the proxy
			$prop['default']  	 = $relationship['is_single'] ? null : ($relationship['model'] ? new Models($relationship['model']['class']) : []);
			$prop['proxy']       = $relationship['proxy'];
			$prop['model']       = $relationship['model'];
			$prop['is_single']   = $relationship['is_single'];
			$prop['column_type'] = null;		// won't be part fo the database table
		}else if ($prop['type'] === self::PROPS_ARRAY){	
			$prop['separator']   = $val('separator', 0, ',');
			$prop['default']	 = null;
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_BOOLEAN){
			// @info nothing, makes sure the column_type = boolean
		}else if ($prop['type'] === self::PROPS_SLUG){
			$prop['target']      = $val('target', 0, false);
			$prop['default']	 = null;
			$prop['column_type'] = 'text';
		}else{
			$prop['column_type'] = 'text';
		}
		
		/*
		// all of those values are text
		}else if ($prop['type'] === 'text'){
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_TIME){	
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_ARRAY){
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_IMAGE){
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_JSON){
			$prop['column_type'] = 'text';
		}else if ($prop['type'] === self::PROPS_SLUG){
			$prop['column_type'] = 'text';
		};
		*/

		$prop				 = array_merge($prop, $data);
		$prop['type']        = strtolower($prop['type']);
		$prop['column_type'] = strtolower($prop['column_type']);

		unset($prop['params']);
		unset($prop['filters']);

		return $prop;
	}

	static function _values ($values){
		$values = _args($values);
		foreach ($values as $i => $v){
			if ($v === self::VALUE_SKIP){
				unset($values[$i]);
			}
		}
		return $values;
	}

	static function _hydrate ($items, $model, $is_single=true){
		$items = new Models($model, $items);
		return $is_single ? $items->first() : $items;
	}

    static function _id ($item, $is_single=null){
		$is_list   = is_a($item, 'Models') || arr_is_list($item);
		$is_single = $is_single === null ? !$is_list : $is_single;
		$items     = !$is_list ? array($item) : $item;
		$ids       = array();

		foreach ($items as $item){
			$id = null;

			if (is_numeric($item)){
				$id = (int)$item;
			}else{
				$id = get_value($item, 'id,ID');
			}

			if ($id !== null){
				$ids[] = $id;
			}
		}

		if (empty($ids)){
			$ids = array(-1);
		}

        return $is_single ? reset($ids) : $ids;
    }

	static function _walk ($item, $props){
		$class = is_object($item) ? get_class($item) : null;
		if (!$class) return $item;

		// TODO extra props to always exports when transforming in JSON format
		$exports = property_exists($class, 'exports') ? $class::$exports : array();
		foreach ($exports as $key => $method){
			// if (method_exists($item, $method)){
			// 	$value 			= $item->$method();
			// 	$exports[$key] 	= $value;
			// }
		}

		$json = array();
		foreach ($props as $prop){
			$key           = $prop['key'];
			$getter        = $prop['type'] ? $prop['type'] : to_slug("get {$key}", "camel");
			$reindex       = $prop['id'];
			$params        = $prop['params'];
			$filter        = $prop['filter'];
			$filter_params = $prop['filter_params'];
			$children 	   = $prop['children'];
			$value 		   = null;
			$skip 		   = false;

			// remove some properties (if there's a "!" before)
			if (strpos($key, '!') === 0){
				$key = str_replace('!', '', $key);
				unset($json[$key]);
				continue;
			}

			if ($key === '*' && is_a($item, 'Model')){
				$values = (array)$item->_values;

				// TODO maybe check if the value is a Model/Models/...Type of values to decode
				foreach ($values as $i=>$value){
					$values[$i] = self::_json($value, '*');
				}
				
				// $json = array_merge(['$type'=>$item->type()], $json, $values, $exports);
				$json = array_merge($json, $values, $exports);
				 
				continue;
			}else if (array_key_exists($key, $exports)){
				$value = $exports[$key];
			}else if (is_object($item) && property_exists($item, $getter)){
				$value = $item->{$getter};
			}else if (is_object($item) && method_exists($item, $getter)){
				$value = call_user_func_array(array($item, $getter), $params);
			}else if (is_object($item) && is_callable(array($item, $getter))){
				try{
					$value = call_user_func_array(array($item, $getter), $params);
				}catch (Exception $e){
					$value = null;
				}

				// the function returned nothing
				if ($value === false){
					continue;
				}
			}else{
				continue;
			}

			// TODO $filter comes from extract()
			if ($filter){
				/*
				if (method_exists($item, $filter)){
					$value = $file
				}
				*/
			}

			$childStructure = isset($children) && count($children) > 0 ? $children : '*';

			if (is_a($value, 'Models') || is_a($value, 'Model')){
				$is_list = is_a($value, 'Models');
				$value   = $value->json($childStructure);
				$value   = $is_list && $reindex ? array_set_keys($value, $reindex) : $value;
			}else if (is_array($value)){
				// TODO go through all the items and check if there's a Model/Models to parse
			}

			$json[$key] = $value;
		}

		return $json;
	}

	// transform the item(s) in JSON format
	static function _json ($items, $structure='*', $data=array()){
		$structure   = string_decode_structure($structure, array('data'=>$data));
		$is_single   = !is_array($items);
		$items       = $is_single ? array($items) : $items;

		foreach ($items as $i=>$item){
			$items[$i] = self::_walk($item, $structure);
		}

		return $is_single ? reset($items) : $items;
	}

	// static functions --------------------------------------------------------
	static function props (){
		if ($props = static::_cache('props')) return $props;
		
		$items = array_merge((array)'id:PrimaryKey', static::$props);
		$props = [];

		foreach ($items as $i => $v){
			if (is_numeric($i)){
				$i = $v;
				$v = '';
			}

			if ($i === '@defaults'){
				$props['dt_create'] = self::_prop('dt_create:DateTime("now")');
				$props['dt_update'] = self::_prop('dt_update:DateTime(null, true)');
				$props['dt_delete'] = self::_prop('dt_delete:DateTime');
				$props['is_active'] = self::_prop('is_active:Boolean');
				$props['order_by']  = self::_prop('order_by:Int(0, true)');
			}else if ($i === '@dates'){	
				$props['dt_create'] = self::_prop('dt_create:DateTime("now")');
				$props['dt_update'] = self::_prop('dt_update:DateTime(null, "now")');
				$props['dt_delete'] = self::_prop('dt_delete:DateTime');
			}else if ($i === '@active'){
				$props['is_active'] = self::_prop('is_active:Boolean');
			}else if ($i === '@order'){
				$props['order_by'] = self::_prop('order_by:Int(0, true)');
			}else{
				$prop 	 			  = self::_prop($i, $v);
				$props[$prop['name']] = $prop;
			}
		}
		
		static::_cache('props', $props);
		return $props;
	}

	static function prop ($key){
		if (is_array($key)) return $key;
		$props = self::props();
		return array_key_exists($key, $props) ? $props[$key] : null;
	}

    static function exists ($model){
		$storage = Storage::instance();
		return !!$storage->model($model);
	}

	static function apply ($methods=null, $params=array(), $ctx=null, $template=null){
		if (!$methods) return null;
		$ctx 	 = $ctx ? $ctx : get_called_class();
		$methods = to_array($methods, function ($v) use ($template){
			if (is_string($v) && $template){
				$v = string_replace($template, ['name'=>$v]);
			}
			return $v;
		});

		return apply($methods, $params, array(
			'ctx'      => $ctx,
			'validate' => function ($c){ return method_exists($c[0], $c[1]); },
		));
	}

	// crud --------------------------------------------------------------------
	static function sql ($sql, $data=array(), $fetch=true){
		if (is_bool($data)){
			$fetch = $data;
			$data  = array();
		}
		return self::$_storage->sql($sql, $data, $fetch);
	}

	static function col ($sql, $data=array(), $fetch=true){
		if (is_bool($data)){
			$fetch = $data;
			$data  = array();
		}
		
		$all = self::$_storage->sql($sql, $data, $fetch);
		$key = null;
		$_all= [];
		foreach ($all as $item){
			if ($key == null){
				$key = array_keys($item);
				$key = reset($key);
			}
			$_all[] = $item[$key];
		}

		return $_all;
	}

	// TODO col, row, single item?


	static function exec ($sql, $data=[]){
		return self::$_storage->exec($sql, $data);
	}
	
	static function query (){
		return self::$_storage
			->find(static::$table)
			->model(get_called_class());
	}

	static function all ($where=null, $args=null, $callback=null){
		// TODO $where could be a string that is a shortcut to a search query
		$args = to_args($args, array(
			'query'   => 'default',            // query filters to use
			'format'  => 'default',            // format filters to use 
			'load'    => '',
			'index'   => false,
			'single'  => false,
			'json'    => false,
			'data'    => array(),              // for the json calls
			'order'   => false,
			'sort'    => false,                // post-sorting items
			'columns' => null,
			'page'    => 1,
			'pages'   => 0,
			'max'	  => 0,
			'model'   => get_called_class(),
			'filter'  => $callback,
			'return'  => null,                 // [query, true, ids, ....]
			'debug'   => false,
		), 'index');

		$entries = array();
		if (is_a($where, 'Models')){
			$entries = $where->array();
		}else if (is_a($where, 'Model')){
			$entries = array($where);
		}else if (isset($where[0]) && is_a($where[0], 'Model')){
			$entries = $where;
		}else{
			$query = self::$_storage
				->find(static::$table, $args['columns'])
				->where($where)
				->order($args['order'])
				->model($args['model']);

			if ($args['pages']){
				$page = $args['page'] - 1;
				$page = $page * $args['pages'];
				$query->limit($page, $args['pages']);
			}else if ($args['max']){
				$query->limit($args['max']);
			}else if ($args['single']){
				$query->limit(1);
			}

			// format the query
			if ($args['query']){
				self::apply($args['query'], [$query], null, 'query_{{ name }}');
			}

			if ($args['single']){
				$entries = array($query->one(null, $args['debug']));
			}else{
				$entries = $query->all(null, false, $args['debug']);
			}
		}

		if (is_callback($args['filter'])){
			$entries = array_each($entries, $args['filter']);
		}
		
		// load children
		if ($args['load']){
			// TODO load some specific valules
			foreach ($entries as $entry){
				$entry->load($args['load']);
			}
		}
		// TODO, maybe remove the "format_" prefix
		if ($args['format']){
			$entries = self::apply($args['format'], [$entries], null, 'format_{{ name }}');
		}
		// post sort/order by
		if ($args['sort']){
			$entries = array_sort($entries, $args['sort']);
		}
		// json
		if ($args['json']){
			$entries = self::_json($entries, $args['json'], $args['data']);
		}
		// re-index
		if ($index = $args['index']){
			$entries = array_set_keys($entries, function ($v) use ($index){
				// if there's a ":" prefix, it's the raw value 
				if (strpos($index, ':') === 0){
					$index = substr($index, 1);
					return $v->value($index);
				}else{
					return $v->{$index};
				}
			});
		}
		
		// TODO cache the result
		
		$value = $args['single'] ? reset($entries) : $entries;

		if ($args['return']){
			$value = [
				'query' => $query,
				'value' => $value,
			];
		}

		return $value;
	}

	static function page ($page=0, $pages=10, $where=null, $args=null){
		if (is_array($page)){
			$where = $page;
			$args  = is_array($pages) ? $pages : null;
			$page  = 0;
			$pages = 10;
		}

		$args = _args($args, [
			'page'   => $page,
			'pages'  => $pages,
			'return' => true
		], 'max');
		
		$data  = self::all($where, $args);
		$count = $data['query']->foundRows;
		$pages = ceil($count / $args['pages']);

		return [
			'count'    => $count,
			'pages'    => $pages,
			'entries'  => $data['value'],
			// 'previous' => null,	// maybe get the "previous" query with page - 1
			// 'next'     => null,
		];
	}

	static function one ($where=null, $args=''){
		$args           = _args($args);
        $args['single'] = true;
		return self::all($where, $args);
	}

	// TODO test
	static function get (){
		$ids        = func_get_args();
		$orderby_id = false;
		
		// TODO add ":first" and ":last" options in the ids

		if (isset($ids[0]) && $ids[0] === true){
			$orderby_id = true;
			array_shift($ids);
		}

		$first 	  = reset($ids);
		$ids      = to_array($ids);
		$is_array = is_array($first) || count($ids) > 1;
		
		if (empty($ids)){
			return $is_array ? [] : null;
		}
		
		$ids = count($ids) > 1 ? $ids : $first;
		$ids = is_array($ids) ? $ids : [$ids];

		// save in memory the elements	
		$entries    = array();
		$search_ids = [];

		/// find the cache entries
		foreach ($ids as $id){
			$entry = self::_cache("entry_{$id}");
			if ($entry){
				$entries[] = $entry;
			}else{
				$search_ids[] = $id;
			}
		}

		if (count($search_ids)){
			$search = self::$_storage
				->find(static::$table)
				->where(array('id', 'IN', $search_ids))
				->model(get_called_class())
				->all();

			foreach ($search as $entry){
				self::_cache("entry_{$id}", $entry);
				$entries[] = $entry;
			}
		}


		if ($is_array){
			// order the fetched items by the ID passed in that passed order
			if ($orderby_id){
				$entries = array_sort($entries, function ($item) use ($ids){
					$id    = get_value($item, 'id');
					$index = array_search($id, $ids);
					return $index;
				});
				$entries = array_values($entries);
			}
		}else{
			$entries = reset($entries);
		}

		return $entries;
	}

	static function first (){
		$entry = self::$_storage
			->find(static::$table)
			->order('id ASC')
			->limit(1)
			->model(get_called_class())
			->one();

		return $entry;
	}

	static function last (){
		$entry = self::$_storage
			->find(static::$table)
			->order('id DESC')
			->limit(1)
			->model(get_called_class())
			->one();

		return $entry;
	}

	static function getBy ($key, $value, $is_single=true){
		$entry = self::$_storage
			->find(static::$table)
			->where(array((string)$key, $value))
			->model(get_called_class());

		if ($is_single){
			return $entry->one();
		}else{
			return $entry->all();
		}
	}

	// find OR create an elemens
	static function sync ($values, $key='id'){
		$class = get_called_class();
		$value = _get($values, $key);
		$entry = $value ? self::getBy($key, $value) : null;
		$entry = $entry ? $entry : new $class();
		$entry->update($values, true);
		return $entry;
	}

	static function data ($args='', $hydrate=false){
		if ($args === true){
			$args = array('hydrate'=>true);
		}else if (is_callback($args)){
			$args = array('filter'=>$args);
		}

		$args = to_args($args, array(
			'id'      => null,        // get an item by ID		
			'column'  => null,        // re-index
			'hydrate' => $hydrate,    // hydrate the objects
			'format'  => 'default',   //
			'filter'  => null,        //
			'active'  => null,        // 
			'cache'   => true,        //
        ), 'id');

		$class = get_called_class();
		$cache = 'storage/' . $class . ($args['format'] ? '_' . to_string($args['format'], 'encode=1') : '');
		$items = $args['cache'] ? get_cache($cache, ['memory'=>true]) : false;
        if (!$items){
			$items = self::all(null, array(
				'index'  => 'id',
				'query'  => false,
				'format' => $args['format'],
				'json'   => true,
			));

			// for all, remove the relationships
			$props = self::props();
			foreach ($items as $i => $item){
				foreach ($props as $ii => $prop){
					if ($prop['type'] !== self::PROPS_RELATIONSHIP) continue;
					unset($item[$ii]);
				}
				$items[$i] = $item;
			}

			set_cache($cache, $items, [
				'memory' => true,
				'expire' => $args['cache']
			]);
        }

		$items = empty($items) ? array() : $items;

        if ($args['active'] !== null){
            $is_active = !!$args['active'];
            $items = array_filter($items, function ($item) use ($is_active){
                return isset($item['is_active']) && $item['is_active'] === $is_active;
            });
        }

		// re-index
		if ($args['column']){
			$items = array_set_keys($items, $args['column']);
		}
		// get 1 item by ID
		if ($id = $args['id']){
			$item  = isset($items[$id]) ? $items[$id] : null;
			$items = $item ? [$item] : [];
		}
		// hydrate the items
		if ($args['hydrate']){
            $items = array_map(function ($item) use ($class){
                return new $class($item);
            }, $items);
        }

		return $args['id'] ? reset($items) : $items;
	}
		
	static function bulkSave ($entries, $deep=false){
		$entries = empty($entries) ? array() : self::all($entries);
		foreach ($entries as $entry){
			is_a($entry, 'Model') && $entry->save($deep);
		}
		return $entries;
	}

	static function bulkUpdate ($entries, $values=null, $save=true, $deep=false){
		$entries = empty($entries) ? array() : self::all($entries);
		foreach ($entries as $entry){
			is_a($entry, 'Model') && $entry->update($values, $save, $deep);
		}
		return $entries;
	}

	static function bulkDelete ($entries, $deep=false){
		$entries = empty($entries) ? array() : self::all($entries);
		foreach ($entries as $entry){
			is_a($entry, 'Model') && $entry->delete($deep);
		}
		return $entries;
	}

	// crud filters ------------------------------------------------------------
	// TODO change the names query_order_by to query_orderBy and query_is_active to query_isActive
	static function query_default ($query){
		self::query_order_by($query);
		self::query_is_active($query);
	}

	static function query_order_by ($query){
		self::prop('order_by') && $query->order('order_by ASC');
	}

	static function query_is_active ($query){
		self::prop('is_active') && $query->where('is_active = 1');
	}
		
	// private:props -----------------------------------------------------------
	private function _decode ($prop, $value){
		if (!$prop) return $value;
		
		$type = $prop['type'];
		if ($type === self::PROPS_PRIMARY_KEY){
			$value = to_value($value);
		}elseif ($type === self::PROPS_STRING){
			$value = !is_array($value) ? (string)$value : $value;
		}else if ($type === self::PROPS_INT){
			$value = is_numeric($value) ? (int)$value : null;
		}else if ($type === self::PROPS_FLOAT || $type === self::PROPS_NUMBER){
			$value = is_numeric($value) ? (float)$value : null;
		}else if ($type === self::PROPS_BOOLEAN){
			$value = !!$value;
		}else if ($type === self::PROPS_ARRAY){
			$value = to_array($value, array('separator'=>$prop['separator'], 'filter'=>true, 'parse'=>true));
			$value = is_array($value) ? $value : array();
		}else if ($type === self::PROPS_JSON || $type === self::PROPS_LIST){
			if (is_string($value)){
				// change the quote (") and "\/" back to normal 
				$value = str_replace(['\u0022', '\\\/'], ['"', '\/'], $value);
				$value = json_decode($value, true);

				// make sure the "new line" are proper new lines
				$decode = function ($v, $d){
					if (is_string($v)){
						return strtr($v, [
							'\r' => "\r",
							'\n' => "\n",
						]);
					}else if (is_array($v)){
						return array_map(function ($vv) use ($d){ return $d($vv, $d); }, $v);
					}
					return $v;
				};
				$value = $decode($value, $decode);				
			}

			$value = is_array($value) ? $value : array();
			$value = $type === self::PROPS_LIST ? array_values($value) : $value;
		}else if ($type === self::PROPS_DATE){
			$value = to_date($value, array('format'=>'Y-m-d'));
		}else if ($type === self::PROPS_TIME){
			$value = to_date($value, array('format'=>'H:i:s', 'input_timezone'=>'UTC'));
		}else if ($type === self::PROPS_DATETIME){
			$value = to_date($value, array('format'=>'Y-m-d H:i:s', 'input_timezone'=>'UTC'));
		}else if ($type === self::PROPS_IMAGE){
			// @nothing
		}else if ($type === self::PROPS_SLUG){
			// @nothing
		}

		return $value;
	}

	private function _encode ($prop, $value){
		$type = $prop['type'];
		if ($type === self::PROPS_STRING){
			$value = (string)$value;
			$value = strlen($value) > $prop['maxlength'] ? substr($value, 0, $prop['maxlength']) : $value;
		}else if ($type === self::PROPS_INT){
			$value = (int)$value;
		}else if ($type === self::PROPS_FLOAT){
			$value = (float)$value;
		}else if ($type === self::PROPS_ARRAY){
			$value = is_array($value) ? implode($prop['separator'], $value) : null;
		}else if ($type === self::PROPS_BOOLEAN){
			$value = !!$value;
		}else if ($type === self::PROPS_JSON || $type === self::PROPS_LIST){
			$value = json_encode($value, JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		}else if ($type === self::PROPS_DATE){
			$value = to_date($value, ['format'=>'Y-m-d', 'timezone'=>'UTC']);
		}else if ($type === self::PROPS_TIME){
			$value = to_date($value, ['format'=>'H:i:s', 'timezone'=>'UTC']);
		}else if ($type === self::PROPS_DATETIME){
			$value = to_date($value, ['format'=>'Y-m-d H:i:s', 'timezone'=>'UTC']);
			// TODO need to validate the timezone are working properly
		}else if ($type === self::PROPS_IMAGE){
			// @nothing
		}

		return $value;
	}

	private function _find ($search, $items=null){
		$where = array();
		foreach ($search['query'] as $query){
			$value = null;

			if ($query['to_value'] !== null){
				$value = $query['to_value'];	
			}else if ($query['from_key'] && $items){
				$value = array_pluck($items, $query['from_key']);
			}else{
				$value = $this->{$query['from_key']};
			}

			$where[] = [
				$query['to_key'],
				$query['compare'],
				$value
			];
		}
		
		// TODO maybe the function could call a function on the target

		$class = string_replace($search['class'], $this->_values);
		$model = self::$_storage->model($class, 'class');
		$items = $model::all($where);

		return $items;
	}

	private function _load ($prop){
		$prop = self::prop($prop);

		if ($prop['proxy']){
			$items = $this->_find($prop['proxy']);
			$items = count($items) ? $this->_find($prop['model'], $items) : array();
		}else if ($prop['model']){
			$items = $this->_find($prop['model']);
		}


		if ($prop['is_single']){
			$items = reset($items);
		}else{
			$items = new Models($prop['model']['class'], $items);
		}

		return $items;
	}

	private function &_get ($name, $params=array()){
		$prop    = self::prop($name);
		$name 	 = $prop ? $prop['name'] : $name;
		$method1 = $name;
		$method2 = to_slug("get-{$name}", 'camel');
		$method3 = $prop ? '_' . to_slug("_get-{$prop['type']}", 'camel') : null;
		$value 	 = isset($this->_values[$name]) ? $this->_values[$name] : null;
		$params  = is_array($params) ? $params : array();

		// cache
		if (empty($params) && array_key_exists($name, $this->_cache)){
			return $this->_cache[$name];
		// {$name}()
		}else if (method_exists($this, $method1)){
			$value = $this->$method1();
		// get{$name}()
		}else if (method_exists($this, $method2)){
			$value = $this->$method2();
		// get{$type}()
		}else if (method_exists($this, $method3)){
			$params = array_merge(array($prop, $value), $params);
			$value  = call_user_func_array(array($this, $method3), $params);
		}

		if (empty($params)){
			$this->_cache[$name] = $value;
		}
		
		return $value;
	}

	private function _set ($name, $params=array()){
		$prop    = self::prop($name);
		$name 	 = $prop ? $prop['name'] : $name;
		$method  = to_slug("set-{$name}", 'camel');
		$method2 = $prop ? '_' . to_slug("_set-{$prop['type']}", 'camel') : null;
		$value 	 = reset($params);

		// clear the cache
		unset($this->_cache[$name]);

		if ($method && method_exists($this, $method)){
			$this->$method($value);			
		}else if ($method2 && method_exists($this, $method2)){
			$params = array_merge(array($prop), $params);
			$this->_values[$name] = call_user_func_array(array($this, $method2), $params);
		}else{
			$this->_values[$name] = $value;
		}

		$prop && $this->isDirty($name); // make sure the property will be saved (if it exists)

		return $this;
	}

	private function _setBoolean ($p, $v){ return !!$v; }
	private function _getBoolean ($p, $v){ return !!$v; }

	// private function _setDatetime ($p, $v){ return to_date($v, 'Y-m-d H:i:s'); }
	private function _setDatetime ($p, $v){ return to_date($v, ':utc-sql'); }
	private function _getDatetime ($p, $v, $format=null){ return to_date($v, $format); }

	private function _setDate ($p, $v){ return to_date($v, 'Y-m-d'); }
	private function _getDate ($p, $v, $format=null){ return to_date($v, $format); }

	private function _setTime ($p, $v){ return to_date($v, 'H:i:s'); }
	private function _getTime ($p, $v, $format=null){ return to_date($v, $format); }

	private function _setJson ($p, $i, $v=null, $clear=false){
		if (is_args($i)){
			$i = to_args($i);
		}

		if (is_array($i)){
			$clear = $v;
			$v 	   = null;
		}

		$set  = to_set($i, $v);
		$json = !$clear && isset($this->_values[$p['name']]) ? $this->_values[$p['name']] : array();
		$json = array_merge($json, $set);

		return $json;
	}
	private function _getJson ($p, $v, $i=null, $fallback=null, $exists = false){
		if ($i && $exists){
			return array_key_exists($i, $v) ? $v[$i] : $fallback;	
		}else if ($i){
			return isset($v[$i]) && is($v[$i]) ? $v[$i] : $fallback;
		}else{
			return $v;
		}
	}

	private function _setList ($p, $i, $v=null){
		if (is_array($i)){
			$list = $i;
		}else{
			$list     = _get($this->_values, $p['name'], []);
			$list[$i] = $v;
		}
		$list = array_values($list);
		return $list;
	}

	private function _getList ($p, $v, $i=null, $fallback=null){
		if ($i){
			return array_key_exists($i, $v) ? $v[$i] : $fallback;
		}else{
			return $v;
		}
	}

	private function _setSlug ($p, $v){ 
		// refresh the slug
		if ($v === true){
			$v = $this->{$p['target']};
		}
		return to_slug($v); 
	}

	private function _setRandom ($p){
		// TODO maybe make this value "not" updatable once set, freezing it
		// always randomize when setting it
		return md5(uniqid(rand(), true));
	}

	private function _setRelationship ($p, $items){
		$model = $p['model']['class'];

		if ($items){
			$items = arr_is_obj($items) || !is_iterable($items) ? array($items) : $items;
			$items = new Models($model, $items);
			
			// make sure to cache that the relationships has been set, so we don't reload it
			if (!in_array($p['name'], $this->_relationships)){
				$this->_relationships[] = $p['name'];
			}
		}else{
			$items = new Models($model);
		}

		if ($p['is_single']){
			$items = $items->first();
		}

		return $items;
	}

	private function _getRelationship ($p, $v, $refresh=false){
		$i 		= $p['name'];
		$exists = in_array($i, $this->_relationships);
		if ($exists && !$refresh) return $v;
	
		$v = $this->_values[$i] = $this->_load($p, $refresh);
				
		if (!$exists) $this->_relationships[] = $i;

		return $v;
	}

	// private function _setImage ($p, $v){

	// }

	private function _getImage ($p, $v, $args=null){
		if ($args === false){
			return $v;
		}

		$dir = isset(static::$images) ? static::$images : "@data/images/" . to_slug($this->_model);
		$args= to_args($args, [
			'dir'      => $dir,
			'edit_dir' => "@env/images/",
		]);
		$img = to_image($v, $args);
		return $img;
	}

	// TODO add a way output values as String right away. Something like $item->toDtCreatedStr (to and Str).

	// private -----------------------------------------------------------------
	public function _apply ($methods=null, $params=array()){
		return self::apply($methods, $params, $this);
	}

	// called on save / or when 
	private function _updateAutoProps ($key=null){
		$props = static::props();

		foreach ($props as $i => $prop){
			if ($key && $i !== $key) continue;

			if ($prop['type'] === self::PROPS_SLUG && $prop['target'] && !$this->{$i} && $this->{$prop['target']}){
				$this->{$i} = $this->{$prop['target']};
			}else if ($prop['type'] === self::PROPS_RANDOM && !$this->{$i}){
				$this->{$i} = true;
			}else if ($prop['default'] && !$this->id){
				$this->isDirty($i);
			}else if ($prop['auto'] && !$prop['once']){
				// $this->isDirty($i);
				$this->{$i} = $prop['auto'];
			}
		}

		// p($this);
	}

	// init --------------------------------------------------------------------
	public function __construct ($values=array(), $find_by=null){
		$this->_model = get_called_class();

		$values = static::_values($values);  // values to use
		$dirty  = array_keys($values);
		$props  = static::props();        // properties of the model	

		// find the item (if it already exists in the database) with reference to a specific prop value (eg. the slug) OR by it's ID,
		if ($find_by !== null){
			if (is_numeric($find_by)){
				$instance = self::get($find_by);
			}else if (is_string($find_by) && isset($values[$find_by])){
				$instance = self::getBy($find_by, $values[$find_by]);
			}

			// TODO have a way to specify it's coming from the Database, so if there's dates, it's UTC types

			// old and new values
			$old 	= $instance ? get_object_vars($instance) : array();
			$values = array_merge($old, $values);
		}

		$this->_apply('onPreInit');

		foreach ($props as $i => $prop){
			$value = isset($values[$i]) ? $values[$i] : $prop['default'];
			$value = $this->_decode($prop, $value);

			// hydrate the item(s)
			if ($prop['type'] === self::PROPS_RELATIONSHIP && $prop['model']){
				$value = self::_hydrate($value, $prop['model']['class'], $prop['is_single']);
			}

			if (in_array($i, $dirty)){
				$this->isDirty($i);
			}

			$this->_values[$i] = $value;
		}

		// add dynamic values
		if (!static::$strict){
			foreach ($values as $i => $v){
				if (array_key_exists($i, $props)) continue;
				$this->_values[$i] = $v;
			}
		}

		// save the current values
		$this->_updateAutoProps();
		$this->_apply('onInit');
	}

	public function &__get ($name){
		return $this->_get($name);
	}

	public function __set ($name, $value){
		$this->isDirty($name);
		return $this->_set($name, array($value));
	}

	public function __call ($name, $params){
		// simple getter, with the property name
		if ($prop = self::prop($name)){
			return $this->_get($name, $params);
		}

		// TODO deal with prefix/suffix (get, set, is, to...Json);
		$match = to_match($name, '/^(get|set|is|load|add|remove|to)?([A-Z][a-zA-Z0-9_-]*?)(Json)?$/');
		if (!isset($match[1])){
			err("Method \"{$name}\" doens't exists");
			return;
		}
		
		$old    = $name;
		$prefix = strtolower($match[0]);
		$suffix = strtolower(isset($match[2]) ? $match[2] : '');
		$name   = $prefix === 'is' ? $old : $match[1];
		$name   = to_slug($name, 'underscore');

		if (!($prop = self::prop($name))){
			err("Method \"{$old}\" doens't exists");
			return;
		}

		if ($prefix === 'get' || ($prefix == 'is' && empty($params))){
			return $this->_get($name, $params);
		}else if ($prefix === 'set' || $prefix === 'is'){
			if (empty($params)){
				err("Method \"{$old}\" won't work without any parameters");
				return;
			}
			return $this->_set($name, $params);
		}else if ($prefix === 'to' && $suffix === 'json'){
			// TODO toXXXJson function
		}else if ($prefix === 'add' && $prop['type'] === self::PROPS_RELATIONSHIP){
			$this->_load($name);
			// $this->_dirty = true;
			// TODO addXXX function
		}else if ($prefix === 'remove' && $prop['type'] === self::PROPS_RELATIONSHIP){
			$this->_load($name);
			// $this->_dirty = true;
			// TODO removeXXX function
		}

		return $this;
	}

	public function __debugInfo (){
		return $this->_values;
	}

	// methods:crud ------------------------------------------------------------
	
	// methods:helpers ---------------------------------------------------------
	public function prepare ($value){
		return Storage::instance()->prepare($value);
	}

	// methods:crud ------------------------------------------------------------
	public function relationship ($is_single=null, $proxy=null, $model=null){
		$prop   = self::_decodeRelationship($is_single, $proxy, $model);
		$values = $this->_load($prop);
		return $values;		
	}

	public function load ($structure='*', $args=''){
		$structure = string_decode_structure($structure);
		$props     = self::props();
		foreach ($props as $i => $prop){
			$struct = array_key_exists($i, $structure) ? $structure[$i] : null;
			if (!isset($struct) && !array_key_exists('*', $structure)){
				continue;
			}

			$values = $this->_get($prop, $struct ? $struct['params'] : array());

			if ($struct && count($struct['children'])){
				foreach ($struct['children'] as $ii => $vv){
					// TODO children
				}
			}
		}
	}

	public function update ($values, $save=false, $deep=false){
		$values = static::_values($values);
		if (empty($values)) return $this;

		// remove the ID, it can't be updated
		unset($values['id']);

		foreach ($values as $i => $v){
			$method1 = to_slug("set-{$i}", 'camel');
			$method2 = strpos($i, 'is') === 0 ? to_slug($i, 'camel') : null;

			if (method_exists($this, $method1)){
				$this->{$method1}($v);
			}else if (method_exists($this, $method2)){
				$this->{$method2}($v);
			}else{
				$this->{$i} = $v;
			}
		}

		$this->_updateAutoProps();
		$save && $this->save($deep);
		
		return $this;
	}

	public function save ($args=null){
		//$this->_log("Saving \"{$this->_model}\" #{$this->id}".($this->_dirty ? ' (dirty)' : ''));

		$args = to_args($args, array(
			'deep'   => false,   // go deep in the children
			'return' => true,    // true=$this, values=$values, id
		), 'deep');

		// make sure the relationships are or aren't dirty
		$props = self::props();
		foreach ($props as $i => $prop){
			if ($prop['type'] === self::PROPS_RELATIONSHIP){
				$value = $this->_values[$i];

				// TODO make sure it's a model OR models
				// if (!is_a($value, 'Models')){
				// 	$value = $this->{$i} = new Models($prop['model']['class'], $value);
				// }
				
				if ($value->isDirty()){
					$this->isDirty($i);
				}
			}else{
				if ($prop['auto']){
					$this->isDirty($i);
				}
			}
		}
		
		// TODO deal with "deep" args
		
		if ($this->_dirty){
			$is_update = ($this->id !== null);

			if ($is_update)	$this->_apply('onPreUpdate');
			else			$this->_apply('onPreInsert');
			$this->_apply('onPreSave');

			// TODO lifecycle, still using those ?
			

			$relationships = array();
			$values        = array();
			foreach ($this->_dirty as $i){
				$prop  = self::prop($i);
				$value = $this->_encode($prop, $this->_values[$i]);
				
				if ($prop['type'] === self::PROPS_RELATIONSHIP){
					// make sure they are saved
					$value->save();

					// need to save links AFTER insert
					if ($prop['is_single'] && !$prop['proxy']){
						// only save the "single" values
						foreach ($prop['model']['query'] as $query){
							if (!$query['from']) continue;
							$i 			= $query['from_key'];
							$v 			= get_value($value, $query['to_key']);
							$values[$i] = $v;
						}
					}else{
						$relationships[] = [
							'prop'  => $prop,
							'value' => $value
						];
					}
				}else{
					$values[$i] = $value;
				}
			}

			if ($is_update){
				self::$_storage->update(static::$table, $this->id, $values);
				$this->_apply('onUpdate');
			}else{
				// @info there can be cases where the Model doesn't have a linked table in the database
				if (isset(static::$table)){
					// TODO if the number as "auto=true" then it should increment
					$this->id = self::$_storage->insert(static::$table, $values);
				}else{
					$this->id = -1;
				}
				$this->_apply('onInsert');
			}

			foreach ($relationships as $i => $v){
				$prop     = $v['prop'];
				$children = $v['value']->array();

				// save proxy items
				if ($prop['proxy']){
					$deletes = [];
					$inserts = [];
					$values  = [];

					foreach ($prop['proxy']['query'] as $query){
						$i = $query['to_key'];

						if ($query['to_value']){
							$v = $query['to_value'];
						}else{
							$v = $this->_values[$query['from_key']];
						}

						$deletes[$i] = $v;
						$values[$i] = $v;
					}
	
					foreach ($prop['model']['query'] as $query){
						$i = $query['from_key'];

						foreach ($children as $ii => $child){
							if ($query['to_value']){
								$v = $query['to_value'];
							}else{
								$v = $child->_values[$query['to_key']];
							}

							if (!isset($inserts[$ii])){
								$inserts[$ii] = array_merge(array(), $values);
							}

							$inserts[$ii][$i] = $v;
						}
					}

					// delete all the proxies
					$model = self::$_storage->model($prop['proxy']['class']);
					self::$_storage->delete($model['table'], $deletes);
					
					// insert new proxies
					$model = self::$_storage->model($prop['proxy']['class']);
					self::$_storage->insert($model['table'], $inserts);
				}else{
					$updates_remove = [];
					$updates_add    = [];

					foreach ($prop['model']['query'] as $query){
						$i = $query['to_key'];

						foreach ($children as $child){
							$ii = $child->id;

							if ($query['to_value']){
								$v = $query['to_value'];
							}else{
								$v = $this->_values[$query['from_key']];
							}

							if (!isset($updates_add[$ii])){
								$updates_add[$ii] = array();
							}

							$updates_add[$ii][$i] = $v;
						}

						$v 					= $this->_values[$query['from_key']];
						$updates_remove[$i] = $v;
					}

					$model = self::$_storage->model($prop['model']['class']);

					// remove old
					if (count($updates_remove)){
						$set   = array_map(function (){ return NULL; }, $updates_remove);
						$where = $updates_remove;
						self::$_storage->update($model['table'], $set, $where);
					}

					// update models
					foreach ($updates_add as $id => $update){
						self::$_storage->update($model['table'], $id, $update);
					}
				}
			}
		}

		$this->isDirty(false);

		if ($args['return'] === 'values'){
			return $values;
		}else if ($args['return'] === 'id'){
			return $this->id;
		}else{
			return $this;
		}
	}

	public function delete ($deep=true){
		$this->_apply('onPreDelete');

		isset(static::$table) && self::$_storage->delete(static::$table, array(
			'id' => $this->id
		));

		if ($deep){
			// TODO delete children under
		}

		$this->_apply('onDelete');
		
		return $this;
	}

	public function isActive ($value=null){
		if (is_bool($value)){
			$this->is_active = $value;
			$this->dt_delete = $value ? null : strtotime('now');
		}else{
			return $this->is_active;
		}
		return $this;
	}

	public function deactivate (){
		$this->isActive(false);
		$this->save();
		return $this;
	}

	public function activate (){
		$this->isActive(true);
		$this->save();
		return $this;
	}

	// methods -----------------------------------------------------------------
	public function isNew (){
		return $this->id === null;
	}

	public function isDirty ($name){
		if ($name === false){
			$this->_dirty = false;	
		}elseif (self::prop($name)){
			$this->_dirty = ($this->_dirty ? $this->_dirty : array());
			if (!in_array($name, $this->_dirty)){
				$this->_dirty[] = $name;
			}
		}
		return !empty($this->_dirty);
	}

	// public function has ($name){
	// 	return isset($this->_values[$name]);
	// }

	public function type ($is=null){
		$class = get_called_class();

		if (is_string($is)){
			$class = strtolower($class);
			$is    = strtolower($is);
			return $class === $is;
		}

		return $class;
	}

	public function clone ($values=array()){
		$clone 		= clone $this;
		$clone->id 	= null;
		$clone->update($values);
		return $clone;
	}

	public function json ($structure='*'){
		return self::_json($this, $structure);
	}

	public function cache ($key, $value=null){
		$key = '$' . $key;
		if ($value === null){
			return array_key_exists($key, $this->_cache) ? $this->_cache[$key] : null;
		}else{
			$this->_cache[$key] = $value;
			return $this;
		}
	}

	public function value ($key=null, $fallback=null){
		if ($key === null){
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$last  = array_pop($trace);
			$key   = isset($last['function']) ? $last['function'] : null;
		}
		return $key && array_key_exists($key, $this->_values) ? $this->_values[$key] : $fallback;
	}
	// public function v ($key){
	// 	return array_key_exists($key, $this->_values) ? $this->_values[$key] : null;
	// }

	// methods:props -----------------------------------------------------------
}

class Models implements ArrayAccess, Iterator, Countable{
	private $_model = null;
	private $_index = 0;
	private $_items = [];
	private $_dirty = false;
	
	// private -----------------------------------------------------------------
	private function _item ($v){
		if (!is_a($v, $this->_model)){
			$v = new $this->_model($v);
		}
		return $v;
	}

	// init --------------------------------------------------------------------
	public function __construct($model, $items=null){
		$this->_model = $model;

		if (!empty($items)){
			foreach ($items as $item){
				$this->_items[] = $this->_item($item);
			}
		}
	}

	public function &__get ($i){
        return $this->_items[$i];
    }

    public function __set ($i, $v){
		$this->isDirty(true);
		$this->_items[$i] = $this->_item($v);
    }

    public function __isset ($i) {
        return isset($this->_items[$i]);
    }

    public function __unset ($i) {
        unset($this->_items[$i]);
    }

	public function __debugInfo (){
		return $this->_items;
	}

	// Array access
    public function offsetSet ($i, $v): void{
		$this->isDirty(true);

		if (is_null($i)) {
            $this->_items[] = $this->_item($v);
        }else{
			$this->_items[$i] = $this->_item($v);
        }
    }

    public function offsetExists ($i): bool {
        return isset($this->_items[$i]);
    }

    public function offsetUnset ($i): void {
        if ($this->offsetExists($i)) {
            unset($this->_items[$i]);
        }
    }

    public function &offsetGet($i): mixed {
        return $this->offsetExists($i) ? $this->_items[$i] : null;
    }

	// Countable
	public function count (): int{
		return count($this->_items);
	}

	// Iterator
	public function key (): mixed{
        return $this->_index;
    }

	public function next (): void{
        ++$this->_index;
    }

	public function rewind (): void{
        $this->_index = 0;
    }

	public function valid (): bool{
        return isset($this->_items[$this->_index]);
    }

	public function current (): mixed{
        return $this->_items[$this->_index];
    }

	public function first (){
        return reset($this->_items);
    }

	public function last (){
        return end($this->_items);
    }

	// methods -----------------------------------------------------------------
	public function save ($deep=false){
		// todo
		// $this->_log('Saving ' . $this->count() . ' Models "' . $this->_model . '"');
	}

	public function update (){

	}

	public function delete (){
		// TODO
	}

	public function array ($filter=null){
		$items = (array)$this->_items;
		$items = $filter ? array_each($items, $filter) : $items;
		return $items;
	}

	public function json ($structure='*'){
		$list = [];
		foreach ($this->_items as $item){
			$list[] = $item->json($structure);
		}
		return $list;
	}

	public function isDirty ($value=null){
		if (is_bool($value)){
			$this->_dirty = $value;
		}
		return !!$this->_dirty;
	}
}