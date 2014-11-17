<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - User Side
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @version		4.1.3
 * @filesource	freeform/mod.freeform.php
 */

if ( ! class_exists('Module_builder_freeform'))
{
	require_once 'addon_builder/module_builder.php';
}

class Freeform extends Module_builder_freeform
{
	/**
	 * return data for when the constructor os only called
	 * unused thusfar in this addon.
	 * @var string
	 */
	public $return_data		= '';

	/**
	 * Multipart form?
	 * @var boolean
	 * @see form
	 */
	public $multipart		= FALSE;

	/**
	 * Params array storage for param
	 * @var array
	 * @see param
	 */
	public $params			= array();

	/**
	 * params id for param fetch
	 * @var integer
	 * @see form
	 * @see insert_params
	 */
	public $params_id		= 0;

	/**
	 * Form ID storage
	 * @var integer
	 * @see form_id
	 */
	public $form_id			= 0;

	/**
	 * Test Mode?
	 *
	 * @var boolean
	 * @see do_exist
	 */
	public $test_mode		= FALSE;

	/**
	 * Default Multipage Marker
	 *
	 * @var string
	 * @see form
	 * @see set_form_params
	 */
	public $default_mp_page_marker = 'page';

	/**
	 * Multipage Page Array
	 *
	 * @var array
	 * @see get_mp_page_array
	 */
	public $mp_page_array;

	/**
	 * Prevents rerunning of set_form_params
	 * @var boolean
	 * @see set_form_params
	 */
	public $params_ran = FALSE;

	/**
	 * Form Data
	 * @var array
	 */
	public $form_data;

	/**
	 * Params With Defaults
	 * @var array
	 * @see get_default_params
	 */
	public $params_with_defaults;

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

		// -------------------------------------
		//  Module Installed and Up to Date?
		// -------------------------------------

		ee()->load->helper(array('text', 'form', 'url', 'string'));

