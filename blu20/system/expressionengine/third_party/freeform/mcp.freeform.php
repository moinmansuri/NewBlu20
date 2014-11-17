<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Control Panel
 *
 * The Control Panel master class that handles all of the CP requests and displaying.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/mcp.freeform.php
 */

if ( ! class_exists('Module_builder_freeform'))
{
	require_once 'addon_builder/module_builder.php';
}

class Freeform_mcp extends Module_builder_freeform
{
	private $migration_batch_limit	= 100;
	private $pro_update				= FALSE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	bool		Enable calling of methods based on URI string
	 * @return	string
	 */

	public function __construct( $switch = TRUE )
	{
		parent::__construct();

		// Install or Uninstall Request
		if ((bool) $switch === FALSE) return;

		if ( ! function_exists('lang'))
		{
			ee()->load->helper('language');
		}

		// --------------------------------------------
		//  Module Menu Items
		// --------------------------------------------

		$menu	= array(
			'module_forms'			=> array(
				'link'  => $this->base,
				'title' => lang('forms')
			),
			'module_fields' 			=> array(
				'link'  => $this->base . AMP . 'method=fields',
				'title' => lang('fields')
			),
			'module_fieldtypes' 			=> array(
				'link'  => $this->base . AMP . 'method=fieldtypes',
				'title' => lang('fieldtypes')
			),
			'module_notifications' 		=> array(
				'link'  => $this->base . AMP . 'method=notifications',
				'title' => lang('notifications')
			),
			
			'module_composer_templates' 			=> array(
				'link'  => $this->base . AMP . 'method=templates',
				'title' => lang('composer_templates')
			),
			
			/*'module_export' 			=> array(
				'link'  => $this->base . AMP . 'method=export',
				'title' => lang('export')
			),*/
			'module_utilities' 		=> array(
				'link'  => $this->base . AMP . 'method=utilities',
				'title' => lang('utilities')
			),
			
			'module_permissions' 		=> array(
				'link'  => $this->base . AMP . 'method=permissions',
				'title' => lang('permissions')
			),
			
			'module_preferences' 		=> array(
				'link'  => $this->base . AMP . 'method=preferences',
				'title' => lang('preferences')
			),
			'module_documentation'		=> array(
				'link'  => FREEFORM_DOCS_URL,
				'title' => lang('help'),
				'new_window' => TRUE
			),
		);

		$this->cached_vars['lang_module_version'] 	= lang('freeform_module_version');
		$this->cached_vars['module_version'] 		= FREEFORM_VERSION;
		$this->cached_vars['module_menu_highlight']	= 'module_forms';
		$this->cached_vars['inner_nav_links'] 		= array();

		// -------------------------------------
		//	css includes. WOOT!
		// -------------------------------------

		$this->cached_vars['cp_stylesheet'] 		= array(
			'chosen',
			'standard_cp'
		);

		$this->cached_vars['cp_javascript'] 		= array(
			'standard_cp.min',
			'chosen.jquery.min'
		);

		// -------------------------------------
		//	custom CP?
		// -------------------------------------

		$debug_normal = (ee()->input->get_post('debug_normal') !== FALSE);

		$is_crappy_ie_version = FALSE;
		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);

		if (stristr($ua, 'msie 6') OR
			stristr($ua, 'msie 7') OR
			stristr($ua, 'msie 8'))
		{
			//technically this should be true for any IE version, but...
			$is_crappy_ie_version = TRUE;
		}

		if ( ! $debug_normal AND
			 ! $is_crappy_ie_version AND
			 ! $this->check_no($this->preference('use_solspace_mcp_style')))
		{
			$this->cached_vars['cp_stylesheet'][] = 'custom_cp';
		}

		//avoids AR collisions
		$this->data->get_module_preferences();
		$this->data->get_global_module_preferences();
		$this->data->show_all_sites();

		// -------------------------------------
		//	run upgrade or downgrade scripts
		// -------------------------------------

		if (FREEFORM_PRO AND $this->data->global_preference('ffp') === 'n' OR
			! FREEFORM_PRO AND $this->data->global_preference('ffp') === 'y')
		{
			$_GET['method'] = 'freeform_module_update';
			$this->pro_update = TRUE;
		}

		

		foreach ($menu as $menu_item => $menu_data)
		{
			if ($menu_item == 'module_documentation')
			{
				continue;
			}

			if ( ! $this->check_permissions($menu_item, FALSE))
			{
				unset($menu[$menu_item]);
			}
		}

		


