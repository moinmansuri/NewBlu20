<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Fields Library
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/libraries/Freeform_fields.php
 */

//doing this to avoid path third const
$__parent_folder = rtrim(realpath(rtrim(dirname(__FILE__), "/") . '/../'), '/') . '/';

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once $__parent_folder . 'addon_builder/addon_builder.php';
}

if ( ! class_exists('Freeform_base_ft'))
{
	require_once $__parent_folder . 'freeform_base_ft.php';
}

if ( ! class_exists('Freeform_cacher'))
{
	require_once $__parent_folder . 'libraries/Freeform_cacher.php';
}

unset($__parent_folder);

class Freeform_fields extends Addon_builder_freeform
{
	private $data_cache		= array();
	private $default_path	= '';

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

		ee()->load->model('freeform_field_model');
		ee()->load->model('freeform_fieldtype_model');

		$this->default_path = $this->addon_path . 'default_fields/';
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Freeform Field Instance
	 *
	 * Returns an object instance of a field with settings and passed in
	 * options applied
	 *
	 * @access	public
	 * @param 	mixed 	array of options or just a field id
	 * @return	object 	reference to the object to use
	 */

	public function & get_field_instance ($options = array())
	{
		//was just a field_id passed?
		if ( ! is_array($options) AND $this->is_positive_intlike($options))
		{
			$options = array('field_id' => $options);
		}

		$defaults = array(
			'field_id'			=> 0,
			'form_id'			=> 0,
			'entry_id'			=> 0,
			'edit'				=> FALSE,
			'extra_settings'	=> array(),
			'field_data'		=> array(),
			'use_cache'			=> TRUE
		);

		foreach ($defaults as $key => $value)
		{
			$$key = (isset($options[$key])) ? $options[$key] : $value;
		}

		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);

		if ($field_id > 0 AND empty($field_data))
		{
			$field_data = $this->data->get_field_info_by_id($field_id);
		}

		if ($field_id == 0 OR $field_data == FALSE)
		{
			$false = FALSE;
			return $false;
		}

		if ( ! $use_cache OR ! $cache->is_set())
		{
			//get class instance of field
			$new_instance = $this->instantiate_fieldtype($field_data['field_type']);

			$new_instance->show_all_sites	= $this->data->show_all_sites();
			$new_instance->field_id			= $field_id;
			$new_instance->field_name		= $field_data['field_name'];
			$settings						= json_decode($field_data['settings'], TRUE);
			$new_instance->settings			= is_array($settings) ? $settings : array();

			$cache->set($new_instance);
		}

		$instance = $cache->get();

		$instance->form_id 		= $form_id;
		$instance->entry_id 	= $entry_id;
		$instance->edit 		= $edit;

		//extra settings?
		foreach ($extra_settings as $key => $value)
		{
			$instance->settings[$key] = $value;
		}

