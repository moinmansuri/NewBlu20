<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Notifications Library
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/libraries/Freeform_notifications.php
 */

$__parent_folder = rtrim(realpath(rtrim(dirname(__FILE__), "/") . '/../'), '/') . '/';

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once $__parent_folder . 'addon_builder/addon_builder.php';
}

unset($__parent_folder);

class Freeform_notifications extends Addon_builder_freeform
{
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
		ee()->load->model('freeform_form_model');
		ee()->load->library('email');
		ee()->load->library('freeform_forms');
		ee()->load->library('template');
		ee()->load->helper(array('text', 'email'));
	}
	//END __construct()


	// --------------------------------------------------------------------

	/**
	 * send notification
	 *
	 * @access	public
	 * @param 	array 	options for the notifications
	 * @return	bool 	user is flagged
	 */

	public function send_notification($options = array())
	{
		// -------------------------------------
		//	defaults
		// -------------------------------------

		$defaults = array(
			'form_id'				=> 0,
			'entry_id'				=> 0,
			'notification_type'		=> FALSE,
			'template'				=> 0,
			'recipients'			=> array(),
			'form_input_data'		=> array(),
			'extra_message'			=> '',
			'from_name'				=> ee()->config->item('webmaster_name'),
			'from_email'			=> ee()->config->item('webmaster_email'),
			'reply_to_name'			=> '',
			'reply_to_email'		=> '',
			'cc_recipients'			=> array(),
			'bcc_recipients'		=> array(),
			'include_attachments'	=> '',
			'enable_spam_log'		=> TRUE,
		);

		$options = array_merge($defaults, $options);

		//make local keys, but only from defaults
		//no funny business
		foreach ($defaults as $key => $value)
		{
			$$key = (isset($options[$key]) ? $options[$key] : $value);
		}

		$form_data = $this->data->get_form_info($form_id);

		//checkity check
		if (
			! $form_data OR
			! $this->is_positive_intlike($entry_id) OR
			! $notification_type OR
			/*! is_array($recipients) OR
			empty($recipients) OR*/
			! is_array($form_input_data) OR
			empty($form_input_data) OR
			! valid_email($from_email)
		)
		{
			return FALSE;
		}

		// -------------------------------------
		//	validate recipients
		// -------------------------------------

		if (is_string($recipients))
		{
			$recipients = str_replace('|', ' , ', $recipients);
		}

		$recipients = $this->validate_emails($recipients);
		$recipients = $recipients['good'];

		if ($notification_type == 'admin' AND
			empty($recipients))
		{
			$recipients = array(ee()->config->item('webmaster_email'));
		}

		if ( empty($recipients))
		{
			return FALSE;
		}

		// -------------------------------------
		//	validate cc/bcc (non-critical)
		// -------------------------------------

		if ($cc_recipients)
		{
			$cc_recipients = $this->validate_emails(
				str_replace('|', ' , ', (string) $cc_recipients)
			);

			$cc_recipients = $cc_recipients['good'];
		}

		if ($bcc_recipients)
		{
			$bcc_recipients = $this->validate_emails(
				str_replace('|', ' , ', (string) $bcc_recipients)
			);

			$bcc_recipients = $bcc_recipients['good'];
		}

		// -------------------------------------
		//	prep libs (don't want to load these
		//  before validation in case we bail)
		// -------------------------------------

		//just in case someone else didn't clean up their mess
		ee()->email->clear(TRUE);

		// -------------------------------------
		//	get notification template
		// -------------------------------------

		$template_id = $template;

		if (empty($template_id))
		{
			if ($notification_type == 'admin')
			{
				$template_id = $form_data['admin_notification_id'];
			}
			else if ($notification_type == 'user')
			{
				$template_id = $form_data['user_notification_id'];
			}
		}

		$template_data = '';

		if (empty($template_id))
		{
			$template_data = $this->default_notification_template();
		}
		else
		{
			//if its not an int, check it as name
			$on_column = $this->is_positive_intlike($template_id) ?
									'notification_id' :
									'notification_name';

			ee()->load->model('freeform_notification_model');

			$t_query = ee()->freeform_notification_model->get_row(array(
				$on_column => $template_id
			));

			if ($t_query !== FALSE)
			{
				$template_data = $t_query;
			}
			else
			{
				$template_data = $this->default_notification_template();
			}
		}

		if ( ! valid_email($reply_to_email))
		{
			if ( ! empty($template_data['reply_to_email']))
			{
				$reply_to_email = $template_data['reply_to_email'];
			}
			else
			{
				$reply_to_email = '';
			}
		}

		if (empty($reply_to_name))
		{
			$reply_to_name = $reply_to_email;
		}

		// -------------------------------------
		//	attachments?
		// -------------------------------------

		if ( ! isset($include_attachments) OR $include_attachments == '')
		{
			$include_attachments = (
				$template_data['include_attachments'] AND
				$this->check_yes($template_data['include_attachments'])
			);
		}

		// -------------------------------------
		//	validate $from_name
		// -------------------------------------


		$from_name				= ($template_data['from_name']) ?
									$template_data['from_name'] :
									$from_name;

		$from_email				= ($template_data['from_email']) ?
									$template_data['from_email'] :
									$from_email;

		//----------------------------------------
		//	prep variables for field parsing
		//----------------------------------------

		$this->subject					= $template_data['email_subject'];
		$this->message					= $template_data['template_data'];
		$this->email					=& ee()->email;
		$this->all_form_fields			= array();
		$this->all_form_fields_string	= array();
		$this->fields					= array();
		$this->wordwrap					= $this->check_yes($template_data['wordwrap']);
		$this->mailtype					= (
			$this->check_yes($template_data['allow_html'])
		) ? 'html': 'text';


		//we need some custom vars from form data and all of the fields
		$this->variables		= array_merge(array(
			'form_name'				=> $form_data['form_name'],
			'form_label'			=> $form_data['form_label'],
			'form_id'				=> $form_data['form_id'],
			'freeform_entry_id'		=> $entry_id,
			'entry_date'			=> time(),
			'attachments'			=> array(),
			'attachment_count'		=> 0,
		));

		$this->field_inputs		= $form_input_data;
		$this->field_outputs	= array();

		// -------------------------------------
		//	get field order for all_form_fields
		// -------------------------------------

		$field_loop_ids = array_keys($form_data['fields']);
		$field_order	= $form_data['field_order'];

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

		// -------------------------------------
		//	get instance of field and parse
		// -------------------------------------

		foreach ($field_loop_ids as $field_id)
		{
			if ( ! isset($form_data['fields'][$field_id]))
			{
				continue;
			}

			$field_data = $form_data['fields'][$field_id];

			//if this is a composer form, and the field is not a
			//member of the form, continue out
			if ( ! empty( $form_data['composer_field_ids'] ) AND
				 ! in_array( $field_id, $form_data['composer_field_ids'] ) )
			{
				continue;
			}

			//get class instance of field
			$instance =& ee()->freeform_fields->get_fieldtype_instance(
				$field_data['field_type']
			);

			$instance->form_id		= $form_id;
			$instance->entry_id		= $entry_id;
			$instance->field_id		= $field_id;
			$instance->field_name	= $field_data['field_name'];
			$i_settings				= json_decode($field_data['settings'], TRUE);
			$instance->settings		= array_merge(
				(is_array($i_settings) ? $i_settings : array()),
				array(
					'entry_id' => $entry_id
				)
			);

			$output_data = $instance->display_email_data(
				(isset($this->field_inputs[$field_data['field_name']]) ?
					$this->field_inputs[$field_data['field_name']] :
					''
				),
				$this
			);

			if ( ! is_string($output_data))
			{
				if (is_array($output_data))
				{
					$output_data = implode("\n", $output_data);
				}
				else
				{
					$output_data = (string) $output_data;
				}
			}

			if ($this->mailtype == 'html')
			{
				$output_data = str_replace("\n", '<br/>', $output_data);
			}

			//fully builds out all fields for auto setup
			$this->all_form_fields_string[] = $field_data['field_label'] . ': ' .
												$output_data;

			$this->all_form_fields[] = array(
				'field_label'		=> $field_data['field_label'],
				'field_description'	=> $field_data['field_description'],
				'field_type'		=> $field_data['field_type'],
				'field_data'		=> $output_data
			);

			$this->field_outputs[$field_data['field_name']] = $output_data;

			//for legacy hooks
			$this->fields[$field_data['field_name']] = $field_data['field_label'];
		}
		//END foreach ($form_data['fields']...

		// -------------------------------------
		//	include attachments?
		// 	some addons might have inlcluded attachments
		// -------------------------------------

		if ( ! $include_attachments)
		{
			//this is going to clear any attachments
			//that any of these fields have had privy to add
			ee()->email->clear(TRUE);
			$this->variables['attachment_count'] = 0;
		}
		//we want all attachments at the top
		else if ($this->variables['attachment_count'] > 0)
		{
			//add final attachment count to all form fields
			array_unshift($this->all_form_fields_string,
				lang('attachments')  . ': ' .
					$this->variables['attachment_count']);

			array_unshift($this->all_form_fields, array(
				'field_label'	=> lang('attachments'),
				'field_type'	=> '',
				'field_data'	=> $this->variables['attachment_count']
			));
		}

		// -------------------------------------
		//	conditionals, date formats, replacements, etc. FUN!
		// -------------------------------------

		$this->subject	= ee()->template->parse_variables(
			$this->subject,
			array(array_merge($this->variables, $this->field_outputs))
		);

		$from_email		= ee()->template->parse_variables(
			$from_email,
			array(array_merge($this->variables, $this->field_outputs))
		);

		$from_name		= ee()->template->parse_variables(
			$from_name,
			array(array_merge($this->variables, $this->field_outputs))
		);

		$reply_to_email	= ee()->template->parse_variables(
			$reply_to_email,
			array(array_merge($this->variables, $this->field_outputs))
		);

		$reply_to_name	= ee()->template->parse_variables(
			$reply_to_name,
			array(array_merge($this->variables, $this->field_outputs))
		);

		//we don't want all form fields going into the subject
		//that would be silly
		$this->variables['all_form_fields_string'] = implode(
			($this->check_yes($template_data['allow_html']) ? "<br/>" : "\n"),
			$this->all_form_fields_string
		);

		$this->variables['all_form_fields'] = $this->all_form_fields;

		$this->message = ee()->template->parse_variables(
			$this->message,
			array(array_merge($this->variables, $this->field_outputs))
		);

		// -------------------------------------
		//	parse standard template data
		// -------------------------------------

		$this->message = $this->actions()->template()
							  ->process_string_as_template(
								  $this->message
							  );

		// -------------------------------------
		//	hook prep
		// -------------------------------------

		//this will allow adding or removing of emails through the hook
		$this->variables['recipients']		= $recipients;
		$this->variables['cc_recipients']	= $cc_recipients;
		$this->variables['bcc_recipients']	= $bcc_recipients;
		$this->variables['reply_to_email']	= $reply_to_email;
		$this->variables['reply_to_name']	= $reply_to_name;
		$this->variables['message']			= $this->variables['msg'] = $this->message;
		$this->variables['subject']			= $this->subject;
		$this->variables['from_name']		= $from_name;
		$this->variables['from_email']		= $from_name;
		$this->variables['field_inputs']	=& $this->field_inputs;
		$this->variables['field_outputs']	=& $this->field_outputs;

		// -------------------------------------
		//	freeform_recipient_email' hook.
		//	This allows developers to alter the
		//	$this->variables array before admin notification is sent.
		// -------------------------------------

		$hook_name = 'freeform_recipient_email';

		if ($notification_type == 'admin')
		{
			$hook_name = 'freeform_module_admin_notification';
		}
		else if ($notification_type == 'user')
		{
			$hook_name = 'freeform_module_user_notification';
		}

		if (ee()->extensions->active_hook($hook_name) === TRUE)
		{
			$this->variables = ee()->extensions->universal_call(
				$hook_name,
				$this->fields,
				$entry_id,
				$this->variables,
				$form_id,
				$this
			);

			if (ee()->extensions->end_script === TRUE) return;
		}

		// -------------------------------------
		//	post hook var prep
		// -------------------------------------

		$recipients			= $this->variables['recipients'];
		$cc_recipients		= $this->variables['cc_recipients'];
		$bcc_recipients		= $this->variables['bcc_recipients'];
		$reply_to_email		= $this->variables['reply_to_email'];
		$reply_to_name		= $this->variables['reply_to_name'];

		//if the message has changed, copy back
		if ($this->variables['message'] !== $this->message)
		{
			$this->message = $this->variables['message'];
		}
		//legacy
		else if ($this->variables['msg'] !== $this->message)
		{
			$this->message = $this->variables['msg'];
		}

		$this->subject = $this->variables['subject'];

		//	----------------------------------------
		//	Send email
		//	----------------------------------------

		ee()->email->wordwrap	= $this->wordwrap;
		ee()->email->mailtype	= $this->mailtype;

		$ascii_message = entities_to_ascii($this->message, ! $template_data['allow_html']);

		// -------------------------------------
		//	cc/bcc?
		// 	these will only run once
		// -------------------------------------

		if (is_array($cc_recipients) AND ! empty($cc_recipients))
		{
			ee()->email->cc($cc_recipients);
		}

		if (is_array($bcc_recipients) AND ! empty($bcc_recipients))
		{
			ee()->email->bcc($bcc_recipients);
		}

		//all recipients
		foreach ($recipients as $email_address)
		{
			if ($reply_to_email AND valid_email($reply_to_email))
			{
				ee()->email->reply_to($reply_to_email, $reply_to_name);
			}

			ee()->email->from($from_email, $from_name);
			ee()->email->to($email_address);
			ee()->email->subject(
				//have to remove all newlines
				//because godaddy barfs on this
				preg_replace(
					"/[\r\n]+/ms",
					' ',
					entities_to_ascii($this->subject, TRUE)
				)
			);
			ee()->email->message($ascii_message);
			ee()->email->send();

			//clear out but keep attachments
			//clear last so the first email can get the CC and BCC
			//on the first item sent
			ee()->email->clear(FALSE);
		}

		//needs a cleanout so the next notification can go
		ee()->email->clear(TRUE);

		// -------------------------------------
		//	clear local vars
		// -------------------------------------

		unset(
			$this->message,
			$this->subject,
			$this->variables,
			$this->all_form_fields,
			$this->email,
			$this->field_inputs,
			$this->field_outputs
		);

		//	----------------------------------------
		//	Register the template used
		//	----------------------------------------

		if ( $notification_type != 'admin' AND $enable_spam_log)
		{
			$this->save_spam_interval(
				$form_id,
				$entry_id,
				$recipients
			);
		}

		return TRUE;
	}
	//END send_notification


	// --------------------------------------------------------------------

	/**
	 * save spam interval
	 *
	 * inserts email recipient count and user data for spam check
	 *
	 * @access	private
	 * @param 	int 	form_id to check
	 * @return	bool 	user is flagged
	 */

	private function save_spam_interval ($form_id, $entry_id, $email_list)
	{
		ee()->db->insert(
			'freeform_user_email',
			array(
				'email_count' 		=> count($email_list),
				'email_addresses' 	=> implode(', ', $email_list),
				'entry_id' 			=> $entry_id,
				'form_id'			=> $form_id,
				'entry_date'		=> ee()->localize->now,
				'ip_address' 		=> ee()->input->ip_address(),
				'author_id' 		=> ee()->session->userdata('member_id'),
				'site_id' 			=> ee()->config->item('site_id')
			)
		);

		// -------------------------------------
		//	clean old
		// -------------------------------------

		$time_check = (
			ee()->localize->now - (
				60 * ( (int) $this->preference('spam_interval') )
			)
		);

		ee()->db->delete(
			'freeform_user_email',
			array('entry_date <' => $time_check)
		);
	}
	//END save_spam_interval


	// --------------------------------------------------------------------

	/**
	 * check spam interval
	 *
	 * checks email recipient interval based on entries
	 *
	 * @access	public
	 * @param 	int 	form_id to check
	 * @return	bool 	user is flagged
	 */

	public function check_spam_interval ($form_id = 0)
	{
		if ($this->check_no($this->preference('enable_spam_prevention')) or
			! $this->is_positive_intlike($form_id))
		{
			return FALSE;
		}

		//	----------------------------------------
		//	Check for spamming or hacking
		//	----------------------------------------

		$time_check = (
			ee()->localize->now - (
				60 * ( (int) $this->preference('spam_interval') )
			)
		);

		$t1 = 'exp_freeform_user_email';
		$t2 = ee()->freeform_form_model->table_name($form_id);

		ee()->db->select_sum($t1 . '.email_count');
		ee()->db->from($t1 . ',' . $t2);
		ee()->db->where("{$t2}.entry_id = {$t1}.entry_id");
		ee()->db->where($t2 . '.ip_address', ee()->input->ip_address());
		ee()->db->where($t2 . '.entry_date >', $time_check);

		$query = ee()->db->get();

		if ( $query->row('email_count') > $this->preference('spam_count') )
		{
			return TRUE;
		}

		FALSE;
	}
	//END check_spam_interval


	// --------------------------------------------------------------------

	/**
	 * default notification template
	 *
	 * returns the default notification template from config files and
	 * lang files
	 *
	 * @final
	 * @access	public
	 * @return	array 	default notification data
	 */

	final public function default_notification_template ()
	{
		static $default_notification_template = array();

		if (empty($default_notification_template))
		{
			$default_notification_template = array(
				'notification_id' 			=> '0',
				'notification_name' 		=> 'default_notification',
				'notification_label' 		=> lang('default_notification'),
				'notification_type' 		=> 'default',
				'notification_description' 	=> lang('default_notification'),
				'wordwrap' 					=> 'y',
				'allow_html' 				=> 'n',
				'from_name' 				=> ee()->config->item('webmaster_name'),
				'from_email' 				=> ee()->config->item('webmaster_email'),
				'reply_to_email' 			=> ee()->config->item('webmaster_name'),
				'email_subject' 			=> lang('default_notification_subject'),
				'template_data' 			=> lang('default_notification_template'),
				'include_attachments'		=> 'y'
			);
		}

		return $default_notification_template;
	}
	//END default_notification_template
}
//END Freeform_notifications
