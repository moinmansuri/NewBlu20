<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Checkbox Group Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.checkbox_group.php
 */

class Checkbox_group_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name'			=> 'Checkbox Group',
		'version'		=> FREEFORM_VERSION,
		'description'	=> 'A field that contains a group of checkboxes for multiple choices.'
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

		$this->info['name']			= lang('default_checkbox_group_name');
		$this->info['description']	= lang('default_checkbox_group_desc');
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
		$data		= $this->prep_multi_item_data($data);

		$list_items	= $this->get_field_options();

		$param_defaults = array(
			'wrapper_open'			=> '<ul>',
			'wrapper_close'			=> '</ul>',
			'row_wrapper_open' 		=> '<li>',
			'row_wrapper_close' 	=> '</li>',
			'label_wrapper_open' 	=> '<label for="%id%">',
			'label_wrapper_close' 	=> '</label>',
			'input_wrapper_open'	=> '',
			'input_wrapper_close'	=> '&nbsp;&nbsp;',
			'order'					=> 'CL'
		);

		$params = array_merge($param_defaults, $params);

		if ( ! isset($attr['style']) AND $params['wrapper_open'] == '<ul>')
		{
			$attr['style'] = 'list-style:none; padding:0;';
		}

		//add the first wrapper, and all attributes
		$return = preg_replace(
			'/\>$/',
			' ' . $this->stringify_attributes($attr) . '>',
			$params['wrapper_open']
		);

		$count = 1;

		//each radio field, dog
		foreach ($list_items as $value => $label)
		{
			$id = 'freeform_' . $this->field_name . '_' . $count++;

			//label
			//we want to add the ID in here
			$lab 	 = str_replace('%id%', $id, $params['label_wrapper_open']);
			$lab 	.= $label;
			$lab 	.= $params['label_wrapper_close'];

			//radio
			$radio 	= $params['input_wrapper_open'];
			$radio .= form_checkbox(array(
				'name'		=> $this->field_name . '[]',
				'id'		=> $id,
				'value'		=> $value,
				'checked' 	=> in_array($value, $data)
			));
			$radio .= $params['input_wrapper_close'];

			//put it all together ;D
			$return .= $params['row_wrapper_open'];
			$return .= (
				(strtoupper($params['order']) == 'CL') ? $radio . $lab : $lab . $radio
			);
			$return .= $params['row_wrapper_close'];
		}

		$return .= $params['wrapper_close'];

		return $return;
	}
	//END display_field


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
//END class Checkbox_group_freeform_ft
