<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform File Upload Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.file_upload.php
 */

class File_upload_freeform_ft extends Freeform_base_ft
{
	//needed for file uploads with the form
	public $requires_multipart 	= TRUE;

	public $info 				= array(
		'name'						=> 'File Upload',
		'version'					=> FREEFORM_VERSION,
		'description'				=> 'A field that allows a user to upload files.'
	);

	public $default_settings 	= array(
		'allowed_file_types'		=> 'jpg|png|gif',
		'file_upload_location'		=> '1',
		'allowed_upload_count'		=> '1',
		'overwrite_on_edit'			=> 'n',
		'disable_xss_clean'			=> 'n'
	);

	private $upload_count;

	public $paginate_limit		= 50;

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

		$this->entry_views[lang('file_field_uploads')] = 'file_uploads_view';

		$this->info['name'] 		= lang('default_file_name');
		$this->info['description'] 	= lang('default_file_desc');

		if ( ! isset(ee()->session->cache['freeform_file_uploads']))
		{
			ee()->session->cache['freeform_file_uploads'] = array();
		}

		$this->cache =& ee()->session->cache['freeform_file_uploads'];

		$this->max_files = (int) @ini_get('max_file_uploads');

		//fall back to 1 on errors
		//hopefully this will never happen, but you never know
		if ( ! $this->max_files)
		{
			$this->max_files = 1;
		}
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * File Uploads Entry View
	 *
	 * @access	public
	 * @return	string html for view
	 */

	public function file_uploads_view ()
	{
		$data = array(
			'caller'		=> $this,
			'files'			=> array(),
			'pagination'	=> FALSE,
			'form_url'		=> $this->field_method_link(array(
				'field_method'	=> 'delete_file_uploads',
				'form_id'		=> $this->form_id,
				'field_id'		=> $this->field_id
			))
		);

		ee()->load->model('freeform_file_upload_model');

		$entry_id = $this->get_post_or_zero('entry_id');

		if ($this->is_positive_intlike($this->form_id))
		{
			ee()->freeform_file_upload_model->where('form_id', $this->form_id);
		}

		if ($this->is_positive_intlike($this->field_id))
		{
			ee()->freeform_file_upload_model->where('field_id', $this->field_id);
		}

		if ($this->is_positive_intlike($entry_id))
		{
			ee()->freeform_file_upload_model->where('entry_id', $entry_id);
		}

		if ( ! $this->show_all_sites)
		{
			ee()->freeform_file_upload_model->where(
				'site_id',
				ee()->config->item('site_id')
			);
		}

		$count = ee()->freeform_file_upload_model->count(array(), FALSE);

		if ($count > 0)
		{
			// -------------------------------------
			//	paginate?
			// -------------------------------------

			$row = $this->get_post_or_zero('row');

			if ($count > $this->paginate_limit)
			{
				$config = array(
					'base_url'				=> $this->field_method_link(array(
						'field_method' => 'file_uploads_view'
					)),
					'total_rows'			=> $count,
					'per_page'				=> $this->paginate_limit,
					'page_query_string'		=> TRUE,
					'query_string_segment'	=> 'row'
				);

				ee()->load->library('pagination');
				ee()->pagination->initialize($config);

				$data['pagination'] = ee()->pagination->create_links();

				ee()->freeform_file_upload_model->limit($this->paginate_limit, $row);
			}

			ee()->freeform_file_upload_model->order_by('file_id', 'desc');

			$files = ee()->freeform_file_upload_model->get();

			$uprefs = $this->upload_prefs();
			$data['uprefs'] = $uprefs;
			//just in case
			if ($files !== FALSE)
			{
				$count = $row;

				foreach ($files as $k => $file)
				{
					$files[$k]['count']		= ++$count;

					$filename = ee()->security->sanitize_filename($files[$k]['filename']);

					$file_path = rtrim($uprefs['server_path'], '/') .
									'/' . $filename;

					$files[$k]['fb']		= in_array(
						strtolower($files[$k]['extension']),
						array('jpg', 'jpeg', 'gif', 'png')
					);

					$files[$k]['link']				= FALSE;
					$files[$k]['download_link']		= FALSE;
					$files[$k]['front_end_link']	= FALSE;

					if (file_exists($file_path))
					{
						if ($files[$k]['fb'])
						{
							$files[$k]['link']			= $this->field_method_link(array(
								'field_method'	=> 'view_file',
								'file_id'		=> $files[$k]['file_id'],
								//just to assist fancybox, not used
								'filename'		=> $filename
							));
						}


						$files[$k]['download_link']	= $this->field_method_link(array(
							'field_method'	=> 'download_file',
							'file_id'		=> $files[$k]['file_id']
						));

						$files[$k]['front_end_link'] = rtrim($uprefs['url'], '/') .
															'/' . $filename;
					}

					$files[$k]['filesize']	= $files[$k]['filesize'] . lang('kb');

					$files[$k]['uprefs_link'] = BASE .
												'&C=content_files' .
												'&M=edit_upload_preferences' .
												'&id=' . $uprefs['id'];

					$files[$k]['uprefs_name'] = $uprefs['name'];
				}

				$data['files'] = $files;
			}
		}

		return ee()->load->view('file_uploads.html', $data, TRUE);
	}
	//end file_uploads_view


	// --------------------------------------------------------------------

