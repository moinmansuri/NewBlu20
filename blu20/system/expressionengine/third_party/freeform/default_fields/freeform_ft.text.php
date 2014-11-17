<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Text Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.text.php
 */

class Text_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Text',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A field for single line text input.'
	);

	public $default_length 	= '150';

	public $field_content_types 	= array(
		'any',
		'email',
		'integer',
		'number',
		'decimal'
	);


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct ()
	{
		parent::__construct();

		$this->info['name'] 		= lang('default_text_name');
		$this->info['description'] 	= lang('default_text_desc');
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
		return form_input(array_merge(array(
			'name'			=> $this->field_name,
			'id'			=> 'freeform_' . $this->field_name,
			'value'			=> $data,
			'maxlength'		=> isset($this->settings['field_length']) ?
								$this->settings['field_length'] :
								$this->default_length
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
		$form_radios 	= '';

		$content_type 	= isset($data['field_content_type']) ?
							$data['field_content_type'] :
							'any';

		foreach ($this->field_content_types as $type)
		{

			$form_radios .= form_radio(array(
				'name' 		=> 'field_content_type',
				'id' 		=> 'content_type_' . $type,
				'value' 	=> $type,
				'checked' 	=> ($content_type == $type)
			));
			$form_radios .= NBS . NBS;
			$form_radios .= lang($type, 'content_type_' . $type);
			$form_radios .= NBS . NBS . NBS . NBS;
		}


		ee()->table->add_row(
			lang('field_content_type', 'field_content_type') .
				'<div class="subtext">' .
					lang('field_content_type_desc') .
				'</div>',
			$form_radios
		);

		ee()->table->add_row(
			lang('field_length', 'field_length') .
				'<div class="subtext">' .
					lang('field_length_desc') .
				'</div>',
			form_input(array(
				'name'		=> 'field_length',
				'id'		=> 'field_length',
				'value'		=> isset($data['field_length']) ?
								$data['field_length'] :
								$this->default_length,
				'maxlength'	=> '250',
				'size'		=> '50',
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

	public function save_settings ($data = array())
	{
		//max field length
		$field_length 	= ee()->input->get_post('field_length');

		$field_length 	= (
			is_numeric($field_length) AND
			$field_length > 0 AND
			$field_length < 9999
		) ?	$field_length : $this->default_length;

		//field content type. only valid if in the list
		$field_content_type = ee()->input->get_post('field_content_type');
		$field_content_type = in_array(
			$field_content_type,
			$this->field_content_types) ?
				$field_content_type :
				'any';

		return array(
			'field_length' 			=> $field_length,
			'field_content_type'	=> $field_content_type,
			'disallow_html_rendering'	=> (
				ee()->input->get_post('disallow_html_rendering') == 'n' ? 'n' : 'y'
			)
		);
	}
	//END save_settings


	// --------------------------------------------------------------------

	/**
	 * validate
	 *
	 * @access	public
	 * @param	string $data 	data to validate
	 * @return	bool  			validated?
	 */

	public function validate ($data)
	{
		if (is_array($data))
		{
			$data = implode("\n", $data);
		}

		$data = trim((string) $data);

		// -------------------------------------
		//	check field length
		// -------------------------------------

		if (isset($this->settings['field_length']) AND
			strlen($data) > $this->settings['field_length'])
		{
			$this->errors[] = str_replace(
				'%num%',
				$this->settings['field_length'],
				lang('max_length_exceeded')
			);

			return $this->errors;
		}

		// -------------------------------------
		//	is the data worth futher checking?
		// -------------------------------------

		if ($data == '' OR
			! isset($this->settings['field_content_type']) OR
			$this->settings['field_content_type'] == 'any')
		{
			return TRUE;
		}

		//validate individually

		$content_type = $this->settings['field_content_type'];

		//lets only validate how we can
		if (in_array($content_type, $this->field_content_types))
		{
			if ($content_type == 'decimal')
			{
				if ( ! preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $data))
				{
					return lang('not_a_decimal');
				}

				// Check if number exceeds mysql limits
				/*if ($data >= 999999.9999)
				{
					$this->errors[] = lang('number_exceeds_limit');
					return FALSE;
				}*/
			}
			else if ($content_type == 'integer')
			{
				//if (($data < -2147483648) OR ($data > 2147483647))
				if ( ! preg_match( '/^[\-+]?[0-9]+$/', $data))
				{
					return lang('not_an_integer');
				}
			}
			else if ($content_type == 'number')
			{
				if ( ! is_numeric($data))
				{
					return lang('not_a_number');
				}
			}
			else if ($content_type == 'email')
			{
				ee()->load->helper('email');

				if ( ! valid_email($data))
				{
					return lang('not_valid_email');
				}
			}
		}

		return TRUE;
	}
	//END validate
}
//END class Text_freeform_ft
