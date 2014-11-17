<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Field Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_field_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_field_model extends Freeform_Model
{
	public $before_get = array('sort_get_on_name');


	// --------------------------------------------------------------------

	/**
	 * Sort fieldname output by field name
	 *
	 * @access public
	 * @param  array $where wheres for get()
	 * @return array        adjusted wheres
	 */

	public function sort_get_on_name ($where)
	{
		$stash = ($this->isolated) ? 'db_isolated_stash' : 'db_stash';

		$has_orderby = FALSE;

		foreach ($this->{$stash} as $call)
		{
			if ($call[0] == 'order_by')
			{
				$has_orderby = TRUE;
			}
		}

		if ( ! $has_orderby)
		{
			$this->order_by('field_name', 'asc');
		}

		return $where;
	}
	//END sort_get_on_name


	// --------------------------------------------------------------------

	/**
	 * get_column_name
	 *
	 * @access	public
	 * @param 	string 	the item to search
	 * @param 	string  search on id or name
	 * @return	string
	 */

	public function get_column_name ($search, $on = 'id')
	{
		$result	= FALSE;
		$row	= FALSE;

		//why check ID if we have ID?
		//need to know if it exists
		if ($on == 'id' AND $this->is_positive_intlike($search))
		{
			$row = $this->get_row($search);
		}
		else if ($on == 'name' AND is_string($search))
		{
			$row = $this->get_row(array('field_name' => $search));
		}

		if ($row and isset($row['field_id']))
		{
			$result = $this->form_field_prefix . $row['field_id'];
		}

		return $result;
	}
	//END get_column_name
}
//END Freeform_field_model