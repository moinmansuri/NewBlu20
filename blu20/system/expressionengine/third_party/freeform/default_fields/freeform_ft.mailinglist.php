<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Freeform Mailing List Fieldtype
 *
 * ExpressionEngine fieldtype interface licensed for use by EllisLab, Inc.
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/default_fields/freeform_ft.mailinglist.php
 */

class Mailinglist_freeform_ft extends Freeform_base_ft
{
	public 	$info 	= array(
		'name' 			=> 'Mailinglist',
		'version' 		=> FREEFORM_VERSION,
		'description' 	=> 'A field that allow users to subscribe to ExpressionEngine Mailing List module lists.'
	);

	public $default_settings 	= array(
		'show_lists_by_default'		=> array(),
		'opt_in_by_default'			=> 'n',
		'send_email_confirmation'	=> 'y'
	);

	private $lists	= array();

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

		$this->info['name'] 		= lang('default_mailinglist_name');
		$this->info['description'] 	= lang('default_mailinglist_desc');
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Maillinglist module enabled?
	 *
	 * @access	public
	 * @return	boolean
	 */

	public function mailinglist_module_enabled()
	{
		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		if ( ee()->config->item('mailinglist_enabled') == 'n' )
		{
			return $cache->set(FALSE);
		}

		if ( isset(ee()->TMPL) AND isset(ee()->TMPL->module_data['Mailinglist']) )
		{
			return $cache->set(TRUE);
		}

		if (ee()->db->table_exists('exp_mailing_lists'))
		{
			return $cache->set(TRUE);
		}

		return $cache->set(FALSE);
	}

	// End mailinglist module enabled



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
		// -------------------------------------
		//	Opt in by default
		// -------------------------------------

		$opt_in_by_default	= array(
			'name'		=> 'opt_in_by_default',
			'id'		=> 'mailinglist_opt_in_by_default',
			'value'		=> 'y',
			'checked'	=> ( ! empty( $data['opt_in_by_default'] ) AND $data['opt_in_by_default'] == 'y' ) ? TRUE: FALSE,
		);

		ee()->table->add_row(
			lang('opt_in_by_default', 'opt_in_by_default') .
				'<div class="subtext">' .
					lang('opt_in_by_default_desc') .
				'</div>',
			//col 2
			form_checkbox($opt_in_by_default) .
			'&nbsp;<label for="mailinglist_opt_in_by_default">' . lang('opt_in_by_default') . '</label>'
		);

		// -------------------------------------
		//	Send email confirmation to join a list
		// -------------------------------------

		$send_email_confirmation	= array(
			'name'		=> 'send_email_confirmation',
			'id'		=> 'mailinglist_send_email_confirmation',
			'value'		=> 'y',
			'checked'	=> ( ! empty( $data['send_email_confirmation'] ) AND $data['send_email_confirmation'] == 'y' ) ? TRUE: FALSE,
		);

		ee()->table->add_row(
			lang('mailinglist_send_email_confirmation', 'mailinglist_send_email_confirmation') .
				'<div class="subtext">' .
					lang('mailinglist_send_email_confirmation_desc') .
				'</div>',
			//col 2
			form_checkbox($send_email_confirmation) .
			'&nbsp;<label for="mailinglist_send_email_confirmation">' . lang('mailinglist_send_email_confirmation') . '</label>'
		);

		// -------------------------------------
		//	Show lists by default
		// -------------------------------------

