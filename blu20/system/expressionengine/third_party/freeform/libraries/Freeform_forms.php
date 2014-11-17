<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Forms Library
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/libraries/Freeform_forms.php
 */

$__parent_folder = rtrim(realpath(rtrim(dirname(__FILE__), "/") . '/../'), '/') . '/';

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once $__parent_folder . 'addon_builder/addon_builder.php';
}

unset($__parent_folder);

class Freeform_forms extends Addon_builder_freeform
{
	private $data_cache 		= array();
	private $default_path 		= '';
	private $hash_expire;
	private $cookie_expire;
	public  $hash_cookie_name	= 'freeform_multipage_hash';

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct('freeform');

		ee()->load->model('freeform_field_model');
		ee()->load->model('freeform_form_model');
		ee()->load->library('freeform_fields');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * check_duplicate - checks to see if a form has a dupe for logged
	 * in user or by current ID
	 *
	 * @access	public
	 * @param	int  	ID of form to check
	 * @param	string 	what field to prevent on
	 * @param	string 	prevention data to check
	 * @param 	bool  	check site_id as well
	 * @return	bool 	is duplicate
	 */

	public function check_duplicate ($form_id = 0, $prevent_on = '',
									 $check = '',  $site_id = FALSE)
	{
		//admins or form 0, goodbye
		if ( ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		$form_name = ee()->freeform_form_model->table_name($form_id);

		//custom dupe check?
		if ( is_string($prevent_on) AND
			 ! in_array(
				 $prevent_on,
				 array('member_id', 'author_id', 'ip_address', ''),
				 TRUE
			)
		)
		{
			$prevent_field = ee()->freeform_field_model->get_column_name(
				$prevent_on,
				'name'
			);

			//this is sep because we dont want to fall back on
			//something else if this fails. That would confuse
			if ($prevent_field AND
				ee()->db->field_exists($prevent_field, $form_name))
			{
				ee()->db->where($prevent_field, $check);
			}
			else
			{
				return FALSE;
			}
		}
		//user logged in?
		else if ($prevent_on !== 'ip_address' AND
				 ee()->session->userdata['member_id'] != '0' )
		{
			ee()->db->where('author_id', ee()->session->userdata['member_id']);
		}
		//anonymous users
		else if ($prevent_on !== 'ip_address' AND ee()->input->ip_address() != '0.0.0.0')
		{
			ee()->db->where('ip_address', ee()->input->ip_address());
		}
		else
		{
			return FALSE;
		}

		if ($site_id)
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		ee()->db->where('complete', 'y');

		ee()->db->from($form_name);

		return ( ee()->db->count_all_results() > 0 );
	}
	//END check_duplicate



	// --------------------------------------------------------------------

	/**
	 * entry
	 *
	 * inserts/updates data into form
	 *
	 * @access	private
	 * @param	int  		ID of form to check
	 * @param	array 		field input data to validate
	 * @param 	int 		entry_id for updating?
	 * @param 	bool 		store this as a complete entry?
	 * @return	entry_id 	id of insert
	 */

	private function entry ($form_id, $field_input_data, $entry_id = 0, $complete = TRUE)
	{
		if ( ! $this->data->is_valid_form_id($form_id))
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$form_data = $this->data->get_form_info($form_id);

		$edit = FALSE;

		$field_save_data = array(
			'author_id'		=> isset($field_input_data['author_id']) ?
								$field_input_data['author_id'] :
								ee()->session->userdata('member_id'),
			'ip_address'	=> isset($field_input_data['ip_address']) ?
								$field_input_data['ip_address'] :
								ee()->input->ip_address(),
			'entry_date'	=> isset($field_input_data['entry_date']) ?
								$field_input_data['entry_date'] :
								ee()->localize->now,
			'edit_date'		=> isset($field_input_data['edit_date']) ?
								$field_input_data['edit_date'] :
								0,
			'status'		=> isset($field_input_data['status']) ?
									$field_input_data['status'] :
									$form_data['default_status'],
			'site_id'		=> ee()->config->item('site_id')
		);

		if ((int) $entry_id !== 0)
		{
			if ($this->data->is_valid_entry_id($entry_id, $form_id))
			{
				$edit = TRUE;

				//we don't want to undo the previous data
				$field_save_data = array(
					'edit_date' 	=> ee()->localize->now
				);

				if (isset($field_input_data['status']))
				{
					$field_save_data['status'] = $field_input_data['status'];
				}
			}
			else
			{
				$this->actions()->full_stop(
					lang('invalid_entry_id') . ' - ' . $entry_id
				);
			}
		}

		//complete? we need this no matter what
		$field_save_data['complete'] = ((bool) $complete) ? 'y' : 'n';

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if ( ! isset($field_input_data[$field_data['field_name']]))
			{
				continue;
			}

			$fid = ee()->freeform_form_model->form_field_prefix . $field_id;

			//get class instance of field
			$instance =& ee()->freeform_fields->get_field_instance(array(
				'field_id'			=> $field_id,
				'form_id'			=> $form_id,
				'edit'				=> $edit,
				'entry_id'			=> $entry_id,
				'edit'				=> $edit,
				'extra_settings'	=> array('entry_id' => $entry_id)
			));

			$field_save_data[$fid] 	= $instance->save(
				$field_input_data[$field_data['field_name']]
			);
		}

		ee()->load->model('freeform_entry_model');

		ee()->freeform_entry_model->id($form_id);

		if ($edit)
		{
			ee()->freeform_entry_model->update(
				$field_save_data,
				array('entry_id' => $entry_id)
			);
		}
		else
		{
			$entry_id = ee()->freeform_entry_model->insert(
				$field_save_data
			);
		}

		// -------------------------------------
		//	post save
		// -------------------------------------

		foreach ($form_data['fields'] as $field_id => $field_data)
		{
			if ( ! isset($field_input_data[$field_data['field_name']]))
			{
				continue;
			}

			//get class instance of field
			$instance =& ee()->freeform_fields->get_field_instance(array(
				'field_id'			=> $field_id,
				'form_id'			=> $form_id,
				'entry_id'			=> $entry_id,
				'edit'				=> $edit,
				'extra_settings'	=> array('entry_id' => $entry_id)
			));

			$fid = ee()->freeform_form_model->form_field_prefix . $field_id;

			$post_save_data = isset($field_input_data[$field_data['field_name']]) ?
									$field_input_data[$field_data['field_name']] :
								'';

			$instance->post_save($field_input_data[$field_data['field_name']]);
		}

		

		//we dont want this to run on every thing
		if ($complete)
		{
			$this->hash_clean_up();
		}

		

		return $entry_id;
	}
	//END entry