	/**
	 * View a file in the CP
	 * Helps with files that are displayed below root
	 *
	 * @access	public
	 * @param	boolean $download	should the file be downloaded instead?
	 * @return	void
	 */

	public function view_file($download = FALSE)
	{
		ee()->load->model('freeform_file_upload_model');

		$file_id	= $this->get_post_or_zero('file_id');

		//these prefs are per field, and automatic from settings
		$uprefs		= $this->upload_prefs();

		$file_data	= ee()->freeform_file_upload_model
							->where('file_id', $file_id)
							->get_row();

		//legit, yo
		if ($file_data == FALSE)
		{
			show_error(lang('invalid_file'));
		}

		$filename	= ee()->security->sanitize_filename($file_data['filename']);

		$file_path	= realpath(rtrim($uprefs['server_path'], '/') . '/' . $filename);

		if ( ! file_exists($file_path))
		{
			show_error(lang('invalid_file'));
		}

		ee()->load->helper('file');

		$mime = get_mime_by_extension($filename);

		if ($download)
		{
			header('Content-disposition: attachment; filename=' . $filename);
		}

		header('Content-type: ' . $mime);
		header('Content-Length: ' . filesize($file_path));
		readfile($file_path);
		exit();
	}
	//END view_file


	// --------------------------------------------------------------------

	/**
	 * Uses view_file with a download header for forced downloading
	 *
	 * @access	public
	 * @return	void
	 */
	public function download_file()
	{
		return $this->view_file(TRUE);
	}
	//END download_file


	// --------------------------------------------------------------------

	/**
	 * Delete File Uploads
	 *
	 * @access	public
	 * @param	boolean		$redirect	redirect?
	 * @param	array		$file_ids	optional array of file ids if called elsewhere
	 * @param	boolean		$update		update the entries with blank info?
	 * @return	null		redirect
	 */

	public function delete_file_uploads ($redirect = TRUE, $file_ids = array(), $update = TRUE)
	{
		$form_id	= $this->get_post_or_zero('form_id');

		if ($form_id == 0 AND
			$this->is_positive_intlike($this->form_id) AND
			$this->form_id > 0)
		{
			$form_id = $this->form_id;
		}

		$file_ids	= (
			empty($file_ids) ?
				ee()->input->get_post('file_ids') :
				$file_ids
		);

		$msg		= '';

		if ($form_id > 0 AND
			$file_ids !== FALSE)
		{
			if ( ! is_array($file_ids))
			{
				$file_ids = array($file_ids);
			}

			//make sure all are ints
			$file_ids = array_filter(
				$file_ids,
				array($this, 'is_positive_intlike')
			);

			if ( ! empty($file_ids))
			{
				ee()->load->model('freeform_file_upload_model');

				if ( ! $this->show_all_sites)
				{
					ee()->freeform_file_upload_model->where(
						'site_id',
						ee()->config->item('site_id')
					);
				}

				$d_files = ee()->freeform_file_upload_model
									->where('form_id', $form_id)
									->where_in('file_id', $file_ids)
									->get(array(), FALSE);

				if ($d_files !== FALSE)
				{
					$msg = 'files_deleted';

					$entry_ids = array();

					foreach ($d_files as $file)
					{
						$entry_ids[] = $file['entry_id'];

						$loc = rtrim($file['server_path'], '/') .
								'/' . ee()->security->sanitize_filename($file['filename']);

						if (file_exists($loc))
						{
							try
							{
								@unlink($loc);
							}
							catch (Exception $e)
							{
								//JACK SQUAT
							}
						}
					}

					if ($update)
					{
						ee()->freeform_file_upload_model->reset();

						//get remaining files per entry id
						$a_files = ee()->freeform_file_upload_model
											->where('form_id', $form_id)
											->where_in('entry_id', $entry_ids)
											->get(array(), FALSE);

						foreach ( $a_files as $row )
						{
							$field_files[ $row['entry_id'] ][ $row['file_id'] ]	= $row['filename'];
						}

						ee()->load->model('freeform_entry_model');

						$col_name = ee()->freeform_entry_model->form_field_prefix . $this->field_id;

						ee()->freeform_entry_model
								 ->id($form_id)
								 ->where_in('entry_id', $entry_ids)
								 ->update(array(
										$col_name => ''
								 ));

						//loop for each file and attach to entry
						foreach ( $field_files as $entry_id => $filenames )
						{
							$implosion	= '';

							foreach ( $filenames as $file_id => $filename )
							{
								if ( in_array( $file_id, $file_ids ) ) continue;

								$implosion	.= $filename . "\n";
							}

							ee()->freeform_entry_model
									 ->id($form_id)
									 ->where('entry_id', $entry_id)
									 ->update(array(
											$col_name => trim( $implosion )
									 ));
						}

					}

					ee()->freeform_file_upload_model->delete();
				}
				else
				{
					ee()->freeform_file_upload_model->reset();
				}
			}
		}

		if ($redirect)
		{
			ee()->functions->redirect($this->field_method_link(array(
				'field_method'	=> 'file_uploads_view',
				'form_id'		=> $this->form_id,
				'msg'			=> $msg
			)));
		}
	}
	// END delete_file_uploads


	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @access	public
	 * @param	array of ids
	 */

