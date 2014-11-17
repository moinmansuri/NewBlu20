<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Hidden Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.hidden.php
 */

class Hidden_freeform_ft extends Freeform_base_ft
{
	public 	$info 		= array(
		'name' 			=> 'Hidden',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A hidden field for collecting information the user does not need to interact with.'
	);

	public $show_label = FALSE;


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

		$this->info['name']			= lang('default_hidden_name');
		$this->info['description']	= lang('default_hidden_desc');
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
		$output = form_hidden(
			$this->field_name,
			$this->parse_specials(
				trim($data) !== '' ?
					$data :
					(
						isset($this->settings['default_data']) ?
							$this->settings['default_data'] :
							''
					)
			)
		);

		if ( ! empty($attr))
		{
			$output = preg_replace(
				"/[\/]?>$/ms",
				$this->stringify_attributes($attr) . ' />',
				$output
			);
		}

		return $output;
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Display Edit field in the CP in case something special is needed
	 *
	 * @access	public
	 * @param 	string 	incoming data
	 * @return	string 	output data
	 */

	public function display_edit_cp ($data)
	{
		return form_input(
			$this->field_name,
			$this->parse_specials(
				trim($data) !== '' ?
					$data :
					(
						isset($this->settings['default_data']) ?
							$this->settings['default_data'] :
							''
					)
			)
		);
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Display field in composer
	 *
	 * This is just a dummy output so composer has a similar looking field
	 * for output. This can be used to fill dummy data or with placeholders
	 *
	 * @access	public
	 * @param   string  $data  any data to be passed to export
	 * @return	string
	 */

	public function display_composer_field ($data = '')
	{
		return lang('hidden_field_not_shown');
	}
	//END display_composer_field


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
		ee()->table->add_row(
			lang('default_hidden_field_data', 'default_data') .
				'<div class="subtext">' .
					lang('default_hidden_field_data_desc') .
				'</div>',
			form_input(array(
				'name'		=> 'default_data',
				'id'		=> 'hidden_default_data',
				'value'		=> isset($data['default_data']) ?
								$data['default_data'] :
								'',
			))
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
		$default_data	= trim((string) ee()->input->get_post('default_data'));

		return array(
			'default_data'	=> strip_tags($default_data)
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
		return TRUE;
	}
	//END validate


	// --------------------------------------------------------------------

	/**
	 * Parse special items for hidden fields
	 *
	 * @access	protected
	 * @param	string	$data	incoming data
	 * @return	string			parsed data
	 */

	protected function parse_specials ($data = '')
	{
		$data = preg_replace(
			'/(?<![:_])\b(CURRENT_URL)\b(?![:_])/ms',
			$this->current_url(),
			$data
		);

		//segments
		if (isset(ee()->TMPL) AND
			is_object(ee()->TMPL) AND
			preg_match_all('/' . LD . 'segment_([0-9]+)' . RD . '/sm', $data, $matches, PREG_SET_ORDER))
		{
			$variables = array();

			foreach ($matches as $match)
			{
				if (isset($match[0]) AND isset($match[1]))
				{
					if (isset(ee()->uri->segments[$match[1]]))
					{
						$variables['segment_' . $match[1]] = ee()->uri->segments[$match[1]];
					}
					else
					{
						$variables['segment_' . $match[1]] = '';
					}
				}
			}

			$data = ee()->TMPL->parse_variables($data, array($variables));
			$data = ee()->TMPL->advanced_conditionals($data);
		}

		return $data;
	}
	//END parse_specials


	// --------------------------------------------------------------------

	/**
	 * Current full URL as best as PHP can get it
	 *
	 * @access	public
	 * @return	string current url
	 */

	public function current_url ()
	{
		$current_url = 'http';
		$current_url .= empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$current_url .= "://".$_SERVER['SERVER_NAME'];
		$current_url .= ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);

		if ( ! isset($_SERVER['REQUEST_URI']))
		{
			$current_url .= $_SERVER['PHP_SELF'];
		}
		else
		{
			$current_url .= $_SERVER['REQUEST_URI'];
		}

		if ( ! stristr('?', $current_url) AND
			isset($_SERVER['QUERY_STRING']) AND
			$_SERVER['QUERY_STRING'] !== '')
		{
			$current_url .= '?' . $_SERVER['QUERY_STRING'];
		}

		return $current_url;
	}
	//END current_url
}
//END class Text_freeform_ft