	// --------------------------------------------------------------------

	/**
	 * insert_new_entry
	 *
	 * inserts data into form
	 *
	 * @access	public
	 * @param	int  		ID of form to check
	 * @param	array 		field input data to validate
	 * @return	entry_id 	id of insert
	 */

	public function insert_new_entry ($form_id, $field_input_data)
	{
		return $this->entry($form_id, $field_input_data);
	}
	//END insert_new_entry


	// --------------------------------------------------------------------

	/**
	 * update_entry
	 *
	 * updates data entry
	 *
	 * @access	public
	 * @param	int  	ID of form to check
	 * @param	int 	entry_id to update
	 * @param 	array 	form_input_data to insert
	 * @return	bool 	update successful
	 */

	public function update_entry ($form_id, $entry_id, $form_input_data)
	{
		return $this->entry($form_id, $form_input_data, $entry_id);
	}
	//END update_entry



	// --------------------------------------------------------------------

	/**
	 * get_form_info
	 *
	 * inserts data into form
	 *
	 * @access	public
	 * @param	int  	ID of form to check
	 * @param 	int 	entry_id
	 * @param 	array 	form input data
	 * @param 	string 	32 bit hash
	 * @param 	bool 	are we finished?
	 * @param 	array 	data to store with the hash
	 * @return	array 	form data
	 */

	public function store_multipage_entry ( $form_id, $form_input_data,
											$hash, $finished = FALSE,
											$hash_data = array())
	{
		if ( ! $this->data->is_valid_form_id($form_id))
		{
			return $this->actions()->full_stop(lang('invalid_form_id'));
		}

		$hash_query = ee()->db->get_where(
			'freeform_multipage_hashes',
			array('hash' => $hash)
		);

		if ($hash_query->num_rows() == 0)
		{
			return FALSE;
		}

		$entry_id = (int) $hash_query->row('entry_id');

		if ($entry_id != 0 AND $this->data->is_valid_entry_id($entry_id, $form_id))
		{
			$this->entry($form_id, $form_input_data, $entry_id, $finished);
		}
		else
		{
			$entry_id = $this->entry($form_id, $form_input_data, NULL, $finished);
		}


		if ( ! $finished)
		{
			//adjusts the entry_id and hash expire date
			$this->update_hash(
				$hash,
				array(
					'entry_id' 	=> $entry_id,
					'data' 		=> $this->json_encode($hash_data)
				)
			);
		}
		else
		{
			//clear hash
			$this->remove_hash($hash);

			//remove edit date (this could be optimized elsewhere)
			ee()->db->update(
				ee()->freeform_form_model->table_name($form_id),
				array('edit_date' 	=> 0),
				array('entry_id' 	=> $entry_id)
			);
		}

		return $entry_id;
	}
	//END store_multipage_entry


