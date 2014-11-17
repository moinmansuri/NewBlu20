<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Field Type Base
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/data.freeform.php
 */

if ( ! class_exists('Freeform_cacher'))
{
	require_once 'libraries/Freeform_cacher.php';
}

class Freeform_base_ft
{
	public $entry_id			= 0;
	public $entry_views			= array();
	public $errors				= array();
	public $field_id			= '';
	public $field_name			= '';
	public $form_id				= 0;
	public $requires_multipart	= FALSE;
	public $settings			= array();
	public $show_all_sites		= FALSE;
	public $show_label			= TRUE;

	public $info				= array(
		'name'			=> '',
		'version'		=> '0.1',
		'description'	=> ''
	);

	//protected in case this needs to get augmented
	//by a child function for validation
	protected $valid_list_types = array(
		'list',
		'value_label',
		'channel_field',
		'nld_textarea'
	);

	//EE instance holder
	protected 	$EE;

	private 	$cache;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		$this->EE =& get_instance();
		ee()->load->helper('form');

		$site_id = ee()->config->item('site_id');

		if ( ! isset(ee()->session->cache['freeform_ft_cache'][$site_id]))
		{
			ee()->session->cache['freeform_ft_cache'][$site_id] = array();
		}

		//private to this parent constructor
		$this->cache =& ee()->session->cache['freeform_ft_cache'][$site_id];
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Pre process data for multiple instances
	 *
	 * @access  public
	 * @param  	string $data data from db row
	 * @return 	string pre_processed data
	 */

	public function pre_process ($data)
	{
		return $data;
	}
	//END pre_process


	// --------------------------------------------------------------------

	/**
	 * Pre process data from the paginated query object before all fields
	 * get used
	 *
	 * @access  public
	 * @param  	object &$query query object from entries tag
	 * @return 	void
	 */

	public function pre_process_entries ($ids = array())
	{
		return;
	}
	//END pre_process_entries


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
		if (is_array($data))
		{
			$data = implode("\n", $data);
		}