	public function delete ($ids = array())
	{
		ee()->load->model('freeform_file_upload_model');

		$file_ids = ee()->freeform_file_upload_model
							 ->key('file_id', 'file_id')
							 ->where('form_id', $this->form_id)
							 ->where('field_id', $this->field_id)
							 ->where_in('entry_id', $ids)
							 ->get();

		if ($file_ids !== FALSE)
		{
			//just in case
			//$temp = $this->form_id;
			//$this->form_id = $form_id;

			$this->delete_file_uploads(FALSE, $file_ids, FALSE);

			//$this->form_id = $temp;
		}

		return;
	}
	//END delete


	// --------------------------------------------------------------------

	/**
	 * Delete field
	 *
	 * @access	public
	 * @param	array of ids
	 */

	public function delete_field ()
	{
		ee()->load->model('freeform_file_upload_model');

		$d_files = ee()->freeform_file_upload_model
							->where_in('field_id', $this->field_id)
							->get(array(), FALSE);

		if ($d_files !== FALSE)
		{
			$entry_ids = array();

			foreach ($d_files as $file)
			{
				$entry_ids[] = $file['entry_id'];

				$loc = rtrim($file['server_path'], '/') .
						'/' . ee()->security->sanitize_filename($file['filename']);

				if (file_exists($loc))
				{
					try
					{
						@unlink($loc);
					}
					catch (Exception $e)
					{
						//JACK SQUAT
					}
				}
			}

			ee()->freeform_file_upload_model->delete();
		}
		else
		{
			ee()->freeform_file_upload_model->reset();
		}

		return TRUE;
	}
	//END delete field


	// --------------------------------------------------------------------

	/**
	 * Pre process data from the paginated query object before all fields
	 * get used
	 *
	 * @access  public
	 * @param  	object &$query query object from entries tag
	 * @return 	void
	 */

	public function pre_process_entries ($entry_ids = array())
	{
		if ( ! empty($entry_ids))
		{
			$upload_prefs = $this->upload_prefs();

			ee()->load->model('freeform_file_upload_model');

			ee()->freeform_file_upload_model->where('form_id', $this->form_id);
			ee()->freeform_file_upload_model->where_in('entry_id', $entry_ids);
			$data_query = ee()->freeform_file_upload_model->get();

			if ($data_query !== FALSE)
			{
				foreach ($data_query as $row)
				{
					if ( ! isset($this->cache['db_data'][$this->form_id][$row['entry_id']][$row['field_id']]))
					{
						$this->cache['db_data'][$this->form_id][$row['entry_id']][$row['field_id']] = array();
					}

					$row['fileurl'] = $upload_prefs['url'] . $row['filename'];

					$this->cache['db_data'][$this->form_id][$row['entry_id']][$row['field_id']][$row['file_id']] = $row;
				}
			}

			//store blanks
			foreach ($entry_ids as $entry_id)
			{
				if ( ! isset($this->cache['db_data'][$this->form_id][$entry_id]))
				{
					$this->cache['db_data'][$this->form_id][$entry_id] = array();
				}
			}

		}
		//no entries? *sad trombone*
	}
	//END pre_process_entries


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
		$output = '';

		if (! empty($this->cache['db_data'][$this->form_id][$this->entry_id][$this->field_id]))
		{
			$output = '<a href="' . $this->field_method_link(array(
				'field_method'	=> 'file_uploads_view',
				'form_id'		=> $this->form_id,
				'entry_id'		=> $this->entry_id,
				'field_id'		=> $this->field_id
			)) . '">' . lang('view_files') . '</a>';
		}

