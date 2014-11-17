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

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once 'addon_builder/addon_builder.php';
}

class Freeform_actions extends Addon_builder_freeform
{
	/**
	 * Test Mode?
	 *
	 * @var boolean
	 * @see full_stop
	 */
	public $test_mode = FALSE;

	// --------------------------------------------------------------------

	/**
	 * full stop
	 *
	 * stop on ajax or user error
	 *
	 * @access	public
	 * @param 	mixed 	string error message
	 * @param 	string 	show_user_error type
	 * @return	null
	 */

	public function full_stop ($errors = '', $error_type = 'submission')
	{
		if ( ! is_array($errors))
		{
			$errors = array($errors);
		}

		if ($this->is_ajax_request())
		{
			$this->send_ajax_response(array(
				'success' => FALSE,
				'errors' => $errors
			));
		}
		else
		{
			//the error array might have sub arrays
			//so we need to flatten
			$error_return = array();

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

			$this->show_error($error_return);
		}

		if ($this->test_mode)
		{
			return;
		}
		else
		{
			exit();
		}
	}
	//END full_stop


	// --------------------------------------------------------------------

	/**
	 * Split a string by pipes with no empty items
	 * Because I got really tired of typing this.
	 *
	 * @access public
	 * @param  string $str pipe delimited string to split
	 * @return array      array of results
	 */

	public function pipe_split ($str)
	{
		return preg_split('/\|/', $str,	-1,	PREG_SPLIT_NO_EMPTY);
	}
	//END pipe_split


	// --------------------------------------------------------------------

	/**
	 * Gives back memory percent used of php.ini limt
	 *
	 * @access public
	 * @return float percent total allowed memory used to 2 decimals
	 */

	public function percent_memory_used ()
	{
		static $limit = FALSE;

		if ($limit == FALSE)
		{
			$limit		= ini_get('memory_limit');
			$last		= strtolower($limit[strlen($limit)-1]);

			switch($last)
			{
				// The 'G' modifier is available since PHP 5.1.0
				case 'g':
					$limit = substr($limit,0,(strlen($limit)-1));
					$limit *= 1024 * 1024 * 1024;
				break;
				case 'm':
					$limit = substr($limit,0,(strlen($limit)-1));
					$limit *= 1024 * 1024;
				break;
				case 'k':
					$limit = substr($limit,0,(strlen($limit)-1));
					$limit *= 1024;
				break;
			}

			unset($last);
		}

		return round(((memory_get_usage() / $limit) * 100), 2);
	}
	//END percent_memory_used


	// --------------------------------------------------------------------

	/**
	 * Template parser instance.
	 *
	 * @access public
	 * @return object template parser instance
	 */

	public function template ()
	{
		if ( ! isset(ee()->TMPL) OR ! is_object(ee()->TMPL))
		{
			if ( ! class_exists('Addon_builder_parser_freeform'))
			{
				require_once $this->addon_path . 'addon_builder/parser.addon_builder.php';
			}

			ee()->TMPL = new Addon_builder_parser_freeform ();
		}

		return ee()->TMPL;
	}
	//END template_parser


	// --------------------------------------------------------------------

	/**
	 * Decodes entities in a loop
	 *
	 * @access	public
	 * @param	mixed $item	items to be checked for strings or arrays of string to decode
	 * @return	mixed		[description]
	 */

	public function decode_entities ($item)
	{
		if (is_array($item))
		{
			foreach ($item as $key => $value)
			{
				$item[$key] = $this->decode_entities($value);
			}

			return $item;
		}
		else if (is_string($item))
		{
			return html_entity_decode($item);
		}
		else
		{
			return $item;
		}
	}
	//END decode_entities


	// --------------------------------------------------------------------

	/**
	 * Is a file upload present for the field?
	 *
	 * @access	public
	 * @param	string $name	name of field to check
	 * @return	boolean			false if nothing found,
	 *							true if at least one file upload
	 */

	public function file_upload_present($name = '', $previous_inputs = array())
	{
		$result = FALSE;

		if (isset($_FILES[$name]['error']))
		{
			foreach($_FILES[$name]['error'] as $error)
			{
				if ($error !== UPLOAD_ERR_NO_FILE)
				{
					$result = TRUE;
					break;
				}
			}
		}


		//no result means possible empty file fields posted
		//without the file field check this would return true for
		//all non-file fields
		if ( ! $result && isset($_FILES[$name]) && isset($previous_inputs[$name]))
		{
			$result = TRUE;
		}

		return $result;
	}
	//END file_upload_present


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
		//EE 2.6+?
		if (is_callable(array(ee()->localize, 'format_date')))
		{
			return ee()->localize->format_date(
				preg_replace(
					'/([a-zA-Z]{1})/is',
					'%$1',
					$this->preference('cp_date_formatting')
				),
				$date
			);
		}
		else
		{
			return ee()->localize->decode_date(
				preg_replace(
					'/([a-zA-Z]{1})/is',
					'%$1',
					$this->preference('cp_date_formatting')
				),
				$date
			);
		}
	}
	//END format_cp_date
}
// END Freeform_actions Class