	// --------------------------------------------------------------------

	/**
	 * Multipage cookie expire time
	 *
	 * @access	public
	 * @return	bool
	 */

	public function cookie_expire_time ()
	{
		if ( ! isset($this->cookie_expire))
		{
			$pref = $this->preference('multi_form_timeout');

			$num  = $this->is_positive_intlike($pref) ? $pref : 7200;

			$this->cookie_expire = ee()->localize->now + $num;
		}

		return $this->cookie_expire;
	}
	//end cookie_expire_time


	// --------------------------------------------------------------------

	/**
	 * start_multipage_hash
	 *
	 * @access	public
	 * @return	string 	32bit hash
	 */

	public function start_multipage_hash ($form_id = 0, $entry_id = 0, $edit = FALSE)
	{
		$hash = md5(uniqid(mt_rand(), TRUE));

		ee()->functions->set_cookie(
			$this->hash_cookie_name,
			$hash,
			$this->hash_expire_time()
		);

		ee()->db->insert(
			'freeform_multipage_hashes',
			array(
				'hash' 			=> $hash,
				'site_id'		=> ee()->config->item('site_id'),
				'date' 			=> ee()->localize->now,
				'ip_address'	=> ee()->input->ip_address(),
				'form_id' 		=> $form_id,
				'entry_id'		=> $this->is_positive_intlike($entry_id) ? $entry_id : 0,
				'edit'			=> $edit ? 'y' : 'n'
			)
		);

		return $hash;
	}
	//END start_multipage_hash


	// --------------------------------------------------------------------

	/**
	 * update_hash
	 *
	 * @access	public
	 * @return	null
	 */

	public function update_hash ($hash = '', $data = array())
	{
		if ($hash == '' OR strlen($hash) !== 32)
		{
			return;
		}

		ee()->functions->set_cookie(
			$this->hash_cookie_name,
			$hash,
			$this->cookie_expire_time()
		);

		ee()->db->update(
			'freeform_multipage_hashes',
			array_merge($data, array('date' => ee()->localize->now)),
			array('hash' => $hash)
		);
	}
	//END update_hash


	// --------------------------------------------------------------------

	/**
	 * remove_hash
	 *
	 * @access	public
	 * @return	null
	 */

	public function remove_hash ($hash = '')
	{
		if ($hash == '' OR strlen($hash) !== 32)
		{
			return;
		}

		ee()->functions->set_cookie(
			$this->hash_cookie_name,
			'',
			''
		);

		ee()->db->delete(
			'freeform_multipage_hashes',
			array('hash' => $hash)
		);
	}
	//END remove_hash


	// --------------------------------------------------------------------

	/**
	 * check multipage hash
	 *
	 * checks to see if the hash is available in cookie or post
	 *
	 * @access	public
	 * @param 	int 		form id
	 * @param	entry_id	is this entry_id > 0 because its perhaps an edit?
	 * @return	string 	hash
	 */

