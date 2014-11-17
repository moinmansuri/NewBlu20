<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Actions
 *
 * Shared functions between many libraries
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/act.freeform.php
 */

if ( ! defined('FREEFORM_VERSION'))
{
	require_once 'constants.freeform.php';
}

require_once 'addon_builder/module_builder.php';

class Freeform_acc extends Module_builder_freeform
{
	public $name		= 'Freeform';
	public $id			= 'freeform_acc';
	public $version		= FREEFORM_VERSION;
	public $description	= 'Recent Freeform entries and short names for fields attached to forms.';
	public $sections	= array();


	// --------------------------------------------------------------------

	/**
	 * __construct
	 *
	 * @access	public
	 */

	public function __construct()
	{
		parent::__construct();

		$this->name			= lang('freeform_module_name');
		$this->description	= lang('freeform_accessory_description');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Set Sections
	 *
	 * @access	public
	 */

	public function set_sections()
	{
		ee()->cp->load_package_js('accessory');

		ee()->load->helper('form');

		// -------------------------------------
		//	sections
		// -------------------------------------

		$this->sections[lang('freeform_form_info')] = '<p>' . form_hidden(
			'freeform_acc_ajax_link',
			str_replace('&amp;', '&', BASE) .
				"&C=addons_accessories" .
				"&M=process_request" .
				"&accessory=" . $this->lower_name .
				"&method=process_form_info"
		) . '<img src="' . PATH_CP_GBL_IMG .'loadingAnimation.gif" /></p>';
	}
	//END set_sections


	// --------------------------------------------------------------------

	/**
	 * Get Form Info
	 *
	 * @access	public
	 * @return	void	exits as its an ajax request
	 */

	public function process_form_info()
	{
		ee()->output->enable_profiler(FALSE);

		$data = array();

		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_field_model');
		ee()->load->model('freeform_entry_model');

		// -------------------------------------
		//	form info
		// -------------------------------------

		if ( ! $this->data->show_all_sites())
		{
			ee()->freeform_form_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$forms = ee()->freeform_form_model->order_by('form_label')->get();

		// -------------------------------------
		//	field info
		// -------------------------------------

		$field_ids = array();

		foreach ($forms as $form)
		{
			$field_ids += $form['field_ids'];
		}

		array_unique($field_ids);

		$fields = ee()->freeform_field_model
					->where_in('field_id', $field_ids)
					->key('field_id', 'field_name')
					->get();

		// -------------------------------------
		//	gather info
		// -------------------------------------

		$form_info = array();

		foreach ($forms as $form)
		{
			$info = array();

			$info['mod_link']			= $this->base;
			$info['form_link']			= $this->base .
											'&method=edit_form&form_id=' .
											$form['form_id'];
			$info['submission_link']	= $this->base .
											'&method=entries&form_id=' .
											$form['form_id'];
			$info['pending_link']		= $this->base .
											'&method=entries&form_id=' .
											$form['form_id'] .
											'&search_status=pending';
			$info['submission_count']	= $this->data
											->get_form_submissions_count($form['form_id']);
			$info['pending_count']		= $this->data
											->get_form_needs_moderation_count($form['form_id']);

			$info['field_names']		= array();
			$info['form_data']			= $form;

			foreach ($form['field_ids'] as $fid)
			{
				if (isset($fields[$fid]))
				{
					$info['field_names'][] = $fields[$fid];
				}
			}

			$form_info[$form['form_id']] = $info;
		}

		$data['form_info'] = $form_info;

		exit($this->view(
			'accessory.html',
			$data,
			TRUE
		));
	}
	//END get_form_info
}
//END Freeform_acc