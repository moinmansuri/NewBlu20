<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Field Type
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/ft.freeform.php
 */

if ( ! defined('FREEFORM_VERSION'))
{
	require_once 'constants.freeform.php';
}

require_once 'addon_builder/addon_builder.php';

class Freeform_ft extends EE_Fieldtype
{
	public 	$info 			= array(
		'name'		=> 'Freeform',
		'version'	=> FREEFORM_VERSION
	);

	public $field_name		= 'freeform_default';
	public $field_id		= 'freeform_default';

	public $has_array_data	= FALSE;

	protected $fob;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	public function __construct()
	{
		//is_callable doesn't do is right here, so we need
		//to use method exists to get a true bearing on it
		if (method_exists('EE_Fieldtype', '__construct'))
		{
			parent::__construct();
		}
		else
		{
			parent::EE_Fieldtype();
		}


		$this->EE =& get_instance();

		$this->field_id		= isset($this->settings['field_id']) ?
								$this->settings['field_id'] :
								$this->field_id;

		$this->field_name	= isset($this->settings['field_name']) ?
								$this->settings['field_name'] :
								$this->field_name;
	}
	// END constructor


	// --------------------------------------------------------------------

	/**
	 * displays field for publish/saef
	 *
	 * @access	public
	 * @param	string	$data	any incoming data from the channel entry
	 * @return	string	html output view
	 */

	public function display_field($data)
	{
		$data = $this->prep_data($data);

		ee()->load->model('freeform_form_model');

		if ( ! $this->fob()->data->show_all_sites())
		{
			ee()->freeform_form_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$c_forms =	ee()->freeform_form_model
						->key('form_id', 'form_label')
						->where('composer_id !=', 0)
						->get();

		$return = '<p>' . lang('no_available_composer_forms', $this->field_name . '[form]') . '</p>';

		if ($c_forms !== FALSE)
		{
			$output_array = array(0 => '--');

			foreach ($c_forms as $form_id => $form_label)
			{
				$output_array[$form_id] = $form_label;
			}

			$return = '<p>';
			$return .= lang('choose_composer_form', $this->field_name . '[form]');
			$return .= form_dropdown(
				$this->field_name . '[form]',
				$output_array,
				isset($data['form']) ? $data['form'] : ''
			);
			$return .= '</p>';

			$return .= '<p>';
			$return .= lang('return_page_field', $this->field_name . '[return]');
			$return .= '<p>' . form_input(array(
				'name'	=> $this->field_name . '[return]',
				'value'	=> isset($data['return']) ? $data['return'] : '',
				'style'	=> 'width:25%;'
			));
			$return .= '</p>';
		}

		return $return;
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Save Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	function save ($data)
	{
		$data	= $this->prep_data($data);
		$return	= '';

		//nothing set?
		if (isset($data['form']) AND
			$this->fob()->is_positive_intlike($data['form']))
		{
			$return = $data['form'] . '|' . (isset($data['return']) ? $data['return'] : '');
		}

		return $return;
	}
	//END save


	// --------------------------------------------------------------------

	/**
	 * Save Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	function validate ($data)
	{
		$data = $this->prep_data($data);

		//nothing set?
		if ( ! $this->fob()->is_positive_intlike($data['form']))
		{
			return TRUE;
		}

		ee()->load->model('freeform_form_model');

		if ( ! $this->fob()->data->show_all_sites())
		{
			ee()->freeform_form_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$c_forms =	ee()->freeform_form_model
						->where('form_id', $data['form'])
						->where('composer_id !=', 0)
						->count();

		return ($c_forms > 0);
	}
	//END validate


	// --------------------------------------------------------------------

	/**
	 * replace tag pair data
	 *
	 * @access	public
	 * @param	array 	data for preprocessing
	 * @param	array 	tag params
	 * @param	string 	tagdata
	 * @return	string	processed tag data
	 */

	public function replace_tag ($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->prep_data($data);

		//nothing set?
		if ( ! isset($data['form']) OR
			 ! $this->fob()->is_positive_intlike($data['form']))
		{
			return '';
		}

		//save old
		$old_tagdata 			= ee()->TMPL->tagdata;
		$old_params				= isset(ee()->TMPL->tagparams) ?
									ee()->TMPL->tagparams :
									array();

		//replace to trick tag :D
		ee()->TMPL->tagdata 	= '';

		if (empty($params))
		{
			$params = array();
		}

		if ( ! is_array($params))
		{
			$params = (array) $params;
		}

		ee()->TMPL->tagparams 	= array_merge(
			array(
				'form_id' 		=> $data['form'],
				'return'		=> $data['return']
			),
			$params
		);

		$return	= $this->fob()->composer();

		//reset
		ee()->TMPL->tagdata		= $old_tagdata;
		ee()->TMPL->tagparams	= $old_params;

		//for some reason CP isn't doing advanced conditionals *shrug*
		if (REQ == 'CP' && stristr($return, LD . 'if'))
		{
			$return = ee()->TMPL->advanced_conditionals($return);
		}

		return $return;
	}
	//END replace_tag

	// --------------------------------------------------------------------

	/**
	 * Form id for replace tag
	 *
	 * @access	public
	 * @param	mixed	$data		incoming data
	 * @param	array	$params		params on tag
	 * @param	mixed	$tagdata	tagdata, boolean false if missing
	 * @return	string				form id or blank
	 */

	public function replace_form_id ($data, $params = array(), $tagdata = FALSE)
	{
		$data = $this->prep_data($data);

		return (
			isset($data['form']) AND
			$this->fob()->is_positive_intlike($data['form'])
		) ? $data['form'] : '';
	}
	//END replace_form_id


	// --------------------------------------------------------------------

	/**
	 * Prep Data for consumption
	 *
	 * @access	protected
	 * @param	string	$data	incoming string data
	 * @return	array			fixed data array
	 */

	protected function prep_data ($data = '')
	{
		if ( ! is_array($data))
		{
			$data = $this->fob()->actions()->pipe_split((string) $data);
		}

		if ( ! isset($data['form']))
		{
			if (isset($data[0]) AND
				$this->fob()->is_positive_intlike($data[0]))
			{
				$data['form'] = $data[0];
			}
			else
			{
				$data['form'] = '';
			}
		}

		if ( ! isset($data['return']))
		{
			if (isset($data[1]) AND is_string($data[1]))
			{
				$data['return'] = $data[1];
			}
			else
			{
				$data['return'] = '';
			}
		}

		return $data;
	}
	//END prep_data


	// --------------------------------------------------------------------

	/**
	 * Freeform module Object.
	 *
	 * @access protected
	 * @return object cached instance of the freeform module object
	 */

	protected function fob ()
	{
		if ( ! isset($this->fob))
		{
			if ( ! class_exists('Freeform'))
			{
				require_once PATH_THIRD . 'freeform/mod.freeform.php';
			}

			$this->fob = new Freeform();
		}

		return $this->fob;
	}
	//END fob
}
//END Freeform_ft