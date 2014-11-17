<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Base Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_model.php
 */

if ( ! class_exists('Freeform_cacher'))
{
	require_once realpath(rtrim(dirname(__FILE__), "/") . '/../libraries/Freeform_cacher.php');
}

class Freeform_Model extends CI_Model
{
	//added these here because multiple items need the form_name
	//we don't add exp_ here because DBForge adds it either way? ok :|
	public $form_table_nomenclature		= 'freeform_form_entries_%NUM%';
	public $form_field_prefix			= 'form_field_';
	public $cache_enabled				= TRUE;

	protected $class 					= __CLASS__;
	protected $isolated 				= FALSE;
	protected $keyed 					= FALSE;

	public $_table;
	public $id;
	public $root_name 					= '';
	public $dbprefix 					= 'exp_';
	public $swap_pre 					= '';

	// -------------------------------------
	//	redoing redundant driver data?
	//	Yes, because EE's version of CI
	//	makes them private with no way to retrive
	//	or use the functions that utilize them
	// -------------------------------------

	//default to mysql, but if another drive is found
	//we will reset in the constructor
	public $_random_keyword 			= ' RAND()';
	public $_escape_char 				= '`';
	public $_count_string 				= 'SELECT COUNT(*) AS ';
	public $_like_escape_str			= '';
	public $_like_escape_chr			= '';

	public $driver_info 				= array(
		'mssql' 	=> array(
			'_random_keyword' 		=> ' ASC',
			'_escape_char' 			=> '',
			'_count_string' 		=> "SELECT COUNT(*) AS ",
			'_like_escape_str'		=> " ESCAPE '%s' ",
			'_like_escape_chr'		=> '!'
		),
		'mysql' 	=> array(
			'_random_keyword' 		=> ' RAND()',
			'_escape_char' 			=> '`',
			'_count_string' 		=> 'SELECT COUNT(*) AS ',
			'_like_escape_str'		=> '',
			'_like_escape_chr'		=> ''
		),
		'mysqli' 	=> array(
			'_random_keyword' 		=> ' RAND()',
			'_escape_char' 			=> '`',
			'_count_string' 		=> 'SELECT COUNT(*) AS ',
			'_like_escape_str'		=> '',
			'_like_escape_chr'		=> ''
		),
		'postgre' 	=> array(
			'_random_keyword' 		=> ' RANDOM()',
			'_escape_char' 			=> '"',
			'_count_string' 		=> "SELECT COUNT(*) AS ",
			'_like_escape_str'		=> " ESCAPE '%s' ",
			'_like_escape_chr'		=> '!'
		),
		'sqlite' 	=> array(
			'_random_keyword' 		=> ' Random()',
			'_escape_char' 			=> '', //not needed for SQLite
			'_count_string' 		=> "SELECT COUNT(*) AS ",
			'_like_escape_str'		=> " ESCAPE '%s' ",
			'_like_escape_chr'		=> '!'
		),
	);

	public $_protect_identifiers	= TRUE;
	public $_reserved_identifiers	= array('*');

	// -------------------------------------
	//	observer groups
	// -------------------------------------

	public $before_count				= array();

	public $before_get					= array();
	public $after_get					= array();

	public $before_insert				= array();
	public $after_insert				= array();

	public $before_update				= array();
	public $after_update				= array();

	public $before_delete				= array();
	public $after_delete				= array();

	// -------------------------------------
	//	stashes
	// -------------------------------------

	public $db_stash					= array();
	public $db_isolated_stash			= array();


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access public
	 * @return object $this
	 */

	public function __construct ()
	{
		parent::__construct();

		$this->class 		= get_class($this);

		$this->root_name 	= strtolower(
			preg_replace("/^Freeform_(.*?)_model/is", '$1', $this->class)
		);

		if ( ! $this->id)
		{
			$this->id 			= $this->root_name . '_id';
		}

		if ( ! $this->_table)
		{
			$this->load->helper('inflector');
			$this->_table = 'freeform_' . trim(plural($this->root_name), '_');
		}

		$this->dbprefix = $this->db->dbprefix;

		//set db items if different than mysql and available
		if (array_key_exists($this->db->dbdriver, $this->driver_info))
		{
			foreach ($this->driver_info[$this->db->dbdriver] as $key => $value)
			{
				$this->{$key} = $value;
			}
		}
	}
	//END __construct


