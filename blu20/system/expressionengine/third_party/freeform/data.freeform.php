<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Data Models
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/data.freeform.php
 */

if ( ! class_exists('Addon_builder_data_freeform'))
{
	require_once 'addon_builder/data.addon_builder.php';
}

if ( ! class_exists('Freeform_cacher'))
{
	require_once 'libraries/Freeform_cacher.php';
}

class Freeform_data extends Addon_builder_data_freeform
{
	public $cached						= array();

	//various default values for backend
	public $defaults					= array(
		//MCP
		'mcp_row_limit' 			=> 50,
		//fields
		'field_length'				=> 150,
		'field_type'				=> 'text',
		//forms
		'default_form_status'		=> 'pending',
		//notifications
		'wordwrap'					=> 'y',
		'allow_html'				=> 'n',
		'default_fields'			=> array(
			
			"checkbox",
			"checkbox_group",
			"country_select",
			"hidden",
			"multiselect",
			"province_select",
			"radio",
			"select",
			"state_select",
			
			"file_upload",
			"mailinglist",
			"text",
			"textarea"
		)
	);

	//cannot be used in form or field names
	public $prohibited_names			= array(
		'all_form_fields',
		'attachment_count',
		'attachments',
		'author',
		'author_id',
		'channel_id',
		'complete',
		'edit_date',
		'entry_date',
		'entry_id',
		'form_label',
		'form_name',
		'form_id',
		'freeform_entry_id',
		'group_id',
		'hash_stored_data',
		'ip_address',
		'status',
		'template',
	);

	//statuses for forms with more added through prefs?
	public $form_statuses				= array(
		'pending',
		'open',
		'closed'
	);

	//default pref values with type of input
	public $default_preferences			= array(
		'default_show_all_site_data'	=> array('type' => 'y_or_n',	'value' => 'n'),
		'use_solspace_mcp_style'		=> array('type' => 'y_or_n',	'value' => 'y'),
		//'censor_using_ee_word_list' 	=> array('type' => 'y_or_n',	'value' => 'n'),

		'spam_keyword_ban_enabled'		=> array('type' => 'y_or_n',	'value' => 'n'),
		'spam_keywords'					=> array('type' => 'textarea',	'value' => "viagra\ncialis"),
		'spam_keyword_ban_message'		=> array('type' => 'text',		'value' => 'invalid entry data'),
		'form_statuses'					=> array('type' => 'list',		'value' => array()),

		'max_user_recipients'			=> array('type' => 'int',		'value' => 10),
		'enable_spam_prevention'		=> array('type' => 'y_or_n',	'value' => 'y'),
		'spam_count'					=> array('type' => 'int',		'value' => 30),
		'spam_interval'					=> array('type' => 'int',		'value' => 60),
		//'allow_user_field_layout'		=> array('type' => 'y_or_n',	'value' => 'y'),

		'multi_form_timeout'			=> array('type' => 'int',		'value' => 7200),
		'keep_unfinished_multi_form'	=> array('type' => 'y_or_n',	'value' => 'n'),

		'cp_date_formatting'			=> array('type' => 'text',		'value' => 'Y-m-d - H:i'),
		'hook_data_protection'			=> array('type' => 'y_or_n',	'value' => 'y'),
		'disable_missing_submit_warning'=> array('type' => 'y_or_n',	'value' => 'n'),
	);

	//default pref values with type of input
	public $default_global_preferences	= array(
		'prefs_all_sites'			=> array('type' => 'y_or_n',	'value' => 'y')
	);

	public $admin_only_prefs			= array(
		'allow_user_field_layout'
	);

	public $msm_only_prefs				= array(
		'default_show_all_site_data'
	);

	public $standard_notification_tags	= array(
		'all_form_fields'			=> "{all_form_fields}\n\t{field_label}\n\t{field_data}\n{/all_form_fields}",
		'all_form_fields_string'	=> '{all_form_fields_string}',
		'freeform_entry_id'			=> '{freeform_entry_id}',
		'entry_date'				=> '{entry_date format=&quot;%Y-%m-%d - %H:%i&quot;}',
		'form_name'					=> '{form_name}',
		'form_label'				=> '{form_label}',
		'form_id'					=> '{form_id}',
		'attachments'				=> "{attachments}\n\t{fileurl}\n\t{filename}\n{/attachments}",
		'attachment_count'			=> '{attachment_count}'
	);

	public $msm_enabled					= FALSE;