	public function check_multipage_hash ($form_id = 0, $entry_id = 0, $edit = FALSE)
	{
		$hash = FALSE;

		if ( ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		// --------------------------------------------
		//  Check for Existing Hash
		// --------------------------------------------

		if ( ! in_array(
			ee()->input->post($this->hash_cookie_name),
			array('', FALSE),
			TRUE
		))
		{
			$hash = ee()->input->post($this->hash_cookie_name);
		}
		else if ( ! in_array(
			ee()->input->cookie($this->hash_cookie_name),
			array('', FALSE),
			TRUE
		))
		{
			$hash = ee()->input->cookie($this->hash_cookie_name);
		}

		//make sure its 32 chars only
		if ($hash AND strlen($hash) != 32)
		{
			$hash = FALSE;
		}

		if ($this->is_positive_intlike($entry_id) AND $entry_id > 0)
		{
			ee()->db->where('entry_id', $entry_id);
		}

		ee()->db->where('edit', $edit ? 'y' : 'n');

		//optional to keep queries down
		if (ee()->db->get_where(
				'freeform_multipage_hashes',
				array(
					'hash' 			=> $hash,
					'ip_address'	=> ee()->input->ip_address(),
					'form_id' 		=> $form_id
				)
			)->num_rows() == 0)
		{
			$hash = FALSE;
		}

		return $hash;
	}
	//END check_multipage_hash





	// --------------------------------------------------------------------

	/**
	 * Multipage hash expire time
	 *
	 * @access	public
	 * @return	bool
	 */

	public function hash_expire_time ()
	{
		if ( ! isset($this->hash_expire))
		{
			$pref = $this->preference('multi_form_timeout');

			$num  = $this->is_positive_intlike($pref) ? $pref : 7200;

			$this->hash_expire = ee()->localize->now - $num;
		}

		return $this->hash_expire;
	}
	//end hash_expire_time


	// --------------------------------------------------------------------

	/**
	 * hash clean
	 * cleans out old hashes and entries
	 *
	 * delete unfinished entries? Default to yes
	 * 	(the pref is a default no, but no means yes!)
	 *
	 * @access	public
	 * @return	null
	 */

	public function hash_clean_up ()
	{
		//delete entries?
		$delete_entries = ! $this->check_yes(
			$this->preference('keep_unfinished_multi_form')
		);

		//all sites or just this one?
		if ( ! $this->data->show_all_sites())
		{
			ee()->db->where('site_id', ee()->config->item('site_id'));
		}

		//could be faster if we have a lot
		if ( ! $delete_entries)
		{
			ee()->db->select('hash');
		}

		$hashes_q = ee()->db->get_where(
			'freeform_multipage_hashes',
			array('date <' => $this->hash_expire_time())
		);

		if ($hashes_q->num_rows() > 0)
		{
			$hashes 	= array();
			$entry_ids 	= array();

			foreach ($hashes_q->result_array() as $row)
			{
				if ($row['form_id'] != 0 AND $delete_entries)
				{
					if ( ! isset($entry_ids[$row['form_id']]))
					{
						$entry_ids[$row['form_id']] = array();
					}

					$entry_ids[$row['form_id']][] = $row['entry_id'];
				}

				$hashes[] = $row['hash'];
			}

			if ($delete_entries)
			{
				ee()->load->model('freeform_form_model');
				ee()->load->model('freeform_entry_model');
				ee()->load->library('freeform_fields');

				//delete all entries in each form
				//but ONLY if they are incomplete
				foreach ($entry_ids as $form_id => $f_entry_ids)
				{
					if ( ! ee()->db->table_exists(
							ee()->freeform_form_model->table_name($form_id)
						)
					)
					{
						continue;
					}

					//lets make sure they are not complete
					$delete_q = ee()->freeform_entry_model
									->id($form_id)
									->where_in('entry_id', $f_entry_ids)
									->where('complete', 'n')
									->select('entry_id')
									->get();

					if ($delete_q !==  FALSE)
					{
						$delete_ids = array();

						foreach ($delete_q as $row)
						{
							$delete_ids[] = $row['entry_id'];
						}

						ee()->freeform_fields->apply_field_method(array(
							'method' 	=> 'delete',
							'form_id' 	=> $form_id,
							'entry_id'	=> $delete_ids
						));

						ee()->freeform_entry_model
							->id($form_id)
							->where_in('entry_id', $delete_ids)
							->delete();
					}
				}
			}

			if ( ! empty($hashes))
			{
				ee()->db->where_in('hash', $hashes);
				ee()->db->delete('freeform_multipage_hashes');
			}
		}
	}
	//end hash_clean_up



	// --------------------------------------------------------------------

	/**
	 * get_multipage_form_data
	 *
	 * @access	public
	 * @param 	int 	form_id
	 * @param 	string 	hash
	 * @return	mixed
	 */

	public function get_multipage_form_data ($form_id, $hash)
	{
		ee()->load->model('freeform_entry_model');

		$previous_inputs = ee()->freeform_entry_model->get_multipage(
			$form_id,
			$hash
		);

		if ( ! $previous_inputs)
		{
			return FALSE;
		}

		if (isset($previous_inputs['hash_stored_data']) AND
			is_string($previous_inputs['hash_stored_data']))
		{
			$json = array();

			//sadly we cannot trust that json will work out
			try
			{
				$json = $this->json_decode(
					$previous_inputs['hash_stored_data'],
					TRUE
				);
			}
			catch (Exception $e)
			{
				$json = array();
			}

			$previous_inputs['hash_stored_data'] = $json;

			unset($json);
		}

		return $previous_inputs;
	}
	//END get_multipage_form_data


	// --------------------------------------------------------------------

	/**
	 * check keyword banning
	 *
	 * checks to see if the hash is available in post
	 *
	 * @access	public
	 * @param 	int 	form id
	 * @return	boolean
	 */

	public function check_keyword_banning ()
	{
		// --------------------------------------------
		//  Get our list of keywords
		// --------------------------------------------

		$keywords = preg_split(
			"/\r|\n/",
			$this->preference('spam_keywords'),
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		//replace all stars with anything thats not a space for now
		$keywords = str_replace('*', '([^\s]*)', '\b' . implode('\b|\b', $keywords) . '\b');

		//this way everything, including keys and sub arrays get tested
		$test	= print_r($_POST, TRUE);

		return (bool) preg_match('/' . $keywords . '/is', $test);
	}
	//END check_keyword_banning




	// --------------------------------------------------------------------

	/**
	 * Add field to form
	 *
	 * @access public
	 * @param  int		$form_id		id of form to add field to
	 * @param  mixed	$new_field_ids	field id or array of fields to add to form
	 * @return void
	 */

	public function add_field_to_form ($form_id, $new_field_ids)
	{
		if ( ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		$form_data = $this->data->get_form_info($form_id);

		if (is_array($form_data['field_ids']))
		{
			$field_ids = $form_data['field_ids'];
		}
		else if ( is_string($form_data['field_ids']))
		{
			$field_ids = $this->actions()->pipe_split($form_data['field_ids']);
		}
		else
		{
			$field_ids = array();
		}

		sort($field_ids);

		// -------------------------------------
		//	make sure field ids are an array and
		//	clean them
		// -------------------------------------
 		if ( is_string($new_field_ids))
		{
			$new_field_ids = $this->actions()->pipe_split($new_field_ids);
		}

		if ( ! is_array($new_field_ids))
		{
			$new_field_ids = array($new_field_ids);
		}

		$new_field_ids = array_filter($new_field_ids, array($this, 'is_positive_intlike'));

		if (empty($new_field_ids))
		{
			return FALSE;
		}

		sort($new_field_ids);

		// -------------------------------------
		//	check combined
		// -------------------------------------

		$our_powers_combined = array_unique(array_merge($field_ids, $new_field_ids));

		sort($our_powers_combined);

		//if there are no changes we can move on
		if ($field_ids == $our_powers_combined)
		{
			return FALSE;
		}

		// -------------------------------------
		//	update
		// -------------------------------------

		return $this->update_form(
			$form_id,
			array(
				'field_ids' => implode('|', $our_powers_combined)
			)
		);
	}
	//END add_field_to_form


	// --------------------------------------------------------------------

	/**
	 * Remove field from form
	 *
	 * @access public
	 * @param  int $form_id  id of form to remove field from
	 * @param  int $field_id field id to remove
	 * @return null
	 */

	public function remove_field_from_form ($form_id, $field_id)
	{
		$form_data = ee()->freeform_form_model->get_row($form_id);

		$field_ids = $form_data['field_ids'];
		$order_ids = $this->actions()->pipe_split($form_data['field_order']);

		// -------------------------------------
		//	if it isn't present, nothing to remove
		// -------------------------------------

		if ( ! in_array($field_id, $field_ids))
		{
			return;
		}

		// -------------------------------------
		//	remove from field ids
		// -------------------------------------

		//removing the field from field ids is simple because
		//sort order isn't important
		unset($field_ids[array_search($field_id, $field_ids)]);

		sort($field_ids);

		$new_order_ids = array();

		if ( ! empty($order_ids))
		{
			foreach ($order_ids as $id)
			{
				if ($id != $field_id)
				{
					$new_order_ids[] = $id;
				}
			}
		}

		// -------------------------------------
		//	call remove
		// -------------------------------------

		ee()->load->library('freeform_fields');

		$instance =& ee()->freeform_fields->get_field_instance($field_id);

		if (is_callable(array($instance, 'remove_from_form')))
		{
			$instance->remove_from_form($form_id);
		}

		// -------------------------------------
		//	remove field from form
		// -------------------------------------

		$this->update_form(
			$form_id,
			array(
				'field_ids'		=> implode('|', $field_ids),
				'field_order'	=> implode('|', $new_order_ids)
			)
		);
	}
	//END remove_field_from_form


	// --------------------------------------------------------------------

	/**
	 * Create Form
	 *
	 * Creates a form and its corosponding tables
	 *
	 * @access	public
	 * @param 	array 	$data data to insert
	 * @return	int 	form id
	 */

	public function create_form ($data)
	{
		ee()->load->model('freeform_form_model');

		$form_id = ee()->freeform_form_model->insert($data);

		//add fields
		if ( isset($data['field_ids']) AND
			 trim($data['field_ids']) !== '')
		{
			$this->update_form_fields($form_id, $data['field_ids']);
		}

		return $form_id;
	}
	//end create_form


	// --------------------------------------------------------------------

	/**
	 * Update Form
	 *
	 * Updates an existing form with new data and an edit date
	 *
	 * @access	public
	 * @param 	int 	$form_id 	the id desired form to be updated
	 * @param 	array 	$data 		data to insert
	 * @return	null
	 */

	public function update_form ($form_id, $data)
	{
		ee()->load->model('freeform_form_model');

		ee()->freeform_form_model->update($data, array('form_id' => $form_id));

		//add fields
		if ( isset($data['field_ids']))
		{
			$this->update_form_fields($form_id, $data['field_ids']);
		}
	}
	//end update_form


	// --------------------------------------------------------------------

	/**
	 * update_form_fields
	 *
	 * @access	public
	 * @param 	int 	form_id number
	 * @param 	mixed 	pipe delimited list, single id, or array of fields to add/remove
	 * @return	bool 	success
	 */

	public function update_form_fields ($form_id, $field_ids)
	{
		if ( ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		ee()->load->dbforge();
		ee()->load->model('freeform_form_model');
		ee()->load->model('freeform_entry_model');

		// -------------------------------------
		//	form data? form data
		// -------------------------------------

		$table_name 			= ee()->freeform_form_model->table_name($form_id);
		$p_table_name			= (
			substr($table_name, 0,strlen(ee()->db->dbprefix)
		) !== ee()->db->dbprefix) ?
			ee()->db->dbprefix . $table_name :
			$table_name;

		// -------------------------------------
		//	get a list of current fields so we don't overwrite
		// -------------------------------------

		$current_fields 		= ee()->freeform_entry_model
									  ->id($form_id)
									  ->list_table_fields(FALSE);

		//we only want the fields aside from the defaults to test against
		$current_field_names 	= array_diff(
			array_keys(
				ee()->freeform_form_model->default_form_table_columns
			),
			$current_fields
		);

		$old_fields				= array();

		//make current fields $custom_field_id => form_field_name array
		foreach ($current_fields as $field_name)
		{
			if (preg_match(
					'/^' . ee()->freeform_form_model->form_field_prefix . '/',
					$field_name
				)
			)
			{
				$old_fields[(int) str_replace(
					ee()->freeform_form_model->form_field_prefix,
					'',
					$field_name
				)] = $field_name;
			}
		}

		$old_field_ids = array_keys($old_fields);

		//we need to make sure our input is an array
		if ( ! is_array($field_ids))
		{
			//number sets (most common)
			if (stristr($field_ids, '|'))
			{
				$field_ids = $this->actions()->pipe_split($field_ids);
			}
			//just a number
			else if ( is_numeric($field_ids))
			{
				$field_ids = array($field_ids);
			}
			//blank
			else if ( is_string($field_ids) AND trim($field_ids) === '')
			{
				$field_ids = array();
			}
			//at this point its rogue data and we need to stop
			//to avoid errors
			else
			{
				return FALSE;
			}
		}

		// -------------------------------------
		//	add/remove fields
		// -------------------------------------

		$remove = array_unique(array_diff($old_field_ids, $field_ids));
		$add 	= array_unique(array_diff($field_ids, $old_field_ids));

		sort($remove);
		sort($add);

		//removing?
		if ( ! empty($remove))
		{
			ee()->load->library('freeform_fields');

			foreach ($remove as $remove_id)
			{
				//lets not do anything hokey
				if (array_key_exists($remove_id, $old_fields))
				{
					// -------------------------------------
					//	call remove before actually removing
					// -------------------------------------

					$instance =& ee()->freeform_fields->get_field_instance($remove_id);

					if (is_callable(array($instance, 'remove_from_form')))
					{
						$instance->remove_from_form($form_id);
					}

					//because db->list_fields is a stupid POS
					if ($this->column_exists($old_fields[$remove_id], $p_table_name, FALSE))
					{
						ee()->dbforge->drop_column(
							$table_name,
							$old_fields[$remove_id]
						);
					}
				}
			}
		}

		//adding?
		if ( ! empty($add))
		{
			$add_fields = array();

			foreach ($add as $add_id)
			{
				//lets not do anything hokey
				if ( ! array_key_exists($add_id, $old_fields))
				{
					$fid = ee()->freeform_form_model->form_field_prefix . $add_id;
					$add_fields[$fid] = ee()->freeform_form_model->custom_field_info;
				}
			}

			if ( ! empty($add_fields))
			{
				ee()->dbforge->add_column($table_name, $add_fields);
			}
		}
	}
	//END update_form_fields


	// --------------------------------------------------------------------

	/**
	 * Delete Form
	 *
	 * Removes a form and its corosponding entries table
	 *
	 * @access	public
	 * @param 	form_id id of form to delete
	 */

	public function delete_form ($form_id)
	{
		if ( ! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		$form_data = $this->data->get_form_info($form_id);

		if ($form_data == FALSE)
		{
			return FALSE;
		}

		ee()->load->library('freeform_fields');

		if ( ! empty($form_data['field_ids']))
		{
			// -------------------------------------
			//	call remove before actually removing
			// -------------------------------------

			foreach ($form_data['field_ids'] as $field_id)
			{
				$instance =& ee()->freeform_fields->get_field_instance($field_id);

				if (is_callable(array($instance, 'remove_from_form')))
				{
					$instance->remove_from_form($form_id);
				}
			}
		}

		// -------------------------------------
		//	clean any left over hashes
		// -------------------------------------

		$this->hash_clean_up();

		// -------------------------------------
		//	check composer
		// -------------------------------------

		if ($this->is_positive_intlike($form_data['composer_id']))
		{
			//only want to remove it if its just used here.
			$total_used = ee()->freeform_form_model
							  ->where(
							  	'composer_id',
							  	$form_data['composer_id']
							  )
							  ->count();

			if ($total_used <= 1)
			{
				ee()->load->model('freeform_composer_model');
				ee()->freeform_composer_model
					->where('composer_id', $form_data['composer_id'])
					->delete();
			}
		}

		return ee()->freeform_form_model->delete($form_id);
	}
	//END delete_form


	// --------------------------------------------------------------------

	/**
	 * Delete entries from a form (one form)
	 *
	 * @access	public
	 * @param	int		$form_id	form_id to delete entries from
	 * @param	mixed	$entry_ids	array or int of entries to be deleted
	 * @return	mixed				return value of db delete
	 */

	public function delete_entries ($form_id, $entry_ids)
	{
		// -------------------------------------
		//	lots of validation
		// -------------------------------------

		if ( ! $this->is_positive_intlike($form_id) OR
			 ! $this->data->is_valid_form_id($form_id))
		{
			return FALSE;
		}

		if ( ! is_array($entry_ids) AND
			 ! $this->is_positive_intlike($entry_ids))
		{
			return FALSE;
		}

		if ( ! is_array($entry_ids))
		{
			$entry_ids = array($entry_ids);
		}

		$entry_ids = array_filter($entry_ids, array($this, 'is_positive_intlike'));

		if (empty($entry_ids))
		{
			return FALSE;
		}

		// -------------------------------------
		//	pre hook
		// -------------------------------------

		if (ee()->extensions->active_hook('freeform_module_entry_delete') === TRUE)
		{
			ee()->extensions->universal_call(
				'freeform_module_entry_delete',
				$form_id,
				$entry_ids,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}

		// -------------------------------------
		//	MUSH!
		// -------------------------------------

		ee()->load->library('freeform_fields');

		ee()->freeform_fields->apply_field_method(array(
			'method'	=> 'delete',
			'form_id'	=> $form_id,
			'entry_id'	=> $entry_ids
		));

		ee()->load->model('freeform_entry_model');

		return ee()->freeform_entry_model
					->id($form_id)
					->where_in('entry_id', $entry_ids)
					->delete();
	}
	//END delete_entries
}
//END Freeform_forms
