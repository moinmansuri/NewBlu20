<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Addon Builder - Extension Builder
 *
 * A class that helps with the building of ExpressionEngine Extensions
 *
 * @package		Solspace:Addon Builder
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/
 * @license		http://www.solspace.com/license_agreement/
 * @version		1.4.4
 * @filesource 	addon_builder/extension_builder.php
 */

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once 'addon_builder.php';
}

class Extension_builder_freeform extends Addon_builder_freeform
{

	public $settings			= array();
	public $name				= '';
	public $version				= '';
	public $description			= '';
	public $settings_exist		= 'n';
	public $docs_url			= '';
	public $default_settings	= array();	// The 'settings' field default
	public $extension_defaults	= array();	// Defaults for the exp_extensions fields
	public $hooks				= array();

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	function __construct()
	{
		parent::__construct();

		// --------------------------------------------
		//  Set Required Extension Variables
		// --------------------------------------------

		//lang loader not loaded?
		if ( ! isset(ee()->lang) AND
			! is_object(ee()->lang))
		{
			$this->fetch_language_file($this->lower_name);
		}

		$this->name			= $this->line($this->lower_name.'_label');
		$this->description	= $this->line($this->lower_name.'_description');

		// -------------------------------------
		//	module backups
		// -------------------------------------

		if ($this->name == $this->lower_name.'_label')
		{
			$this->name = $this->line($this->lower_name . '_module_name');
		}

		if ($this->name == $this->lower_name . '_description')
		{
			$this->description = $this->line($this->lower_name . '_module_description');
		}

		// --------------------------------------------
		//  Extension Table Defaults
		// --------------------------------------------

		$this->extension_defaults = array(
			'class'			=> $this->extension_name,
			'settings'		=> '',
			'priority'		=> 10,
			'version'		=> $this->version,
			'enabled'		=> 'y'
		);

		// --------------------------------------------
		//  Default CP Variables
		// --------------------------------------------

		if (REQ == 'CP')
		{
			//BASE is not set until AFTER sessions_end,
			//and we don't want to clobber it.
			$base_const = defined('BASE') ? BASE :  SELF . '?S=0';

			//2.x adds an extra param for base
			if (substr($base_const, -4) != 'D=cp')
			{
				$base_const .= '&amp;D=cp';
			}

			// For 2.0, we have '&amp;D=cp' with BASE and
			// we want pure characters, so we convert it
			$this->base	= str_replace('&amp;', '&', $base_const) .
							'&C=addons_extensions&M=extension_settings&file=' .
							$this->lower_name;

			$this->cached_vars['page_crumb']	= '';
			$this->cached_vars['page_title']	= '';
			$this->cached_vars['base_uri']		= $this->base;

			$this->cached_vars['extension_menu'] = array();
			$this->cached_vars['extension_menu_highlight'] = '';

			//install wizard doesn't load lang shortcut
			if (function_exists('lang'))
			{
				$this->add_crumb(
					lang($this->lower_name.'_label'),
					$this->cached_vars['base_uri']
				);
			}
		}
	}
	//END __construct


	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * @access	public
	 * @return	null
	 */

	function activate_extension()
	{
		$this->update_extension_hooks(TRUE);

		return TRUE;
	}
	// END activate_extension


	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * @access	public
	 * @return	null
	 */

	function disable_extension()
	{
		$this->remove_extension_hooks();
	}
	// END disable_extension


	// --------------------------------------------------------------------

	/**
	 * Last Extension Call Variable
	 *
	 * You know that annoying ee()->extensions->last_call
	 * class variable that some moron put into the Extensions
	 * class for when multiple extensions call the same hook?
	 *  This will take the possible default
	 * parameter and a default value and return whichever is valid.
	 * Examples:
	 *
	 * // Default argument or Last Call?
	 * $argument = $this->last_call($argument);
	 *
	 * // No default argument.  If no Last Call, empty array is default.
	 * $argument = $this->last_call(NULL, array());
	 *
	 *	@access		public
	 *	@param		mixed	$arugument	The default argument sent by ext
	 *	@param		mixed	$default	other default
	 *	@return		mixed
	 */

	function get_last_call($argument, $default = NULL)
	{
		if (ee()->extensions->last_call !== FALSE)
		{
			return ee()->extensions->last_call;
		}
		elseif ($argument !== NULL)
		{
			return $argument;
		}
		else
		{
			return $default;
		}
	}
	// END get_last_call
}
// END Extension_builder Class