		//avoids AR collisions
		$this->data->get_module_preferences();
		$this->data->get_global_module_preferences();
		$this->data->show_all_sites();
	}
	// END __construct()


	// --------------------------------------------------------------------

	/**
	 * Form Info
	 *
	 * @access	public
	 * @return	string parsed tagdata
	 */

	public function form_info()
	{
		$form_ids = $this->form_id(TRUE, FALSE);

		ee()->load->model('freeform_form_model');

		if ($form_ids)
		{
			ee()->freeform_form_model->where_in('form_id', $form_ids);
		}

		// -------------------------------------
		//	site ids
		// -------------------------------------

		//if its star, allow all
		if (ee()->TMPL->fetch_param('site_id') !== '*')
		{
			$site_id = $this->parse_numeric_array_param('site_id');

			//if this isn't false, its single or an array
			if ($site_id !== FALSE)
			{
				if (empty($site_id['ids']))
				{
					ee()->freeform_form_model->reset();
					return $this->no_results_error();
				}
				else if ($site_id['not'])
				{
					ee()->freeform_form_model->where_not_in(
						'site_id',
						$site_id['ids']
					);
				}
				else
				{
					ee()->freeform_form_model->where_in(
						'site_id',
						$site_id['ids']
					);
				}
			}
			//default
			else
			{
				ee()->freeform_form_model->where(
					'site_id',
					ee()->config->item('site_id')
				);
			}
		}

		// -------------------------------------
		//	form data
		// -------------------------------------

		$form_data =	ee()->freeform_form_model
							->select(
								'form_id, site_id, ' .
								'form_name, form_label, ' .
								'form_description, author_id, ' .
								'entry_date, edit_date'
							)
							->order_by('form_id', 'asc')
							->get();

		if ( ! $form_data)
		{
			return	$this->no_results_error(($form_ids) ?
						'invalid_form_id' :
						NULL
					);
		}

		// -------------------------------------
		//	author data
		// -------------------------------------

		$author_ids		= array();
		$author_data	= array();

		foreach ($form_data as $row)
		{
			$author_ids[] = $row['author_id'];
		}

		$a_query = ee()->db->select('member_id, username, screen_name')
							->from('members')
							->where_in('member_id', array_unique($author_ids))
							->get();

		if ($a_query->num_rows() > 0)
		{
			$author_data = $this->prepare_keyed_result(
				$a_query,
				'member_id'
			);
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		$variables = array();

		ee()->load->model('freeform_entry_model');

		foreach ($form_data as $row)
		{
			$new_row = array();

			foreach ($row as $key => $value)
			{
				$new_row['freeform:' . $key] = $value;
			}

			$new_row['freeform:total_entries']	=	ee()->freeform_entry_model
														->id($row['form_id'])
														->where('complete', 'y')
														->count();
			$new_row['freeform:author']			=	(
				isset($author_data[$row['author_id']]) ?
					(
						isset($author_data[$row['author_id']]['screen_name']) ?
							$author_data[$row['author_id']]['screen_name'] :
							$author_data[$row['author_id']]['username']
					) :
					lang('n_a')
			);

			$variables[] = $new_row;
		}

		$prefixed_tags	= array(
			'count',
			'switch',
			'total_results'
		);

		$tagdata = ee()->TMPL->tagdata;

		$tagdata = $this->tag_prefix_replace(
			'freeform:',
			$prefixed_tags,
			$tagdata
		);

		//this should handle backspacing as well
		$tagdata = ee()->TMPL->parse_variables($tagdata, $variables);

		$tagdata = $this->tag_prefix_replace(
			'freeform:',
			$prefixed_tags,
			$tagdata,
			TRUE
		);

		return $tagdata;
	}
	//END form_info


	// --------------------------------------------------------------------

	/**
	 * Freeform:Entries
	 * {exp:freeform:entries}
	 *
	 * @access	public
	 * @return	string 	tagdata
	 */

	public function entries ()
	{
		// -------------------------------------
		//	form id
		// -------------------------------------

		$form_ids = $this->form_id(TRUE, FALSE);

		if ( ! $form_ids)
		{
			return $this->no_results_error('invalid_form_id');
		}

		if ( ! is_array($form_ids))
		{
			$form_ids = array($form_ids);
		}

		// -------------------------------------
		//	libs, models, helper
		// -------------------------------------

		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_entry_model');
		ee()->load->model('freeform_field_model');
		ee()->load->library('freeform_forms');
		ee()->load->library('freeform_fields');

		// -------------------------------------
		//	start cache for count and result
		// -------------------------------------

		$forms_data	=	ee()->freeform_form_model
							->key('form_id')
							->get(array('form_id' => $form_ids));

		$statuses	= array_keys($this->data->get_form_statuses());

		// -------------------------------------
		//	field data
		// -------------------------------------

		$all_field_ids	= array();
		$all_order_ids	= array();

		foreach ($forms_data as $form_data)
		{
			//this should always be true, but NEVER TRUST AN ELF
			if (isset($form_data['field_ids']) AND
				is_array($form_data['field_ids']))
			{
				$all_field_ids = array_merge(
					$all_field_ids,
					$form_data['field_ids']
				);

				$all_order_ids = array_merge(
					$all_order_ids,
					$this->actions()->pipe_split($form_data['field_order'])
				);
			}
		}

		$all_field_ids = array_unique($all_field_ids);
		$all_order_ids = array_unique($all_order_ids);

		sort($all_field_ids);

		// -------------------------------------
		//	get field data
		// -------------------------------------

		$all_field_data = FALSE;

		if ( ! empty($all_field_ids))
		{
			$all_field_data = ee()->freeform_field_model
									->key('field_id')
									->where_in('field_id', $all_field_ids)
									->get();
		}

		$field_data = array();

		if ($all_field_data)
		{
			foreach ($all_field_data as $row)
			{
				$field_data[$row['field_id']] = $row;
			}
		}

		// -------------------------------------
		//	set tables
		// -------------------------------------

		ee()->freeform_entry_model->id($form_ids);

		// -------------------------------------
		//	replace CURRENT_USER before we get
		//	started because the minute we don't
		//	someone is going to figure out
		//	a way to need it in site_id=""
		// -------------------------------------

		$this->replace_current_user();

		// -------------------------------------
		//	site ids
		// -------------------------------------

		//if its star, allow all
		if (ee()->TMPL->fetch_param('site_id') !== '*')
		{
			$site_id = $this->parse_numeric_array_param('site_id');

			//if this isn't false, its single or an array
			if ($site_id !== FALSE)
			{
				if (empty($site_id['ids']))
				{
					ee()->freeform_entry_model->reset();
					return $this->no_results_error();
				}
				else if ($site_id['not'])
				{
					ee()->freeform_entry_model->where_not_in(
						'site_id',
						$site_id['ids']
					);
				}
				else
				{
					ee()->freeform_entry_model->where_in(
						'site_id',
						$site_id['ids']
					);
				}
			}
			//default
			else
			{
				ee()->freeform_entry_model->where(
					'site_id',
					ee()->config->item('site_id')
				);
			}
		}

		// -------------------------------------
		//	entry ids
		// -------------------------------------

		$entry_id = $this->parse_numeric_array_param('entry_id');

		if ($entry_id !== FALSE)
		{
			if (empty($entry_id['ids']))
			{
				ee()->freeform_entry_model->reset();
				return $this->no_results_error();
			}
			else if ($entry_id['not'])
			{
				ee()->freeform_entry_model->where_not_in(
					'entry_id',
					$entry_id['ids']
				);
			}
			else
			{
				ee()->freeform_entry_model->where_in(
					'entry_id',
					$entry_id['ids']
				);
			}
		}

		// -------------------------------------
		//	author ids
		// -------------------------------------

		$author_id = $this->parse_numeric_array_param('author_id');

		if ($author_id !== FALSE)
		{
			if (empty($author_id['ids']))
			{
				ee()->freeform_entry_model->reset();
				return $this->no_results_error();
			}
			else if ($author_id['not'])
			{
				ee()->freeform_entry_model->where_not_in(
					'author_id',
					$author_id['ids']
				);
			}
			else
			{
				ee()->freeform_entry_model->where_in(
					'author_id',
					$author_id['ids']
				);
			}
		}

		// -------------------------------------
		//	freeform:all_form_fields
		// -------------------------------------

		$tagdata = $this->replace_all_form_fields(
			ee()->TMPL->tagdata,
			$field_data,
			$all_order_ids
		);

		// -------------------------------------
		//	get standard columns and labels
		// -------------------------------------

		$standard_columns 	= array_keys(
			ee()->freeform_form_model->default_form_table_columns
		);

		$standard_columns[] = 'author';

		$column_labels		= array();

		//keyed labels for the front end
		foreach ($standard_columns as $column_name)
		{
			$column_labels[$column_name] = lang($column_name);
		}

		// -------------------------------------
		//	available fields
		// -------------------------------------

		//this makes the keys and values the same
		$available_fields	= array_combine(
			$standard_columns,
			$standard_columns
		);

		$custom_fields		= array();
		$field_descriptions	= array();

		foreach ($field_data as $field_id => $f_data)
		{
			$fid = ee()->freeform_form_model->form_field_prefix . $field_id;

			//field_name => field_id_1, etc
			$available_fields[$f_data['field_name']]	= $fid;
			//field_id_1 => field_id_1, etc
			$available_fields[$fid]						= $fid;
			$custom_fields[] = $f_data['field_name'];

			//labels
			$column_labels[$f_data['field_name']]		= $f_data['field_label'];
			$column_labels[$fid]						= $f_data['field_label'];

			$field_descriptions[
				'freeform:description:' . $f_data['field_name']
			]	= $f_data['field_description'];
		}

		// -------------------------------------
		//	search:field_name="kittens"
		// -------------------------------------

		foreach (ee()->TMPL->tagparams as $key => $value)
		{
			if (substr($key, 0, 7) == 'search:')
			{
				$search_key = substr($key, 7);

				if (isset($available_fields[$search_key]))
				{
					ee()->freeform_entry_model->add_search(
						$available_fields[$search_key],
						$value
					);
				}
			}
		}

		// -------------------------------------
		//	date range
		// -------------------------------------

		$date_range			= ee()->TMPL->fetch_param('date_range');
		$date_range_start	= ee()->TMPL->fetch_param('date_range_start');
		$date_range_end		= ee()->TMPL->fetch_param('date_range_end');

		ee()->freeform_entry_model->date_where(
			$date_range,
			$date_range_start,
			$date_range_end
		);

		// -------------------------------------
		//	complete
		// -------------------------------------

		$show_incomplete = ee()->TMPL->fetch_param('show_incomplete');

		if ($show_incomplete === 'only')
		{
			ee()->freeform_entry_model->where('complete', 'n');
		}
		else if ( ! $this->check_yes($show_incomplete))
		{
			ee()->freeform_entry_model->where('complete', 'y');
		}

		// -------------------------------------
		//	status
		// -------------------------------------

		$status = ee()->TMPL->fetch_param('status', 'open');

		if ($status !== 'all')
		{
			//make it an array either way
			$status = array_map('trim', $this->actions()->pipe_split($status));

			$approved = array_map('strtolower', $statuses);

			$search_status = array();

			//only keep legit ones
			foreach($status as $potential_status)
			{
				if (in_array(strtolower($potential_status), $approved))
				{
					$search_status[] = $potential_status;
				}
			}

			if ( ! empty($search_status))
			{
				ee()->freeform_entry_model->where_in(
					'status',
					$search_status
				);
			}
		}

		// -------------------------------------
		//	orderby/sort
		// -------------------------------------

		$sort 		= ee()->TMPL->fetch_param('sort');
		$orderby 	= ee()->TMPL->fetch_param('orderby');

		if ($orderby !== FALSE AND trim($orderby) !== '')
		{
			$orderby = $this->actions()->pipe_split(strtolower(trim($orderby)));

			array_walk($orderby, 'trim');

			// -------------------------------------
			//	sort
			// -------------------------------------

			if ($sort !== FALSE AND trim($sort) !== '')
			{
				$sort = $this->actions()->pipe_split(strtolower(trim($sort)));

				array_walk($sort, 'trim');

				//correct sorts
				foreach ($sort as $key => $value)
				{
					if ( ! in_array($value, array('asc', 'desc')))
					{
						$sort[$key] = 'asc';
					}
				}
			}
			else
			{
				$sort = array('asc');
			}

			// -------------------------------------
			//	add sorts and orderbys
			// -------------------------------------

			foreach ($orderby as $key => $value)
			{
				if (isset($available_fields[$value]))
				{
					//if the sort is not set, just use the first
					//really this should teach people to be more specific :p
					$temp_sort = isset($sort[$key]) ? $sort[$key] : $sort[0];

					ee()->freeform_entry_model->order_by(
						$available_fields[$value],
						$temp_sort
					);
				}
			}
		}

		//--------------------------------------
		//  pagination start vars
		//--------------------------------------

		$limit				= ee()->TMPL->fetch_param('limit', 50);
		$offset				= ee()->TMPL->fetch_param('offset', 0);
		$row_count			= 0;
		$total_entries		= ee()->freeform_entry_model->count(array(), FALSE);
		$current_page		= 0;

		if ($total_entries == 0)
		{
			ee()->freeform_entry_model->reset();
			return $this->no_results_error();
		}

		// -------------------------------------
		//	pagination?
		// -------------------------------------

		$prefix = stristr($tagdata, LD . 'freeform:paginate' . RD);

		if ($limit > 0 AND ($total_entries - $offset) > $limit)
		{
			//get pagination info
			$pagination_data = $this->universal_pagination(array(
				'total_results'			=> $total_entries,
				'tagdata'				=> $tagdata,
				'limit'					=> $limit,
				'offset'				=> $offset,
				'uri_string'			=> ee()->uri->uri_string,
				'prefix'				=> 'freeform:',
				'auto_paginate'			=> TRUE
			));

			//if we paginated, sort the data
			if ($pagination_data['paginate'] === TRUE)
			{
				$tagdata		= $pagination_data['tagdata'];
				$current_page 	= $pagination_data['pagination_page'];
			}
		}
		else
		{
			$this->paginate = FALSE;
		}

		ee()->freeform_entry_model->limit($limit, $current_page + $offset);

		// -------------------------------------
		//	get data
		// -------------------------------------

		$result_array = ee()->freeform_entry_model->get();

		if (empty($result_array))
		{
			ee()->freeform_entry_model->reset();
			return $this->no_results_error();
		}

		$output_labels = array();

		//column labels for output
		foreach ($column_labels as $key => $value)
		{
			$output_labels['freeform:label:' . $key] = $value;
		}

		$count				= $row_count;

		$variable_rows		= array();

		$replace_tagdata	= '';

		// -------------------------------------
		//	allow pre_process
		// -------------------------------------

		$entry_ids = array();

		foreach ($result_array as $row)
		{
			if ( ! isset($entry_ids[$row['form_id']]))
			{
				$entry_ids[$row['form_id']] = array();
			}

			$entry_ids[$row['form_id']][] = $row['entry_id'];
		}

		// -------------------------------------
		//	preprocess items
		// -------------------------------------
		//	These are separated by form id so this
		//	is not iterating over each entry id
		//	but rather grouped by form.
		// -------------------------------------

		foreach ($entry_ids as $f_form_id => $f_entry_ids)
		{
			ee()->freeform_fields->apply_field_method(array(
				'method'		=> 'pre_process_entries',
				'form_id'		=> $f_form_id,
				'form_data'		=> $forms_data,
				'entry_id'		=> $f_entry_ids,
				'field_data'	=> $field_data
			));
		}

		// -------------------------------------
		//	output
		// -------------------------------------

		$to_prefix = array(
			'absolute_count',
			'absolute_results',
			'author_id',
			'author',
			'complete',
			'edit_date',
			'entry_date',
			'entry_id',
			'form_id',
			'form_name',
			'ip_address',
			'reverse_count'
		);

		$absolute_count = $current_page + $offset;
		$total_results	= count($result_array);
		$count			= 0;

		foreach ($result_array as $row)
		{
			//apply replace tag to our field data
			$field_parse = ee()->freeform_fields->apply_field_method(array(
				'method'			=> 'replace_tag',
				'form_id'			=> $row['form_id'],
				'entry_id'			=> $row['entry_id'],
				'form_data'			=> $forms_data,
				'field_data'		=> $field_data,
				'field_input_data'	=> $row,
				'tagdata'			=> $tagdata
			));


			$row = array_merge(
				$output_labels,
				$field_descriptions,
				$row,
				$field_parse['variables']
			);

			if ($replace_tagdata == '')
			{
				$replace_tagdata = $field_parse['tagdata'];
			}

			$row['freeform:form_name']			= $forms_data[$row['form_id']]['form_name'];
			$row['freeform:form_label']			= $forms_data[$row['form_id']]['form_label'];

			//prefix
			foreach ($row as $key => $value)
			{
				if ( ! preg_match('/^freeform:/', $key))
				{
					if (in_array($key, $custom_fields) AND
						! isset($row['freeform:field:' . $key]))
					{
						$row['freeform:field:' . $key] = $value;
					}
					else if ( ! isset($row['freeform:' . $key]))
					{
						$row['freeform:' . $key] = $value;
					}

					unset($row[$key]);
				}
			}

			// -------------------------------------
			//	other counts
			// -------------------------------------
			$row['freeform:reverse_count']		= $total_results - $count++;
			$row['freeform:absolute_count']		= ++$absolute_count;
			$row['freeform:absolute_results']	= $total_entries;


			$variable_rows[] = $row;
		}

		$tagdata = $replace_tagdata;

		$prefixed_tags	= array(
			'count',
			'switch',
			'total_results'
		);

		$tagdata = $this->tag_prefix_replace('freeform:', $prefixed_tags, $tagdata);

		//this should handle backspacing as well
		$tagdata = ee()->TMPL->parse_variables($tagdata, $variable_rows);

		$tagdata = $this->tag_prefix_replace('freeform:', $prefixed_tags, $tagdata, TRUE);

		// -------------------------------------
		//	add pagination
		// -------------------------------------

		//prefix or no prefix?
		if ($prefix)
		{
			$tagdata = $this->parse_pagination(array(
				'prefix' 	=> 'freeform:',
				'tagdata' 	=> $tagdata
			));
		}
		else
		{
			$tagdata = $this->parse_pagination(array(
				'tagdata' 	=> $tagdata
			));
		}

		return $tagdata;
	}
	//END entries


	

	// --------------------------------------------------------------------

	/**
	 * Run composer in edit mode
	 *
	 * @access	public
	 * @return	string	html tagdata
	 */

	public function composer_edit()
	{
		return $this->composer(TRUE);
	}
	//END composer_edit


	// --------------------------------------------------------------------

	/**
	 * Parses composer fields over a template then sends to :form
	 *
	 * @access	public
	 * @param	boolean $edit	edit mode? (gets passed to form)
	 * @return	string			parsed tagdata
	 */

	public function composer($edit = FALSE)
	{
		// -------------------------------------
		//	form id
		// -------------------------------------

		$form_id = $this->form_id(FALSE, FALSE);

		if ( ! $form_id)
		{
			return $this->no_results_error('invalid_form_id');
		}

		$this->form_data = $form_data = $this->data->get_form_info($form_id);
		$composer_id	= $form_data['composer_id'];
		$preview		= FALSE;
		$preview_fields	= array();

		// -------------------------------------
		//	is this a preview?
		// -------------------------------------

		$preview_id		= ee()->input->get_post('preview_id');

		if ( ! $this->is_positive_intlike($preview_id))
		{
			if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
			{
				$preview_id = ee()->TMPL->fetch_param('preview_id');
			}
		}

		if ($this->is_positive_intlike($preview_id))
		{
			$preview	= TRUE;
			$composer_id	= $preview_id;
		}

		// -------------------------------------
		//	build query
		// -------------------------------------

		if ( ! $this->is_positive_intlike($composer_id) OR
			( ! $preview AND empty($form_data['fields'])))
		{
			return $this->no_results_error('invalid_composer_id');
		}

		ee()->load->model('freeform_composer_model');

		$composer = ee()->freeform_composer_model->get_row($composer_id);

		if ($composer == FALSE)
		{
			return $this->no_results_error('invalid_composer_id');
		}

		$composer_data = $this->json_decode($composer['composer_data'], TRUE);

		if ( ! $composer_data OR
			 ! isset($composer_data['rows']) OR
			 empty($composer_data['rows']))
		{
			return $this->no_results_error('invalid_composer_data');
		}

		$composer_fields		= ( ! empty($composer_data['fields'])) ?
										$composer_data['fields'] :
										array();

		$available_fields		= array_keys($form_data['fields']);
		$needed_preview_fields	= array_diff($composer_fields, $available_fields);

		// -------------------------------------
		//	preview fields? (composer preview)
		// -------------------------------------
		//	we only want them if there is a difference
		//  validate fields in case some where deleted at some point
		//  we dont want composer trying to output those
		// -------------------------------------

		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_field_model');

		if ($preview AND
			! empty($needed_preview_fields))
		{
			sort($needed_preview_fields);

			//dont worry this will cache for later
			$valid_preview_fields = ee()->freeform_field_model
										->where_in('field_id', $needed_preview_fields)
										->key('field_id')
										->get();

			if ($valid_preview_fields !== FALSE AND
				 ! empty($valid_preview_fields))
			{
				$preview_fields		= array_keys($valid_preview_fields);

				//join into available for composer checking
				$available_fields	= array_merge($preview_fields, $available_fields);

				//add fields for valid mess
				foreach ($valid_preview_fields as $p_field_id => $p_field_data)
				{
					$p_field_data['preview']			= TRUE;
					$form_data['fields'][$p_field_id]	= $p_field_data;
				}
			}
		}

		// -------------------------------------
		//	calculate pages early
		// -------------------------------------

		$total_pages = 1;

		$skip_pages = ! $this->check_yes(
			ee()->TMPL->fetch_param('disable_mp_performance')
		);

		//this cannot be found normally with composer
		//so we are getting it from the composer
		//data itself
		foreach ($composer_data['rows'] as $row)
		{
			if ($row == 'page_break')
			{
				$total_pages++;
			}
		}

		if ($total_pages == 1)
		{
			$skip_pages = FALSE;
		}
		else
		{
			$this->set_form_param('multipage', 'yes', true);
		}

		// -------------------------------------
		//	get params (do this _after_ multipage)
		// -------------------------------------

		$pages = $this->set_page_positions();

		$current_page = $pages['current_page'];

		// -------------------------------------
		//	build variables
		// -------------------------------------

		$page					= 0;

		/*
		//very complicated when dealing
		//with multiple pages. need to
		//ask customers and see what
		//they need

		$field_absolute_count	= 0;
		$field_aboslute_total	= 0;
		$column_absolute_count	= 0;
		$column_absolute_total	= 0;

		foreach ($composer_data['rows'] as $row)
		{
			$column_absolute_total += count($row);

			foreach ($row as $column)
			{
				$field_absolute_count += count($column);
			}
		}
		*/

		$variables				= array();

		$variables[]			= array(
			'composer:multi_page_start'	=> '',
			'composer:multi_page_end'	=> '',
			'composer:rows'				=> array(),
			'composer:preview'			=> $preview
		);

		$required				= array();
		$dynamic_recipients		= array();

		$has_captcha			= FALSE;

		foreach ($composer_data['rows'] as $row)
		{
			if ($row == 'page_break')
			{
				$page++;

				//set for 0 if not present
				if ($page == 1)
				{
					$variables[$page - 1]['composer:multi_page_start']	= '{freeform:page:' . $page . '}';
					$variables[$page - 1]['composer:multi_page_end']	= '{/freeform:page:' . $page . '}';
				}

				if ( ! empty($required))
				{
					$variables[$page - 1]['composer:multi_page_start']	= '' .
						'{freeform:page:' . $page . ' required="' . implode('|', $required) . '"}';
					$required = array();
				}

				$variables[] = array(
					'composer:multi_page_start'	=> '{freeform:page:' . ($page + 1) . '}',
					'composer:multi_page_end'	=> '{/freeform:page:' . ($page + 1) . '}',
					'composer:rows'				=> array(),
					'composer:preview'			=> $preview
				);

				continue;
			}

			$row_array = array(
				'composer:columns'		=> array()
			);

			// -------------------------------------
			//	skip rendering pages we aren't on
			//	for performance
			// -------------------------------------

			if ($skip_pages && $current_page != ($page + 1))
			{
				continue;
			}


			$column_count	= 0;

			foreach ($row as $column)
			{
				$column_array = array(
					'composer:fields'		=> array(),
					'composer:colspan'		=> (12/count($row)),
					'composer:column_total'	=> count($row),
					'composer:column_count'	=> ++$column_count,
					'composer:field_total'	=> count($column),
				);

				$field_count	= 0;

				foreach ($column as $field)
				{
					$fields = array();

					// -------------------------------------
					//	defaults for every field
					// -------------------------------------

					$fields['composer:field_type']		= $field['type'];
					$fields['composer:field_label']		= '';
					$fields['composer:field_name']		= '';
					$fields['composer:field_output']	= '';
					$fields['composer:field_required']	= FALSE;
					$fields['composer:field_count']		= ++$field_count;

					// -------------------------------------
					//	regular fields
					// -------------------------------------

					if ($field['type'] == 'field' AND
						in_array($field['fieldId'], $available_fields))
					{
						$field_data = $form_data['fields'][$field['fieldId']];
						$fields['composer:field_name'] = $field_data['field_name'];

						$instance =& ee()->freeform_fields->get_field_instance(array(
							'field_id'		=> $field['fieldId'],
							'form_id'		=> $form_id,
							'field_data'	=> $field_data
						));

						$fields['composer:field_type']		= $field_data['field_type'];

						if ($instance->show_label)
						{
							$fields['composer:field_label']		= $field_data['field_label'];
						}

						$fields['composer:field_output']	= '{freeform:field:' . $field_data['field_name'] .'}';

						if (isset($field['required']) AND $field['required'] == 'yes')
						{
							$required[] = $field_data['field_name'];
							$fields['composer:field_required']	= TRUE;
						}
					}

					// -------------------------------------
					//	title
					// -------------------------------------

					else if ($field['type'] == 'nonfield_title')
					{
						$fields['composer:field_output'] = $form_data['form_label'];
					}

					// -------------------------------------
					//	paragraph
					// -------------------------------------

					else if ($field['type'] == 'nonfield_paragraph')
					{
						$fields['composer:field_output'] = $field['html'];
					}

					// -------------------------------------
					//	user recipients
					// -------------------------------------

					else if ($field['type'] == 'nonfield_user_recipients')
					{
						$fields['composer:field_label'] = $field['html'];
						$fields['composer:field_output'] = form_input(array(
							'name'	=> 'recipient_email_user',
							'value'	=> '{freeform:mp_data:user_recipient_emails}'
						));

						$this->set_form_param('recipient_user_input', 'yes', true);

						if (isset($field['required']) AND $field['required'] == 'yes')
						{
							$required[] = 'recipient_email_user';
							$fields['composer:field_required']	= TRUE;
						}
					}

					// -------------------------------------
					//	dynamic recipients
					// -------------------------------------

					else if ($field['type'] == 'nonfield_dynamic_recipients')
					{
						$fields['composer:field_label'] = $field['label'];

						$dynrec_data		= $field['data'];

						//notification id?
						if (isset($field['notificationId']) AND
							$this->is_positive_intlike($field['notificationId']) AND
							//we want to allow overrides
							! isset(ee()->TMPL->tagparams['recipient_template']))
						{
							$this->set_form_param(
								'recipient_template',
								$field['notificationId'],
								true
							);
						}

						$dynrec_emails		= array();

						//fix items and output
						if ($dynrec_data)
						{
							foreach ($dynrec_data as $recipient_email => $recipient_name)
							{
								$dynamic_recipients[$recipient_email] = $recipient_name;

								//we need to find the num like this
								//in case someone dupped an email on different
								//rows/pages. Yes thats unlikely, but you know
								//it will happen and someone will be pissed.
								$dynrec_email_num = array_search(
									$recipient_email,
									array_keys($dynamic_recipients)
								) + 1;

								$dynrec_output_array[
									'{freeform:recipient_value' . $dynrec_email_num . '}'
								] = '{freeform:recipient_name' . $dynrec_email_num . '}';
							}

							if ($field['outputType'] == 'checks')
							{

							$fields['composer:field_output'] = 	'
								<ul class="dynamic_recipients">
								{freeform:recipients}
									<li>
										<label>
											<input
												type="checkbox"
												name="recipient_email[]"
												{if freeform:recipient_selected}
													checked="checked"
												{/if}
												value="{freeform:recipient_value}" />
												&nbsp;
												{freeform:recipient_name}
										</label>
									</li>
								{/freeform:recipients}
								</ul>';
							}
							else
							{
								//this will just be #0 because everything
								//else is keyed
								array_unshift($dynrec_output_array, '--');

								$fields['composer:field_output'] = '
								<select name="recipient_email" />
									<option value="0">--</option>
									{freeform:recipients}
									<option value="{freeform:recipient_value}"
									{if freeform:recipient_selected}selected="selected"{/if}>
										{freeform:recipient_name}
									</option>
									{/freeform:recipients}
								</select>';

							}

							if (isset($field['required']) AND $field['required'] == 'yes')
							{
								$required[] = 'recipient_email';
								$fields['composer:field_required']	= TRUE;
							}
						}
					}

					// -------------------------------------
					//	captcha
					// -------------------------------------

					else if ($field['type'] == 'nonfield_captcha')
					{
						$has_captcha = TRUE;
						$fields['composer:field_label']		= lang('captcha_input_instructions');
						$fields['composer:field_output']	= '{freeform:captcha}<br />' .
								'<input type="text" name="captcha" value="" ' .
									'size="20"   maxlength="20" style="width:140px;" />';

						if (isset($field['required']) AND $field['required'] == 'yes')
						{
							$required[] = 'captcha';
							$fields['composer:field_required']	= TRUE;
						}
					}

					// -------------------------------------
					//	submit
					// -------------------------------------

					else if ($field['type'] == 'nonfield_submit')
					{
						if ($field['html'])
						{
							$fields['composer:field_output'] = '{freeform:submit attr:value="' .
																$field['html'] . '"}';
						}
						else
						{
							$fields['composer:field_output'] = '{freeform:submit}';
						}

					}

					// -------------------------------------
					//	Submit previous
					// -------------------------------------

					else if ($field['type'] == 'nonfield_submit_previous')
					{
						if ($field['html'])
						{
							$fields['composer:field_output'] = '{freeform:submit_previous attr:value="' .
																$field['html'] . '"}';
						}
						else
						{
							$fields['composer:field_output'] = '{freeform:submit_previous}';
						}
					}

					$column_array['composer:fields'][] = $fields;
				}
				//END foreach ($column as $field)

				$row_array['composer:columns'][] = $column_array;
			}
			//END foreach ($row as $column)

			$variables[$page]['composer:rows'][] = $row_array;
		}
		//END foreach ($composer_data['rows'] as $row)

		//catch the last required items
		if ( $page > 0 AND ! empty($required))
		{
			$variables[$page]['composer:multi_page_start']	= '' .
				'{freeform:page:' . ($page + 1) . ' required="' . implode('|', $required) . '"}';
			$required = array();
		}
		//single page form?
		else if (! empty($required))
		{
			//manual additions?
			if (isset(ee()->TMPL->tagparams['required']))
			{
				$required = array_unique(
					array_merge(
						$this->actions()->pipe_split(ee()->TMPL->tagparams['required']),
						$required
					)
				);
			}

			$this->set_form_param('required', implode('|', $required), true);
		}

		// -------------------------------------
		//	dynamic recipients?
		// -------------------------------------

		if ( ! empty($dynamic_recipients))
		{
			//we want to allow overrides
			if ( ! isset(ee()->TMPL->tagparams['recipients']))
			{
				$this->set_form_param('recipients', 'yes', true);
			}

			//foreach recipients
			$dynrec_counter = 1;
			foreach ($dynamic_recipients as $dynrec_email => $dynrec_name)
			{
				$this->set_form_param(
					'recipient' . $dynrec_counter,
					$dynrec_name . '|' . $dynrec_email,
					true
				);
				$dynrec_counter++;
			}
		}

		// -------------------------------------
		//	There was a captcha in the composer
		//	templates?
		// -------------------------------------
		//	This seems a little weird but the
		//	issue is that we are testing for
		//	the tag pair's existance in form()
		//	but because conditionals run late
		//	you might still have the tag pair
		//	in the tagdata even if its not
		//	in the selected composer fields.
		//	This means people with static
		//	captcha in their templates need to
		//	set extra params that override
		//	this default of off if no captcha
		//	is in the composer fields.
		// -------------------------------------

		if ( ! isset(ee()->TMPL->tagparams['require_captcha']))
		{
			$this->set_form_param(
				'require_captcha',
				($has_captcha) ? 'yes' : 'no',
				true
			);
		}

		// -------------------------------------
		//	check for composer data
		// -------------------------------------

		$tagdata = ee()->TMPL->tagdata;

		if (empty($tagdata))
		{
			// -------------------------------------
			//	composer?
			// -------------------------------------

			$template_param_id = ee()->TMPL->fetch_param('composer_template_id');
			$template_param_name = ee()->TMPL->fetch_param('composer_template_name');

			$template = FALSE;

			ee()->load->model('freeform_template_model');

			if ($this->is_positive_intlike($template_param_id))
			{
				$template = ee()->freeform_template_model
								->select('template_data, param_data')
								->where('enable_template', 'y')
								->where('template_id', $template_param_id)
								->get_row();
			}
			else if ( ! in_array($template_param_name, array(FALSE, ''), TRUE))
			{
				$template = ee()->freeform_template_model
								->select('template_data, param_data')
								->where('enable_template', 'y')
								->where('template_name', $template_param_name)
								->get_row();
			}
			else if ($form_data['template_id'] > 0)
			{
				$template = ee()->freeform_template_model
								->select('template_data, param_data')
								->where('enable_template', 'y')
								->where('template_id', $form_data['template_id'])
								->get_row();
			}

			if ($template !== FALSE)
			{
				$tagdata = $template['template_data'];

				//extra tag params?
				if (isset($template['param_data']) AND
					is_string($template['param_data']))
				{
					$template['param_data'] = json_decode($template['param_data'], TRUE);

					if (is_array($template['param_data']))
					{
						foreach ($template['param_data'] as $param => $value)
						{
							$this->set_form_param($param, $value, true);
						}
					}
				}
			}

			//backup plan
			if (empty($tagdata))
			{
				$tagdata = $this->view(
					'default_composer_template.html',
					array('wrappers' => FALSE),
					TRUE
				);
			}
		}

		//because we are doing some inline replacements with start and end
		//might fix this to be more efficient later
		$tagdata = str_replace(
			array(
				'{composer:page}',
				'{/composer:page}'
			),
			array(
				'{composer:page}{composer:multi_page_start}',
				'{composer:multi_page_end}{/composer:page}'
			),
			$tagdata
		);

		$output_vars = array(array('composer:page' => $variables));

		ee()->TMPL->tagdata = ee()->TMPL->parse_variables($tagdata, $output_vars);

		return $this->form($edit, $preview, $preview_fields);
	}
	//END composer

	// --------------------------------------------------------------------

	/**
	 * Freeform:Edit
	 *
	 * This adds an extra level of protection so someone cannot inject entry
	 * ids and force an edit
	 *
	 * @access	public
	 * @return	string 	tagdata
	 */

	public function edit ()
	{
		return $this->form(TRUE);
	}
	//END edit

	

	// --------------------------------------------------------------------

	/**
	 * Freeform:Form
	 * {exp:freeform:form}
	 *
	 * @access	public
	 * @param 	bool 	$edit			edit mode? external for security
	 * @param	bool	$preview		preview mode?
	 * @param	mixed	$preview_fields	extra preview fields?
	 * @return	string 	tagdata
	 */

	public function form($edit = FALSE, $preview = FALSE, $preview_fields = FALSE)
	{
		if ($this->check_yes(ee()->TMPL->fetch_param('require_logged_in')) AND
			ee()->session->userdata['member_id'] == '0')
		{
			return $this->no_results_error('not_logged_in');
		}

		// -------------------------------------
		//	form id
		// -------------------------------------

		$form_id = $this->form_id(FALSE, FALSE);

		if ( ! $form_id)
		{
			return $this->no_results_error('invalid_form_id');
		}

		// -------------------------------------
		//	libs, helpers, etc
		// -------------------------------------

		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_field_model');
		ee()->load->library('freeform_forms');
		ee()->load->library('freeform_fields');
		ee()->load->helper('form');

		// -------------------------------------
		//	build query
		// -------------------------------------

		$this->form_data = $form_data = $this->data->get_form_info($form_id);

		// -------------------------------------
		//	preview fields? (composer preview)
		// -------------------------------------

		if ( ! empty($preview_fields))
		{
			ee()->load->model('freeform_field_model');

			$valid_preview_fields = ee()->freeform_field_model
										->where_in('field_id', $preview_fields)
										->key('field_id')
										->get();

			if ($valid_preview_fields)
			{
				foreach ($valid_preview_fields as $p_field_id => $p_field_data)
				{
					$p_field_data['preview']			= TRUE;
					$form_data['fields'][$p_field_id]	= $p_field_data;
				}
			}
		}

		// -------------------------------------
		//	form data
		// -------------------------------------

		$this->params['form_id'] = $form_id;

		// -------------------------------------
		//	edit?
		// -------------------------------------

		$entry_id	= 0;

		$edit_data	= array();

		

		if ($edit)
		{
			$entry_id = $this->entry_id();

			if ( ! $entry_id)
			{
				return $this->no_results_error('invalid_entry_id');
			}

			$edit_data = $this->data->get_entry_data_by_id($entry_id, $form_id);

			//this should be unlikely, but hey...
			if ($edit_data == FALSE)
			{
				return $this->no_results_error('invalid_entry_id');
			}
		}

		

		$this->params['edit']				= $edit;
		$this->params['entry_id']			= $entry_id;

		// -------------------------------------
		//	replace CURRENT_USER everywhere
		// -------------------------------------

		$this->replace_current_user();

		// -------------------------------------
		//	default params
		// -------------------------------------

		$this->default_mp_page_marker = 'page';

		$this->set_form_params();

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$duplicate = FALSE;

		//we can only prevent dupes on entry like this
		if ( ! $edit AND $this->params['prevent_duplicate_on'])
		{
			if ( in_array(
					$this->params['prevent_duplicate_on'],
					array('member_id', 'ip_address'),
					TRUE
				))
			{
				$duplicate = ee()->freeform_forms->check_duplicate(
					$form_id,
					$this->params['prevent_duplicate_on'],
					'',
					$this->params['prevent_duplicate_per_site']
				);
			}
		}

		//	----------------------------------------
		//	duplicate?
		//	----------------------------------------

		if ($duplicate)
		{
			if ($this->params['duplicate_redirect'] !== '')
			{
				ee()->functions->redirect(
					$this->prep_url(
						$this->params['duplicate_redirect'],
						$this->params['secure_duplicate_redirect']
					)
				);

				return $this->do_exit();
			}
			else if ($this->params['error_on_duplicate'])
			{
				return $this->no_results_error('no_duplicates');
			}
			/*else if (preg_match(
				'/' . LD . 'if freeform_duplicate' . RD . '(*?)' '/',
				ee()->TMPL->tagdata, ))
			{

			}*/
		}

		// -------------------------------------
		//	check user email field
		// 	if this is from form prefs, its an ID
		// -------------------------------------

		$valid_user_email_field = FALSE;

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if ($this->params['user_email_field'] == $field_data['field_name'] OR
				$this->params['user_email_field'] == $field_id)
			{
				$valid_user_email_field = TRUE;

				//in case the setting is an id
				$this->params['user_email_field'] = $field_data['field_name'];
				break;
			}
		}

		//  if it doesn't exist in the form, lets blank it
		$this->params['user_email_field'] = (
			$valid_user_email_field ?
				$this->params['user_email_field'] :
				''
		);

		

		// -------------------------------------
		//	restrict edit to author?
		// -------------------------------------

		if ($edit AND
			$this->params['restrict_edit_to_author'] AND
			ee()->session->userdata('group_id') != 1 AND
			(	ee()->session->userdata('member_id') == 0 OR
				$edit_data['author_id'] != ee()->session->userdata('member_id')
			)
		)
		{
			return $this->no_results_error('author_edit_only');
		}

		

		$this->edit	= $edit;

		//	----------------------------------------
		//	'freeform_module_form_begin' hook.
		//	 - This allows developers to change data before form processing.
		//	----------------------------------------

		if (ee()->extensions->active_hook('freeform_module_form_begin') === TRUE)
		{
			ee()->extensions->universal_call(
				'freeform_module_form_begin',
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}
		//	----------------------------------------

		// -------------------------------------
		//	start form
		// -------------------------------------

		$tagdata				= ee()->TMPL->tagdata;
		$return					= '';
		$hidden_fields			= array();
		$outer_template_vars	= array();
		$variables				= array();
		$page_total				= 1;
		$current_page			= 0;
		$last_page				= TRUE;
		$multipage				= $this->params['multipage'];

		// -------------------------------------
		//	check if this is multi-page
		// -------------------------------------

		

		if ( $multipage )
		{
			// -------------------------------------
			//	calc markers, current page, etc
			// -------------------------------------

			$pos = $this->set_page_positions();

			$current_page	= $pos['current_page'];
			$page_total		= $pos['page_total'];
			$next_page		= $pos['next_page'];
			$previous_page	= $pos['previous_page'];
			$page_type		= $pos['page_type'];


			// -------------------------------------
			//	page by name?
			// -------------------------------------

			$mp_page_array = $this->get_mp_page_array();

			if ( ! empty($mp_page_array))
			{
				//replace all things like {freeform:page:first_name with
				//{freeform:page:1
				for ($i = 0, $l = count($mp_page_array); $i <  $l;  $i++)
				{
					$tagdata = preg_replace(
						'/(' . LD . '[' . preg_quote('/', '/') .
							']?freeform:page:)' .
							preg_quote($mp_page_array[$i], '/') .
							'([\s|' . RD . '])/s',
						'${1}' . ($i + 1) . '${2}',
						$tagdata
					);
				}
			}

			// -------------------------------------
			//	check total pages?
			// -------------------------------------
			//	looks for:
			//	{freeform:page:1}stuff{/freeform:page:1}
			//	tag pairs
			// -------------------------------------

			$page_matches = array();

			preg_match_all(
				'/' . LD . 'freeform:page:([0-9]+).*?' . RD .
					'(.*?)' .
				LD . '\/freeform:page:\\1' . RD . '/ms',
				$tagdata,
				$page_matches
			);

			// -------------------------------------
			//	If we have no matches, there is nothing
			//	we can do but treat this like a single
			//	page form.
			// -------------------------------------

			if ( ! $page_matches OR
				 ! isset($page_matches[0]) OR
				 empty($page_matches[0]))
			{
				$current_page	= 1;

				//if there are no pages, then this isn't multipage, is it...
				$this->param['multipage'] 	= $multipage = FALSE;
			}
			// -------------------------------------
			//	count page totals
			// -------------------------------------
			//	This can't really be done until
			//	we are here in the form because it
			//	needs full tagdata to work.
			// -------------------------------------
			else
			{
				//if we are automatically paging
				if ($page_type == 'int')
				{
					$page_total		= count($page_matches[0]);

					//reset because we have a new page total
					$next_page		= ($current_page < $page_total) ?
										$current_page + 1 : 0;

					$previous_page	= ($current_page > 1) ?
						$current_page - 1 : 0;
				}

				foreach ($page_matches[0] as $key => $value)
				{
					//current page we are looking at?
					if ($current_page == $page_matches[1][$key])
					{
						//remove outer {page tags for current
						$tagdata = str_replace(
							$value,
							$page_matches[2][$key],
							$tagdata
						);

						// -------------------------------------
						//	Check for override params
						// -------------------------------------

						//no need for an if. if we are here, this matched before
						preg_match(
							'/' . LD . 'freeform:page:([0-9]+).*?' . RD . '/',
							$value,
							$sub_matches
						);

						// Checking for variables/tags embedded within tags
						// {exp:channel:entries channel="{master_channel_name}"}
						if (stristr(substr($sub_matches[0], 1), LD) !== FALSE)
						{
							$sub_matches[0] = ee()->functions->full_tag(
								$sub_matches[0],
								$value
							);
						}

						$page_params = ee()->functions->assign_parameters(
							$sub_matches[0]
						);

						$allowed_overrides = array(
							'paging_url',
							'required',
							'matching_fields'
						);

						foreach ($allowed_overrides as $key)
						{
							if (isset($page_params[$key]))
							{
								$this->params[$key] = $page_params[$key];
							}
						}
					}
					// -------------------------------------
					//	Not current page. Remove its
					//	{freeform:page:1}stuff{/freeform:page:1}
					//	tag pair so nothing parses.
					// -------------------------------------
					else
					{
						//remove
						$tagdata = str_replace($value, '', $tagdata);
					}
				}
			}

			// -------------------------------------
			//	finally if our paging_url is not set
			//	we should set it to current url + page1, etc
			// -------------------------------------

			if ($this->params['paging_url'] == '')
			{
				$this->params['paging_url'] = '/' .
					ee()->uri->uri_string . '/' .
					'%page%';
			}

			// -------------------------------------
			//	next page for multipage
			// -------------------------------------

			$this->params['last_page'] = $last_page = ($current_page == $page_total);

			if ( ! $last_page)
			{
				$page_replacer = ($page_type == 'int') ?
									$this->params['page_marker'] .
										$next_page :
									$next_page;

				$this->params['multipage_next_page'] = str_replace(
					'%page%',
					$page_replacer,
					$this->params['paging_url']
				);
			}


			$p_page_replacer = ($page_type == 'int') ?
								$this->params['page_marker'] .
									$previous_page :
								$previous_page;


			$this->params['multipage_previous_page'] = '';

			if ($current_page !== 1)
			{
				$this->params['multipage_previous_page'] = str_replace(
					'%page%',
					$p_page_replacer,
					$this->params['paging_url']
				);
			}

			// -------------------------------------
			//	check hashes and attempt to get data
			// -------------------------------------

			$hash = ee()->freeform_forms->check_multipage_hash(
				$form_id,
				$entry_id,
				$edit
			);

			$previous_inputs = array();

			if ( $hash AND ! $edit )
			{
				$previous_inputs = ee()->freeform_forms->get_multipage_form_data($form_id, $hash);
			}
			else if ($hash AND $edit)
			{
				$previous_inputs = $edit_data;
			}
			//either the previous inputs could not be found,
			//or the hash was bad
			if ( ! $hash)
			{
				//if we are redirecting on timeout
				//lets move on
				if ((int) $current_page !== 1 AND
					$this->params['redirect_on_timeout'] AND
					$this->params['redirect_on_timeout_to'] !== '')
				{
					ee()->functions->redirect(
						$this->prep_url(
							$this->params['redirect_on_timeout_to']
						)
					);

					return $this->do_exit();
				}

				$hash = ee()->freeform_forms->start_multipage_hash(
					$form_id,
					$entry_id,
					$edit
				);
			}

			$hidden_fields[ee()->freeform_forms->hash_cookie_name] = $hash;
		}
		else
		{
			
			$current_page = 1;
			
		}
			

		// -------------------------------------
		//	set for hooks
		// -------------------------------------

		$this->multipage = $multipage;
		$this->last_page = $last_page;

		// -------------------------------------
		//	check again for captcha now that
		//	tagdata has been adjusted
		// -------------------------------------

		if ($this->params['require_captcha'])
		{
			$this->params['require_captcha'] = (
				stristr($tagdata, LD . 'freeform:captcha' . RD) != FALSE
			);
		}

		// -------------------------------------
		//	submit
		// -------------------------------------

		//standard submits
		$variables['freeform:submit'] = form_submit('submit', lang('submit'));

		//replace submit buttons that have args
		$tagdata = $this->replace_submit(array(
			'tag'		=> 'freeform:submit',
			'pre_args'	=> array(
				'name' => 'submit',
				'value' => lang('submit')
			),
			'tagdata'	=> $tagdata
		));

		// -------------------------------------
		//	other random vars
		// -------------------------------------

		$variables['freeform:submit_previous']	= '';
		$variables['freeform:duplicate']		= $duplicate;
		$variables['freeform:not_duplicate']	= ! $duplicate;
		$variables['freeform:form_label']		= $form_data['form_label'];
		$variables['freeform:form_description']	= $form_data['form_description'];
		$variables['freeform:last_page']		= $last_page;
		$variables['freeform:current_page']		= $current_page;

		

		// -------------------------------------
		//	previous page links
		// -------------------------------------

		if ($multipage && $current_page > 1)
		{
			$variables['freeform:submit_previous'] = form_submit(
				'submit_to_previous',
				lang('previous')
			);

			//replace submit button with args
			$tagdata = $this->replace_submit(array(
				'tag'		=> 'freeform:submit_previous',
				'pre_args'	=> array(
					'value' => lang('previous')
				),
				'post_args' => array(
					'name' => 'submit_to_previous',
				),
				'tagdata'	=> $tagdata
			));
		}
		//first page? empty
		else
		{
			$tagdata = $this->replace_submit(array(
				'tag'		=> 'freeform:submit_previous',
				'remove'	=> true,
				'tagdata'	=> $tagdata
			));
		}
		//END if ($multipage && $current_page > 1)

		// -------------------------------------
		//	edit vars
		// -------------------------------------

		if ($edit)
		{
			$edit_data_items = array(
				'author',
				'author_id',
				'complete',
				'edit_date',
				'entry_date',
				'entry_id',
				'site_id',
				'status',
			);

			foreach ($edit_data_items as $edit_data_item)
			{
				$variables['freeform:edit_data:' . $edit_data_item]	= isset(
					$edit_data[$edit_data_item]
				) ? $edit_data[$edit_data_item] : '';
			}
		}
		

		// -------------------------------------
		//	display fields
		// -------------------------------------

		$field_error_data	= array();
		$general_error_data	= array();
		$field_input_data	= array();

		// -------------------------------------
		//	inline errors?
		// -------------------------------------

		if ($this->params['inline_errors'] AND
			$this->is_positive_intlike(
				ee()->session->flashdata('freeform_errors')
			)
		)
		{
			ee()->load->model('freeform_param_model');

			$error_query = ee()->freeform_param_model->get_row(
				ee()->session->flashdata('freeform_errors')
			);

			if ($error_query !== FALSE)
			{
				$potential_error_data = json_decode($error_query['data'], TRUE);

				//specific field errors
				if (isset($potential_error_data['field_errors']))
				{
					$field_error_data = $potential_error_data['field_errors'];
				}

				//errors that aren't field based
				if (isset($potential_error_data['general_errors']))
				{
					$general_error_data = $potential_error_data['general_errors'];
				}

				//gets inputs for repopulation
				if (isset($potential_error_data['inputs']))
				{
					$field_input_data = $potential_error_data['inputs'];
				}

				//restore recipient_emails
				if (! empty($potential_error_data['stored_data']['recipient_emails']))
				{
					$previous_inputs['hash_stored_data']['recipient_emails'] =
						$potential_error_data['stored_data']['recipient_emails'];
				}

				//restore user_recipient_emails
				if (! empty($potential_error_data['stored_data']['user_recipient_emails']))
				{
					$previous_inputs['hash_stored_data']['user_recipient_emails'] =
						$potential_error_data['stored_data']['user_recipient_emails'];
				}
			}
		}
		//END if ($this->params['inline_errors']

		// -------------------------------------
		//	build field variables
		// -------------------------------------

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			// -------------------------------------
			//	label?
			// -------------------------------------

			$error = '';

			if (isset($field_error_data[$field_data['field_name']]))
			{
				$error = is_array($field_error_data[$field_data['field_name']]) ?
							implode(', ', $field_error_data[$field_data['field_name']]) :
							$field_error_data[$field_data['field_name']];
			}

			// -------------------------------------
			//	variables for later parsing
			// -------------------------------------

			$variables['freeform:error:' . $field_data['field_name']] = $error;

			$variables['freeform:label:' . $field_data['field_name']] = $field_data['field_label'];
			$variables['freeform:description:' . $field_data['field_name']] = $field_data['field_description'];

			// -------------------------------------
			//	values?
			// -------------------------------------

			$col_name = ee()->freeform_form_model->form_field_prefix . $field_id;

			// -------------------------------------
			//	multipage previous inputs?
			// -------------------------------------

			$possible = (
				isset($previous_inputs[$col_name]) ?
					$previous_inputs[$col_name] :
					(
						isset($previous_inputs[$field_data['field_name']]) ?
							$previous_inputs[$field_data['field_name']] :
							''
					)
			);

			$possible = $this->prep_multi_item_data($possible, $field_data['field_type']);

			$variables['freeform:mp_data:' . $field_data['field_name']] = $possible;

			

			// -------------------------------------
			//	previous edit data?
			// -------------------------------------

			if ($edit)
			{
				$possible = (
					isset( $edit_data[$field_data['field_name']] ) ?
						$edit_data[$field_data['field_name']] :
						''
				);

				$possible = $this->prep_multi_item_data($possible, $field_data['field_type']);
				$variables['freeform:edit_data:' . $field_data['field_name']]	= $possible;
			}

			

		}
		//END foreach ($form_data['fields'] as $field_id => $field_data)

		// -------------------------------------
		//	This is done after edit data in
		//	cause they edited data, but had an error
		//	in their edits and we are now in
		//	inline error mode
		// -------------------------------------

		if ( ! empty($edit_data))
		{
			$field_input_data = array_merge($edit_data, $field_input_data);
		}
		else if ( ! empty($previous_inputs))
		{
			$field_input_data = array_merge($previous_inputs, $field_input_data);
		}

		// -------------------------------------
		//	recipient emails from multipage?
		// -------------------------------------

		$variables['freeform:mp_data:user_recipient_emails'] = '';

		if (isset($previous_inputs['hash_stored_data']['user_recipient_emails']) AND
			is_array($previous_inputs['hash_stored_data']['user_recipient_emails']))
		{
			$variables['freeform:mp_data:user_recipient_emails'] = implode(
				', ',
				$previous_inputs['hash_stored_data']['user_recipient_emails']
			);
		}


		// -------------------------------------
		//	freeform:all_form_fields
		// -------------------------------------

		$tagdata = $this->replace_all_form_fields(
			$tagdata,
			$form_data['fields'],
			$form_data['field_order'],
			$field_input_data
		);

		// -------------------------------------
		//	general errors
		// -------------------------------------

		if ( ! empty($general_error_data))
		{
			//the error array might have sub arrays
			//so we need to flatten
			$_general_error_data = array();

			foreach ($general_error_data as $error_set => $error_data)
			{
				if (is_array($error_data))
				{
					foreach ($error_data as $sub_key => $sub_error)
					{
						$_general_error_data[] = array(
							'freeform:error_message' => $sub_error
						);
					}
				}
				else
				{
					$_general_error_data[] = array(
						'freeform:error_message' => $error_data
					);
				}
			}

			$general_error_data = $_general_error_data;
		}

		$variables['freeform:general_errors'] = $general_error_data;

		//have to do this so the conditional will work,
		//seems that parse variables doesn't think a non-empty array = YES
		$tagdata = ee()->functions->prep_conditionals(
			$tagdata,
			array('freeform:general_errors' => ! empty($general_error_data))
		);

		// -------------------------------------
		//	apply replace tag to our field data
		// -------------------------------------

		$field_parse = ee()->freeform_fields->apply_field_method(array(
			'method'			=> 'display_field',
			'form_id'			=> $form_id,
			'entry_id'			=> $entry_id,
			'form_data'			=> $form_data,
			'field_input_data'	=> $field_input_data,
			'tagdata'			=> $tagdata
		));

		$this->multipart 	= $field_parse['multipart'];
		$variables 			= array_merge($variables, $field_parse['variables']);
		$tagdata 			= $field_parse['tagdata'];

		// -------------------------------------
		//	dynamic recipient list
		// -------------------------------------

		$this->params['recipients']		= (
			! in_array(ee()->TMPL->fetch_param('recipients'), array(FALSE, ''))
		);

		//preload list with usable info if so
		$this->params['recipients_list'] = array();

		if ( $this->params['recipients'] )
		{
			$i				= 1;
			$while_limit	= 1000;
			$counter		= 0;

			while ( ! in_array(ee()->TMPL->fetch_param('recipient' . $i), array(FALSE, '')) )
			{
				$recipient = explode('|', ee()->TMPL->fetch_param('recipient' . $i));

				//has a name?
				if ( count($recipient) > 1)
				{
					$recipient_name		= trim($recipient[0]);
					$recipient_email	= trim($recipient[1]);
				}
				//no name, we assume its just an email
				//(though, this makes little sense, it needs a name to be useful)
				else
				{
					$recipient_name		= '';
					$recipient_email	= trim($recipient[0]);
				}

				$recipient_selected = FALSE;

				if (isset($previous_inputs['hash_stored_data']['recipient_emails']) AND
					is_array($previous_inputs['hash_stored_data']['recipient_emails']))
				{
					$recipient_selected = in_array(
						$recipient_email,
						$previous_inputs['hash_stored_data']['recipient_emails']
					);
				}

				//add to list
				$this->params['recipients_list'][$i] = array(
					'name'		=> $recipient_name,
					'email'		=> $recipient_email,
									//because this wasn't being unique enough
									//on stupid windows servers *sigh*
					'key'		=> uniqid('', true),
					'selected'	=> $recipient_selected
				);

				$i++;

				//extra protection because while loops are scary
				if (++$counter >= $while_limit)
				{
					break;
				}
			}

			//if we end up with nothing, then lets not attempt later
			if (empty($this->params['recipients_list']))
			{
				$this->params['recipients'] = FALSE;
			}
		}

		//	----------------------------------------
		//	parse {captcha}
		//	----------------------------------------

		$variables['freeform:captcha'] = FALSE;

		if ($this->params['require_captcha'])
		{
			$variables['freeform:captcha'] = ee()->functions->create_captcha();
		}

		// -------------------------------------
		//	dynamic recipient tagdata
		// -------------------------------------

		if ( $this->params['recipients'] AND
			count($this->params['recipients_list']) > 0)
		{
			$variables['freeform_recipients'] = array();

			$recipient_list 	= $this->params['recipients_list'];

			//dynamic above starts with 1, so does this
			for ( $i = 1, $l = count($recipient_list); $i <= $l; $i++ )
			{
				$variables['freeform:recipient_name' . $i] = $recipient_list[$i]['name'];
				$variables['freeform:recipient_value' . $i] = $recipient_list[$i]['key'];
				$variables['freeform:recipient_selected' . $i] = $recipient_list[$i]['selected'];

				$variables['freeform:recipients'][] = array(
					'freeform:recipient_name' 		=> $recipient_list[$i]['name'],
					'freeform:recipient_value'		=> $recipient_list[$i]['key'],
					'freeform:recipient_count'		=> $i,
					//selected from hash data from multipages
					'freeform:recipient_selected' 	=> $recipient_list[$i]['selected']
				);
			}
		}

		// -------------------------------------
		//	status pairs
		// -------------------------------------

		$tagdata = $this->parse_status_tags($tagdata);

		//	----------------------------------------
		//	'freeform_module_pre_form_parse' hook.
		//	 - This allows developers to change data before tagdata processing.
		//	----------------------------------------

		$this->variables = $variables;

		if (ee()->extensions->active_hook('freeform_module_pre_form_parse') === TRUE)
		{
			$backup_tagdata = $tagdata;

			$tagdata = ee()->extensions->universal_call(
				'freeform_module_pre_form_parse',
				$tagdata,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

						//valid data?
			if ( (! is_string($tagdata) OR empty($tagdata)) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$tagdata = $backup_tagdata;
			}
		}
		//	----------------------------------------

		//extra precaution in case someone hoses this
		if (isset($this->variables) AND is_array($this->variables))
		{
			$variables = $this->variables;
		}

		// -------------------------------------
		//	parse external vars
		// -------------------------------------

		$outer_template_vars['freeform:form_page']			= $current_page;
		$outer_template_vars['freeform:form_page_total']	= $page_total;
		$outer_template_vars['freeform:form_name']			= $form_data['form_name'];
		$outer_template_vars['freeform:form_label']			= $form_data['form_label'];

		ee()->TMPL->template = ee()->functions->prep_conditionals(
			ee()->TMPL->template,
			$outer_template_vars
		);

		ee()->TMPL->template = ee()->functions->var_swap(
			ee()->TMPL->template,
			$outer_template_vars
		);

		// -------------------------------------
		//	parse all vars
		// -------------------------------------

		$tagdata = ee()->TMPL->parse_variables(
			$tagdata,
			array(array_merge($outer_template_vars,$variables))
		);

		// -------------------------------------
		//	this doesn't force ana ajax request
		//	but instead forces it _not_ to be
		//	if the ajax param = 'no'
		// -------------------------------------

		if ( ! $this->params['ajax'])
		{
			$hidden_fields['ajax_request'] = 'no';
		}

		//-------------------------------------
		//	build form
		//-------------------------------------

		$return .= $this->build_form(array(
			'action'			=> $this->get_action_url('save_form'),
			'method'			=> 'post',
			'hidden_fields'		=> array_merge($hidden_fields, array(
				// 	no more params can be set after this
				'params_id' => $this->insert_params(),
			)),
			'tagdata'			=> $tagdata
		));

		//	----------------------------------------
		//	'freeform_module_form_end' hook.
		//	 - This allows developers to change the form before output.
		//	----------------------------------------

		if (ee()->extensions->active_hook('freeform_module_form_end') === TRUE)
		{
			$backup_return = $return;

			$return = ee()->extensions->universal_call(
				'freeform_module_form_end',
				$return,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;

			//valid data?
			if ( (! is_string($return) OR empty($return)) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$return = $backup_return;
			}
		}
		//	----------------------------------------

		return $return;
	}
	//END form


	// -------------------------------------
	//	action requests
	// -------------------------------------


	// --------------------------------------------------------------------

	/**
	 * ajax_validate
	 *
	 * does a save form that stops after validation
	 *
	 * @access	public
	 * @return	mixed 	ajax request
	 */

	public function ajax_validate_form ()
	{
		return $this->save_form(TRUE);
	}
	//END ajax_validate


	// --------------------------------------------------------------------

	/**
	 * save_form
	 *
	 * form save from front_end/action request
	 *
	 * @access	public
	 * @param 	bool validate only
	 * @return	null
	 */

	public function save_form($validate_only = FALSE)
	{
		if ( ! $validate_only AND REQ !== 'ACTION' AND ! $this->test_mode)
		{
			return;
		}

		ee()->load->library('freeform_forms');
		ee()->load->library('freeform_fields');
		ee()->load->model('freeform_form_model');

		if (ee()->input->get_post('params_id') === FALSE)
		{
			return $this->pre_validation_error(
				lang('missing_post_data') . ' - params_id'
			);
		}

		// -------------------------------------
		//	require logged in?
		// -------------------------------------

		if ($this->param('require_logged_in') AND
			ee()->session->userdata['member_id'] == '0')
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('not_logged_in')
			);
		}

		// -------------------------------------
		//	blacklist, banned
		// -------------------------------------

		if (ee()->session->userdata['is_banned'] OR (
				$this->check_yes(ee()->blacklist->blacklisted) AND
				$this->check_no(ee()->blacklist->whitelisted)
			)
		)
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('reason_banned')
			);
		}

		// -------------------------------------
		//	require ip? (except admin)
		// -------------------------------------

		if ($this->param('require_ip'))
		{
			if (ee()->input->ip_address() == '0.0.0.0')
			{
				return $this->pre_validation_error(
					lang('not_authorized') . ' - ' .
					lang('reason_ip_required')
				);
			}
		}

		// -------------------------------------
		//	Is the nation of the user banned?
		// -------------------------------------

		if ($this->nation_ban_check(FALSE))
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				ee()->config->item('ban_message')
			);
		}

		

		// -------------------------------------
		//	Keyword banning?
		// -------------------------------------

		if ($this->check_yes($this->preference('spam_keyword_ban_enabled')))
		{
			if (ee()->freeform_forms->check_keyword_banning())
			{
				return $this->pre_validation_error(
					$this->preference('spam_keyword_ban_message')
				);
			}
		}

		

		// -------------------------------------
		//	valid form id
		// -------------------------------------

		$form_id = $this->form_id(FALSE, FALSE);

		if ( ! $form_id)
		{
			return $this->pre_validation_error(lang('invalid_form_id'));
		}

		// -------------------------------------
		//	is this an edit? entry_id
		// -------------------------------------

		$entry_id 		= $this->entry_id();

		$edit 			= ($entry_id AND $entry_id != 0);

		// -------------------------------------
		//	for multipage check later
		// -------------------------------------

		$multipage			= $this->param('multipage');
		$current_page		= $this->param('current_page');
		$last_page			= $this->param('last_page');
		$previous_inputs	= array();

		

		if ($multipage)
		{
			ee()->freeform_forms->hash_clean_up();

			$hash = ee()->freeform_forms->check_multipage_hash(
				$form_id,
				$entry_id,
				$edit
			);

			if ( $hash )
			{
				$previous_inputs =	ee()->freeform_forms
										->get_multipage_form_data(
											$form_id,
											$hash
										);
			}
			//either the previous inputs could not be found,
			//or the hash was bad
			else
			{
				//if we are redirecting on timeout
				//lets move on
				if ($this->param('redirect_on_timeout') AND
					$this->param('redirect_on_timeout_to') !== '')
				{
					ee()->functions->redirect(
						$this->prep_url(
							$this->param('redirect_on_timeout_to')
						)
					);

					return $this->do_exit();
				}
				//if they don't want to redirect on timeout... uh new hash?
				else
				{
					$hash = ee()->freeform_forms->start_multipage_hash(
						$form_id,
						$entry_id,
						$edit
					);
				}
			}
		}

		

		// -------------------------------------
		//	form data
		// -------------------------------------

		$this->form_data = $form_data = $this->data->get_form_info($form_id);
		$field_labels		= array();
		$valid_fields		= array();
		$column_names		= array();

		foreach ( $form_data['fields'] as $row)
		{
			$field_labels[$row['field_name']] 	= $row['field_label'];
			$valid_fields[] 					= $row['field_name'];
			//fill previous inputs names correctly

			$column_name = 'form_field_' . $row['field_id'];

			if (isset($previous_inputs[$column_name]))
			{
				$previous_inputs[$row['field_name']] = $previous_inputs[$column_name];
			}

			$column_names[$row['field_name']] = $column_name;
		}

		// -------------------------------------
		//	for hooks
		// -------------------------------------

		$this->edit			= $edit;
		$this->multipage	= $multipage;
		$this->last_page	= $last_page;

		// -------------------------------------
		//	user email max/spam count
		// -------------------------------------

		ee()->load->library('freeform_notifications');

		if ($last_page AND ($this->param('recipient_user_input') OR
			 $this->param('recipients')) AND
			 ee()->freeform_notifications->check_spam_interval($form_id)
		)
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('email_limit_exceeded')
			);
		}

		// -------------------------------------
		//	Check for duplicate
		// -------------------------------------

		$duplicate = FALSE;

		if ($this->param('prevent_duplicate_on'))
		{
			$duplicate = ee()->freeform_forms->check_duplicate(
				$form_id,
				$this->param('prevent_duplicate_on'),
				ee()->input->get_post(
					$this->param('prevent_duplicate_on'),
					TRUE
				),
				$this->param('prevent_duplicate_per_site')
			);
		}

		if ($duplicate)
		{
			return $this->pre_validation_error(lang('no_duplicates'));
		}

		// -------------------------------------
		//	pre xid check
		// -------------------------------------
		// 	we aren't going to delete just yet
		// 	because if they have input errors
		// 	then we want to keep this xid for a bit
		// 	and only delete xid on success
		// -------------------------------------
		// 	EE 2.7+ does this automatically for
		// 	all POSTS front end and back now
		// 	so this is going to cause errors
		// 	if we check it there.
		// -------------------------------------

		if (version_compare($this->ee_version, '2.7.0', '<') &&
			! ee()->security->check_xid(ee()->input->post('XID')))
		{
			return $this->pre_validation_error(
				lang('not_authorized') . ' - ' .
				lang('reason_secure_form_timeout')
			);
		}

		// -------------------------------------
		//	pre-validate hook
		// -------------------------------------

		$errors				= array();
		//have to do this weird for backward compat
		$this->field_errors = array();

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
		//	require fields
		// -------------------------------------

		if ($this->param('required'))
		{
			$required = $this->actions()->pipe_split($this->param('required'));

			foreach ($required as $required_field)
			{
				//require need to work for recipients and recipient email user
				$valid_require = array_merge(
					$valid_fields,
					array('recipient_email_user', 'recipient_email')
				);

				$require_labels = $field_labels;
				$require_labels['recipient_email_user']	= lang('user_recipients');
				$require_labels['recipient_email']		= lang('dynamic_recipients');

				//just in case someone misspelled a require
				//or removes a field after making the require list
				if ( ! in_array($required_field, $valid_require))
				{
					continue;
				}

				$gp_value = ee()->input->get_post($required_field);

				if ( (
						//empty array
						(is_array($gp_value) AND count($gp_value) < 1) OR
						//empty string or false
						( ! is_array($gp_value) AND trim((string) $gp_value) === '') OR
						//recipient email <select> default is '0'
						(
							$required_field === 'recipient_email' AND
							$gp_value === '0'
						)
					)
					//required field could be a file
					//check to see if something uploaded for that name
					AND ! $this->actions()->file_upload_present(
						$required_field,
						$previous_inputs
					)
				)
				{
					$this->field_errors[
						$required_field
					] = lang('required_field_missing');


					//only want the postfixing of errors
					//if we are sending to general errors screen
					//or an error page
					//the second conditional is for people requesting
					//the custom error page via ajax
					if ( ! $this->param('inline_errors') AND
						 ! ($this->is_ajax_request() AND
							! trim($this->param('error_page'))))
					{
						$this->field_errors[$required_field] .= ': '.
										$require_labels[$required_field];
					}
				}
			}
		}

		// -------------------------------------
		//	matching fields
		// -------------------------------------

		if ($this->param('matching_fields'))
		{
			$matching_fields = $this->actions()->pipe_split($this->param('matching_fields'));

			foreach ($matching_fields as $match_field)
			{

				//just in case someone misspelled a require
				//or removes a field after making the require list
				if ( ! in_array($match_field, $valid_fields))
				{
					continue;
				}

				//array comparison is correct in PHP and this should work
				//no matter what.
				//normal validation will fix other issues
				if ( ee()->input->get_post($match_field) == FALSE OR
					 ee()->input->get_post($match_field . '_confirm') == FALSE OR
					 ee()->input->get_post($match_field) !==
						ee()->input->get_post($match_field . '_confirm')
				)
				{
					$this->field_errors[$match_field] = lang('fields_do_not_match') .
										$field_labels[$match_field] .
										' | ' .
										$field_labels[$match_field] .
										' ' .
										lang('confirm');
				}
			}
		}

		// -------------------------------------
		//	validate dynamic recipients
		// 	no actual validation errors
		// 	will throw here, but in case we do
		// 	in the future
		// -------------------------------------

		$recipient_emails = array();

		if ($this->param('recipients'))
		{
			$recipient_email_input = ee()->input->get_post('recipient_email');

			if ( ! in_array($recipient_email_input, array(FALSE, ''), TRUE))
			{
				if ( ! is_array($recipient_email_input))
				{
					$recipient_email_input = array($recipient_email_input);
				}

				// recipients are encoded, so lets check for keys
				// since dynamic recipients are dev inputted
				// we aren't going to error on invalid ones
				// but rather just accept if present, and move on if not

				$recipients_list	= $this->param('recipients_list');
				$field_out			= '';

				foreach($recipients_list as $i => $r_data)
				{
					if (in_array($r_data['key'], $recipient_email_input))
					{
						$recipient_emails[] = $r_data['email'];
						$field_out .= $r_data['name'] . ' <' . $r_data['email'] . '>' . "\n";
					}
				}

				//THE ENGLISH ARE TOO MANY!
				if (count($recipient_emails) > $this->param('recipients_limit'))
				{
					$errors['recipient_email'] = lang('over_recipient_limit');
				}

				//does the user have a recipient_email custom field?
				else if (in_array('recipient_email', $valid_fields))
				{
					$_POST['recipient_email'] = trim($field_out);
				}
			}

			//if there is previous recipient emails
			if (empty($recipient_emails) AND
				! empty($previous_inputs['hash_stored_data']['recipient_emails']))
			{
				$recipient_emails = $previous_inputs['hash_stored_data']['recipient_emails'];
			}
		}

		// -------------------------------------
		//	validate user inputted emails
		// -------------------------------------

		$user_recipient_emails = array();

		if ($this->param('recipient_user_input'))
		{
			$user_recipient_email_input = ee()->input->get_post('recipient_email_user');

			if ( ! in_array($user_recipient_email_input, array(FALSE, ''), TRUE))
			{
				$user_recipient_emails = $this->validate_emails($user_recipient_email_input);

				$user_recipient_emails = $user_recipient_emails['good'];

				//if we are here that means we submitted at least something
				//but nothing passed
				if (empty($user_recipient_emails))
				{
					$errors['recipient_user_input'] = lang('no_valid_recipient_emails');
				}
				else if (count($user_recipient_emails) > $this->param('recipient_user_limit'))
				{
					$errors['recipient_email_user'] = lang('over_recipient_user_limit');
				}
			}

			//if there is previous user recipient emails
			if (empty($user_recipient_emails) AND
				! empty($previous_inputs['hash_stored_data']['user_recipient_emails']))
			{
				$user_recipient_emails = $previous_inputs['hash_stored_data']['user_recipient_emails'];
			}
		}

		// -------------------------------------
		//	validate status
		// -------------------------------------

		$status				= $form_data['default_status'];
		$input_status		= ee()->input->post('status', TRUE);
		$param_status		= $this->param('status');
		$available_statuses	= $this->data->get_form_statuses();

		//user status input
		if ($this->param('allow_status_edit') AND
			$input_status !== FALSE AND
			array_key_exists($input_status, $available_statuses))
		{
			$status = $input_status;
		}
		//status param
		else if ($param_status !== $status AND
				array_key_exists($param_status, $available_statuses))
		{
			$status = $param_status;
		}

		// -------------------------------------
		//	validate
		// -------------------------------------

		$field_input_data	= array();

		$field_list			= array();

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			$field_list[$field_data['field_name']] = $field_data['field_label'];

			$field_post = ee()->input->post($field_data['field_name'], TRUE);

			//if it's not even in $_POST or $_GET, lets skip input
			//unless its an uploaded file, then we'll send false anyway
			//because its field type will handle the rest of that work
			if ($field_post !== FALSE OR
				isset($_FILES[$field_data['field_name']]))
			{
				$field_input_data[$field_data['field_name']] = $field_post;
			}
		}

		//form fields do their own validation,
		//so lets just get results! (sexy results?)
		$this->field_errors = array_merge(
			$this->field_errors,
			ee()->freeform_fields->validate(
				$form_id,
				$field_input_data,
				! ($this->is_ajax_request() OR $this->param('inline_errors'))
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

		// -------------------------------------
		//	captcha
		// -------------------------------------


		if ( ! $validate_only AND
			ee()->input->get_post('validate_only') === FALSE AND
			$last_page AND
			$this->param('require_captcha'))
		{
			if ( trim(ee()->input->post('captcha')) == '')
			{
				$errors[] = lang('captcha_required');
			}
			else
			{
				ee()->db->from('captcha');
				ee()->db->where(array(
					'word'			=> ee()->input->post('captcha'),
					'ip_address'	=> ee()->input->ip_address(),
					'date >'		=> ee()->localize->now - 7200
				));

				if (ee()->db->count_all_results() == 0)
				{
					$errors[] = lang('captcha_required');
				}
			}
		}

		$all_errors = array_merge($errors, $this->field_errors);

		// -------------------------------------
		//	halt on errors
		// -------------------------------------

		if (count($all_errors) > 0)
		{
			// -------------------------------------
			//	EE 2.7 auto removes XID so we
			//	have to restore it on errors where
			//	we want the back button to work
			// -------------------------------------

			if (version_compare($this->ee_version, '2.7', '>='))
			{
				ee()->security->restore_xid();
			}

			// -------------------------------------
			//	inline errors
			// -------------------------------------

			if ($this->param('inline_errors'))
			{
				ee()->load->model('freeform_param_model');

				$error_param_id = ee()->freeform_param_model->insert_params(
					array(
						'general_errors'	=> $errors,
						'field_errors'		=> $this->field_errors,
						'inputs'			=> $field_input_data,
						'stored_data'		=> array(
							'recipient_emails'		=> $recipient_emails,
							'user_recipient_emails'	=> $user_recipient_emails
						)
					)
				);

				ee()->session->set_flashdata('freeform_errors', $error_param_id);

				ee()->functions->redirect(
					$this->prep_url(
						$this->param('inline_error_return'),
						$this->param('secure_return')
					)
				);
				return $this->do_exit();
			}

			

			// -------------------------------------
			//	error page
			// -------------------------------------

			if (trim($this->param('error_page')) !== '')
			{
				return $this->error_page($all_errors);
			}
			

			// -------------------------------------
			//	ajax or standard user error
			// -------------------------------------

			$this->actions()->full_stop($all_errors);
		}

		//send ajax response exists
		//but this is in case someone is using a replacer
		//that uses
		if ($validate_only OR ee()->input->get_post('validate_only') !== FALSE)
		{
			if ($this->is_ajax_request())
			{
				$this->restore_xid();
				$this->send_ajax_response(array(
					'success'	=> TRUE,
					'errors'	=> array()
				));
			}

			return $this->do_exit();
		}

		// -------------------------------------
		//	status
		// -------------------------------------

		$field_input_data['status'] = $status;

		// -------------------------------------
		//	entry insert begin hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_insert_begin') === TRUE)
		{
			$backup_data = $field_input_data;

			$field_input_data = ee()->extensions->universal_call(
				'freeform_module_insert_begin',
				$field_input_data,
				$entry_id,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE)
			{
				return;
			}

			// -------------------------------------
			//	if some butthead doesn't return
			//	the array from their extension
			//	we need a backup here or everything
			//	busts.
			// -------------------------------------

			if ( ! is_array($field_input_data) AND
				 $this->check_yes($this->preference('hook_data_protection')))
			{
				$field_input_data = $backup_data;
			}
		}

		// -------------------------------------
		//	insert/update data into db
		// -------------------------------------


		$submit_to_previous = (ee()->input->get_post('submit_to_previous') !== FALSE);

		if ($multipage)
		{
			$entry_id = ee()->freeform_forms->store_multipage_entry(
				$form_id,
				$field_input_data,
				$hash,		//cookie will get updated
				($last_page && ! $submit_to_previous),	//if true will finish up for us
				array(
					'recipient_emails'		=> $recipient_emails,
					'user_recipient_emails' => $user_recipient_emails
				)
			);
		}
		else if ($edit)
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
		//	entry insert end hook
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

			if (ee()->extensions->end_script === TRUE)
			{
				return;
			}
		}

		// -------------------------------------
		//	delete xid and captcha
		// -------------------------------------
		//	wait this late because we dont
		//	want to remove before a custom field
		// 	has a chance to throw an error
		// 	on one of its actions, like file
		//	upload
		// -------------------------------------

		if ($last_page AND $this->param('require_captcha'))
		{
			ee()->db->where(array(
				'word'			=> ee()->input->post('captcha'),
				'ip_address'	=> ee()->input->ip_address()
			));
			ee()->db->or_where('date <', ee()->localize->now - 7200);
			ee()->db->delete('captcha');
		}

		if ($this->check_yes(ee()->config->item('secure_forms')) )
		{
			ee()->security->delete_xid(ee()->input->post('XID'));
		}

		// -------------------------------------
		//	if we are multipaging and going
		//	to the previous page, bail early
		// -------------------------------------

		if ($multipage &&
			$submit_to_previous &&
			$this->param('multipage_previous_page'))
		{
			ee()->functions->redirect(
				$this->prep_url(
					$this->param('multipage_previous_page'),
					$this->param('secure_return')
				)
			);

			return $this->do_exit();
		}

		// -------------------------------------
		//	if we are multi-paging, move on
		// -------------------------------------

		if ($multipage AND ! $last_page)
		{
			ee()->functions->redirect(
				$this->prep_url(
					$this->param('multipage_next_page'),
					$this->param('secure_return')
				)
			);
			return $this->do_exit();
		}

		// -------------------------------------
		//	edit data for notifications?
		// -------------------------------------

		if ($edit)
		{
			$edit_data = $this->data->get_entry_data_by_id($entry_id, $form_id);

			if (is_array($previous_inputs))
			{
				$previous_inputs = array_merge($edit_data, $previous_inputs);
			}
			else
			{
				$previous_inputs = $edit_data;
			}
		}

		// -------------------------------------
		//	previous inputs need their real names
		// -------------------------------------

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if (is_array($previous_inputs))
			{
				$fid = ee()->freeform_form_model->form_field_prefix . $field_id;

				//id name? field_id_1, etc
				if (isset($previous_inputs[$fid]))
				{
					$previous_inputs[$field_data['field_name']] = $previous_inputs[$fid];
				}
			}
		}

		$field_input_data = array_merge(
			(is_array($previous_inputs) ? $previous_inputs : array()),
			array('entry_id' => $entry_id),
			$field_input_data
		);

		// -------------------------------------
		//	do notifications
		// -------------------------------------

		if ( ! $edit OR $this->param('notify_on_edit'))
		{
			if ($this->param('notify_admin'))
			{
				ee()->freeform_notifications->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'admin',
					'recipients'		=> $this->param('admin_notify'),
					'form_input_data'	=> $field_input_data,
					'cc_recipients'		=> $this->param('admin_cc_notify'),
					'bcc_recipients'	=> $this->param('admin_bcc_notify'),
					'template'			=> $this->param('admin_notification_template')
				));
			}

			//this is a custom field named by the user
			//notifications does its own validation
			//so if someone puts a non-validated input field
			//then notifications will just silently fail
			//because it wont be a user input problem
			//but rather a dev implementation problem
			if ($this->param('notify_user') AND
				$this->param('user_email_field') AND
				isset($field_input_data[$this->param('user_email_field')]))
			{
				ee()->freeform_notifications->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user',
					'recipients'		=> $field_input_data[$this->param('user_email_field')],
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('user_notification_template'),
					'enable_spam_log'	=> FALSE
				));
			}

			//recipients
			if ( ! empty($recipient_emails))
			{
				ee()->freeform_notifications->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user_recipient',
					'recipients'		=> $recipient_emails,
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('recipient_template')
				));
			}

			//user inputted recipients
			if ( ! empty($user_recipient_emails))
			{
				ee()->freeform_notifications->send_notification(array(
					'form_id'			=> $form_id,
					'entry_id'			=> $entry_id,
					'notification_type'	=> 'user_recipient',
					'recipients'		=> $user_recipient_emails,
					'form_input_data'	=> $field_input_data,
					'template'			=> $this->param('recipient_user_template')
				));
			}
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		$return_url = $this->param('return');

		if (ee()->input->post('return') !== FALSE)
		{
			$return_url = ee()->input->post('return');
		}

		$return = str_replace(
			//because. Shut up.
			array(
				'%%form_entry_id%%',
				'%%entry_id%%',
				'%form_entry_id%',
				'%entry_id%'
			),
			$entry_id,
			$this->prep_url(
				$return_url,
				$this->param('secure_return')
			)
		);

		//detergent?
		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success'		=> TRUE,
				'entry_id'		=> $entry_id,
				'form_id'		=> $form_id,
				'return'		=> $return,
				'return_url'	=> $return
			));
		}
		else
		{
			ee()->functions->redirect($return);
		}
	}
	//END save_form


	// --------------------------------------------------------------------
	//	private! No looky!
	// --------------------------------------------------------------------


	// --------------------------------------------------------------------

	/**
	 * Pre-Validation Errors that are deal breakers
	 *
	 * @access	protected
	 * @param	mixed		$errors	error string or array of errors
	 * @return	null		exits
	 */

	protected function pre_validation_error($errors)
	{
		// -------------------------------------
		//	NOTE: These are pre-validation errors
		//	that are only supposed to be things
		//	that are fatal to the submitting
		//	of the form. This we are _not_
		//	restoring XIDs here (EE 2.7+)
		//	because its not a standard user
		//	error where the back button is relevant.
		// -------------------------------------

		if ($this->param('inline_errors'))
		{
			ee()->load->model('freeform_param_model');

			$error_param_id = ee()->freeform_param_model->insert_params(
				array(
					'general_errors'	=> is_array($errors) ? $errors : array($errors),
					'field_errors'		=>	array(),
					'inputs'			=> array()
				)
			);

			ee()->session->set_flashdata('freeform_errors', $error_param_id);

			ee()->functions->redirect(
				$this->prep_url(
					$this->param('inline_error_return'),
					$this->param('secure_return')
				)
			);
			return $this->do_exit();
		}

		
		if (trim($this->param('error_page')) !== '')
		{
			return $this->error_page($errors);
		}
		

		return $this->actions()->full_stop($errors);
	}
	//END pre_validation_error


	// --------------------------------------------------------------------

	/**
	 * build_form
	 *
	 * builds a form based on passed data
	 *
	 * @access	private
	 * @
	 * @return 	mixed  	boolean false if not found else id
	 */

	private function build_form( $data )
	{
		// -------------------------------------
		//	prep input data
		// -------------------------------------

		//set defaults for optional items
		$input_defaults	= array(
			'action' 			=> '/',
			'hidden_fields' 	=> array(),
			'tagdata'			=> ee()->TMPL->tagdata,
		);

		//array2 overwrites any duplicate key from array1
		$data 			= array_merge($input_defaults, $data);

		//OK, so form_open is supposed to be doing this,
		//but guess what: It only works if CI sees that
		//config->item('csrf_protection') === true, and uh
		//sometimes it's false eventhough secure_forms == 'y'
		//
		if ( $this->check_yes(ee()->config->item('secure_forms')) )
		{
			$data['hidden_fields']['XID'] = $this->create_xid();
		}

		// --------------------------------------------
		//  HTTPS URLs?
		// --------------------------------------------

		$data['action'] = $this->prep_url(
			$data['action'],
			(
				isset($this->params['secure_action']) AND
				$this->params['secure_action']
			)
		);


		foreach(array('return', 'RET') as $return_field)
		{
			if (isset($data['hidden_fields'][$return_field]))
			{
				$data['hidden_fields'][$return_field] = $this->prep_url(
					$data['hidden_fields'][$return_field],
					(
						isset($this->params['secure_return']) AND
						$this->params['secure_return']
					)
				);
			}
		}

		// --------------------------------------------
		//  Override Form Attributes with form:xxx="" parameters
		// --------------------------------------------

		$form_attributes = array();

		if (is_object(ee()->TMPL) AND ! empty(ee()->TMPL->tagparams))
		{
			foreach(ee()->TMPL->tagparams as $key => $value)
			{
				if (strncmp($key, 'form:', 5) == 0)
				{
					//allow action override.
					if (substr($key, 5) == 'action')
					{
						$data['action'] = $value;
					}
					else
					{
						$form_attributes[substr($key, 5)] = $value;
					}
				}
			}
		}

		// --------------------------------------------
		//  Create and Return Form
		// --------------------------------------------

		//have to have this for file uploads
		if ($this->multipart)
		{
			$form_attributes['enctype'] = 'multipart/form-data';
		}

		$form_attributes['method'] = $data['method'];

		$return		= form_open(
			$data['action'],
			$form_attributes,
			$data['hidden_fields']
		);

		$return		.= stripslashes($data['tagdata']);

		$return		.= "</form>";

		return $return;
	}
	//END build_form


	// --------------------------------------------------------------------

	/**
	 * form_id - finds form id the best it can
	 *
	 * @access	private
	 * @param   bool 	$allow_multiple allow multiple input?
	 * @return 	mixed  	boolean false if not found else id
	 */

	private function form_id($allow_multiple = FALSE, $use_cache = TRUE)
	{
		if ($use_cache AND $this->form_id)
		{
			return $this->form_id;
		}

		$form_id		= FALSE;
		$possible_name	= FALSE;
		$possible_label	= FALSE;
		$possible_id	= FALSE;
		$tmpl_available	= (isset(ee()->TMPL) AND is_object(ee()->TMPL));
		// -------------------------------------
		//	by direct param first
		// -------------------------------------

		if ($tmpl_available)
		{
			$possible_id = ee()->TMPL->fetch_param('form_id');
		}

		// -------------------------------------
		//	by name param
		// -------------------------------------

		if ( ! $possible_id AND $tmpl_available)
		{
			$possible_name = ee()->TMPL->fetch_param('form_name');
		}

		// -------------------------------------
		//	by label (with legacy for collection)
		// -------------------------------------

		if ($tmpl_available AND ! $possible_id AND ! $possible_name)
		{
			$possible_label = ee()->TMPL->fetch_param('form_label');

			if ( ! $possible_label)
			{
				$possible_label = ee()->TMPL->fetch_param('collection');
			}
		}

		// -------------------------------------
		//	params id
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label AND
			 $this->param('form_id'))
		{
			$possible_id = $this->param('form_id');
		}

		// -------------------------------------
		//	params name
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label AND
			 $this->param('form_name'))
		{
			$possible_name = $this->param('form_name');
		}

		// -------------------------------------
		//	get post id
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label)
		{
			$possible_id = ee()->input->get_post('form_id');
		}

		// -------------------------------------
		//	get post name
		// -------------------------------------

		if ( ! $possible_id AND
			 ! $possible_name AND
			 ! $possible_label)
		{
			$possible_name = ee()->input->get_post('form_name');
		}

		// -------------------------------------
		//	check possibles
		// -------------------------------------

		if ($possible_id)
		{
			//if multiple and match pattern...
			if ($allow_multiple AND preg_match('/^[\d\|]+$/', $possible_id))
			{
				$ids = $this->actions()->pipe_split($possible_id);

				ee()->load->model('freeform_form_model');

				$result = ee()->freeform_form_model->select('form_id')
												   ->get(array('form_id' => $ids));
				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else if ($this->is_positive_intlike($possible_id) AND
					 $this->data->is_valid_form_id($possible_id))
			{
				$form_id = $possible_id;
			}
		}

		if ( ! $form_id AND $possible_name)
		{
			//if multiple and pipe
			if ($allow_multiple AND stristr($possible_name, '|'))
			{
				$names = $this->actions()->pipe_split($possible_name);

				ee()->load->model('freeform_form_model');

				$result = ee()->freeform_form_model->select('form_id')
												   ->get(array('form_name' => $names));

				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else
			{
				$possible_id = $this->data->get_form_id_by_name($possible_name);

				if ($possible_id !== FALSE AND $possible_id > 0)
				{
					$form_id = $possible_id;
				}
			}
		}

		if ( ! $form_id AND $possible_label)
		{
			ee()->load->model('freeform_form_model');

			//if multiple and pipe
			if ($allow_multiple AND stristr($possible_label, '|'))
			{
				$names = $this->actions()->pipe_split($possible_label);

				$result =	ee()->freeform_form_model
								->select('form_id')
								->get(array('form_label' => $names));

				//we only want results, not everything
				if ($result !== FALSE)
				{
					$form_id = array();

					foreach ($result as $row)
					{
						$form_id[] = $row['form_id'];
					}
				}
			}
			else
			{
				$possible_id =	ee()->freeform_form_model
									->select('form_id')
									->get_row(array('form_label' => $possible_label));

				if ($possible_id !== FALSE)
				{
					$form_id = $possible_id['form_id'];
				}
			}
		}

		// -------------------------------------
		//	store if good
		// -------------------------------------

		if ($form_id AND $form_id > 0)
		{
			$this->form_id = $form_id;
		}

		return $form_id;
	}
	//END form_id


	// --------------------------------------------------------------------

	/**
	 * entry_id - finds entry id the best it can
	 *
	 * @access	private
	 * @return 	mixed	boolean false if not found else id
	 */

	private function entry_id()
	{
		$form_id = $this->form_id();

		if ( ! $form_id)
		{
			return FALSE;
		}

		if (isset($this->entry_id) AND $this->entry_id)
		{
			return $this->entry_id;
		}

		$entry_id = FALSE;

		// -------------------------------------
		//	by direct param first
		// -------------------------------------

		if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
		{
			$entry_id_param = ee()->TMPL->fetch_param('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				 $this->data->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	params id
		// -------------------------------------

		if ( ! $entry_id AND $this->param('entry_id'))
		{
			$entry_id_param = $this->param('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				 $this->data->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	get post id
		// -------------------------------------

		if ( ! $entry_id AND ee()->input->get_post('entry_id'))
		{
			$entry_id_param = ee()->input->get_post('entry_id');

			if ( $this->is_positive_intlike($entry_id_param) AND
				  $this->data->is_valid_entry_id($entry_id_param, $form_id))
			{
				$entry_id = $entry_id_param;
			}
		}

		// -------------------------------------
		//	store if good
		// -------------------------------------

		if ($entry_id AND $entry_id > 0)
		{
			$this->entry_id = $entry_id;
		}

		return $entry_id;
	}
	//END entry_id


	// --------------------------------------------------------------------

	/**
	 * Set Form Params
	 *
	 * @access	public
	 * @param	array		incoming form data for defaults
	 * @param	bool		rerun param finding?
	 * @return	array		array of params found
	 */

	public function set_form_params($rerun = FALSE)
	{
		if ( ! $rerun && $this->params_ran)
		{
			return $this->params;
		}

		$params_with_defaults = $this->get_default_params();

		foreach ($params_with_defaults as $name => $default)
		{
			$this->set_form_param($name);
		}

		//we use the bool here because
		//the params array can have items set to it
		//outside of the defaults running here
		$this->params_ran = true;

		return $this->params;
	}
	//set_form_params


	// --------------------------------------------------------------------

	/**
	 * Set Form Param
	 *
	 * @access	public
	 * @param	string	$name			name of param
	 * @param	mixed	$value			optional value to set param to
	 * @param	boolean $set_tagparam [description]
	 */

	public function set_form_param($name, $value = null, $set_tagparam = false)
	{
		$params_with_defaults = $this->get_default_params();

		//not default? lets just set it.
		if ( ! isset($params_with_defaults[$name]))
		{
			if ($value === null)
			{
				$value = ee()->TMPL->fetch_param($name);
			}

			$this->params[$name] = $value;
		}
		else
		{
			$default = $params_with_defaults[$name];

			//if the default is a boolean value we only want a boolean
			//output
			if (is_bool($default))
			{
				if ($value === null)
				{
					$value = ee()->TMPL->fetch_param($name);
				}

				//and if there is a string version of the param
				if (is_string($value))
				{
					//and if the default is boolean true
					if ($default === TRUE)
					{
						//and if the template param uses an indicator of the
						//'false' variety, we want to override the default
						//of TRUE and set FALSE.
						$this->params[$name] = ! $this->check_no($value);
					}
					//but if the default is boolean false
					else
					{
						//and the template param is trying to turn the feature
						//on through a 'y', 'yes', or 'on' value, then we want
						//to convert the FALSE to a TRUE
						$this->params[$name] = $this->check_yes($value);
					}
				}
				//there is no template param version of
				//this default so the default stands
				else
				{
					//for setting tagparam, however rare
					$value = $default;
					$this->params[$name] = $default;
				}
			}
			//other wise check for the param or fallback on default
			else
			{
				if ($value === null)
				{
					$value = trim(
						ee()->TMPL->fetch_param($name, $default)
					);
				}

				$this->params[$name] = $value;
			}

		}

		if ($set_tagparam && $value !== null)
		{
			ee()->TMPL->tagparams[$name] = $value;
		}
	}
	//END set_form_param


	// --------------------------------------------------------------------


	/**
	 * Get Default Params
	 *
	 * Gets default Params and sets them to class var if not alraedy set
	 * @access	public
	 * @return	array	key value array of default params
	 */

	public function get_default_params()
	{
		if (isset($this->params_with_defaults))
		{
			return $this->params_with_defaults;
		}

		if ( ! isset($this->form_data))
		{
			$this->form_data = $this->data->get_form_info($this->form_id());
		}

		$this->params_with_defaults	= array(
			//security
			'secure_action'					=> FALSE,
			'secure_return'					=> FALSE,
			'require_captcha'				=> (
				$this->check_yes(ee()->config->item('captcha_require_members')) OR
				(
					$this->check_no(ee()->config->item('captcha_require_members')) AND
					ee()->session->userdata('member_id') == 0
				)
			),
			'require_ip'					=> ! $this->check_no(
				ee()->config->item("require_ip_for_posting")
			),
			'return'						=> ee()->uri->uri_string,
			'inline_error_return'			=> ee()->uri->uri_string,
			'error_page'					=> '',
			'ajax'							=> TRUE,
			'restrict_edit_to_author'		=> TRUE,

			'inline_errors'					=> FALSE,

			//dupe prevention
			'prevent_duplicate_on'			=> '',
			'prevent_duplicate_per_site'	=> FALSE,
			'secure_duplicate_redirect'		=> FALSE,
			'duplicate_redirect'			=> '',
			'error_on_duplicate'			=> FALSE,

			//required or matching fields
			'required'						=> '',
			'matching_fields'				=> '',

			//multipage
			'last_page'						=> TRUE,
			'multipage'						=> FALSE,
			'redirect_on_timeout'			=> TRUE,
			'redirect_on_timeout_to'		=> '',
			'page_marker'					=> $this->default_mp_page_marker,
			'multipage_page'				=> '',
			'paging_url'					=> '',
			'multipage_page_names'			=> '',

			//notifications
			'admin_notify'					=> $this->form_data['admin_notification_email'],
			'admin_cc_notify'				=> '',
			'admin_bcc_notify'				=> '',
			'notify_user'					=> $this->check_yes($this->form_data['notify_user']),
			'notify_admin'					=> $this->check_yes($this->form_data['notify_admin']),
			'notify_on_edit'				=> FALSE,
			'user_email_field'				=> $this->form_data['user_email_field'],

			//dynamic_recipients
			'recipients'					=> FALSE,
			'recipients_limit'				=> '3',

			//user inputted recipients
			'recipient_user_input'			=> FALSE,
			'recipient_user_limit'			=> '3',

			//templates
			'recipient_template'			=> "",
			'recipient_user_template'		=> "",
			'admin_notification_template'	=> $this->form_data['admin_notification_id'],
			'user_notification_template'	=> $this->form_data['user_notification_id'],

			'status'						=> $this->form_data['default_status'],
			'allow_status_edit'				=> FALSE,
		);

		return $this->params_with_defaults;
	}
	//END get_default_params


	// --------------------------------------------------------------------

	/**
	 * Set Page Positions
	 *
	 * @access	public
	 * @return	array	array of page positions for current page
	 */

	public function set_page_positions()
	{
		if (isset($this->page_positions))
		{
			return $this->page_positions;
		}

		// -------------------------------------
		//	need form params
		// -------------------------------------

		$this->set_form_params();

		// -------------------------------------
		//	defaults
		// -------------------------------------

		$current_page	= 0;
		$next_page		= 0;
		$previous_page	= 0;
		$page_type		= 'int';
		$page_total		= 1;

		// -------------------------------------
		//	page array?
		// -------------------------------------

		$mp_page_array = $this->get_mp_page_array();

		// -------------------------------------
		//	parse
		// -------------------------------------

		if ( ! empty($mp_page_array))
		{
			$page_type = 'text';

			$position		= array_search(
				$this->params['multipage_page'],
				$mp_page_array
			);

			if (in_array($position, array(-1, FALSE), TRUE))
			{
				$position = 0;
			}

			//this will be our human readable
			$current_page		= $position + 1;

			$page_total			= count($mp_page_array);

			$next_page			= ($position + 1 < $page_total) ?
									$mp_page_array[$position + 1] : 0;
			$previous_page		= ($position - 1 >= 0) ?
									$mp_page_array[$position - 1] : 0;

			// -------------------------------------
			//	set paging url
			// -------------------------------------

			if ($this->params['paging_url'] == '')
			{
				$matches = array();

				if ($this->params['multipage_page'])
				{
					//we want to match all and get the last
					preg_match_all(
						'/(?:\/|^)(' .
							preg_quote($this->params['multipage_page'], '/') .
						')(?:\/|$)/',
						ee()->uri->uri_string,
						$matches,
						PREG_SET_ORDER
					);
				}

				$match = array();

				if ($matches)
				{
					$match = array_pop($matches);
				}

				if (isset($match[1]))
				{
					$segs	= explode('/', ee()->uri->uri_string);

					$i		= count($segs);

					while ($i--)
					{
						if ($segs[$i] == $match[1])
						{
							$segs[$i] = '%page%';
							break;
						}
					}

					$this->params['paging_url'] = implode($segs, '/');

					//if they didn't set a redirect on timeout
					//we can assume its this same setup, but with
					//page1 instead of page whatever else.
					if ($this->params['redirect_on_timeout_to'] == '')
					{
						//swap the number from the match, then swap the match
						$this->params['redirect_on_timeout_to'] = str_replace(
							'%page%',
							$mp_page_array[0],
							$this->params['paging_url']
						);
					}
				}
				//END if (isset($match[1]))
			}
			//END if ($this->params['paging_url'] == '')
		}
		//else to if ( ! empty($mp_page_array))
		//manually passed integer?
		else if ($this->params['multipage_page'] != '')
		{
			//remove the marker in case someone sends us a segment
			//with the marker in it like "page2"
			$multipage_page = trim(
				str_replace(
					$this->params['page_marker'],
					'',
					$this->params['multipage_page']
				)
			);

			if ($multipage_page AND
				$this->is_positive_intlike($multipage_page))
			{
				$current_page = $multipage_page;

				$next_page		= ($current_page < $page_total) ?
									$current_page + 1 : 0;

				$previous_page	= ($current_page > 1) ?
									$current_page - 1 : 0;
			}
		}
		//END if ( ! empty($mp_page_array))
		//else if ($this->params['multipage_page'] != '')

		// -------------------------------------
		//	find dynamically?
		// -------------------------------------

		if ($current_page == 0)
		{
			$pm = $this->params['page_marker'];

			//find the page number in /ee/template/page1/
			preg_match(
				'/(?:\/|^)' . preg_quote($pm, '/') . '([0-9]+)(?:\/|$)/',
				ee()->uri->uri_string,
				$matches
			);

			if (isset($matches[1]))
			{
				$current_page = $matches[1];

				//swap the number from the match, then swap the match
				$this->params['paging_url'] = str_replace(
					$pm . $matches[1],
					'%page%',
					ee()->uri->uri_string
				);

				//if they didn't set a redirect on timeout
				//we can assume its this same setup, but with
				//page1 instead of page whatever else.
				if ($this->params['redirect_on_timeout_to'] == '')
				{
					//swap the number from the match, then swap the match
					$this->params['redirect_on_timeout_to'] = str_replace(
						$matches[0],
						str_replace(
							$matches[1],
							'1',
							$matches[0]
						),
						ee()->uri->uri_string
					);
				}
			}
		}

		//if none of that crap worked, page 1
		if ($current_page == 0)
		{
			$current_page = 1;

			$next_page		= ($current_page < $page_total) ?
				$current_page + 1 : 0;

			$previous_page	= ($current_page > 1) ?
				$current_page - 1 : 0;
		}

		$this->page_positions = array(
			'page_type'			=> $page_type,
			'current_page'		=> $current_page,
			'page_total'		=> $page_total,
			'next_page'			=> $next_page,
			'previous_page'		=> $previous_page,
		);

		return $this->page_positions;
	}
	//END set_page_positions


	// --------------------------------------------------------------------

	/**
	 * Get Multipage page array
	 *
	 * @access	public
	 * @return	array	array of multipage pages or empty array
	 */

	public function get_mp_page_array()
	{
		if (isset($this->mp_page_array))
		{
			return $this->mp_page_array;
		}

		$this->set_form_params();

		if ($this->params['multipage_page_names'] != '')
		{
			$mp_page_array = $this->actions()->pipe_split(
				$this->params['multipage_page_names']
			);

			array_walk($mp_page_array, 'trim');

			$this->mp_page_array = $mp_page_array;

			// -------------------------------------
			//	multipage page missing?
			// -------------------------------------

			if ( ! $this->params['multipage_page'])
			{
				foreach ($mp_page_array as $key => $page)
				{
					$mp_page_array[$key] = preg_quote($page, '/');
				}

				$match_string = '/(?:\/|^)(' .
									implode('|', $mp_page_array) .
								')(?:\/|$)/';

				//we want to match all and get the last
				preg_match_all(
					$match_string,
					ee()->uri->uri_string,
					$matches,
					PREG_SET_ORDER
				);

				if ($matches)
				{
					$match = array_pop($matches);

					if ( ! $this->params['multipage_page'])
					{
						$this->params['multipage_page'] = $match[1];
					}
				}
			}
		}
		else
		{
			$this->mp_page_array = array();
		}

		return $this->mp_page_array;
	}
	//END get_mp_page_array


	// --------------------------------------------------------------------

	/**
	 * param - gets stored paramaters
	 *
	 * @access	private
	 * @param	string  $which	which param needed
	 * @param	string  $type	type of param
	 * @return	bool 			$which was empty
	 */

	private function param($which = '', $type = 'all')
	{
		//	----------------------------------------
		//	Params set?
		//	----------------------------------------

		if ( count( $this->params ) == 0 )
		{
			ee()->load->model('freeform_param_model');

			//	----------------------------------------
			//	Empty id?
			//	----------------------------------------

			$params_id = ee()->input->get_post('params_id', TRUE);

			if ( ! $this->is_positive_intlike($params_id) )
			{
				return FALSE;
			}

			$this->params_id = $params_id;

			// -------------------------------------
			//	pre-clean so cache can keep
			// -------------------------------------

			ee()->freeform_param_model->cleanup();

			//	----------------------------------------
			//	Select from DB
			//	----------------------------------------

			$data = ee()->freeform_param_model->select('data')
											  ->get_row($this->params_id);

			//	----------------------------------------
			//	Empty?
			//	----------------------------------------

			if ( ! $data )
			{
				return FALSE;
			}

			//	----------------------------------------
			//	Unserialize
			//	----------------------------------------

			$this->params				= json_decode( $data['data'], TRUE );
			$this->params				= is_array($this->params) ? $this->params : array();
			$this->params['set']		= TRUE;
		}
		//END if ( count( $this->params ) == 0 )


		//	----------------------------------------
		//	Fetch from params array
		//	----------------------------------------

		if ( isset( $this->params[$which] ) )
		{
			$return	= str_replace( "&#47;", "/", $this->params[$which] );

			return $return;
		}

		//	----------------------------------------
		//	Fetch TMPL
		//	----------------------------------------

		if ( isset( ee()->TMPL ) AND
			 is_object(ee()->TMPL) AND
			 ee()->TMPL->fetch_param($which) )
		{
			return ee()->TMPL->fetch_param($which);
		}

		//	----------------------------------------
		//	Return (if which is blank, we are just getting data)
		//	else if we are looking for something that doesn't exist...
		//	----------------------------------------

		return ($which === '');
	}
	//End param


	// --------------------------------------------------------------------

	/**
	 * insert_params - adds multiple params to stored params
	 *
	 * @access	private
	 * @param	array	$param	sassociative array of params to send
	 * @return	mixed			insert id or false
	 */

	private function insert_params( $params = array() )
	{
		ee()->load->model('freeform_param_model');

		if (empty($params) AND isset($this->params))
		{
			$params = $this->params;
		}

		return ee()->freeform_param_model->insert_params($params);
	}
	//	End insert params


	// --------------------------------------------------------------------

	/**
	 * prep_url
	 *
	 * checks a url for {path} or url creation needs with https replacement
	 *
	 * @access	private
	 * @param 	string 	url to be prepped
	 * @param 	bool 	replace http with https?
	 * @return	string 	url prepped with https or not
	 */

	private function prep_url($url, $https = FALSE)
	{
		$return = trim($url);

		$return = ($return !== '') ? $return : ee()->config->item('site_url');

		if ( preg_match( "/".LD."\s*path=(.*?)".RD."/", $return, $match ) > 0 )
		{
			$return	= ee()->functions->create_url( $match['1'] );
		}
		else if ( ! preg_match('/^http[s]?:\/\/|^\/\//', $return) )
		{
			$return	= ee()->functions->create_url( $return );
		}

		if ($https)
		{
			$return = preg_replace('/^http:\/\/|^\/\//', 'https://', $return);
		}

		return $return;
	}
	//end prep_url


	// --------------------------------------------------------------------

	/**
	 * nation ban check
	 *
	 * sessions built in nation ban check doesn't properly
	 * return a bool if show errors are off
	 * and we want ajax responses with this
	 *
	 * @access	private
	 * @param 	bool 	show fatal errors instead of returning bool true
	 * @return	bool 	is banned or now
	 */

	private function nation_ban_check($show_error = TRUE)
	{
		if ( ! $this->check_yes(ee()->config->item('require_ip_for_posting')) OR
			 ! $this->check_yes(ee()->config->item('ip2nation')) OR
			 ! ee()->db->table_exists('exp_ip2nation'))
		{
			return FALSE;
		}

		//2.5.2 has a different table and ipv6 support
		if (version_compare($this->ee_version, '2.5.2', '<='))
		{
			ee()->db->select("country");
			ee()->db->where('ip <', ip2long(ee()->input->ip_address()));
			ee()->db->order_by('ip', 'desc');
			$query = ee()->db->get('ip2nation', 1);
		}
		else
		{
			// all IPv4 go to IPv6 mapped
			$addr = ee()->input->ip_address();

			if (strpos($addr, ':') === FALSE AND
				strpos($addr, '.') !== FALSE)
			{
				$addr = '::'.$addr;
			}

			$addr = inet_pton($addr);

			$query = ee()->db
				->select('country')
				->where("ip_range_low <= '".$addr."'", '', FALSE)
				->where("ip_range_high >= '".$addr."'", '', FALSE)
				->order_by('ip_range_low', 'desc')
				->limit(1, 0)
				->get('ip2nation');
		}

		if ($query->num_rows() == 1)
		{
			$ip2_query = ee()->db->get_where(
				'ip2nation_countries',
				array(
					'code' 		=> $query->row('country'),
					'banned' 	=> 'y'
				)
			);

			if ($ip2_query->num_rows() > 0)
			{
				if ($show_error == TRUE)
				{
					return ee()->output->fatal_error(
						ee()->config->item('ban_message'),
						0
					);
				}
				else
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}
	//END nation_ban_check


	// --------------------------------------------------------------------

	/**
	 * Parse Numeric Array Param
	 *
	 * checks template param for item like 'not 1|2|3'
	 *
	 * @access	private
	 * @param 	string 	name of param to parse
	 * @return	mixed	false if not set, array if set
	 */

	private function parse_numeric_array_param($name = '')
	{
		$return = array();

		if (trim($name) == '')
		{
			return FALSE;
		}

		$name_id = ee()->TMPL->fetch_param($name);

		if ($name_id == FALSE)
		{
			return FALSE;
		}

		$name_id 	= trim(strtolower($name_id));
		$not 		= FALSE;

		if (substr($name_id, 0, 3) == 'not')
		{
			$not 		= TRUE;
			$name_id 	= preg_replace('/^not[\s]*/', '', $name_id);
		}

		$clean_ids = array();

		if ($name_id !== '')
		{
			$name_id = str_replace(
				'CURRENT_USER',
				ee()->session->userdata('member_id'),
				$name_id
			);

			if (stristr($name_id, '|'))
			{
				$name_id = $this->actions()->pipe_split($name_id);
			}

			if ( ! is_array($name_id))
			{
				$name_id = array($name_id);
			}

			foreach ($name_id as $value)
			{
				$value = trim($value);

				if ($this->is_positive_intlike($value))
				{
					$clean_ids[] = $value;
				}
			}
		}

		return array(
			'not' 	=> $not,
			'ids'	=> $clean_ids
		);
	}
	//END parse_numeric_array_param


	// --------------------------------------------------------------------

	/**
	 * Tag Prefix replace
	 *
	 * Takes a set of tags and removes unprefixed tags then removes the
	 * prefixes from the prefixed ones. Sending reverse true re-instates the
	 * unprefixed items
	 *
	 * @param  string  $prefix  prefix for tags
	 * @param  array   $tags    array of tags to look for prefixes with
	 * @param  string  $tagdata incoming tagdata to replace on
	 * @param  boolean $reverse reverse the replacements?
	 * @return string  tagdata with replacements
	 */

	public function tag_prefix_replace($prefix = '', $tags = array(),
										$tagdata = '', $reverse = FALSE)
	{
		if ($prefix == '' OR ! is_array($tags) OR empty($tags))
		{
			return $tagdata;
		}

		//allowing ':' in a prefix
		if (substr($prefix, -1, 1) !== ':')
		{
			$prefix = rtrim($prefix, '_') . '_';
		}


		$hash 	= '02be645684a54f45f08d0b1dbadf78e1a3a9f2ee';

		$find 			= array();
		$hash_replace 	= array();
		$prefix_replace = array();

		$length = count($tags);

		foreach ($tags as $key => $item)
		{
			$nkey = $key + $length;

			//if there is nothing prefixed, we don't want to do anything datastardly
			if ( ! $reverse AND
				strpos($tagdata, LD . $prefix . $item) === FALSE)
			{
				continue;
			}

			//this is terse, but it ensures that we
			//find any an all tag pairs if they occur
			$find[$key] 			= $item;
			$find[$nkey] 			= '/' .  $item;
			$hash_replace[$key] 	= $hash . $item;
			$hash_replace[$nkey] 	= '/' .  $hash . $item;
			$prefix_replace[$key] 	= $prefix . $item;
			$prefix_replace[$nkey] 	= '/' .  $prefix . $item;
		}

		//prefix standard and replace prefixes
		if ( ! $reverse)
		{
			foreach ($find as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$hash_replace[$key],
					$tagdata
				);
			}

			foreach ($prefix_replace as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$find[$key],
					$tagdata
				);
			}

			//$tagdata = str_replace($find, $hash_replace, $tagdata);
			//$tagdata = str_replace($prefix_replace, $find, $tagdata);
		}
		//we are on the return, fix the hashed ones
		else
		{
			//$tagdata = str_replace($hash_replace, $find, $tagdata);
			foreach ($hash_replace as $key => $value)
			{
				$tagdata = preg_replace(
					'/(?<![:_])\b(' . preg_quote($value, '/') . ')\b(?![:_])/ms',
					$find[$key],
					$tagdata
				);
			}
		}

		return $tagdata;
	}
	//END tag_prefix_replace


	// --------------------------------------------------------------------

	/**
	 * Checks first for an error block if present
	 *
	 * @access protected
	 * @param  string	$line	error line
	 * @return string			parsed html tagdata
	 */

	protected function no_results_error($line = '')
	{
		if ($line != '' AND
			preg_match(
				"/".LD."if " .preg_quote($this->lower_name).":error" .
					RD."(.*?)".LD.preg_quote("/", '/')."if".RD."/s",
				ee()->TMPL->tagdata,
				$match
			)
		)
		{
			$error_tag = $this->lower_name . ":error";

			return ee()->TMPL->parse_variables(
				$match[1],
				array(array(
					$error_tag		=> $line,
					'error_message' => lang($line)
				))
			);
		}
		else if ( preg_match(
				"/".LD."if " .preg_quote($this->lower_name).":no_results" .
					RD."(.*?)".LD.preg_quote("/", '/')."if".RD."/s",
				ee()->TMPL->tagdata,
				$match
			)
		)
		{
			return $match[1];
		}
		else
		{
			return ee()->TMPL->no_results();
		}
	}
	//END no_results_error


	// --------------------------------------------------------------------

	/**
	 * Replaces CURRENT_USER in tag params
	 *
	 * @access	protected
	 * @return	null
	 */

	protected function replace_current_user()
	{
		if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
		{
			foreach (ee()->TMPL->tagparams as $key => $value)
			{
				if (stristr($value, 'CURRENT_USER'))
				{
					ee()->TMPL->tagparams[$key] = preg_replace(
						'/(?<![:_])\b(CURRENT_USER)\b(?![:_])/ms',
						ee()->session->userdata('member_id'),
						$value
					);
				}
			}
		}
	}
	//END replace_current_user


	

	// --------------------------------------------------------------------

	/**
	 *	Output Custom Error Template
	 *
	 *	@access		private
	 *	@param		array	$errors	what errors we are going to show
	 *	@param		string	$type	type of error
	 *	@return		string	$html	error output template parsed
	 */

	private function error_page($errors, $type = 'submission')
	{
		$error_return = array();

		if (is_string($errors))
		{
			$errors = array($errors);
		}

		foreach ($errors as $error_set => $error_data)
		{
			if (is_array($error_data))
			{
				foreach ($error_data as $sub_key => $sub_error)
				{
					$error_return[] = $sub_error;
				}
			}
			else
			{
				$error_return[] = $error_data;
			}
		}

		$errors = $error_return;

		$error_page = (
			$this->param('error_page') ?
				$this->param('error_page') :
				ee()->input->post('error_page', TRUE)
		);

		if ( ! $error_page AND
			REQ == 'PAGE' AND
			isset(ee()->TMPL) AND
			is_object(ee()->TMPL) AND
			ee()->TMPL->fetch_param('error_page') !== FALSE)
		{
			$error_page = ee()->TMPL->fetch_param('error_page');
		}

		if ( ! $error_page)
		{
			return $this->show_error($errors);
		}

		//	----------------------------------------
		//  Retrieve Template
		//	----------------------------------------

		$x = explode('/', $error_page);

		if ( ! isset($x[1])) $x[1] = 'index';

		//	----------------------------------------
		//  Template as File?
		//	----------------------------------------

		$template_data = '';

		if ($template_data == '')
		{
			$query =	ee()->db->select('template_data, group_name, template_name, template_type')
								->from('exp_templates as t')
								->from('exp_template_groups as tg')
								->where('t.site_id', ee()->config->item('site_id'))
								->where('t.group_id = tg.group_id')
								->where('t.template_name', $x[1])
								->where('tg.group_name', $x[0])
								->limit(1)
								->get();

			if ($query->num_rows() > 0)
			{
				if (ee()->config->item('save_tmpl_files') == 'y' AND
					ee()->config->item('tmpl_file_basepath') != '')
				{
					ee()->load->library('api');
					ee()->api->instantiate('template_structure');

					$row = $query->row_array();

					$template_data = $this->find_template_file(
						$row['group_name'],
						$row['template_name'],
						ee()->api_template_structure->file_extensions(
							$row['template_type']
						)
					);
				}

				//no file? query it is
				if ($template_data == '')
				{
					$template_data = stripslashes($query->row('template_data'));
				}

			}
		}

		// -------------------------------------
		//	query didn't work but save templates
		//	as files is enabled? Lets see if its there
		//	as an html file anyway
		// -------------------------------------

		if ($template_data == '' AND
			ee()->config->item('save_tmpl_files') == 'y' AND
			ee()->config->item('tmpl_file_basepath') != '')
		{
			$template_data = $this->find_template_file($x[0], $x[1]);
		}

		// -------------------------------------
		//	still no template data? buh bye
		// -------------------------------------

		if ($template_data == '')
		{
			return $this->show_error($errors);
		}

		if ($type == 'general')
		{
			$heading = lang('general_error');
		}
		else
		{
			$heading = lang('submission_error');
		}

		//	----------------------------------------
		//  Create List of Errors for Content
		//	----------------------------------------

		$content  = '<ul>';

		if ( ! is_array($errors))
		{
			$content.= "<li>".$errors."</li>\n";
		}
		else
		{
			foreach ($errors as $val)
			{
				$content.= "<li>".$val."</li>\n";
			}
		}

		$content .= "</ul>";

		//	----------------------------------------
		//  Data Array
		//	----------------------------------------

		$data = array(
			'title' 		=> lang('error'),
			'heading'		=> $heading,
			'content'		=> $content,
			'redirect'		=> '',
			'meta_refresh'	=> '',
			'link'			=> array(
				'javascript:history.go(-1)',
				lang('return_to_previous')
			),
			'charset'		=> ee()->config->item('charset')
		);

		if (is_array($data['link']) AND count($data['link']) > 0)
		{
			$refresh_msg = (
				$data['redirect'] != '' AND
				$this->refresh_msg == TRUE
			) ? lang('click_if_no_redirect') : '';

			$ltitle = ($refresh_msg == '') ? $data['link']['1'] : $refresh_msg;

			$url = (
				strtolower($data['link']['0']) == 'javascript:history.go(-1)') ?
					$data['link']['0'] :
					ee()->security->xss_clean($data['link']['0']
			);

			$data['link'] = "<a href='".$url."'>".$ltitle."</a>";
		}

		//	----------------------------------------
		//  For a Page Request, we parse variables and return
		//  to let the Template Parser do the rest of the work
		//	----------------------------------------

		if (REQ == 'PAGE')
		{
			foreach ($data as $key => $val)
			{
				$template_data = str_replace('{'.$key.'}', $val, $template_data);
			}

			return str_replace('/', '/', $template_data);
		}

		// --------------------------------------------
		//	Parse as Template
		// --------------------------------------------

		$this->actions()->template();

		ee()->TMPL->global_vars	= array_merge(ee()->TMPL->global_vars, $data);
		$out = ee()->TMPL->process_string_as_template($template_data);

		exit($out);
	}
	// END error_page


	// --------------------------------------------------------------------

	/**
	 * Find the template
	 *
	 * @access	protected
	 * @param	string	$group		template group
	 * @param	string	$template	template name
	 * @param	string	$extension	file extension
	 * @return	string				template data or empty string
	 */

	protected function find_template_file($group, $template, $extention = '.html')
	{
		$template_data = '';

		$extention = '.' . ltrim($extention, '.');

		$filepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/') . '/';
		$filepath .= ee()->config->item('site_short_name') . '/';
		$filepath .= $group . '.group/';
		$filepath .= $template;
		$filepath .= $extention;

		ee()->security->sanitize_filename($filepath);

		if (file_exists($filepath))
		{
			$template_data = file_get_contents($filepath);
		}

		return $template_data;
	}
	//END find_template_file

	


	// --------------------------------------------------------------------

	/**
	 * Replace all form fields tags in the {freeform:all_form_fields} loop
	 *
	 * @access	protected
	 * @param	string	$tagdata			incoming tagdata
	 * @param	array	$fields				field_id => field_data array
	 * @param	array	$field_order		order of field by field id (optional)
	 * @param	array	$field_input_data	field input data (optional)
	 * @return	string						transformed output data
	 */

	protected function replace_all_form_fields($tagdata,
												$fields,
												$field_order = array(),
												$field_input_data = array())
	{
		// -------------------------------------
		//	all form fields loop
		// -------------------------------------
		//	this can be used to build normal output
		//	or custom output for edit.
		// -------------------------------------

		if (preg_match_all(
			'/' . LD . 'freeform:all_form_fields.*?' . RD .
				'(.*?)' .
			LD . '\/freeform:all_form_fields' . RD . '/ms',
			$tagdata,
			$matches,
			PREG_SET_ORDER
		))
		{
			$all_field_replace_data = array();

			$field_loop_ids = array_keys($fields);

			// -------------------------------------
			//	order ids?
			// -------------------------------------

			if ( ! is_array($field_order) AND is_string($field_order))
			{
				$field_order = $this->actions()->pipe_split($field_order);
			}

			$order_ids = array();

			if (is_array($field_order))
			{
				$order_ids = array_filter($field_order, array($this, 'is_positive_intlike'));
			}

			if ( ! empty($order_ids))
			{
				//this makes sure that any fields in 'fields' are in the
				//order set as well. Will add missing at the end like this
				$field_loop_ids = array_merge(
					$order_ids,
					array_diff($field_loop_ids, $order_ids)
				);
			}

			//build variables

			ee()->load->model('freeform_form_model');

			foreach ($field_loop_ids as $field_id)
			{

				if ( ! isset($fields[$field_id]))
				{
					continue;
				}

				$field_data = $fields[$field_id];

				// -------------------------------------
				//	get previous data
				// -------------------------------------

				$col_name = ee()->freeform_form_model->form_field_prefix . $field_id;

				$display_field_data = '';

				if (isset($field_input_data[$field_data['field_name']]))
				{
					$display_field_data = $field_input_data[$field_data['field_name']];
				}
				else if (isset($field_input_data[$col_name]))
				{
					$display_field_data = $field_input_data[$col_name];
				}

				// -------------------------------------
				//	load field data
				// -------------------------------------

				$all_field_replace_data[] = array(
					'freeform:field_id'		=> $field_id,
					'freeform:field_data'	=> $display_field_data,
					'freeform:field_name'	=> $field_data['field_name'],
					'freeform:field_type'	=> $field_data['field_type'],
					'freeform:field_label'	=> LD . 'freeform:label:' .
												$field_data['field_name'] . RD,
					'freeform:field_output'	=> LD . 'freeform:field:' .
												$field_data['field_name'] . RD,
					'freeform:field_description'	=> LD . 'freeform:description:' .
												$field_data['field_name'] . RD
				);
			}

			foreach ($matches as $match)
			{
				$tagdata_replace = ee()->TMPL->parse_variables(
					$match[1],
					$all_field_replace_data
				);

				$tagdata = str_replace($match[0], $tagdata_replace, $tagdata);
			}
		}

		return $tagdata;
	}
	//END replace_all_form_fields


	// --------------------------------------------------------------------

	/**
	 * Parse Status Tags
	 *
	 * Parses:
	 * 	 	{freeform:statuses status="not closed|open"}
	 *			{status_name} {status_value}
	 *		{/freeform:statuses}
	 *
	 * @access	protected
	 * @param	string $tagdata	tagdata to be parsed
	 * @return	string			adjusted tagdata with status pairs parsed
	 */

	protected function parse_status_tags ($tagdata)
	{
		$matches 	= array();
		$tag		= 'freeform:statuses';
		$statuses	= $this->data->get_form_statuses();

		preg_match_all(
			'/' . LD . $tag . '.*?' . RD .
				'(.*?)' .
			LD . '\/' . $tag . RD . '/ms',
			$tagdata,
			$matches,
			PREG_SET_ORDER
		);

		if ($matches AND
			isset($matches[0]) AND
			! empty($matches[0]))
		{
			foreach ($matches as $key => $value)
			{
				$replace_with	= '';
				$tdata			= $value[1];

				//no need for an if. if we are here, this matched before
				preg_match(
					'/' . LD . $tag . '.*?' . RD . '/',
					$value[0],
					$sub_matches
				);

				// Checking for variables/tags embedded within tags
				// {exp:channel:entries channel="{master_channel_name}"}
				if (stristr(substr($sub_matches[0], 1), LD) !== FALSE)
				{
					$sub_matches[0] = ee()->functions->full_tag(
						$sub_matches[0],
						$value[0]
					);

					// -------------------------------------
					//	fix local tagdata
					// -------------------------------------

					preg_match(
						'/' . preg_quote($sub_matches[0]) .
							'(.*?)' .
						LD . '\/' . $tag . RD . '/ms',
						$value[0],
						$tdata_matches
					);

					if (isset($tdata_matches[1]))
					{
						$tdata = $tdata_matches[1];
					}
				}

				$tag_params = ee()->functions->assign_parameters(
					$sub_matches[0]
				);

				$out_status = $statuses;

				if (isset($tag_params['status']))
				{
					$names	= strtolower($tag_params['status']);
					$not	= FALSE;

					if (preg_match("/^not\s+/s", $names))
					{
						$names	= preg_replace('/^not\s+/s', '', $names);
						$not	= TRUE;
					}

					$names = preg_split(
						'/\|/s',
						trim($names),
						-1,
						PREG_SPLIT_NO_EMPTY
					);

					foreach ($out_status as $status_name => $status_value)
					{
						if (in_array(strtolower($status_name), $names) == $not)
						{
							unset($out_status[$status_name]);
						}
					}
				}

				foreach ($out_status as $out_name => $out_value)
				{
					$replace_with .= str_replace(
						array(LD . 'status_name' . RD, LD . 'status_label' . RD),
						array($out_name, $out_value),
						$tdata
					);
				}

				//remove
				$tagdata = str_replace($value[0], $replace_with, $tagdata);
			}
		}

		return $tagdata;
	}
	//END parse_status_tags


	// --------------------------------------------------------------------

	/**
	 * Runs each fields 'prep_multi_item_data' so that things like 'cat|~|dog'
	 * dont show publicly
	 *
	 * @access	public
	 * @param	string	$data		incoming data to parse
	 * @param	string	$field_type	fieldtype data is from
	 * @return	string				result of prepping multiitem data
	 */

	public function prep_multi_item_data($data = '', $field_type = 'text')
	{
		//nothing to do if its empty, yo
		if ( ! $data)
		{
			return $data;
		}

		ee()->load->library('freeform_fields');

		$instance =& ee()->freeform_fields->get_fieldtype_instance(
			$field_type
		);

		$result = $instance->prep_multi_item_data($data);

		//eh, bad dog
		if ( ! $result)
		{
			return $data;
		}

		//array?
		if ( ! is_string($result))
		{
			return implode("\n", (array) $result);
		}

		return $result;
	}
	//END prep_multi_item_data


	// --------------------------------------------------------------------

	/**
	 * Replace Submit buttons with args
	 *
	 * @access public
	 * @param	array  $options	input options
	 * @return	string			parsed tagdata
	 */

	public function replace_submit($options = array())
	{
		$defaults = array(
			'pre_args'	=> array(),
			'post_args'	=> array(),
			'tagdata'	=> '',
			'tag'		=> '',
			'remove'	=> false
		);

		//set vars
		foreach($defaults as $key => $value)
		{
			$$key = (isset($options[$key])) ? $options[$key] : $value;
		}

		if (preg_match_all(
				"/" . LD . preg_quote($tag, '/') . "\b(.*?)" . RD . "/ims",
				$tagdata,
				$matches
			)
		)
		{
			$total_matches 	= count($matches[0]);

			for ($i = 0; $i < $total_matches; $i++)
			{

				// Checking for variables/tags embedded within tags
				// {exp:channel:entries channel="{master_channel_name}"}
				if (stristr(substr($matches[0][$i], 1), LD) !== FALSE)
				{
					$matches[0][$i] = ee()->functions->full_tag($matches[0][$i], $tagdata);
				}

				//if we aren't dealing with just random space
				if (trim($matches[1][$i]) != '')
				{
					$args = ee()->functions->assign_parameters($matches[1][$i]);

					$attr = array();

					if ($args)
					{
						foreach ($args as $key => $value)
						{
							if (substr($key, 0, 5) == 'attr:')
							{
								$attr[substr($key, 5)] = $value;
							}
						}
					}

					//wont work without the name being correct
					$args = array_merge(
						$pre_args,
						$attr,
						$post_args
					);

					if ($remove)
					{
						$tagdata = str_replace(
							$matches[0][$i],
							'',
							$tagdata
						);
					}
					else
					{
						$tagdata = str_replace(
							$matches[0][$i],
							form_submit($args),
							$tagdata
						);
					}
				}
				//END if trim
			}
			//END for
		}
		//END preg_match_all

		return $tagdata;
	}
	//END replace_submit


	// --------------------------------------------------------------------

	/**
	 * Do Exit
	 *
	 * Helper for unit tests so PHP exit isn't fired in the middle of it
	 *
	 * @access protected
	 * @return void
	 */

	protected function do_exit()
	{
		if ($this->test_mode)
		{
			return;
		}
		else
		{
			exit();
		}
	}
	//END do_exit


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