	public $allowed_html_tags			= array(
		'p','br','a','strong','b','i','em',
		'dl','dd','dt','ul','ol','li', 'a',
		'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'address'
	);

	private $show_all_sites;
	private $module_preferences;
	private $global_module_preferences;

	public $doc_links					= array(
		'custom_fields'				=> 'http://solspace.com/docs/freeform/fieldtype_development/'
	);

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct($obj = null)
	{
		parent::__construct($obj);

		$this->msm_enabled = $this->check_yes(
			ee()->config->item('multiple_sites_enabled')
		);
	}
	//END __construct()


	// --------------------------------------------------------------------

	/**
	 * Show all sites
	 *
	 * do we want to show data from all sites?
	 *
	 * @access	public
	 * @return	bool
	 */

	public function show_all_sites ()
	{
		if ( ! isset($this->show_all_sites))
		{
			$this->show_all_sites = (
				! $this->msm_enabled OR
				! $this->check_no(
					$this->preference('default_show_all_site_data')
				)
			);
		}

		return $this->show_all_sites;
	}
	//END show_all_sites


	// --------------------------------------------------------------------

	/**
	 * Get Form Statuses
	 *
	 * returns an array of form statuses
	 *
	 * @access	public
	 * @return	array
	 */

	public function get_form_statuses ()
	{
		$statuses = array();

		foreach ($this->form_statuses as $status)
		{
			$statuses[$status] = lang($status);
		}



		$pref_statuses = $this->preference('form_statuses');

		if ($pref_statuses)
		{
			foreach ($pref_statuses as $status)
			{
				$statuses[form_prep($status)] = form_prep($status);
			}
		}



		return $statuses;
	}
	//END get_form_statuses


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype Installed
	 *
	 * Returns true if a fieldtype is installed, false if not
	 * caches list of installed fieldtypes
	 *
	 * @access	public
	 * @param	string 		$ft_name 	fieldtype name
	 * @return	boolean
	 */

	public function freeform_fieldtype_installed ($ft_name)
	{
		//cache?
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		ee()->load->model('freeform_fieldtyle_model');

		$installed_fieldtypes = ee()->freeform_fieldtype_model->installed_fieldtypes();

		//set cache and return
		return $cache->set(array_key_exists(
			$ft_name,
			$installed_fieldtypes
		));
	}
	//END freeform_fieldtype_installed


	// --------------------------------------------------------------------

	/**
	 * get_form_submissions_count
	 *
	 * returns the total submissions for the entry table
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	int  	total table count
	 */

