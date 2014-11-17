<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Textarea Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.textarea.php
 */

class Textarea_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Textarea',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A field for multi-line text input.'
	);

	public $default_rows = 6;


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

		$this->info['name'] 		= lang('default_textarea_name');
		$this->info['description'] 	= lang('default_textarea_desc');
	}
	//END __construct


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
		if (isset($this->settings['disallow_html_rendering']) AND
			$this->settings['disallow_html_rendering'] == 'n')
		{
			return ee()->functions->encode_ee_tags($data, TRUE);
		}
		else
		{
			return $this->form_prep_encode_ee($data);
		}
	}
	//END display_entry_cp


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
		if (isset($this->settings['disallow_html_rendering']) AND
			$this->settings['disallow_html_rendering'] == 'n')
		{
			return ee()->functions->encode_ee_tags($data, TRUE);
		}
		else
		{
			return $this->form_prep_encode_ee($data);
		}
	}
	//END replace_tag

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
		return form_textarea(array_merge(array(
			'name'	=> $this->field_name,
			'id'	=> 'freeform_' . $this->field_name,
			'value'	=> ee()->functions->encode_ee_tags($data, TRUE),
			'rows'	=> isset($this->settings['field_ta_rows']) ?
						$this->settings['field_ta_rows'] :
						$this->default_rows,
			'cols'	=> '50'
		), $attr));
	}
	//END display_field


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
		$field_rows	= ( ! isset($data['field_ta_rows']) OR
						$data['field_ta_rows'] == '') ?
							$this->default_rows :
							$data['field_ta_rows'];

		ee()->table->add_row(
			lang('textarea_rows', 'field_ta_rows') .
			'<div class="subtext">' .
				lang('textarea_rows_desc') .
			'</div>',
			form_input(array(
				'id'	=> 'field_ta_rows',
				'name'	=> 'field_ta_rows',
				'size'	=> 4,
				'value'	=> $field_rows
			))
		);

		$disallow_html_rendering	= ( ! isset($data['disallow_html_rendering']) OR
						$data['disallow_html_rendering'] == '') ?
							'y' :
							$data['disallow_html_rendering'];

		ee()->table->add_row(
			lang('disallow_html_rendering', 'disallow_html_rendering') .
			'<div class="subtext">' .
				lang('disallow_html_rendering_desc') .
			'</div>',
			form_hidden('disallow_html_rendering', 'n') .
			form_checkbox(array(
				'id'	=> 'disallow_html_rendering',
				'name'	=> 'disallow_html_rendering',
				'value'		=> 'y',
				'checked' 	=> $disallow_html_rendering == 'y'
			)) .
			'&nbsp;&nbsp;' .
			lang('enable', 'disallow_html_rendering')
		);
	}
	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_settings($data = array())
	{
		$field_rows 	= ee()->input->get_post('field_ta_rows');

		$field_rows 	= (
			is_numeric($field_rows) AND
			$field_rows > 0
		) ?	$field_rows : $this->default_rows;

		return array(
			'field_ta_rows'				=> $field_rows,
			'disallow_html_rendering'	=> (
				ee()->input->get_post('disallow_html_rendering') == 'n' ? 'n' : 'y'
			)
		);
	}
	//END save_settings
}
//END class Textarea_freeform_ft
