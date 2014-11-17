<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Updater
 *
 * In charge of the install, uninstall, and updating of the module.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/upd.freeform.php
 */

if ( ! class_exists('Module_builder_freeform'))
{
	require_once 'addon_builder/module_builder.php';
}

class Freeform_upd extends Module_builder_freeform
{

	public $module_actions		= array();
	public $hooks				= array();
	public $default_settings	= array();

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

		// --------------------------------------------
		//  Module Actions
		// --------------------------------------------

		$this->module_actions	= array(
			'save_form'
		);

		// --------------------------------------------
		//  Extension Hooks
		// --------------------------------------------

		$default				= array(
			'class'			=> $this->extension_name,
			'settings'		=> '',
			'priority'		=> 10,
			'version'		=> FREEFORM_VERSION,
			'enabled'		=> 'y'
		);

		$this->hooks			= array();
	}
	// END Freeform_updater_base()


	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */

	public function install()
	{
		// Already installed, let's not install again.
		if ($this->database_version() !== FALSE)
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Our Default Install
		// --------------------------------------------

		if ($this->default_module_install() == FALSE)
		{
			return FALSE;
		}

		// -------------------------------------
		//	there are issues installing default
		//	fields if the mod is selected to
		//	install when EE itself is installed
		// -------------------------------------

		if ( strpos( APPPATH, '/installer/' ) === FALSE )
		{
			// --------------------------------------------
			//  Load default field types
			// --------------------------------------------

			ee()->load->library('freeform_fields');

			ee()->freeform_fields->install_default_freeform_fields();

			// --------------------------------------------
			//  Install sample fields and form
			// --------------------------------------------

			$this->install_sample_fields_and_form();
		}

		// --------------------------------------------
		//  Module Install
		// --------------------------------------------

		ee()->db->insert(
			'exp_modules',
			array(
				'module_name'		=> $this->class_name,
				'module_version'	=> FREEFORM_VERSION,
				'has_cp_backend'	=> 'y'
			)
		);

		ee()->load->model('freeform_preference_model');

		ee()->freeform_preference_model->insert(array(
			'preference_name' 	=> 'ffp',
			'preference_value'	=> FREEFORM_PRO ? 'y' : 'n',
			'site_id' 			=> '0'
		));

		return TRUE;
	}
	// END install()


	// --------------------------------------------------------------------

	/**
	 * Install sample fields and form
	 *
	 * @access	public
	 * @return	bool
	 */

	public function install_sample_fields_and_form()
	{
		// --------------------------------------------
		//  Get default site id
		// --------------------------------------------

		$query		= ee()->db->select('site_id')
							  ->order_by('site_id')
							  ->get('sites', '1');

		$site_id	= ( $query->num_rows() == 0 ) ? 1: $query->row('site_id');

		// --------------------------------------------
		//  Get default author id
		// --------------------------------------------

		$query		= ee()->db->select('member_id, email')
							  ->where('group_id', '1')
							  ->order_by('member_id')
							  ->get('members', 1);

		$author_id		= ( $query->num_rows() == 0 ) ? 1: $query->row('member_id');
		$author_email	= ( $query->num_rows() == 0 ) ? '': $query->row('email');

		// --------------------------------------------
		//  Field data
		// --------------------------------------------

		$fields	= array(
			'first_name'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'first_name',
				'field_label'	=> 'First Name',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'any'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s first name.'
			),
			'last_name'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'last_name',
				'field_label'	=> 'Last Name',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'any'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s last name.'
			),
			'email'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'email',
				'field_label'	=> 'Email',
				'field_type'	=> 'text',
				'settings'		=> array(
					'field_length'			=> 150,
					'field_content_type'	=> 'email'
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'A basic email field for collecting stuff like an email address.'
			),
			'user_message'	=> array(
				//'field_id'		=> '',
				'site_id'		=> $site_id,
				'field_name'	=> 'user_message',
				'field_label'	=> 'Message',
				'field_type'	=> 'textarea',
				'settings'		=> array(
					'field_ta_rows'			=> 6
				),
				'author_id'		=> $author_id,
				'entry_date'	=> ee()->localize->now,
				'required'		=> 'n',
				'submissions_page'	=> 'y',
				'moderation_page'	=> 'y',
				'composer_use'		=> 'y',
				'field_description'	=> 'This field contains the user\'s message.'
			)
		);

		// --------------------------------------------
		//  Loop and create fields
		// --------------------------------------------

		ee()->load->model('freeform_field_model');

		foreach ( $fields as $key => $data )
		{
			$data['settings']	= json_encode($data['settings']);

			$field_ids[$key] = ee()->freeform_field_model->insert($data);
		}

		// --------------------------------------------
		//  Form data
		// --------------------------------------------

		$forms	= array(
			'contact'	=>  array(
				'site_id'			=> $site_id,
				'form_name'			=> 'contact',
				'form_label'		=> 'Contact',
				'default_status'	=> 'pending',
				'notify_admin'		=> 'y',
				'admin_notification_id'		=> $author_id,
				'admin_notification_email'	=> $author_email,
				'form_description'	=> 'This is a basic contact form.',
				'field_ids'			=> implode( '|', $field_ids ),
				'author_id'			=> $author_id,
				'entry_date'		=> ee()->localize->now
			)
		);

		// --------------------------------------------
		//  Create form
		// --------------------------------------------

		ee()->load->model('freeform_form_model');
		ee()->load->library('freeform_forms');

		$prefix 	= ee()->freeform_form_model->form_field_prefix;
		$form_id 	= ee()->freeform_forms->create_form($forms['contact']);

		// --------------------------------------------
		//  Sample entry
		// --------------------------------------------

		$entries	= array(
			'hi'	=> array(
				'site_id'		=> $site_id,
				'author_id'		=> 0,
				'complete'		=> 'y',
				'ip_address'	=> '127.0.0.1',
				'entry_date'	=> ee()->localize->now,
				'status'		=> 'pending',
				$prefix . $field_ids['first_name']		=> 'Jake',
				$prefix . $field_ids['last_name']		=> 'Solspace',
				$prefix . $field_ids['email']			=> 'support@solspace.com',
				$prefix . $field_ids['user_message']	=> 'Welcome to Freeform. We hope that you will enjoy Solspace software.'
			)
		);

		ee()->db->insert_batch(
			ee()->freeform_form_model->table_name($form_id),
			$entries
		);

	}
	//	End install sample fields and form


	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */

	public function uninstall()
	{
		// Cannot uninstall what does not exist, right?
		if ($this->database_version() === FALSE)
		{
			return FALSE;
		}

		// -------------------------------------
		//  uninstall routine for fieldtypes
		// -------------------------------------

		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_fieldtype_model');

		$installed_fieldtypes = ee()->freeform_fieldtype_model->installed_fieldtypes();

		if ( $installed_fieldtypes !== FALSE )
		{
			foreach ($installed_fieldtypes as $name => $data)
			{
				ee()->freeform_fields->uninstall_fieldtype($name);
			}
		}

		// -------------------------------------
		//  delete all extra form tables
		// -------------------------------------

		ee()->load->model('freeform_form_model');

		$query = ee()->freeform_form_model->select('form_id')->get();

		if ($query)
		{
			foreach ($query as $row)
			{
				ee()->freeform_form_model->delete($row['form_id']);
			}
		}

		// -------------------------------------
		//  Delete legacy tables if a migration was done from FF3 to FF4
		// -------------------------------------

		ee()->load->library('freeform_migration');
		ee()->freeform_migration->uninstall();

		// --------------------------------------------
		//  Default Module Uninstall
		// --------------------------------------------

		if ($this->default_module_uninstall() == FALSE)
		{
			return FALSE;
		}

		return TRUE;
	}
	// END uninstall()


	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */

	public function update()
	{
		/*
		if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
		{
			return FALSE;
		}
		*/

		if ($this->version_compare(
				$this->database_version(TRUE),
				'==',
				constant(strtoupper($this->lower_name).'_VERSION')
			)
			AND ee()->input->get_post('update_pro') !== 'true'
		)
		{
			return TRUE;
		}

		$pro_update = (
			$this->data->global_preference('ffp') !== FALSE AND
			($this->check_yes($this->data->global_preference('ffp')) !== FREEFORM_PRO)
		);

		// --------------------------------------------
		//  Default Module Update
		// --------------------------------------------

		$this->default_module_update();

		$this->actions();

		ee()->load->library('freeform_fields');
		ee()->load->library('freeform_migration');

		// --------------------------------------------
		// rename legacy (<=3.x) tables before we install new sql
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0.d1'))
		{
			ee()->freeform_migration->upgrade_rename_tables();
		}

		// --------------------------------------------
		//  Default Module Install
		// --------------------------------------------

		//all db tables should have create if not exists, so this is safe
		$this->install_module_sql();

		ee()->load->model('freeform_preference_model');


		// --------------------------------------------
		// update serialzed data to json
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0.d5'))
		{
			ee()->db->truncate('freeform_multipage_hashes');

			ee()->load->model(array(
				'freeform_field_model',
				'freeform_fieldtype_model',
				'freeform_param_model',
				'freeform_preference_model'
			));

			ee()->freeform_param_model->clear_table();

			// -------------------------------------
			//	update field global settings (none yet)
			// -------------------------------------

			ee()->freeform_fieldtype_model->update(
				array('settings' => json_encode(array()))
			);

			$fields =	ee()->freeform_field_model
							->key('field_id', 'settings')
							->get();

			// -------------------------------------
			//	update field settings
			// -------------------------------------

			if ($fields !== FALSE)
			{
				foreach ($fields as $field_id => $settings)
				{
					$un_settings = @unserialize(@base64_decode($settings));

					ee()->freeform_field_model->update(
						array(
							'settings'	=> (is_array($un_settings) ?
											json_encode($un_settings) :
											json_encode(array()))
						),
						array('field_id' => $field_id)
					);
				}
			}

			// -------------------------------------
			//	update prefs settings
			// -------------------------------------

			$prefs =	ee()->freeform_preference_model
							->where('preference_name', 'form_statuses')
							->key('preference_id', 'preference_value')
							->get();

			if ($prefs !== FALSE)
			{
				foreach ($prefs as $preference_id => $preference_value)
				{
					$preference_value = @unserialize(@base64_decode($preference_value));

					ee()->freeform_preference_model->update(
						array(
							'preference_value'	=> (is_array($preference_value) ?
											json_encode($preference_value) :
											json_encode(array()))
						),
						array('preference_id' => $preference_id)
					);
				}
			}
		}

		// -------------------------------------
		//  insert / update default fields
		// -------------------------------------

		//if we are coming from 3.x we need to install some defaults
		if ($this->version_compare($this->database_version(), '<', '4.0.0.d1'))
		{
			ee()->freeform_fields->install_default_freeform_fields();

			ee()->freeform_preference_model->insert(array(
				'preference_name' 	=> 'ffp',
				'preference_value'	=> FREEFORM_PRO ? 'y' : 'n',
				'site_id' 			=> '0'
			));
		}
		else
		{
			ee()->freeform_fields->update_default_freeform_fields($pro_update);
		}

		// --------------------------------------------
		//  Rename legacy tables
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0.d1'))
		{
			ee()->freeform_migration->upgrade_migrate_preferences();
			ee()->freeform_migration->upgrade_notification_templates();
		}

		// --------------------------------------------
		//  add missing column to composer template
		// --------------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0.b2') AND
			! $this->column_exists('param_data', 'exp_freeform_composer_templates'))
		{
			ee()->load->dbforge();
			ee()->dbforge->add_column(
				'freeform_composer_templates',
				array(
					'param_data' => array('type' => 'TEXT')
				)
			);
		}

		// -------------------------------------
		//	nonefield_submit_button => nonfield_submit
		// -------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.0.b3'))
		{

			ee()->db->query(
				"UPDATE exp_freeform_composer_layouts
				 SET	composer_data = REPLACE(composer_data,'nonfield_submit_button','nonfield_submit')"
			);

			ee()->db->query(
				"UPDATE exp_freeform_composer_templates
				 SET	template_data = REPLACE(template_data,'nonfield_submit_button','nonfield_submit')"
			);
		}

		// -------------------------------------
		//	edit_date => entry_date *sigh*
		// -------------------------------------

		if ($this->version_compare($this->database_version(), '<', '4.0.4') AND
			$this->column_exists('edit_date', 'exp_freeform_user_email'))
		{
			ee()->load->dbforge();
			ee()->dbforge->modify_column(
				'freeform_user_email',
				array(
					'edit_date' => array(
						'name'	=> 'entry_date',
						//we aren't changing this but stupid mysql
						//requires you send a type when changing
						//a column name
						'type'	=> 'INT'
					)
				)
			);
		}

		// -------------------------------------
		//	pro version vs free version?
		// -------------------------------------

		//up to pro
		if (FREEFORM_PRO)
		{
			$this->install_ffp_channel_field();
		}
		//down to free
		else
		{
			ee()->load->model('freeform_field_model');

			ee()->freeform_field_model
				->where_not_in('field_type', $this->data->defaults['default_fields'])
				->update(array('field_type' => 'text'));

			$this->uninstall_ffp_channel_field();
		}

		if (ee()->freeform_preference_model->count(array(
				'site_id'			=> 0,
				'preference_name'	=> 'ffp'
			)) > 0
		)
		{
			ee()->freeform_preference_model->update(
				array(
					'preference_value'	=> FREEFORM_PRO ? 'y' : 'n',
				),
				array(
					'site_id'			=> '0',
					'preference_name'	=> 'ffp'
				)
			);
		}
		else
		{
			ee()->freeform_preference_model->insert(array(
				'preference_name' 	=> 'ffp',
				'preference_value'	=> FREEFORM_PRO ? 'y' : 'n',
				'site_id' 			=> '0'
			));
		}

		// --------------------------------------------
		//  Version Number Update - LAST!
		// --------------------------------------------

		$data = array(
			'module_version'		=> FREEFORM_VERSION,
			'has_publish_fields'	=> 'n'
		);

		ee()->db->update(
			'modules',
			$data,
			array(
				'module_name'		=> $this->class_name
			)
		);

		return TRUE;
	}
	// END update()


	// --------------------------------------------------------------------

	/**
	 * Uninstall FFP Channel Fieldtype
	 *
	 * @access	public
	 * @return	null
	 */

	public function uninstall_ffp_channel_field ()
	{
		ee()->load->library('addons/addons_installer');
		ee()->load->model('addons_model');

		if (ee()->addons_model->fieldtype_installed($this->lower_name))
		{
			ee()->addons_installer->uninstall($this->lower_name, 'fieldtype', FALSE);
		}
	}
	//END uninstall_ffp_channel_field


	// --------------------------------------------------------------------

	/**
	 * Install FFP Channel Fieldtype
	 *
	 * @access	public
	 * @return	null
	 */

	public function install_ffp_channel_field ()
	{
		ee()->load->library('addons/addons_installer');
		ee()->load->model('addons_model');

		if ( ! ee()->addons_model->fieldtype_installed($this->lower_name))
		{
			ee()->addons_installer->install($this->lower_name, 'fieldtype', FALSE);
		}
	}
	//END install_ffp_channel_field
}
// END Class Freeform_updater_base