		return $instance;
	}
	//END get_field_instance


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype Instance
	 * this seems useless, but we need instanciate fieldtype to be _not_
	 * passed by ref, but here we are
	 *
	 * returns an object instance of of the requested fieldtype
	 * This is only a separate function so we can return by reference
	 *
	 * @access	public
	 * @param 	string  name of item to look for
	 * @return	object 	reference to the object to use
	 */

	public function & get_fieldtype_instance ($name, $use_cache = TRUE)
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);

		if ($use_cache AND $cache->is_set())
		{
			$return = $cache->get();
		}
		else
		{
			$return = $cache->set($this->instantiate_fieldtype($name));
		}

		return $return;
	}
	//END get_fieldtype_instance


	// --------------------------------------------------------------------

	/**
	 * instantiate Fieldtype
	 *
	 * files a fieldtypes class and creates a new instance of it
	 *
	 * @access	public
	 * @param 	string  name of item to look for
	 * @return	object 	new instance of object
	 */

	public function instantiate_fieldtype ($name = '')
	{
		//for now, since we just support one per directory...
		$ft_lower_name	= strtolower($name);
		$ft_class_name	= ucfirst($ft_lower_name . '_freeform_ft');
		$file 			= 'freeform_ft.' . $ft_lower_name . '.php';
		$third_path 	= PATH_THIRD . $ft_lower_name . '/';
		$include_pkg	= FALSE;

		if ( ! class_exists($ft_class_name))
		{
			//is this a default?
			if ( file_exists($this->default_path . $file))
			{
				include_once $this->default_path . $file;
			}
			else if ( file_exists($third_path . $file))
			{
				include_once $third_path . $file;
				$include_pkg	= TRUE;
			}

		}

		if ( ! class_exists($ft_class_name))
		{
			//replace with text type as a fallback in case something bad
			//happened or there was a force uninstall
			return $this->instantiate_fieldtype('text');
		}

		if ( $include_pkg )
		{
			ee()->load->add_package_path($third_path);
			$this->lang_autoload($name);
		}

		return new $ft_class_name();
	}
	//END instantiante_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype Available Fieldtypes
	 *
	 * returns an array of all of the installed fieldtypes with instances
	 *
	 * @access	public
	 * @param 	boolean use cache?
	 * @return	array 	available fieldtypes, install status, and versions
	 */

	public function get_available_fieldtypes ($use_cache = TRUE)
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($use_cache AND $cache->is_set()){ return $cache->get(); }

		$installed_fieldtypes 		= ee()->freeform_fieldtype_model->installed_fieldtypes();
		$installable_fieldtypes 	= $this->get_installable_fieldtypes($installed_fieldtypes);

		//this makes sure any updates run before we try to use them
		//
		foreach ($installed_fieldtypes as $name => $version)
		{
			$installed_fieldtypes[$name] = $installable_fieldtypes[$name];
		}

		ksort($installed_fieldtypes);

		return $cache->set($installed_fieldtypes);
	}
	//get_available_fieldtypes


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype defaults
	 *
	 * returns all of the default fieldtypes with
	 * install status, and versions
	 *
	 * @access	public
	 * @param 	boolean use cache?
	 * @return	array 	default fieldtypes, install status, and versions
	 */

	public function get_default_fieldtypes ($use_cache = TRUE)
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($use_cache AND $cache->is_set()){ return $cache->get(); }

		ee()->load->helper('directory');

		$fieldtypes		= array();

		$installed_fieldtypes = ee()->freeform_fieldtype_model->installed_fieldtypes();

		if ($installed_fieldtypes === FALSE)
		{
			$installed_fieldtypes = array();
		}

		$pattern		= "/freeform_ft\.([a-z_\-0-9]+)\.php/is";

		// -------------------------------------
		//	default fields
		// -------------------------------------

		$default_fields = array();

		foreach($this->data->defaults['default_fields'] as $default_field)
		{
			$potential_path = $this->addon_path .
									'default_fields/freeform_ft.' .
									$default_field . '.php';

			if (file_exists($potential_path))
			{
				$default_fields[] = $potential_path;
			}
		}

		foreach ($default_fields as $file)
		{
			if ( preg_match($pattern, $file, $match))
			{
				$ft_lower_name = $match[1];
				$ft_class_name = ucfirst($ft_lower_name . '_freeform_ft');

				include_once $file;

				//if we cannot even get a class, move on
				if (class_exists($ft_class_name))
				{
					$ft_temp = new $ft_class_name;

					$installed = array_key_exists($ft_lower_name, $installed_fieldtypes);

					//version, description required, dog
					$fieldtypes[$ft_lower_name] = array(
						'name' 			=> $ft_temp->info['name'],
						'version' 		=> $ft_temp->info['version'],
						'description'	=> $ft_temp->info['description'],
						'installed' 	=> $installed,
						'default_type' 	=> TRUE,
						'class_name'	=> $ft_class_name
					);

					//mem and crap
					unset($ft_temp);
				}
			}
		}
		//END foreach ($default_fields as $file)

		ksort($fieldtypes);

		return $cache->set($fieldtypes);
	}
	//END get_default_fieldtypes


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype installable
	 *
	 * returns all of the installable fieldtypes with
	 * install status, and versions
	 *
	 * @access	public
	 * @param 	array  	filter array to only get what we need? (TODO)
	 * @param 	boolean use cache?
	 * @return	array 	installable fieldtypes, install status, and versions
	 */

	public function get_installable_fieldtypes ($filter = array(), $use_cache = TRUE)
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($use_cache AND $cache->is_set()){ return $cache->get(); }

		ee()->load->helper('directory');

		$fieldtypes 			= $this->get_default_fieldtypes();

		

		$installed_fieldtypes	= ee()->freeform_fieldtype_model->installed_fieldtypes();

		$installed_updated		= FALSE;

		$third_party_fields		= directory_map(PATH_THIRD);

		//each item in the path third
		foreach ($third_party_fields as $name => $folder)
		{
			$file 			= 'freeform_ft.' . strtolower($name) . '.php';
			$pkg_path		= PATH_THIRD . strtolower($name) . '/';

			if (is_array($folder) AND $name !== 'freeform' AND file_exists($pkg_path . $file))
			{
				$ft_lower_name 	= strtolower($name); //$match[1];
				$ft_class_name 	= ucfirst($ft_lower_name . '_freeform_ft');
				//$pkg_path		= PATH_THIRD . strtolower($name) . '/';

				include_once $pkg_path . $file;

				//if we cannot even get a class, move on
				if (class_exists($ft_class_name))
				{
					ee()->load->add_package_path($pkg_path);

					$this->lang_autoload($name);

					//new instance? YEAAAH
					$ft_temp = new $ft_class_name;

					$installed = array_key_exists($ft_lower_name, $installed_fieldtypes);

					//version, description required, dog
					$fieldtypes[$ft_lower_name] = array(
						'name'			=> $ft_temp->info['name'],
						'version'		=> $ft_temp->info['version'],
						'description'	=> $ft_temp->info['description'],
						'installed'		=> $installed,
						'default_type'	=> FALSE,
						'class_name'	=> $ft_class_name
					);

					//higher version number? run update
					if ($fieldtypes[$ft_lower_name]['installed'] AND
						$this->version_compare(
							$fieldtypes[$ft_lower_name]['version'],
							'>',
							$installed_fieldtypes[$ft_lower_name]['version']
						)
					)
					{
						$ft_temp->update();

						//update version number
						ee()->freeform_fieldtype_model->update(
							array('fieldtype_name' => $ft_lower_name),
							array('version' => $fieldtypes[$ft_lower_name]['version'])
						);
					}

					//mem and crap
					unset($ft_temp);

					ee()->load->remove_package_path($pkg_path);
				}
			}
		}
		//END foreach ($third_party_fields as $name => $folder)

		

		ksort($fieldtypes);

		return $cache->set($fieldtypes);
	}
	//END get_installable_fieldtypes


	// --------------------------------------------------------------------

	/**
	 * Atempts to load a lang file for a directory but only if the file exists
	 *
	 * @access  public
	 * @param  	string $name name of package to attempt to auto load for
	 * @return 	void
	 */

	public function lang_autoload ($name = '', $alternate_file_name = '')
	{
		// -------------------------------------
		//	pre-flight checks
		// -------------------------------------

		$name 					= trim(strtolower($name));

		if ($name == '')
		{
			return;
		}

		$alternate_file_name 	= trim(strtolower($alternate_file_name));
		$loaded 				= in_array($name, ee()->lang->is_loaded);

		if ($loaded AND
			($alternate_file_name == '' OR
			 $name == $alternate_file_name OR
			 in_array($alternate_file_name, ee()->lang->is_loaded)))
		{
			return;
		}

		//check for lang or alternate lang

		$deft_lang 	= ( ! ee()->config->item('language')) ?
						'english' :
						ee()->config->item('language');

		$pkg_path		= PATH_THIRD . strtolower($name) . '/';
		$lang_dir 	 	= $pkg_path . 'language/' . $deft_lang . '/';

		if (is_dir($lang_dir))
		{
			//load standard lang file
			if ( ! $loaded AND
				(file_exists($lang_dir . $name . '_lang.php') OR
				file_exists($lang_dir . 'lang.' . $name . '.php')))
			{
				ee()->lang->loadfile($name);
			}

			//this allows for extra lang files per addon
			if ($alternate_file_name AND $name !== $alternate_file_name)
			{
				if (file_exists($lang_dir . $alternate_file_name . '_lang.php') OR
					file_exists($lang_dir . 'lang.' . $alternate_file_name . '.php'))
				{
					ee()->lang->loadfile($alternate_file_name);
				}
			}
		}
	}
	//END lang_autoload


	// --------------------------------------------------------------------

	/**
	 * Uninstall Fieldtype
	 *
	 * @access	public
	 * @param 	string name of fieldtype to uninstall
	 * @return	void
	 */

	public function uninstall_fieldtype ($name)
	{
		//we have to have this field and do not want it uninstalled
		if ($name == 'text')
		{
			return FALSE;
		}

		$obj = $this->get_fieldtype_instance($name);

		if ( is_object($obj))
		{
			$obj->uninstall();
		}

		//set fields to default text
		ee()->freeform_field_model->update(
			array('field_type' => 'text'),
			array('field_type' => $name)
		);

		$success = ee()->freeform_fieldtype_model->delete(array(
			'fieldtype_name' => $name
		));

		Freeform_cacher::clear(__CLASS__, 'get_available_fieldtypes');

		return $success;
	}
	//END uninstall_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Install Fieldtype
	 *
	 * @access	public
	 * @param 	string name of fieldtype to install
	 * @return	void
	 */

	public function install_fieldtype ($name)
	{
		$obj = $this->get_fieldtype_instance(strtolower($name));

		if ( ! is_object($obj)){ return FALSE; }

		$obj->install();

		ee()->freeform_fieldtype_model->insert(array(
			'fieldtype_name' 	=> $name,
			'version' 			=> $obj->info['version']
		));

		Freeform_cacher::clear(__CLASS__, 'get_available_fieldtypes');

		return TRUE;
	}
	//END install_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Get fieldtype display settings.
	 * allows the use of the table class add_rows
	 * or direct template returning
	 *
	 * @access	public
	 * @param 	string name of fieldtype to get display settings for
	 * @param 	object instnace of object to save mem
	 * @return	string its blank if no output was set, else html
	 */

	public function fieldtype_display_settings_output ($name, $settings = array(), $obj = NULL)
	{
		if ( ! $obj OR ! is_object($obj))
		{
			$obj = $this->get_fieldtype_instance(strtolower($name));
		}

		ee()->load->library('table');
		ee()->load->helper('form');

		$settings_return = $obj->display_settings($settings);

		$return = '';

		//
		if ( ! empty(ee()->table->rows))
		{
			ee()->table->set_template(array(
				'table_open'  => '<table class="mainTable padTable ' .
								 'freeform_table headless" style="width:100%;">'
			));
			$return = ee()->table->generate();
		}
		else if ($settings_return AND
				is_string($settings_return) AND
				trim($settings_return) !== '')
		{
			$return = trim($settings_return);
		}

		//cleanup, isle 4!
		ee()->table->clear();

		return $return;
	}
	//END get_fieldtype_display_settings


	// --------------------------------------------------------------------

	/**
	 * Installs default freeform fieldtypes
	 * deletes all default ones first
	 *
	 * @access  public
	 * @return  null
	 */

	public function install_default_freeform_fields ()
	{
		//delete existing
		ee()->freeform_fieldtype_model->delete(array('default_field' => 'y'));

		$default_fieldtypes = $this->get_default_fieldtypes();

		//insert current
		foreach ($default_fieldtypes as $name => $data)
		{
			ee()->freeform_fieldtype_model->insert(array(
				'fieldtype_name' 	=> $name,
				'version' 			=> FREEFORM_VERSION,
				'default_fieldtype' => 'y'
			));
		}

		Freeform_cacher::clear(__CLASS__, 'get_available_fieldtypes');
	}
	//END install_default_freeform_fields


	// --------------------------------------------------------------------

	/**
	 * updates default freeform fieldtypes
	 *
	 * @access  public
	 * @return  boolean		success
	 */

	public function update_default_freeform_fields ($install_missing = FALSE)
	{
		ee()->load->model('freeform_fieldtype_model');

		if ($install_missing)
		{
			//delete existing
			$installed =	ee()->freeform_fieldtype_model
								->key('fieldtype_name', 'fieldtype_name')
								->where('default_field','y')
								->get();

			$default_fieldtypes = $this->get_default_fieldtypes();

			//insert
			foreach ($default_fieldtypes as $name => $data)
			{
				if ( ! isset($installed[$name]))
				{
					ee()->freeform_fieldtype_model->insert(array(
						'fieldtype_name'	=> $name,
						'version'			=> FREEFORM_VERSION,
						'default_fieldtype'	=> 'y'
					));
				}
			}

			Freeform_cacher::clear(__CLASS__, 'get_available_fieldtypes');
		}

		return ee()->freeform_fieldtype_model->update(
			array('version'			=> FREEFORM_VERSION),
			array('default_field'	=> 'y')
		);
	}
	//END update_default_freeform_fields


	// --------------------------------------------------------------------

	/**
	 * Apply Field Method
	 *
	 * Applies a method to each field while feeding it data
	 *
	 * @access  public
	 * @param 	array 	options
	 * @return  array
	 */

	public function apply_field_method ($options = array())
	{
		$defaults = array(
			'form_id'			=> 0,
			'entry_id'			=> 0,
			'edit'				=> FALSE,
			'method'			=> 'display_field',
			'form_data'			=> array(),
			'field_data'		=> array(),
			'field_input_data'	=> array(),
			'variables'			=> array(),
			'tagdata'			=> (
				isset(ee()->TMPL) AND is_object(ee()->TMPL) ?
					ee()->TMPL->tagdata : ''
			),
			'export_type'		=> ''
		);

		$options = array_merge($defaults, $options);

		//make local keys, but only from defaults
		//no funny business
		foreach ($defaults as $key => $value)
		{
			$$key = (isset($options[$key]) ? $options[$key] : $value);
		}

		// -------------------------------------
		//	checks
		// -------------------------------------

		if (($form_id == 0 AND empty($form_data)))
		{
			return FALSE;
		}

		// -------------------------------------
		//	form_data
		// -------------------------------------

		if ( $form_id == 0 AND isset($form_data['form_id']))
		{
			$form_id = $form_data['form_id'];
		}
		else if (empty($form_data))
		{
			$form_data = $this->data->get_form_info($form_id);
		}

		// -------------------------------------
		//	entry id
		// -------------------------------------

		if ( $entry_id == 0 AND isset($field_input_data['entry_id']))
		{
			$entry_id = $field_input_data['entry_id'];
		}

		if ( $entry_id != 0 AND ! is_array($entry_id) AND empty($field_input_data))
		{
			$field_input_data = $this->data->get_entry_data_by_id(
				$entry_id,
				$form_id
			);
		}

		// -------------------------------------
		//	field data
		// -------------------------------------

		if (empty($field_data) AND isset($form_data['fields']))
		{
			$field_data = $form_data['fields'];
		}

		//some functions need a single entry_id, and if this is an array
		//of IDs then we just send 0
		$send_entry_id = $this->is_positive_intlike($entry_id) ? $entry_id : 0;

		$multipart = FALSE;

		// -------------------------------------
		//	display fields
		// -------------------------------------

		foreach ($field_data as $field_id => $f_data)
		{
			// -------------------------------------
			//	get instance of field
			// -------------------------------------

			//get class instance of field
			$instance =& $this->get_field_instance(array(
				'field_id'			=> $field_id,
				'form_id'			=> $form_id,
				'entry_id'			=> $send_entry_id,
				'edit'				=> $edit,
				'field_data'		=> $f_data,
				'extra_settings'	=> array(
					'entry_id'	=> $send_entry_id,
					'preview'	=> (
						isset($f_data['preview']) AND
						$f_data['preview'] === TRUE
					)
				)
			));

			//if we cannot call it, move on
			//we have pre-defines for all functions but still
			if ( ! is_callable(array($instance, $method)))
			{
				continue;
			}

			// -------------------------------------
			//	names n stuff
			// -------------------------------------

			$col_name		= ee()->freeform_form_model->form_field_prefix . $field_id;

			$f_prefix		= (
				$method == 'display_field' OR
				$method == 'replace_tag'
			) ? 'freeform:field:' : '';

			$field_tag_name = $f_prefix . $f_data['field_name'];

			// -------------------------------------
			//	some methods have no need for tagdata
			// -------------------------------------

			if ($method == 'delete')
			{
				//delete should always get
				if ( ! is_array($entry_id))
				{
					$entry_id = array($entry_id);
				}

				$instance->delete($entry_id);

				continue;
			}
			else if ($method == 'remove_from_form')
			{
				$instance->remove_from_form($form_id);

				continue;
			}
			else if ($method == 'pre_process_entries')
			{
				$instance->pre_process_entries($entry_id);

				continue;
			}

			// -------------------------------------
			//	reload previous data
			// -------------------------------------

			$display_field_data = '';

			if (isset($field_input_data[$f_data['field_name']]))
			{
				$display_field_data = $field_input_data[$f_data['field_name']];
			}
			else if (isset($field_input_data[$col_name]))
			{
				$display_field_data = $field_input_data[$col_name];
			}
			//if we didn't meet the previous two issets, then we have nothing
			else if ($method == 'export')
			{
				continue;
			}

			// -------------------------------------
			//	export?
			// -------------------------------------

			if ($method == 'export')
			{
				$variables[$f_data['field_name']] = $instance->export(
					$display_field_data,
					$export_type
				);

				continue;
			}

			// -------------------------------------
			//	CP display?
			// -------------------------------------

			if ($method == 'display_entry_cp')
			{
				$variables[$col_name] = $instance->display_entry_cp(
					$display_field_data
				);

				continue;
			}

			// -------------------------------------
			//	does the isntance require a multipart form
			// -------------------------------------

			if ($instance->requires_multipart)
			{
				$multipart = TRUE;
			}

			// -------------------------------------
			//	parse tagdata?
			// -------------------------------------

			if ($tagdata AND preg_match_all(
				"/" . LD . preg_quote($field_tag_name, '/') . '\b(.*?)' .
				RD . "/s",
				$tagdata,
				$matches
			))
			{
				//we need a sep tagdata for positioning
				$p_tagdata = $tagdata;

				//pre process?
				if ($method == 'replace_tag' AND
					is_callable(array($instance, 'pre_process')))
				{
					$display_field_data = $instance->pre_process($display_field_data);
				}

				$total_matches 	= count($matches[0]);

				for ($i = 0; $i < $total_matches; $i++)
				{
					$args 				= array();
					$attr 				= array();
					$params 			= array();
					$tagpair_tagdata	= array();

					// Checking for variables/tags embedded within tags
					// {exp:channel:entries channel="{master_channel_name}"}
					if (stristr(substr($matches[0][$i], 1), LD) !== FALSE)
					{
						$matches[0][$i] = ee()->functions->full_tag($matches[0][$i], $tagdata);
					}

					if (trim($matches[1][$i]) != '')
					{
						$args = ee()->functions->assign_parameters($matches[1][$i]);

						if ($args)
						{
							//Because I'm a jerkpants! That's why!
							//Oh, and it would totally screw up saving data.
							unset($args['attr:name'], $args['attr:value']);

							//separate attr's and params
							foreach ( $args as $key => $value)
							{
								if (substr($key, 0, 5) == 'attr:')
								{
									$attr[substr($key, 5)] = $value;
								}
								else
								{
									$params[$key] = $value;
								}
							}
						}
					}

					$tagpair_replace 	= $matches[0][$i];

					$p_start			= strpos($p_tagdata, $matches[0][$i]);
					$t_length 			= strlen($matches[0][$i]);
					$d_start 			= $p_start + $t_length;
					$field_tag_open		= LD . $field_tag_name;
					$field_tag_close	= LD . '/' . $field_tag_name . RD;

					$p_tagdata			= substr($p_tagdata, $d_start);

					$next_start			= strpos($p_tagdata, $field_tag_open);
					$next_end			= strpos($p_tagdata, $field_tag_close);

					if ($next_end !== FALSE AND ($next_start === FALSE OR $next_start > $next_end))
					{
						$tagpair_tagdata 	= substr($p_tagdata, 0, $next_end);

						//if we have the same thing, but with different insides
						//we dont want to send it to the array as the same thing
						$tagpair_replace = substr(trim($tagpair_replace), 0, -1) .
							' freeform_replace_marker="' . $i . '"' . RD;

						//our replace method will take care of all
						//tag pairs that are exactly the same
						$tagdata 			= str_replace(
							$matches[0][$i] . $tagpair_tagdata . $field_tag_close,
							$tagpair_replace,
							$tagdata
						);

						//shrink subset
						$p_tagdata = substr($p_tagdata, $next_end + strlen($next_end));
					}

					//remove the beginning and ending {}
					$var_name = trim(trim($tagpair_replace), '{}');

					//default value available?
					if ($display_field_data == '' AND
						isset($params['default_value']))
					{
						$display_field_data = str_replace('|', "\n", $params['default_value']);
					}

					if ($method == 'display_field')
					{
						$variables[$var_name] =
							$instance->display_field(
								$display_field_data,
								$params,
								$attr,
								$tagpair_tagdata
							);
					}
					else if ($method == 'replace_tag')
					{
						$variables[$var_name] =
							$instance->replace_tag(
								$display_field_data,
								$params,
								$tagpair_tagdata
							);
					}
				}
				//END for ($i
			}
			//END if preg_match_all
		}
		//END foreach ($form_data['fields'] as $field_id => $field_data)

		//since these all default, its safe to return them each time
		//it just wont matter for things like removing forms and deleting
		//entries
		return array(
			'variables' => $variables,
			'tagdata' 	=> $tagdata,
			'multipart' => $multipart
		);
	}
	//END apply_field_method


	// --------------------------------------------------------------------

	/**
	 * Delete Field
	 *
	 * checks field to see if its in any forms, then removes the field
	 *
	 * @access public
	 * @param  mixed 	int/array of ints 	$field_id field id to delete
	 * @return boolean	success
	 */

	public function delete_field ($field_ids)
	{
		if ( ! is_array($field_ids) AND
			 ! $this->is_positive_intlike($field_ids))
		{
			return FALSE;
		}

		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		$field_ids = array_filter($field_ids, array($this, 'is_positive_intlike'));

		ee()->load->library('freeform_forms');

		//go through each field and remove it from every form its in
		$remove_fields = array();

		foreach ($field_ids as $field_id)
		{
			$field_form_data = $this->data->get_form_info_by_field_id($field_id);

			if ( $field_form_data !== FALSE )
			{
				foreach ($field_form_data as $form_id => $form_data)
				{
					ee()->freeform_forms->remove_field_from_form($form_id, $field_id);
				}
			}

			$instance =& $this->get_field_instance($field_id);

			if (is_callable(array($instance, 'delete_field')))
			{
				$instance->delete_field();
			}

			$remove_fields[] = $field_id;
		}

		if (empty($remove_fields)){ return FALSE; }

		//delete all fields
		ee()->freeform_field_model->where_in('field_id', $remove_fields)->delete();

		Freeform_cacher::clear(__CLASS__, 'get_field_instance');

		return TRUE;
	}
	//END delete_field


	// --------------------------------------------------------------------

	/**
	 * validate
	 *
	 * runs field validation against passed data
	 *
	 * @access	public
	 * @param	int  	ID of form to check
	 * @param	array 	field input data to validate
	 * @param	bool	include_names
	 * @return	array 	error array
	 */

	public function validate ($form_id, $field_input_data, $include_labels = TRUE)
	{
		$errors = array();

		if ( ! $this->data->is_valid_form_id($form_id))
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$form_data = $this->data->get_form_info($form_id);

		//loading for fields themselves if they want it
		ee()->load->library('form_validation');

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if ( ! isset($field_input_data[$field_data['field_name']]))
			{
				continue;
			}

			//get class instance of field
			$instance =& $this->get_field_instance(array(
				'field_id'			=> $field_id,
				'form_id'			=> $form_id
			));

			if ( ! is_object($instance) OR
				 ! is_callable(array($instance, 'validate')))
			{
				continue;
			}

			$output = $instance->validate(
				$field_input_data[$field_data['field_name']]
			);

			if ($output !== TRUE)
			{

				$prefix = ($include_labels) ?  $field_data['field_label'] . ': ' : '';

				//if the field itself defines errors, lets show them
				//else we show a generic
				if ( is_array($output) AND ! empty($output))
				{
					if ( ! isset($errors[$field_data['field_name']]))
					{
						$errors[$field_data['field_name']] = array();
					}
					//did this start as a string?
					else if ( ! is_array($errors[$field_data['field_name']]))
					{
						$errors[$field_data['field_name']] = (array) $errors[$field_data['field_name']];
					}

					//if we are prefixing for general output
					//lets make sure any incoming items are prefixed as well
					if ($prefix AND ! empty($errors[$field_data['field_name']]))
					{
						foreach ($errors[$field_data['field_name']] as $e_k => $e_v)
						{
							if (substr($e_v, 0, strlen($prefix)) !== $prefix)
							{
								$errors[$field_data['field_name']][$e_k] = $prefix . $e_v;
							}
						}
					}

					//add new errors
					foreach($output as $field_error)
					{
						$errors[$field_data['field_name']][] = $prefix . $field_error;
					}
				}
				else if (is_string($output))
				{
					$errors[$field_data['field_name']] = $prefix . $output;
				}
				else
				{
					$errors[$field_data['field_name']] = $prefix . lang('generic_invalid_field_input');
				}
			}
		}

		return $errors;
	}
	//END validate

}
//END Freeform_notifications