		return ee()->functions->encode_ee_tags($data, TRUE);
	}
	//END replace_tag


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
		return '';
	}
	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Validate settings before saving on field save
	 *
	 * @access public
	 * @return mixed 	boolean true/false, or array of errors
	 */

	public function validate_settings ()
	{
		return TRUE;
	}
	//END validate_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_settings ()
	{
		return array();
	}
	//END save_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function post_save_settings ()
	{
		return;
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
		return form_input(array(
			'name'		=> $this->field_name,
			'id'		=> 'freeform_field_' . $this->field_id,
			'value'		=> ee()->functions->encode_ee_tags($data, TRUE),
			'maxlength'	=> '250',
			'size'		=> '50',
		));
	}
	//END display_field


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
	 * Save Field Data
	 *
	 * @access	public
	 * @param	string 	data to be inserted
	 * @return	string 	data to go into the entry_field
	 */

	public function save ($data)
	{
		if (is_array($data))
		{
			$data = implode("\n", $data);
		}

		return (string) $data;
	}
	//END save


	// --------------------------------------------------------------------

	/**
	 * Post Save Field Data
	 *
	 * @access	public
	 * @param	string	data to be inserted
	 * @return	null
	 */

	public function post_save ($data)
	{
		return;
	}
	//END post_save


	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @access	public
	 * @param	array of ids
	 */

	public function delete ($ids = array())
	{
		return;
	}
	//END delete


	// --------------------------------------------------------------------

	/**
	 * Export Data
	 *
	 * @access	public
	 * @param	string input data to prep
	 * @param   string export type
	 * @return	string preped data
	 */

	public function export ($data = '', $export_type = '')
	{
		return $data;
	}
	//END export


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

	public function display_email_data ($data, $notification_obj = null)
	{
		if (is_array($data))
		{
			$data = implode("\n", $data);
		}

		return $this->encode_ee(entities_to_ascii($data));
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
		if (is_array($data))
		{
			$data = implode("\n", $data);
		}

		return $this->form_prep_encode_ee($data);
	}
	//END display_entry_cp


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
		return $this->display_field($data);
	}
	//END display_edit_cp


	// --------------------------------------------------------------------

	/**
	 * Install
	 *
	 * @access	public
	 * @return	void
	 */

	public function install ()
	{
		return;
	}
	//END install


	// --------------------------------------------------------------------

	/**
	 * Update
	 *
	 * @access	public
	 * @return	void
	 */

	public function update ()
	{
		return;
	}
	//END update


	// --------------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @access	public
	 * @return	void
	 */

	public function uninstall ()
	{
		return;
	}
	//END uninstall


	// --------------------------------------------------------------------

	/**
	 * Remove Form Form
	 *
	 * @access	public
	 * @param   $form_id form id that field was removed from
	 * @return	void
	 */

	public function remove_from_form ($form_id)
	{
		return;
	}
	//END remove_from_form


	// --------------------------------------------------------------------

	/**
	 * Delete Field
	 *
	 * @access	public
	 * @return	void
	 */

	public function delete_field ()
	{
		return;
	}
	//END delete_field


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
		return $this->display_field($data);
	}
	//END display_composer_field


	// --------------------------------------------------------------------

	/**
	 * stringify attributes
	 *
	 * helper for form_helper functions that take strings of attritutes
	 * rather than arrays like form_input
	 *
	 * @access	protected
	 * @param	array	  	k=>v array of attributes to add to the element
	 * @return	string 		html string snippet to insert
	 */

	protected function stringify_attributes ($array = array())
	{
		$string = '';

		if ( is_array($array) AND empty($array))
		{
			return $string;
		}
		else if ( is_string($array))
		{
			return $array;
		}

		foreach ($array as $key => $value)
		{
			$string .=  ' ' . $key . '="' . $value . '"';
		}

		return $string;
	}
	//END stringify_attributes


	// --------------------------------------------------------------------

	/**
	 * yes no row
	 *
	 * helper for functions to add quick yes/no options for rows
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string	lang string for label
	 * @param	string	data key for settings array to get the yes/no name=""
	 * @param	string	prefix for data key to name="" param
	 * @return	null
	 */

	protected function yes_no_row ($data, $lang, $data_key, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix . '_' : '';

		$val_is_y = ($data[$data_key] == 'y');

		ee()->table->add_row(
			'<label>'.lang($lang).'</label>',
			form_radio(
				$prefix . $data_key,
				'y',
				$val_is_y,
				'id="' . $prefix . $data_key . '_y"'
			) . NBS .
			lang('yes', $prefix . $data_key . '_y') . NBS . NBS . NBS .
			form_radio(
				$prefix . $data_key,
				'n',
				( ! $val_is_y),
				'id="' . $prefix . $data_key . '_n"'
			) . NBS .
			lang('no',$prefix .  $data_key . '_n')
		);
	}
	//END yes_no_row

	//to help mitigate conversions from standard fieldtypes
	protected function _yes_no_row($data, $lang, $data_key, $prefix = FALSE)
	{
		return $this->yes_no_row($data, $lang, $data_key, $prefix);
	}


	// --------------------------------------------------------------------

	/**
	 * text_direction_row
	 *
	 * adds a text direction, duh
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string	prefix for data key to name="" param
	 * @return	null
	 */

	protected function text_direction_row ($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix . '_' : '';

		ee()->table->add_row(
			'<label>'.lang('text_direction').'</label>',
			form_radio(
				$prefix . 'field_text_direction',
				'ltr',
				($data[$prefix . 'field_text_direction'] == 'ltr'),
				'id="' . $prefix . 'field_text_direction_ltr"'
			) . NBS .
			lang('ltr', $prefix . 'field_text_direction_ltr') . NBS . NBS . NBS .
			form_radio(
				$prefix . 'field_text_direction',
				'rtl',
				($data[$prefix . 'field_text_direction'] == 'rtl'),
				'id="' . $prefix . 'field_text_direction_rtl"'
			) . NBS .
			lang('rtl', $prefix . 'field_text_direction_rtl')
		);
	}
	//END text_direction_row


	// --------------------------------------------------------------------

	/**
	 * multi_item_row
	 *
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string	prefix for data key to name="" param
	 * @param	string	optional lang line override for the row label
	 * @param	string	optional lang line override for the row desc
	 * @return	null
	 */

	protected function multi_item_row (	$data,
										$prefix = FALSE,
										$label = 'multi_list_items',
										$desc = 'multi_list_items_desc' )
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		// -------------------------------------
		//	clean field settings for html output
		// -------------------------------------

		$field_list_items = isset($data[$prefix . 'field_list_items']) ?
							$data[$prefix . 'field_list_items'] :
							'';

		if ( is_array($field_list_items) AND ! empty($field_list_items))
		{
			foreach ($field_list_items as $key => $value)
			{
				$field_list_items[$key] = htmlspecialchars($value, ENT_QUOTES, "UTF-8", FALSE);
			}
		}

		// -------------------------------------
		//	variables for multi item
		// -------------------------------------

		$vars = array(
			'prefix' 				=> $prefix,
			'list_setting'			=> isset($data['list_type']) ?
										$data['list_type'] : '',
			'channel_field_list'	=> $this->load_field_list(),
			'pre' 					=> $prefix,
			'field_list_items' 		=> $field_list_items
		);

		// -------------------------------------
		//	addamaladd
		// -------------------------------------

		ee()->table->add_row(
			lang($label, $prefix . 'multi_list_items') .
				'<div class="subtext">' . lang($desc) . '</div>',
			array(
				'data'	=> ee()->load->view('multi_item_row.html', $vars, TRUE),
				'style'	=> 'vertical-align:top;'
			)
		);
	}
	//END multi_item_row


	// --------------------------------------------------------------------

	/**
	 * validate_multi_item_row_settings
	 *
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string	prefix for data key to name="" param
	 * @return	null
	 */

	protected function validate_multi_item_row_settings ($data, $prefix = FALSE)
	{
		return $this->save_multi_item_row_settings($data, $prefix, TRUE);
	}
	//END validate_multi_item_row_settings


	// --------------------------------------------------------------------

	/**
	 * save_multi_item_row_settings
	 *
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string	prefix for data key to name="" param
	 * @param	bool	validate only?
	 * @return	mixed	boolean if validate, array of settings if not
	 */

	protected function save_multi_item_row_settings ($data, $prefix = FALSE, $validate = FALSE)
	{
		$prefix = ($prefix) ? $prefix . '_' : '';

		// -------------------------------------
		//	list type
		// -------------------------------------

		$list_type = ee()->input->post($prefix . 'list_type', TRUE);

		$list_type = in_array($list_type, $this->valid_list_types) ?
						$list_type :
						$this->valid_list_types[0];

		// -------------------------------------
		//	field settings
		// -------------------------------------

		$field_list_items = array();

		if ($list_type == 'channel_field')
		{
			//yes this is returning a string. So what.
			$channel_field = trim(
				ee()->input->post(
					$prefix . 'channel_field',
					TRUE
				)
			);

			//no funny stuff
			if (preg_match('/^[0-9]+_([ut]{1}|[0-9]+)$/', $channel_field))
			{
				$field_list_items = $channel_field;
			}
		}
		else if ($list_type == 'value_label')
		{
			$keys 	= ee()->input->post(
				$prefix . 'list_value_holder_input',
				TRUE
			);

			$values = ee()->input->post(
				$prefix . 'list_label_holder_input',
				TRUE
			);

			if ($keys AND $values)
			{
				foreach ($keys as $key => $value)
				{
					//no blanks!
					if (isset($values[$key]) AND ! isset($field_list_items[trim($value)]))
					{
						//we want the values for both
						//because one is a key and the other a value!
						$field_list_items[trim($value)] = trim($values[$key]);
					}
				}

				// -------------------------------------
				//	remove blanks from end
				// -------------------------------------

				$last_fli = end($field_list_items);
				$last_key = key($field_list_items);
				reset($field_list_items);

				while ($last_key === '' AND $last_fli === '')
				{
					array_pop($field_list_items);

					$last_fli = end($field_list_items);
					$last_key = key($field_list_items);
					reset($field_list_items);
				}
			}
		}
		else if ($list_type == 'nld_textarea')
		{
			//any cleanup for this will be done on output
			//so the fieldtype can be created automaticly with
			//third_party scripts
			$field_list_items = rtrim(
				ee()->input->post(
					$prefix . 'list_nld_textarea_input',
					TRUE
				)
			);
		}
		else
		{
			$temp_settings = ee()->input->post(
				$prefix . 'list_holder_input',
				TRUE
			);

			foreach ($temp_settings as $key => $value)
			{
				//only allow one blank key
				if (trim($value) !== '' OR ! in_array('', $field_list_items, TRUE))
				{
					$field_list_items[] = $value;
				}
			}

			// -------------------------------------
			//	remove blanks from end
			// -------------------------------------

			$last_fli = end($field_list_items);
			reset($field_list_items);

			while ($last_fli === '')
			{
				array_pop($field_list_items);

				$last_fli = end($field_list_items);
				reset($field_list_items);
			}
		}

		//Y U NO CHOOSE??
		if ($validate)
		{
			if (empty($field_list_items) OR
				( ! is_array($field_list_items) AND
				trim($field_list_items) == ''))
			{
				$this->errors[] = lang('you_must_choose_field_options');
				return FALSE;
			}
			else
			{
				return TRUE;
			}
		}

		//:3
		return array(
			$prefix . 'list_type' 			=> $list_type,
			$prefix . 'field_list_items' 	=> $field_list_items
		);
	}
	//END save_multi_item_row_settings


	// --------------------------------------------------------------------

	/**
	 * Mutli Item Values
	 *
	 * Takes a string, piped string or array and returns a key value
	 * set of names and values from available options.
	 *
	 * @access protected
	 * @param  mixed  	$input key value array of options
	 * @return array 	key value array for parsing tags
	 */

	protected function multi_item_values($input = array(), $prefix = FALSE)
	{
		if ( ! is_string($input) AND ! is_array($input))
		{
			return array();
		}

		$options = $this->get_field_options($prefix);

		$prefix  = ($prefix) ? $prefix . '_' : '';

		$output  = array();

		if ( is_string($input))
		{
			$input = preg_split(
				'/\|/',
				$input,
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			$input = array_map('trim', $input);
		}

		foreach ($input as $value)
		{
			if (isset($options[$value]))
			{
				$output[$value] = $options[$value];
			}
			else
			{
				$output[$value] = $value;
			}
		}

		return $output;
	}
	//END multi_item_values


	// --------------------------------------------------------------------

	/**
	 * Multi Item Replace Tag
	 *
	 * @access	protected
	 * @param	string	$data	incoming data
	 * @param	mixed	$prefix	boolean if none, string if one
	 * @return	string			parsed tagdata
	 */

	protected function multi_item_replace_tag ($data, $tagdata = '', $prefix = FALSE, $params = array())
	{
		if (empty($data))
		{
			return '';
		}

		$data	= $this->prep_multi_item_data($data);

		if ( ! empty($tagdata))
		{
			$rows	= array();

			$options = $this->get_field_options($prefix, FALSE);

			foreach ($data as $key => $val)
			{
				if (isset($options[$val]))
				{
					$rows[]	= array(
						'freeform:data:value'	=>	$this->encode_ee($val),
						'freeform:data:label'	=>	$this->encode_ee($options[$val])
					);
				}
			}

			if (empty($rows))
			{
				return '';
			}

			$output = ee()->TMPL->parse_variables($tagdata, $rows);

			// -------------------------------------
			//	backspace?
			// -------------------------------------

			if (isset($params['backspace']) AND
				ctype_digit((string) $params['backspace']))
			{
				$output = substr($output, 0, $params['backspace'] * -1);
			}

			return $output;
		}
		else
		{
			$output	= array();

			$options = $this->get_field_options($prefix, FALSE);

			foreach ($data as $key => $val)
			{
				if (isset($options[$val]))
				{
					$output[] = $this->encode_ee($options[$val]);
				}
			}

			return implode("<br/>", $output );
		}
	}
	//END multi_item_replace_tag


	// --------------------------------------------------------------------

	/**
	 * Preps multi item from the database
	 *
	 * @access	public
	 * @param	string	$data	string from DB to get parsed out to choices
	 * @return	array			array of choices
	 */

	public function prep_multi_item_data ($data)
	{
		if ( ! is_string($data))
		{
			return $data;
		}

		$output	= array();

		$data	= explode("\n", $data);

		foreach ($data as $value)
		{
			$sub		= explode('|~|', $value);
			$output[]	= $sub[0];
		}

		// !! disabled due to customer requests !!
		//remove blanks
		//$output = array_filter($output);

		//reset empty keys without sorting
		$output = array_merge($output, array());

		return $output;
	}
	//END prep_multi_item_data



	// --------------------------------------------------------------------

	/**
	 * Display Output
	 *
	 * generic output for multi-item row
	 *
	 * @access	protected
	 * @param	string	$data	from table for email output
	 * @param	mixed	$prefix	boolean if none, string if one
	 * @return	string			output data
	 */

	protected function display_multi_item_output ($data, $html = TRUE, $prefix = FALSE)
	{
		if (empty($data))
		{
			return '';
		}

		$data	= $this->prep_multi_item_data($data);

		$output	= array();

		$options = $this->get_field_options($prefix, FALSE);

		foreach ($data as $key)
		{
			if (isset($options[$key]))
			{
				$output[] = ($html) ? $this->form_prep_encode_ee($options[$key]) : $options[$key];
			}
		}

		//foreach ($this->get_field_options($prefix, FALSE) as $key => $val)
		//{
		//	if (isset($data[$key]) OR in_array($key, $data))
		//	{
		//		$output[] = ($html) ? $this->form_prep_encode_ee($val) : $val;
		//	}
		//}

		return implode(($html ? '<br/>' : "\n"), $output);
	}
	//END display_output


	// --------------------------------------------------------------------

	/**
	 * Save Multi-item Row
	 *
	 * @access	protected
	 * @param	mixed	$data	incoming post data
	 * @param	mixed	$prefix	boolean if none, string if one
	 * @return	string			combined string of options and choices
	 */

	protected function save_multi_item ($data, $prefix = FALSE)
	{
		if (empty($data) OR (! is_string($data) AND ! is_array($data)))
		{
			return '';
		}

		if ( is_string($data))
		{
			$data = array($data);
		}

		$output	= array();

		$options = $this->get_field_options($prefix, FALSE);

		foreach ($data as $val)
		{
			if (isset($options[$val]))
			{
				$output[$val] = $val . '|~|' . $options[$val];
			}
		}

		// -------------------------------------
		//	Re-thought the below, and users
		//	probably want data displaying
		//	as it came in.
		// -------------------------------------

		//sort on option order so output
		//comes in as expected
		//foreach ($options as $key => $value)
		//{
		//	if (isset($temp[$key]))
		//	{
		//		$output[] = $temp[$key];
		//	}
		//}

		return implode("\n", $output);
	}
	//END save_multi_item_row_settings


	// --------------------------------------------------------------------

	/**
	 * get_field_options
	 *
	 *
	 * @access	protected
	 * @param	array 	settings array passed from parant display_settings/field
	 * @param	string 	prefix for settings
	 * @return	array 	array of field items
	 */

	protected function get_field_options ($prefix = FALSE, $output_mode = TRUE)
	{
		$prefix = ($prefix) ? $prefix . '_' : '';

		$field_options = array();

		if ( ! isset($this->settings[$prefix . 'list_type']) OR
			! isset($this->settings[$prefix . 'field_list_items']))
		{
			return array();
		}

		if ($this->settings[$prefix . 'list_type'] == 'list')
		{
			if (is_array($this->settings[$prefix . 'field_list_items']))
			{
				//trim, remove blanks, form prep
				$items = array_map(
					'trim',
					$this->settings[$prefix . 'field_list_items']
				);

				foreach ($items as $v)
				{
					$field_options[$v] = $v;
				}
			}
		}
		else if ($this->settings[$prefix . 'list_type'] == 'value_label')
		{
			if (is_array($this->settings[$prefix . 'field_list_items']))
			{
				foreach ($this->settings[$prefix . 'field_list_items'] as $k => $v)
				{
					$field_options[trim($k)] = trim($v);
				}
			}
		}
		else if ($this->settings[$prefix . 'list_type'] == 'channel_field')
		{
			list($channel_id, $field_id) = explode('_', $this->settings[$prefix . 'field_list_items']);

			// We need to pre-populate this menu from an another channel custom field
			if ($field_id == 'u')
			{
				ee()->db->select('url_title as output_item');
				ee()->db->from('channel_titles');
			}
			else if ($field_id == 't')
			{
				ee()->db->select('title as output_item');
				ee()->db->from('channel_titles');
			}
			else
			{
				ee()->db->from('channel_data');
				ee()->db->select('field_id_'.$field_id . ' as output_item');
			}

			ee()->db->select('entry_id');
			ee()->db->where('channel_id', $channel_id);
			$pop_query = ee()->db->get();

			if ($pop_query->num_rows() > 0)
			{
				foreach ($pop_query->result_array() as $prow)
				{
					//$selected = ($prow['field_id_'.$field_id] == $data) ? 1 : '';
					$pretitle = /*substr(*/$prow['output_item']/*, 0, 110)*/;
					$pretitle = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $pretitle);

					$field_options[$prow['entry_id']] = $pretitle;
				}
			}
		}
		else if ($this->settings[$prefix . 'list_type'] == 'nld_textarea')
		{
			if ( trim($this->settings[$prefix . 'field_list_items']) !== '')
			{
				$items = preg_split(
					'/\R/',
					rtrim($this->settings[$prefix . 'field_list_items'])//,
					//-1,
					//PREG_SPLIT_NO_EMPTY
				);

				//trim, remove blanks, form prep
				$items = array_map('trim', $items);

				foreach ($items as $v)
				{
					$field_options[trim($v)] = trim($v);
				}
			}
		}
		//I am baffled as to how we would ever get here, but defaults gonna default
		else
		{
			$v = trim($this->settings[$prefix . 'field_list_items']);
			$field_options[$v] = $v;
		}

		if ($output_mode)
		{
			$prepped_field_options = array();

			foreach ($field_options as $key => $value)
			{
				$prepped_field_options[
					$this->form_prep_encode_ee($key)
				] = $this->form_prep_encode_ee($value);
			}

			$field_options = $prepped_field_options;
		}

		return $field_options;
	}
	//END get_field_options


	// --------------------------------------------------------------------

	/**
	 * load_field_list
	 *
	 * loads a list of all fields, grouped by channel
	 *
	 * @access	protected
	 * @param   bool    $use_cache allows the use of cache. Default true.
	 * @return	array 	array of field items grouped by channel
	 */

	protected function load_field_list ($use_cache = TRUE)
	{
		if ($use_cache AND isset($this->cache['field_list_items']))
		{
			return $this->cache['field_list_items'];
		}

		$list = array('' => '--');
		// Fetch the channel names

		ee()->db->select('channel_id, channel_title, field_group');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->order_by('channel_title', 'asc');
		$query = ee()->db->get('channels');

		$vars['field_pre_populate_id_options'] = array();

		foreach ($query->result_array() as $row)
		{
			$ct =& $row['channel_title'];

			// Fetch the field names
			ee()->db->select('field_id, field_label');
			ee()->db->where('group_id', $row['field_group']);
			ee()->db->order_by('field_label','ASC');
			$rez = ee()->db->get('channel_fields');

			$list[$ct] = array(
				$row['channel_id'] . '_t' => $ct . ' &gt; ' . lang('title'),
				$row['channel_id'] . '_u' => $ct . ' &gt; ' . lang('url_title')
			);

			if ($rez->num_rows() > 0)
			{
				//loads the list like
				//$list['My Channel Title']['1_1'] = 'My Field Label';

				foreach ($rez->result_array() as $frow)
				{
					$list[$ct][
						$row['channel_id'] . '_' . $frow['field_id']
					] = $ct . ' &gt; ' . $frow['field_label'];
				}
			}
		}

		$this->cache['field_list_items'] = $list;

		return $this->cache['field_list_items'];
	}
	//END load_field_list


	// --------------------------------------------------------------------

	/**
	 * is positive intlike
	 *
	 * returns bool true if its numeric, an integer, not a bool,
	 * and equal to or above the threshold min
	 *
	 * (is_positive_entlike would have taken forever)
	 *
	 * @access 	protected
	 * @param 	mixed 	num 		number/int/string to check for numeric
	 * @param 	int 	threshold 	lowest number acceptable (default 1)
	 * @return  bool
	 */

	protected function is_positive_intlike ($num, $threshold = 1)
	{
		//without is_numeric, bools return positive
		//because preg_match auto converts to string
		return (
			is_numeric($num) AND
			preg_match("/^[0-9]+$/", $num) AND
			$num >= $threshold
		);
	}
	//END is_positive_intlike


	// --------------------------------------------------------------------

	/**
	 * get_post_or_zero
	 *
	 * @access	protected
	 * @param 	string 	name of GET/POST var to check
	 * @return	int 	returns 0 if the get/post is not present or numeric or above 0
	 */

	protected function get_post_or_zero ($name)
	{
		$name = ee()->input->get_post($name);
		return ($this->is_positive_intlike($name) ? $name : 0);
	}
	//END get_post_or_zero


	// --------------------------------------------------------------------

	/**
	 * Returns the entry table name of the given form_id
	 *
	 * @access protected
	 * @param  int 			$form_id form_id of the table to find
	 * @return string 		table name
	 */

	protected function form_table_name ($form_id)
	{
		ee()->load->model('freeform_form_model');
		return ee()->freeform_form_model->table_name($form_id);
	}
	//END form_table_name


	// --------------------------------------------------------------------

	/**
	 * Field Method Link
	 *
	 * makes BASE . AMP . 'key=value' out of arrays
	 *
	 * @access	public
	 * @param 	array 	key value pair of get vars to add to base
	 * @param 	bool 	$real_amp 	use a real ampersand?
	 * @return	string
	 */

	public function field_method_link ($vars = array(), $real_amp = FALSE)
	{
		$base = BASE;

		if ($real_amp)
		{
			$base = str_replace('&amp;', '&', $base);
		}

		$base_vars	= array(
			'C'			=> 'addons_modules',
			'M'			=> 'show_module_cp',
			'module'	=> 'freeform',
			'method'	=> 'field_method'
		);

		if (isset($this->field_id) AND $this->is_positive_intlike($this->field_id))
		{
			$base_vars['field_id'] = $this->field_id;
		}

		if (isset($this->form_id) AND $this->is_positive_intlike($this->form_id))
		{
			$base_vars['form_id'] = $this->form_id;
		}

		$link		= $base;
		$amp		= $real_amp ? '&' : AMP;

		foreach ($base_vars as $key => $value)
		{
			$link .= $amp . $key . '=' . $value;
		}

		if ( ! empty($vars))
		{
			foreach ($vars as $key => $value)
			{
				$link .= $amp . $key . '=' . $value;
			}
		}

		return $link;
	}
	//END mod_link


	// --------------------------------------------------------------------

	/**
	 * Form Prep plus encoding of EE tags
	 *
	 * @access	public
	 * @param	string $str		input string to encode and prep
	 * @return	string			encoded strings
	 */

	public function form_prep_encode_ee ($str = '')
	{
		return $this->encode_ee(form_prep($str));
	}
	//END form_prep_encode_ee


	// --------------------------------------------------------------------

	/**
	 * Encoding of EE tags
	 * Separated from EE function in case we ever need to replace it
	 * or augment it.
	 *
	 * @access	public
	 * @param	string $str		input string to encode
	 * @return	string			encoded strings
	 */

	public function encode_ee ($str = '')
	{
		return ee()->functions->encode_ee_tags($str, TRUE);
	}
	//END form_prep_encode_ee


	// --------------------------------------------------------------------

	/**
	 * full stop
	 *
	 * stop on ajax or user error
	 *
	 * @access	public
	 * @param 	mixed 	string error message
	 * @param 	string 	show_user_error type
	 * @return	null
	 */

	public function full_stop ($errors = '', $error_type = 'submission')
	{
		if ( ! class_exists('Freeform_actions'))
		{
			require_once 'act.freeform.php';
		}

		$actions = new Freeform_actions();

		return $actions->full_stop($errors, $error_type);
	}
}
//END class Freeform_base_ft