		if ( ( $this->lists = $this->get_lists(TRUE) ) !== FALSE )
		{
			$count = 0;

			$out[]	= '<ul style="list-style:none; padding:0;">';

			foreach ( $this->lists as $key => $val )
			{
				$count++;

				$check	= array(
					'name'		=> 'show_lists_by_default[]',
					'id'		=> 'mailinglist_show_lists_by_default_' . $count,
					'value'		=> $key,
					'checked'	=> ( empty( $data['show_lists_by_default'] ) OR in_array( $key, $data['show_lists_by_default'] ) === TRUE ) ? TRUE: FALSE,
				);

				$out[]	= '<li>';
				$out[]	= form_checkbox($check);
				$out[]	= '&nbsp;<label for="mailinglist_show_lists_by_default_' . $count . '">' . $val . '</label>';
				$out[]	= '</li>';
			}

			$out[]	= '</ul>';

			ee()->table->add_row(
				lang('show_lists_by_default', 'show_lists_by_default') .
					'<div class="subtext">' .
						lang('show_lists_by_default_desc') .
					'</div>',
				//col 2
				implode( NL, $out )
			);
		}
	}

	//END display_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Settings
	 *
	 * @access	public
	 * @return	string
	 */

	public function save_settings ($data = array())
	{
		// -------------------------------------
		//	validate and return settings
		// -------------------------------------

		if ( $this->validate_settings($data) === TRUE )
		{
			return $this->validate_settings($data,TRUE);
		}

		// -------------------------------------
		//	errors?
		// -------------------------------------

		return $this->actions()->full_stop($this->validate_settings($data));
	}
	//END save_settings


	// --------------------------------------------------------------------

	/**
	 * Get lists
	 *
	 * @access	public
	 * @return	mixed
	 */

	public function get_lists ($show_all = FALSE)
	{
		if (!$this->mailinglist_module_enabled()) return FALSE;

		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		// --------------------------------------------
		// Get lists from DB
		// --------------------------------------------

		ee()->db->select(array('list_id','list_title'));

		// --------------------------------------------
		// Filter?
		// --------------------------------------------

		if ( ! empty( $this->settings['show_lists_by_default'] ) AND is_array( $this->settings['show_lists_by_default'] ) AND $show_all === FALSE )
		{
			ee()->db->where_in( 'list_id', $this->settings['show_lists_by_default'] );
		}

		// --------------------------------------------
		// Query
		// --------------------------------------------

		$query	= ee()->db->get('mailing_lists');

		if ( $query->num_rows() == 0 ) return $cache->set(FALSE);

		foreach ( $query->result_array() as $row )
		{
			$out[ $row['list_id'] ]	= $row['list_title'];
		}

		// --------------------------------------------
		// Return
		// --------------------------------------------

		return $cache->set($out);
	}
	// End get lists


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
		if ( ( $this->lists = $this->get_lists()) === FALSE )
		{
			return lang('no_mailinglists');
		}

		$data_values = explode("\n", trim($data));

		$default_y	= FALSE;

		if ( empty( $this->entry_id ) AND
			! empty( $this->settings['opt_in_by_default'] ) AND
			$this->settings['opt_in_by_default'] == 'y' )
		{
			$default_y	= TRUE;
		}

		$count = 1;

		// -------------------------------------
		//	wrapper params
		// -------------------------------------

		$param_defaults = array(
			'wrapper_open'			=> '<ul>',
			'wrapper_close'			=> '</ul>',
			'row_wrapper_open' 		=> '<li>',
			'row_wrapper_close' 	=> '</li>',
			'label_wrapper_open' 	=> '<label for="%id%">',
			'label_wrapper_close' 	=> '</label>',
			'input_wrapper_open'	=> '',
			'input_wrapper_close'	=> '&nbsp;&nbsp;'
		);

		$params = array_merge($param_defaults, $params);

		if ( ! isset($attr['style']) AND $params['wrapper_open'] == '<ul>')
		{
			$attr['style'] = 'list-style:none; padding:0;';
		}

		//add the first wrapper, and all attributes
		$return = preg_replace(
			'/\>$/',
			' ' . $this->stringify_attributes($attr) . '>',
			$params['wrapper_open']
		);

		$return .= NL;

		foreach ($this->lists as $value => $label)
		{
			$count++;

			if ( ! empty( $data_values ) )
			{
				if ( is_array( $data_values ) AND
					in_array( $value, $data_values ) )
				{
					$checked_y	= TRUE;
					$checked_n	= FALSE;
				}
				else
				{
					$checked_y	= $default_y;
					$checked_n	= !$default_y;
				}
			}

			$return .= $params['row_wrapper_open'];

			$return .= form_hidden(
				$this->field_name . '[' . $value . ']',
				'n'
			);

			// -------------------------------------
			//	yes
			// -------------------------------------

			$id = 'freeform_' . $this->field_name . '_y_' . $count;

			$return .= $params['input_wrapper_open'];

			$return .= form_checkbox(array(
				'name'		=> $this->field_name . '[' . $value . ']',
				'id'		=> $id,
				'value'		=> 'y',
				'checked' 	=> $checked_y
			));

			$return .= $params['input_wrapper_close'];

			$return .= str_replace('%id%', $id, $params['label_wrapper_open']);
			$return .= $label;
			$return .= $params['label_wrapper_close'];

			$return .= $params['row_wrapper_close'] . NL;
		}

		$return .= $params['wrapper_close'] . NL;

		return $return;
	}

	//END display_field


	// --------------------------------------------------------------------

	/**
	 * Validate settings before saving on field save
	 *
	 * @access public
	 * @return mixed 	boolean true/false, or array of errors
	 */

	public function validate_settings ($data = array(), $return_settings = FALSE)
	{
		// -------------------------------------
		//	Cache
		// -------------------------------------

		$cache = new Freeform_cacher(func_get_args(), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		foreach ( $this->default_settings as $key => $val )
		{
			if ( is_array( $val ) AND empty( $data[$key] ) )
			{
				$settings[$key]	= $val;
			}

			$settings[$key]	= ( ! empty( $_POST[$key] ) ) ? $_POST[$key]: '';

			if ( isset( $data[ $key ] ) === TRUE )
			{
				$settings[ $key ]	= $data[ $key ];
			}
		}

		// -------------------------------------
		//	we don't really need to validate anything
		// -------------------------------------

		// -------------------------------------
		//	return
		// -------------------------------------

		if ( ! empty( $errors ) )
		{
			return $cache->set($errors);
		}

		if ( $return_settings === TRUE )
		{
			return $cache->set($settings);
		}

		return TRUE;
	}
	//END validate_settings


	// --------------------------------------------------------------------

	/**
	 * Save Field Data
	 *
	 * @access	public
	 * @param	string 	data to be inserted
	 * @return	string 	data to go into the entry_field
	 */

	public function post_save ($data)
	{
		$out = $this->subscribe_to_lists($data);
	}
	//post_save


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
		// -------------------------------------
		//	is there an email?
		// -------------------------------------

		$emails	= array();

		if ( ee()->input->get_post('email') )
		{
			ee()->load->helper('email');

			$email = ee()->input->get_post('email');
			$email = trim(strip_tags($email));

			if ( valid_email( $email ) )
			{
				$emails[]	= $email;
			}
		}

		if ( empty( $emails ) ) return FALSE;

		// -------------------------------------
		//	process
		// -------------------------------------

		if ( empty( $data ) )
		{
			return FALSE;
		}

		// -------------------------------------
		//	subscribe
		// -------------------------------------

		if ( ( $data = $this->subscribe_to_lists( $data, $emails ) ) === FALSE )
		{
			return FALSE;
		}

		// -------------------------------------
		//	be done
		// -------------------------------------

		return implode( "\n", $data );
	}
	//END save


	// --------------------------------------------------------------------

	/**
	 * Subscribe to lists
	 *
	 * @access	private
	 * @param	array 	emails to be subscribed
	 * @param	array 	lists to be joined
	 */

	private function subscribe_to_lists ($data = array(), $emails = array() )
	{
		if ( empty( $data ) OR ! is_array( $data ) ) return FALSE;

		// -------------------------------------
		//	Cache
		// -------------------------------------

		$cache = new Freeform_cacher(array( $emails, $data ), __FUNCTION__, __CLASS__);
		if ($cache->is_set()){ return $cache->get(); }

		// -------------------------------------
		//	Get lists
		// -------------------------------------

		$this->lists = $this->get_lists();

		// -------------------------------------
		//	Clean
		// -------------------------------------

		$subscribe		= array();
		$unsubscribe	= array();

		foreach ( $data as $key => $val )
		{
			if ( ! is_numeric( $key ) OR ! isset( $this->lists[$key] ) ) continue;

			if ( $val == 'n' )
			{
				$unsubscribe[]	= $key;
			}
			else
			{
				$subscribe[]	= $key;
			}

			$data	= $subscribe;
		}

		// -------------------------------------
		//	Instantiate module
		// -------------------------------------

		if ( class_exists('Mailinglist') === FALSE )
		{
			require PATH_MOD.'/mailinglist/mod.mailinglist'.EXT;
		}

		$mailinglist = new Mailinglist;

		// -------------------------------------
		//	Get current subscriptions
		// -------------------------------------

		$current_subscriptions	= array();

		if ( ! empty( $emails ) )
		{
			$query	= ee()->db->query(
				"SELECT email, list_id
				 FROM exp_mailing_list
				 WHERE email
				 IN ('" . implode( "','", $emails ) . "')
				 AND list_id
				 IN (" . implode( ',', array_keys( $this->lists ) ) . ")"
			);

			foreach ( $query->result_array() as $row )
			{
				$current_subscriptions[ $row['email'] ][ $row['list_id'] ]	= $row['list_id'];
			}
		}

		// -------------------------------------
		//	Loop for email
		// -------------------------------------

		foreach ( $emails as $email )
		{
			// Kill duplicate emails from authorization queue.  This prevents an error if a user
			// signs up but never activates their email, then signs up again.  Note- check for list_id
			// as they may be signing up for two different lists

			$sql	 = "DELETE FROM exp_mailing_list_queue
						WHERE email = '" . ee()->db->escape_str($email) . "'";

			if ( ! empty( $subscribe ) )
			{
				$sqla[]	= " list_id IN ('" . implode( "','", $subscribe ) . "')";
			}

			if ( ! empty( $unsubscribe ) )
			{
				$sqla[]	= " list_id IN ('" . implode( "','", $unsubscribe ) . "')";
			}

			if ( ! empty( $sqla ) )
			{
				$sql	.= " AND (" . implode( " OR ", $sqla ) . ")";
			}

			ee()->db->query( $sql );

			// -------------------------------------
			//	Subscribe
			// -------------------------------------

			$code = ee()->functions->random('alnum', 10);

			foreach ( $subscribe as $list_id )
			{
				if ( isset( $current_subscriptions[$email][$list_id] ) ) continue;

				if ( empty( $this->settings['send_email_confirmation'] ) OR $this->settings['send_email_confirmation'] != 'y' )
				{
					ee()->db->query("INSERT INTO exp_mailing_list (list_id, authcode, email, ip_address)
										  VALUES ('".ee()->db->escape_str($list_id)."', '".$code."', '".ee()->db->escape_str($email)."', '".ee()->db->escape_str(ee()->input->ip_address())."')");
				}
				else
				{
					ee()->db->query("INSERT INTO exp_mailing_list_queue (email, list_id, authcode, date) VALUES ('".ee()->db->escape_str($email)."', '".ee()->db->escape_str($list_id)."', '".$code."', '".time()."')");

					$mailinglist->send_email_confirmation($email, $code, $list_id);
				}
			}

			// -------------------------------------
			//	Unsubscribe
			// -------------------------------------

			if ( ! empty( $current_subscriptions[$email] ) AND ! empty( $unsubscribe ) )
			{
				$uns	= array_intersect( $current_subscriptions[$email], $unsubscribe );
			}

			if ( ! empty( $uns ) )
			{
				$sql	= "DELETE FROM exp_mailing_list WHERE email = '" . ee()->db->escape_str($email) . "' AND list_id IN (" . implode( ',', $uns ) . ")";

				ee()->db->query( $sql );

				$sql	= "DELETE FROM exp_mailing_list_queue WHERE email = '" . ee()->db->escape_str($email) . "' AND list_id IN (" . implode( ',', $uns ) . ")";

				ee()->db->query( $sql );
			}
		}

		// -------------------------------------
		//	return
		// -------------------------------------

		return $cache->set($data);
	}
	//END subscribe to lists
}
//END class Mailing_list_ft