		$this->cached_vars['module_menu'] 			= $menu;
	}
	// END Freeform_cp_base()


	//---------------------------------------------------------------------
	// begin views
	//---------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Module's Main Homepage
	 *
	 * @access	public
	 * @param	string
	 * @return	null
	 */

	public function index ($message='')
	{
		if ($message == '' AND ee()->input->get('msg') !== FALSE)
		{
			$message = lang(ee()->input->get('msg'));
		}

		return $this->forms($message);
	}
	// END index()


	// --------------------------------------------------------------------

	/**
	 * My Forms
	 *
	 * @access	public
	 * @param	string $message incoming message for flash data
	 * @return	string html output
	 */

	public function forms ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND ! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$new_form_link = $this->mod_link(array(
			'method' => 'edit_form'
		));

		$this->cached_vars['new_form_link']	= $new_form_link;

		$this->add_crumb( lang('forms') );

		$this->freeform_add_right_link(lang('new_form'), $new_form_link);

		$this->set_highlight('module_forms');

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$row_limit			= $this->data->defaults['mcp_row_limit'];
		$paginate			= '';
		$row_count			= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		ee()->load->model('freeform_form_model');

		if ( ! $this->data->show_all_sites())
		{
			ee()->freeform_form_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$total_results = ee()->freeform_form_model->count(array(), FALSE);

		// do we need pagination?
		if ( $total_results > $row_limit )
		{
			$row_count		= $this->get_post_or_zero('row');

			$url 			= $this->mod_link(array(
				'method' => 'forms'
			));

			//get pagination info
			$pagination_data 	= $this->universal_pagination(array(
				'total_results'			=> $total_results,
				'limit'					=> $row_limit,
				'current_page'			=> $row_count,
				'pagination_config'		=> array('base_url' => $url),
				'query_string_segment'	=> 'row'
			));

			ee()->freeform_form_model->limit(
				$row_limit,
				$pagination_data['pagination_page']
			);

			$paginate 		= $pagination_data['pagination_links'];
		}

		ee()->freeform_form_model->order_by('form_label');

		$this->cached_vars['paginate'] = $paginate;

		// -------------------------------------
		//	Did they upgrade from FF3?
		// -------------------------------------

		$this->cached_vars['legacy']		= FALSE;
		$this->cached_vars['migrate_link']	= '';

		ee()->load->library('freeform_migration');

		if ( ee()->freeform_migration->legacy() === TRUE )
		{
			$this->cached_vars['legacy']		= TRUE;
			$this->cached_vars['migrate_link']	= $this->mod_link(array('method' => 'utilities'));
		}

		// -------------------------------------
		//	data
		// -------------------------------------

		$rows = ee()->freeform_form_model->get();
		$form_data = array();

		if ($rows !== FALSE)
		{
			// -------------------------------------
			//	check for composer for each form
			// -------------------------------------

			$form_ids = array();

			$potential_composer_ids = array();

			foreach ($rows as $row)
			{
				$form_ids[] = $row['form_id'];

				if ($this->is_positive_intlike($row['composer_id']))
				{
					$potential_composer_ids[$row['form_id']] = $row['composer_id'];
				}
			}

			$has_composer = array();

			if ( ! empty($potential_composer_ids))
			{
				ee()->load->model('freeform_composer_model');
				$composer_ids = ee()->freeform_composer_model
									->key('composer_id', 'composer_id')
									->where('preview !=', 'y')
									->where_in(
										'composer_id',
										array_values($potential_composer_ids)
									)
									->get();

				if ( ! empty($composer_ids))
				{
					foreach ($potential_composer_ids as $form_id => $composer_id)
					{
						if (in_array($composer_id, $composer_ids))
						{
							$has_composer[$form_id] = $composer_id;
						}
					}
				}
			}

			// -------------------------------------
			//	suppliment rows
			// -------------------------------------

			foreach ($rows as $row)
			{
				$row['submissions_count']		= (
					$this->data->get_form_submissions_count($row['form_id'])
				);

				$row['moderate_count']			= (
					$this->data->get_form_needs_moderation_count($row['form_id'])
				);

				$row['has_composer']			= isset(
					$has_composer[$row['form_id']]
				);

				// -------------------------------------
				//	piles o' links
				// -------------------------------------

				$row['form_submissions_link'] 	= $this->mod_link(array(
					'method' 		=> 'entries',
					'form_id' 		=> $row['form_id']
				));

				$row['form_moderate_link'] 		= $this->mod_link(array(
					'method' 		=> 'moderate_entries',
					'form_id' 		=> $row['form_id'],
					'search_status'	=> 'pending'
				));

				$row['form_edit_composer_link'] = $this->mod_link(array(
					'method' 		=> 'form_composer',
					'form_id' 		=> $row['form_id']
				));

				$row['form_settings_link']		= $this->mod_link(array(
					'method' 		=> 'edit_form',
					'form_id' 		=> $row['form_id']
				));

				$row['form_duplicate_link']		= $this->mod_link(array(
					'method' 		=> 'edit_form',
					'duplicate_id' 	=> $row['form_id']
				));

				$row['form_delete_link']		= $this->mod_link(array(
					'method' 		=> 'delete_confirm_form',
					'form_id' 		=> $row['form_id']
				));

				$form_data[] = $row;
			}
		}

		$this->cached_vars['form_data'] = $form_data;

		$this->cached_vars['form_url']	= $this->mod_link(array(
			'method' 		=> 'delete_confirm_form'
		));

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		// -------------------------------------
		//	JS
		// -------------------------------------

		ee()->cp->add_js_script(
			array('plugin' => array('tooltip', 'dataTables'))
		);

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'forms.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END forms


	// --------------------------------------------------------------------

	/**
	 * delete_confirm_form
	 *
	 * @access	public
	 * @return	string
	 */

	public function delete_confirm_form ()
	{
		$form_ids = ee()->input->get_post('form_id', TRUE);

		if ( ! is_array($form_ids) AND
			 ! $this->is_positive_intlike($form_ids) )
		{
			$this->actions()->full_stop(lang('no_form_ids_submitted'));
		}

		//already checked for numeric :p
		if ( ! is_array($form_ids))
		{
			$form_ids = array($form_ids);
		}

		return $this->delete_confirm(
			'delete_forms',
			array('form_ids' => $form_ids),
			'delete_form_confirmation'
		);
	}
	//END delete_confirm_form


	// --------------------------------------------------------------------

	/**
	 * delete_forms
	 *
	 * @access	public
	 * @return	string
	 */

	public function delete_forms ($form_ids = array())
	{
		$message = 'delete_form_success';

		if ( empty($form_ids) )
		{
			$form_ids = ee()->input->get_post('form_ids');
		}

		if ( ! is_array($form_ids) AND
			 $this->is_positive_intlike($form_ids))
		{
			$form_ids = array($form_ids);
		}

		//if everything is all nice and array like, DELORT
		//but one last check on each item to make sure its a number
		if ( is_array($form_ids))
		{
			ee()->load->library('freeform_forms');

			foreach ($form_ids as $form_id)
			{
				if ($this->is_positive_intlike($form_id))
				{
					ee()->freeform_forms->delete_form($form_id);
				}
			}
		}

		//the voyage home
		ee()->functions->redirect($this->mod_link(array(
			'method'	=> 'index',
			'msg'		=> $message
		)));
	}
	//END delete_forms


	// --------------------------------------------------------------------

	/**
	 * Edit Form
	 *
	 * @access	public
	 * @return	string html output
	 */

	public function edit_form ()
	{
		// -------------------------------------
		//	form ID? we must be editing
		// -------------------------------------

		$form_id 	= $this->get_post_or_zero('form_id');

		$update 	= $this->cached_vars['update'] = ($form_id != 0);

		// -------------------------------------
		//	default data
		// -------------------------------------

		$inputs = array(
			'form_id'					=> '0',
			'form_name'					=> '',
			'form_label'				=> '',
			'default_status'			=> $this->data->defaults['default_form_status'],
			'notify_admin'				=> 'n',
			'notify_user'				=> 'n',
			'user_email_field' 			=> '',
			'user_notification_id'		=> '0',
			'admin_notification_id'		=> '0',
			'admin_notification_email'	=> ee()->config->item('webmaster_email'),
			'form_description'			=> '',
			'template_id'				=> '0',
			'composer_id'				=> '0',
			'field_ids'					=> '',
			'field_order'				=> '',
		);

		// -------------------------------------
		//	updating?
		// -------------------------------------

		if ($update)
		{
			$form_data = $this->data->get_form_info($form_id);

			if ($form_data)
			{
				foreach ($form_data as $key => $value)
				{
					if ($key == 'admin_notification_email')
					{
						$value = str_replace('|', ', ', $value);
					}

					if ($key == 'field_ids')
					{
						$value =  ( ! empty($value)) ? implode('|', $value) : '';
					}

					$inputs[$key] = form_prep($value);
				}
			}
			else
			{
				$this->actions()->full_stop(lang('invalid_form_id'));
			}
		}

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb(
			lang('forms'),
			$this->mod_link(array('method' => 'forms'))
		);

		$this->add_crumb(
			$update ?
				lang('update_form') . ': ' . $form_data['form_label'] :
				lang('new_form')
		);

		$this->set_highlight('module_forms');

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id = $this->get_post_or_zero('duplicate_id');

		$this->cached_vars['duplicate_id']	= $duplicate_id;
		$this->cached_vars['duplicated']	= FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$form_data = $this->data->get_form_info($duplicate_id);

			if ($form_data)
			{
				foreach ($form_data as $key => $value)
				{
					if (in_array($key, array('form_id', 'form_label', 'form_name')))
					{
						continue;
					}

					if ($key == 'field_ids')
					{
						$value =  ( ! empty($value)) ? implode('|', $value) : '';
					}

					if ($key == 'admin_notification_email')
					{
						$value = str_replace('|', ', ', $value);
					}

					$inputs[$key] = form_prep($value);
				}

				$this->cached_vars['duplicated'] 		= TRUE;
				$this->cached_vars['duplicated_from']	= $form_data['form_label'];
			}
		}

		if (isset($form_data['field_ids']) AND
			! empty($form_data['field_ids']) AND
			isset($form_data['field_order']) AND
			! empty($form_data['field_order']))
		{
			$field_ids = $form_data['field_ids'];

			if ( ! is_array($field_ids))
			{
				$field_ids = $this->actions()->pipe_split($field_ids);
			}

			$field_order = $form_data['field_order'];

			if ( ! is_array($field_order))
			{
				$field_order = $this->actions()->pipe_split($field_order);
			}

			$missing_ids = array_diff($field_ids, $field_order);

			$inputs['field_order'] = implode('|', array_merge($field_order, $missing_ids));
		}

		// -------------------------------------
		//	load inputs
		// -------------------------------------

		foreach ($inputs as $key => $value)
		{
			$this->cached_vars[$key] = $value;
		}

		// -------------------------------------
		//	select boxes
		// -------------------------------------

		$this->cached_vars['statuses']			= $this->data->get_form_statuses();

		ee()->load->model('freeform_field_model');

		$available_fields						= ee()->freeform_field_model->get();

		$available_fields						= ($available_fields !== FALSE) ?
													$available_fields :
													array();

		//fields
		$this->cached_vars['available_fields']	= $available_fields;

		//notifications
		$this->cached_vars['notifications']		= $this->data->get_available_notification_templates();

		// -------------------------------------
		//	user email fields
		// -------------------------------------

		$user_email_fields = array('' => lang('choose_user_email_field'));

		$f_rows =	ee()->freeform_field_model
						->select('field_id, field_label, settings')
						->get(array('field_type' => 'text'));

		//we only want fields that are being validated as email
		if ($f_rows)
		{
			foreach ($f_rows as $row)
			{
				$row_settings = json_decode($row['settings'], TRUE);
				$row_settings = (is_array($row_settings)) ? $row_settings : array();

				if (isset($row_settings['field_content_type']) AND
					$row_settings['field_content_type'] == 'email')
				{
					$user_email_fields[$row['field_id']] = $row['field_label'];
				}
			}
		}

		$this->cached_vars['user_email_fields'] = $user_email_fields;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' => 'save_form'
		));

		// -------------------------------------
		//	js libs
		// -------------------------------------

		$this->load_fancybox();
		$this->cached_vars['cp_javascript'][] = 'jquery.smooth-scroll.min';

		ee()->cp->add_js_script(array(
			'ui'	=> array('draggable', 'droppable', 'sortable')
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'edit_form.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END edit_form


	// --------------------------------------------------------------------

	/**
	 * form composer
	 *
	 * ajax form and field builder
	 *
	 * @access	public
	 * @param 	string message lang line
	 * @return	string html output
	 */

	public function form_composer ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND ! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	form_id
		// -------------------------------------

		$form_id 	= ee()->input->get_post('form_id', TRUE);
		$form_data 	= $this->data->get_form_info($form_id);

		if ( ! $form_data)
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$update = $form_data['composer_id'] != 0;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb( lang('forms'), $this->base );
		$this->add_crumb( lang('composer') . ': ' . $form_data['form_label'] );

		$this->set_highlight('module_forms');

		// -------------------------------------
		//	data
		// -------------------------------------

		$this->cached_vars['form_data'] 		= $form_data;

		// -------------------------------------
		//	fields for composer
		// -------------------------------------

		ee()->load->model('freeform_field_model');

		$available_fields = ee()->freeform_field_model
								->where('composer_use', 'y')
								->order_by('field_label')
								->key('field_name')
								->get();

		$available_fields = ($available_fields !== FALSE) ?
								$available_fields :
								array();

		// -------------------------------------
		//	templates
		// -------------------------------------

		ee()->load->model('freeform_template_model');

		$available_templates =  ee()->freeform_template_model
									->where('enable_template', 'y')
									->order_by('template_label')
									->key('template_id', 'template_label')
									->get();

		$available_templates = ($available_templates !== FALSE) ?
									$available_templates :
									array();

		// -------------------------------------
		//	get field output for composer
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$field_composer_output	= array();
		$field_id_list			= array();

		foreach ($available_fields as $field_name => $field_data)
		{
			$field_id_list[$field_data['field_id']] = $field_name;

			//encode to keep JS from running
			//camel case because its exposed in JS
			$field_composer_output[$field_name] = $this->composer_field_data(
				$field_data['field_id'],
				$field_data,
				TRUE
			);
		}

		$this->cached_vars['field_id_list']					= $this->json_encode($field_id_list);
		$this->cached_vars['field_composer_output_json']	= $this->json_encode($field_composer_output);
		$this->cached_vars['available_fields']				= $available_fields;
		$this->cached_vars['available_templates']			= $available_templates;
		$this->cached_vars['prohibited_field_names']		= $this->data->prohibited_names;
		$this->cached_vars['notifications']					= $this->data->get_available_notification_templates();
		$this->cached_vars['disable_missing_submit_warning'] = $this->check_yes(
			$this->preference('disable_missing_submit_warning')
		);

		// -------------------------------------
		//	previous composer data?
		// -------------------------------------

		$composer_data = '{}';

		if ($form_data['composer_id'] > 0)
		{
			ee()->load->model('freeform_composer_model');

			$composer = ee()->freeform_composer_model
							->select('composer_data')
							->where('composer_id', $form_data['composer_id'])
							->get_row();

			if ($composer !== FALSE)
			{
				$composer_data_test = $this->json_decode($composer['composer_data']);

				if ($composer_data_test)
				{
					$composer_data = $composer['composer_data'];
				}
			}
		}

		$this->cached_vars['composer_layout_data'] = $composer_data;

		// ----------------------------------------
		//	Load vars
		// ----------------------------------------

		$this->cached_vars['lang_allowed_html_tags'] = (
			lang('allowed_html_tags') .
			"&lt;" . implode("&gt;, &lt;", $this->data->allowed_html_tags) . "&gt;"
		);

		$this->cached_vars['captcha_dummy_url'] = $this->sc->addon_theme_url .
													'images/captcha.png';

		$this->cached_vars['new_field_url'] = $this->mod_link(array(
			'method' 	=> 'edit_field',
			//this builds a URL, so yes this is intentionally a string
			'modal' 	=> 'true'
		), TRUE);

		$this->cached_vars['field_data_url'] = $this->mod_link(array(
			'method'	=> 'composer_field_data'
		), TRUE);

		$this->cached_vars['composer_preview_url'] = $this->mod_link(array(
			'method'	=> 'composer_preview',
			'form_id'	=> $form_id
		), TRUE);

		$this->cached_vars['composer_ajax_save_url'] = $this->mod_link(array(
			'method'	=> 'save_composer_data',
			'form_id'	=> $form_id
		), TRUE);

		//
		$this->cached_vars['composer_save_url'] = $this->mod_link(array(
			'method'	=> 'save_composer_data',
			'form_id'	=> $form_id
		));

		$this->cached_vars['allowed_html_tags'] = "'" .
			implode("','", $this->data->allowed_html_tags) . "'";

		// -------------------------------------
		//	js libs
		// -------------------------------------

		$this->load_fancybox();

		ee()->cp->add_js_script(array(
			'ui'		=> array('sortable', 'draggable', 'droppable'),
			'file'		=> array('underscore', 'json2')
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['cp_javascript'][] = 'composer_cp.min';
		$this->cached_vars['cp_javascript'][] = 'edit_field_cp.min';
		$this->cached_vars['cp_javascript'][] = 'security.min';

		$this->cached_vars['current_page'] = $this->view(
			'composer.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END form_composer


	// --------------------------------------------------------------------

	/**
	 * Composer preview
	 *
	 * @access	public
	 * @return	mixed 	ajax return if detected or else html without cp
	 */

	public function composer_preview ()
	{
		$form_id		= $this->get_post_or_zero('form_id');
		$template_id	= $this->get_post_or_zero('template_id');
		$composer_id	= $this->get_post_or_zero('composer_id');
		$preview_id		= $this->get_post_or_zero('preview_id');
		$subpreview		= (ee()->input->get_post('subpreview') !== FALSE);
		$composer_page	= $this->get_post_or_zero('composer_page');

		if ( ! $this->data->is_valid_form_id($form_id))
		{
			$this->actions()->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	is this a preview?
		// -------------------------------------

		if ($preview_id > 0)
		{
			$preview_mode	= TRUE;
			$composer_id	= $preview_id;
		}

		$page_count = 1;

		// -------------------------------------
		//	main output or sub output?
		// -------------------------------------

		if ( ! $subpreview)
		{
			// -------------------------------------
			//	get composer data and build page count
			// -------------------------------------

			if ($composer_id > 0)
			{
				ee()->load->model('freeform_composer_model');

				$composer = ee()->freeform_composer_model
								->select('composer_data')
								->where('composer_id', $composer_id)
								->get_row();

				if ($composer !== FALSE)
				{
					$composer_data = $this->json_decode(
						$composer['composer_data'],
						TRUE
					);

					if ($composer_data AND
						 isset($composer_data['rows']) AND
						 ! empty($composer_data['rows']))
					{
						foreach ($composer_data['rows'] as $row)
						{
							if ($row == 'page_break')
							{
								$page_count++;
							}
						}
					}
				}
			}

			$page_url = array();

			for ($i = 1, $l = $page_count; $i <= $l; $i++)
			{
				$page_url[] = $this->mod_link(array(
					'method'		=> __FUNCTION__,
					'form_id'		=> $form_id,
					'template_id'	=> $template_id,
					'preview_id'	=> $preview_id,
					'subpreview'	=> 'true',
					'composer_page'	=> $i
				));
			}

			$this->cached_vars['page_url']		= $page_url;
			$this->cached_vars['default_preview_css'] = $this->sc->addon_theme_url .
														'css/default_composer.css';
			$this->cached_vars['jquery_src']	= rtrim(ee()->config->item('theme_folder_url'), '/') .
										'/javascript/compressed/jquery/jquery.js';

			$html = $this->view('composer_preview.html', NULL, TRUE);
		}
		else
		{

			$subhtml = "{exp:freeform:composer form_id='$form_id'";
			$subhtml .= ($composer_page > 1) ? " multipage_page='" . $composer_page . "'" : '';
			$subhtml .= ($template_id > 0) ? " composer_template_id='" . $template_id . "'" : '';
			$subhtml .= ($preview_id > 0) ? " preview_id='" . $preview_id . "'" : '';
			$subhtml .= "}";

			$html = $this->actions()->template()->process_string_as_template($subhtml);
		}

		if ($this->is_ajax_request())
		{

			return $this->send_ajax_response(array(
				'success'	=> TRUE,
				'html'		=> $html
			));
		}
		else
		{
			exit($html);
		}
	}
	//end composer preview


	// --------------------------------------------------------------------

	/**
	 * entries
	 *
	 * @access	public
	 * @param 	string 	$message 	message lang line
	 * @param 	bool 	$moderate 	are we moderating?
	 * @param   bool  	$export 	export?
	 * @return	string 				html output
	 */

	public function entries ( $message = '' , $moderate = FALSE, $export = FALSE)
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND
			! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg', TRUE));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	moderate
		// -------------------------------------

		$search_status = ee()->input->get_post('search_status');

		$moderate = (
			$moderate AND
			($search_status == 'pending' OR
				$search_status === FALSE
			)
		);

		//if moderate and search status was not submitted, fake into pending
		if ($moderate AND $search_status === FALSE)
		{
			$_POST['search_status'] = 'pending';
		}

		$this->cached_vars['moderate']	= $moderate;
		$this->cached_vars['method']	= $method = (
			$moderate ? 'moderate_entries' : 'entries'
		);

		// -------------------------------------
		//	user using session id instead of cookies?
		// -------------------------------------

		$this->cached_vars['fingerprint'] = isset(
			ee()->session->userdata['fingerprint']
		) ? ee()->session->userdata['fingerprint'] : 0;

		// -------------------------------------
		//	form data? legit? GTFO?
		// -------------------------------------

		$form_id = ee()->input->get_post('form_id');

		ee()->load->library('freeform_forms');
		ee()->load->model('freeform_form_model');

		//form data does all of the proper id validity checks for us
		$form_data = $this->data->get_form_info($form_id);

		if ( ! $form_data)
		{
			$this->actions()->full_stop(lang('invalid_form_id'));
			exit();
		}

		$this->cached_vars['form_id']		= $form_id;
		$this->cached_vars['form_label']	= $form_data['form_label'];

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb(
			lang('forms'),
			$this->mod_link(array('method' => 'forms'))
		);

		$this->add_crumb(
			$form_data['form_label'] . ': ' .
				lang($moderate ? 'moderate' : 'entries')
		);

		$this->set_highlight('module_forms');

		$this->freeform_add_right_link(
			lang('new_entry'),
			$this->mod_link(array(
				'method'	=> 'edit_entry',
				'form_id'	=> $form_id
			))
		);

		// -------------------------------------
		//	status prefs
		// -------------------------------------

		$form_statuses = $this->data->get_form_statuses();

		$this->cached_vars['form_statuses'] = $form_statuses;

		// -------------------------------------
		//	rest of models
		// -------------------------------------

		ee()->load->model('freeform_entry_model');

		ee()->freeform_entry_model->id($form_id);

		// -------------------------------------
		//	custom field labels
		// -------------------------------------

		$standard_columns 	= $this->get_standard_column_names();

		//we want author instead of author id until we get data
		$possible_columns 	= $standard_columns;
		//key = value
		$all_columns = array_combine($standard_columns, $standard_columns);
		$column_labels 		= array();
		$field_column_names = array();

		//field prefix
		$f_prefix 			= ee()->freeform_form_model->form_field_prefix;

		//keyed labels for the front end
		foreach ($standard_columns as $column_name)
		{
			$column_labels[$column_name] = lang($column_name);
		}

		// -------------------------------------
		//	check for fields with custom views for entry tables
		// -------------------------------------

		ee()->load->library('freeform_fields');

		//fields in this form
		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			//outputs form_field_1, form_field_2, etc for ->select()
			$field_id_name = $f_prefix . $field_id;

			$field_column_names[$field_id_name] 		= $field_data['field_name'];
			$all_columns[$field_id_name] 		= $field_data['field_name'];

			$column_labels[$field_data['field_name']] 	= $field_data['field_label'];
			$column_labels[$field_id_name] 				= $field_data['field_label'];

			$possible_columns[] 						= $field_id;

			$instance =& ee()->freeform_fields->get_field_instance(array(
				'field_id'		=> $field_id,
				'field_data'	=> $field_data
			));

			if ( ! empty($instance->entry_views))
			{
				foreach ($instance->entry_views as $e_lang => $e_method)
				{
					$this->freeform_add_right_link(
						$e_lang,
						$this->mod_link(array(
							'method'		=> 'field_method',
							'field_id'		=> $field_id,
							'field_method'	=> $e_method,
							'form_id'		=> $form_id
						))
					);
				}
			}
		}

		// -------------------------------------
		//	visible columns
		// -------------------------------------

		$visible_columns = $this->visible_columns($standard_columns, $possible_columns);

		$this->cached_vars['visible_columns']	= $visible_columns;
		$this->cached_vars['column_labels']		= $column_labels;
		$this->cached_vars['possible_columns']	= $possible_columns;
		$this->cached_vars['all_columns']		= $all_columns;

		// -------------------------------------
		//	prep unused from from possible
		// -------------------------------------

		//so so used
		$un_used = array();

		foreach ($possible_columns as $pcid)
		{
			$check = ($this->is_positive_intlike($pcid)) ?
						$f_prefix . $pcid :
						$pcid;

			if ( ! in_array($check, $visible_columns))
			{
				$un_used[] = $check;
			}
		}

		$this->cached_vars['unused_columns'] = $un_used;

		// -------------------------------------
		//	build query
		// -------------------------------------

		//base url for pagination
		$pag_url		= array(
			'method' 	=> $method,
			'form_id'	=> $form_id
		);

		//cleans out blank keys from unset
		$find_columns 	= array_merge(array(), $visible_columns);
		$must_haves 	= array('entry_id');

		// -------------------------------------
		//	search criteria
		// 	building query
		// -------------------------------------

		$has_search = FALSE;

		$search_vars = array(
			'search_keywords',
			'search_status',
			'search_date_range',
			'search_date_range_start',
			'search_date_range_end',
			'search_on_field'
		);

		foreach ($search_vars as $search_var)
		{
			$$search_var = ee()->input->get_post($search_var, TRUE);

			//set for output
			$this->cached_vars[$search_var] = (
				($$search_var) ? trim($$search_var) : ''
			);
		}

		// -------------------------------------
		//	search keywords
		// -------------------------------------

		if ($search_keywords AND
			trim($search_keywords) !== '' AND
			$search_on_field AND
			in_array($search_on_field, $visible_columns))
		{
			ee()->freeform_entry_model->like(
				$search_on_field,
				$search_keywords
			);

			//pagination
			$pag_url['search_keywords'] = $search_keywords;
			$pag_url['search_on_field'] = $search_on_field;

			$has_search = TRUE;
		}
		//no search on field? guess we had better search it all *gulp*
		else if ($search_keywords AND trim($search_keywords) !== '')
		{
			$first = TRUE;

			ee()->freeform_entry_model->group_like(
				$search_keywords,
				array_values($visible_columns)
			);

			$pag_url['search_keywords'] = $search_keywords;

			$has_search = TRUE;
		}

		//status search?
		if ($moderate)
		{
			ee()->freeform_entry_model->where('status', 'pending');
		}
		else if ($search_status AND in_array($search_status, array_flip( $form_statuses)))
		{
			ee()->freeform_entry_model->where('status', $search_status);

			//pagination
			$pag_url['search_status'] = $search_status;

			$has_search = TRUE;
		}

		// -------------------------------------
		//	date range?
		// -------------------------------------

		//pagination
		if ($search_date_range == 'date_range')
		{
			//if its the same date, lets set the time to the end of the day
			if ($search_date_range_start == $search_date_range_end)
			{
				$search_date_range_end .= ' 23:59';
			}

			if ($search_date_range_start !== FALSE)
			{
				$pag_url['search_date_range_start'] = $search_date_range_start;
			}

			if ($search_date_range_end !== FALSE)
			{
				$pag_url['search_date_range_end'] = $search_date_range_end;
			}

			//pagination
			if ($search_date_range_start OR $search_date_range_end)
			{
				$pag_url['search_date_range'] = 'date_range';
				$has_search = TRUE;
			}
		}
		else if ($search_date_range !== FALSE)
		{
			$pag_url['search_date_range'] = $search_date_range;
			$has_search = TRUE;
		}

		ee()->freeform_entry_model->date_where(
			$search_date_range,
			$search_date_range_start,
			$search_date_range_end
		);

		// -------------------------------------
		//	any searches?
		// -------------------------------------

		$this->cached_vars['has_search'] = $has_search;

		// -------------------------------------
		//	data from all sites?
		// -------------------------------------

		if ( ! $this->data->show_all_sites())
		{
			ee()->freeform_entry_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		//we need the counts for exports and end results
		$total_entries 		= ee()->freeform_entry_model->count(array(), FALSE);

		// -------------------------------------
		//	orderby
		// -------------------------------------

		$order_by	= 'entry_date';

		$p_order_by = ee()->input->get_post('order_by');

		if ($p_order_by !== FALSE AND in_array($p_order_by, $all_columns))
		{
			$order_by				= $p_order_by;
			$pag_url['order_by']	= $order_by;
		}

		// -------------------------------------
		//	sort
		// -------------------------------------

		$sort		= ($order_by == 'entry_date') ? 'desc' : 'asc';

		$p_sort		= ee()->input->get_post('sort');

		if ($p_sort !== FALSE AND
			in_array(strtolower($p_sort), array('asc', 'desc')))
		{
			$sort					= strtolower($p_sort);
			$pag_url['sort']		= $sort;
		}

		ee()->freeform_entry_model->order_by($order_by, $sort);

		$this->cached_vars['order_by']	= $order_by;
		$this->cached_vars['sort']		= $sort;

		// -------------------------------------
		//	export button
		// -------------------------------------

		if ($total_entries > 0)
		{
			$this->freeform_add_right_link(
				lang('export_entries'),
				'#export_entries'
			);
		}

		// -------------------------------------
		//	export url
		// -------------------------------------

		$export_url							= $pag_url;
		$export_url['moderate']				= $moderate ? 'true' : 'false';
		$export_url['method']				= 'export_entries';

		$this->cached_vars['export_url']	= $this->mod_link($export_url);

		// -------------------------------------
		//	export?
		// -------------------------------------

		if ($export)
		{
			$export_fields = ee()->input->get_post('export_fields');
			$export_labels = $column_labels;

			// -------------------------------------
			//	build possible select alls
			// -------------------------------------

			$select = array();

			//are we sending just the selected fields?
			if ($export_fields != 'all')
			{
				$select = array_unique(array_merge($must_haves, $find_columns));

				foreach ($export_labels as $key => $value)
				{
					//clean export labels for json
					if ( ! in_array($key, $select))
					{
						unset($export_labels[$key]);
					}
				}

				//get real names
				foreach ($select as $key => $value)
				{
					if (isset($field_column_names[$value]))
					{
						$select[$key] = $field_column_names[$value];
					}
				}
			}
			//sending all fields means we need to still clean some labels
			else
			{
				foreach ($all_columns as $field_id_name => $field_name)
				{
					//clean export labels for json
					if ($field_id_name != $field_name)
					{
						unset($export_labels[$field_id_name]);
					}

					$select[] = $field_name;
				}
			}

			foreach ($export_labels as $key => $value)
			{
				//fix entities
				$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');

				$export_labels[$key] = $value;

				if (isset($field_column_names[$key]))
				{
					$export_labels[$field_column_names[$key]] = $value;
				}
			}

			ee()->freeform_entry_model->select(implode(', ', $select));

			// -------------------------------------
			//	check for chunking, etc
			// -------------------------------------

			ee()->load->library('freeform_export');

			ee()->freeform_export->format_dates = (ee()->input->get_post('format_dates') == 'y');

			ee()->freeform_export->export(array(
				'method' 			=> ee()->input->get_post('export_method'),
				'form_id' 			=> $form_id,
				'form_name' 		=> $form_data['form_name'],
				'output' 			=> 'download',
				'model' 			=> ee()->freeform_entry_model,
				'remove_entry_id' 	=> ($export_fields != 'all' AND ! in_array('entry_id', $visible_columns)),
				'header_labels' 	=> $export_labels,
				'total_entries' 	=> $total_entries
			));
		}
		//END if ($export)

		// -------------------------------------
		//	selects
		// -------------------------------------

		$needed_selects = array_unique(array_merge($must_haves, $find_columns));

		ee()->freeform_entry_model->select(implode(', ', $needed_selects));

		//--------------------------------------
		//  pagination start vars
		//--------------------------------------

		$pag_url 			= $this->mod_link($pag_url);
		$row_limit			= $this->data->defaults['mcp_row_limit'];
		$paginate			= '';
		$row_count			= 0;
		//moved above exports
		//$total_entries 		= ee()->freeform_entry_model->count(array(), FALSE);
		$current_page		= 0;

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		// do we need pagination?
		if ($total_entries > $row_limit )
		{
			$row_count			= $this->get_post_or_zero('row');

			//get pagination info
			$pagination_data 	= $this->universal_pagination(array(
				'total_results'			=> $total_entries,
				'limit'					=> $row_limit,
				'current_page'			=> $row_count,
				'pagination_config'		=> array('base_url' => $pag_url),
				'query_string_segment'	=> 'row'
			));

			$paginate 			= $pagination_data['pagination_links'];
			$current_page 		= $pagination_data['pagination_page'];

			ee()->freeform_entry_model->limit($row_limit, $current_page);
		}

		$this->cached_vars['paginate'] = $paginate;

		// -------------------------------------
		//	get data
		// -------------------------------------

		$result_array 	= ee()->freeform_entry_model->get();

		$count 			= $row_count;

		$entries 		= array();

		if ( ! $result_array)
		{
			$result_array = array();
		}

		$entry_ids = array();

		foreach ($result_array as $row)
		{
			$entry_ids[] = $row['entry_id'];
		}

		// -------------------------------------
		//	allow pre_process
		// -------------------------------------

		ee()->freeform_fields->apply_field_method(array(
			'method' 		=> 'pre_process_entries',
			'form_id' 		=> $form_id,
			'entry_id'		=> $entry_ids,
			'form_data'		=> $form_data,
			'field_data'	=> $form_data['fields']
		));

		foreach ( $result_array as $row)
		{
			//apply display_entry_cp to our field data
			$field_parse = ee()->freeform_fields->apply_field_method(array(
				'method' 			=> 'display_entry_cp',
				'form_id' 			=> $form_id,
				'entry_id' 			=> $row['entry_id'],
				'form_data'			=> $form_data,
				'field_data'		=> $form_data['fields'],
				'field_input_data' 	=> $row
			));

			$row = array_merge($row, $field_parse['variables']);

			$entry						= array();

			$entry['view_entry_link']	= $this->mod_link(array(
				'method' 		=> 'view_entry',
				'form_id' 		=> $form_id,
				'entry_id' 		=> $row['entry_id']
			));

			$entry['edit_entry_link']	= $this->mod_link(array(
				'method' 		=> 'edit_entry',
				'form_id' 		=> $form_id,
				'entry_id' 		=> $row['entry_id']
			));

			$entry['approve_link']		= $this->mod_link(array(
				'method' 		=> 'approve_entries',
				'form_id' 		=> $form_id,
				'entry_ids' 	=> $row['entry_id']
			));

			$entry['count']				= ++$count;
			$entry['id']				= $row['entry_id'];

			// -------------------------------------
			//	remove entry_id and author_id if we
			// 	arent showing them
			// -------------------------------------

			if ( ! in_array('entry_id', $visible_columns))
			{
				unset($row['entry_id']);
			}

			// -------------------------------------
			//	dates
			// -------------------------------------

			if (in_array('entry_date', $visible_columns))
			{
				$row['entry_date'] = $this->format_cp_date($row['entry_date']);
			}

			if (in_array('edit_date', $visible_columns))
			{
				$row['edit_date'] = ($row['edit_date'] == 0) ? '' : $this->format_cp_date($row['edit_date']);
			}

			$entry['data'] 				= $row;

			$entries[] 					= $entry;
		}

		$this->cached_vars['entries'] = $entries;

		// -------------------------------------
		//	ajax request?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'entries'			=> $entries,
				'paginate'			=> $paginate,
				'visibleColumns'	=> $visible_columns,
				'allColumns'		=> $all_columns,
				'columnLabels'		=> $column_labels,
				'success'			=> TRUE
			));
			exit();
		}

		// -------------------------------------
		//	moderation count?
		// -------------------------------------

		//lets not waste the query if we are already moderating
		$moderation_count 	= (
			( ! $moderate) ?
				$this->data->get_form_needs_moderation_count($form_id) :
				0
		);

		if ($moderation_count > 0)
		{
			$this->cached_vars['lang_num_items_awaiting_moderation'] = str_replace(
				array('%num%', '%form_label%'),
				array($moderation_count, $form_data['form_label']),
				lang('num_items_awaiting_moderation')
			);
		}

		$this->cached_vars['moderation_count'] 	= $moderation_count;
		$this->cached_vars['moderation_link']	= $this->mod_link(array(
			'method' 		=> 'moderate_entries',
			'form_id' 		=> $form_id,
			'search_status'	=> 'pending'
		));

		// -------------------------------------
		//	is admin?
		// -------------------------------------

		$this->cached_vars['is_admin'] = $is_admin = (
			ee()->session->userdata('group_id') == 1
		);

		// -------------------------------------
		//	can save field layout?
		// -------------------------------------

		//$this->cached_vars['can_edit_layout'] = $can_edit_layout = (
		//	$is_admin OR
		//	$this->check_yes($this->preference('allow_user_field_layout'))
		//);

		//just in case
		$this->cached_vars['can_edit_layout'] = TRUE;

		$this->freeform_add_right_link(
			lang('edit_field_layout'),
			'#edit_field_layout'
		);

		// -------------------------------------
		// member groups
		// -------------------------------------

		$member_groups = array();

		if ($is_admin)
		{
			ee()->db->select('group_id, group_title');

			$member_groups = $this->prepare_keyed_result(
				ee()->db->get('member_groups'),
				'group_id',
				'group_title'
			);
		}

		$this->cached_vars['member_groups'] 	= $member_groups;

		// -------------------------------------
		//	lang items
		// -------------------------------------

		// -------------------------------------
		//	no results lang
		// -------------------------------------

		$this->cached_vars['lang_no_results_for_form'] = (
			($has_search) ?
				lang('no_results_for_search') :
				(
					($moderate) ?
						lang('no_entries_awaiting_approval') :
						lang('no_entries_for_form')
				)
		);

		// -------------------------------------
		//	moderation lang
		// -------------------------------------

		$this->cached_vars['lang_viewing_moderation'] = str_replace(
			'%form_label%',
			$form_data['form_label'],
			lang('viewing_moderation')
		);

		// -------------------------------------
		//	other vars
		// -------------------------------------

		$this->cached_vars['form_url'] 			= $this->mod_link(array(
			'method' 		=> 'entries_action',
			'return_method' => (($moderate) ? 'moderate_' :	'' ) . 'entries'
		));

		$this->cached_vars['save_layout_url']	= $this->mod_link(array(
			'method' 		=> 'save_field_layout'
		));

		// -------------------------------------
		//	js libs
		// -------------------------------------

		$this->load_fancybox();
		//$this->load_datatables();

		ee()->cp->add_js_script(array(
			'ui'	=> array('datepicker', 'sortable'),
			'file'	=> 'underscore'
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'entries.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END entries


	// --------------------------------------------------------------------

	/**
	 * Fire a field method as a page view
	 *
	 * @access	public
	 * @param	string $message		lang line to load for a message
	 * @return	string				html output
	 */
	public function field_method ($message = '')
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND
			! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg', TRUE));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	goods
		// -------------------------------------

		$form_id		= $this->get_post_or_zero('form_id');
		$field_id		= $this->get_post_or_zero('field_id');
		$field_method	= ee()->input->get_post('field_method');
		$instance		= FALSE;

		if ( $field_method == FALSE OR
			! $this->data->is_valid_form_id($form_id) OR
			$field_id == 0)
		{
			ee()->functions->redirect($this->mod_link(array('method' => 'forms')));
		}

		ee()->load->library('freeform_fields');

		$instance =& ee()->freeform_fields->get_field_instance(array(
			'form_id'	=> $form_id,
			'field_id'	=> $field_id
		));

		//legit?
		if ( ! is_object($instance) OR
			 empty($instance->entry_views) OR
			 //removed so you can post to this
			 //! in_array($field_method, $instance->entry_views) OR
			 ! is_callable(array($instance, $field_method)))
		{
			ee()->functions->redirect($this->mod_link(array('method' => 'forms')));
		}

		$method_lang = lang('field_entry_view');

		foreach ($instance->entry_views as $e_lang => $e_method)
		{
			if ($field_method == $e_method)
			{
				$method_lang = $e_lang;
			}
		}

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb(
			lang('entries'),
			$this->mod_link(array(
				'method'	=> 'entries',
				'form_id'	=> $form_id
			))
		);

		$this->add_crumb($method_lang);

		$this->set_highlight('module_forms');

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $instance->$field_method();

		// -------------------------------------
		//	loading these after the instance
		//	incase the instance exits
		// -------------------------------------

		$this->load_fancybox();

		return $this->ee_cp_view('index.html');
	}
	//END field_method


	// --------------------------------------------------------------------

	/**
	 * migrate collections
	 *
	 * @access	public
	 * @return	string
	 */

	public function migrate_collections ( $message = '' )
	{
		if ($message == '' AND ee()->input->get('msg') !== FALSE)
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(lang('utilities'));

		$this->set_highlight('module_utilities');

		//--------------------------------------
		//  Library
		//--------------------------------------

		ee()->load->library('freeform_migration');

		//--------------------------------------
		//  Variables
		//--------------------------------------

		$migrate_empty_fields	= 'n';
		$migrate_attachments	= 'n';

		$this->cached_vars['total']			= 0;
		$this->cached_vars['collections']	= '';

		$collections = ee()->input->post('collections');

		if ( $collections !== FALSE  )
		{
			$this->cached_vars['total']			= ee()->freeform_migration
														->get_migration_count(
															$collections
														);

			$this->cached_vars['collections']	= implode('|', $collections);

			if ($this->check_yes( ee()->input->post('migrate_empty_fields') ) )
			{
				$migrate_empty_fields	= 'y';
			}

			if ($this->check_yes( ee()->input->post('migrate_attachments') ) )
			{
				$migrate_attachments	= 'y';
			}
		}

		//--------------------------------------
		//  Migration ajax url
		//--------------------------------------

		$this->cached_vars['ajax_url']		= $this->base .
			'&method=migrate_collections_ajax' .
			'&migrate_empty_fields=' . $migrate_empty_fields .
			'&migrate_attachments=' . $migrate_attachments .
			'&collections=' .
			urlencode( $this->cached_vars['collections'] ) .
			'&total=' .
			$this->cached_vars['total'] .
			'&batch=0';

		//--------------------------------------
		//  images
		//--------------------------------------

		//Success image
		$this->cached_vars['success_png_url']	= $this->sc->addon_theme_url . 'images/success.png';

		//  Error image
		$this->cached_vars['error_png_url']		= $this->sc->addon_theme_url . 'images/exclamation.png';

		//--------------------------------------
		//  Javascript
		//--------------------------------------

		$this->cached_vars['cp_javascript'][] = 'migrate';

		ee()->cp->add_js_script(
			array('ui' => array('core', 'progressbar'))
		);

		//--------------------------------------
		//  Load page
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'migrate.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//	End migrate collections


	// --------------------------------------------------------------------

	/**
	 * migrate collections ajax
	 *
	 * @access	public
	 * @return	string
	 */

	public function migrate_collections_ajax()
	{
		$upper_limit	= 9999;

		//--------------------------------------
		//  Base output
		//--------------------------------------

		$out	= array(
			'done'	=> FALSE,
			'batch'	=> ee()->input->get('batch'),
			'total'	=> ee()->input->get('total')
		);

		//--------------------------------------
		//  Libraries
		//--------------------------------------

		ee()->load->library('freeform_migration');

		//--------------------------------------
		//  Validate
		//--------------------------------------
		$collections = ee()->input->get('collections');

		if ( empty( $collections ) OR
			 ee()->input->get('batch') === FALSE )
		{
			$out['error']	= TRUE;
			$out['errors']	= array( 'no_collections' => lang('no_collections') );
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Done?
		//--------------------------------------

		if ( ee()->input->get('batch') !== FALSE AND
			 ee()->input->get('total') !== FALSE AND
			 ee()->input->get('batch') >= ee()->input->get('total') )
		{
			$out['done']	= TRUE;
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Anything?
		//--------------------------------------

		$collections	= $this->actions()->pipe_split(
			urldecode(
				ee()->input->get('collections')
			)
		);

		$counts			= ee()->freeform_migration->get_collection_counts($collections);

		if (empty($counts))
		{
			$out['error']	= TRUE;
			$out['errors']	= array( 'no_collections' => lang('no_collections') );
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Do any of the submitted collections have unmigrated entries?
		//--------------------------------------

		$migrate	= FALSE;

		foreach ( $counts as $form_name => $val )
		{
			if ( ! empty( $val['unmigrated'] ) )
			{
				$migrate = TRUE;
			}
		}

		if ( empty( $migrate ) )
		{
			$out['done']	= TRUE;
			$this->send_ajax_response($out);
			exit();
		}

		//--------------------------------------
		//  Master arrays
		//--------------------------------------

		$forms			= array();
		$form_fields	= array();

		//--------------------------------------
		//  Loop and process
		//--------------------------------------

		foreach ( $counts as $form_name => $val )
		{
			//--------------------------------------
			//  For each collection, create a form
			//--------------------------------------

			$form_data = ee()->freeform_migration->create_form($form_name);

			if ( $form_data !== FALSE )
			{
				$forms[ $form_data['form_name'] ]['form_id']	= $form_data['form_id'];
				$forms[ $form_data['form_name'] ]['form_label']	= $form_data['form_label'];
			}
			else
			{
				$errors = ee()->freeform_migration->get_errors();

				if ( $errors !== FALSE )
				{
					$out['error']	= TRUE;
					$out['errors']	= $errors;
					$this->send_ajax_response($out);
					exit();
				}
			}

			//--------------------------------------
			//  For each collection, determine fields
			//--------------------------------------

			$migrate_empty_fields	= (
				$this->check_yes(ee()->input->get('migrate_empty_fields'))
			) ? 'y': 'n';

			$fields = ee()->freeform_migration->get_fields_for_collection(
				$form_name,
				$migrate_empty_fields
			);

			if ($fields !== FALSE)
			{
				$form_fields[ $form_name ]['fields']	= $fields;
			}
			else
			{
				$errors = ee()->freeform_migration->get_errors();

				if ($errors !== FALSE)
				{
					$out['error']	= TRUE;
					$out['errors']	= $errors;
					$this->send_ajax_response($out);
					exit();
				}
			}

			//--------------------------------------
			//  For each collection, create necessary fields if they don't yet exist.
			//--------------------------------------

			$created_field_ids	= array();

			if ( ! empty( $form_fields[$form_name]['fields'] ) )
			{
				foreach ( $form_fields[$form_name]['fields'] as $name => $attr )
				{
					$field_id = ee()->freeform_migration->create_field(
						$forms[$form_name]['form_id'],
						$name,
						$attr
					);

					if ($field_id !== FALSE )
					{
						$created_field_ids[]	= $field_id;
					}
					else
					{
						$errors = ee()->freeform_migration->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
				}
			}

			//--------------------------------------
			//  For each collection, create upload fields if needed.
			//--------------------------------------

			$attachment_profiles = ee()->freeform_migration->get_attachment_profiles( $form_name );

			if ($this->check_yes( ee()->input->get('migrate_attachments') ) AND
				$attachment_profiles !== FALSE )
			{
				foreach ( $attachment_profiles as $row )
				{
					$field_id = ee()->freeform_migration->create_upload_field(
						$forms[ $form_name ]['form_id'],
						$row['name'],
						$row
					);

					if ($field_id !== FALSE)
					{
						$created_field_ids[]					= $field_id;
						$upload_pref_id_map[ $row['pref_id'] ]	= array(
							'field_id'		=> $field_id,
							'field_name'	=> $row['name']
						);
					}
					else
					{
						$errors = ee()->freeform_migration->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
				}

				if ( ! empty( $upload_pref_id_map ) )
				{
					ee()->freeform_migration->set_property(
						'upload_pref_id_map',
						$upload_pref_id_map
					);
				}
			}

			//--------------------------------------
			//  Assign the fields to our form.
			//--------------------------------------

			ee()->freeform_migration->assign_fields_to_form(
				$forms[ $form_data['form_name'] ]['form_id'],
				$created_field_ids
			);

			//--------------------------------------
			//	Safeguard?
			//--------------------------------------

			if ( ee()->input->get('batch') == $upper_limit )
			{
				$this->send_ajax_response(array('done' => TRUE ));
				exit();
			}

			//--------------------------------------
			//  Pass attachments pref
			//--------------------------------------

			if ($this->check_yes( ee()->input->get('migrate_attachments') ) )
			{
				ee()->freeform_migration->set_property('migrate_attachments', TRUE);
			}

			//--------------------------------------
			//  Grab entries
			//--------------------------------------

			ee()->freeform_migration->set_property(
				'batch_limit',
				$this->migration_batch_limit
			);

			$entries = ee()->freeform_migration->get_legacy_entries($form_name);

			if ( $entries !== FALSE )
			{
				foreach ( $entries as $entry )
				{
					//--------------------------------------
					//  Insert
					//--------------------------------------

					$entry_id = ee()->freeform_migration->set_legacy_entry(
						$forms[ $form_name ]['form_id'],
						$entry
					);

					if ( $entry_id === FALSE )
					{
						$errors = ee()->freeform_migration->get_errors();

						if ( $errors !== FALSE )
						{
							$out['error']	= TRUE;
							$out['errors']	= $errors;
							$this->send_ajax_response($out);
							exit();
						}
					}
					else
					{
						$out['batch']	= $out['batch'] + 1;
					}
				}
			}
		}

		//--------------------------------------
		//  Are we done?
		//--------------------------------------

		$this->send_ajax_response($out);
		exit();
	}
	//	End migrate collections ajax


	// --------------------------------------------------------------------

	/**
	 * moderate entries
	 *
	 * almost the same as entries but with some small modifications
	 *
	 * @access	public
	 * @param 	string message lang line
	 * @return	string html output
	 */

	public function moderate_entries ( $message = '' )
	{
		return $this->entries($message, TRUE);
	}
	//END moderate_entries


	// --------------------------------------------------------------------

	/**
	 * Action from submitted entries links
	 *
	 * @access public
	 * @return string 	html output
	 */

	public function entries_action ()
	{
		$action = ee()->input->get_post('submit_action');

		if ($action == 'approve')
		{
			return $this->approve_entries();
		}
		else if ($action == 'delete')
		{
			return $this->delete_confirm_entries();
		}
		/*
		else if ($action == 'edit')
		{
			$this->edit_entries();
		}
		*/
	}
	//END entries_action


	// --------------------------------------------------------------------

	/**
	 * [edit_entry description]
	 *
	 * @access	public
	 * @return	string parsed html
	 */

	public function edit_entry ($message = '')
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND ! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		// -------------------------------------
		//	edit?
		// -------------------------------------

		$form_id	= $this->get_post_or_zero('form_id');
		$entry_id	= $this->get_post_or_zero('entry_id');

		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_entry_model');

		$form_data = $this->data->get_form_info($form_id);

		if ( ! $form_data)
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$entry_data	= array();
		$edit		= FALSE;

		if ($entry_id > 0)
		{
			$entry_data = ee()->freeform_entry_model
							  ->id($form_id)
							  ->where('entry_id', $entry_id)
							  ->get_row();

			if ($entry_data == FALSE)
			{
				return $this->actions()->full_stop(lang('invalid_entry_id'));
			}

			$edit = TRUE;
		}

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb(
			lang('forms'),
			$this->mod_link(array('method' => 'forms'))
		);

		$this->add_crumb(
			lang($edit ? 'edit_entry' : 'new_entry')  . ': ' . $form_data['form_label']
		);

		$this->set_highlight('module_forms');

		// -------------------------------------
		//	load the template library in case
		//	people get upset or something
		// -------------------------------------

		if ( ! isset(ee()->TMPL) OR ! is_object(ee()->TMPL))
		{
			ee()->load->library('template');
			$globals['TMPL'] =& ee()->template;
			ee()->TMPL =& ee()->template;
		}

		// -------------------------------------
		//	get fields
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$field_output_data = array();

		$field_loop_ids = array_keys($form_data['fields']);

		if ($form_data['field_order'] !== '')
		{
			$order_ids = $this->actions()->pipe_split($form_data['field_order']);

			if ( ! empty($order_ids))
			{
				//this makes sure that any fields in 'fields' are in the
				//order set as well. Will add missing at the end like this
				$field_loop_ids = array_merge(
					$order_ids,
					array_diff($field_loop_ids, $order_ids)
				);
			}
		}

		foreach ($field_loop_ids as $field_id)
		{
			if ( ! isset($form_data['fields'][$field_id]))
			{
				continue;
			}

			$f_data = $form_data['fields'][$field_id];

			$instance =& ee()->freeform_fields->get_field_instance(array(
				'field_id'		=> $field_id,
				'field_data'	=> $f_data,
				'form_id'		=> $form_id,
				'entry_id'		=> $entry_id,
				'edit'			=> $edit,
				'extra_settings' 	=> array(
					'entry_id'	=> $entry_id
				)
			));

			$column_name = ee()->freeform_entry_model->form_field_prefix . $field_id;

			$display_field_data = '';

			if ($edit)
			{
				if (isset($entry_data[$column_name]))
				{
					$display_field_data = $entry_data[$column_name];
				}
				else if (isset($entry_data[$f_data['field_name']]))
				{
					$display_field_data = $entry_data[$f_data['field_name']];
				}
			}

			$field_output_data[$field_id] = array(
				'field_display'		=> $instance->display_edit_cp($display_field_data),
				'field_label'		=> $f_data['field_label'],
				'field_description' => $f_data['field_description']
			);
		}

		$entry_data['screen_name'] = '';
		$entry_data['group_title'] = '';

		if (!empty($entry_data['author_id']))
		{
			$member_data =  ee()->db
								->select('group_title, screen_name')
								->from('members')
								->join('member_groups', 'members.group_id = member_groups.group_id', 'left')
								->where('member_id', $entry_data['author_id'])
								->limit(1)
								->get();

			if ($member_data->num_rows() > 0)
			{
				$entry_data['screen_name'] = $member_data->row('screen_name');
				$entry_data['group_title'] = $member_data->row('group_title');
			}
		}

		if ($entry_data['screen_name'] == '')
		{
			$entry_data['screen_name'] = lang('guest');
			$entry_data['group_title'] = lang('guest');

			$guest_data =	ee()->db
								->select('group_title')
								->from('member_groups')
								->where('group_id', 3)
								->limit(1)
								->get();

			if ($guest_data->num_rows() > 0)
			{
				$entry_data['group_title'] = $guest_data->row('group_title');
			}
		}

		$entry_data['entry_date']	= ( ! empty( $entry_data['entry_date'] ) ) ?
										$entry_data['entry_date'] :
										ee()->localize->now;
		$entry_data['entry_date']	= $this->format_cp_date($entry_data['entry_date']);
		$entry_data['edit_date']	= (!empty($entry_data['edit_date'])) ?
										$this->format_cp_date($entry_data['edit_date']) :
										lang('n_a');

		$this->cached_vars['entry_data']		= $entry_data;
		$this->cached_vars['field_output_data'] = $field_output_data;
		$this->cached_vars['form_uri']			= $this->mod_link(array(
			'method'	=> 'save_entry'
		));
		$this->cached_vars['form_id']			= $form_id;
		$this->cached_vars['entry_id']			= $entry_id;
		$this->cached_vars['statuses'] 			= $this->data->get_form_statuses();

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->load_fancybox();
		$this->cached_vars['cp_javascript'][] = 'jquery.smooth-scroll.min';
		$this->cached_vars['current_page'] = $this->view(
			'edit_entry.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END edit_entry


	// --------------------------------------------------------------------

	/**
	 * fields
	 *
	 * @access	public
	 * @param 	string message
	 * @return	string
	 */

	public function fields ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND ! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->cached_vars['new_field_link'] = $this->mod_link(array(
			'method' => 'edit_field'
		));

		$this->add_crumb( lang('fields') );

		//optional
		$this->freeform_add_right_link(lang('new_field'), $this->cached_vars['new_field_link']);

		$this->set_highlight('module_fields');

		// -------------------------------------
		//	data
		// -------------------------------------

		$this->cached_vars['fingerprint'] = isset(
			ee()->session->userdata['fingerprint']
		) ? ee()->session->userdata['fingerprint'] : 0;

		$row_limit			= $this->data->defaults['mcp_row_limit'];
		$paginate			= '';
		$row_count			= 0;
		$field_data 		= array();

		$paginate_url		= array('method' => 'fields');

		ee()->load->model('freeform_field_model');

		ee()->freeform_field_model->select(
			'field_label,
			 field_name,
			 field_type,
			 field_id,
			 field_description'
		);

		// -------------------------------------
		//	search?
		// -------------------------------------

		$field_search		= '';
		$field_search_on	= '';
		$search				= ee()->input->get_post('field_search', TRUE);

		if ($search)
		{
			$f_search_on = ee()->input->get_post('field_search_on');

			if ($f_search_on == 'field_name')
			{
				ee()->freeform_field_model->like('field_name', $search);
				$field_search_on = 'field_name';
			}
			else if ($f_search_on == 'field_label')
			{
				ee()->freeform_field_model->like('field_label', $search);
				$field_search_on = 'field_label';
			}
			else if ($f_search_on == 'field_description')
			{
				ee()->freeform_field_model->like('field_description', $search);
				$field_search_on = 'field_description';
			}
			else //if ($f_search_on == 'all')
			{
				ee()->freeform_field_model->like('field_name', $search);
				ee()->freeform_field_model->or_like('field_label', $search);
				ee()->freeform_field_model->or_like('field_description', $search);

				$field_search_on = 'all';
			}

			$field_search = $search;

			$paginate_url['field_search']		= $search;
			$paginate_url['field_search_on']	= $field_search_on;
		}

		$this->cached_vars['field_search']		= $field_search;
		$this->cached_vars['field_search_on']	= $field_search_on;

		// -------------------------------------
		//	all sites?
		// -------------------------------------

		if ( ! $this->data->show_all_sites())
		{
			ee()->freeform_field_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		ee()->freeform_field_model->order_by('field_name', 'asc');

		$total_results = ee()->freeform_field_model->count(array(), FALSE);

		$row_count = 0;

		// do we need pagination?
		if ( $total_results > $row_limit )
		{
			$row_count		= $this->get_post_or_zero('row');

			//get pagination info
			$pagination_data 	= $this->universal_pagination(array(
				'total_results'			=> $total_results,
				'limit'					=> $row_limit,
				'current_page'			=> $row_count,
				'pagination_config'		=> array('base_url' => $this->mod_link($paginate_url)),
				'query_string_segment'	=> 'row'
			));

			$paginate 		= $pagination_data['pagination_links'];

			ee()->freeform_field_model->limit(
				$row_limit,
				$pagination_data['pagination_page']
			);
		}

		$query = ee()->freeform_field_model->get();

		$count = $row_count;

		if ($query !== FALSE)
		{
			foreach ($query as $row)
			{
				$row['count']					= ++$count;
				$row['field_edit_link'] 		= $this->mod_link(array(
					'method' 		=> 'edit_field',
					'field_id'		=> $row['field_id']
				));

				$row['field_duplicate_link'] 	= $this->mod_link(array(
					'method' 		=> 'edit_field',
					'duplicate_id'	=> $row['field_id']
				));

				$row['field_delete_link'] 		= $this->mod_link(array(
					'method' 		=> 'delete_confirm_fields',
					'field_id'		=> $row['field_id']
				));

				$field_data[] 			= $row;
			}
		}

		$this->cached_vars['field_data'] 	= $field_data;
		$this->cached_vars['paginate']		= $paginate;

		// -------------------------------------
		//	ajax?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'success'		=> TRUE,
				'field_data'	=> $field_data,
				'paginate'		=> $paginate
			));
		}

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' 		=> 'delete_confirm_fields'
		));

		// -------------------------------------
		//	js
		// -------------------------------------

		ee()->cp->add_js_script(array(
			'file'		=> array('underscore', 'json2')
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'fields.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END fields


	// --------------------------------------------------------------------

	/**
	 * Edit Field
	 *
	 * @access	public
	 * @param   bool  	$modal 	is this a modal version?
	 * @return	string
	 */

	public function edit_field ($modal = FALSE)
	{
		// -------------------------------------
		//	field ID? we must be editing
		// -------------------------------------

		$field_id 		= $this->get_post_or_zero('field_id');

		$update 		= ($field_id != 0);

		$modal 			= ( ! $modal AND $this->check_yes(ee()->input->get_post('modal'))) ? TRUE : $modal;

		$this->cached_vars['modal'] 			= $modal;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb( lang('fields') , $this->base . AMP . 'method=fields');
		$this->add_crumb( lang(($update ? 'update_field' : 'new_field')) );


		//optional
		//$this->freeform_add_right_link(lang('home'), $this->base . AMP . 'method=some_method');

		$this->set_highlight('module_fields');

		// -------------------------------------
		//	default values
		// -------------------------------------

		$inputs = array(
			'field_id'				=> '0',
			'field_name'			=> '',
			'field_label'			=> '',
			'field_order'			=> '0',
			'field_type'			=> $this->data->defaults['field_type'],
			'field_length'			=> $this->data->defaults['field_length'],
			'field_description'		=> '',
			'submissions_page'		=> 'y',
			'moderation_page'		=> 'y',
			'composer_use'			=> 'y',
		);

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$this->cached_vars['edit_warning'] = FALSE;

		$field_in_forms = array();
		$incoming_settings = FALSE;


		if ($update)
		{
			$field_data = $this->data->get_field_info_by_id($field_id);

			if (empty($field_data))
			{
				$this->actions()->full_stop(lang('invalid_field_id'));
			}

			foreach ($field_data as $key => $value)
			{
				$inputs[$key] = $value;
			}

			// -------------------------------------
			//	is this change going to affect any
			//	forms that use this field?
			// -------------------------------------

			$form_info = $this->data->get_form_info_by_field_id($field_id);

			if ($form_info AND ! empty($form_info))
			{
				$this->cached_vars['edit_warning'] = TRUE;

				$form_names = array();

				foreach ($form_info as $row)
				{
					$field_in_forms[] 	= $row['form_id'];
					$form_names[] 		= $row['form_label'];
				}

				$this->cached_vars['lang_field_edit_warning'] = lang('field_edit_warning');

				$this->cached_vars['lang_field_edit_warning_desc'] = str_replace(
					'%form_names%',
					implode(', ', $form_names),
					lang('field_edit_warning_desc')
				);
			}
		}

		$this->cached_vars['form_ids'] = implode('|', array_unique($field_in_forms));

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id	= $this->get_post_or_zero('duplicate_id');
		$duplicate		= FALSE;

		$this->cached_vars['duplicated'] = FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$field_data = $this->data->get_field_info_by_id($duplicate_id);

			if ($field_data)
			{
				$duplicate = TRUE;

				foreach ($field_data as $key => $value)
				{
					if (in_array($key, array('field_id', 'field_label', 'field_name')))
					{
						continue;
					}

					$inputs[$key] = $value;
				}

				$this->cached_vars['duplicated'] 		= TRUE;
				$this->cached_vars['duplicated_from']	= $field_data['field_label'];
			}
		}

		// -------------------------------------
		//	get available field types
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$this->cached_vars['fieldtypes'] = ee()->freeform_fields->get_available_fieldtypes();

		// -------------------------------------
		//	get available forms to add this to
		// -------------------------------------

		if ( ! $this->data->show_all_sites())
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		$this->cached_vars['available_forms'] = $this->prepare_keyed_result(
			ee()->db->get('freeform_forms'),
			'form_id'
		);

		// -------------------------------------
		//	add desc and lang for field types
		// -------------------------------------

		foreach($this->cached_vars['fieldtypes'] as $fieldtype => $field_data)
		{
			//settings?
			$settings = (
				($update OR $duplicate ) AND
				$inputs['field_type'] == $fieldtype AND
				isset($inputs['settings'])
			) ? json_decode($inputs['settings'], TRUE) : array();

			//we are encoding this and decoding in JS because leaving the
			//fields intact while in storage in the html makes dupes of fields.
			//I could do some html moving around, but that would keep running
			//individual settings JS over and over again when it should
			//only be run on display.
			$this->cached_vars['fieldtypes'][$fieldtype]['settings_output'] = base64_encode(
				ee()->freeform_fields->fieldtype_display_settings_output(
					$fieldtype,
					$settings
				)
			);
		}

		if (isset($inputs['field_label']))
		{
			$inputs['field_label'] = form_prep($inputs['field_label']);
		}

		if (isset($inputs['field_description']))
		{
			$inputs['field_description'] = form_prep($inputs['field_description']);
		}

		// -------------------------------------
		//	load inputs
		// -------------------------------------

		foreach ($inputs as $key => $value)
		{
			$this->cached_vars[$key] = $value;
		}

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' 		=> 'save_field'
		));

		//----------------------------------------
		//	Load vars
		//----------------------------------------

		$this->cached_vars['lang_submit_word'] 			= lang(
			($update ? 'update_field' : 'create_field')
		);

		$this->cached_vars['prohibited_field_names'] 	= $this->data->prohibited_names;

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['cp_javascript'][] = 'edit_field_cp.min';

		$this->cached_vars['current_page'] = $this->view(
			'edit_field.html',
			NULL,
			TRUE
		);

		if ($modal)
		{
			exit($this->cached_vars['current_page']);
		}

		$this->load_fancybox();
		$this->cached_vars['cp_javascript'][] = 'jquery.smooth-scroll.min';

		return $this->ee_cp_view('index.html');
	}
	//END edit_field


	// --------------------------------------------------------------------

	/**
	 * Field Types
	 *
	 * @access	public
	 * @param	message
	 * @return	string
	 */

	public function fieldtypes ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND ! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->add_crumb( lang('fieldtypes') );

		$this->set_highlight('module_fieldtypes');

		//--------------------------------------
		//  start vars
		//--------------------------------------

		$fieldtypes = array();

		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_field_model');
		ee()->load->model('freeform_fieldtype_model');

		if ( ( $installed_fieldtypes = ee()->freeform_fieldtype_model->installed_fieldtypes() ) === FALSE )
		{
			$installed_fieldtypes	= array();
		}

		$fieldtypes 			= ee()->freeform_fields->get_installable_fieldtypes();

		// -------------------------------------
		//	missing fieldtype folders?
		// -------------------------------------

		$missing_fieldtypes		= array();

		foreach ($installed_fieldtypes as $name => $version)
		{
			if ( ! array_key_exists($name, $fieldtypes))
			{
				$missing_fieldtypes[] = $name;
			}
		}

		// -------------------------------------
		//	add urls and crap
		// -------------------------------------

		$action_url = $this->base . AMP . 'method=freeform_fieldtype_action';

		foreach ($fieldtypes as $name => $data)
		{
			$fieldtypes[$name]['installed_lang'] = lang($data['installed'] ? 'installed' : 'not_installed');

			$action = ($data['installed'] ? 'uninstall' : 'install');

			$fieldtypes[$name]['action_lang'] 	 = lang($action);
			$fieldtypes[$name]['action_url']	 = $this->mod_link(array(
				'method' 		=> 'freeform_fieldtype_action',
				'name'			=> $name,
				'action'		=> $action,
				//some other time -gf
				//'folder' 		=> base64_encode($data['folder'])
			));
		}

		$this->cached_vars['fieldtypes'] 		= $fieldtypes;

		$this->cached_vars['freeform_ft_docs_url'] = $this->data->doc_links['custom_fields'];

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'fieldtypes.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END field_types


	// --------------------------------------------------------------------

	/**
	 * notifications
	 *
	 * @access	public
	 * @param 	string 	message to output
	 * @return	string 	outputted template
	 */

	public function notifications ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND
			! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->cached_vars['new_notification_link'] = $this->mod_link(array(
			'method' 		=> 'edit_notification'
		));

		$this->add_crumb( lang('notifications') );

		$this->freeform_add_right_link(
			lang('new_notification'),
			$this->cached_vars['new_notification_link']
		);

		$this->set_highlight('module_notifications');

		// -------------------------------------
		//	data
		// -------------------------------------

		$row_limit			= $this->data->defaults['mcp_row_limit'];
		$paginate			= '';
		$row_count			= 0;
		$notification_data  = array();

		ee()->db->start_cache();

		ee()->db->order_by('notification_name', 'asc');

		if ( ! $this->data->show_all_sites())
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		ee()->db->from('freeform_notification_templates');

		ee()->db->stop_cache();

		$total_results = ee()->db->count_all_results();

		// do we need pagination?
		if ( $total_results > $row_limit )
		{
			$row_count			= $this->get_post_or_zero('row');

			$url 				= $this->mod_link(array(
				'method' 				=> 'notifications'
			));

			//get pagination info
			$pagination_data 	= $this->universal_pagination(array(
				'total_results'			=> $total_results,
				'limit'					=> $row_limit,
				'current_page'			=> $row_count,
				'pagination_config'		=> array('base_url' => $url),
				'query_string_segment'	=> 'row'
			));

			$paginate 			= $pagination_data['pagination_links'];

			ee()->db->limit($row_limit, $pagination_data['pagination_page']);
		}

		$query = ee()->db->get();

		ee()->db->flush_cache();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$row['notification_edit_link'] 		= $this->mod_link(array(
					'method' 			=> 'edit_notification',
					'notification_id'	=> $row['notification_id'],
				));

				$row['notification_duplicate_link'] = $this->mod_link(array(
					'method' 			=> 'edit_notification',
					'duplicate_id'		=> $row['notification_id'],
				));

				$row['notification_delete_link'] 	= $this->mod_link(array(
					'method' 			=> 'delete_confirm_notification',
					'notification_id'	=> $row['notification_id'],
				));

				$notification_data[] 				= $row;
			}
		}

		$this->cached_vars['notification_data'] 	= $notification_data;
		$this->cached_vars['paginate']				= $paginate;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' => 'delete_confirm_notification'
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'notifications.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END notifications


	// --------------------------------------------------------------------

	/**
	 * edit_notification
	 *
	 * @access	public
	 * @return	string
	 */

	public function edit_notification ()
	{
		// -------------------------------------
		//	notification ID? we must be editing
		// -------------------------------------

		$notification_id 	= $this->get_post_or_zero('notification_id');

		$update 			= ($notification_id != 0);

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(
			lang('notifications'),
			$this->base . AMP . 'method=notifications'
		);

		$this->add_crumb(
			lang(($update ? 'update_notification' : 'new_notification'))
		);

		$this->set_highlight('module_notifications');

		// -------------------------------------
		//	data items
		// -------------------------------------

		$inputs = array(
			'notification_id'			=> '0',
			'notification_name'			=> '',
			'notification_label'		=> '',
			'notification_description'	=> '',
			'wordwrap'					=> $this->data->defaults['wordwrap'],
			'allow_html'				=> $this->data->defaults['allow_html'],
			'from_name'					=> form_prep(ee()->config->item('webmaster_name')),
			'from_email'				=> ee()->config->item('webmaster_email'),
			'reply_to_email' 			=> ee()->config->item('webmaster_email'),
			'email_subject'				=> '',
			'template_data'				=> '',
			'include_attachments' 		=> 'n'
		);

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$this->cached_vars['edit_warning'] = FALSE;

		if ($update)
		{
			$notification_data = $this->data->get_notification_info_by_id($notification_id);

			foreach ($notification_data as $key => $value)
			{
				$inputs[$key] = form_prep($value);
			}

			// -------------------------------------
			//	is this change going to affect any
			//	forms that use this field?
			// -------------------------------------

			$form_info = $this->data->get_form_info_by_notification_id($notification_id);

			if ($form_info AND ! empty($form_info))
			{
				$this->cached_vars['edit_warning'] = TRUE;

				$form_names = array();

				foreach ($form_info as $row)
				{
					$form_names[] = $row['form_label'];
				}

				$this->cached_vars['lang_notification_edit_warning'] = str_replace(
					'%form_names%',
					implode(', ', $form_names),
					lang('notification_edit_warning')
				);
			}
		}

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id = $this->get_post_or_zero('duplicate_id');

		$this->cached_vars['duplicated'] = FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$notification_data = $this->data->get_notification_info_by_id($duplicate_id);

			if ($notification_data)
			{
				foreach ($notification_data as $key => $value)
				{
					//TODO: remove other items that dont need to be duped?

					if (in_array($key, array(
						'notification_id',
						'notification_label',
						'notification_name'
					)))
					{
						continue;
					}

					$inputs[$key] = form_prep($value);
				}

				$this->cached_vars['duplicated'] 		= TRUE;
				$this->cached_vars['duplicated_from']	= $notification_data['notification_label'];
			}
		}

		// -------------------------------------
		//	get available fields
		// -------------------------------------

		ee()->load->model('freeform_field_model');

		$this->cached_vars['available_fields']	= array();

		if ( ( $fields = ee()->freeform_field_model->get() ) !== FALSE )
		{
			$this->cached_vars['available_fields']	= $fields;
		}

		$this->cached_vars['standard_tags']		= $this->data->standard_notification_tags;

		// -------------------------------------
		//	load inputs
		// -------------------------------------

		foreach ($inputs as $key => $value)
		{
			$this->cached_vars[$key] = $value;
		}

		$this->cached_vars['form_uri'] = $this->base . AMP .
											'method=save_notification';

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['lang_submit_word'] 	= lang(
			($update ? 'update_notification' : 'create_notification')
		);

		$this->load_fancybox();
		$this->cached_vars['cp_javascript'][] = 'jquery.smooth-scroll.min';

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'edit_notification.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END edit_notification

	

	// --------------------------------------------------------------------

	/**
	 * templates
	 *
	 * @access	public
	 * @param 	string 	message to output
	 * @return	string 	outputted template
	 */

	public function templates ( $message = '' )
	{
		// -------------------------------------
		//  Messages
		// -------------------------------------

		if ($message == '' AND
			! in_array(ee()->input->get('msg'), array(FALSE, '')) )
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------------
		//	Crumbs and tab highlight
		//--------------------------------------------

		$this->cached_vars['new_template_link'] = $this->mod_link(array(
			'method' 		=> 'edit_template'
		));

		$this->add_crumb( lang('templates') );

		$this->freeform_add_right_link(
			lang('new_template'),
			$this->cached_vars['new_template_link']
		);

		$this->set_highlight('module_composer_templates');

		// -------------------------------------
		//	data
		// -------------------------------------

		$row_limit			= $this->data->defaults['mcp_row_limit'];
		$paginate			= '';
		$row_count			= 0;
		$template_data		= array();

		ee()->load->model('freeform_template_model');

		ee()->freeform_template_model->order_by('template_name', 'asc');

		if ( ! $this->data->show_all_sites())
		{
			ee()->freeform_template_model->where('site_id', ee()->config->item('site_id'));
		}

		$total_results = ee()->freeform_template_model->count(array(), FALSE);

		// do we need pagination?
		if ( $total_results > $row_limit )
		{
			$row_count			= $this->get_post_or_zero('row');

			$url 				= $this->mod_link(array(
				'method' 				=> 'templates'
			));

			//get pagination info
			$pagination_data 	= $this->universal_pagination(array(
				'total_results'			=> $total_results,
				'limit'					=> $row_limit,
				'current_page'			=> $row_count,
				'pagination_config'		=> array('base_url' => $url),
				'query_string_segment'	=> 'row'
			));

			$paginate 			= $pagination_data['pagination_links'];

			ee()->freeform_template_model->limit($row_limit, $pagination_data['pagination_page']);
		}

		$query = ee()->freeform_template_model->get();

		if ($query !== FALSE)
		{
			foreach ($query as $row)
			{
				$row['template_edit_link'] 		= $this->mod_link(array(
					'method' 			=> 'edit_template',
					'template_id'	=> $row['template_id'],
				));

				$row['template_duplicate_link'] = $this->mod_link(array(
					'method' 			=> 'edit_template',
					'duplicate_id'		=> $row['template_id'],
				));

				$row['template_delete_link'] 	= $this->mod_link(array(
					'method' 			=> 'delete_confirm_template',
					'template_id'	=> $row['template_id'],
				));

				$template_data[] 				= $row;
			}
		}

		$this->cached_vars['template_data'] 	= $template_data;
		$this->cached_vars['paginate']			= $paginate;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' => 'delete_confirm_template'
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'templates.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END templates


	// --------------------------------------------------------------------

	/**
	 * edit_template
	 *
	 * @access	public
	 * @return	string
	 */

	public function edit_template ()
	{
		// -------------------------------------
		//	template ID? we must be editing
		// -------------------------------------

		$template_id		= $this->get_post_or_zero('template_id');

		$update 			= ($template_id != 0);

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(
			lang('templates'),
			$this->base . AMP . 'method=templates'
		);

		$this->add_crumb(
			lang(($update ? 'update_template' : 'new_template'))
		);

		$this->set_highlight('module_composer_templates');

		// -------------------------------------
		//	data items
		// -------------------------------------

		$inputs = array(
			'template_id'			=> '0',
			'site_id'				=> ee()->config->item('site_id'),
			'template_name'			=> '',
			'template_label'		=> '',
			'template_description'	=> '',
			'enable_template'		=> '',
			'template_data'			=> '',
			'param_data'			=> array()
		);

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$this->cached_vars['edit_warning'] = FALSE;

		ee()->load->model('freeform_template_model');

		if ($update)
		{
			$template_data = ee()->freeform_template_model->get_row(array(
				'template_id'	=> $template_id
			));

			if ($template_data !== FALSE)
			{
				foreach ($template_data as $key => $value)
				{
					$inputs[$key] = form_prep($value);
				}

				// -------------------------------------
				//	is this change going to affect any
				//	forms that use this field?
				// -------------------------------------

				ee()->load->model('freeform_form_model');

				$form_info = ee()->freeform_form_model->get(array(
					'template_id' => $template_id
				));

				if ($form_info AND ! empty($form_info))
				{
					$this->cached_vars['edit_warning'] = TRUE;

					$form_names = array();

					foreach ($form_info as $row)
					{
						$form_names[] = $row['form_label'];
					}

					$this->cached_vars['lang_template_edit_warning'] = str_replace(
						'%form_names%',
						implode(', ', $form_names),
						lang('template_edit_warning')
					);
				}
			}
		}

		// -------------------------------------
		//	duplicating?
		// -------------------------------------

		$duplicate_id = $this->get_post_or_zero('duplicate_id');

		$this->cached_vars['duplicated'] = FALSE;

		if ( ! $update AND $duplicate_id > 0)
		{
			$template_data = ee()->freeform_template_model->get_row(array(
				'template_id'	=> $duplicate_id
			));

			if ($template_data)
			{
				foreach ($template_data as $key => $value)
				{
					//TODO: remove other items that dont need to be duped?

					if (in_array($key, array(
						'template_id',
						'template_label',
						'template_name'
					)))
					{
						continue;
					}

					$inputs[$key] = form_prep($value);
				}

				$this->cached_vars['duplicated']		= TRUE;
				$this->cached_vars['duplicated_from']	= $template_data['template_label'];
			}
		}

		// -------------------------------------
		//	load inputs
		// -------------------------------------

		if (empty($inputs['template_data']))
		{
			$inputs['template_data'] = $this->view(
				'default_composer_template.html',
				array('wrappers' => FALSE),
				TRUE
			);
		}

		// -------------------------------------
		//	make sure param data gets decoded
		//	or its default blank array
		// -------------------------------------

		if (isset($template_data['param_data']) AND
			is_string($template_data['param_data']))
		{
			$inputs['param_data'] = json_decode($template_data['param_data'], TRUE);
		}

		if ( ! is_array($inputs['param_data']))
		{
			$inputs['param_data'] = array();
		}

		// -------------------------------------
		//	send values
		// -------------------------------------

		foreach ($inputs as $key => $value)
		{
			$this->cached_vars[$key] = $value;
		}

		$this->cached_vars['form_uri']	=	$this->mod_link(array(
												'method' => 'save_template'
											));

		$this->cached_vars['update']	= $update;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['lang_submit_word'] 	= lang(
			($update ? 'update_template' : 'create_template')
		);

		$this->load_fancybox();
		$this->cached_vars['cp_javascript'][] = 'jquery.smooth-scroll.min';

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'edit_template.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END edit_template


	// --------------------------------------------------------------------

	/**
	 * permissions
	 *
	 * @access	public
	 * @return	string
	 */

	public function permissions ( $message = '' )
	{
		if ($message == '' AND ee()->input->get('msg') !== FALSE)
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(lang('permissions'));

		$this->set_highlight('module_permissions');

		// -------------------------------------
		//	permissions
		// -------------------------------------

		$permissions = $this->data->global_preference('permissions');

		if ($permissions === FALSE)
		{
			$permissions = array();
		}

		$global_permissions = (
			isset($permissions['global_permissions']) AND
			$permissions['global_permissions'] == TRUE
		);

		$site_id = ($global_permissions) ? 0 : ee()->config->item('site_id');

		$this->cached_vars['global_permissions'] = $global_permissions;

		$this->cached_vars['permissions'] = isset($permissions[$site_id]) ? $permissions[$site_id] : array();

		// -------------------------------------
		//	menu items
		// -------------------------------------

		$menu_items = array();

		foreach ($this->cached_vars['module_menu'] as $menu_name => $menu_data)
		{
			if ($menu_name == 'module_documentation')
			{
				continue;
			}

			$menu_items[] = str_replace('module_', '', $menu_name);
		}

		$this->cached_vars['menu_items'] = $menu_items;

		// -------------------------------------
		//	member groups
		// -------------------------------------

		$m_groups =	ee()->db
						->from('member_groups')
						->select('group_id, group_title')
						->where_not_in('group_id', array(1, 2, 3, 4))
						->get();

		$member_groups = array();

		if ($m_groups->num_rows() > 0)
		{
			foreach ($m_groups->result_array() as $row)
			{
				$member_groups[$row['group_id']] = $row['group_title'];
			}
		}

		$this->cached_vars['member_groups'] = $member_groups;

		// -------------------------------------
		//	other vars
		// -------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method'	=> 'save_permissions'
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'permissions.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END permissions


	

	// --------------------------------------------------------------------

	/**
	 * preferences
	 *
	 * @access	public
	 * @return	string
	 */

	public function preferences ( $message = '' )
	{
		if ($message == '' AND ee()->input->get('msg') !== FALSE)
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(lang('preferences'));

		$this->set_highlight('module_preferences');

		// -------------------------------------
		//	global prefs
		// -------------------------------------

		$this->cached_vars['msm_enabled'] = $msm_enabled = $this->data->msm_enabled;

		$is_admin = (ee()->session->userdata('group_id') == 1);

		$this->cached_vars['show_global_prefs'] = ($is_admin AND $msm_enabled);

		$global_pref_data		= array();

		$global_prefs 			= $this->data->get_global_module_preferences();

		$default_global_prefs	= array_keys($this->data->default_global_preferences);

		//dynamically get prefs and lang so we can just add them to defaults
		foreach( $global_prefs as $key => $value )
		{
			//this skips things that don't need to be shown on this page
			//so we can use the prefs table for other areas of the addon
			if ( ! in_array($key, $default_global_prefs))
			{
				continue;
			}

			$pref = array();

			$pref['name']		= $key;
			$pref['lang_label']	= lang($key);
			$key_desc 			= $key . '_desc';
			$pref['lang_desc']	= (lang($key_desc) == $key_desc) ? '' : lang($key_desc);
			$pref['value']		= form_prep($value);
			$pref['type']		= $this->data->default_global_preferences[$key]['type'];

			$global_pref_data[]		= $pref;
		}

		$this->cached_vars['global_pref_data']	= $global_pref_data;

		// -------------------------------------
		//	these two will only be used if MSM
		// 	is enabled, but setting them
		// 	anyway to avoid potential PHP errors
		// -------------------------------------

		$prefs_all_sites 	=  ! $this->check_no(
			$this->data->global_preference('prefs_all_sites')
		);

		$this->cached_vars['lang_site_prefs_for_site'] = (
			lang('site_prefs_for') . ' ' . (
				($prefs_all_sites) ?
					lang('all_sites') :
					ee()->config->item('site_label')
			)
		);

		//--------------------------------------
		//  per site prefs
		//--------------------------------------

		$pref_data		= array();

		$prefs 			= $this->data->get_module_preferences();

		$default_prefs 	= array_keys($this->data->default_preferences);

		//dynamically get prefs and lang so we can just add them to defaults
		foreach( $prefs as $key => $value )
		{
			//this skips things that don't need to be shown on this page
			//so we can use the prefs table for other areas of the addon
			if ( ! in_array($key, $default_prefs))
			{
				continue;
			}

			//admin only pref?
			//MSM pref and no MSM?
			if (
				(ee()->session->userdata('group_id') != 1 AND
				in_array($key, $this->data->admin_only_prefs)) OR
				( ! $msm_enabled AND
				  in_array($key, $this->data->msm_only_prefs))
			)
			{
				continue;
			}

			$pref = array();

			$pref['name']		= $key;
			$pref['lang_label']	= lang($key);
			$key_desc 			= $key . '_desc';
			$pref['lang_desc']	= (lang($key_desc) == $key_desc) ? '' : lang($key_desc);
			$pref['value']		= form_prep($value);
			$pref['type']		= $this->data->default_preferences[$key]['type'];

			$pref_data[]		= $pref;
		}

		$this->cached_vars['pref_data']	= $pref_data;

		//	----------------------------------------
		//	Load vars
		//	----------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' => 'save_preferences'
		));

		// --------------------------------------------
		//  Load page
		// --------------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'preferences.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END preferences


	// --------------------------------------------------------------------

	/**
	 * delete confirm page abstractor
	 *
	 * @access	private
	 * @param 	string 	method you want to post data to after confirm
	 * @param 	array 	all of the values you want to carry over to post
	 * @param 	string 	the lang line of the warning message to display
	 * @param 	string 	the lang line of the submit button for confirm
	 * @param   bool    $message_use_lang use the lang wrapper for the message?
	 * @return	string
	 */

	private function delete_confirm ($method, 		$hidden_values,
									 $message_line, $submit_line = 'delete',
									 $message_use_lang = TRUE)
	{
		$this->cached_vars = array_merge($this->cached_vars, array(
			'hidden_values' => $hidden_values,
			'lang_message' 	=> ($message_use_lang ? lang($message_line) : $message_line),
			'lang_submit' 	=> lang($submit_line),
			'form_url'		=> $this->mod_link(array('method' => $method))
		));

		$this->cached_vars['current_page'] 	= $this->view(
			'delete_confirm.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}
	//END delete_confirm


	//--------------------------------------------------------------------
	// 	end views
	//--------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Clean up inputted paragraph html
	 *
	 * @access	public
	 * @param	string $string	string to clean
	 * @return	mixed			string if html or json if not
	 */

	public function clean_paragraph_html ($string = '', $allow_ajax = TRUE)
	{
		if ( ! $string OR trim($string == ''))
		{
			$string = ee()->input->get_post('clean_string');

			if ( $string === FALSE OR trim($string) === '')
			{
				$string = '';
			}
		}

		$allowed_tags	= '<' . implode('><', $this->data->allowed_html_tags) . '>';

		$string			= strip_tags($string, $allowed_tags);

		$string			= ee()->security->xss_clean($string);

		return $string;
	}
	//END clean_paragraph_html


	// --------------------------------------------------------------------

	/**
	 * Fieldtype Actions
	 *
	 * this installs or uninstalles depending on the sent action
	 * this will redirect no matter what
	 *
	 * @access	public
	 * @return	null
	 */

	public function freeform_fieldtype_action ()
	{
		$return_url = array('method' => 'fieldtypes');
		$name 		= ee()->input->get_post('name', TRUE);
		$action 	= ee()->input->get_post('action');

		if ($name AND $action)
		{
			ee()->load->library('freeform_fields');

			if ($action === 'install')
			{
				if (ee()->freeform_fields->install_fieldtype($name))
				{
					$return_url['msg'] = 'fieldtype_installed';
				}
				else
				{
					return $this->actions()->full_stop(lang('fieldtype_install_error'));
				}
			}
			else if ($action === 'uninstall')
			{
				$uninstall = $this->uninstall_confirm_fieldtype($name);

				//if its not a boolean true, its a confirmation
				if ($uninstall !== TRUE)
				{
					return $uninstall;
				}
				else
				{
					$return_url['msg'] = 'fieldtype_uninstalled';
				}
			}
		}

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'success' 	=> TRUE,
				'message' 	=> lang('fieldtype_uninstalled')
			));
		}
		else
		{
			ee()->functions->redirect($this->mod_link($return_url));
		}
	}
	//END freeform_fieldtype_action


	// --------------------------------------------------------------------

	/**
	 * save field layout
	 *
	 * ajax called method for saving field layout in the entries screen
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_field_layout ()
	{
		ee()->load->library('freeform_forms');
		ee()->load->model('freeform_form_model');

		$save_for		= ee()->input->get_post('save_for', TRUE);
		$shown_fields	= ee()->input->get_post('shown_fields', TRUE);
		$form_id		= $this->get_post_or_zero('form_id');
		$form_data		= $this->data->get_form_info($form_id);

		// -------------------------------------
		//	valid
		// -------------------------------------

		if (
			! $this->is_ajax_request() OR
			! is_array($save_for) OR
			! is_array($shown_fields) OR
			! $form_data
		)
		{
			return $this->send_ajax_response(array(
				'success' 	=> FALSE,
				'message' 	=> lang('invalid_input')
			));
		}

		// -------------------------------------
		//	permissions?
		// -------------------------------------

		//if (ee()->session->userdata('group_id') != 1 AND
		//	! $this->check_yes($this->preference('allow_user_field_layout'))
		//)
		//{
		//	return $this->send_ajax_response(array(
		//		'success' 	=> FALSE,
		//		'message' 	=> lang('invalid_permissions')
		//	));
		//}

		// -------------------------------------
		//	save
		// -------------------------------------

		$field_layout_prefs = $this->preference('field_layout_prefs');

		$original_prefs 	= (
			is_array($field_layout_prefs) ?
				$field_layout_prefs :
				array()
		);


		// -------------------------------------
		//	who is it for?
		// -------------------------------------

		$for 				= array();

		foreach ($save_for as $item)
		{
			//if this is for everyone, we can stop
			if (in_array($item, array('just_me', 'everyone')))
			{
				$for = $item;
				break;
			}

			if ($this->is_positive_intlike($item))
			{
				$for[] = $item;
			}
		}

		// -------------------------------------
		//	what do they want to see?
		// -------------------------------------

		$standard_columns = $this->get_standard_column_names();
		$possible_columns = $standard_columns;

		//build possible columns
		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$possible_columns[] = $field_id;
		}

		$data 			 = array();

		$prefix 		 = ee()->freeform_form_model->form_field_prefix;

		//check for field validity, no funny business
		foreach ($shown_fields as $field_name)
		{
			$field_id = str_replace($prefix, '', $field_name);

			if (in_array($field_name, $standard_columns))
			{
				$data[] = $field_name;

				unset(
					$possible_columns[
						array_search(
							$field_name,
							$possible_columns
						)
					]
				);
			}
			else if (in_array($field_id , array_keys($form_data['fields'])))
			{
				$data[] = $field_id;

				unset(
					$possible_columns[
						array_search(
							$field_id,
							$possible_columns
						)
					]
				);
			}
		}

		//removes holes
		sort($possible_columns);

		// -------------------------------------
		//	insert the data per group or all
		// -------------------------------------

		$settings = array(
			'visible' 	=> $data,
			'hidden' 	=> $possible_columns
		);

		if ($for == 'just_me')
		{
			$id = ee()->session->userdata('member_id');

			$original_prefs['entry_layout_prefs']['member'][$id] = $settings;
		}
		else if ($for == 'everyone')
		{
			$original_prefs['entry_layout_prefs']['all']['visible'] = $settings;
		}
		else
		{
			foreach ($for as $who)
			{
				$original_prefs['entry_layout_prefs']['group'][$who]['visible'] = $settings;
			}
		}

		// -------------------------------------
		//	save
		// -------------------------------------

		$this->data->set_module_preferences(array(
			'field_layout_prefs' => json_encode($original_prefs)
		));

		// -------------------------------------
		//	success!
		// -------------------------------------

		//TODO test for ajax request or redirect back
		//don't want JS erorrs preventing this from
		//working
		$this->send_ajax_response(array(
			'success' 		=> TRUE,
			'message' 		=> lang('layout_saved'),
			'update_fields' => array()
		));

		//prevent EE CP default spit out
		exit();
	}
	//END save_field_layout


	// --------------------------------------------------------------------

	/**
	 * save_form
	 *
	 * @access	public
	 * @return	null
	 */

	public function save_form ()
	{
		// -------------------------------------
		//	form ID? we must be editing
		// -------------------------------------

		$form_id		= $this->get_post_or_zero('form_id');

		$update			= ($form_id != 0);

		// -------------------------------------
		//	default status
		// -------------------------------------

		$default_status	= ee()->input->get_post('default_status', TRUE);

		$default_status	= ($default_status AND trim($default_status) != '') ?
							$default_status :
							$this->data->defaults['default_form_status'];

		// -------------------------------------
		//	composer return?
		// -------------------------------------

		$do_composer			= (FREEFORM_PRO AND ee()->input->get_post('ret') == 'composer');

		$composer_save_finish	= (FREEFORM_PRO AND ee()->input->get_post('ret') == 'composer_save_finish');

		if ($composer_save_finish)
		{
			$do_composer = TRUE;
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors			= array();

		// -------------------------------------
		//	field name
		// -------------------------------------

		$form_name		= ee()->input->get_post('form_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $form_name OR ! trim($form_name))
		{
			$errors['form_name'] = lang('form_name_required');
		}
		else
		{
			$form_name = strtolower(trim($form_name));

			if ( in_array($form_name, $this->data->prohibited_names ) )
			{
				$errors['form_name'] = str_replace(
					'%name%',
					$form_name,
					lang('reserved_form_name')
				);
			}

			//if the form_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $form_name) OR
				is_numeric($form_name))
			{
				$errors['form_name'] = lang('form_name_can_only_contain');
			}

			ee()->load->model('freeform_form_model');

			//get dupe from field names
			$dupe_data = ee()->freeform_form_model->get_row(array(
				'form_name' => $form_name
			));

			//if we are updating, we don't want to error on the same field id
			if ( ! empty($dupe_data) AND
				! ($update AND $dupe_data['form_id'] == $form_id))
			{
				$errors['form_name'] = str_replace(
					'%name%',
					$form_name,
					lang('form_name_exists')
				);
			}
		}

		// -------------------------------------
		//	form label
		// -------------------------------------

		$form_label = ee()->input->get_post('form_label', TRUE);

		if ( ! $form_label OR ! trim($form_label) )
		{
			$errors['form_label'] = lang('form_label_required');
		}

		// -------------------------------------
		//	admin notification email
		// -------------------------------------

		$admin_notification_email = ee()->input->get_post('admin_notification_email', TRUE);

		if ($admin_notification_email AND
			trim($admin_notification_email) != '')
		{
			ee()->load->helper('email');

			$emails = preg_split(
				'/(\s+)?\,(\s+)?/',
				$admin_notification_email,
				-1,
				PREG_SPLIT_NO_EMPTY
			);

			$errors['admin_notification_email'] = array();

			foreach ($emails as $key => $email)
			{
				$emails[$key] = trim($email);

				if ( ! valid_email($email))
				{
					$errors['admin_notification_email'][] = str_replace('%email%', $email, lang('non_valid_email'));
				}
			}

			if (empty($errors['admin_notification_email']))
			{
				unset($errors['admin_notification_email']);
			}

			$admin_notification_email = implode('|', $emails);
		}
		else
		{
			$admin_notification_email = '';
		}

		// -------------------------------------
		//	user email field
		// -------------------------------------

		$user_email_field = ee()->input->get_post('user_email_field');

		ee()->load->model('freeform_field_model');

		$field_ids = ee()->freeform_field_model->key('field_id', 'field_id')->get();

		if ($user_email_field AND
			$user_email_field !== '--' AND
			trim($user_email_field) !== '' AND
			! in_array($user_email_field, $field_ids ))
		{
			$errors['user_email_field'] = lang('invalid_user_email_field');
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->actions()->full_stop($errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			exit();
		}

		// -------------------------------------
		//	field ids
		// -------------------------------------

		$field_ids = array_filter(
			$this->actions()->pipe_split(
				ee()->input->get_post('field_ids', TRUE)
			),
			array($this, 'is_positive_intlike')
		);

		$sorted_field_ids = $field_ids;

		sort($sorted_field_ids);

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data = array(
			'form_name'					=> strip_tags($form_name),
			'form_label'				=> strip_tags($form_label),
			'default_status'			=> $default_status,
			'user_notification_id'		=> $this->get_post_or_zero('user_notification_id'),
			'admin_notification_id'		=> $this->get_post_or_zero('admin_notification_id'),
			'admin_notification_email'	=> $admin_notification_email,
			'form_description'			=> strip_tags(ee()->input->get_post('form_description', TRUE)),
			'author_id' 				=> ee()->session->userdata('member_id'),
			'field_ids'					=> implode('|', $sorted_field_ids),
			'field_order'				=> implode('|', $field_ids),
			'notify_admin'				=> (
				(ee()->input->get_post('notify_admin') == 'y') ? 'y' : 'n'
			),
			'notify_user'				=> (
				(ee()->input->get_post('notify_user') == 'y') ? 'y' : 'n'
			),
			'user_email_field' 			=> $user_email_field,
		);

		//load the forms model if its not been already
		ee()->load->library('freeform_forms');

		if ($do_composer)
		{
			unset($data['field_ids']);
			unset($data['field_order']);
		}

		if ($update)
		{
			unset($data['author_id']);

			if ( ! $do_composer)
			{
				$data['composer_id'] = 0;
			}

			ee()->freeform_forms->update_form($form_id, $data);
		}
		else
		{
			//we don't want this running on update, will only happen for dupes
			$composer_id = $this->get_post_or_zero('composer_id');

			//this is a dupe and they want composer to dupe too?
			if ($do_composer AND $composer_id > 0)
			{
				ee()->load->model('freeform_composer_model');

				$composer_data = ee()->freeform_composer_model
									 ->select('composer_data')
									 ->where('composer_id', $composer_id)
									 ->get_row();

				if ($composer_data !== FALSE)
				{
					$data['composer_id'] = ee()->freeform_composer_model->insert(
						array(
							'composer_data' => $composer_data['composer_data'],
							'site_id'		=> ee()->config->item('site_id')
						)
					);
				}
			}

			$form_id = ee()->freeform_forms->create_form($data);
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		if ( ! $composer_save_finish AND $do_composer)
		{
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'form_composer',
				'form_id' 	=> $form_id,
				'msg' 		=> 'edit_form_success'
			)));
		}
		//'save and finish, default'
		else
		{
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'forms',
				'msg' 		=> 'edit_form_success'
			)));
		}
	}
	//END save_form


	// --------------------------------------------------------------------

	/**
	 * Save Entry
	 *
	 * @access	public
	 * @return	null 	redirect
	 */

	public function save_entry ()
	{
		// -------------------------------------
		//	edit?
		// -------------------------------------

		$form_id	= $this->get_post_or_zero('form_id');
		$entry_id	= $this->get_post_or_zero('entry_id');

		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_entry_model');
		ee()->load->library('freeform_forms');
		ee()->load->library('freeform_fields');

		$form_data = $this->data->get_form_info($form_id);

		// -------------------------------------
		//	valid form id
		// -------------------------------------

		if ( ! $form_data)
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$previous_inputs 	= array();

		if ( $entry_id > 0)
		{
			$entry_data = ee()->freeform_entry_model
							  ->id($form_id)
							  ->where('entry_id', $entry_id)
							  ->get_row();

			if ( ! $entry_data)
			{
				return $this->actions()->full_stop(lang('invalid_entry_id'));
			}

			$previous_inputs = $entry_data;
		}

		// -------------------------------------
		//	form data
		// -------------------------------------

		$field_labels 	= array();
		$valid_fields 	= array();

		foreach ( $form_data['fields'] as $row)
		{
			$field_labels[$row['field_name']] 	= $row['field_label'];
			$valid_fields[] 					= $row['field_name'];
		}

		// -------------------------------------
		//	is this an edit? entry_id
		// -------------------------------------

		$edit 			= ($entry_id AND $entry_id != 0);

		// -------------------------------------
		//	for hooks
		// -------------------------------------

		$this->edit			= $edit;
		$this->multipage	= FALSE;
		$this->last_page	= TRUE;

		// -------------------------------------
		//	prevalidate hook
		// -------------------------------------

		$errors				= array();
		//to assist backward compat
		$this->field_errors	= array();

		if (ee()->extensions->active_hook('freeform_module_validate_begin') === TRUE)
		{
			$backup_errors = $errors;

			$errors = ee()->extensions->universal_call(
				'freeform_module_validate_begin',
				$errors,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($errors) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$errors = $backup_errors;
			}
		}

		// -------------------------------------
		//	validate
		// -------------------------------------

		$field_input_data	= array();

		$field_list			= array();

		// -------------------------------------
		//	status?
		// -------------------------------------

		$available_statuses	= $this->data->get_form_statuses();

		$status = ee()->input->get_post('status');

		if ( ! array_key_exists($status, $available_statuses))
		{
			$field_input_data['status'] = $this->data->defaults['default_form_status'];
		}
		else
		{
			$field_input_data['status'] = $status;
		}

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$field_list[$field_data['field_name']] = $field_data['field_label'];

			$field_post = ee()->input->get_post($field_data['field_name']);

			//if it's not even in $_POST or $_GET, lets skip input
			//unless its an uploaded file, then we'll send false anyway
			//because its fieldtype will handle the rest of that work
			if ($field_post !== FALSE OR
				isset($_FILES[$field_data['field_name']]))
			{
				$field_input_data[$field_data['field_name']] = $field_post;
			}
		}

		//form fields do thier own validation,
		//so lets just get results! (sexy results?)
		$this->field_errors = array_merge(
			$this->field_errors,
			ee()->freeform_fields->validate(
				$form_id,
				$field_input_data
			)
		);

		// -------------------------------------
		//	post validate hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_validate_end') === TRUE)
		{
			$backup_errors = $errors;

			$errors = ee()->extensions->universal_call(
				'freeform_module_validate_end',
				$errors,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($errors) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$errors = $backup_errors;
			}
		}

		$errors = array_merge($errors, $this->field_errors);

		// -------------------------------------
		//	halt on errors
		// -------------------------------------

		if (count($errors) > 0)
		{
			$this->actions()->full_stop($errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			if ($this->is_ajax_request())
			{
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			exit();
		}

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_begin') === TRUE)
		{
			$backup_field_input_data = $field_input_data;

			$field_input_data = ee()->extensions->universal_call(
				'freeform_module_insert_begin',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( ! is_array($field_input_data) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$field_input_data = $backup_field_input_data;
			}
		}

		// -------------------------------------
		//	insert/update data into db
		// -------------------------------------

		if ($edit)
		{
			ee()->freeform_forms->update_entry(
				$form_id,
				$entry_id,
				$field_input_data
			);
		}
		else
		{
			$entry_id = ee()->freeform_forms->insert_new_entry(
				$form_id,
				$field_input_data
			);
		}

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_end') === TRUE)
		{
			ee()->extensions->universal_call(
				'freeform_module_insert_end',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		$success_line = ($edit) ? 'edit_entry_success' : 'new_entry_success';

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'form_id'	=> $form_id,
				'entry_id'	=> $entry_id,
				'message'	=> lang($success_line),
				'success'	=> TRUE
			));
		}
		//'save and finish, default'
		else
		{
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'entries',
				'form_id'	=> $form_id,
				'msg' 		=> $success_line
			)));
		}
	}
	//END edit_entry

	

	// --------------------------------------------------------------------

	/**
	 * Save composer data
	 *
	 * @access public
	 * @return null		exits with an ajax response or redirects after save
	 */

	public function save_composer_data ()
	{
		$form_id		= $this->get_post_or_zero('form_id');
		$template_id	= $this->get_post_or_zero('template_id');
		$composer_data	= ee()->input->get_post('composer_data');
		$preview		= $this->check_yes(ee()->input->get_post('preview')) ? 'y' : 'n';

		if ( ! $this->data->is_valid_form_id($form_id))
		{
			$this->actions()->full_stop(lang('invalid_form_id'));
		}

		if ( ! is_string($composer_data))
		{
			$this->actions()->full_stop(lang('invalid_composer_data'));
		}

		// -------------------------------------
		//	make sure json is valid
		// -------------------------------------

		$composer_data_array = $this->json_decode($composer_data, TRUE);

		if ( ! is_array($composer_data_array) OR ! isset($composer_data_array['rows']))
		{
			$this->actions()->full_stop(lang('invalid_composer_data'));
		}

		// -------------------------------------
		//	clean paragraph
		// -------------------------------------

		foreach ($composer_data_array['rows'] as $rk => $row)
		{
			//page break isn't a row
			if ( ! is_array($row))
			{
				continue;
			}

			foreach ($row as $ck => $column)
			{
				foreach ($column as $fk => $field)
				{
					if ($field['type'] == 'nonfield_paragraph' AND
						trim($field['html']) !== '')
					{
						$composer_data_array['rows'][$rk][$ck][$fk]['html'] = (
							$this->clean_paragraph_html($field['html'])
						);
					}
					else if ($field['type'] == 'nonfield_submit' AND
						trim($field['html']) !== '')
					{
						$composer_data_array['rows'][$rk][$ck][$fk]['html'] = (
							$this->clean_paragraph_html($field['html'])
						);
					}
				}
			}
		}

		$composer_fields	= implode('|', $composer_data_array['fields']);
		$composer_data		= $this->json_encode($composer_data_array);

		// -------------------------------------
		//	form data
		// -------------------------------------

		$form_data = $this->data->get_form_info($form_id);

		// -------------------------------------
		//	start data
		// -------------------------------------

		ee()->load->model('freeform_composer_model');

		//update?
		if ($preview == 'n' AND $form_data['composer_id'] != 0 AND
			ee()->freeform_composer_model->count(array(
				'composer_id' => $form_data['composer_id']
			)) != 0)
		{
			$composer_id = $form_data['composer_id'];

			ee()->freeform_composer_model->update(
				array(
					'composer_data'	=> $composer_data,
					'preview'		=> $preview
				),
				array('composer_id' => $composer_id)
			);
		}
		//insert
		else
		{
			$composer_id = ee()->freeform_composer_model->insert(array(
				'composer_data'	=> $composer_data,
				'preview'		=> $preview
			));

			ee()->freeform_composer_model->clean();
		}

		// -------------------------------------
		//	update form
		// -------------------------------------

		$form_update_data = array();

		if ($form_data['template_id'] != $template_id )
		{
			$form_update_data['template_id'] = $template_id;
		}

		if ($preview == 'n' AND $form_data['composer_id'] != $composer_id)
		{
			$form_update_data['composer_id'] = $composer_id;
		}

		if ( ! empty($form_update_data))
		{
			ee()->load->model('freeform_form_model');

			ee()->freeform_form_model->update(
				$form_update_data,
				array('form_id' => $form_id)
			);
		}

		// -------------------------------------
		//	add fields to form
		// -------------------------------------

		if ($preview == 'n' )
		{
			ee()->load->library('freeform_forms');

			ee()->freeform_forms->add_field_to_form($form_id, $composer_fields);
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			return $this->send_ajax_response(array(
				'success'		=> TRUE,
				'message'		=> lang('composer_data_saved'),
				'composerId'	=> $composer_id,
			));
		}
		else
		{
			ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'index',
				'msg'		=> 'composer_data_saved'
			)));
		}
	}
	//END save_composer_data

	

	// --------------------------------------------------------------------

	/**
	 * approve entries
	 *
	 * accepts ajax call to approve entries
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @param 	mixed 	array or int of entry ids to approve
	 * @return	string
	 */

	public function approve_entries ($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	valid form id?
		// -------------------------------------

		if ( ! $form_id OR $form_id <= 0)
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->actions()->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->actions()->full_stop(lang('invalid_entry_id'));
		}

		// -------------------------------------
		//	approve!
		// -------------------------------------

		$updates = array();

		foreach($entry_ids as $entry_id)
		{
			$updates[] = array(
				'entry_id' 	=> $entry_id,
				'status' 	=> 'open'
			);
		}

		ee()->load->model('freeform_form_model');

		ee()->db->update_batch(
			ee()->freeform_form_model->table_name($form_id),
			$updates,
			'entry_id'
		);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE
			));
			exit();
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method :
							'moderate_entries';

			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> $method,
				'form_id'	=> $form_id,
				'msg' 		=> 'entries_approved'
			)));
		}
	}
	//END approve_entry


	// --------------------------------------------------------------------

	/**
	 * get/post form_id
	 *
	 * gets and validates the current form_id possibly passed
	 *
	 * @access	private
	 * @param 	bool 	validate form_id
	 * @return	mixed  	integer of passed in form_id or bool false
	 */

	private function get_post_form_id ($validate = TRUE)
	{
		$form_id = $this->get_post_or_zero('form_id');

		if ($form_id == 0 OR
			($validate AND
			 ! $this->data->is_valid_form_id($form_id))
		)
		{
			return FALSE;
		}

		return $form_id;
	}
	//ENd get_post_form_id


	// --------------------------------------------------------------------

	/**
	 * get/post entry_ids
	 *
	 * gets and validates the current entry possibly passed
	 *
	 * @access	private
	 * @return	mixed  	array of passed in entry_ids or bool false
	 */

	private function get_post_entry_ids ()
	{
		$entry_ids = ee()->input->get_post('entry_ids');

		if ( ! is_array($entry_ids) AND
			 ! $this->is_positive_intlike($entry_ids))
		{
			return FALSE;
		}

		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		//clean and validate each as int
		$entry_ids = array_filter($entry_ids, array($this, 'is_positive_intlike'));

		if (empty($entry_ids))
		{
			return FALSE;
		}

		return $entry_ids;
	}
	//END get_post_entry_ids


	// --------------------------------------------------------------------

	/**
	 * delete_confirm_entries
	 *
	 * accepts ajax call to delete entry
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @param 	mixed 	array or int of entry ids to delete
	 * @return	string
	 */

	public function delete_confirm_entries ($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	ajax requests should be doing front
		//  end delete confirm. This also handles
		// 	the ajax errors properly
		// -------------------------------------

		if ( $this->is_ajax_request())
		{
			return $this->delete_entries();
		}

		// -------------------------------------
		//	form id?
		// -------------------------------------

		if ( ! $form_id OR $form_id <= 0)
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->show_error(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->show_error(lang('invalid_entry_id'));
		}

		// -------------------------------------
		//	return method?
		// -------------------------------------

		$return_method = ee()->input->get_post('return_method');

		$return_method = ($return_method AND
						  is_callable(array($this, $return_method))) ?
							$return_method :
							'entries';

		// -------------------------------------
		//	confirmation page
		// -------------------------------------

		return $this->delete_confirm(
			'delete_entries',
			array(
				'form_id' 		=> $form_id,
				'entry_ids' 	=> $entry_ids,
				'return_method' => $return_method
			),
			'confirm_delete_entries'
		);
	}
	//END delete_confirm_entries


	// --------------------------------------------------------------------

	/**
	 * delete entries
	 *
	 * accepts ajax call to delete entry
	 * or can be called via another view function on post
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @param 	mixed 	array or int of entry ids to delete
	 * @return	string
	 */

	public function delete_entries ($form_id = 0, $entry_ids = array())
	{
		// -------------------------------------
		//	valid form id?
		// -------------------------------------

		if ( ! $this->is_positive_intlike($form_id))
		{
			$form_id = $this->get_post_form_id();
		}

		if ( ! $form_id)
		{
			$this->actions()->full_stop(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! $entry_ids OR empty($entry_ids) )
		{
			$entry_ids = $this->get_post_entry_ids();
		}

		//check
		if ( ! $entry_ids)
		{
			$this->actions()->full_stop(lang('invalid_entry_id'));
		}

		ee()->load->library('freeform_forms');
		$success = ee()->freeform_forms->delete_entries($form_id, $entry_ids);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => $success
			));
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method : 'entries';

			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> $method,
				'form_id'	=> $form_id,
				'msg' 		=> 'entries_deleted'
			)));
		}
	}
	//END delete_entries


	// --------------------------------------------------------------------

	/**
	 * Confirm Delete Fields
	 *
	 * @access public
	 * @return html
	 */

	public function delete_confirm_fields ()
	{
		//the following fields will be deleted
		//the following forms will be affected
		//they contain the forms..

		$field_ids = ee()->input->get_post('field_id', TRUE);

		if ( ! is_array($field_ids) AND
			 ! $this->is_positive_intlike($field_ids) )
		{
			$this->actions()->full_stop(lang('no_field_ids_submitted'));
		}

		//already checked for numeric :p
		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		$delete_field_confirmation = '';

		$clean_field_ids = array();

		foreach ($field_ids as $field_id)
		{
			if ($this->is_positive_intlike($field_id))
			{
				$clean_field_ids[] = $field_id;
			}
		}

		if (empty($clean_field_ids))
		{
			$this->actions()->full_stop(lang('no_field_ids_submitted'));
		}

		// -------------------------------------
		//	build a list of forms affected by fields
		// -------------------------------------

		ee()->db->where_in('field_id', $clean_field_ids);

		$all_field_data 			= ee()->db->get('freeform_fields');

		$delete_field_confirmation 	= lang('delete_field_confirmation');

		$extra_form_data 			= '';

		foreach ($all_field_data->result_array() as $row)
		{
			//this doesn't get field data, so we had to above;
			$field_form_data = $this->data->get_form_info_by_field_id($row['field_id']);

			// -------------------------------------
			//	get each form affected by each field listed
			//	and show the user what forms will be affected
			// -------------------------------------

			if ( $field_form_data !== FALSE )
			{
				$freeform_affected = array();

				foreach ($field_form_data as $form_id => $form_data)
				{
					$freeform_affected[] = $form_data['form_label'];
				}

				$extra_form_data .= '<p>' .
										'<strong>' .
											$row['field_label'] .
										'</strong>: ' .
										implode(', ', $freeform_affected) .
									'</p>';
			}
		}

		//if we have anything, add some extra warnings
		if ($extra_form_data != '')
		{
			$delete_field_confirmation .= 	'<p>' .
												lang('freeform_will_lose_data') .
											'</p>' .
											$extra_form_data;
		}

		return $this->delete_confirm(
			'delete_fields',
			array('field_id' => $clean_field_ids),
			$delete_field_confirmation,
			'delete',
			FALSE
		);
	}
	//END delete_confirm_fields

	// --------------------------------------------------------------------

	/**
	 * utilities
	 *
	 * @access	public
	 * @return	string
	 */

	public function utilities ( $message = '' )
	{
		if ($message == '' AND ee()->input->get('msg') !== FALSE)
		{
			$message = lang(ee()->input->get('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  Title and Crumbs
		//--------------------------------------

		$this->add_crumb(lang('utilities'));

		$this->set_highlight('module_utilities');

		//--------------------------------------
		//  Counts
		//--------------------------------------

		ee()->load->library('freeform_migration');

		$this->cached_vars['counts']	= ee()->freeform_migration->get_collection_counts();

		// $test	= ee()->freeform_migration->create_upload_field( 1, 'goff', array( 'file_upload_location' => '1' ) );

		// print_r( $test ); print_r( ee()->freeform_migration->get_errors() );

		//--------------------------------------
		//  File upload field installed?
		//--------------------------------------

		$this->cached_vars['file_upload_installed']	= ee()->freeform_migration->get_field_type_installed('file_upload');

		$query	= ee()->db->query( "SELECT * FROM exp_freeform_fields WHERE field_type = 'file_upload'" );

		//--------------------------------------
		//	Load vars
		//--------------------------------------

		$this->cached_vars['form_uri'] = $this->mod_link(array(
			'method' => 'migrate_collections'
		));

		//--------------------------------------
		//  Load page
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view(
			'utilities.html',
			NULL,
			TRUE
		);

		return $this->ee_cp_view('index.html');
	}

	//	End utilities

	// --------------------------------------------------------------------

	/**
	 * Confirm Uninstall Fieldtypes
	 *
	 * @access public
	 * @return html
	 */

	public function uninstall_confirm_fieldtype ($name = '')
	{
		$name = trim($name);

		if ($name == '')
		{
			ee()->functions->redirect($this->base);
		}

		ee()->load->model('freeform_field_model');

		$items = ee()->freeform_field_model
						->key('field_label', 'field_label')
						->get(array('field_type' => $name));

		if ($items == FALSE)
		{
			return $this->uninstall_fieldtype($name);
		}
		else
		{
			$confirmation = '<p>' . lang('following_fields_converted') . ': <strong>';

			$confirmation .= implode(', ', $items) . '</strong></p>';

			return $this->delete_confirm(
				'uninstall_fieldtype',
				array('fieldtype' => $name),
				$confirmation,
				'uninstall',
				FALSE
			);
		}
	}
	//END uninstall_confirm_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Uninstalls fieldtypes
	 *
	 * @access public
	 * @param  string $name fieldtype name to remove
	 * @return void
	 */

	public function uninstall_fieldtype ($name = '', $redirect = TRUE)
	{
		if ($name == '')
		{
			$name = ee()->input->get_post('fieldtype', TRUE);
		}

		if ( ! $name)
		{
			$this->actions()->full_stop(lang('no_fieldtypes_submitted'));
		}

		ee()->load->library('freeform_fields');
		$success = ee()->freeform_fields->uninstall_fieldtype($name);

		if ( ! $redirect)
		{
			return $success;
		}

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' 	=> $success,
				'message' 	=> lang('fieldtype_uninstalled'),
			));
		}
		else
		{
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'fieldtypes',
				'msg' 		=> 'fieldtype_uninstalled'
			)));
		}
	}
	//END uninstall_fieldtype


	// --------------------------------------------------------------------

	/**
	 * Delete Fields
	 *
	 * @access public
	 * @return void
	 */

	public function delete_fields ()
	{
		// -------------------------------------
		//	safety goggles
		// -------------------------------------
		//
		$field_ids = ee()->input->get_post('field_id', TRUE);

		if ( ! is_array($field_ids) AND
			 ! $this->is_positive_intlike($field_ids) )
		{
			$this->actions()->full_stop(lang('no_field_ids_submitted'));
		}

		//already checked for numeric :p
		if ( ! is_array($field_ids))
		{
			$field_ids = array($field_ids);
		}

		// -------------------------------------
		//	delete fields
		// -------------------------------------

		ee()->load->library('freeform_fields');

		ee()->freeform_fields->delete_field($field_ids);

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => TRUE
			));
		}
		else
		{
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'fields',
				'msg' 		=> 'fields_deleted'
			)));
		}
	}
	//END delete_fields


	// --------------------------------------------------------------------

	/**
	 * Confirm deletion of notifications
	 *
	 * accepts ajax call to delete notification
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @param 	mixed 	array or int of notification ids to delete
	 * @return	string
	 */

	public function delete_confirm_notification ($notification_id = 0)
	{
		// -------------------------------------
		//	ajax requests should be doing front
		//  end delete confirm. This also handles
		// 	the ajax errors properly
		// -------------------------------------

		if ( $this->is_ajax_request())
		{
			return $this->delete_notification();
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$notification_id = ee()->input->get_post('notification_id');
		}

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$this->actions()->full_stop(lang('invalid_notification_id'));
		}

		if ( is_array($notification_id))
		{
			$notification_id = array_filter(
				$notification_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$notification_id = array($notification_id);
		}

		// -------------------------------------
		//	confirmation page
		// -------------------------------------

		return $this->delete_confirm(
			'delete_notification',
			array(
				'notification_id'	=> $notification_id,
				'return_method'		=> 'notifications'
			),
			'confirm_delete_notification'
		);
	}
	//END delete_confirm_notification


	// --------------------------------------------------------------------

	/**
	 * Delete Notifications
	 *
	 * @access	public
	 * @param	integer	$notification_id	notification
	 * @return	null
	 */

	public function delete_notification ($notification_id = 0)
	{
		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$notification_id = ee()->input->get_post('notification_id');
		}

		if ( ! is_array($notification_id) AND
			 ! $this->is_positive_intlike($notification_id))
		{
			$this->actions()->full_stop(lang('invalid_notification_id'));
		}

		if ( is_array($notification_id))
		{
			$notification_id = array_filter(
				$notification_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$notification_id = array($notification_id);
		}

		ee()->load->model('freeform_notification_model');

		$success = ee()->freeform_notification_model
						->where_in('notification_id', $notification_id)
						->delete();

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => $success
			));
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method : 'notifications';

			ee()->functions->redirect($this->mod_link(array(
				'method'	=> $method,
				'msg'		=> 'delete_notification_success'
			)));
		}

	}
	//END delete_notification

	

	// --------------------------------------------------------------------

	/**
	 * Confirm deletion of templates
	 *
	 * accepts ajax call to delete template
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @param 	mixed 	array or int of template ids to delete
	 * @return	string
	 */

	public function delete_confirm_template ($template_id = 0)
	{
		// -------------------------------------
		//	ajax requests should be doing front
		//  end delete confirm. This also handles
		// 	the ajax errors properly
		// -------------------------------------

		if ( $this->is_ajax_request())
		{
			return $this->delete_template();
		}

		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($template_id) AND
			 ! $this->is_positive_intlike($template_id))
		{
			$template_id = ee()->input->get_post('template_id');
		}

		if ( ! is_array($template_id) AND
			 ! $this->is_positive_intlike($template_id))
		{
			$this->actions()->full_stop(lang('invalid_template_id'));
		}

		if ( is_array($template_id))
		{
			$template_id = array_filter(
				$template_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$template_id = array($template_id);
		}

		// -------------------------------------
		//	confirmation page
		// -------------------------------------

		return $this->delete_confirm(
			'delete_template',
			array(
				'template_id'	=> $template_id,
				'return_method'		=> 'templates'
			),
			'confirm_delete_template'
		);
	}
	//END delete_confirm_template


	// --------------------------------------------------------------------

	/**
	 * Delete Templates
	 *
	 * @access	public
	 * @param	integer	$template_id	template
	 * @return	null
	 */

	public function delete_template ($template_id = 0)
	{
		// -------------------------------------
		//	entry ids?
		// -------------------------------------

		if ( ! is_array($template_id) AND
			 ! $this->is_positive_intlike($template_id))
		{
			$template_id = ee()->input->get_post('template_id');
		}

		if ( ! is_array($template_id) AND
			 ! $this->is_positive_intlike($template_id))
		{
			$this->actions()->full_stop(lang('invalid_template_id'));
		}

		if ( is_array($template_id))
		{
			$template_id = array_filter(
				$template_id,
				array($this, 'is_positive_intlike')
			);
		}
		else
		{
			$template_id = array($template_id);
		}

		ee()->load->model('freeform_template_model');

		$success = ee()->freeform_template_model
						->where_in('template_id', $template_id)
						->delete();

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => $success
			));
		}
		else
		{
			$method = ee()->input->get_post('return_method');

			$method = ($method AND is_callable(array($this, $method))) ?
							$method : 'templates';

			ee()->functions->redirect($this->mod_link(array(
				'method'	=> $method,
				'msg'		=> 'delete_template_success'
			)));
		}

	}
	//END delete_template

	

	// --------------------------------------------------------------------

	/**
	 * save_field
	 *
	 * @access	public
	 * @return	void (redirect)
	 */

	public function save_field ()
	{
		// -------------------------------------
		//	field ID? we must be editing
		// -------------------------------------

		$field_id 		= $this->get_post_or_zero('field_id');

		$update 		= ($field_id != 0);

		// -------------------------------------
		//	yes or no items (all default yes)
		// -------------------------------------

		$y_or_n = array('submissions_page', 'moderation_page', 'composer_use');

		foreach ($y_or_n as $item)
		{
			//set as local var
			$$item = $this->check_no(ee()->input->get_post($item)) ? 'n' : 'y';
		}

		// -------------------------------------
		//	field instance
		// -------------------------------------

		$field_type = ee()->input->get_post('field_type', TRUE);

		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_field_model');

		$available_fieldtypes = ee()->freeform_fields->get_available_fieldtypes();

		//get the update with previous settings if this is an edit
		if ($update)
		{
			$field = ee()->freeform_field_model
					->where('field_id', $field_id)
					->where('field_type', $field_type)
					->count();

			//make sure that we have the correct type just in case they
			//are changing type like hooligans
			if ($field)
			{
				$field_instance =& ee()->freeform_fields->get_field_instance($field_id);
			}
			else
			{
				$field_instance =& ee()->freeform_fields->get_fieldtype_instance($field_type);
			}
		}
		else
		{
			$field_instance =& ee()->freeform_fields->get_fieldtype_instance($field_type);
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors = array();

		// -------------------------------------
		//	field name
		// -------------------------------------

		$field_name = ee()->input->get_post('field_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $field_name OR ! trim($field_name))
		{
			$errors['field_name'] = lang('field_name_required');
		}
		else
		{
			$field_name = strtolower(trim($field_name));

			if ( in_array($field_name, $this->data->prohibited_names ) )
			{
				$errors['field_name'] = str_replace(
					'%name%',
					$field_name,
					lang('freeform_reserved_field_name')
				);
			}

			//if the field_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $field_name) OR
				is_numeric($field_name))
			{
				$errors['field_name'] = lang('field_name_can_only_contain');
			}

			//get dupe from field names
			$f_query = ee()->db->select('field_name, field_id')->get_where(
				'freeform_fields',
				 array('field_name' => $field_name)
			);

			//if we are updating, we don't want to error on the same field id
			if ($f_query->num_rows() > 0 AND
				! ($update AND $f_query->row('field_id') == $field_id))
			{
				$errors['field_name'] = str_replace(
					'%name%',
					$field_name,
					lang('field_name_exists')
				);
			}
		}

		// -------------------------------------
		//	field label
		// -------------------------------------

		$field_label = ee()->input->get_post('field_label', TRUE);

		if ( ! $field_label OR ! trim($field_label) )
		{
			$errors['field_label'] = lang('field_label_required');
		}

		// -------------------------------------
		//	field type
		// -------------------------------------

		if ( ! $field_type OR ! array_key_exists($field_type, $available_fieldtypes))
		{
			$errors['field_type'] = lang('invalid_fieldtype');
		}

		// -------------------------------------
		//	field settings errors?
		// -------------------------------------

		$field_settings_validate = $field_instance->validate_settings();

		if ( $field_settings_validate !== TRUE)
		{
			if (is_array($field_settings_validate))
			{
				$errors['field_settings'] = $field_settings_validate;
			}
			else if (! empty($field_instance->errors))
			{
				$errors['field_settings'] = $field_instance->errors;
			}
			else
			{
				$errors['field_settings'] = lang('field_settings_error');
			}
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->actions()->full_stop($errors);
		}

		if ($this->check_yes(ee()->input->get_post('validate_only')) AND
			$this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' 	=> TRUE
			));
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data 		= array(
			'field_name' 		=> strip_tags($field_name),
			'field_label' 		=> strip_tags($field_label),
			'field_type' 		=> $field_type,
			'edit_date' 		=> '0', //overridden if update
			'field_description' => strip_tags(ee()->input->get_post('field_description', TRUE)),
			'submissions_page' 	=> $submissions_page,
			'moderation_page' 	=> $moderation_page,
			'composer_use' 		=> $composer_use,
			'settings' 			=> json_encode($field_instance->save_settings())
		);

		if ($update)
		{
			ee()->freeform_field_model->update(
				array_merge(
					$data,
					array(
						'edit_date' => ee()->localize->now
					)
				),
				array('field_id' => $field_id)
			);
		}
		else
		{
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
		}

		$field_instance->field_id = $field_id;

		$field_instance->post_save_settings();

		$field_in_forms = array();

		if ($update)
		{
			$field_in_forms = $this->data->get_form_info_by_field_id($field_id);

			if ($field_in_forms)
			{
				$field_in_forms = array_keys($field_in_forms);
			}
			else
			{
				$field_in_forms = array();
			}
		}

		$form_ids = ee()->input->get_post('form_ids');

		if ($form_ids !== FALSE)
		{
			$form_ids = preg_split(
				'/\|/',
				$form_ids,
				-1,
				PREG_SPLIT_NO_EMPTY
			);
		}
		else
		{
			$form_ids = array();
		}

		if ( ! (empty($form_ids) AND empty($field_in_forms)))
		{
			$remove = array_unique(array_diff($field_in_forms, $form_ids));
			$add 	= array_unique(array_diff($form_ids, $field_in_forms));

			ee()->load->library('freeform_forms');

			foreach ($add as $add_id)
			{
				ee()->freeform_forms->add_field_to_form($add_id, $field_id);
			}

			foreach ($remove as $remove_id)
			{
				ee()->freeform_forms->remove_field_from_form($remove_id, $field_id);
			}
		}

		// -------------------------------------
		//	success
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$return = array(
				'success' 	=> TRUE,
				'field_id'	=> $field_id,
			);

			if ($this->check_yes(ee()->input->get_post('include_field_data')))
			{
				$return['composerFieldData'] = $this->composer_field_data($field_id, NULL, TRUE);
			}

			$this->send_ajax_response($return);
		}
		else
		{
			//redirect back to fields on success
			ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'fields',
				'msg'		=> 'edit_field_success'
			)));
		}
	}
	//END save_field

	

	// --------------------------------------------------------------------

	/**
	 * Composer Field Data
	 *
	 * returns field data for the front end for adding fields to composer
	 * if this isn't an ajax request, redirects to field
	 *
	 * @access 	public
	 * @param  	integer $field_id	field to get data from. Checks get/post if 0
	 * @param   boolean	$return		return the data for a local call?
	 * @return 	mixed
	 */

	public function composer_field_data ($field_id = 0, $field_data = NULL, $return = FALSE)
	{
		if ( ! $return AND ! $this->is_ajax_request())
		{
			//redirect back to fields on non-ajax
			ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'fields'
			)));
		}

		$field_id = ($this->is_positive_intlike($field_id)) ?
						$field_id :
						ee()->input->get_post('field_id');

		// -------------------------------------
		//	valid field?
		// -------------------------------------

		if ( ! $field_data)
		{
			$field_data = $this->data->get_field_info_by_id($field_id, FALSE);
		}

		if ( ! $field_data)
		{
			$this->actions()->full_stop(lang('invalid_field_id'));
		}

		// -------------------------------------
		//	field instance
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$instance =& ee()->freeform_fields->get_field_instance(array(
			'field_id'		=> $field_id,
			'field_data'	=> $field_data
		));

		//camel case because its exposed in JS
		$composer_field_data = array(
			'fieldId'		=> $field_data['field_id'],
			'fieldName'		=> $field_data['field_name'],
			'fieldLabel'	=> $field_data['field_label'],
			//encode to keep JS from running
			'fieldOutput' 	=> base64_encode($instance->display_composer_field()),
			'fieldEditUrl'	=> $this->mod_link(array(
				'method' 	=> 'edit_field',
				//this builds a URL, so yes this is intentionally a string
				'modal' 	=> 'true',
				'field_id' 	=> $field_data['field_id']
			), TRUE)
		);

		if ($return)
		{
			return $composer_field_data;
		}

		$this->send_ajax_response(array(
			'success' 				=> TRUE,
			'fieldId'				=> $field_id,
			'composerFieldData'		=> $composer_field_data
		));
	}
	//END composer_field_data

	

	// --------------------------------------------------------------------

	/**
	 * save_notification
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_notification ()
	{
		// -------------------------------------
		//	notification ID? we must be editing
		// -------------------------------------

		$notification_id 		= $this->get_post_or_zero('notification_id');

		$update 				= ($notification_id != 0);

		// -------------------------------------
		//	yes or no items (default yes)
		// -------------------------------------

		$y_or_n = array('wordwrap');

		foreach ($y_or_n as $item)
		{
			//set as local var
			$$item = $this->check_no(ee()->input->get_post($item)) ? 'n' : 'y';
		}

		// -------------------------------------
		//	yes or no items (default no)
		// -------------------------------------

		$n_or_y = array('allow_html', 'include_attachments');

		foreach ($n_or_y as $item)
		{
			//set as local var
			$$item = $this->check_yes(ee()->input->get_post($item)) ? 'y' : 'n';
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors = array();

		// -------------------------------------
		//	notification name
		// -------------------------------------

		$notification_name = ee()->input->get_post('notification_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $notification_name OR ! trim($notification_name))
		{
			$errors['notification_name'] = lang('notification_name_required');
		}
		else
		{
			$notification_name = strtolower(trim($notification_name));

			if ( in_array($notification_name, $this->data->prohibited_names ) )
			{
				$errors['notification_name'] = str_replace(
					'%name%',
					$notification_name,
					lang('reserved_notification_name')
				);
			}

			//if the field_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $notification_name) OR
				is_numeric($notification_name))
			{
				$errors['notification_name'] = lang('notification_name_can_only_contain');
			}

			//get dupe from field names
			ee()->db->select('notification_name, notification_id');

			$f_query = ee()->db->get_where(
				'freeform_notification_templates',
				array(
					'notification_name' => $notification_name
				)
			);

			//if we are updating, we don't want to error on the same field id
			if ($f_query->num_rows() > 0 AND
				! ($update AND $f_query->row('notification_id') == $notification_id))
			{
				$errors['notification_name'] = str_replace(
					'%name%',
					$notification_name,
					lang('notification_name_exists')
				);
			}
		}

		// -------------------------------------
		//	notification label
		// -------------------------------------

		$notification_label = ee()->input->get_post('notification_label', TRUE);

		if ( ! $notification_label OR ! trim($notification_label) )
		{
			$errors['notification_label'] = lang('notification_label_required');
		}

		ee()->load->helper('email');

		// -------------------------------------
		//	notification email
		// -------------------------------------

		$from_email = ee()->input->get_post('from_email', TRUE);

		if ($from_email AND trim($from_email) != '')
		{
			$from_email = trim($from_email);

			//allow tags
			if ( ! preg_match('/' . LD . '([a-zA-Z0-9\_]+)' . RD . '/is', $from_email))
			{
				if ( ! valid_email($from_email))
				{
					$errors['from_email'] = str_replace(
						'%email%',
						$from_email,
						lang('non_valid_email')
					);
				}
			}
		}

		// -------------------------------------
		//	from name
		// -------------------------------------

		$from_name = ee()->input->get_post('from_name', TRUE);

		if ( ! $from_name OR ! trim($from_name) )
		{
			//$errors['from_name'] = lang('from_name_required');
		}

		// -------------------------------------
		//	reply to email
		// -------------------------------------

		$reply_to_email = ee()->input->get_post('reply_to_email', TRUE);

		if ($reply_to_email AND trim($reply_to_email) != '')
		{
			$reply_to_email = trim($reply_to_email);

			//allow tags
			if ( ! preg_match('/' . LD . '([a-zA-Z0-9\_]+)' . RD . '/is', $reply_to_email))
			{
				if ( ! valid_email($reply_to_email))
				{
					$errors['reply_to_email'] = str_replace(
						'%email%',
						$reply_to_email,
						lang('non_valid_email')
					);
				}
			}
		}
		else
		{
			$reply_to_email = '';
		}

		// -------------------------------------
		//	email subject
		// -------------------------------------

		$email_subject = ee()->input->get_post('email_subject', TRUE);

		if ( ! $email_subject OR ! trim($email_subject) )
		{
			$errors['email_subject'] = lang('email_subject_required');
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->actions()->full_stop($errors);
		}
		//ajax checking?
		else if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			return $this->send_ajax_response(array(
				'success' 				=> TRUE
			));
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		$data 		= array(
			'notification_name'			=> strip_tags($notification_name),
			'notification_label'		=> strip_tags($notification_label),
			'notification_description'	=> strip_tags(ee()->input->get_post('notification_description', TRUE)),
			'wordwrap'					=> $wordwrap,
			'allow_html'				=> $allow_html,
			'from_name'					=> $from_name,
			'from_email'				=> $from_email,
			'reply_to_email'			=> $reply_to_email,
			'email_subject'				=> strip_tags($email_subject),
			'template_data'				=> ee()->input->get_post('template_data'),
			'include_attachments'		=> $include_attachments
		);

		ee()->load->model('freeform_notification_model');

		if ($update)
		{
			ee()->freeform_notification_model->update(
				$data,
				array('notification_id' => $notification_id)
			);
		}
		else
		{
			$notification_id = ee()->freeform_notification_model->insert(
				array_merge(
					$data,
					array(
						'site_id' => ee()->config->item('site_id')
					)
				)
			);
		}

		// -------------------------------------
		//	ajax?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' 				=> TRUE,
				'notification_id' 		=> $notification_id
			));
		}
		else
		{
			//redirect back to fields on success
			ee()->functions->redirect($this->mod_link(array(
				'method' 	=> 'notifications',
				'msg' 		=> 'edit_notification_success'
			)));
		}
	}
	//END save_notification

	

	// --------------------------------------------------------------------

	/**
	 * save_template
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_template ()
	{
		// -------------------------------------
		//	template ID? we must be editing
		// -------------------------------------

		$template_id 		= $this->get_post_or_zero('template_id');

		$update 			= ($template_id != 0);

		ee()->load->model('freeform_template_model');

		// -------------------------------------
		//	yes or no items (default yes)
		// -------------------------------------

		$y_or_n = array('enable_template');

		foreach ($y_or_n as $item)
		{
			//set as local var
			$$item = $this->check_no(ee()->input->get_post($item)) ? 'n' : 'y';
		}

		// -------------------------------------
		//	yes or no items (default no)
		// -------------------------------------

		$n_or_y = array();

		foreach ($n_or_y as $item)
		{
			//set as local var
			$$item = $this->check_yes(ee()->input->get_post($item)) ? 'y' : 'n';
		}

		// -------------------------------------
		//	error on empty items or bad data
		//	(doing this via ajax in the form as well)
		// -------------------------------------

		$errors = array();

		// -------------------------------------
		//	template name
		// -------------------------------------

		$template_name = ee()->input->get_post('template_name', TRUE);

		//if the field label is blank, make one for them
		//we really dont want to do this, but here we are
		if ( ! $template_name OR ! trim($template_name))
		{
			$errors['template_name'] = lang('template_name_required');
		}
		else
		{
			$template_name = strtolower(trim($template_name));

			if ( in_array($template_name, $this->data->prohibited_names ) )
			{
				$errors['template_name'] = str_replace(
					'%name%',
					$template_name,
					lang('reserved_template_name')
				);
			}

			//if the field_name they submitted isn't like how a URL title may be
			//also, cannot be numeric
			if (preg_match('/[^a-z0-9\_\-]/i', $template_name) OR
				is_numeric($template_name))
			{
				$errors['template_name'] = lang('template_name_can_only_contain');
			}

			//get dupe from field names

			$f_query = ee()->freeform_template_model
							->select('template_name, template_id')
							->get_row(array('template_name' => $template_name));

			//if we are updating, we don't want to error on the same field id
			if ($f_query !== FALSE AND
				! ($update AND $f_query['template_id'] == $template_id))
			{
				$errors['template_name'] = str_replace(
					'%name%',
					$template_name,
					lang('template_name_exists')
				);
			}
		}

		// -------------------------------------
		//	template label
		// -------------------------------------

		$template_label = ee()->input->get_post('template_label', TRUE);

		if ( ! $template_label OR ! trim($template_label) )
		{
			$errors['template_label'] = lang('template_label_required');
		}

		// -------------------------------------
		//	param data
		// -------------------------------------

		$param_options = array();

		$params	= ee()->input->post('list_param_holder_input');

		$values	= ee()->input->post('list_value_holder_input');

		if ($params AND $values)
		{
			foreach ($params as $key => $value)
			{
				//no blanks!
				if (trim($value) !== '' AND isset($values[$key]))
				{
					$param_options[trim($value)] = trim($values[$key]);
				}
			}
		}

		// -------------------------------------
		//	errors? For shame :(
		// -------------------------------------

		if ( ! empty($errors))
		{
			return $this->actions()->full_stop($errors);
		}
		//ajax checking?
		else if ($this->check_yes(ee()->input->get_post('validate_only')))
		{
			return $this->send_ajax_response(array(
				'success' => TRUE
			));
		}

		// -------------------------------------
		//	insert data
		// -------------------------------------

		ee()->load->helper('text');

		$data = array(
			'template_name'			=> strip_tags($template_name),
			'template_label'		=> strip_tags($template_label),
			'template_description'	=> word_limiter( strip_tags( ee()->input->get_post('template_description', TRUE) ), 200 ),
			'template_data'			=> ee()->input->get_post('template_data'),
			'enable_template'		=> $enable_template,
			'param_data'			=> json_encode($param_options)
		);

		if ($update)
		{
			ee()->freeform_template_model->update(
				$data,
				array('template_id' => $template_id)
			);
		}
		else
		{
			$template_id = ee()->freeform_template_model->insert(
				array_merge(
					$data,
					array(
						'site_id' => ee()->config->item('site_id')
					)
				)
			);
		}

		// -------------------------------------
		//	ajax?
		// -------------------------------------

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'		=> TRUE,
				'template_id'	=> $template_id
			));
		}
		else
		{
			//redirect back to fields on success
			ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'templates',
				'msg'		=> 'edit_template_success'
			)));
		}
	}
	//END save_template


	// --------------------------------------------------------------------

	/**
	 * save_permissions
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_permissions ()
	{
		// -------------------------------------
		//	menu items
		// -------------------------------------

		$menu_items = array();

		foreach ($this->cached_vars['module_menu'] as $menu_name => $menu_data)
		{
			//now why would we deny documentation to anyone? :(
			if ($menu_name == 'module_documentation')
			{
				continue;
			}

			$menu_items[] = str_replace('module_', '', $menu_name);
		}

		// -------------------------------------
		//	member groups
		// -------------------------------------

		$m_groups =	ee()->db
						->from('member_groups')
						->select('group_id')
						->where_not_in('group_id', array(1, 2, 3, 4))
						->get();

		$member_groups = array();

		if ($m_groups->num_rows() > 0)
		{
			foreach ($m_groups->result_array() as $row)
			{
				$member_groups[] = $row['group_id'];
			}
		}

		// -------------------------------------
		//	permissions
		// -------------------------------------

		$global_permissions		= (ee()->input->get_post('global_permissions') == 'y');

		$default_permission_ng	= (
			ee()->input->get_post('default_permission_new_group') == 'deny'
		) ? 'deny' : 'allow';

		$permissions = $this->data->global_preference('permissions');

		if ($permissions === FALSE)
		{
			$permissions = array();
		}

		$permissions['global_permissions'] = $global_permissions;

		$site_id = ($global_permissions) ? 0 : ee()->config->item('site_id');

		$permissions[$site_id]['default_permission_new_group'] = $default_permission_ng;

		foreach ($menu_items as $menu_item)
		{
			//validate
			$allow_type = ee()->input->get_post($menu_item . '_allow_type');
			$allow_type = in_array(
				$allow_type,
				array('allow_all', 'deny_all', 'by_group')
			) ? $allow_type : 'allow_all';

			$permissions[$site_id][$menu_item]['allow_type'] = $allow_type;
			$permissions[$site_id][$menu_item]['groups'] = array();

			if ($allow_type == 'by_group')
			{
				foreach ($member_groups as $group_id)
				{
					$permissions[$site_id][$menu_item]['groups'][$group_id] = (
						ee()->input->get_post($menu_item . '_' . $group_id) == 'y'
					) ? 'y' : 'n';
				}
			}
		}


		// -------------------------------------
		//	save
		// -------------------------------------

		ee()->load->model('freeform_preference_model');

		$update =	ee()->freeform_preference_model
						->where('site_id', 0)
						->where('preference_name', 'permissions')
						->count();

		$permissions = json_encode($permissions);

		if ($update > 0)
		{
			ee()->freeform_preference_model->update(
				array(
					'preference_value'		=> $permissions
				),
				array(
					'site_id'				=> 0,
					'preference_name'		=> 'permissions'
				)
			);
		}
		else
		{
			ee()->freeform_preference_model->insert(
				array(
					'preference_value'		=> $permissions,
					'site_id'				=> 0,
					'preference_name'		=> 'permissions'
				)
			);
		}

		// ----------------------------------
		//  Redirect to Homepage with Message
		// ----------------------------------

		ee()->functions->redirect($this->mod_link(array(
			'method'	=> 'permissions',
			'msg'		=> 'permissions_updated'
		)));
	}
	//END save_permissions


	// --------------------------------------------------------------------

	/**
	 * check_permissions
	 *
	 * @access	public
	 * @param	string	$menu_item	string of menu item
	 * @param	bool	$redirect	redirect on permission false or return bool
	 * @return	mixed				bool permission or redirect
	 */

	public function check_permissions ($menu_item = '', $redirect = TRUE)
	{
		$group_id	= ee()->session->userdata('group_id');

		if ($group_id == 1)
		{
			return TRUE;
		}

		$menu_item		= preg_replace('/^module_/s', '', $menu_item);

		$permissions	= $this->data->global_preference('permissions');

		if ($permissions === FALSE)
		{
			return TRUE;
		}

		$global_permissions = (
			isset($permissions['global_permissions']) AND
			$permissions['global_permissions'] == TRUE
		);

		$site_id	= ($global_permissions) ? 0 : ee()->config->item('site_id');

		$no_home	= BASE;

		//no prefs? (permissions is admin only by default)
		if ( ! isset($permissions[$site_id]) AND $menu_item !== 'permissions')
		{
			return TRUE;
		}
		//else we have permissions
		else if (isset($permissions[$site_id]))
		{
			$deny_missing = (
				isset($permissions[$site_id]['default_permission_new_group']) AND
				$permissions[$site_id]['default_permission_new_group'] == 'deny'
			);

			if (isset($permissions[$site_id][$menu_item]))
			{
				if ($permissions[$site_id][$menu_item]['allow_type'] == 'allow_all')
				{
					return TRUE;
				}
				else if ($permissions[$site_id][$menu_item]['allow_type'] == 'by_group')
				{
					if (isset($permissions[$site_id][$menu_item]['groups'][$group_id]) AND
						$permissions[$site_id][$menu_item]['groups'][$group_id] == 'y')
					{
						return TRUE;
					}
					//the menu item should be present, but just in case
					else if ( ! $deny_missing)
					{
						return TRUE;
					}
				}
				//ok, no permissions to the home page, but they have permission to something else?
				else if ($redirect AND ($menu_item == 'index' OR $menu_item == 'forms'))
				{
					foreach ($permissions[$site_id] as $m_item => $m_data)
					{
						if ($m_data['allow_type'] == 'allow_all' OR
							($m_data['allow_type'] == 'by_group' AND
							isset($m_data['groups'][$group_id]) AND
							$m_data['groups'][$group_id] == 'y'))
						{
							$no_home = $this->cached_vars['module_menu']['module_' . $m_item]['link'];
							break;
						}
					}
				}
			}
			//the menu item should be present, but just in case
			else if ( ! $deny_missing)
			{
				return TRUE;
			}
		}

		if ( ! $redirect)
		{
			return FALSE;
		}

		// ----------------------------------
		//  Redirect to Homepage with Message
		// ----------------------------------

		if ($menu_item == 'index' OR $menu_item == 'forms')
		{
			ee()->functions->redirect($no_home);
		}
		else
		{
			ee()->functions->redirect($this->mod_link());
		}
	}
	//END check_permissions

	


	// --------------------------------------------------------------------

	/**
	 * Sets the menu highlight and assists with permissions (Freeform Pro)
	 *
	 * @access	protected
	 * @param	string		$menu_item	The menu item to highlight
	 */

	protected function set_highlight ($menu_item = 'module_forms')
	{
		
		$this->check_permissions($menu_item);
		
		$this->cached_vars['module_menu_highlight'] = $menu_item;
	}
	//END set_highlight


	// --------------------------------------------------------------------

	/**
	 * save_preferences
	 *
	 * @access	public
	 * @return	null (redirect)
	 */

	public function save_preferences ()
	{
		//defaults are in data.freeform.php
		$prefs = array();

		$all_prefs = array_merge(
			$this->data->default_preferences,
			$this->data->default_global_preferences
		);

		//check post input for all existing prefs and default if not present
		foreach($all_prefs as $pref_name => $data)
		{
			$input 					= ee()->input->get_post($pref_name, TRUE);
			//default
			$output					= $data['value'];

			//int
			if ($data['type'] == 'int' AND
				$this->is_positive_intlike($input, -1))
			{
				$output = $input;
			}
			//yes or no
			elseif ($data['type'] == 'y_or_n' AND
					in_array(trim($input), array('y', 'n'), TRUE))
			{
				$output = trim($input);
			}
			//list of items
			//this seems nutty, but this serializes the list of items
			elseif ($data['type'] == 'list')
			{
				//lotses?
				if (is_array($input))
				{
					$temp_input = array();

					foreach ($input as $key => $value)
					{
						if (trim($value) !== '')
						{
							$temp_input[] = trim($value);
						}
					}

					$output = json_encode($temp_input);
				}
				//just one :/
				else if (trim($input) !== '')
				{
					$output = json_encode(array(trim($input)));
				}
			}
			//text areas
			elseif ($data['type'] == 'text' OR
					$data['type'] == 'textarea' )
			{
				$output = trim($input);
			}


			$prefs[$pref_name] 	= $output;
		}

		//send all prefs to DB
		$this->data->set_module_preferences($prefs);

		// ----------------------------------
		//  Redirect to Homepage with Message
		// ----------------------------------

		ee()->functions->redirect(
			$this->base .
				AMP . 'method=preferences' .
				AMP . 'msg=preferences_updated'
		);
	}
	//END save_preferences


	// --------------------------------------------------------------------

	/**
	 * Export Entries
	 *
	 * Calls entries with proper flags to cue export
	 *
	 * @access public
	 * @return mixed 	forces a download of the exported items or error
	 */

	public function export_entries ()
	{
		$moderate = (ee()->input->get_post('moderate') == 'true');

		return $this->entries(NULL, $moderate, TRUE);
	}
	//END export_entries


	// --------------------------------------------------------------------

	/**
	 * get_standard_column_names
	 *
	 * gets the standard column names and replaces author_id with author
	 *
	 * @access	private
	 * @return	null
	 */

	private function get_standard_column_names()
	{
		$standard_columns 	= array_keys(
			ee()->freeform_form_model->default_form_table_columns
		);

		array_splice(
			$standard_columns,
			array_search('author_id', $standard_columns),
			1,
			'author'
		);

		return $standard_columns;
	}
	//END get_standard_column_names


	// --------------------------------------------------------------------

	/**
	 * mod_link
	 *
	 * makes $this->base . AMP . 'key=value' out of arrays
	 *
	 * @access	public
	 * @param 	array 	key value pair of get vars to add to base
	 * @param 	bool 	$real_amp 	use a real ampersand?
	 * @return	string
	 */

	private function mod_link ($vars = array(), $real_amp = FALSE)
	{
		$link 	= $this->base;
		$amp 	= $real_amp ? '&' : AMP;

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
	 * load_fancybox
	 *
	 * loads fancybox jquery UI plugin and its needed css
	 *
	 * @access	private
	 * @return	null
	 */

	private function load_fancybox()
	{
		//so currently the fancybox setup inlucded in EE doesn't get built
		//automaticly and requires relying on the current CP theme.
		//Dislike. Inlcuding our own version instead.
		//Seems fancy box has also been removed from some later versions
		//of EE, so instinct here was correct.
		$css_link = $this->sc->addon_theme_url . 'fancybox/jquery.fancybox-1.3.4.css';
		$js_link = $this->sc->addon_theme_url . 'fancybox/jquery.fancybox-1.3.4.pack.js';

		ee()->cp->add_to_head('<link href="' . $css_link . '" type="text/css" rel="stylesheet" media="screen" />');
		ee()->cp->add_to_foot('<script src="' . $js_link . '" type="text/javascript"></script>');
	}
	//END load_fancybox


	// --------------------------------------------------------------------

	/**
	 * freeform_add_right_link
	 *
	 * abstractor for cp add_right_link so freeform can move it how it needs
	 * when an alternate style is chosen
	 *
	 * @access	private
	 * @param 	string 	words of link to display
	 * @param 	string 	link to display
	 * @return	null
	 */

	private function freeform_add_right_link ($lang, $link)
	{
		$this->cached_vars['inner_nav_links'][$lang] = $link;

		//return $this->add_right_link($lang, $link);
	}
	//END freeform_add_right_link


	// --------------------------------------------------------------------

	/**
	 * Module Upgrading
	 *
	 * This function is not required by the 1.x branch of ExpressionEngine by default.  However,
	 * as the install and deinstall ones are, we are just going to keep the habit and include it
	 * anyhow.
	 *		- Originally, the $current variable was going to be passed via parameter, but as there might
	 *		  be a further use for such a variable throughout the module at a later date we made it
	 *		  a class variable.
	 *
	 *
	 * @access	public
	 * @return	bool
	 */

	public function freeform_module_update()
	{
		if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
		{
			$this->add_crumb(lang('update_freeform_module'));
			$this->build_crumbs();
			$this->cached_vars['form_url'] = $this->base . '&msg=update_successful';

			if ($this->pro_update)
			{
				$this->cached_vars['form_url'] .= "&update_pro=true";
			}

			$this->cached_vars['current_page'] = $this->view(
				'update_module.html',
				NULL,
				TRUE
			);

			return $this->ee_cp_view('index.html');
		}

		require_once $this->addon_path . 'upd.freeform.php';

		$U = new Freeform_upd();

		if ($U->update() !== TRUE)
		{
			return ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'index',
				'msg'		=> lang('update_failure')
			)));
		}
		else
		{
			return ee()->functions->redirect($this->mod_link(array(
				'method'	=> 'index',
				'msg'		=> lang('update_successful')
			)));
		}
	}
	// END freeform_module_update()


	// --------------------------------------------------------------------

	/**
	 * Visible Columns
	 *
	 * @access 	protected
	 * @param   $possible_columns possible columns
	 * @return 	array array of visible columns
	 */

	protected function visible_columns ($standard_columns = array(),
										$possible_columns = array())
	{
		// -------------------------------------
		//	get column settings
		// -------------------------------------

		$column_settings 	= array();

		ee()->load->model('freeform_form_model');

		$field_layout_prefs = $this->preference('field_layout_prefs');
		$member_id 			= ee()->session->userdata('member_id');
		$group_id 			= ee()->session->userdata('group_id');
		$f_prefix 			= ee()->freeform_form_model->form_field_prefix;

		//¿existe? Member? Group? all?
		if ($field_layout_prefs)
		{
			//$field_layout_prefs = json_decode($field_layout_prefs, TRUE);

			$entry_layout_prefs = (
				isset($field_layout_prefs['entry_layout_prefs']) ?
					$field_layout_prefs['entry_layout_prefs'] :
					FALSE
			);

			if ($entry_layout_prefs)
			{
				if (isset($entry_layout_prefs['member'][$member_id]))
				{
					$column_settings = $entry_layout_prefs['member'][$member_id];
				}
				else if (isset($entry_layout_prefs['all']))
				{
					$column_settings = $entry_layout_prefs['all'];
				}
				else if (isset($entry_layout_prefs['group'][$group_id]))
				{
					$column_settings = $entry_layout_prefs['group'][$group_id];
				}
			}
		}

		//if a column is missing, we don't want to error
		//and if its newer than the settings, show it by default
		//settings are also in order of appearence here.

		//we also store the field ids without the prefix
		//in case someone changed it. That would probably
		//hose everything, but who knows? ;)
		if ( ! empty($column_settings))
		{
			$to_sort = array();

			//we are going over possible instead of settings in case something
			//is new or an old column is missing
			foreach ($possible_columns as $cid)
			{
				//if these are new, put them at the end
				if (! in_array($cid, $column_settings['visible']) AND
					! in_array($cid, $column_settings['hidden'])
				)
				{
					$to_sort[$cid] = $cid;
				}
			}

			//now we want columns from the settings order to go first
			//this way stuff thats not been removed gets to keep settings
			foreach ($column_settings['visible'] as $ecid)
			{
				if (in_array($ecid, $possible_columns))
				{
					//since we are getting our real results now
					//we can add the prefixes
					if ( ! in_array($ecid, $standard_columns) )
					{
						$ecid = $f_prefix . $ecid;
					}

					$visible_columns[] = $ecid;
				}
			}

			//and if we have anything left over (new fields probably)
			//its at the end
			if (! empty($to_sort))
			{
				foreach ($to_sort as $tsid)
				{
					//since we are getting our real results now
					//we can add the prefixes
					if ( ! in_array($tsid, $standard_columns) )
					{
						$tsid = $f_prefix . $tsid;
					}

					$visible_columns[] = $tsid;
				}
			}
		}
		//if we don't have any settings, just toss it all in in order
		else
		{
			foreach ($possible_columns as $pcid)
			{
				if ( ! in_array($pcid, $standard_columns) )
				{
					$pcid = $f_prefix . $pcid;
				}

				$visible_columns[] = $pcid;
			}

			//in theory it should always be there if prefs are empty ...

			$default_hide = array('site_id', 'entry_id', 'complete');

			foreach ($default_hide as $hide_me_seymour)
			{
				if (in_array($hide_me_seymour, $visible_columns))
				{
					unset(
						$visible_columns[
							array_search(
								$hide_me_seymour,
								$visible_columns
							)
						]
					);
				}
			}

			//fix keys, but preserve order
			$visible_columns = array_merge(array(), $visible_columns);
		}

		return $visible_columns;
	}
	//END visible_columns


	// --------------------------------------------------------------------

	/**
	 * Format CP date
	 *
	 * @access	public
	 * @param	mixed	$date	unix time
	 * @return	string			unit time formatted to cp date formatting pref
	 */

	public function format_cp_date($date)
	{
		return $this->actions()->format_cp_date($date);
	}
	//END format_cp_date


	// --------------------------------------------------------------------

	/**
	 * Send AJAX response
	 *
	 * Outputs and exit either an HTML string or a
	 * JSON array with the Profile disabled and correct
	 * headers sent.
	 *
	 * @access	public
	 * @param	string|array	String is sent as HTML, Array is sent as JSON
	 * @param	bool			Is this an error message?
	 * @param 	bool 			bust cache for JSON?
	 * @return	void
	 */

	public function send_ajax_response($msg, $error = FALSE, $cache_bust = TRUE)
	{
		$this->restore_xid();
		parent::send_ajax_response($msg, $error, $cache_bust);
	}
	//END send_ajax_response


	// --------------------------------------------------------------------

	/**
	 * EE 2.7+ restore xid with version check
	 * @access	public
	 * @return	voic
	 */

	public function restore_xid()
	{
		if (version_compare($this->ee_version, '2.7', '>='))
		{
			ee()->security->restore_xid();
		}
	}
	//END restore_xid
}
// END CLASS Freeform