<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Country Select Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.country_select.php
 */

class Country_select_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Country Select',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A dropdown selection of countries.'
	);

	private $countries = array();


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

		$this->info['name'] 		= lang('default_country_name');
		$this->info['description'] 	= lang('default_country_desc');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Get countries
	 *
	 * @access	public
	 * @return	mixed
	 */

	public function get_countries()
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		$output = array();

		// --------------------------------------------
		// Get countries from config
		// --------------------------------------------

		$countries_file = APPPATH . 'config/countries.php';

		if (is_file($countries_file))
		{
			include_once $countries_file;

			if ( ! empty( $countries ) )
			{
				$output = $countries;
			}
		}

		return $cache->set($output);
	}
	// End get countries

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
		$countries = $this->get_countries();

		return form_dropdown(
			$this->field_name,
			$countries,
			(isset($countries[$data]) ? array($data) : NULL),
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
		$countries = $this->get_countries();

		$data = trim($data);

		$return = TRUE;

		if ($data != '' AND ! array_key_exists($data, $countries))
		{
			$return = lang('invalid_country');
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
//END class Country_select_ft