	// --------------------------------------------------------------------
	//	CRUD
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * count
	 *
	 * @access public
	 * @param  mixed  	$where 		mixed. if single, id =>, if array, where =>
	 * @param  boolean 	$cleanup   	cleanup and deisolate?
	 * @return int         			count of all results
	 */

	public function count ($where = array(), $cleanup = TRUE)
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return 0; }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	wheres
		// -------------------------------------

		$where = $this->observe('before_count', $where);

		// -------------------------------------
		//	cache
		// -------------------------------------

		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$cache = $this->cacher(array(func_get_args(), $this->{$stash}, $where), __FUNCTION__);

		if ($this->cache_enabled AND $cache->is_set())
		{
			if ($cleanup){ $this->reset()->deisolate();	}

			return $cache->get();
		}

		// -------------------------------------
		//	get
		// -------------------------------------

		$this->wheres($where);

		$this->run_stash();

		$count = $this->db->count_all_results($this->_table);

		if ($cleanup)
		{
			//cleanup
			$this->reset()->deisolate();
		}

		return $cache->set($count);
	}
	//END count


	// --------------------------------------------------------------------

	/**
	 * Count all results
	 *
	 * @access public
	 * @return int  	total count of all results in table
	 */

	public function count_all ($cleanup = TRUE)
	{
		//	cache
		$cache = $this->cacher(func_get_args(), __FUNCTION__);
		if ($this->cache_enabled AND $cache->is_set()){return $cache->get();}

		$count = $this->db->count_all($this->_table);

		if ($cleanup)
		{
			//cleanup
			$this->reset()->deisolate();
		}

		return $cache->set($count);
	}
	//END count_all


	// --------------------------------------------------------------------

	/**
	 * Get
	 *
	 * @access public
	 * @param  mixed  	$where 		mixed. if single, id =>, if array, where =>
	 * @param  boolean 	$cleanup   	cleanup and deisolate?
	 * @param  boolean 	$all 		return all results? (mainly used for)
	 * @return array    			array of data or array of rows if $all
	 */

	public function get ($where = array(), $cleanup = TRUE, $all = TRUE)
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return FALSE; }

			$where = array($this->id => $where);
		}

		$this->wheres($where);

		// -------------------------------------
		//	wheres
		// -------------------------------------

		$where = $this->observe('before_get', $where, $all);

		// -------------------------------------
		//	cache
		// -------------------------------------

		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		//where is reset here because its not the same as arg where
		$cache = $this->cacher(
			array(func_get_args(), $this->{$stash}, $all, $this->keyed),
			__FUNCTION__
		);

		if ($this->cache_enabled AND $cache->is_set())
		{
			if ($cleanup){ $this->reset()->deisolate();	}

			return $cache->get();
		}

		// -------------------------------------
		//	get
		// -------------------------------------

		if ( ! $all)
		{
			$this->limit(1);
		}

		$this->run_stash();

		$query = $this->db->get($this->_table);

		// -------------------------------------
		//	keyed?
		// -------------------------------------

		$keyed = $this->keyed;

		// -------------------------------------
		//	clean
		// -------------------------------------

		if ($cleanup)
		{
			$this->reset()->deisolate();
		}

		// -------------------------------------
		//	cache and go
		// -------------------------------------

		if ($query->num_rows() > 0 )
		{
			$rows = $this->observe('after_get', $query->result_array(), $all);

			//keyed result?
			if ($all AND $keyed)
			{

				$args = $keyed;
				array_unshift($args, $rows);
				$rows = call_user_func_array(array($this, 'prepare_keyed_result'), $args);
			}

			return $cache->set(( ! $all) ? $rows[0] : $rows);
		}
		else
		{
			return $cache->set(FALSE);
		}
	}
	//END get


	// --------------------------------------------------------------------

	/**
	 * Get Row
	 *
	 * Same as get but returns just the first row
	 *
	 * @access	public
	 * @param 	mixed 	$where 		the id of the form or an array of wheres
	 * @param   boolean $cleanup   	cleanup and deisolate?
	 * @return	array 				data from the form id or an empty array
	 */

	public function get_row ($where = array(), $cleanup = TRUE)
	{
		return $this->get($where, $cleanup, FALSE);
	}
	//get_all


	// --------------------------------------------------------------------

	/**
	 * Insert Data
	 *
	 * @access public
	 * @param  array 	$data 	array of data to insert
	 * @return mixed       		id if success, boolean false if not
	 */

	public function insert ($data)
	{
		$data = $this->observe('before_insert', $data);

		$success = $this->db->insert($this->_table, $data);

		if ($success)
		{
			Freeform_cacher::clear($this->class);

			$id = $this->db->insert_id();

			$this->observe('after_insert', $id);

			$return = $id;
		}
		else
		{
			$return = FALSE;
		}

		//just in case
		$this->reset()->deisolate();

		return $return;
	}
	//END insert


	// --------------------------------------------------------------------

	/**
	 * Update
	 *
	 * @access 	public
	 * @param  	mixed  	$where 	mixed. if single, id =>, if array, where =>
	 * @param  	array 	$data 	array of data to update
	 * @return 	mixed       	id if success, boolean false if not
	 */

	public function update ($data, $where = array())
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return FALSE; }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	update
		// -------------------------------------

		//run listener
		$data = $this->observe('before_update', $data);

		$ids = $this->get_ids($where);

		$this->wheres($where);

		$this->run_stash();

		$success = $this->db->update($this->_table, $data);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($success)
		{
			Freeform_cacher::clear($this->class);

			$this->observe('after_update', $ids);

			$return = TRUE;
		}
		else
		{
			$return = FALSE;
		}

		//just in case
		$this->reset()->deisolate();

		return $return;
	}
	//END update


	// --------------------------------------------------------------------

	/**
	 * delete
	 *
	 * @access 	public
	 * @param  	mixed  	$where 	mixed. if single, id =>, if array, where =>
	 * @return 	mixed       	id if success, boolean false if not
	 */

	public function delete ($where = array())
	{
		// -------------------------------------
		//	validate
		// -------------------------------------

		if ( ! is_array($where))
		{
			//if its not an array and not an INT we cannot use it :p
			if ( ! $this->is_positive_intlike($where)){	return array(); }

			$where = array($this->id => $where);
		}

		// -------------------------------------
		//	make sure the return function has Ids
		//	to work with done first in case a query
		//	or some such gets added
		// -------------------------------------

		$ids = $this->get_ids($where);

		// -------------------------------------
		//	delete
		// -------------------------------------

		$this->wheres($where);

		$this->run_stash();

		$success = $this->db->delete($this->_table);

		//just in case
		$this->reset()->deisolate();

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($success)
		{
			Freeform_cacher::clear($this->class);

			$this->observe('after_delete', $ids);

			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	//END delete


	// --------------------------------------------------------------------
	//	utilities
	// --------------------------------------------------------------------
	// 	Some functions here are repeated from elsehwere in the Freeform
	// 	code but this is intention so models can be more standalone
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Reset selections, etc
	 *
	 * @access public
	 * @return object $this returns self for chaining
	 */

	public function reset ()
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$this->{$stash} = array();
		$this->swap_pre = '';
		$this->keyed 	= FALSE;

		return $this;
	}
	//END reset


	// --------------------------------------------------------------------

	/**
	 * Set results to be keys
	 *
	 * @access public
	 * @param  string $key key to sort on
	 * @param  string $val return a value instead?
	 * @return object      $this for chaining
	 */

	public function key ($key = '', $val = '')
	{
		$args = func_get_args();

		if ($val !== '')
		{
			$this->select(implode(', ', $args));
		}

		$this->keyed = $args;

		return $this;
	}
	//END key


	// --------------------------------------------------------------------

	/**
	 * Prepare keyed result
	 *
	 * Take a query object and return an associative array. If $val is empty,
	 * the entire row per record will become the value attached to the indicated key.
	 *
	 * For example, if you do a query on exp_channel_titles and exp_channel_data
	 * you can use this to quickly create an associative array of channel entry
	 * data keyed to entry id.
	 *
	 * @access	public
	 * @param 	array 	$rows data rows to sort
	 * @param   string $key
	 * @return	mixed
	 */

	public function prepare_keyed_result ( $rows, $key = '', $val = '' )
	{
		if ( ! is_array( $rows )  OR $key == '' ) return FALSE;

		// --------------------------------------------
		//  Loop through query
		// --------------------------------------------

		$data	= array();

		foreach ( $rows as $row )
		{
			if ( isset( $row[$key] ) === FALSE ) continue;

			$data[ $row[$key] ]	= ( $val != '' AND isset( $row[$val] ) ) ? $row[$val]: $row;
		}

		return ( empty( $data ) ) ? FALSE : $data;
	}
	// END prepare_keyed_result


	// --------------------------------------------------------------------

	/**
	 * Cacher
	 * This is abstracted because some models might need to make different
	 * use of it
	 *
	 * Table is mixed in by default because some models might change the table
	 *
	 * @access protected
	 * @param  array 	$args 	arguments from func_get_args();
	 * @param  string 	$func 	function name from __FUNCTION__
	 * @return object       	new cacher instance
	 */

	protected function cacher ($args, $func)
	{
		return new Freeform_cacher(array($args, $this->_table), $func, $this->class);
	}
	//END cacher


	// --------------------------------------------------------------------

	/**
	 * Clear class cache
	 *
	 * @access public
	 * @param  string $func function to clear on, else clears entire class
	 * @return object       returns $this for chaining
	 */

	public function clear_cache ($func = '')
	{
		if (trim($func) !== '')
		{
			Freeform_cacher::clear($this->class, $func);
		}
		else
		{
			Freeform_cacher::clear($this->class);
		}

		return $this;
	}
	//end clear_cache


	// --------------------------------------------------------------------

	/**
	 * Any direct calls that are AR like db calls we stash
	 * and will call when we call the get/insert/etc.
	 *
	 * This will also allow interupt and override.
	 *
	 * @access public
	 * @param  string $method    method name
	 * @param  array  $arguments args
	 * @return object            if its db callable, return self for chain
	 */

	public function __call ($method, $arguments)
	{
		if (is_callable(array($this->db, $method)))
		{
			$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

			$this->{$stash}[] = array($method, $arguments);

			return $this;
		}
		else
		{
			trigger_error(
				str_replace(
					array('%class%', '%method%'),
					array($this->class, $method),
					lang('call_to_undefined_method')
				),
				E_USER_ERROR
			);
		}
	}
	//END __call


	// --------------------------------------------------------------------

	/**
	 * Run stashed db calls
	 *
	 * @access protected
	 * @return object		$this for chaining
	 */

	protected function run_stash ($reset_after_call = FALSE)
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		foreach ($this->{$stash} as $call)
		{
			$method 	= $call[0];
			$arguments 	= $call[1];

			//db method?
			if (is_callable(array($this->db, $method)))
			{
				call_user_func_array(array($this->db, $method), $arguments);
			}
			//local method?
			else if (is_callable(array($this, $method)))
			{
				call_user_func_array(array($this, $method), $arguments);
			}
		}

		if ($reset_after_call)
		{
			$this->reset();
		}

		return $this;
	}
	//END run_stash


	// --------------------------------------------------------------------

	/**
	 * Runs over the $where array and sends it to where_in if its an array
	 *
	 * @access 	protected
	 * @param  	array  $where array of where clauses
	 * @return 	object        $this for chaining
	 */

	protected function wheres ($where = array())
	{
		if ( ! empty($where))
		{
			foreach ($where as $find => $criteria)
			{
				if (is_array($criteria))
				{
					$this->where_in($find, $criteria);
				}
				else
				{
					$this->where($find, $criteria);
				}
			}
		}

		return $this;
	}
	//END wheres


	// --------------------------------------------------------------------

	/**
	 * Get the ids either from the current where
	 * Isolated in case a function needs to override
	 *
	 * @access protected
	 * @param  array $where  wheres from parent
	 * @return array         array of ids
	 */

	protected function get_ids ($where)
	{
		$ids = array();

		//if ID is not part of the query, we need it
		if (array_key_exists($this->id, $where))
		{
			$ids = $where[$this->id];

			if ( ! is_array($ids))
			{
				$ids = array($ids);
			}
		}

		return $ids;
	}
	//END get_ids


	// --------------------------------------------------------------------

	/**
	 * Sets $this->db to its own instance to avoid AR collision
	 *
	 * @access public
	 * @return object $this for chaining
	 */

	public function isolate ()
	{
		if ( ! $this->isolated)
		{
			$this->isolated = TRUE;
			$this->db 		= DB('default');
		}

		return $this;
	}
	//END isolate


	// --------------------------------------------------------------------

	/**
	 * UnSets $this->db from its own instance and parent __get falls back to
	 * $CI->db
	 *
	 * @access public
	 * @return object $this for chaining
	 */

	public function deisolate ()
	{
		if ($this->isolated)
		{
			$this->isolated = FALSE;
			$this->db->close();
			unset($this->db);
		}

		return $this;
	}
	//END deisolate


	// --------------------------------------------------------------------

	/**
	 * Run observer hooks on data
	 *
	 * @access protected
	 * @param  string 	$listener 	name of listener array to run
	 * @param  mixed 	$data     	data to iterate over
	 * @return mixed           		affected data
	 */

	protected function observe ($listener, $data)
	{
		//no funny stuff
		if ( ! isset($this->$listener) OR ! is_array($this->$listener))
		{
			return $data;
		}

		//everything after the first arg
		$args = func_get_args();
		array_shift($args);

		foreach ($this->$listener as $method)
		{
			if (is_callable(array($this, $method)))
			{
				$data = call_user_func_array(array($this, $method), $args);
			}
		}

		return $data;
	}
	//END observe


	// --------------------------------------------------------------------

	/**
	 * is positive intlike
	 *
	 * returns bool true if its numeric, an integer, not a bool,
	 * and equal to or above the threshold min
	 *
	 * (is_positive_entlike would have taken forever)
	 *
	 * @access 	protected
	 * @param 	mixed 	num 		number/int/string to check for numeric
	 * @param 	int 	threshold 	lowest number acceptable (default 1)
	 * @return  bool
	 */

	protected function is_positive_intlike ($num, $threshold = 1)
	{
		//without is_numeric, bools return positive
		//because preg_match auto converts to string
		return (
			is_numeric($num) AND
			preg_match("/^[0-9]+$/", $num) AND
			$num >= $threshold
		);
	}
	//END is_positive_intlike


	// --------------------------------------------------------------------

	/**
	 * Split a string by pipes with no empty items
	 * Because I got really tired of typing this.
	 *
	 * @access public
	 * @param  string $str pipe delimited string to split
	 * @return array      array of results
	 */

	public function pipe_split ($str)
	{
		return preg_split('/\|/', $str,	-1,	PREG_SPLIT_NO_EMPTY);
	}
	//END pipe_split




	// --------------------------------------------------------------------

	/**
	 * Clear Table
	 *
	 * Truncates table
	 *
	 * @access	public
	 * @return	object this for chaning
	 */
	public function clear_table ()
	{
		$this->db->truncate($this->_table);
		return $this;
	}
	//END clear_table


	// --------------------------------------------------------------------

	/**
	 * List fields
	 *
	 * @access	public
	 * @return	array of field names
	 */

	public function list_table_fields ($use_cache = TRUE)
	{

		//where is reset here because its not the same as arg where
		$cache = $this->cacher(array(), __FUNCTION__);

		if ($this->cache_enabled AND $use_cache AND $cache->is_set())
		{
			return $cache->get();
		}

		$p_table_name = (substr($this->_table, 0, strlen($this->dbprefix)) !== $this->dbprefix) ?
									$this->dbprefix . $this->_table :
									$table_name;

		$fields = array();

		$field_q = $this->db->query(
			"SHOW COLUMNS FROM " . $this->db->escape_str($p_table_name)
		);

		if ($field_q->num_rows() > 0)
		{
			foreach ($field_q->result_array() as $row)
			{
				if (isset($row['Field']))
				{
					$fields[] = $row['Field'];
				}
			}
		}

		return $cache->set($fields);
	}
	//END list_table_fields


	// --------------------------------------------------------------------
	//	Copied and modified AR functions because they are not
	//	public nor do they return the SQL instead of directly
	//	sending to the DB as a query or storing in private arrays.
	// --------------------------------------------------------------------
	//	This could be mitigated if the current version of CI in EE was up
	//	to date with the github version, but it appears to be an old fork
	//	and we sadly cannot trust what will and wont change so the only
	//	option was to copy and modify these functions locally as pulling
	//	in the newer AR driver from github just wouldn't work. :/ -gf
	// --------------------------------------------------------------------
	//	The below functions, even in thier slightly modified state,
	//	are derived from Codeigniter 2.0.1, which is Copyright (c)
	//	2008 - 2011, EllisLab, Inc. The license of which can be found
	//	here: https://github.com/EllisLab/CodeIgniter/blob/v2.1.0/license.txt
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------

	/**
	 * Where
	 *
	 * @access	public
	 * @param	mixed
	 * @param	mixed
	 * @return	array
	 */

	public function _where ($key, $value = NULL, $type = 'AND ', $escape = NULL)
	{
		if ( ! is_array($key))
		{
			$key = array($key => $value);
		}

		// If the escape value was not set will will base it on the global setting
		if ( ! is_bool($escape))
		{
			$escape = TRUE;
		}

		$where = array();

		foreach ($key as $k => $v)
		{
			if (is_null($v) && ! $this->_has_operator($k))
			{
				// value appears not to have been set, assign the test to IS NULL
				$k .= ' IS NULL';
			}

			if ( ! is_null($v))
			{
				if ($escape === TRUE)
				{
					$k = $this->_protect_identifiers($k, FALSE, $escape);

					$v = ' '.$this->db->escape($v);
				}

				if ( ! $this->_has_operator($k))
				{
					$k .= ' = ';
				}
			}
			else
			{
				$k = $this->_protect_identifiers($k, FALSE, $escape);
			}

			$where[] = $k.$v;
		}

		return $where;
	}
	//END _where


	// --------------------------------------------------------------------

	/**
	 * Where_in
	 *
	 * @access	public
	 * @param	string	The field to search
	 * @param	array	The values searched on
	 * @param	boolean	If the statement would be IN or NOT IN
	 * @return	string
	 */

	public function _where_in ($key = NULL, $values = NULL, $not = FALSE, $type = 'AND ')
	{
		if ($key === NULL OR $values === NULL)
		{
			return;
		}

		if ( ! is_array($values))
		{
			$values = array($values);
		}

		$not = ($not) ? ' NOT' : '';

		$where_in = array();

		foreach ($values as $value)
		{
			$where_in[] = $this->db->escape($value);
		}

		$where_in = $this->_protect_identifiers($key) .
						$not . " IN (" . implode(", ", $where_in) . ") ";

		return $where_in;
	}
	//END _where_in


	// --------------------------------------------------------------------

	/**
	 * Like
	 *
	 * Called by like() or orlike()
	 *
	 * @access	private
	 * @param	mixed
	 * @param	mixed
	 * @param	string
	 * @return	object
	 */

	function _like ($field, $match = '', $type = 'AND ', $side = 'both', $not = '')
	{
		if ( ! is_array($field))
		{
			$field = array($field => $match);
		}

		$like_statements = array();

		foreach ($field as $k => $v)
		{
			$k = $this->_protect_identifiers($k);

			$v = $this->db->escape_like_str($v);

			if ($side == 'before')
			{
				$like_statement = " $k $not LIKE '%{$v}'";
			}
			elseif ($side == 'after')
			{
				$like_statement = " $k $not LIKE '{$v}%'";
			}
			else
			{
				$like_statement = " $k $not LIKE '%{$v}%'";
			}

			// some platforms require an escape sequence
			// definition for LIKE wildcards
			if ($this->_like_escape_str != '')
			{
				$like_statement .= sprintf(
					$this->_like_escape_str,
					$this->_like_escape_chr
				);
			}

			$like_statements[] = $like_statement;
		}

		return $like_statements;
	}
	//END _like


	// --------------------------------------------------------------------

	/**
	 * Sets the ORDER BY value
	 *
	 * @access	public
	 * @param	string
	 * @param	string	direction: asc or desc
	 * @return	object
	 */

	public function _order_by ($orderby, $direction = '')
	{
		if (strtolower($direction) == 'random')
		{
			$orderby = ''; // Random results want or don't need a field name
			$direction = $this->random_keyword;
		}
		elseif (trim($direction) != '')
		{
			$direction = (in_array(
				strtoupper(trim($direction)),
				array('ASC', 'DESC'),
				TRUE
			)) ? ' ' . $direction : ' ASC';
		}

		if (strpos($orderby, ',') !== FALSE)
		{
			$temp = array();

			foreach (explode(',', $orderby) as $part)
			{
				$temp[] = trim($part);
			}

			$orderby = implode(', ', $temp);
		}
		else if ($direction != $this->_random_keyword)
		{
			$orderby = $this->_protect_identifiers($orderby);
		}

		$orderby_statement = $orderby.$direction;

		return $orderby_statement;
	}
	//END _order_by


	// --------------------------------------------------------------------

	/**
	 * Protect Identifiers
	 *
	 * This function is used extensively by the Active Record class, and by
	 * a couple functions in this class.
	 * It takes a column or table name (optionally with an alias) and inserts
	 * the table prefix onto it.  Some logic is necessary in order to deal with
	 * column names that include the path.  Consider a query like this:
	 *
	 * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
	 *
	 * Or a query with aliasing:
	 *
	 * SELECT m.member_id, m.member_name FROM members AS m
	 *
	 * Since the column name can include up to four segments (host, DB, table, column)
	 * or also have an alias prefix, we need to do a bit of work to figure this out and
	 * insert the table prefix (if it exists) in the proper position, and escape only
	 * the correct identifiers.
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @param	mixed
	 * @param	bool
	 * @return	string
	 */

	public function _protect_identifiers ($item, $prefix_single = FALSE, $protect_identifiers = NULL, $field_exists = TRUE)
	{
		if ( ! is_bool($protect_identifiers))
		{
			$protect_identifiers = $this->_protect_identifiers;
		}

		if (is_array($item))
		{
			$escaped_array = array();

			foreach($item as $k => $v)
			{
				$escaped_array[$this->_protect_identifiers($k)] = $this->_protect_identifiers($v);
			}

			return $escaped_array;
		}

		// Convert tabs or multiple spaces into single spaces
		$item = preg_replace('/[\t ]+/', ' ', $item);

		// If the item has an alias declaration we remove it and set it aside.
		// Basically we remove everything to the right of the first space
		$alias = '';
		if (strpos($item, ' ') !== FALSE)
		{
			$alias = strstr($item, " ");
			$item = substr($item, 0, - strlen($alias));
		}

		// This is basically a bug fix for queries that use MAX, MIN, etc.
		// If a parenthesis is found we know that we do not need to
		// escape the data or add a prefix.  There's probably a more graceful
		// way to deal with this, but I'm not thinking of it -- Rick
		if (strpos($item, '(') !== FALSE)
		{
			return $item.$alias;
		}

		// Break the string apart if it contains periods, then insert the table prefix
		// in the correct location, assuming the period doesn't indicate that we're dealing
		// with an alias. While we're at it, we will escape the components
		if (strpos($item, '.') !== FALSE)
		{
			$parts	= explode('.', $item);

			// Does the first segment of the exploded item match
			// one of the aliases previously identified?  If so,
			// we have nothing more to do other than escape the item
			if (in_array($parts[0], $this->ar_aliased_tables))
			{
				if ($protect_identifiers === TRUE)
				{
					foreach ($parts as $key => $val)
					{
						if ( ! in_array($val, $this->_reserved_identifiers))
						{
							$parts[$key] = $this->_escape_identifiers($val);
						}
					}

					$item = implode('.', $parts);
				}
				return $item.$alias;
			}

			// Is there a table prefix defined in the config file?  If not, no need to do anything
			if ($this->dbprefix != '')
			{
				// We now add the table prefix based on some logic.
				// Do we have 4 segments (hostname.database.table.column)?
				// If so, we add the table prefix to the column name in the 3rd segment.
				if (isset($parts[3]))
				{
					$i = 2;
				}
				// Do we have 3 segments (database.table.column)?
				// If so, we add the table prefix to the column name in 2nd position
				elseif (isset($parts[2]))
				{
					$i = 1;
				}
				// Do we have 2 segments (table.column)?
				// If so, we add the table prefix to the column name in 1st segment
				else
				{
					$i = 0;
				}

				// This flag is set when the supplied $item does not contain a field name.
				// This can happen when this function is being called from a JOIN.
				if ($field_exists == FALSE)
				{
					$i++;
				}

				// Verify table prefix and replace if necessary
				if ($this->swap_pre != '' && strncmp($parts[$i], $this->swap_pre, strlen($this->swap_pre)) === 0)
				{
					$parts[$i] = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $parts[$i]);
				}

				// We only add the table prefix if it does not already exist
				if (substr($parts[$i], 0, strlen($this->dbprefix)) != $this->dbprefix)
				{
					$parts[$i] = $this->dbprefix.$parts[$i];
				}

				// Put the parts back together
				$item = implode('.', $parts);
			}

			if ($protect_identifiers === TRUE)
			{
				$item = $this->_escape_identifiers($item);
			}

			return $item.$alias;
		}

		// Is there a table prefix?  If not, no need to insert it
		if ($this->dbprefix != '')
		{
			// Verify table prefix and replace if necessary
			if ($this->swap_pre != '' && strncmp($item, $this->swap_pre, strlen($this->swap_pre)) === 0)
			{
				$item = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $item);
			}

			// Do we prefix an item with no segments?
			if ($prefix_single == TRUE AND substr($item, 0, strlen($this->dbprefix)) != $this->dbprefix)
			{
				$item = $this->dbprefix.$item;
			}
		}

		if ($protect_identifiers === TRUE AND ! in_array($item, $this->_reserved_identifiers))
		{
			$item = $this->_escape_identifiers($item);
		}

		return $item.$alias;
	}
	//END _protect_indentifiers


	// --------------------------------------------------------------------

	/**
	 * Escape the SQL Identifiers
	 *
	 * This function escapes column and table names
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function _escape_identifiers ($item)
	{
		if ($this->_escape_char == '')
		{
			return $item;
		}

		foreach ($this->_reserved_identifiers as $id)
		{
			if (strpos($item, '.'.$id) !== FALSE)
			{
				$str = $this->_escape_char. str_replace('.', $this->_escape_char.'.', $item);

				// remove duplicates if the user already included the escape
				return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
			}
		}

		if (strpos($item, '.') !== FALSE)
		{
			$str = 	$this->_escape_char .
					str_replace(
						'.',
						$this->_escape_char . '.' . $this->_escape_char,
						$item
					) .
					$this->_escape_char;
		}
		else
		{
			$str = $this->_escape_char.$item.$this->_escape_char;
		}

		// remove duplicates if the user already included the escape
		return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
	}
	//END _escape_identifiers


	// --------------------------------------------------------------------

	/**
	 * Tests whether the string has an SQL operator
	 * utilized from ./codeigniter/system/database/DB_driver.php
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */

	public function _has_operator ($str)
	{
		$str = trim($str);
		if ( ! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str))
		{
			return FALSE;
		}

		return TRUE;
	}
	//END _has_operator

	// --------------------------------------------------------------------
	//	End Codeigniter derived functions. See above for details.
	// --------------------------------------------------------------------
}
//END Freeform_model