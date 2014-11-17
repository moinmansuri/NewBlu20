<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Form Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_form_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_form_model extends Freeform_Model
{
	public $custom_field_info 			= array(
		'type'		=> 'TEXT',
		'null'		=> TRUE
	);

	public $default_form_table_columns = array(
		'entry_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'auto_increment'	=> TRUE
		),
		'site_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 1
		),
		'author_id'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'complete'			=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 1,
			'null'				=> FALSE,
			'default'			=> 'y'
		),
		'ip_address'		=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 40,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'entry_date'		=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'edit_date'			=> array(
			'type'				=> 'INT',
			'constraint'		=> 10,
			'unsigned'			=> TRUE,
			'null'				=> FALSE,
			'default'			=> 0
		),
		'status'			=> array(
			'type'				=> 'VARCHAR',
			'constraint'		=> 50
		),
	);


	//crud listeners
	public $after_get 			= array('parse_form_strings');

	public $before_insert 		= array('before_insert_add');
	public $after_insert		= array('create_form_table');

	public $before_update 		= array('before_update_add');
	public $after_update		= array('create_form_table');

	public $after_delete		= array('remove_entry_tables');


	// --------------------------------------------------------------------

	/**
	 * Parse Form Strings
	 *
	 * decodes settings and unpipes field ids
	 *
	 * @access protected
	 * @param  $data 	array  array of data rows
	 * @param  $all 	boolean all?
	 * @return array       affected data rows
	 */

	protected function parse_form_strings ($data, $all)
	{
		foreach ($data as $k => $row)
		{
			if (isset($row['settings']) AND $row['settings'] !== NULL)
			{
				$settings = json_decode($row['settings'], TRUE);

				$data[$k]['settings'] = (is_array($settings)) ? $settings : array();
			}

			if (isset($data[$k]['field_ids']) AND $data[$k]['field_ids'] !== NULL)
			{
				$data[$k]['field_ids'] 	= $this->pipe_split($row['field_ids']);
			}
		}

		return $data;
	}
	//END parse_form_strings


	// --------------------------------------------------------------------

	/**
	 * Before insert add
	 *
	 * adds site id and entry date to insert data
	 *
	 * @access protected
	 * @param  array $data array of insert data
	 * @return array       affected data
	 */

	protected function before_insert_add ($data)
	{
		if ( ! isset($data['entry_date']))
		{
			$data['entry_date'] = $this->localize->now;
		}

		if ( ! isset($data['site_id']))
		{
			$data['site_id'] = $this->config->item('site_id');
		}

		return $data;
	}
	//END before_insert_add


	// --------------------------------------------------------------------

	/**
	 * Before update add
	 *
	 * adds site id and edit date to update data
	 *
	 * @access protected
	 * @param  array $data array of update data
	 * @return array       affected data
	 */

	protected function before_update_add ($data)
	{
		if ( ! isset($data['edit_date']))
		{
			$data['edit_date'] = $this->localize->now;
		}

		return $data;
	}
	//END before_update_add


	// --------------------------------------------------------------------

	/**
	 * Remove Entry Tables
	 *
	 * Removes a form and its corosponding entries table
	 *
	 * @access	protected
	 * @param 	array 	$form_ids ids of form entry tables to delete
	 * @return  null
	 */

	protected function remove_entry_tables ($form_ids)
	{
		$this->load->dbforge();

		if ( ! is_array($form_ids))
		{
			if ( ! $this->is_positive_intlike($form_ids)){ return; }

			$form_ids = array($form_ids);
		}

		foreach ($form_ids as $form_id)
		{
			$table_name = $this->table_name($form_id);

			//need to check else this fails on error :|
			if ($this->db->table_exists($table_name))
			{
				$this->dbforge->drop_table($table_name);
			}
		}
	}
	//end remove_entry_tables


	// --------------------------------------------------------------------

	/**
	 * table_name
	 *
	 * returns the entris table name of the from id passed
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	string  table name with form iD replaced
	 */

	public function table_name ($form_id)
	{
		return str_replace(
			'%NUM%',
			$form_id,
			$this->form_table_nomenclature
		);
	}
	//END table_name


	// --------------------------------------------------------------------

	/**
	 * create_form_table
	 *
	 * @access	public
	 * @param 	int 	form_id number
	 * @return	bool 	success
	 */

	public function create_form_table ($form_id)
	{
		if (is_array($form_id))
		{
			$form_id = array_pop($form_id);
		}

		if ( ! $this->is_positive_intlike($form_id)){ return FALSE; }

		$this->load->dbforge();

		$table_name = $this->table_name($form_id);

		//if the table doesn't exist, lets create it with all of the default fields
		if ( ! $this->db->table_exists($table_name))
		{
			//adds all defaults and sets primary key
			$this->dbforge->add_field($this->default_form_table_columns);
			$this->dbforge->add_key('entry_id', TRUE);

			$this->dbforge->create_table($table_name, TRUE);

			return TRUE;
		}
		//oops!
		//TODO: check for major fields or error differently?
		else
		{
			return FALSE;
		}
	}
	//END create_form_table


	// --------------------------------------------------------------------

	/**
	 * forms_with_field_id
	 *
	 * @access	public
	 * @param	number 		$field_id 	id of field that info is needed for
	 * @param   boolean  	$use_cache	use cache?
	 * @return	array 					data of forms by id
	 */

	public function forms_with_field_id ($field_id, $use_cache = TRUE)
	{
		if ( ! $this->is_positive_intlike($field_id)){ return FALSE; }

		//cache?
		$cache = $this->cacher(func_get_args(), __FUNCTION__);
		if ($use_cache AND $cache->is_set()){ return $cache->get(); }

		// --------------------------------------------
		//  get form info
		// --------------------------------------------

		$field_id 	= $this->db->escape_str($field_id);

		$sql = "SELECT	*
				FROM	exp_freeform_forms
				WHERE	field_ids
				REGEXP	('^$field_id$|^$field_id\\\\||\\\\|$field_id\\\\||\\\\|$field_id$')";

		//e.g. finds '1' in (1, 1|2|3, 3|2|1, 2|1|3) but not in (10|11|12, 10, etc..)
		$query		= $this->db->query($sql);

		$cache->set(FALSE);

		//get form info
		if ($query->num_rows() > 0)
		{
			$cache->set($this->prepare_keyed_result($query->result_array(), 'form_id'));
		}

		return $cache->get();
	}
	//END forms_with_field_id
}
//END Freeform_form_model