		return $output;
	}
	//END display_entry_cp


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
		ee()->load->model('freeform_file_upload_model');

		$db_data		= array();

		$upload_prefs	= $this->upload_prefs();

		//build cache data so we aren't running queries a bajillion times
		if (isset($this->cache['db_data'][$this->form_id][$this->entry_id]))
		{
			//not grouping these two on purpose because we want to get here
			//if the entry id is already cached, because it means there were
			//no uploads for this field and we dont want to run th query
			//again for no reason
			if (isset($this->cache['db_data'][$this->form_id][$this->entry_id][$this->field_id]))
			{
				$db_data = $this->cache['db_data'][$this->form_id][$this->entry_id][$this->field_id];
			}
		}
		else
		{
			$data_query = ee()->freeform_file_upload_model->get(
				array(
					'form_id' 	=> $this->form_id,
					'entry_id'	=> $this->entry_id,
					'field_id'	=> $this->field_id
				)
			);

			if ($data_query !== FALSE)
			{
				foreach ($data_query as $row)
				{
					$row['fileurl'] = $upload_prefs['url'] . $row['filename'];
					$db_data[$row['file_id']] 		= $row;
				}
			}

			$this->cache['db_data'][$this->form_id][$this->entry_id][$this->field_id] = $db_data;
		}

		if (isset($params['total_uploads']) AND
			in_array(strtolower($params['total_uploads']), array('yes', 'y', 'true', 'on')))
		{
			if ( ! isset($this->cache['total_counts'][$this->form_id][$this->entry_id]))
			{
				$total = 0;

				if (isset($this->cache['db_data'][$this->form_id][$this->entry_id]))
				{
					foreach ($this->cache['db_data'][$this->form_id][$this->entry_id] as $key => $value)
					{
						$total += count($this->cache['db_data'][$this->form_id][$this->entry_id][$key]);
					}
				}
				else
				{
					$total = ee()->freeform_file_upload_model->count(
						array(
							'form_id' 	=> $this->form_id,
							'entry_id'	=> $this->entry_id
						)
					);
				}

				$this->cache['total_counts'][$this->form_id][$this->entry_id] = $total;
			}

			return $this->cache['total_counts'][$this->form_id][$this->entry_id];
		}

		if ( empty($db_data))
		{
			return '';
		}

		$output = '';

		if ($tagdata)
		{
			//building template rows automatically repeats tagdata
			//but we don't want all data from these rows as it could
			//override some data from the parent template (though, it shouldn't)
			$template_rows = array();

			foreach ($db_data as $row)
			{
				$arr = array(
					//'server_path'	=> $row['server_path'],
					'freeform:filename'		=> $row['filename'],
					'freeform:extension'	=> $row['extension'],
					//filesize in other langauges can have commas in floats
					//and EE's current version of CI cannot handle it
					//so we have to cast to string here
					'freeform:filesize'		=> (string) $row['filesize'],
					'freeform:fileurl'		=> rtrim($upload_prefs['url'], '/') .
										'/' . ee()->security->sanitize_filename($row['filename']),
				);

				// -------------------------------------
				//	legacy
				// -------------------------------------

				if ( empty($params['disable_legacy']) OR
					$params['disable_legacy'] == 'no')
				{
					$arr['filename']	= $arr['freeform:filename'];
					$arr['extension']	= $arr['freeform:extension'];
					$arr['filesize']	= $arr['freeform:filesize'];
					$arr['fileurl']		= $arr['freeform:fileurl'];
				}

				$template_rows[]	= $arr;
			}

			$output = ee()->TMPL->parse_variables($tagdata, $template_rows);
		}
		else
		{
			foreach ($db_data as $row)
			{
				$output .= ee()->security->sanitize_filename($row['filename']) . "\n";
			}
		}

		return $output;
	}
	//END replace_tag


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
		$param_defaults = array(
			'input_wrapper_open'	=> '',
			'input_wrapper_close'	=> '<br/>',
			'backspace'				=> FALSE,
		);

		$params 	= array_merge($param_defaults, $params);

		$settings 	= array_merge($this->default_settings, $this->settings);

		//default backspace
		if ($params['input_wrapper_open']	=== ''		AND
			$params['input_wrapper_close']	=== '<br/>'	AND
			$params['backspace']			=== FALSE)
		{
			$params['backspace'] = strlen($params['input_wrapper_close']);
		}

		// -------------------------------------
		//	number of items to show?
		// -------------------------------------

		$num_shown = 1;

		$allowed_upload_count = $this->max_files;

		 if ($this->is_positive_intlike($settings['allowed_upload_count']) AND
			$settings['allowed_upload_count'] <= $this->max_files )
		{
			$allowed_upload_count = $settings['allowed_upload_count'];
		}

		//param override?
		if (isset($params['show']) AND
			$this->is_positive_intlike($params['show']) AND
			$params['show'] <= $allowed_upload_count )
		{
			$num_shown = $params['show'];
		}
		else
		{
			$num_shown = $allowed_upload_count;
		}

		// -------------------------------------
		//	backspace?
		// -------------------------------------

		if (isset($params['backspace']) AND
			$this->is_positive_intlike($params['backspace']))
		{
			$backspace = $params['backspace'];
		}
		else
		{
			$backspace = FALSE;
		}

		// -------------------------------------
		//	do we have some files?
		// -------------------------------------

		$file_string		= '';
		$file_string_count	= 0;

		if ( ! empty( $this->entry_id ) )
		{
			$tagdata	= '<li><a class="ff_uploaded_files" href="{fileurl}" target="_blank">{filename}</a></li>';

			$file_string	= '<p><ul id="upload' . $this->field_id . '">';
			$file_string	.= $this->replace_tag( array(), array(), $tagdata );
			$file_string	.= '</ul></p>';

			$file_string_count	= substr_count( $file_string, 'ff_uploaded_files' );
		}

		// -------------------------------------
		//	build output
		// -------------------------------------

		$output 	= $file_string;

		for ($i = 0; $i < $num_shown; $i++)
		{
			$output .= $params['input_wrapper_open'];
			$output .= form_upload(array_merge(array(
				'name'			=> $this->field_name . '[' . $i . ']',
				'id'			=> 'freeform_' . $this->field_name . $i,
				'value'			=> ''
			), $attr));
			$output .= $params['input_wrapper_close'];
		}

		if ($backspace)
		{
			$output = substr($output, 0, $backspace * -1);
		}

		return $output;
	}
	//END display_field


	// --------------------------------------------------------------------

	/**
	 * validate
	 *
	 * @access	public
	 * @param	string 	input data from post to be validated
	 * @return	bool
	 */

	public function validate ($data = '')
	{
		// we are OK with blank
		// because the required checker does the work
		if ($this->count_uploads() == 0)
		{
			return TRUE;
		}

		//allowed_file_types
		//file_upload_location
		//allowed_upload_count
		$settings 		= array_merge(
			$this->default_settings,
			$this->settings
		);

		if ( $this->count_uploads() > $settings['allowed_upload_count'])
		{
			$this->errors[] = lang('file_upload_limit_exceeded');

			return $this->errors;
		}

		$upload_prefs =	$this->upload_prefs();

		//separate files and rename if necessary
		$upload_array = $this->separate_uploads($upload_prefs['server_path']);

		$this->cache[$this->field_name]['upload_array'] = $upload_array;

		ee()->load->library('upload');
		ee()->upload->initialize($upload_prefs);

		$path = ee()->upload->validate_upload_path();

		$errors = array();

		if ( ! $path)
		{
			$errors = array_merge($errors, ee()->upload->error_msg);
		}

		foreach ($upload_array as $upload_data)
		{
			$errors = array_merge($errors, $this->validate_file_upload($upload_data));
		}

		if ( ! empty($errors))
		{
			$this->errors = array_merge($this->errors, $errors);
		}

		if ( ! empty($this->errors))
		{
			return $this->errors;
		}

		return TRUE;
	}
	//END validate


	// --------------------------------------------------------------------

	/**
	 * upload prefs
	 *
	 * @access	private
	 * @return	array 	upload prefs
	 */

	private function upload_prefs ()
	{
		if (isset($this->cache[$this->field_name]['upload_prefs']))
		{
			return $this->cache[$this->field_name]['upload_prefs'];
		}

		$settings 		= array_merge(
			$this->default_settings,
			$this->settings
		);

		ee()->load->model('file_upload_preferences_model');

		$upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(
			1, //we need all data here so we can get the upload prefs
			$settings['file_upload_location'],
			TRUE
		);

		//seems in newer versions of the file upload prefs model, upload path
		//isnt set.
		if (empty($upload_prefs['upload_path']) AND
			! empty($upload_prefs['server_path']))
		{
			$upload_prefs['upload_path'] = $upload_prefs['server_path'];
		}

		$extra_options = array(
			'allowed_types' => $settings['allowed_file_types'],
			'temp_prefix'	=> ''
		);

		// -------------------------------------
		//	So if you init upload AFTER construction
		//	it doesn't load xss clean options
		//	if you do it during, it gets overriden
		//	no matter what! Yay!
		// -------------------------------------

		ee()->load->helper('xss');

		$extra_options['xss_clean'] = xss_check();

		if ($extra_options['xss_clean'] AND
			$settings['disable_xss_clean'] == 'y')
		{
			$extra_options['xss_clean']	= FALSE;
		}

		$this->cache[$this->field_name]['upload_prefs'] = array_merge(
			$upload_prefs,
			$extra_options
		);

		return $this->cache[$this->field_name]['upload_prefs'];
	}
	//END upload_prefs


	// --------------------------------------------------------------------

	/**
	 * count uploads
	 *
	 * @access	private
	 * @return	int 	upload count
	 */

	private function count_uploads ()
	{
		//cache
		if (isset($this->upload_count))
		{
			return $this->upload_count;
		}

		//make sure its not empty
		if ( ! isset($_FILES[$this->field_name]) 		OR
			 $_FILES[$this->field_name]['name'] == '' 	OR
			 (
				is_array($_FILES[$this->field_name]['name']) AND
				empty($_FILES[$this->field_name]['name'])
			 )
		)
		{
			return 0;
		}

		//if its an array of files, some could be empty
		if (is_array($_FILES[$this->field_name]['name']))
		{
			$count = 0;

			foreach ( $_FILES[$this->field_name]['name'] as $name )
			{
				if ( $name != '' )
				{
					$count++;
				}
			}

			$this->upload_count = $count;
		}
		//else its not an array and its not empty
		else
		{
			$this->upload_count = 1;
		}

		return $this->upload_count;
	}
	//END count_uploads


	// --------------------------------------------------------------------

	/**
	 * separate_uploads
	 *
	 * @access	private
	 * @return	array 	 uploads separated
	 */

	private function separate_uploads ($path = '')
	{
		$return = array();

		//extra check. its cached anyway
		if ($this->count_uploads() == 0)
		{
			return $return;
		}

		if ( is_array($_FILES[$this->field_name]['name']))
		{
			$l = count($_FILES[$this->field_name]['name']);

			for ($i = 0; $i < $l; $i++)
			{
				//if its empty we don't want it
				if ($_FILES[$this->field_name]['name'][$i] !== '')
				{
					//we want to rename this because CI
					//does this funkity temp thing
					$name = strtolower($this->rename_file(
						$path,
						$_FILES[$this->field_name]['name'][$i]
					));

					$return[] = array(
						'name' 		=> ee()->security->sanitize_filename($name),
						'type' 		=> $_FILES[$this->field_name]['type' 	][$i],
						'tmp_name' 	=> $_FILES[$this->field_name]['tmp_name'][$i],
						'error' 	=> $_FILES[$this->field_name]['error' 	][$i],
						'size' 		=> $_FILES[$this->field_name]['size' 	][$i]
					);
				}
			}
		}

		return $return;
	}
	//END separate_uploads


	// --------------------------------------------------------------------

	/**
	 *  rename_file - determines if a file
	 *	exists. If so, it'll append a number to
	 *	the filename and call itself again. It
	 *	does this as many times as necessary
	 *	until a filename is clear.
	 *
	 * @access	private
	 * @param	string	full path of file
	 * @param	string	file name
	 * @param	int		iterator for end of filename
	 * @return	string	filename
	 */

	private function rename_file ($path, $name, $i = 0)
	{
		if (file_exists($path.$name))
		{
			$xy = explode(".", $name);
			$ext = end($xy);

			$name = str_replace('.'.$ext, '', $name);

			if (preg_match('/' . $i . '$/is', $name))
			{
				$name = substr($name, 0, -1);
			}

			$i++;

			$name .= $i . '.' . $ext;

			return $this->rename_file($path, $name, $i);
		}

		return $name;
	}
	//END rename_file


	// --------------------------------------------------------------------

	/**
	 * Save Field Data
	 *
	 * uploads what we validated earlier
	 *
	 * @access	public
	 * @param	string 	data to be inserted
	 * @return	string 	data to go into the entry_field
	 */

	public function save ($data)
	{
		$data = array();

		ee()->load->library('upload');
		$prefs = $this->upload_prefs();
		ee()->upload->initialize($prefs);

		if ( isset($this->cache[$this->field_name]['upload_array']) AND
			! empty($this->cache[$this->field_name]['upload_array']))
		{

			foreach ($this->cache[$this->field_name]['upload_array'] as $upload_data)
			{
				if (isset($upload_data['name']))
				{
					$data[] = $upload_data['name'];
				}
			}
		}

		return implode("\n", $data);
	}
	//END save


	// --------------------------------------------------------------------

	/**
	 * Save Field Data
	 *
	 * uploads what we validated earlier
	 *
	 * @access	public
	 * @param	string 	data to be inserted
	 * @return	string 	data to go into the entry_field
	 */

	public function post_save ($data)
	{
		ee()->load->library('upload');
		$prefs = $this->upload_prefs();
		ee()->upload->initialize($prefs);

		if ( isset($this->cache[$this->field_name]['upload_array']) AND
			! empty($this->cache[$this->field_name]['upload_array']))
		{
			$inserts	= array();
			$errors		= array();
			$output		= array();

			foreach ($this->cache[$this->field_name]['upload_array'] as $upload_data)
			{
				$_FILES[$this->field_name] = $upload_data;

				if ( ! ee()->upload->do_upload($this->field_name))
				{
					$errors[] = ee()->upload->display_errors();
				}
				else
				{
					$file_data	= ee()->upload->data();

					$output[]	= $file_data['file_name'];

					$inserts[]	= array(
						'form_id' 		=> $this->form_id,
						'entry_id'		=> $this->entry_id,
						'field_id'		=> $this->field_id,
						'site_id'		=> ee()->config->item('site_id'),
						'server_path'	=> $file_data['file_path'],
						'filename'		=> $file_data['file_name'],
						'extension'		=> preg_replace('/^\./', '', $file_data['file_ext']),
						'filesize'		=> (string) $file_data['file_size']
					);
				}
			}

			if ($errors)
			{
				$this->full_stop($errors);
			}

			if ( ! empty($inserts))
			{
				ee()->load->model('freeform_file_upload_model');

				// -------------------------------------
				//	are we supposed to overwrite existing files during edit?
				// -------------------------------------

				if ( ! empty( $this->settings['overwrite_on_edit'] ) AND
					$this->settings['overwrite_on_edit'] == 'y' AND
					! empty( $this->entry_id ) )
				{
					$delete	= array(
						'form_id' 		=> $this->form_id,
						'entry_id'		=> $this->entry_id,
						'field_id'		=> $this->field_id,
						'site_id'		=> ee()->config->item('site_id')
					);

					ee()->freeform_file_upload_model->delete($delete);
				}

				// -------------------------------------
				//	insert
				// -------------------------------------

				foreach ($inserts as $insert)
				{
					ee()->freeform_file_upload_model->insert($insert);
				}
			}
		}
	}
	//END post_save


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
		$dropdown = array();

		for ($i = 1; $i <= $this->max_files; $i++)
		{
			$dropdown[$i] = $i;
		}

		$auc = 'allowed_upload_count';

		$count_choice 	= isset($data[$auc]) ? $data[$auc] : '';

		ee()->table->add_row(
			lang($auc, $auc) .
				'<div class="subtext">' .
					lang($auc . '_desc') .
				'</div>',
			//col 2
			form_dropdown(
				$auc,
				$dropdown,
				$count_choice,
				$this->stringify_attributes(array(
					'class' => 'chzn_select_no_search',
					'id'	=> 	$auc,
				))
			)
		);

		// -------------------------------------
		//	file upload choices
		// -------------------------------------

		$query = ee()->db->select('id, name')->get('upload_prefs');

		$upload_places = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$upload_places[$row['id']] = $row['name'];
			}
		}

		// -------------------------------------
		//	file upload dropdown
		// -------------------------------------

		$ful 			= 'file_upload_location';

		if ( ! empty($upload_places))
		{
			$upload_choice 	= isset($data[$ful]) ? $data[$ful] : '';

			$column_two = form_dropdown(
				$ful,
				$upload_places,
				$upload_choice,
				$this->stringify_attributes(array(
					'class' => 'chzn_select',
					'id'	=> 	$ful,
				))
			);
		}
		else
		{
			$column_two = '<span class="ss_notice">' .
							lang('file_upload_pref_missing_error') .
							'</span>';
		}

		ee()->table->add_row(
			lang($ful, $ful) .
				'<div class="subtext">' .
					lang($ful . '_desc') .
				'</div>',
			//col 2
			$column_two
		);

		// -------------------------------------
		//	allowed file types
		// -------------------------------------

		ee()->table->add_row(
			lang('allowed_file_types', 'allowed_file_types') .
				'<div class="subtext">' .
					lang('allowed_file_types_desc') .
				'</div>',
			form_input(array(
				'name'		=> 'allowed_file_types',
				'id'		=> 'allowed_file_types',
				'value'		=> isset($data['allowed_file_types']) ?
								$data['allowed_file_types'] :
								$this->default_settings['allowed_file_types'],
				'size'		=> '50',
			))
		);

		// -------------------------------------
		//	overwrite on edit
		// -------------------------------------

		$out	= array();

		//	Yes
		$out[] = form_radio(array_merge(array(
			'name'		=> 'overwrite_on_edit',
			'id'		=> 'overwrite_on_edit_y',
			'value'		=> 'y',
			'checked'	=> ( isset($data['overwrite_on_edit']) AND
							$data['overwrite_on_edit'] == 'y' )
		)));

		$out[]	= NBS . lang('yes', 'overwrite_on_edit_y') . NBS . NBS;

		//	No
		$out[] = form_radio(array_merge(array(
			'name'		=> 'overwrite_on_edit',
			'id'		=> 'overwrite_on_edit_n',
			'value'		=> 'n',
			'checked'	=> ( empty($data['overwrite_on_edit']) OR
								$data['overwrite_on_edit'] == 'n' )
		)));

		$out[]	= NBS . lang('no', 'overwrite_on_edit_n');

		ee()->table->add_row(
			lang('overwrite_on_edit', 'overwrite_on_edit') .
				'<div class="subtext">' .
					lang('overwrite_on_edit_desc') .
				'</div>',
			implode( NL, $out )
		);


		// -------------------------------------
		//	disable system XSS clean on this field
		// -------------------------------------

		$disable_xss_clean = isset($data['disable_xss_clean']) ?
								$data['disable_xss_clean'] :
								$this->default_settings['disable_xss_clean'];

		$out	= array();

		//	Yes
		$out[] = form_radio(array_merge(array(
			'name'		=> 'disable_xss_clean',
			'id'		=> 'disable_xss_clean_y',
			'value'		=> 'y',
			'checked'	=> ($disable_xss_clean == 'y')
		)));

		$out[]	= NBS . lang('yes', 'disable_xss_clean_y') . NBS . NBS;

		//	No
		$out[] = form_radio(array_merge(array(
			'name'		=> 'disable_xss_clean',
			'id'		=> 'disable_xss_clean_n',
			'value'		=> 'n',
			'checked'	=> ($disable_xss_clean == 'n')
		)));

		$out[]	= NBS . lang('no', 'disable_xss_clean_n');

		ee()->table->add_row(
			lang('disable_xss_clean', 'disable_xss_clean') .
				'<div class="subtext">' .
					lang('disable_xss_clean_desc') .
				'</div>',
			implode( NL, $out )
		);


	}
	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Validate settings before saving on field save
	 *
	 * @access public
	 * @return mixed 	boolean true/false, or array of errors
	 */

	public function validate_settings ($data = array(), $return_settings = FALSE)
	{
		foreach ( $this->default_settings as $key => $val )
		{
			$settings[$key]	= (string) ee()->input->get_post($key);

			if ( isset( $data[ $key ] ) === TRUE )
			{
				$settings[ $key ]	= $data[ $key ];
			}
		}

		// -------------------------------------
		//	$allowed_upload_count
		// -------------------------------------

		$errors = array();

		if ( ! $this->is_positive_intlike($settings['allowed_upload_count']) OR
			$settings['allowed_upload_count'] > $this->max_files)
		{
			$errors[] = lang('invalid_upload_count');
		}

		// -------------------------------------
		//	$file_upload_location
		// -------------------------------------

		$ful = $settings['file_upload_location'];

		$errors = array();

		if ($ful == FALSE OR ! $this->is_positive_intlike($ful))
		{
			$errors[] = lang('file_upload_missing_error');
		}
		else
		{
			$query = ee()->db->select('id, name')
									->where('id', $ful)
									->get('upload_prefs');

			if ($query->num_rows() == 0)
			{
				$errors[] = lang('invalid_file_upload_preference');
			}
		}

		// -------------------------------------
		//	allowed filetypes
		// -------------------------------------

		if ( ! preg_match('/^([a-zA-Z0-9\_\\|]+|\*)$/', $settings['allowed_file_types']))
		{
			$errors[] = lang('invalid_upload_location');
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		if ( ! empty( $errors ) )
		{
			return $errors;
		}

		if ($return_settings)
		{
			return $settings;
		}

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

	public function save_settings ($data = array())
	{
		return $this->validate_settings($data, TRUE);
	}
	//END save_settings


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
		$form_id = $this->form_id;
		$entry_id = $this->entry_id;

		ee()->load->model('freeform_file_upload_model');

		$db_data		= array();

		$upload_prefs	= $this->upload_prefs();

		$data_query = ee()->freeform_file_upload_model->get(
			array(
				'form_id' 	=> $this->form_id,
				'entry_id'	=> $this->entry_id,
				'field_id'	=> $this->field_id
			)
		);

		if (empty($data_query))
		{
			return $data;
		}

		$attachment_count = count($data_query);

		$lang_uploads 		= lang('uploads');
		$lang_upload_count 	= lang('upload_count');

		//add to the over all count
		$notification_obj->variables['attachment_count'] += $attachment_count;

		$output = array();

		foreach ($data_query as $file_data)
		{
			$url 		= urlencode($upload_prefs['url'] . $file_data['filename']);
			$full_path	= $upload_prefs['server_path'] . $file_data['filename'];

			//something to look at if nothing attached
			$output[] = $file_data['filename'];

			//attach woo!
			$notification_obj->email->attach($full_path);

			//this will be an array tag pair
			$notification_obj->variables['attachments'][] = array(
				'fileurl' 	=> $url,
				'filename'	=> $file_data['filename']
			);
		}

		return implode("\n", $output);
	}
	//END display_email_data


	// --------------------------------------------------------------------

	/**
	 * Validate File Upload
	 *
	 * @access 	public
	 * @param	array 	file upload array
	 * @return	array 	errors
	 */

	private function validate_file_upload ($file_array)
	{
		ee()->load->library('upload');
		$prefs = $this->upload_prefs();
		ee()->upload->initialize($prefs);

		$errors = array();

		// Was the file able to be uploaded? If not, determine the reason why.
		if ( ! is_uploaded_file($file_array['tmp_name']))
		{
			$error = ( ! isset($file_array['error'])) ? 4 : $file_array['error'];

			switch($error)
			{
				case 1:	// UPLOAD_ERR_INI_SIZE
					$errors[] = lang('upload_file_exceeds_limit');
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$errors[] = lang('upload_file_exceeds_form_limit');
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					$errors[] = lang('upload_file_partial');
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					$errors[] = lang('upload_no_file_selected');
					break;
				case 6: // UPLOAD_ERR_NO_TMP_DIR
					$errors[] = lang('upload_no_temp_directory');
					break;
				case 7: // UPLOAD_ERR_CANT_WRITE
					$errors[] = lang('upload_unable_to_write_file');
					break;
				case 8: // UPLOAD_ERR_EXTENSION
					$errors[] = lang('upload_stopped_by_extension');
					break;
				default :
					$errors[] = lang('upload_no_file_selected');
					break;
			}

			//have to return now because the rest wont work
			return $errors;
		}

		// Set the uploaded data as class variables
		ee()->upload->file_temp 	= $file_array['tmp_name'];
		ee()->upload->file_size 	= $file_array['size'];
		ee()->upload->file_type 	= preg_replace("/^(.+?);.*$/", "\\1", $file_array['type']);
		ee()->upload->file_type 	= strtolower(trim(stripslashes(ee()->upload->file_type), '"'));
		ee()->upload->file_name 	= $this->prep_filename($file_array['name']);
		ee()->upload->file_ext	 	= ee()->upload->get_extension(ee()->upload->file_name);
		ee()->upload->client_name 	= ee()->upload->file_name;

		// Is the file type allowed to be uploaded?
		if ( ! ee()->upload->is_allowed_filetype())
		{
			$errors[] = lang('upload_invalid_filetype');
		}

		// Convert the file size to kilobytes
		if (ee()->upload->file_size > 0)
		{
			ee()->upload->file_size = round(ee()->upload->file_size/1024, 2);
		}

		// Is the file size within the allowed maximum?
		if ( ! ee()->upload->is_allowed_filesize())
		{
			$errors[] = lang('upload_invalid_filesize');
		}

		//clean
		ee()->upload->file_temp 	= '';
		ee()->upload->file_size 	= '';
		ee()->upload->file_type 	= '';
		ee()->upload->file_name 	= '';
		ee()->upload->file_ext	 	= '';
		ee()->upload->client_name 	= '';

		return $errors;
	}
	//END


	// --------------------------------------------------------------------

	/**
	 * Prep Filename
	 *
	 * Prevents possible script execution from Apache's handling of files multiple extensions
	 * http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
	 * @access 	public
	 * @param	string
	 * @return	string
	 */

	private function prep_filename ($filename)
	{
		if (strpos($filename, '.') === FALSE OR
			ee()->upload->allowed_types == '*')
		{
			return $filename;
		}

		$parts		= explode('.', $filename);
		$ext		= array_pop($parts);
		$filename	= array_shift($parts);

		foreach ($parts as $part)
		{
			if ( ! in_array(strtolower($part), ee()->upload->allowed_types) OR
				ee()->upload->mimes_types(strtolower($part)) === FALSE)
			{
				$filename .= '.'.$part.'_';
			}
			else
			{
				$filename .= '.'.$part;
			}
		}

		$filename .= '.'.$ext;

		return $filename;
	}
	//END prep_filename


	// --------------------------------------------------------------------

	/**
	 * Cycles Between Values
	 *
	 * Takes a list of arguments and cycles through them on each call
	 *
	 * @access	public
	 * @param	string|array	The items that need to be cycled through
	 * @return	string|array
	 */

	function cycle ($items)
	{
		if ( ! is_array($items))
		{
			$items = func_get_args();
		}

		$hash = md5(implode('|', $items));

		if ( ! isset($this->switches[$hash]) OR ! isset($items[$this->switches[$hash] + 1]))
		{
			$this->switches[$hash] = 0;
		}
		else
		{
			$this->switches[$hash]++;
		}

		return $items[$this->switches[$hash]];
	}
	//END cycle()
}
//end class File_upload_freeform_ft