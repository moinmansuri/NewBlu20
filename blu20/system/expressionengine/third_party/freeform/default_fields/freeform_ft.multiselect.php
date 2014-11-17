<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Multi-Select Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.multiselect.php
 */

class Multiselect_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Multiselect',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A field that has a list of items that can have multiple selections.'
	);


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		$this->info['name'] 		= lang('default_multiselect_name');
		$this->info['description'] 	= lang('default_multiselect_desc');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */

	public function display_settings ($data = array())
	{
		$this->multi_item_row($data);
	}
	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Validate Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */

	public function validate_settings ($data = array())
	{
		return $this->validate_multi_item_row_settings($data);
	}
	//END validate_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_settings ($data = array())
	{
		return $this->save_multi_item_row_settings($data);
	}
	//END save_settings



	// --------------------------------------------------------------------

	/**
	 * Display Field
	 *
	 * @access	public
	 * @param	string 	saved data input
	 * @param  	array 	input params from tag
	 * @param 	array 	attribute params from tag
	 * @return	string 	display output
	 */

	public function display_field ($data = '', $params = array(), $attr = array())
	{
		//form_multiselect needs its options form preped to compare
		$data	= array_map('form_prep', $this->prep_multi_item_data($data));

		$list_items = $this->get_field_options();

		return form_multiselect(
			$this->field_name . '[]',
			$list_items,
			$data,
			$this->stringify_attributes(array_merge(
				array(
					'id' 	=> 'freeform_' . $this->field_name,
					'style' => 'min-width:150px;'
				),
				$attr
			))
		);
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Replace Tag
	 *
	 * @access	public
	 * @param	string 	data
	 * @param 	array 	params from tag
 	 * @param 	string 	tagdata if there is a tag pair
	 * @return	string
	 */

	public function replace_tag ($data, $params = array(), $tagdata = FALSE)
	{
		return $this->multi_item_replace_tag($data, $tagdata, FALSE, $params);
	}
	//END replace tag


	// --------------------------------------------------------------------

	/**
	 * display_email_data
	 *
	 * formats data for email notifications
	 *
	 * @access	public
	 * @param 	string 	data from table for email output
	 * @param 	object 	instance of the notification object
	 * @return	string 	output data
	 */

	public function display_email_data ($data, $notification_obj)
	{
		ee()->load->helper('text');
		return ee()->functions->encode_ee_tags(
			str_replace('<br/>', "\n", entities_to_ascii($this->replace_tag($data))),
			TRUE
		);
	}
	//END display_email_data


	// --------------------------------------------------------------------

	/**
	 * Display Entry in the CP
	 *
	 * formats data for cp entry
	 *
	 * @access	public
	 * @param 	string 	data from table for email output
	 * @return	string 	output data
	 */

	public function display_entry_cp ($data)
	{
		return $this->display_multi_item_output($data);
	}
	//END display_entry_cp


	// --------------------------------------------------------------------

	/**
	 * validate
	 *
	 * @access	public
	 * @param	string 	input data from post to be validated
	 * @return	bool
	 */

	public function validate ($data)
	{
		// we are OK with blank
		if (empty($data))
		{
			return TRUE;
		}

		if (is_string($data))
		{
			$data = array($data);
		}

		// If any of the incoming data does not exist as a key in the options array, fail.
		$opts	= $this->get_field_options(FALSE, FALSE);

		foreach ( $data as $val )
		{
			if ( ! isset( $opts[$val] ) )
			{
				return FALSE;
			}
		}

		return TRUE;
	}
	//END validate



	// --------------------------------------------------------------------

	/**
	 * Save Field Data
	 *
	 * @access	public
	 * @param	string 	data to be inserted
	 * @param	int 	form id
	 * @return	string 	data to go into the entry_field
	 */

	public function save ($data)
	{
		return $this->save_multi_item($data);
	}
	//END save


	// --------------------------------------------------------------------

	/**
	 * Export
	 *
	 * @access	public
	 * @param	string 	data to be exported
	 * @return	string 	data to go into the export
	 */

	public function export ($data, $export_type)
	{
		return $this->display_multi_item_output($data, FALSE);
	}
	//END export
}
//END class Multiselect_freeform_ft