	public function get_form_submissions_count ($form_id)
	{
		ee()->load->model('freeform_entry_model');

		if ( ! $this->show_all_sites())
		{
			ee()->freeform_entry_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		//set cache and return
		return ee()->freeform_entry_model->id($form_id)->count();
	}
	//END get_form_submissions_count


	// --------------------------------------------------------------------

	/**
	 * get_form_needs_moderation_count
	 *
	 * returns the total items needing moderation
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	int  	total moderation count
	 */

	public function get_form_needs_moderation_count ($form_id)
	{
		ee()->load->model('freeform_entry_model');

		if ( ! $this->show_all_sites())
		{
			ee()->freeform_entry_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		return ee()->freeform_entry_model->id($form_id)->count(
			array('status' => 'pending')
		);
	}
	//END get_form_needs_moderation_count


	// --------------------------------------------------------------------

	/**
	 * get_available_notification_templates
	 *
	 * @access	public
	 * @return	array types of notifications with id=>label pairs
	 */

	public function get_available_notification_templates ()
	{
		ee()->load->model('freeform_notification_model');

		if ( ! $this->show_all_sites())
		{
			ee()->freeform_notification_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$notifications = ee()->freeform_notification_model
							 ->key('notification_id', 'notification_label')
							 ->get();

		$items = array('0' => lang('default'));

		if ($notifications)
		{
			foreach ($notifications as $key => $value)
			{
				$items[$key] = $value;
			}
		}

		return $items;
	}
	//get_available_notification_templates


	// --------------------------------------------------------------------

	/**
	 * set_module_preferences
	 *
	 * @access	public
	 * @param	array  associative array of prefs and values
	 * @return	null
	 */

	public function set_module_preferences ($new_prefs = array())
	{
		//no shenanigans
		if (empty($new_prefs))
		{
			return;
		}

		// -------------------------------------
		//	separate global prefs
		// -------------------------------------

		$global_prefs = array();

		foreach ($new_prefs as $key => $value)
		{
			if (array_key_exists($key, $this->default_global_preferences))
			{
				$global_prefs[$key] = $value;
				unset($new_prefs[$key]);
			}
		}

		// -------------------------------------
		//	site id?
		// -------------------------------------

		$prefs_all_sites = FALSE;

		if (isset($global_prefs['prefs_all_sites']))
		{
			$prefs_all_sites = (
				! $this->check_no($global_prefs['prefs_all_sites'])
			);
		}
		else
		{
			$prefs_all_sites = (
				! $this->check_no(
					$this->global_preference('prefs_all_sites')
				)
			);
		}

		$site_id = $prefs_all_sites ? 1 : ee()->config->item('site_id');

		// -------------------------------------
		//	get old prefs
		// -------------------------------------

		$prefs   = array();

		ee()->load->model('freeform_preference_model');

		$p_query = ee()->freeform_preference_model->get();

		if ($p_query !== FALSE)
		{
			//either we've changed to prefs all sites,
			//or its been that way
			//either way, we need to set some prelims
			if ($prefs_all_sites)
			{
				foreach ($p_query as $row)
				{
					// if we are swaping to one set of prefs,
					// or we are swtiching to one set of prefs
					// then we just want old prefs from site_id 1
					// this will then preserve things like layout prefs
					// from site_id 1
					if ($prefs_all_sites AND
						! in_array($row['site_id'], array(0, 1, '0', '1'), TRUE)
					)
					{
						continue;
					}

					if ( ! isset($prefs[$row['site_id']]))
					{
						$prefs[$row['site_id']] = array();
					}

					$prefs[$row['site_id']][$row['preference_name']] = $row['preference_value'];
				}
			}
		}

		//kill all entries so we don't have to mix updates
		ee()->freeform_preference_model->clear_table();

		//keep old prefs and only overwrite updated/ add new
		//manual merge because we need the numeric keys intact
		if (array_key_exists($site_id, $prefs))
		{
			//if the old pref
			foreach ($prefs as $pref_site_id => $sub_prefs)
			{
				if ($pref_site_id == $site_id)
				{
					foreach ($new_prefs as $key => $value)
					{
						$prefs[$pref_site_id][$key] = $value;
					}
				}
			}
		}
		else
		{
			$prefs[$site_id] = $new_prefs;
		}

		//globals
		$prefs[0] = (isset($prefs[0])) ?
						array_merge($prefs[0], $global_prefs) :
						$global_prefs;

		// -------------------------------------
		//	prepare to insert new prefs
		// -------------------------------------

		foreach($prefs as $site_id => $sub_prefs)
		{
			foreach ($sub_prefs as $key => $value)
			{
				if (is_array($value))
				{
					$value = json_encode($value);
				}

				ee()->freeform_preference_model->insert(array(
					'preference_name' 	=> $key,
					'preference_value'	=> $value,
					'site_id' 			=> $site_id
				));
			}
		}
	}
	// END set_module_preferences


	// --------------------------------------------------------------------

	/**
	 * get_module_preferences
	 *
	 * @access	public
	 * @return	array 	all prefs with default backups
	 */

	public function get_module_preferences()
	{
		//no need for caching here because the model will do it for us

		// -------------------------------------
		//	get prefs
		// -------------------------------------

		$prefs = array();

		//start with defaults
		foreach ($this->default_preferences as $key => $data)
		{
			$prefs[$key] = $data['value'];
		}

		// -------------------------------------
		//	Are we in 4.x yet?
		// -------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0'))
		{
			return $prefs;
		}

		//installed?
		if ($this->database_version())
		{
			$site_id = (
				! $this->check_no($this->global_preference('prefs_all_sites')) ?
					1 :
					ee()->config->item('site_id')
			);

			ee()->load->model('freeform_preference_model');

			$query =	ee()->freeform_preference_model
							->key('preference_name', 'preference_value')
							->where('site_id', $site_id)
							->get();

			//override any of not all defaults
			if ($query !== FALSE)
			{
				$prefs = array_merge($prefs, $query);
			}
		}

		return $prefs;
	}
	//END get_module_preferences


	// --------------------------------------------------------------------

	/**
	 * get_global_module_preferences
	 *
	 * @access	public
	 * @return	array 	all prefs with default backups
	 */

	public function get_global_module_preferences()
	{
		//no need for caching here because the model will do it for us

		$prefs = array();

		//start with defaults
		foreach ($this->default_global_preferences as $key => $data)
		{
			$prefs[$key] = $data['value'];
		}

		// -------------------------------------
		//	Are we in 4.x yet?
		// -------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0'))
		{
			//best not set the cache here in case we need it after we upgrade
			return $prefs;
		}

		//installed?
		if ($this->database_version())
		{
			ee()->load->model('freeform_preference_model');

			$query =	ee()->freeform_preference_model
							->key('preference_name', 'preference_value')
							->where('site_id', '0')
							->get();

			//override any of not all defaults
			if ($query !== FALSE)
			{
				$prefs = array_merge($prefs, $query);
			}
		}

		return $prefs;
	}
	//END get_global_module_preferences


	// --------------------------------------------------------------------

	/**
	 * Find and return global preference
	 *
	 * Any number of possible arguments, although typically
	 * there will be only one or two
	 *
	 * @access	public
	 * @param	string			Preference to retrieve
	 * @return	null|string		If preference does not exist,
	 * 								NULL is returned, else the value
	 */

	public function global_preference ()
	{
		$s = func_num_args();

		if ($s == 0)
		{
			return NULL;
		}

		//--------------------------------------------
		// Fetch Module Preferences
		//--------------------------------------------

		if ( ! isset($this->global_module_preferences) OR
			empty($this->global_module_preferences))
		{
			$this->global_module_preferences = $this->get_global_module_preferences();
		}

		//--------------------------------------------
		// Find Our Value, If It Exists
		//--------------------------------------------

		$value = (isset($this->global_module_preferences[func_get_arg(0)])) ?
					$this->global_module_preferences[func_get_arg(0)] : NULL;

		for($i = 1; $i < $s; ++$i)
		{
			if ( ! isset($value[func_get_arg($i)]))
			{
				return NULL;
			}

			$value = $value[func_get_arg($i)];
		}

		return $value;
	}
	// END global_preference()


	// --------------------------------------------------------------------

	/**
	 * is_valid_form_id
	 *
	 * @access	public
	 * @param	int 	id of form to check
	 * @return	bool 	is valid id
	 */

	public function is_valid_form_id ( $form_id = 0)
	{
		ee()->load->model('freeform_form_model');

		return (ee()->freeform_form_model->count($form_id) > 0);
	}
	// END is_valid_form_id()


	// --------------------------------------------------------------------

	/**
	 * is_valid_entry_id
	 *
	 * @access	public
	 * @param	int 	id of field to check
	 * @param	int 	id of form to check
	 * @return	bool 	is valid id
	 */

	public function is_valid_entry_id ( $entry_id = 0, $form_id = 0)
	{
		//proper INTs?
		if ( ! $this->is_positive_intlike($entry_id) OR
			 ! $this->is_valid_form_id($form_id))
		{
			return FALSE;
		}

		ee()->load->model('freeform_entry_model');

		return (ee()->freeform_entry_model->id($form_id)->count($entry_id) > 0);
	}
	// END is_valid_entry_id()


	// --------------------------------------------------------------------

	/**
	 * get_form_id_by_name
	 *
	 * @access	public
	 * @param	string 	name of field the id is desired for
	 * @param	bool 	use cache
	 * @return	mixed 	name of form or FALSE
	 */

	public function get_form_id_by_name ( $form_name = '')
	{
		if ( trim($form_name) == ''){ return FALSE;	}

		ee()->load->model('freeform_form_model');

		$row = ee()->freeform_form_model->select('form_id')
										->get_row(array('form_name' => $form_name));

		return ($row ? $row['form_id'] : FALSE);
	}
	// END get_form_id_by_name()


	// --------------------------------------------------------------------

	/**
	 * get entry data by id
	 *
	 * @access	public
	 * @param	int  	entry id is desired for
	 * @param 	int 	form_id desired
	 * @param	bool 	use cache
	 * @return	mixed 	name of form or FALSE
	 */

	public function get_entry_data_by_id ( $entry_id = 0, $form_id = 0, $use_cache = TRUE )
	{
		//valid INTs?
		if ( ! $this->is_positive_intlike($entry_id) OR
			 ! $this->is_valid_form_id($form_id))
		{
			return FALSE;
		}

		ee()->load->model('freeform_entry_model');

		return ee()->freeform_entry_model->id($form_id)->get_row($entry_id);
	}
	// END get_entry_data_by_id


	// --------------------------------------------------------------------

	/**
	 * get_form_info
	 *
	 * @access	public
	 * @param	number 	id of form that info is needed for
	 * @return	array 	data from form
	 */

	public function get_form_info ( $form_id = 0 )
	{
		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_field_model');

		$data = ee()->freeform_form_model->get_row($form_id);

		if (empty($data))
		{
			return FALSE;
		}

		//get all associated fields and add in
		if ( ! empty($data['field_ids']) )
		{
			$field_data = ee()->freeform_field_model->get(array(
				'field_id' => $data['field_ids']
			));

			//get fields
			if ( ! empty($field_data))
			{
				foreach ($field_data as $row)
				{
					$data['fields'][$row['field_id']] = $row;
				}
			}
			else
			{
				$data['fields'] = array();
			}
		}
		else
		{
			$data['fields'] = array();
		}

		//get all associated composer fields and add in
		$data['composer_field_ids']	= ''; $data['composer_fields']	= array();

		if ( ! empty($data['composer_id']) )
		{
			ee()->load->model('freeform_composer_model');
			$cdata = ee()->freeform_composer_model->get_row($data['composer_id']);

			if ( ! empty( $cdata['composer_data'] ) )
			{
				$fields	= (array) $this->json_decode( $cdata['composer_data'] );

				if ( ! empty( $fields['fields'] ) )
				{
					$data['composer_field_ids']	= $fields['fields'];

					sort($data['composer_field_ids']);

					$find = array_diff($data['composer_field_ids'], array_keys($data['fields']));

					$c_fields = array();

					if ( ! empty($find))
					{
						$c_field_data = ee()->freeform_field_model->get(array(
							'field_id' => $find
						));

						if ($c_field_data !== FALSE)
						{
							foreach ($c_field_data as $row)
							{
								$c_fields[$row['field_id']] = $row;
							}
						}
					}

					//set fields
					foreach ($data['composer_field_ids'] as $field_id)
					{
						if (isset($data['fields'][$field_id]))
						{
							$data['composer_fields'][$field_id] =& $data['fields'][$field_id];
						}
						else if (isset($c_fields[$field_id]))
						{
							$data['composer_fields'][$field_id] = $c_fields[$field_id];
						}
					}

					unset($c_fields);
				}
			}
		}

		return $data;
	}
	// END get_form_info()


	// --------------------------------------------------------------------

	/**
	 * get_field_info_by_id
	 *
	 * @access	public
	 * @param	number 	$field_id 	id of field that info is needed for
	 * @return	array 	data from form
	 */

	public function get_field_info_by_id ( $field_id = 0)
	{
		ee()->load->model('freeform_field_model');

		return ee()->freeform_field_model->get_row($field_id);
	}
	// END get_field_info_by_id()


	// --------------------------------------------------------------------

	/**
	 * Get Notfication info By ID
	 *
	 * @access	public
	 * @param	number 		$notificatio_id 	id of notification
	 * @return	array 		data from form
	 */

	public function get_notification_info_by_id ( $notification_id = 0)
	{
		ee()->load->model('freeform_notification_model');

		return ee()->freeform_notification_model->get_row($notification_id);
	}
	// END get_notification_info_by_id()


	// --------------------------------------------------------------------

	/**
	 * get_form_info_by_field_id
	 *
	 * @access	public
	 * @param	number 		$field_id 	id of field that info is needed for
	 * @param   boolean  	$use_cache	use cache?
	 * @return	array 					data of forms by id
	 */

	public function get_form_info_by_field_id ( $field_id = 0, $use_cache = TRUE )
	{
		ee()->load->model('freeform_form_model');

		return ee()->freeform_form_model->forms_with_field_id($field_id, $use_cache);
	}
	// END get_form_ids_by_field_id()


	// --------------------------------------------------------------------

	/**
	 * get_form_info_by_notification_id
	 *
	 * @access	public
	 * @param	number 	id of field that info is needed for
	 * @return	array 	data of forms by id
	 */

	public function get_form_info_by_notification_id ( $notification_id = 0)
	{
		if ( ! $this->is_positive_intlike($notification_id)){ return FALSE; }

		// -------------------------------------
		//  get form info
		// -------------------------------------

		ee()->load->model('freeform_form_model');

		$query =	ee()->freeform_form_model
						->key('form_id')
						->where('user_notification_id', $notification_id)
						->or_where('admin_notification_id', $notification_id)
						->get();

		return $query;
	}
	// END get_form_info_by_notification_id()


	// --------------------------------------------------------------------
}
// END CLASS Freeform_data
