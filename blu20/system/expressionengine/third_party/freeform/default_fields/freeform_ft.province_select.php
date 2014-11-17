<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Province Select Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.province_select.php
 */

class Province_select_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name'			=> 'Province Select',
		'version'		=> FREEFORM_VERSION,
		'description'	=> 'A dropdown selection of Canadian provinces and territories.'
	);

	private $provinces = array();


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

		$this->info['name'] 		= lang('default_provinces_name');
		$this->info['description'] 	= lang('default_provinces_desc');

		// -------------------------------------
		//	parse provinces
		// -------------------------------------

		$provinces 	= array_map(
			'trim',
			preg_split(
				'/[\n\r]+/',
				lang('list_of_canadian_provinces'),
				-1,
				PREG_SPLIT_NO_EMPTY
			)
		);

		//need matching key => value pairs for the select values to be correct
		//for the output value we are removing the ' (MB)' code for the value and the 'Manitoba' code for the key
		foreach ($provinces as $key => $value)
		{
			$this->provinces[
				preg_replace('/[\w|\s]+\(([a-zA-Z\-_]+)\)$/', "$1", $value)
			] = preg_replace('/\s+\([a-zA-Z\-_]+\)$/', '', $value);
		}
	}
	//END __construct


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
		return form_dropdown(
			$this->field_name,
			$this->provinces,
			(isset($this->provinces[$data]) ? array($data) : NULL),
			$this->stringify_attributes(array_merge(
				array('id' => 'freeform_' . $this->field_name),
				$attr
			))
		);
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */

	public function validate ($data)
	{
		$data = trim($data);

		$return = TRUE;

		if ($data != '' AND ! array_key_exists($data, $this->provinces))
		{
			$return = lang('invalid_province');
		}

		return $return;
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
		$data = trim($data);
		return $this->validate($data) ? $data : '';
	}
	//END save
}
//END class Province_select_freeform_ft