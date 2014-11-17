<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Migration Library
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/libraries/Freeform_migration.php
 */

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once realpath(dirname(__FILE__) . '/../addon_builder/addon_builder.php');
}

class Freeform_migration extends Addon_builder_freeform
{
	public $cache_path;
	public $table_suffix			= '_legacy';
	private $batch_limit			= 10;
	private $migrate_attachments	= FALSE;
	private $errors					= array();
	private $upload_pref_id_map		= array();
	private	$tables					= array(
		'exp_freeform_attachments',
		'exp_freeform_entries',
		'exp_freeform_fields',
		'exp_freeform_preferences',
		'exp_freeform_templates',
		'exp_freeform_user_email'
	);

	private	$new_has_site_id		= array(
		'exp_freeform_fields',
		'exp_freeform_preferences',
		'exp_freeform_user_email'
	);

	// --------------------------------------------------------------------

	/**
	 * Upgrade: migrate notification templates
	 *
	 * @access public
	 * @return boolean
	 */

	public function upgrade_notification_templates()
	{
		// -------------------------------------
		//	Map table
		// -------------------------------------

		$trans	= array(
			'wordwrap'			=> 'wordwrap',
			'html'				=> 'allow_html',
			'template_name'		=> 'notification_name',
			'template_label'	=> 'notification_label',
			'data_from_name'	=> 'from_name',
			'data_from_email'	=> 'from_email',
			'data_title'		=> 'email_subject',
			'template_data'		=> 'template_data'
		);

		// -------------------------------------
		//	Capture from legacy
		// -------------------------------------

		$query	= ee()->db->get('exp_freeform_templates' . $this->table_suffix);

		// -------------------------------------
		//	Loop for each template
		// -------------------------------------

		foreach ($query->result_array() as $row)
		{
			$insert	= array(
				'site_id'	=> ee()->config->item('site_id')
			);

			// -------------------------------------
			//	Remap data
			// -------------------------------------

			foreach ($trans as $key => $val)
			{
				if ( ! empty($row[$key]))
				{
					$insert[$val]	= $row[$key];
				}
			}

			// -------------------------------------
			//	Reply to
			// -------------------------------------

			if ( ! empty($insert['from_email']))
			{
				$insert['reply_to_email']	= $insert['from_email'];
			}

			// -------------------------------------
			//	Enable attachments?
			// -------------------------------------

			if ( ! empty($insert['template_data']) AND
				strpos($insert['template_data'], 'attach') !== FALSE)
			{
				$insert['include_attachments']	= 'y';
			}

			// -------------------------------------
			//	Convert
			// -------------------------------------

			$replace	= array(
				'{all_custom_fields}'	=> '{all_form_fields_string}'
			);

			$insert	= str_replace(array_keys($replace), $replace, $insert);

			// -------------------------------------
			//	Insert to new table
			// -------------------------------------

			ee()->db->insert(
				'freeform_notification_templates',
				$insert
			);
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return TRUE;
	}
	//	End upgrade: migrate notification templates


	// --------------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @access public
	 * @return boolean
	 */

	public function uninstall()
	{
		// -------------------------------------
		//	Drop
		// -------------------------------------

		ee()->load->dbforge();

		foreach ($this->tables as $table)
		{
			//oh, dbforge, you card
			$t_name = preg_replace(
				'/^exp_/ms',
				'',
				$table . $this->table_suffix
			);

			ee()->dbforge->drop_table($t_name);
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return TRUE;
	}
	//	End uninstall


	// --------------------------------------------------------------------

	/**
	 * Upgrade: migrate preferences
	 *
	 * @access public
	 * @return boolean
	 */

	public function upgrade_migrate_preferences()
	{
		// -------------------------------------
		//	make sure prefs legacy is there
		// -------------------------------------

		$query = ee()->db->query(
			"SHOW TABLES
			 LIKE 'exp_freeform_preferences" .
			 	ee()->db->escape_str($this->table_suffix) . "'"
		);

		if ($query->num_rows() == 0)
		{
			return TRUE;
		}

		// -------------------------------------
		//	Prefs list
		// -------------------------------------

		$prefs	= array(
			'max_user_recipients'	=> 10,
			'spam_count'			=> 30,
			'spam_interval'			=> 60
		);

		// -------------------------------------
		//	Capture from legacy
		// -------------------------------------

		$query	= ee()->db->get('exp_freeform_preferences' . $this->table_suffix);

		// -------------------------------------
		//	Push into new prefs table
		// -------------------------------------

		foreach ($query->result_array() as $row)
		{
			if (isset($prefs[$row['preference_name']]) === TRUE AND
				$prefs[$row['preference_name']] != $row['preference_value'])
			{

				ee()->db->insert(
					'freeform_preferences',
					array(
						'preference_name'	=> $row['preference_name'],
						'preference_value'	=> $row['preference_value'],
						'site_id'			=> ee()->config->item('site_id')
					)
				);
			}
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return TRUE;
	}
	//	End upgrade: migrate preferences


	// --------------------------------------------------------------------

	/**
	 * Upgrade: rename tables
	 *
	 * @access public
	 * @return boolean
	 */

	public function upgrade_rename_tables()
	{
		ee()->load->dbforge();

		// -------------------------------------
		//	Loop and rename
		// -------------------------------------

		foreach ($this->tables as $name)
		{
			// -------------------------------------
			//	dbforge doesn't check for prefixes.
			//	FUN! :D
			// -------------------------------------

			$np_name = preg_replace(
				'/^exp_/ms',
				'',
				$name
			);

			$np_legacy = preg_replace(
				'/^exp_/ms',
				'',
				$name.$this->table_suffix
			);

			//all legit? shoo shoo
			if (ee()->db->table_exists($name) === FALSE AND
				ee()->db->table_exists($name.$this->table_suffix) === TRUE)
			{
				continue;
			}
			//do both the old and new tables exist?
			//whoops...
			else if (
				ee()->db->table_exists($name) === TRUE AND
				ee()->db->table_exists($name.$this->table_suffix) === TRUE
			)
			{
				//irrelevant table?
				if ( ! in_array($name, $this->new_has_site_id))
				{
					continue;
				}

				//is it already the new table we care about?
				if ($this->column_exists('site_id', $name))
				{
					continue;
				}
				//so, we already have a legacy table
				//and this is NOT the new schema, we need to drop
				//so update can add the new table with proper schema
				else
				{
					$table_count	= ee()->db->count_all($name);
					$l_table_count	= ee()->db->count_all($name.$this->table_suffix);

					//if the legacy table is empty and the old one isn't
					//then we need to keep the one with entries
					if ($table_count > 0 AND $l_table_count == 0)
					{
						ee()->dbforge->drop_table($np_legacy);
						//no continue here because we now need the rename_table
						//to run
					}
					//drop bad table and move on
					else
					{
						ee()->dbforge->drop_table($np_name);
						continue;
					}

				}
			}

			ee()->db->query('RENAME TABLE ' . $name . ' TO '. $name.$this->table_suffix);
			//dbforge is stupid and sometimes doesn't account
			//for a screwed up db prefix GRRRRR
			//ee()->dbforge->rename_table($np_name, $np_legacy);
		}

		// -------------------------------------
		//	Add tracking column
		// -------------------------------------

		if ($this->column_exists(
				'new_entry_id',
				'exp_freeform_entries' . $this->table_suffix
			) === FALSE)
		{
			ee()->dbforge->add_column(
				'freeform_entries' . $this->table_suffix,
				array('new_entry_id' => array(
					'type'			=> 'INT',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'null'			=> FALSE,
					'default'		=> 0
				))
			);
		}

		// -------------------------------------
		//	Rename any empty form_name column to the default of freeform_form
		// -------------------------------------

		ee()->db->update(
			'exp_freeform_entries' . $this->table_suffix,
			array('form_name'	=> 'freeform_form'),
			array('form_name'	=> '')
		);

		// -------------------------------------
		//	Add form label column
		// -------------------------------------

		if ($this->column_exists(
				'form_label',
				'exp_freeform_entries' . $this->table_suffix
			) === FALSE)
		{

			ee()->dbforge->add_column(
				'freeform_entries' . $this->table_suffix,
				array('form_label' => array(
					'type'			=> 'TEXT'
				)),
				'form_name'
			);
		}

		// -------------------------------------
		//	Get form names
		// -------------------------------------

		$query = ee()->db
					->select('form_name')
					->group_by('form_name')
					->get("exp_freeform_entries" . $this->table_suffix);

		ee()->load->helper('url');

		foreach ($query->result_array() as $row)
		{
			ee()->db->update(
				"exp_freeform_entries" . $this->table_suffix,
				array(
					'form_name' => url_title(
						$row['form_name'],
						ee()->config->item('word_separator'),
						TRUE
					),
					'form_label' => $row['form_name']
				),
				array(
					'form_name' => $row['form_name']
				)
			);
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return TRUE;
	}
	//	End upgrade: rename tables


	// --------------------------------------------------------------------

	/**
	 * Assign fields to form
	 *
	 * @access public
	 * @return boolean
	 */

	public function assign_fields_to_form ($form_id = '', $field_ids = array())
	{
		// -------------------------------------
		//	Validate
		// -------------------------------------

		if (empty($form_id) OR empty($field_ids)) return FALSE;

		// -------------------------------------
		//	Go to Greg
		// -------------------------------------

		ee()->load->library('freeform_forms');

		$data['field_ids']		= implode('|', $field_ids);
		$data['field_order']	= $data['field_ids'];

		ee()->freeform_forms->update_form($form_id, $data);

		// -------------------------------------
		//	Return
		// -------------------------------------

		return TRUE;
	}
	//	End assign fields to form


	// --------------------------------------------------------------------

	/**
	 * Create field
	 *
	 * @access public
	 * @return integer
	 */

	public function create_field ($form_id = '', $field_name = '', $field_attr = array())
	{
		// -------------------------------------
		//	Validate
		// -------------------------------------

		if (empty($form_id) OR
			empty($field_name))
		{
			$this->errors['missing_data_for_field_creation'] = lang(
				'missing_data_for_field_creation'
			);

			return FALSE;
		}

		// -------------------------------------
		//	Workaround name?
		// -------------------------------------

		if (in_array($field_name, array('status')))
		{
			$field_name	= $field_name . $this->table_suffix;
		}

		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if ( ! empty($this->cache['create_field'][$form_id.$field_name]))
		{
			return $this->cache['create_field'][$form_id.$field_name];
		}

		// -------------------------------------
		//	Valid name?
		// -------------------------------------

		if (in_array($field_name, $this->data->prohibited_names))
		{
			$this->errors['field_name'] = str_replace(
				'%name%',
				$field_name,
				lang('freeform_reserved_field_name')
			);

			return FALSE;
		}

		// -------------------------------------
		//	Exists?
		// -------------------------------------

		ee()->load->model('freeform_field_model');
		ee()->freeform_field_model->clear_cache();

		$row =	ee()->freeform_field_model
					->where('field_name', $field_name)
					->get_row();


		//field exists? Move on.
		if ($row AND isset($row['field_id']))
		{
			$this->cache['create_field'][$form_id.$field_name] = $row['field_id'];
			return $this->cache['create_field'][$form_id.$field_name];
		}

		// -------------------------------------
		//	field label
		// -------------------------------------

		$field_label = (empty($field_attr['field_label'])) ?
							$field_name :
							$field_attr['field_label'];

		// -------------------------------------
		//	field type
		// -------------------------------------

		$field_type = ($field_attr['field_type'] != 'textarea') ? 'text': 'textarea';

		// -------------------------------------
		//	default text size
		// -------------------------------------

		//grab old post just in case
		$old_post = $_POST;

		if ($field_type == 'text' AND
			isset($field_attr['field_length']) AND
			$this->is_positive_intlike($field_attr['field_length']))
		{
			$_POST = array(
				'field_length' => $field_attr['field_length']
			);
		}

		// -------------------------------------
		//	load and save field
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$available_fieldtypes = ee()->freeform_fields->get_available_fieldtypes();

		$field_instance =& ee()->freeform_fields->get_fieldtype_instance($field_type);

		// -------------------------------------
		//	field type
		// -------------------------------------

		if ( ! $field_type OR ! array_key_exists($field_type, $available_fieldtypes))
		{
			$this->errors['field_type'] = lang('invalid_fieldtype');

			return FALSE;
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data 		= array(
			'field_name'		=> $field_name,
			'field_label'		=> $field_label,
			'field_type'		=> $field_type,
			'edit_date'			=> '0', //overridden if update
			'field_description'	=> '',
			'submissions_page'	=> 'y',
			'moderation_page'	=> 'y',
			'composer_use'		=> 'y',
			'settings'			=> json_encode($field_instance->save_settings())
		);

		ee()->load->model('freeform_field_model');

		$field_id = ee()->freeform_field_model->insert(
			array_merge(
				$data,
				array(
					'author_id'		=> ee()->session->userdata('member_id'),
					'entry_date'	=> ee()->localize->now,
					'site_id' 		=> ee()->config->item('site_id')
				)
			)
		);

		$field_instance->field_id = $field_id;

		//reset old post if changed
		$_POST = $old_post;

		$field_instance->post_save_settings();

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $this->cache['create_field'][$form_id.$field_name] = $field_id;
	}
	//	End create field


	// --------------------------------------------------------------------

	/**
	 * Create upload field
	 *
	 * @access public
	 * @return integer
	 */

	public function create_upload_field ($form_id = '', $field_name = '', $field_attr = array())
	{
		// -------------------------------------
		//	Validate
		// -------------------------------------

		if (empty($form_id) OR empty($field_name))
		{
			$this->errors['missing_data_for_field_creation'] = lang(
				'missing_data_for_field_creation'
			);

			return FALSE;
		}

		// -------------------------------------
		//	field label
		// -------------------------------------

		$field_label = (empty($field_attr['field_label'])) ?
						$field_name :
						$field_attr['field_label'];

		// -------------------------------------
		//	field name
		// -------------------------------------

		ee()->load->helper('url');

		$field_name	= url_title(
			$field_name,
			ee()->config->item('word_separator'),
			TRUE
		);

		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if ( ! empty($this->cache['create_upload_field'][$form_id.$field_name]))
		{
			return $this->cache['create_upload_field'][$form_id.$field_name];
		}

		// -------------------------------------
		//	Valid name?
		// -------------------------------------

		if (in_array($field_name, $this->data->prohibited_names))
		{
			$this->errors['field_name'] = str_replace(
				'%name%',
				$field_name,
				lang('freeform_reserved_field_name')
			);

			return FALSE;
		}

		// -------------------------------------
		//	Exists?
		// -------------------------------------

		ee()->load->model('freeform_field_model');
		ee()->freeform_field_model->clear_cache();

		$row = ee()->freeform_field_model->get_row(array(
			'field_name' => $field_name
		));


		//do we already have an upload field?
		if ($row AND isset($row['field_id']))
		{
			$this->cache['create_field'][$form_id.$field_name] = $row['field_id'];

			return $row['field_id'];
		}

		// -------------------------------------
		//	field instance
		// -------------------------------------

		$field_type = 'file_upload';

		ee()->load->library('freeform_fields');

		$available_fieldtypes = ee()->freeform_fields->get_available_fieldtypes();
		$field_instance =& ee()->freeform_fields->get_fieldtype_instance($field_type);

		// -------------------------------------
		//	field type
		// -------------------------------------

		if ( ! $field_type OR ! array_key_exists($field_type, $available_fieldtypes))
		{
			$this->errors['field_type'] = lang('invalid_fieldtype');
			return FALSE;
		}

		// -------------------------------------
		//	default settings
		// -------------------------------------

		foreach ($field_instance->default_settings as $key => $val)
		{
			$settings[$key]	= $val;

			if ( ! empty($field_attr[$key]))
			{
				$settings[$key]	= $field_attr[$key];
			}
		}

		// -------------------------------------
		//	allowed file types forced?
		// -------------------------------------

		if ( ! empty($field_attr['allowed_types']))
		{
			if ($field_attr['allowed_types'] == 'all')
			{
				$settings['allowed_file_types']	= '*';
			}
			elseif ($field_attr['allowed_types'] != 'img')
			{
				$settings['allowed_file_types']	= '';
			}
		}

		// -------------------------------------
		//	allowed upload count
		// -------------------------------------

		if ( ! empty($field_attr['allowed_upload_count']))
		{
			$settings['allowed_upload_count']	= $field_attr['allowed_upload_count'];

			// You know. Maybe a spammer went to town on this person's
			// site and dumped a bunch of attachments in.
			// I think we protected against that in FF3, but whatevs
			if ($settings['allowed_upload_count'] > $field_instance->max_files)
			{
				$settings['allowed_upload_count']	= 3;
			}
		}

		// -------------------------------------
		//	file upload location
		// -------------------------------------

		if ( ! empty($field_attr['pref_id']))
		{
			$settings['file_upload_location']	= $field_attr['pref_id'];
		}

		// -------------------------------------
		//	validate settings
		// -------------------------------------

		foreach ($settings as $key => $value)
		{
			$_POST[$key] = $value;
		}

		$errors = $field_instance->validate_settings();

		if ($errors !== TRUE)
		{
			$this->errors	= array_merge($this->errors, $errors);
			return FALSE;
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data 		= array(
			'field_name'		=> $field_name,
			'field_label'		=> $field_label,
			'field_type'		=> $field_type,
			'edit_date'			=> '0', //overridden if update
			'field_description'	=> '',
			'submissions_page'	=> 'y',
			'moderation_page'	=> 'y',
			'composer_use'		=> 'y',
			'settings'			=> json_encode($field_instance->save_settings())
		);

		ee()->load->model('freeform_field_model');

		$field_id = ee()->freeform_field_model->insert(
			array_merge(
				$data,
				array(
					'author_id'		=> ee()->session->userdata('member_id'),
					'entry_date'	=> ee()->localize->now,
					'site_id' 		=> ee()->config->item('site_id')
				)
			)
		);

		$field_instance->field_id = $field_id;

		$field_instance->post_save_settings();

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $this->cache['create_upload_field'][$form_id.$field_name] = $field_id;
	}
	//	End create upload field


	// --------------------------------------------------------------------

	/**
	 * Create Form
	 *
	 * @access	public
	 * @param	string $form_name	name of form to create
	 * @return	mixed				boolean false if errors, array if not
	 */

	public function create_form ($form_name = '')
	{
		// -------------------------------------
		//	Migrated / Unmigrated
		// -------------------------------------

		if (empty($form_name))
		{
			$this->errors['empty_form_name'] = lang('empty_form_name');
			return FALSE;
		}

		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if ( ! empty($this->cache['create_form'][$form_name]))
		{
			return $this->cache['forms'][$form_name];
		}
		// -------------------------------------
		//	Label
		// -------------------------------------

		$out['form_label']	= $form_name;

		$query = ee()->db
						->select('form_label')
						->where('form_name', $form_name)
						->get("exp_freeform_entries" . $this->table_suffix);


		if ($query->num_rows() > 0)
		{
			$out['form_label']	= $query->row('form_label');
		}

		// -------------------------------------
		//	Clean name
		// -------------------------------------

		ee()->load->helper('url');

		$form_name = url_title(
			$form_name,
			ee()->config->item('word_separator'),
			TRUE
		);

		// -------------------------------------
		//	Prohibited name?
		// -------------------------------------

		if (in_array($form_name, $this->data->prohibited_names))
		{
			$this->errors['form_name'] = str_replace(
				'%name%',
				$form_name,
				lang('reserved_form_name')
			);

			return FALSE;
		}

		// -------------------------------------
		//	Collision?
		// -------------------------------------

		$query = ee()->db
						->select('form_id, form_name, form_label')
						->where('site_id', ee()->config->item('site_id'))
						->where('form_name', $form_name)
						->get('freeform_forms');

		if ($query->num_rows() > 0)
		{
			$out['form_name']	= $query->row('form_name');
			$out['form_label']	= $query->row('form_label');
			$out['form_id']		= $query->row('form_id');

			return $this->cache['create_form'][$form_name] = $out;
		}

		// -------------------------------------
		//	Load
		// -------------------------------------

		ee()->load->model('freeform_form_model');
		ee()->load->library('freeform_forms');

		// -------------------------------------
		//	former parameter only fields
		// -------------------------------------
		//	The legacy FF did not save these values
		//	in the DB.
		// -------------------------------------
		//	These values may be available in templates,
		//	but since it could be different from
		//	template to template and the same
		//	collections used for different forms,
		//	it's rather impossible to guess
		//	correctly.
		// -------------------------------------

		// -------------------------------------
		//	Admin notification email
		// -------------------------------------

		// -------------------------------------
		//	User email field
		// -------------------------------------

		// -------------------------------------
		//	Notify admin
		// -------------------------------------

		$notify_admin	= 'n';

		// -------------------------------------
		//	Notify user
		// -------------------------------------

		$notify_user	= 'n';

		// -------------------------------------
		//	Insert data
		// -------------------------------------

		$data = array(
			'form_name'					=> $form_name,
			'form_label'				=> $out['form_label'],
			'default_status'			=> 'open',
			'author_id' 				=> ee()->session->userdata('member_id'),
			'notify_admin'				=> $notify_admin,
			'notify_user'				=> $notify_user
		);

		$form_id = ee()->freeform_forms->create_form($data);

		// -------------------------------------
		//	Return
		// -------------------------------------

		$out['form_id']		= $form_id;
		$out['form_name']	= $form_name;

		return $this->cache['create_form'][$form_name] = $out;
	}
	//END create form


	// --------------------------------------------------------------------

	/**
	 * Get attachment profiles
	 *
	 * @access	public
	 * @param	string	$collection		limit to collection
	 * @return	mixed					array of results of boolean false
	 */

	public function get_attachment_profiles($collection = '')
	{
		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if (isset($this->cache['get_attachment_profiles'][$collection]))
		{
			return $this->cache['get_attachment_profiles'][$collection];
		}

		// -------------------------------------
		//	Query
		// -------------------------------------

		$query = ee()->db
					->select('a.pref_id, p.name, p.allowed_types')
					->from("exp_freeform_attachments" . $this->table_suffix . ' a')
					->join('exp_upload_prefs p', 'p.id = a.pref_id', 'left')
					->where('p.site_id', ee()->config->item('site_id'))
					->group_by('p.id')
					->get();

		if ($query->num_rows() == 0)
		{
			$this->cache['get_attachment_profiles'][$collection] = FALSE;
			return FALSE;
		}

		// -------------------------------------
		//	Get allowed_upload_count
		// -------------------------------------

		$cquery = ee()->db
						->select('entry_id, pref_id')
						->get("exp_freeform_attachments" . $this->table_suffix);

		//1!
		$temp = array();

		//2! 2 arrays, ah ah ah ah! }:[
		$counts = array();

		// -------------------------------------
		//	build a count for each pref->entry_id
		//	so we can get an upload count for
		//	each entry
		// -------------------------------------

		foreach ($cquery->result_array() as $row)
		{
			$temp[$row['pref_id']][$row['entry_id']][]	= 1;
		}

		foreach ($temp as $pref_id => $entries)
		{
			//dump entry_ids and sort on highest count
			//so we can assume a highest count on an entry was
			//the previous limit
			rsort($entries);

			//if not set or we don't already have a
			//higher count, set pref ID
			if ( ! isset($counts[$pref_id]) OR
				(count($entries[0]) > $counts[$pref_id]))
			{
				$counts[$pref_id] = count($entries[0]);
			}
		}

		//foreach pref id,
		foreach ($query->result_array() as $row)
		{
			$out[$row['pref_id']]	= $row;
			$out[$row['pref_id']]['allowed_upload_count']	= 1;

			if ( ! empty($counts[$row['pref_id']]))
			{
				$out[$row['pref_id']]['allowed_upload_count']	= $counts[$row['pref_id']];
			}
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		$this->cache['get_attachment_profiles'][$collection] = $out;

		return $this->cache['get_attachment_profiles'][$collection];
	}
	//END get_attachment_profiles


	// --------------------------------------------------------------------

	/**
	 * Get collection counts
	 *
	 * @access public
	 * @return array
	 */

	public function get_collection_counts($collections = array())
	{
		$counts	= array();

		// -------------------------------------
		//	Bother?
		// -------------------------------------

		if ($this->legacy() === FALSE)
		{
			return $counts;
		}
		//Oh, bother.

		// -------------------------------------
		//	Migrated
		// -------------------------------------

		$table	= 'exp_freeform_entries' . $this->table_suffix;

		if ( ! empty($collections))
		{
			ee()->db->where_in('form_name', $collections);
		}

		$query = ee()->db
					->select('COUNT(*) AS count, form_name')
					->where('new_entry_id', 0)
					->group_by('form_name')
					->get($table);

		foreach ($query->result_array() as $row)
		{
			$counts[$row['form_name']]['migrated']		= 0;
			$counts[$row['form_name']]['unmigrated']	= $row['count'];
		}

		// -------------------------------------
		//	unmigrated
		// -------------------------------------

		if ( ! empty($collections))
		{
			ee()->db->where_in('form_name', $collections);
		}

		$query = ee()->db
					->select('COUNT(*) AS count, form_name')
					->where('new_entry_id !=', 0)
					->group_by('form_name')
					->get($table);

		foreach ($query->result_array() as $row)
		{
			$counts[$row['form_name']]['unmigrated']	= (
				empty($counts[$row['form_name']]['unmigrated'])
			) ? 0: $counts[$row['form_name']]['unmigrated'];

			$counts[$row['form_name']]['migrated']		= $row['count'];
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $counts;
	}
	//	End get collection counts


	// --------------------------------------------------------------------

	/**
	 * Get field type installed
	 *
	 * @access	public
	 * @param	string $field_type	fieldtype to check as installed
	 * @return	boolean				fieldtype installed
	 */
	public function get_field_type_installed($field_type = '')
	{
		if (empty($field_type))
		{
			return FALSE;
		}

		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if ( ! empty($this->cache['get_field_type_installed'][$field_type]))
		{
			return $this->cache['get_field_type_installed'][$field_type];
		}

		// -------------------------------------
		//	Query
		// -------------------------------------

		ee()->load->model('freeform_fieldtype_model');

		ee()->freeform_fieldtype_model->clear_cache();

		$field_types = ee()->freeform_fieldtype_model->installed_fieldtypes();

		$this->cache['get_field_type_installed'][$field_type] = ! empty($field_types[$field_type]);

		return $this->cache['get_field_type_installed'][$field_type];
	}
	//END get_field_type_installed


	// --------------------------------------------------------------------

	/**
	 * Get fields
	 *
	 * @access public
	 * @return array	array of legacy fields
	 */

	public function get_fields()
	{
		// -------------------------------------
		//	Cached?
		// -------------------------------------

		if ( ! empty($this->cache['get_fields']))
		{
			return $this->cache['get_fields'];
		}

		// -------------------------------------
		//	DB fetch
		// -------------------------------------

		$query = ee()->db
						->select('field_id, field_type, field_order, field_length')
						->select('name AS field_name, label AS field_label')
						->order_by('field_order')
						->get("exp_freeform_fields" . $this->table_suffix);

		$this->cache['get_fields'] = $this->prepare_keyed_result(
			$query,
			'field_name'
		);

		return $this->cache['get_fields'];
	}
	//END get_fields


	// --------------------------------------------------------------------

	/**
	 * Get fields for collection
	 *
	 * @access	public
	 * @param	string $collection		limit results to collection
	 * @param	string $show_empties	include empty fields
	 * @return	mixed					bool false if no results, or array of fields
	 */

	public function get_fields_for_collection($collection = '', $show_empties = 'n')
	{
		// -------------------------------------
		//	Get fields
		// -------------------------------------

		$fields	= $this->get_fields();

		if (empty($fields))
		{
			return FALSE;
		}

		// -------------------------------------
		//	Show empties?
		// -------------------------------------

		if ($show_empties != 'n')
		{
			return $fields;
		}

		// -------------------------------------
		//	Yes filter to make sure we exclude empty fields now.
		// -------------------------------------

		$ors	= array();

		foreach (array_keys($fields) as $val)
		{
			$ors[]	= "`" .$val . "` != ''";
		}

		$query	= ee()->db->select(implode(',', array_keys($fields)))
					->where('form_name', $collection)
					->where("(" . implode(' OR ', $ors) . ")")
					->get("exp_freeform_entries" . $this->table_suffix);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$out	= array();

		foreach ($query->result_array() as $row)
		{
			foreach ($row as $key => $val)
			{
				if ($val != '')
				{
					$out[$key]	= $key;
				}
			}
		}

		foreach (array_keys($fields) as $val)
		{
			if (in_array($val, $out) === FALSE)
			{
				unset($fields[$val]);
			}
		}

		return (empty($fields) ? FALSE : $fields);
	}

	//	End get fields for collection


	// --------------------------------------------------------------------

	/**
	 * Get migration count
	 *
	 * @access	public
	 * @param	array	$collections	sets of collections to limit count to
	 * @return	int						number of results for unmigrated entries
	 */

	public function get_migration_count($collections = array())
	{
		// -------------------------------------
		//	Unmigrated
		// -------------------------------------

		if ( ! empty($collections))
		{
			ee()->db->where_in('form_name', $collections);
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return ee()->db
					->where('new_entry_id', 0)
					->count_all_results(
						'exp_freeform_entries' . $this->table_suffix
					);
	}
	//END get_migration_count


	// --------------------------------------------------------------------

	/**
	 * Legacy
	 *
	 * @access public
	 * @return boolean
	 */

	public function legacy()
	{
		if (isset($this->cache['legacy']))
		{
			return $this->cache['legacy'];
		}

		$this->cache['legacy'] = FALSE;

		// -------------------------------------
		//	DB check
		// -------------------------------------

		$query = ee()->db->query(
			"SHOW TABLES
			 LIKE 'exp_freeform_entries" . ee()->db->escape_str($this->table_suffix) . "'"
		);

		if ($query->num_rows() > 0)
		{
			$count = ee()->db
						->where('new_entry_id', 0)
						->count_all_results(
							'exp_freeform_entries' . $this->table_suffix
						);

			if ($count > 0)
			{
				$this->cache['legacy'] = TRUE;
			}
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $this->cache['legacy'];
	}
	//	End legacy


	// --------------------------------------------------------------------

	/**
	 * Get errors
	 *
	 * @access	public
	 * @return	array
	 */

	public function get_errors()
	{
		if (empty($this->errors))
		{
			return FALSE;
		}

		return $this->errors;
	}
	// End get errors


	// --------------------------------------------------------------------

	/**
	 * Get legacy entry
	 *
	 * @access	public
	 * @return	array
	 */

	public function get_legacy_entry($form_name)
	{
		// -------------------------------------
		//	SQL
		// -------------------------------------

		$query = ee()->db
						->where('new_entry_id', 0)
						->where('form_name', $form_name)
						->limit(1)
						->get('exp_freeform_entries' . $this->table_suffix);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$entry	= $query->row_array();

		// -------------------------------------
		//	Get attachments
		// -------------------------------------

		$aquery = ee()->db
						->where('entry_id', $query->row('entry_id'))
						->get("exp_freeform_attachments" . $this->table_suffix);

		foreach ($aquery->result_array() as $row)
		{
			$entry['attachments'][]	= $row;
		}

		// -------------------------------------
		//	Get email log
		// -------------------------------------

		$equery = ee()->db
						->select('email_count')
						->where('entry_id', $query->row('entry_id'))
						->get("exp_freeform_user_email" . $this->table_suffix);

		foreach ($equery->result_array() as $row)
		{
			$entry['user_emails'][]	= $row['email_count'];
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $entry;
	}
	// End get legacy entry


	// --------------------------------------------------------------------

	/**
	 * Get legacy entries
	 *
	 * @access	public
	 * @return	array
	 */

	public function get_legacy_entries($form_name)
	{
		// -------------------------------------
		//	SQL
		// -------------------------------------

		$query = ee()->db
						->where('new_entry_id', 0)
						->where('form_name', $form_name)
						->limit($this->batch_limit)
						->get('exp_freeform_entries' . $this->table_suffix);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$entries = $this->prepare_keyed_result($query, 'entry_id');

		// -------------------------------------
		//	Get attachments
		// -------------------------------------

		$aquery = ee()->db
						->where_in('entry_id', array_keys($entries))
						->get("exp_freeform_attachments" . $this->table_suffix);

		foreach ($aquery->result_array() as $row)
		{
			if ( ! empty($row['entry_id']))
			{
				$entries[$row['entry_id']]['attachments'][]	= $row;
			}
		}

		// -------------------------------------
		//	Get email log
		// -------------------------------------

		$equery = ee()->db
						->select('email_count')
						->where_in('entry_id', array_keys($entries))
						->get("exp_freeform_user_email" . $this->table_suffix);

		foreach ($equery->result_array() as $row)
		{
			if ( ! empty($row['entry_id']))
			{
				$entries[$row['entry_id']]['user_emails'][]	= $row['email_count'];
			}
		}

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $entries;
	}
	// End get legacy entries


	// --------------------------------------------------------------------

	/**
	 * Set legacy entry
	 *
	 * @access	public
	 * @return	array
	 */

	public function set_legacy_entry($form_id = '', $entry = array())
	{
		// -------------------------------------
		//	Validate
		// -------------------------------------

		if (empty($form_id) OR empty($entry))
		{
			$this->errors['empty_form_name']	= lang('empty_form_name');
			return FALSE;
		}

		// -------------------------------------
		//	Library
		// -------------------------------------

		ee()->load->library('freeform_forms');
		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_form_model');

		// -------------------------------------
		//	form data
		// -------------------------------------

		$form_data 		= $this->data->get_form_info($form_id);

		// -------------------------------------
		//	Workaround name?
		// -------------------------------------

		foreach (array('status') as $name)
		{
			if (isset($entry[$name]))
			{
				$entry[$name.$this->table_suffix]	= $entry[$name];
				unset($entry[$name]);
			}
		}

		// -------------------------------------
		//	validate
		// -------------------------------------

		$field_input_data	= array();
		$field_list			= array();

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$field_list[$field_data['field_name']] = $field_data['field_label'];

			$field_post	= ( ! empty($entry[$field_data['field_name']])) ? $entry[$field_data['field_name']]: '';

			$field_input_data[$field_data['field_name']] = $field_post;
		}

		$defaults	= array(
			'author_id',
			'ip_address',
			'entry_date',
			'edit_date',
			'status'
		);

		foreach ($defaults as $default)
		{
			if ( ! empty($entry[$default]))
			{
				$field_input_data[$default]	= $entry[$default];
			}
		}

		// -------------------------------------
		//	correct for edit date
		// -------------------------------------

		if ( ! empty($field_input_data['entry_date']) AND ! empty($field_input_data['edit_date']) AND $field_input_data['entry_date'] == $field_input_data['edit_date'])
		{
			$field_input_data['edit_date']	= 0;
		}

		// -------------------------------------
		//	attachments?
		// -------------------------------------

		if ($this->migrate_attachments === TRUE AND ! empty($entry['attachments']))
		{
			//--------------------------------------
			//  Attachments? Add to entries table.
			//--------------------------------------

			$attachments	= $entry['attachments'];

			$temp	= array();

			foreach ($attachments as $val)
			{
				$temp[$val['pref_id']][]	= $val['filename'] . $val['extension'];
			}

			foreach ($temp as $pref_id => $names)
			{
				if (isset($this->upload_pref_id_map[$pref_id]['field_id']) === TRUE)
				{
					$field_input_data[$form_data['fields'][$this->upload_pref_id_map[$pref_id]['field_id']]['field_name']]	= implode("\n", $names);
				}
			}
		}

		unset($entry['attachments']);

		//form fields do thier own validation,
		//so lets just get results! (sexy results?)
		/*
		//skipping validation, because these entries are
		//already approved if we are importing.
		$this->errors = array_merge(
			$this->errors,
			ee()->freeform_fields->validate(
				$form_id,
				$field_input_data
			)
		);
		*/

		// -------------------------------------
		//	Errors
		// -------------------------------------

		if ( ! empty($this->errors))
		{
			return FALSE;
		}

		// -------------------------------------
		//	Insert
		// -------------------------------------

		$entry_id = ee()->freeform_forms->insert_new_entry(
			$form_id,
			$field_input_data
		);

		//--------------------------------------
		//  Add attachments to file uploads table
		//--------------------------------------

		if ( ! empty($attachments))
		{
			foreach ($attachments as $attachment)
			{
				if (empty($this->upload_pref_id_map[$attachment['pref_id']]['field_id']))
				{
					continue;
				}

				$field_id	= $this->upload_pref_id_map[$attachment['pref_id']]['field_id'];
				$file_id	= $this->set_legacy_attachment(
					$form_id,
					$entry_id,
					$field_id,
					$attachment
				);

				if ($file_id === FALSE)
				{
					//	return FALSE;
					//	why we don't return false?
					//	it is a mystery
				}
			}
		}

		// -------------------------------------
		//	User emails?
		// -------------------------------------

		if ( ! empty($entry['user_emails']))
		{
			$insert	= array(
				'site_id'		=> ee()->config->item('site_id'),
				'author_id'		=> (
					! empty($field_input_data['author_id'])
				) ? $field_input_data['author_id']: '',
				'ip_address'	=> (
					! empty($field_input_data['ip_address'])
				) ? $field_input_data['ip_address']: '',
				'form_id'		=> $form_id,
				'entry_id'		=> $entry_id
			);

			foreach ($entry['user_emails'] as $email_count)
			{
				$insert['email_count']	= $email_count;

				ee()->db->insert(
					'exp_freeform_user_email',
					$insert
				);
			}
		}

		// -------------------------------------
		//	Record new entry id
		// -------------------------------------

		ee()->db->update(
			'exp_freeform_entries' . $this->table_suffix,
			array('new_entry_id' => $entry_id),
			array('entry_id' => $entry['entry_id'])
		);

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $entry_id;
	}

	// End set legacy entry


	// --------------------------------------------------------------------

	/**
	 * Set legacy attachment
	 *
	 * @access	public
	 * @return	array
	 */

	public function set_legacy_attachment(
		$form_id = '',
		$entry_id = '',
		$field_id = '',
		$attachment = array()
	) {
		// -------------------------------------
		//	Validate
		// -------------------------------------

		if (empty($form_id) OR
			 empty($entry_id) OR
			 empty($field_id) OR
			 empty($attachment)
		)
		{
			return FALSE;
		}

		// -------------------------------------
		//	Prep
		// -------------------------------------

		$insert 	= array(
			'form_id' 		=> $form_id,
			'entry_id'		=> $entry_id,
			'field_id'		=> $field_id,
			'site_id' 		=> ee()->config->item('site_id'),
			'server_path' 	=> $attachment['server_path'],
			'filename'		=> $attachment['filename'],
			'extension' 	=> preg_replace('/^\./', '', $attachment['extension']),
			'filesize'		=> $attachment['filesize']
		);

		$insert['filename']	= $insert['filename'] . '.' . $insert['extension'];

		// -------------------------------------
		//	Library
		// -------------------------------------

		ee()->load->model('freeform_file_upload_model');

		// -------------------------------------
		//	Insert
		// -------------------------------------

		$file_id = ee()->freeform_file_upload_model->insert($insert);

		// -------------------------------------
		//	Return
		// -------------------------------------

		return $file_id;
	}
	// End set legacy attachment


	// --------------------------------------------------------------------

	/**
	 * Propery Setter
	 *
	 * @access	public
	 * @param	string	$property	string name of object property to set
	 * @param	mixed	$value		value of $property
	 * @return	bool				value was successfuly set anew
	 */
	public function set_property($property, $value)
	{
		if (isset($this->$property) === FALSE)
		{
			return FALSE;
		}

		$this->$property	= $value;

		return TRUE;
	}
	// End set property
}
//END Freeform_migration