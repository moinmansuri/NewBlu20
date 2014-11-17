<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Addon Builder - Base Class
 *
 * A class that helps with the building of ExpressionEngine Add-Ons
 * Supports EE 2.4.0+
 *
 * @package		Solspace:Addon Builder
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/
 * @license		http://www.solspace.com/license_agreement/
 * @version		1.4.4
 * @filesource 	addon_builder/addon_builder.php
 */

//--------------------------------------------
//	Alias to get_instance()
//--------------------------------------------

//pre EE 2.6
if ( ! function_exists('ee') )
{
	function ee()
	{
		return get_instance();
	}
}

class Addon_builder_freeform
{
	/**
	 * AOB version
	 * $this->version is already used by EE for modules.
	 *
	 * @var string
	 */
	static $class_version		= '1.4.4';

	/**
	 * Current EE version
	 * @var string
	 * @see __construct()
	 */
	public $ee_version			= '2';

	/**
	 * Internal cache
	 *
	 * @var array
	 * @see set_cache
	 */
	public $cache				= array();

	/**
	 * Object level for views
	 *
	 * @var	integer
	 * @see	view
	 */
	public $ob_level			= 0;

	/**
	 * cached output variables for view
	 *
	 * @var array
	 * @see view
	 */
	public $cached_vars			= array();

	/**
	 * cached item switching
	 *
	 * @var array
	 * @see cycle
	 */
	public $switches			= array();

	/**
	 * The general class name (ucfirst with underscores)
	 * used in database and class instantiation
	 *
	 * @var string
	 * @see __construct
	 */
	public $class_name			= '';

	/**
	 * The lowercased class name
	 * used for referencing module files and in URLs
	 *
	 * @var string
	 * @see __construct
	 */
	public $lower_name			= '';

	/**
	 * uppercase class name
	 * used for checking defined constants
	 *
	 * @var string
	 * @see __construct
	 */
	public $upper_name			= '';

	/**
	 * The name that we put into the Extensions DB table
	 *
	 * @var string
	 * @see __construct
	 */
	public $extension_name		= '';

	/**
	 * Module disabled? Typically used when an update is in progress.
	 *
	 * @deprecated
	 * @var boolean
	 */
	public $disabled			= FALSE;

	/**
	 * Path to addon using AOB
	 *
	 * @var string
	 * @see __construct
	 */
	public $addon_path			= '';

	/**
	 * EE module version number
	 *
	 * @var string
	 * @see __construct
	 */
	public $version				= '';

	/**
	 * Crumbs array for CP
	 *
	 * @var array
	 * @see add_crumb
	 * @see build_crumb
	 */
	public $crumbs				= array();

	/**
	 * Object instance for data file instance
	 *
	 * @var mixed
	 * @see data
	 */
	public $data				= FALSE;

	/**
	 * object instance for actions file instance
	 *
	 * @var boolean
	 * @see actions
	 */
	public $actions				= FALSE;

	/**
	 * cache for module prefernece
	 *
	 * @var array
	 * @see get_prefernece
	 */
	public $module_preferences	= array();

	/**
	 * Remote data holder for fetch_url
	 *
	 * @var string
	 * @see fetch_url
	 */
	public $remote_data			= '';

	/**
	 * Short Cuts
	 *
	 * @var object
	 * @see generate_shortcuts
	 */
	public $sc;

	/**
	 * for upper right link building
	 *
	 * @var array
	 * @see build_right_links
	 */
	public $right_links			= array();

	/**
	 * Member Fields array
	 *
	 * @var array
	 * @see mfields
	 */
	public $mfields				= array();

	/**
	 * Updater object instance
	 *
	 * @var object
	 * @see udpater
	 */
	public $updater;

	/**
	 * AOB path to this folder
	 *
	 * @var string
	 * @see __construct
	 */
	public $aob_path			= '';

	/**
	 * Automatically create class variables
	 * commonly used with pagination
	 *
	 * This lets universal_pagination and
	 * parse_pagination easily work together
	 *
	 * @var boolean
	 * @see universal_pagination
	 * @see parse_pagination
	 */
	public $auto_paginate 		= FALSE;

	/**
	 * Local language array cache for items
	 * that manually load lang lines
	 *
	 * @var array
	 * @see fetch_language_file
	 * @see line
	 */
	public $language			= array();

	/**
	 * Local is_loaded cache for lang files
	 *
	 * @var array
	 * @see fetch_language_file
	 * @see line
	 */
	public $is_loaded			= array();

	/**
	 * Settings array for extensions
	 *
	 * @var array
	 */
	public $settings			= array();

	/**
	 * helper for regexes needing conversion
	 *
	 * @var string
	 */
	public $t_slash				= "/";

	/**
	 * Unit test mode bool for alternating
	 * to test objects instead of helper functions
	 *
	 * @var boolean
	 */
	protected $unit_test_mode	= FALSE;

	/**
	 * Unit test dummy object for helpers
	 * that cannot be mocked
	 *
	 * @var object
	 * @see test_object
	 */
	protected $test_mock;

	/**
	 * Sites array in site_id => site_label form
	 *
	 * @var array
	 * @see get_sites
	 */
	protected $sites;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		// -------------------------------------
		//	ee version
		// -------------------------------------

		//normal
		if (defined('APP_VER'))
		{
			$this->ee_version = APP_VER;
		}
		//install wizard
		else if (isset(ee()->version))
		{
			$this->ee_version = ee()->version;
		}

		// -------------------------------------
		//	because this is not always loaded
		// -------------------------------------

		if ( ! function_exists('lang') AND (
			file_exists(BASEPATH . 'helpers/language_helper.php') OR
			file_exists(APPPATH . 'helpers/language_helper.php')
			))
		{
			ee()->load->helper('language');
		}


		// -------------------------------------
		//	standard paths
		// -------------------------------------

		//path to this folder
		$this->aob_path = rtrim(realpath(dirname(__FILE__)), '/') . '/';

		$this->EE =& get_instance();

		//--------------------------------------------
		//	legacy t_slash until we clean them out
		//	of all addons
		//--------------------------------------------

		if ( ! defined('T_SLASH'))
		{
			define('T_SLASH', $this->t_slash);
		}

		//--------------------------------------------
		// Auto-Detect Name
		//--------------------------------------------

		$name = preg_replace('/^Addon_builder_/ms', '', __CLASS__);

		//--------------------------------------------
		// Important Class Vars
		//--------------------------------------------

		$this->lower_name		= strtolower(
			ee()->security->sanitize_filename($name)
		);
		$this->upper_name		= strtoupper($this->lower_name);
		$this->class_name		= ucfirst($this->lower_name);
		$this->extension_name	= $this->class_name . '_ext';

		// -------------------------------------
		//	set short cuts (must be done after lowername)
		// -------------------------------------

		$this->sc = $this->generate_shortcuts();

		// -------------------------------------
		//	set cache
		// -------------------------------------

		$this->set_cache();

		//--------------------------------------------
		// Add-On Path
		//--------------------------------------------

		$this->addon_path = PATH_THIRD . $this->lower_name . '/';

		// -------------------------------------
		//	package path loaded?
		// -------------------------------------
		//	Sometimes our package path isn't
		//	auto loaded in the Install Wiz *sigh*
		// -------------------------------------

		$paths = ee()->load->get_package_paths();

		if ( ! in_array($this->addon_path, $paths))
		{
			ee()->load->add_package_path($this->addon_path);
		}

		//--------------------------------------------
		// Language auto load
		//--------------------------------------------

		//EE autoloads lang for us, so lets check that first
		if (
			//install wizrd doesn't set EE lang
			! isset(ee()->lang->is_loaded) OR
			(
			! in_array($this->lower_name . '_lang.php', ee()->lang->is_loaded) AND
			! in_array('lang.' . $this->lower_name . '.php', ee()->lang->is_loaded)
			)
		)
		{
			if (isset(ee()->lang) AND
				is_object(ee()->lang) AND
				//loading before userdata is set can screw up per user lang
				//settings because the is_loaded cache does not flush
				//after sessions load :/
				isset(ee()->session->userdata['language'])
			)
			{
				ee()->lang->loadfile($this->lower_name);
			}
			else
			{
				//add our lang file to the EE->lang->langauge array
				//without adding it to EE->lang->is_loaded
				//list so once we get our user's session lang setting,
				//it will override it properly
				$this->fetch_language_file($this->lower_name);
			}
		}

		//--------------------------------------------
		// Module Constants
		//--------------------------------------------

		if ( defined($this->upper_name.'_VERSION') == FALSE AND
			 file_exists($this->addon_path.'constants.'.$this->lower_name.'.php'))
		{
			require_once $this->addon_path.'constants.'.$this->lower_name.'.php';
		}

		if (defined($this->upper_name.'_VERSION'))
		{
			$this->version = constant($this->upper_name.'_VERSION');
		}

		if (defined($this->upper_name.'_DOCS_URL'))
		{
			$this->docs_url = constant($this->upper_name.'_DOCS_URL');
		}

		$this->data();

		//--------------------------------------------
		// Important Cached Vars - Used in Both Extensions and Modules
		//--------------------------------------------

		$this->cached_vars['XID_SECURE_HASH'] 	= (
			! defined('XID_SECURE_HASH')
		) ? '' : XID_SECURE_HASH;

		$this->cached_vars['page_crumb']		= '';
		$this->cached_vars['page_title']		= '';
		$this->cached_vars['text_direction']	= 'ltr';
		$this->cached_vars['message']			= '';
		$this->cached_vars['caller'] 			=& $this;
		$this->cached_vars['theme_url']			= $this->sc->addon_theme_url;
		$this->cached_vars['addon_theme_url']	= $this->sc->addon_theme_url;

		//--------------------------------------------
		// Determine View Path for Add-On
		//--------------------------------------------

		if ( isset($this->cache['view_path']))
		{
			$this->view_path = $this->cache['view_path'];
		}
		else
		{
			$possible_paths = array(
				$this->addon_path . 'views/',
				$this->addon_path . 'views/2.x/' //legacy
			);

			foreach(array_unique($possible_paths) as $path)
			{
				if ( is_dir($path))
				{
					$this->view_path = $path;
					$this->cache['view_path'] = $path;
					break;
				}
			}
		}
	}
	// END __construct()


	// --------------------------------------------------------------------

	/**
	 * Sets cache link to class
	 *
	 * @access public
	 * @return object	cache instance
	 */

	public function set_cache()
	{
		//helps clean this ugly ugly code
		$s = 'solspace';
		$l = $this->lower_name;
		$g = 'global';
		$c = 'cache';
		$b = 'addon_builder';
		$a = 'addon';

		//no sessions? lets use global until we get here again
		if ( ! isset(ee()->session) OR
			! is_object(ee()->session))
		{
			if ( ! isset($GLOBALS[$s][$c][$b][$a][$l]))
			{
				$GLOBALS[$s][$c][$b][$a][$l] = array();
			}

			$this->cache =& $GLOBALS[$s][$c][$b][$a][$l];

			if ( ! isset($GLOBALS[$s][$c][$b][$g]) )
			{
				$GLOBALS[$s][$c][$b][$g] = array();
			}

			$this->global_cache =& $GLOBALS[$s][$c][$b][$g];
		}
		//sessions?
		else
		{
			//been here before?
			if ( ! isset(ee()->session->cache[$s][$b][$a][$l]))
			{
				//grab pre-session globals, and only unset the ones for this addon
				if ( isset($GLOBALS[$s][$c][$b][$a][$l]))
				{
					ee()->session->cache[$s][$b][$a][$l] = $GLOBALS[$s][$c][$b][$a][$l];

					//cleanup, isle 5
					unset($GLOBALS[$s][$c][$b][$a][$l]);
				}
				else
				{
					ee()->session->cache[$s][$b][$a][$l] = array();
				}
			}

			//check for solspace-wide globals
			if ( ! isset(ee()->session->cache[$s][$b][$g]) )
			{
				if (isset($GLOBALS[$s][$c][$b][$g]))
				{
					ee()->session->cache[$s][$b][$g] = $GLOBALS[$s][$c][$b][$g];

					unset($GLOBALS[$s][$c][$b][$g]);
				}
				else
				{
					ee()->session->cache[$s][$b][$g] = array();
				}
			}

			$this->global_cache =& ee()->session->cache[$s][$b][$g];
			$this->cache 		=& ee()->session->cache[$s][$b][$a][$l];
		}

		return $this->cache;
	}
	//END set_cache


	// --------------------------------------------------------------------

	/**
	 * Loads data object. Uses cached version if available.
	 *
	 * @access	public
	 * @return	object	data instance
	 */

	public function data()
	{
		if ( isset($this->cache['objects']['data']) AND
			 is_object($this->cache['objects']['data']))
		{
			$this->data =& $this->cache['objects']['data'];
		}
		else
		{
			if ( file_exists($this->addon_path . 'data.' . $this->lower_name.'.php'))
			{
				$name = $this->class_name . '_data';

				if ( ! class_exists($name))
				{
					require_once $this->addon_path . 'data.' . $this->lower_name.'.php';
				}

				$this->data = new $name($this);

				$this->data->sc	= $this->sc;
			}
			else if (file_exists($this->aob_path . 'data.addon_builder.php'))
			{
				if ( ! class_exists('Addon_builder_data_freeform'))
				{
					require_once $this->aob_path . 'data.addon_builder.php';
				}

				$this->data = new Addon_builder_data_freeform($this);
			}

			$this->cache['objects']['data'] =& $this->data;
		}

		$this->data->parent_aob_instance =& $this;

		return $this->data;
	}
	//END data


	// --------------------------------------------------------------------

	/**
	 * Creates shortcuts for common changed items between versions.
	 *
	 * @access	public
	 * @return	object
	 */

	public function generate_shortcuts ()
	{
		if (defined('URL_THIRD_THEMES'))
		{
			$theme_url = URL_THIRD_THEMES;
		}
		else
		{
			$theme_url = (
				rtrim(ee()->config->item('theme_folder_url'), '/') .
				'/third_party/'
			);
		}

		if (defined('PATH_THIRD_THEMES'))
		{
			$theme_path = PATH_THIRD_THEMES;
		}
		else
		{
			$theme_path = (
				rtrim(ee()->config->item('theme_folder_path'), '/') .
				'/third_party/'
			);
		}

		return (object) array(
			'db'	=> (object) array(
				'channel_name'			=> 'channel_name',
				'channel_url'			=> 'channel_url',
				'channel_title'			=> 'channel_title',
				'channels'				=> 'exp_channels',
				'data'					=> 'exp_channel_data',
				'channel_data'			=> 'exp_channel_data',
				'fields'				=> 'exp_channel_fields',
				'channel_fields'		=> 'exp_channel_fields',
				'id'					=> 'channel_id',
				'channel_id'			=> 'channel_id',
				'member_groups'			=> 'exp_channel_member_groups',
				'channel_member_groups'	=> 'exp_channel_member_groups',
				'titles'				=> 'exp_channel_titles',
				'channel_titles'		=> 'exp_channel_titles'
			),
			'channel'					=> 'channel',
			'channels'					=> 'channels',
			'theme_url'					=> $theme_url,
			'theme_path'				=> $theme_path,
			'addon_theme_url'			=> $theme_url . $this->lower_name . '/',
			'addon_theme_path'			=> $theme_path . $this->lower_name . '/',
		);
	}
	/* END generate_shortcuts() */


	// --------------------------------------------------------------------

	/**
	 * Module's Action Object
	 *
	 * intantiates the actions object and sticks it to $this->actions
	 *
	 * @access	public
	 * @return	object
	 */

	public function actions()
	{
		if ( ! is_object($this->actions))
		{
			$name = $this->class_name.'_actions';

			if ( ! class_exists($name))
			{
				$path = $this->addon_path . 'act.'.$this->lower_name.'.php';

				if ( ! file_exists($path))
				{
					return false;
				}

				require_once $path;
			}

			$this->actions = new $name();
			$this->actions->data =& $this->data;
		}

		return $this->actions;
	}
	// END actions()


	// --------------------------------------------------------------------

	/**
	 * Load Session
	 *
	 * Loads session if not present, for items like cp_js_end
	 *
	 * @access	public
	 * @return	mixed	sessions object or boolean false
	 */

	public function load_session()
	{
		if ( ! isset(ee()->session) OR ! is_object(ee()->session))
		{
			//needed for remember class
			if (file_exists(APPPATH . 'libraries/Localize.php'))
			{
				ee()->load->library('localize');
			}

			//EE 2.6.x+
			if (file_exists(APPPATH . 'libraries/Remember.php'))
			{
				ee()->load->library('remember');
			}

			if (file_exists(APPPATH.'libraries/Session.php'))
			{
				ee()->load->library('session');
			}
		}

		return (isset(ee()->session) AND is_object(ee()->session)) ?
					ee()->session :
					FALSE;
	}
	//END load_session


	// --------------------------------------------------------------------

	/**
	 * Database Version
	 *
	 * Returns the version of the module in the database
	 *
	 * @access	public
	 * @param 	bool 	ignore all caches and get version from database
	 * @return	string
	 */

	public function database_version($ignore_cache = FALSE)
	{
		if ( ! $ignore_cache AND
			 isset($this->cache['database_version']))
		{
			return $this->cache['database_version'];
		}

		//	----------------------------------------
		//	 Use Template object variable, if available
		// ----------------------------------------

		if ( ! $ignore_cache AND
			 isset(ee()->TMPL) AND
			 is_object(ee()->TMPL) AND
			 count(ee()->TMPL->module_data) > 0)
		{
			if ( ! isset(ee()->TMPL->module_data[$this->class_name]))
			{
				$this->cache['database_version'] = FALSE;
			}
			else
			{
				$this->cache['database_version'] = ee()->TMPL->module_data[$this->class_name]['version'];
			}
		}
		//global cache
		elseif ( ! $ignore_cache AND
			isset($this->global_cache['module_data']) AND
			isset($this->global_cache['module_data'][$this->lower_name]['database_version']))
		{
			$this->cache['database_version'] = $this->global_cache['module_data'][$this->lower_name]['database_version'];
		}
		//fill global with last resort
		else
		{
			//	----------------------------------------
			//	 Retrieve all Module Versions from the Database
			//	  - By retrieving all of them at once,
			//   we can limit it to a max of one query per
			//   page load for all Bridge Add-Ons
			// ----------------------------------------

			$query = $this->cacheless_query(
				"SELECT module_version, module_name
				 FROM 	exp_modules"
			);

			foreach($query->result_array() as $row)
			{
				if ( isset(ee()->session) AND is_object(ee()->session))
				{
					$this->global_cache['module_data'][
						strtolower($row['module_name'])
					]['database_version'] = $row['module_version'];
				}

				if ($this->class_name == $row['module_name'])
				{
					$this->cache['database_version'] = $row['module_version'];
				}
			}
		}

		//did get anything?
		return isset($this->cache['database_version']) ?
				$this->cache['database_version'] :
				FALSE;
	}
	// END database_version()


	// --------------------------------------------------------------------

	/**
	 * Find and return preference
	 *
	 * Any number of possible arguments, although
	 * typically I expect there will be only one or two
	 *
	 * @access	public
	 * @param	string			Preference to retrieve
	 * @return	null|string		If preference does not exist, NULL is returned, else the value
	 */

	public function preference()
	{
		$s = func_num_args();

		if ($s == 0)
		{
			return NULL;
		}

		//--------------------------------------------
		// Fetch Module Preferences
		//--------------------------------------------

		if (count($this->module_preferences) == 0 AND
			$this->database_version() !== FALSE)
		{
			if ($this->actions() !== FALSE &&
				method_exists($this->actions(), 'module_preferences'))
			{
				$this->module_preferences = $this->actions()->module_preferences();
			}
			elseif ( method_exists($this->data, 'get_module_preferences'))
			{
				$this->module_preferences = $this->data->get_module_preferences();
			}
			else
			{
				return NULL;
			}
		}

		//--------------------------------------------
		// Find Our Value, If It Exists
		//--------------------------------------------

		$value = (isset($this->module_preferences[func_get_arg(0)])) ?
					$this->module_preferences[func_get_arg(0)] : NULL;

		for($i = 1; $i < $s; ++$i)
		{
			if ( ! isset($value[func_get_arg($i)]))
			{
				return NULL;
			}

			$value = $value[func_get_arg($i)];
		}

		return $value;
	}
	// END preference()


	// --------------------------------------------------------------------

	/**
	 * Checks to see if extensions are allowed
	 *
	 *
	 * @access	public
	 * @return	bool	Whether the extensions are allowed
	 */

	public function extensions_allowed ()
	{
		return $this->check_yes(ee()->config->item('allow_extensions'));
	}
	//END extensions_allowed


	// --------------------------------------------------------------------

	/**
	 * Homegrown Version of Version Compare
	 *
	 * Compared two versions in the form of 1.1.1.d12 <= 1.2.3.f0
	 *
	 * @access	public
	 * @param	string	First Version
	 * @param	string	Operator for Comparison
	 * @param	string	Second Version
	 * @return	bool	Whether the comparison is TRUE or FALSE
	 */

	public function version_compare ($v1, $operator, $v2)
	{
		// Allowed operators
		if ( ! in_array($operator, array('>', '<', '>=', '<=', '==', '!=')))
		{
			trigger_error("Invalid Operator in Add-On Library - Version Compare", E_USER_WARNING);
			return FALSE;
		}

		// Normalize and Fix Invalid Values
		foreach(array('v1', 'v2') as $var)
		{
			$x = array_slice(preg_split("/\./", trim($$var), -1, PREG_SPLIT_NO_EMPTY), 0, 4);

			for($i=0; $i < 4; $i++)
			{
				if ( ! isset($x[$i]))
				{
					$x[$i] = ($i == 3) ? 'f0' : '0';
				}
				elseif ($i < 3 AND ctype_digit($x[$i]) == FALSE)
				{
					$x[$i] = '0';
				}
				elseif($i == 3 AND ! preg_match("/^[abdf]{0,1}[0-9]+$/", $x[$i]))
				{
					$x[$i] = 'f0';
				}

				// Set up for PHP's version_compare
				if ($i == 3 && ! ctype_digit($x[3]))
				{
					$letter 	 = substr($x[3], 0, 1);
					$sans_letter = substr($x[3], 1);

					if ($letter == 'd')
					{
						$letter = 'dev';
					}
					elseif($letter == 'f')
					{
						$letter = 'RC';
					}

					$x[3] = $letter.'.'.$sans_letter;
				}
			}

			$$var = implode('.', $x);
		}

		// echo $v1.' - '.$v2;

		//this is a php built in function,
		//self::version_compare is just prep work
		return version_compare($v1, $v2, $operator);
	}
	// END version_compare()


	// --------------------------------------------------------------------

	/**
	 * ExpressionEngine CP View Request
	 *
	 * Just like a typical view request but we do a few EE CP related things too
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function ee_cp_view ($view)
	{
		//--------------------------------------------
		// Build Crumbs!
		//--------------------------------------------

		$this->build_crumbs();
		$this->build_right_links();

		//--------------------------------------------
		// Load View Path, Call View File
		//--------------------------------------------

		return $this->view($view, array(), TRUE);
	}
	// END ee_cp_view()


	// --------------------------------------------------------------------

	/**
	 * Javascript/CSS File View Request
	 *
	 * Outputs a View file as if it were a Javascript file
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function file_view ($view, $modification_time = '')
	{
		//--------------------------------------------
		// Auto-detect the Type
		//--------------------------------------------

		if (preg_match("/\.([cjs]{2,3})$/i", $view, $match) AND
			in_array($match[1], array('css', 'js')))
		{
			switch($match[1])
			{
				case 'css'	:
					$type = 'css';
				break;
				case 'js'	:
					$type = 'javascript';
				break;
			}
		}
		else
		{
			exit;
		}

		//--------------------------------------------
		// Load View Path, Call View File
		//--------------------------------------------

		$output = $this->view($view, array(), TRUE);

		//--------------------------------------------
		// EE 1.x, We Add Secure Form Hashes and Output Content to Browser
		//--------------------------------------------

		if ($type == 'javascript' AND stristr($output, '{XID_SECURE_HASH}'))
		{
			$output = str_replace('{XID_SECURE_HASH}', '{XID_HASH}', $output);
		}

		if ($type == 'javascript')
		{
			$output = ee()->functions->add_form_security_hash($output);
		}

		//----------------------------------------
		// Generate HTTP headers
		//----------------------------------------

		if (ee()->config->item('send_headers') == 'y')
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$file = ($ext == '') ? $view.EXT : $view;
			$path = $this->view_path.$file;

			$max_age			= 5184000;
			$modification_time	= ($modification_time != '') ? $modification_time : filemtime($path);
			$modified_since		= ee()->input->server('HTTP_IF_MODIFIED_SINCE');

			if ( ! ctype_digit($modification_time))
			{
				$modification_time	= filemtime($path);
			}

			// Remove anything after the semicolon

			if ($pos = strrpos($modified_since, ';') !== FALSE)
			{
				$modified_since = substr($modified_since, 0, $pos);
			}

			// Send a custom ETag to maintain a useful cache in
			// load-balanced environments

			header("ETag: ".md5($modification_time.$path));

			// If the file is in the client cache, we'll
			// send a 304 and be done with it.

			if ($modified_since AND (strtotime($modified_since) == $modification_time))
			{
				ee()->output->set_status_header(304);
				exit;
			}

			ee()->output->set_status_header(200);
			@header("Cache-Control: max-age={$max_age}, must-revalidate");
			@header('Vary: Accept-Encoding');
			@header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modification_time).' GMT');
			@header('Expires: '.gmdate('D, d M Y H:i:s', time() + $max_age).' GMT');
			@header('Content-Length: '.strlen($output));
		}

		//----------------------------------------
		// Send JavaScript/CSS Header and Output
		//----------------------------------------

		@header("Content-type: text/".$type);

		exit($output);
	}
	// END ee_cp_view()


	// --------------------------------------------------------------------

	/**
	 * View File Loader
	 *
	 * Takes a file from the filesystem and loads it so that we can parse PHP within it just
	 *
	 *
	 * @access		public
	 * @param		string		$view - The view file to be located
	 * @param		array		$vars - Array of data variables to be parsed in the file system
	 * @param		bool		$return - Return file as string or put into buffer
	 * @param		string		$path - Override path for the file rather than using $this->view_path
	 * @return		string
	 */

	public function view ($view, $vars = array(), $return = FALSE, $path='')
	{
		//--------------------------------------------
		// Determine File Name and Extension for Requested File
		//--------------------------------------------

		if ($path == '')
		{
			$ext = pathinfo($view, PATHINFO_EXTENSION);
			$file = ($ext == '') ? $view.EXT : $view;
			$path = $this->view_path.$file;
		}
		else
		{
			$x = explode('/', $path);
			$file = end($x);
		}

		//--------------------------------------------
		// Make Sure the File Actually Exists
		//--------------------------------------------

		if ( ! file_exists($path))
		{
			trigger_error("Invalid View File Request of '".$path."'");
			return FALSE;
		}

		// All variables sent to the function are cached, which allows us to use them
		// within embedded view files within this file.

		if (is_array($vars))
		{
			$this->cached_vars = array_merge($this->cached_vars, $vars);
		}
		extract($this->cached_vars, EXTR_PREFIX_SAME, 'var_');

		//print_r($this->cached_vars);

		//--------------------------------------------
		// Buffer Output
		// - Increases Speed
		// - Allows Views to be Nested Within Views
		//--------------------------------------------

		ob_start();

		//--------------------------------------------
		// Load File and Rewrite Short Tags
		//--------------------------------------------

		// Hard coded setting for now...
		$rewrite_short_tags = TRUE;

		if ((bool) @ini_get('short_open_tag') === FALSE AND
			$rewrite_short_tags == TRUE)
		{
			echo eval('?'.'>'.preg_replace("/;*\s*\?".">/", "; ?".">",
					  str_replace('<'.'?=', '<?php echo ',
					 file_get_contents($path))).'<'.'?php ');
		}
		else
		{
			include($path);
		}

		//--------------------------------------------
		// Return Parsed File as String
		//--------------------------------------------

		if ($return === TRUE)
		{
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}

		//--------------------------------------------
		// Flush Buffer
		//--------------------------------------------

		if (ob_get_level() > $this->ob_level + 1)
		{
			ob_end_flush();
		}
		else
		{
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
	}
	// END view()


	// --------------------------------------------------------------------

	/**
	 * Add Array of Breadcrumbs for a Page
	 *
	 * @access	public
	 * @param	array
	 * @return	null
	 */

	public function add_crumbs ($array)
	{
		if ( is_array($array))
		{
			foreach($array as $value)
			{
				if ( is_array($value))
				{
					$this->add_crumb($value[0], $value[1]);
				}
				else
				{
					$this->add_crumb($value);
				}
			}
		}
	}
	// END add_crumbs


	// --------------------------------------------------------------------

	/**
	 * Add Single Crumb to List of Breadcrumbs
	 *
	 * @access	public
	 * @param	string		Text of breacrumb
	 * @param	string		Link, if any for breadcrumb
	 * @return	null
	 */

	public function add_crumb ($text, $link='')
	{
		$this->crumbs[] = ($link == '') ? array($text) : array($text, $link);
	}
	// END add_crumb


	// --------------------------------------------------------------------

	/**
	 * Takes Our Crumbs and Builds them into the Breadcrumb List
	 *
	 * @access	public
	 * @return	null
	 */

	public function build_crumbs()
	{
		if ( is_string($this->crumbs))
		{
			$this->cached_vars['page_crumb'] = $this->crumbs;
			$this->cached_vars['page_title'] = $this->crumbs;
			return;
		}
		$this->cached_vars['page_crumb'] = '';
		$this->cached_vars['page_title'] = '';

		$item = (count($this->crumbs) == 1) ? TRUE : FALSE;

		ee()->load->helper('url');

		foreach($this->crumbs as $key => $value)
		{
			if (is_array($value))
			{
				$name = $value[0];

				if (isset($value[1]))
				{
					$name = "<a href='{$value[1]}'>{$value[0]}</a>";
				}

				$this->cached_vars['page_title'] = $value[0];
			}
			else
			{
				$name = $value;
				$this->cached_vars['page_title'] = $value;
			}

			if (is_array($value) AND isset($value[1]))
			{
				ee()->cp->set_breadcrumb($value[1], $value[0]);
			}
		}

		$this->cached_vars['cp_page_title'] = $this->cached_vars['page_title'];

		//set_variable depracated in 2.6.0
		if (version_compare($this->ee_version, '2.6.0', '>='))
		{
			ee()->view->cp_page_title = $this->cached_vars['cp_page_title'];
		}
		else
		{
			ee()->cp->set_variable('cp_page_title', $this->cached_vars['cp_page_title'] );
		}

	}
	// END build_crumbs


	// --------------------------------------------------------------------

	/**
	 * Field Output Prep for arrays and strings
	 *
	 *
	 * @access	public
	 * @param	string|array	The item that needs to be prepped for output
	 * @return	string|array
	 */

	public function output($item)
	{
		if (is_array($item))
		{
			$array = array();

			foreach($item as $key => $value)
			{
				$array[$this->output($key)] = $this->output($value);
			}

			return $array;
		}
		else if (is_string($item))
		{
			return htmlspecialchars($item, ENT_QUOTES);
		}
		else
		{
			return $item;
		}
	}
	// END output


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

	public function cycle($items)
	{
		if ( ! is_array($items))
		{
			$items = func_get_args();
		}

		$hash = md5(implode('|', $items));

		if ( ! isset($this->switches[$hash]) OR
			! isset($items[$this->switches[$hash] + 1]))
		{
			$this->switches[$hash] = 0;
		}
		else
		{
			$this->switches[$hash]++;
		}

		return $items[$this->switches[$hash]];
	}
	// END cycle


	// --------------------------------------------------------------------

	/**
	 * Column Exists in DB Table
	 *
	 * @access	public
	 * @param	string	$column		The column whose existence we are looking for
	 * @param	string	$table		In which table?
	 * @return	array
	 */

	public function column_exists($column, $table, $cache = TRUE)
	{
		if ($cache === TRUE AND isset($this->cache['column_exists'][$table][$column]))
		{
			return $this->cache['column_exists'][$table][$column];
		}

		//	----------------------------------------
		//	Check for columns in tags table
		// ----------------------------------------

		$query	= ee()->db->query(
			"DESCRIBE	`".ee()->db->escape_str( $table )."`
						`".ee()->db->escape_str( $column )."`"
		);

		if ( $query->num_rows() > 0 )
		{
			return $this->cache['column_exists'][$table][$column] = TRUE;
		}

		return $this->cache['column_exists'][$table][$column] = FALSE;
	}
	// END column_exists


	// --------------------------------------------------------------------

	/**
	 * Retrieve Remote File and Cache It
	 *
	 * @access public
	 * @param  string  $url				URL to be retrieved
	 * @param  integer $cache_length	How long to cache the result, if successful retrieval
	 * @param  string  $path			path to cache
	 * @param  string  $file			file name to cache
	 * @return bool						Success or failure.  Data result stored in $this->remote_data
	 */

	public function retrieve_remote_file($url, $cache_length = 24, $path='', $file='')
	{
		$path		= ($path == '') ? PATH_CACHE.'addon_builder/' : rtrim($path, '/').'/';
		$file		= ($file == '') ? md5($url).'.txt' : $file;
		$file_path	= $path.$file;

		// --------------------------------------------
		//  Check for Cached File
		// --------------------------------------------

		if ( ! file_exists($file_path) OR
			(time() - filemtime($file_path)) > (60 * 60 * round($cache_length))
		)
		{
			@unlink($file_path);
		}
		elseif (($this->remote_data = file_get_contents($file_path)) === FALSE)
		{
			@unlink($file_path);
		}
		else
		{
			return TRUE;
		}

		// --------------------------------------------
		//  Validate and Create Cache Directory
		// --------------------------------------------

		ee()->load->helper('string');

		if ( ! is_dir($path))
		{
			$dirs = explode('/', trim(reduce_double_slashes($path), '/'));

			$path = '/';

			foreach ($dirs as $dir)
			{
				if ( ! @is_dir($path.$dir))
				{
					if ( ! @mkdir($path.$dir, 0777))
					{
						$this->errors[] = 'Unable to Create Directory: '.$path.$dir;
						return;
					}

					@chmod($path.$dir, 0777);
				}

				$path .= $dir.'/';
			}
		}

		if ($this->is_really_writable($path) === FALSE)
		{
			$this->errors[] = 'Cache Directory is Not Writable: '.$path;
			return FALSE;
		}

		// --------------------------------------------
		//  Retrieve Our URL
		// --------------------------------------------

		$this->remote_data = $this->fetch_url($url);

		if ($this->remote_data == '')
		{
			$this->errors[] = 'Unable to Retrieve URL: '.$url;
			return FALSE;
		}

		// --------------------------------------------
		//  Write Cache File
		// --------------------------------------------

		if ( ! $this->write_file($file_path, $this->remote_data))
		{
			$this->errors[] = 'Unable to Write File to Cache';
			return FALSE;
		}

		return TRUE;
	}
	// END retrieve_remote_file


	// --------------------------------------------------------------------

	/**
	 * Fetch the Data for a URL
	 *
	 * @access public
	 * @param  string  $url			The URI that we are fetching
	 * @param  array   $post		The POST array we are sending
	 * @param  boolean $username	Possible username required
	 * @param  boolean $password	Password to go with the username
	 * @return string				url data
	 */
	public function fetch_url ($url, $post = array(), $username = FALSE, $password = FALSE)
	{
		$data = '';

		$user_agent = ini_get('user_agent');

		if ( empty($user_agent))
		{
			$user_agent = $this->class_name.'/1.0';
		}

		// --------------------------------------------
		//  file_get_contents()
		// --------------------------------------------

		if ((bool) @ini_get('allow_url_fopen') !== FALSE &&
			empty($post) && $username == FALSE)
		{
			$opts = array(
				'http'	=> array('header' => "User-Agent:".$user_agent."\r\n"),
				'https'	=> array('header' => "User-Agent:".$user_agent."\r\n")
			);

			$context = stream_context_create($opts);

			if ($data = @file_get_contents($url, FALSE, $context))
			{
				return $data;
			}
		}

		// --------------------------------------------
		//  cURL
		// --------------------------------------------

		if (function_exists('curl_init') === TRUE AND
			($ch = @curl_init()) !== FALSE)
		{
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

			// prevent a PHP warning on certain servers
			if (! ini_get('safe_mode') AND ! ini_get('open_basedir'))
			{
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			}

			//	Are we posting?
			if ( ! empty( $post ) )
			{
				$str	= '';

				foreach ( $post as $key => $val )
				{
					$str	.= urlencode( $key ) . "=" . urlencode( $val ) . "&";
				}

				$str	= substr( $str, 0, -1 );

				curl_setopt( $ch, CURLOPT_POST, TRUE );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $str );
			}

			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			if ($username != FALSE)
			{
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

				if (defined('CURLOPT_HTTPAUTH'))
				{
					curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC | CURLAUTH_DIGEST);
				}
			}

			$data = curl_exec($ch);
			curl_close($ch);

			if ($data !== FALSE)
			{
				return $data;
			}
		}

		// --------------------------------------------
		//  fsockopen() - Last but only slightly least...
		// --------------------------------------------

		$parts	= parse_url($url);
		$host	= $parts['host'];
		$path	= (!isset($parts['path'])) ? '/' : $parts['path'];
		$port	= ($parts['scheme'] == "https") ? '443' : '80';
		$ssl	= ($parts['scheme'] == "https") ? 'ssl://' : '';

		if (isset($parts['query']) AND $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}

		$data = '';

		$fp = @fsockopen($ssl.$host, $port, $error_num, $error_str, 7);

		if (is_resource($fp))
		{
			$getpost	= ( ! empty( $post ) ) ? 'POST ': 'GET ';

			fputs($fp, $getpost.$path." HTTP/1.0\r\n" );
			fputs($fp, "Host: ".$host . "\r\n" );

			if ( ! empty( $post ) )
			{
				$str	= '';

				foreach ( $post as $key => $val )
				{
					$str	.= urlencode( $key ) . "=" . urlencode( $val ) . "&";
				}

				$str	= substr( $str, 0, -1 );

				fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
				fputs($fp, "Content-length: " . strlen( $str ) . "\r\n");
			}

			fputs($fp,  "User-Agent: ".$user_agent."r\n");

			if ($username != FALSE)
			{
				fputs ($fp, "Authorization: Basic ".base64_encode($username.':'.$password)."\r\n");
			}

			fputs($fp, "Connection: close\r\n\r\n");

			if ( ! empty( $post ) )
			{
				fputs($fp, $str . "\r\n\r\n");
			}

			// ------------------------------
			//  This error suppression has to do with a PHP bug involving
			//  SSL connections: http://bugs.php.net/bug.php?id=23220
			// ------------------------------

			$old_level = error_reporting(0);

			$headers = '';

			while ( ! feof($fp))
			{
				$bit = fgets($fp, 128);

				$headers .= $bit;

				if(preg_match("/^\r?\n$/", $bit)) break;
			}

			while ( ! feof($fp))
			{
				$data .= fgets($fp, 128);
			}

			error_reporting($old_level);

			fclose($fp);
		}

		return trim($data);
	}
	/* END fetch_url() */


	// --------------------------------------------------------------------

	/**
	 * Write File
	 *
	 * @access	public
	 * @param	$file	Full location of final file
	 * @param	$data	Data to put into file
	 * @return	bool
	 */

	public function write_file($file, $data)
	{
		$temp_file = $file.'.tmp';

		if ( ! file_exists($temp_file))
		{
			// Remove old cache file, prevents rename problem on Windows
			// http://bugs.php.net/bug.php?id=44805

			@unlink($file);

			if (file_exists($file))
			{
				$this->errors[] = "Unable to Delete Old Cache File: ".$file;
				return FALSE;
			}

			if ( ! $fp = @fopen($temp_file, 'wb'))
			{
				$this->errors[] = "Unable to Write Temporary Cache File: ".$temp_file;
				return FALSE;
			}

			if ( ! flock($fp, LOCK_EX | LOCK_NB))
			{
				$this->errors[] = "Locking Error when Writing Cache File";
				return FALSE;
			}

			fwrite($fp, $data);
			flock($fp, LOCK_UN);
			fclose($fp);

			// Write, then rename...
			@rename($temp_file, $file);

			// Double check permissions
			@chmod($file, 0777);

			// Just in case the rename did not work
			@unlink($temp_file);
		}

		return TRUE;
	}
	// END write_file()


	// --------------------------------------------------------------------

	/**
	 * Check that File is Really Writable, Even on Windows
	 *
	 * is_writable() returns TRUE on Windows servers when you really cannot write to the file
	 * as the OS reports to PHP as FALSE only if the read-only attribute is marked.  Ugh!
	 *
	 * Oh, and there is some silly thing with
	 *
	 * @access	public
	 * @param	string		$path	- Path to be written to.
	 * @param	bool		$remove	- If writing a file, remove it after testing?
	 * @return	bool
	 */


	public static function is_really_writable($file, $remove = FALSE)
	{
		// is_writable() returns TRUE on Windows servers
		// when you really can't write to the file
		// as the OS reports to PHP as FALSE only if the
		// read-only attribute is marked.  Ugh?

		if (substr($file, -1) == '/' OR is_dir($file))
		{
			return self::is_really_writable(rtrim($file, '/').'/'.uniqid(mt_rand()), TRUE);
		}

		if (($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}
		else
		{
			if ($remove === TRUE)
			{
				@unlink($file);
			}

			fclose($fp);
			return TRUE;
		}
	}
	// END is_really_writable()


	// --------------------------------------------------------------------

	/**
	 *	Check Captcha
	 *
	 *	If Captcha is required by a module, we simply do all the work
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function check_captcha ()
	{
		if ( ee()->config->item('captcha_require_members') == 'y'  OR
			(ee()->config->item('captcha_require_members') == 'n' AND
			 ee()->session->userdata['member_id'] == 0))
		{
			if ( empty($_POST['captcha']))
			{
				return FALSE;
			}
			else
			{
				$res = ee()->db->query(
					"SELECT COUNT(*) AS count
					 FROM 	exp_captcha
					 WHERE  word = '" . ee()->db->escape_str($_POST['captcha']) . "'
					 AND 	ip_address = '" . ee()->db->escape_str(ee()->input->ip_address()) . "'
					 AND 	date > UNIX_TIMESTAMP()-7200"
				);

				if ($res->row('count') == 0)
				{
					return FALSE;
				}

				ee()->db->query(
					"DELETE FROM exp_captcha
					 WHERE 		(
						word = '" . ee()->db->escape_str($_POST['captcha']) . "'
						AND ip_address = '" . ee()->db->escape_str(ee()->input->ip_address()) . "'
					 )
					 OR 	date < UNIX_TIMESTAMP()-7200"
				);
			}
		}

		return TRUE;
	}
	// END check_captcha()


	// --------------------------------------------------------------------

	/**
	 *	Check Secure Forms
	 *
	 *	Checks to see if Secure Forms is enabled, and if so sees if the submitted hash is valid
	 *
	 *	@access		public
	 *	@return		bool
	 */

	public function check_secure_forms ($xid = FALSE)
	{
		if ( ! $xid)
		{
			$xid = ee()->input->get_post('XID');
		}

		return ee()->security->secure_forms_check($xid);
	}
	// END check_secure_forms()


	// --------------------------------------------------------------------

	/**
	 * A Slightly More Flexible Magic Checkbox
	 *
	 * Toggles the checkbox based on clicking anywhere in the
	 * table row that contains the checkbox
	 * Also allows multiple master toggle checkboxes at the top
	 * and bottom of a table to de/select all checkboxes
	 *	- give them a name="toggle_all_checkboxes" attribute
	 * 	- give the parent table a class of 'cb_toggle'
	 * 	- jQuery should be present
	 *
	 * @deprecated	this should be in the module header files, not PHP :p
	 * @access	public
	 * @return	string
	 */

	function js_magic_checkboxes ()
	{
		return <<< EOT
<script type="text/javascript">
	jQuery(function($){
		$('table.magic_checkbox_table, ' +
		  'table.magicCheckboxTable, '  +
		  'table.cb_toggle'
		).each(function(){
			var \$table 		= $(this),
				\$magicCB	= \$table.find(
					'input[type=checkbox][name=toggle_all_checkboxes]'
				);

			\$magicCB.each(function(){
				var \$that 		= $(this),
					colNum 		= \$that.parent().index();

				\$that.click(function(){
					var checked = (\$that.is(':checked')) ? 'checked' : false;

					\$table.find('tr').find(
						'th:eq(' + colNum + ') input[type=checkbox], ' +
						'td:eq(' + colNum + ') input[type=checkbox]'
					).attr('checked', checked);
				});
			});
		});
	});
</script>
EOT;
	}
	// END js_magic_checkboxes



	// --------------------------------------------------------------------

	/**
	 * Balance a URI
	 *
	 * @access	public
	 * @param	string	$uri
	 * @return	array
	 */

	public function balance_uri ( $uri )
	{
		$uri = '/'.trim($uri, '/').'/';

		if ($uri == '//' OR $uri == '')
		{
			$uri = '/';
		}

		return $uri;
	}
	/* END balance_uri() */


	// --------------------------------------------------------------------

	/**
	 * Fetch Themes for a path
	 *
	 * @access	public
	 * @param	string		$path - Absolute server path to theme directory
	 * @return	array
	 */

	public function fetch_themes ($path)
	{
		$themes = array();

		if ($fp = @opendir($path))
		{
			while (false !== ($file = readdir($fp)))
			{
				if (is_dir($path.$file) AND substr($file, 0, 1) != '.')
				{
					$themes[] = $file;
				}
			}

			closedir($fp);
		}

		sort($themes);

		return $themes;
	}
	// END fetch_themes()


	// --------------------------------------------------------------------

	/**
	 * Allowed Group
	 *
	 * Member access validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function allowed_group ($which = '')
	{
		if ( is_object(ee()->cp))
		{
			return ee()->cp->allowed_group($which);
		}
	}
	// END allowed_group


	// --------------------------------------------------------------------

	/**
	 * Global Error Message Routine
	 *
	 * @access	public
	 * @param	mixed	$message	error string or array of error strings
	 * @param	bool	$restore	optional restore XID on error
	 * @return	mixed				void if not unit test
	 */

	public function show_error($message = '', $restore = true)
	{
		if ($this->unit_test_mode)
		{
			return $this->test_method(
				__FUNCTION__,
				'show_error',
				array($message)
			);
		}
		//EL is wanting to deprecate output->show_user_error for CP
		//removed deprecation in EE 2.7, but its coming back i suppose
		else if (REQ == 'CP')
		{
			// -------------------------------------
			//	auto restore XID? (ee 2.7 only)
			// -------------------------------------

			if (version_compare($this->ee_version, '2.7', '>='))
			{
				$errors = is_array($message) ? $message : array($message);

				foreach($errors as $error)
				{
					foreach (array(
							lang('not_authorized'),
							lang('unauthorized_access'),
							lang('invalid_action')
						) as $exception
					)
					{
						if (strpos($error, $exception) !== FALSE)
						{
							$restore = false;
						}
					}
				}

				if ($restore)
				{
					ee()->security->restore_xid();
				}
			}

			return show_error($message);
		}
		else
		{
			$type = ( ! empty($_POST)) ? 'submission' : 'general';

			return ee()->output->show_user_error($type, $message);
		}
	}
	// END show_error()


	// --------------------------------------------------------------------

	/**
	 *	Check if Submitted String is a Yes value
	 *
	 *	If the value is 'y', 'yes', 'true', or 'on', then returns TRUE, otherwise FALSE
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		bool
	 */

	function check_yes ($which)
	{
		if (is_string($which))
		{
			$which = strtolower(trim($which));
		}

		return in_array($which, array('yes', 'y', 'true', 'on'), TRUE);
	}
	// END check_yes()


	// --------------------------------------------------------------------

	/**
	 *	Check if Submitted String is a No value
	 *
	 *	If the value is 'n', 'no', 'false', or 'off', then returns TRUE, otherwise FALSE
	 *
	 *	@access		public
	 *	@param		string
	 *	@return		bool
	 */

	function check_no ($which)
	{
		if (is_string($which))
		{
			$which = strtolower(trim($which));
		}

		return in_array($which, array('no', 'n', 'false', 'off'), TRUE);
	}
	// END check_no()


	// --------------------------------------------------------------------

	/**
	 *	json_encode
	 *
	 *	@access		public
	 *	@param		object
	 *	@return		string
	 */

	public function json_encode ($data)
	{
		if (function_exists('json_encode'))
		{
			return json_encode($data);
		}

		//so far EE 2.x has no json_encode replacement
		//and uses this.. thing.
		ee()->load->library('javascript');
		return ee()->javascript->generate_json($data);
	}
	// END json_encode()


	// --------------------------------------------------------------------

	/**
	 *	json_decode
	 *
	 *	@access		public
	 *	@param		string
	 *	@param		bool		$associative - By default JSON decode
	 *											returns object,
	 *											this forces an array
	 *	@return		object
	 */

	public function json_decode ($data, $associative = FALSE)
	{
		if (function_exists('json_decode'))
		{
			return json_decode($data, $associative);
		}

		ee()->load->library('Services_json');

		return json_decode($data, $associative);
	}
	// END json_decode()


	// --------------------------------------------------------------------

	/**
	 *	Pagination for all versions front-end and back
	 *
	 *	* = optional
	 *	$input_data = array(
	 *		'sql'					=> '',
	 *		'total_results'			=> '',
	 *		*'url_suffix' 			=> '',
	 *		'tagdata'				=> ee()->TMPL->tagdata,
	 *		'limit'					=> '',
	 *		*'offset'				=> ee()->TMPL->fetch_param('offset'),
	 *		*'query_string_segment'	=> 'P',
	 *		'uri_string'			=> ee()->uri->uri_string,
	 *		*'current_page'			=> 0
	 *		*'pagination_config'	=> array()
	 *	);
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		array
	 */

	public function universal_pagination ( $input_data )
	{
		// -------------------------------------
		//	prep input data
		// -------------------------------------

		//set defaults for optional items
		$input_defaults	= array(
			'url_suffix' 			=> '',
			'query_string_segment' 	=> 'P',
			'offset'				=> 0,
			'pagination_page'		=> 0,
			'pagination_config'		=> array(),
			'sql'					=> '',
			'tagdata'				=> '',
			'uri_string'			=> '',
			'paginate_prefix'		=> '',
			'prefix'				=> '',
			'total_results'			=> 0,
			'request'				=> REQ,
			'auto_paginate'			=> FALSE
		);

		//array2 overwrites any duplicate key from array1
		$input_data 					= array_merge($input_defaults, $input_data);

		// -------------------------------------
		//	using the prefix? well, lets use it like the old. Stupid legacy
		// -------------------------------------

		if (trim($input_data['prefix']) !== '')
		{
			//allowing ':' in a prefix
			if (substr($input_data['prefix'], -1, 1) !== ':')
			{
				$input_data['prefix'] = rtrim($input_data['prefix'], '_') . '_';
			}

			$input_data['paginate_prefix'] = $input_data['prefix'];
		}

		//using query strings?
		//technically, ACT is the same here, but ACT is not for templates :p
		$use_query_strings 				= (
			REQ == 'CP' OR
			$input_data['request'] == 'CP' OR
			ee()->config->item('enable_query_strings')
		);

		//make sure there is are surrounding slashes.
		$input_data['uri_string']		= '/' . trim($input_data['uri_string'], '/') . '/';

		//shortcuts
		$config							= $input_data['pagination_config'];
		$p								= $input_data['query_string_segment'];
		$config['query_string_segment'] = $input_data['query_string_segment'];
		$config['page_query_string']	= $use_query_strings;

		//need the prefix so our segments are like /segment/segment/P10
		//instead of like /segment/segment/10
		//this only works in EE 2.x because CI 1.x didn't have the prefix
		//a hack later in the code makes this work for EE 1.x
		if (REQ == 'PAGE')
		{
			$config['prefix'] = $config['query_string_segment'];
		}

		ee()->load->helper('string');

		//current page
		if ( ! $use_query_strings AND preg_match("/$p(\d+)/s", $input_data['uri_string'], $match) )
		{
			if ( $input_data['pagination_page'] == 0 AND is_numeric($match[1]) )
			{
				$input_data['pagination_page'] 	= $match[1];

				//remove page from uri string, query_string, and uri_segments
				$input_data['uri_string'] 		= reduce_double_slashes(
					str_replace($p . $match[1] , '', $input_data['uri_string'] )
				);
			}
		}
		else if ( $use_query_strings === FALSE)
		{
			if ( ! is_numeric($input_data['pagination_page']) )
			{
				$input_data['pagination_page'] = 0;
			}
		}
		else if ( ! in_array(
			ee()->input->get_post($input_data['query_string_segment']),
			array(FALSE, '')
		))
		{
			$input_data['pagination_page'] = ee()->input->get_post($input_data['query_string_segment']);
		}

		// --------------------------------------------
		//  Automatic Total Results
		// --------------------------------------------

		if ( empty($input_data['total_results']) AND
			 ! empty($input_data['sql'])
		)
		{
			$query = ee()->db->query(
				preg_replace(
					"/SELECT(.*?)\s+FROM\s+/is",
					'SELECT COUNT(*) AS count FROM ',
					$input_data['sql'],
					1
				)
			);

			$input_data['total_results'] = $query->row('count');
		}

		//this prevents the CI pagination class from
		//trying to find the number itself...
		$config['uri_segment'] = 0;

		// -------------------------------------
		//	prep return data
		// -------------------------------------

		$return_data 	= array(
			'paginate'				=> FALSE,
			'paginate_tagpair_data'	=> '',
			'current_page'			=> 0,
			'total_pages'			=> 0,
			'total_results'			=> $input_data['total_results'],
			'page_count'			=> '',
			'pagination_links'		=> '',
			'pagination_array'		=> '', //2.3.0+
			'base_url'				=> '',
			'page_next'				=> '',
			'page_previous'			=> '',
			'pagination_page'		=> $input_data['pagination_page'],
			'tagdata'				=> $input_data['tagdata'],
			'sql'					=> $input_data['sql'],
		);

		// -------------------------------------
		//	Begin pagination check
		// -------------------------------------

		if (REQ == 'CP' OR
			$input_data['request'] == 'CP' OR
			(
				strpos(
					$return_data['tagdata'],
					LD . $input_data['paginate_prefix'] . 'paginate'
				) !== FALSE
				OR
				strpos(
					$return_data['tagdata'],
					LD . 'paginate'
				) !== FALSE
			)
		)
		{
			$return_data['paginate'] = TRUE;

			// -------------------------------------
			//	If we have prefixed pagination tags,
			//	lets do those first
			// -------------------------------------

			if ($input_data['paginate_prefix'] != '' AND preg_match(
					"/" . LD . $input_data['paginate_prefix'] . "paginate" . RD .
						"(.+?)" .
					LD . preg_quote(T_SLASH, '/') .
						$input_data['paginate_prefix'] . "paginate" .
					RD . "/s",
					$return_data['tagdata'],
					$match
				))
			{
				$return_data['paginate_tagpair_data']	= $match[1];
				$return_data['tagdata'] 				= str_replace(
					$match[0],
					'',
					$return_data['tagdata']
				);
			}
			//else lets check for normal pagination tags
			else if (preg_match(
					"/" . LD . "paginate" . RD .
						"(.+?)" .
					LD . preg_quote(T_SLASH, '/') . "paginate" . RD . "/s",
					$return_data['tagdata'],
					$match
				))
			{
				$return_data['paginate_tagpair_data']	= $match[1];
				$return_data['tagdata'] 				= str_replace(
					$match[0],
					'',
					$return_data['tagdata']
				);
			}

			// ----------------------------------------
			//  Calculate total number of pages
			// ----------------------------------------

			$return_data['current_page'] 	= floor(
				$input_data['pagination_page'] / $input_data['limit']
			) + 1;

			$return_data['total_pages']		= ceil(
				($input_data['total_results'] - $input_data['offset']) / $input_data['limit']
			);

			$return_data['page_count'] 		= lang('page') 		. ' ' .
											  $return_data['current_page'] 	. ' ' .
											  lang('of') 		. ' ' .
											  $return_data['total_pages'];

			// ----------------------------------------
			//  Do we need pagination?
			// ----------------------------------------

			if ( ($input_data['total_results'] - $input_data['offset']) > $input_data['limit'] )
			{
				if ( ! isset( $config['base_url'] )  )
				{
					$config['base_url']			= ee()->functions->create_url(
						$input_data['uri_string'] . $input_data['url_suffix'],
						FALSE,
						0
					);
				}

				$config['total_rows'] 	= ($input_data['total_results'] - $input_data['offset']);
				$config['per_page']		= $input_data['limit'];
				$config['cur_page']		= $input_data['pagination_page'];
				$config['first_link'] 	= lang('pag_first_link');
				$config['last_link'] 	= lang('pag_last_link');

				ee()->load->library('pagination');

				ee()->pagination->initialize($config);

				$return_data['pagination_links'] = ee()->pagination->create_links();
				$return_data['pagination_array'] = ee()->pagination->create_link_array();

				$return_data['base_url'] = ee()->pagination->base_url;

				// ----------------------------------------
				//  Prepare next_page and previous_page variables
				// ----------------------------------------

				//next page?
				if ( (($return_data['total_pages'] * $input_data['limit']) - $input_data['limit']) >
					 $return_data['pagination_page'])
				{
					$return_data['page_next'] = $return_data['base_url'] .
						($use_query_strings ? '' : $p) .
						($input_data['pagination_page'] + $input_data['limit']) . '/';
				}

				//previous page?
				if (($return_data['pagination_page'] - $input_data['limit'] ) >= 0)
				{
					$return_data['page_previous'] = $return_data['base_url'] .
						($use_query_strings ? '' : $p) .
						($input_data['pagination_page'] - $input_data['limit']) . '/';
				}
			}
		}

		//move current page to offset
		//$return_data['current_page'] += $input_data['offset'];

		//add limit to passed in sql
		$return_data['sql'] .= 	' LIMIT ' .
			($return_data['pagination_page'] + $input_data['offset']) .
			', ' . $input_data['limit'];

		//if we are automatically making magic, lets add all of the class vars
		if ($input_data['auto_paginate'] === TRUE)
		{
			$this->auto_paginate	= TRUE;
			$this->paginate			= $return_data['paginate'];
			$this->page_next		= $return_data['page_next'];
			$this->page_previous	= $return_data['page_previous'];
			$this->p_page			= $return_data['pagination_page'];
			$this->current_page  	= $return_data['current_page'];
			$this->pagination_links	= $return_data['pagination_links'];
			$this->pagination_array	= $return_data['pagination_array'];
			$this->basepath			= $return_data['base_url'];
			$this->total_pages		= $return_data['total_pages'];
			$this->paginate_data	= $return_data['paginate_tagpair_data'];
			$this->page_count		= $return_data['page_count'];
			//ee()->TMPL->tagdata	= $return_data['tagdata'];
		}

		return $return_data;
	}
	//	End universal_pagination


	// --------------------------------------------------------------------

	/**
	 * Universal Parse Pagination
	 *
	 * This creates a new XID hash in the DB for usage.
	 *
	 * @access	public
	 * @param 	array
	 * @return	tagdata
	 */

	public function parse_pagination ($options = array())
	{
		// -------------------------------------
		//	prep input data
		// -------------------------------------

		//set defaults for optional items
		$defaults	= array(
			'prefix' 			=> '',
			'tagdata' 			=> ((isset(ee()->TMPL) and is_object(ee()->TMPL)) ?
									ee()->TMPL->tagdata : ''),
			'paginate'  		=> FALSE,
			'page_next' 		=> '',
			'page_previous' 	=> '',
			'p_page' 			=> 0,
			'current_page' 		=> 0,
			'pagination_links' 	=> '',
			'pagination_array'	=> '',
			'basepath' 			=> '',
			'total_pages' 		=> '',
			'paginate_data' 	=> '',
			'page_count' 		=> '',
			'auto_paginate' 	=> $this->auto_paginate
		);

		//array2 overwrites any duplicate key from array1
		$options = array_merge($defaults, $options);

		// -------------------------------------
		//	auto paginate?
		// -------------------------------------

		if ($options['auto_paginate'])
		{
			$options = array_merge($options, array(
				'paginate'  		=> $this->paginate,
				'page_next' 		=> $this->page_next,
				'page_previous' 	=> $this->page_previous,
				'p_page' 			=> $this->p_page,
				'current_page' 		=> $this->current_page,
				'pagination_links' 	=> $this->pagination_links,
				'pagination_array'	=> $this->pagination_array,
				'basepath' 			=> $this->basepath,
				'total_pages' 		=> $this->total_pages,
				'paginate_data' 	=> $this->paginate_data,
				'page_count' 		=> $this->page_count,
			));
		}

		// -------------------------------------
		//	prefixed items?
		// -------------------------------------

		$prefix = '';

		if (trim($options['prefix']) != '')
		{
			//allowing ':' in a prefix
			if (substr($options['prefix'], -1, 1) !== ':')
			{
				$options['prefix'] = rtrim($options['prefix'], '_') . '_';
			}

			$prefix = $options['prefix'];
		}

		$tag_paginate			= $prefix . 'paginate';
		$tag_pagination_links	= $prefix . 'pagination_links';
		$tag_current_page		= $prefix . 'current_page';
		$tag_total_pages		= $prefix . 'total_pages';
		$tag_page_count			= $prefix . 'page_count';
		$tag_previous_page		= $prefix . 'previous_page';
		$tag_next_page			= $prefix . 'next_page';
		$tag_auto_path			= $prefix . 'auto_path';
		$tag_path				= $prefix . 'path';

		// -------------------------------------
		//	TO VARIABLES!
		// -------------------------------------

		extract($options);

		// ----------------------------------------
		//	no paginate? :(
		// ----------------------------------------

		if ( $paginate === FALSE )
		{
			return ee()->functions->prep_conditionals(
				$tagdata,
				array($tag_paginate => FALSE)
			);
		}

		// -------------------------------------
		//	replace {if (prefix_)paginate} blocks
		// -------------------------------------

		$tagdata = ee()->functions->prep_conditionals(
			$tagdata,
			array($tag_paginate => TRUE)
		);

		// -------------------------------------
		//	count and link conditionals
		// -------------------------------------

		$pagination_items = array(
			$tag_pagination_links	=> $pagination_links,
			$tag_current_page		=> $current_page,
			$tag_total_pages		=> $total_pages,
			$tag_page_count			=> $page_count
		);

		// -------------------------------------
		//	ee 2.3 pagination array?
		// -------------------------------------

		if ( ! empty($pagination_array))
		{
			//if we don't do this first, parse_pagination
			//will attempt to convert the array to a string to compare
			$paginate_data	= ee()->functions->prep_conditionals(
				$paginate_data,
				array(
					$tag_pagination_links => true
				)
			);

			// Check to see if pagination_links is being used as a single
			// variable or as a variable pair
			if (preg_match_all(
					"/" . LD . $tag_pagination_links . RD .
						"(.+?)" .
					LD . '\/' . $tag_pagination_links . RD . "/s",
					$paginate_data,
					$matches
				))
			{
				// Parse current_page and total_pages
				$paginate_data = ee()->TMPL->parse_variables(
					$paginate_data,
					array(array($tag_pagination_links => array($pagination_array)))
				);
			}
		}
		//need blanks if there is no data
		//this helps tag pairs return blank
		else
		{
			$pagination_array = array(
				'first_page'	=> array(),
				'previous_page'	=> array(),
				'page'			=> array(),
				'next_page'		=> array(),
				'last_page'		=> array(),
			);

			//if we don't do this first, parse_pagination
			//will attempt to convert the array to a string to compare
			$paginate_data	= ee()->functions->prep_conditionals(
				$paginate_data,
				array(
					$tag_pagination_links => false
				)
			);

			$paginate_data = ee()->TMPL->parse_variables(
				$paginate_data,
				array(array($tag_pagination_links => array($pagination_array)))
			);
		}


		// -------------------------------------
		//	parse everything left
		// -------------------------------------

		$paginate_data	= ee()->functions->prep_conditionals(
			$paginate_data,
			$pagination_items
		);

		// -------------------------------------
		//	if this is EE 2.3+, we need to parse the pagination
		//	tag pair before we str_replace
		// -------------------------------------

		foreach ( $pagination_items as $key => $val )
		{
			$paginate_data	= str_replace(
				LD . $key . RD,
				$val,
				$paginate_data
			);
		}

		// ----------------------------------------
		//	Previous link
		// ----------------------------------------

		if (preg_match(
				"/" . LD . "if " . $tag_previous_page . RD .
					"(.+?)" .
				 LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
				 $paginate_data,
				 $match
			))
		{
			if ($page_previous == '')
			{
				 $paginate_data = preg_replace(
					"/" . LD . "if " . $tag_previous_page . RD .
						".+?" .
					LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
					'',
					$paginate_data
				);
			}
			else
			{
				$match['1'] 	= preg_replace(
					"/" . LD . $tag_path . '.*?' . RD . "/",
					$page_previous,
					$match['1']
				);

				$match['1'] 	= preg_replace(
					"/" . LD . $tag_auto_path . RD . "/",
					$page_previous,
					$match['1']
				);

				$paginate_data 	= str_replace(
					$match['0'],
					$match['1'],
					$paginate_data
				);
			}
		}

		// ----------------------------------------
		//	Next link
		// ----------------------------------------

		if (preg_match(
				"/" . LD . "if " . $tag_next_page . RD .
					"(.+?)" .
				LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
				$paginate_data,
				$match
			))
		{
			if ($page_next == '')
			{
				$paginate_data = preg_replace(
					"/" . LD . "if " . $tag_next_page . RD .
						".+?" .
					LD . preg_quote(T_SLASH, '/') . "if" . RD . "/s",
					'',
					$paginate_data
				);
			}
			else
			{
				$match['1'] 	= preg_replace(
					"/" . LD . $tag_path . '.*?' . RD . "/",
					$page_next,
					$match['1']
				);

				$match['1'] 	= preg_replace(
					"/" . LD . $tag_auto_path . RD . "/",
					$page_next,
					$match['1']
				);

				$paginate_data 	= str_replace(
					$match['0'],
					$match['1'],
					$paginate_data
				);
			}
		}

		// ----------------------------------------
		//	Add pagination
		// ----------------------------------------

		if ( ee()->TMPL->fetch_param('paginate') == 'both' )
		{
			$tagdata	= $paginate_data . $tagdata . $paginate_data;
		}
		elseif ( ee()->TMPL->fetch_param('paginate') == 'top' )
		{
			$tagdata	= $paginate_data . $tagdata;
		}
		else
		{
			$tagdata	= $tagdata . $paginate_data;
		}

		// ----------------------------------------
		//	Return
		// ----------------------------------------

		return $tagdata;
	}
	//END parse_pagination


	// --------------------------------------------------------------------

	/**
	 * pagination_prefix_replace
	 * gets the tag group id from a number of places and sets it to the
	 * instance default param
	 *
	 * @access 	public
	 * @param 	string 	prefix for tag
	 * @param 	string 	tagdata
	 * @param 	bool 	reverse, are we removing the preixes we did before?
	 * @return	string 	tag data with prefix changed out
	 */

	public function pagination_prefix_replace ($prefix = '', $tagdata = '', $reverse = FALSE)
	{
		if ($prefix == '')
		{
			return $tagdata;
		}

		//allowing ':' in a prefix
		if (substr($prefix, -1, 1) !== ':')
		{
			$prefix = rtrim($prefix, '_') . '_';
		}

		//if there is nothing prefixed, we don't want to do anything datastardly
		if ( ! $reverse AND
			strpos($tagdata, LD.$prefix . 'paginate'.RD) === FALSE)
		{
			return $tagdata;
		}

		$hash 	= 'e2c518d61874f2d4a14bbfb9087a7c2d';

		$items 	= array(
			'paginate',
			'pagination_links',
			'current_page',
			'total_pages',
			'page_count',
			'previous_page',
			'next_page',
			'auto_path',
			'path'
		);

		$find 			= array();
		$hash_replace 	= array();
		$prefix_replace = array();

		$length = count($items);

		foreach ($items as $key => $item)
		{
			$nkey = $key + $length;

			//this is terse, but it ensures that we
			//find any an all tag pairs if they occur
			$find[$key] 			= LD . $item . RD;
			$find[$nkey] 			= LD . T_SLASH .  $item . RD;
			$hash_replace[$key] 	= LD . $hash . $item . RD;
			$hash_replace[$nkey] 	= LD . T_SLASH .  $hash . $item . RD;
			$prefix_replace[$key] 	= LD . $prefix . $item . RD;
			$prefix_replace[$nkey] 	= LD . T_SLASH .  $prefix . $item . RD;
		}

		//prefix standard and replace prefixs
		if ( ! $reverse)
		{
			$tagdata = str_replace($find, $hash_replace, $tagdata);
			$tagdata = str_replace($prefix_replace, $find, $tagdata);
		}
		//we are on the return, fix the hashed ones
		else
		{
			$tagdata = str_replace($hash_replace, $find, $tagdata);
		}

		return $tagdata;
	}
	//END pagination_prefix_replace


	// --------------------------------------------------------------------

	/**
	 * Create XID
	 *
	 * This creates a new XID hash in the DB for usage.
	 *
	 * @access	public
	 * @return	string
	 */

	public function create_xid ()
	{
		if (is_callable(array(ee()->security, 'generate_xid')))
		{
			return ee()->security->generate_xid();
		}
		else
		{
			$sql	= "INSERT INTO exp_security_hashes (date, ip_address, hash) VALUES";

			$hash	= ee()->functions->random('encrypt');
			$sql	.= "(UNIX_TIMESTAMP(), '". ee()->input->ip_address() . "', '" . $hash . "')";

			$this->cacheless_query($sql);

			return $hash;
		}
	}
	// END Create XID


	// --------------------------------------------------------------------

	/**
	 * cacheless_query
	 *
	 * this sends a query to the db non-cached
	 *
	 * @access	public
	 * @param	string	sql to query
	 * @return	object	query object
	 */
	public function cacheless_query($sql)
	{
		$reset = FALSE;

		// Disable DB caching if it's currently set

		if (ee()->db->cache_on == TRUE)
		{
			ee()->db->cache_off();
			$reset = TRUE;
		}

		$query = ee()->db->query($sql);

		// Re-enable DB caching
		if ($reset == TRUE)
		{
			ee()->db->cache_on();
		}

		return $query;
	}
	// END cacheless_query


	// --------------------------------------------------------------------

	/**
	 * Implodes an Array and Hashes It
	 *
	 * @access	public
	 * @return	string
	 */

	public function _imploder ($arguments)
	{
		return md5(serialize($arguments));
	}
	// END


	// --------------------------------------------------------------------

	/**
	 * Prepare keyed result
	 *
	 * Take a query object and return an associative array. If $val is empty,
	 * the entire row per record will become the value attached to the indicated key.
	 *
	 * For example, if you do a query on exp_channel_titles and exp_channel_data
	 * you can use this to quickly create an associative array of channel entry
	 * data keyed to entry id.
	 *
	 * @access	public
	 * @return	mixed
	 */

	public function prepare_keyed_result ( $query, $key = '', $val = '' )
	{
		if ( ! is_object( $query )  OR $key == '' ){ return FALSE; }

		// --------------------------------------------
		//  Loop through query
		// --------------------------------------------

		$data	= array();

		foreach ( $query->result_array() as $row )
		{
			if ( isset( $row[$key] ) === FALSE ){ continue; }

			$data[ $row[$key] ]	= ( $val != '' AND isset( $row[$val] ) ) ? $row[$val]: $row;
		}

		return ( empty( $data ) ) ? FALSE : $data;
	}
	// END prepare_keyed_result


	// --------------------------------------------------------------------

	/**
	 * returns the truthy or last arg i
	 *
	 * @access	public
	 * @param	array 	args to be checked against
	 * @param	mixed	bool or array of items to check against
	 * @return	mixed
	 */
	public function either_or_base ($args = array(), $test = FALSE)
	{
		foreach ($args as $arg)
		{
			//do we have an array of nots?
			//if so, we need to be test for type
			if ( is_array($test))
			{
				if ( ! in_array($arg, $test, TRUE) ){ return $arg; }
			}
			//is it implicit false?
			elseif ($test)
			{
				if ($arg !== FALSE){ return $arg; }
			}
			//else just test for falsy
			else
			{
				if ($arg){ return $arg; }
			}
		}

		return end($args);
	}
	//END either_or_base


	// --------------------------------------------------------------------

	/**
	 * returns the truthy or last arg
	 *
	 * @access	public
	 * @param	mixed	any number of arguments consisting of variables to be returned false
	 * @return	mixed
	 */
	public function either_or()
	{
		$args = func_get_args();

		return $this->either_or_base($args);
	}
	//END either_or


	// --------------------------------------------------------------------

	/**
	 * returns the non exact bool FALSE or last arg
	 *
	 * @access	public
	 * @param	mixed	any number of arguments consisting of variables to be returned false
	 * @return	mixed
	 */
	public function either_or_strict()
	{
		$args = func_get_args();

		return $this->either_or_base($args, TRUE);
	}
	// END either_or_strict


	//---------------------------------------------------------------------

	/**
	 * add_right_link
	 * @access	public
	 * @param	string	$text	string of link name
	 * @param	string	$link	html link for right link
	 * @return	void
	 */

	public function add_right_link($text, $link)
	{
		//no funny business
		if (REQ != 'CP') return;

		$this->right_links[$text] = $link;
	}
	//end add_right_link


	//---------------------------------------------------------------------

	/**
	 * build_right_links
	 * @access	public
	 * @return	(null)
	 */

	public function build_right_links()
	{
		//no funny business
		if (REQ != 'CP' OR empty($this->right_links)) return;

		ee()->cp->set_right_nav($this->right_links);
	}
	//end build_right_links


	// --------------------------------------------------------------------

	/**
	 *	Fetch List of Member Fields and Data
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function mfields()
	{
		return $this->mfields = $this->data->get_member_fields();
	}
	// END mfields()


	// --------------------------------------------------------------------

	/**
	 * do we have any hooks?
	 *
	 *
	 * @access	public
	 * @return	bool	Whether the extensions are allowed
	 */

	public function has_hooks ()
	{
		//is it there? is it array? is it empty?
		//Such are life's unanswerable questions, until now.
		if ( ! $this->updater() OR
			((! isset($this->updater()->hooks)		OR
				! is_array($this->updater->hooks))	AND
			(! isset($this->hooks)					OR
				! is_array($this->hooks)))			OR
			(empty($this->hooks) AND empty($this->updater->hooks))
		)
		{
			return FALSE;
		}

		return TRUE;
	}
	//end has hooks


	// --------------------------------------------------------------------

	/**
	 * loads updater object and sets it to $this->upd and returns it
	 *
	 *
	 * @access	public
	 * @return	obj		updater object for module
	 */

	public function updater ()
	{
		if ( ! is_object($this->updater) )
		{
			$class		= $this->class_name . '_upd';

			$update_file 	= $this->addon_path .
								'upd.' . $this->lower_name . '.php';

			if (! class_exists($class))
			{
				if (is_file($update_file))
				{
					require_once $update_file;
				}
				else
				{
					return FALSE;
				}
			}

			$this->updater	= new $class();
		}

		return $this->updater;
	}
	//end updater


	// --------------------------------------------------------------------

	/**
	 * Checks to see if extensions are enabled for this module
	 *
	 *
	 * @access	public
	 * @param	bool	match exact number of hooks
	 * @return	bool	Whether the extensions are enabled if need be
	 */

	public function extensions_enabled ( $check_all_enabled = FALSE )
	{
		if ( ! $this->has_hooks() ) return TRUE;
		//we don't want to end on this as it would confuse users
		if ( $this->updater() === FALSE )	return TRUE;

		$num_enabled = 0;

		foreach ($this->updater()->hooks as $hook_data)
		{
			if (isset(ee()->extensions->extensions[$hook_data['hook']]))
			{
				foreach(ee()->extensions->extensions[$hook_data['hook']] as $priority => $hook_array)
				{
					if (isset($hook_array[$this->extension_name]))
					{
						$num_enabled++;
					}
				}
			}
		}

		//we arent going to look for all of the hooks
		//because some could be turned off manually for testing
		return (($check_all_enabled) ?
					($num_enabled == count($this->updater()->hooks) ) :
					($num_enabled > 0) );
	}
	//END extensions_enabled


	// --------------------------------------------------------------------

	/**
	 * AJAX Request
	 *
	 * Tests via headers or GET/POST parameter whether the incoming
	 * request is AJAX in nature
	 * Useful when we want to change the output of a method.
	 *
	 * @access public
	 * @return boolean
	 */

	public function is_ajax_request ()
	{
		// --------------------------------------------
		//  Headers indicate this is an AJAX Request
		//	- They can disable via a parameter or GET/POST
		//	- If not, TRUE
		// --------------------------------------------

		if (ee()->input->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest')
		{
			// Check for parameter
			if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
			{
				if (ee()->TMPL->fetch_param('ajax_request') !== FALSE &&
					$this->check_no(ee()->TMPL->fetch_param('ajax_request')))
				{
					return FALSE;
				}
			}

			// Check for GET/POST variable
			if (ee()->input->get_post('ajax_request') !== FALSE &&
				$this->check_no(ee()->input->get_post('ajax_request')))
			{
				return FALSE;
			}

			// Not disabled
			return TRUE;
		}

		// --------------------------------------------
		//  Headers do NOT indicate it is an AJAX Request
		//	- They can force with a parameter OR GET/POST variable
		//	- If not, FALSE
		// --------------------------------------------

		if (isset(ee()->TMPL) AND is_object(ee()->TMPL))
		{
			if ($this->check_yes(ee()->TMPL->fetch_param('ajax_request')))
			{
				return TRUE;
			}
		}

		if ($this->check_yes(ee()->input->get_post('ajax_request')))
		{
			return TRUE;
		}

		return FALSE;
	}
	// END is_ajax_request()


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

	public function send_ajax_response ($msg, $error = FALSE, $cache_bust = TRUE)
	{
		ee()->output->enable_profiler(FALSE);

		if ($error === TRUE)
		{
			//ee()->output->set_status_header(500);
		}

		$send_headers = (ee()->config->item('send_headers') == 'y');

		//if this is an array or object, output json
		if (is_array($msg) OR is_object($msg))
		{
			if ($send_headers)
			{
				if ($cache_bust)
				{
					//cache bust
					@header('Cache-Control: no-cache, must-revalidate');
					@header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				}

				@header('Content-Type: application/json');
			}

			echo $this->json_encode($msg);
		}
		else
		{
			if ($send_headers)
			{
				@header('Content-Type: text/html; charset=UTF-8');
			}

			echo (string) $msg;
		}

		exit();
	}
	//END send_ajax_response()


	// --------------------------------------------------------------------

	/**
	 *	Validate Emails
	 *
	 *	Validates an array or parses a string of emails and then validates
	 *
	 *	@access		public
	 *	@param		string|array
	 *	@return		array  $vars - Contains two keys good/bad of,
	 *								what else, good and bad emails
	 */
	public function validate_emails ($emails)
	{
		ee()->load->helper('email');

		if ( is_string($emails))
		{
			// Remove all white space and replace with commas
			$email	= trim(
				preg_replace("/\s*(\S+)\s*/s", "\\1,", trim($emails)),
				','
			);

			// Remove duplicate commas
			$email	= str_replace(',,', ',', $email);

			// Explode and make unique
			$emails	= array_unique(explode(",", $email));
		}

		$vars['good']	= array();
		$vars['bad']	= array();

		foreach($emails as $addr)
		{
			if (preg_match('/<(.*)>/', $addr, $match))
			{
				$addr = $match[1];
			}

			if ( ! valid_email($addr))
			{
				$vars['bad'][] = $addr;
				continue;
			}

			$vars['good'][] = $addr;
		}

		return $vars;
	}
	// END validate_emails();


	// --------------------------------------------------------------------

	/**
	 *	Get Action URL
	 *
	 * 	returns a full URL for an action
	 *
	 *	@access		public
	 *	@param		string method name
	 *	@return		string url for action
	 */

	public function get_action_url ($method_name)
	{
		$action_q	= ee()->db->where(array(
			'class'		=> $this->class_name,
			'method'	=> $method_name
		))->get('actions');

		if ($action_q->num_rows() == 0)
		{
			return false;
		}

		$action_id = $action_q->row('action_id');

		return ee()->functions->fetch_site_index(0, 0) .
					QUERY_MARKER . 'ACT=' . $action_id;
	}
	//END get_action_url


	// --------------------------------------------------------------------

	/**
	 * is_positive_intlike
	 *
	 * return
	 *
	 * (is_positive_entlike would have taken forever)
	 *
	 * @access 	public
	 * @param 	mixed 	num 		number/int/string to check for numeric
	 * @param 	int 	threshold 	lowest number acceptable (default 1)
	 * @return  bool
	 */

	public function is_positive_intlike($num, $threshold = 1)
	{
		//without is_numeric, bools return positive
		//because preg_match auto converts to string
		return (
			is_numeric($num) AND
			preg_match("/^[0-9]+$/", $num) AND
			$num >= $threshold
		);
	}
	//END is_positive_intlike


	// --------------------------------------------------------------------

	/**
	 * get_post_or_zero
	 *
	 * @access	public
	 * @param 	string 	name of GET/POST var to check
	 * @return	int 	returns 0 if the get/post is not present or numeric or above 0
	 */

	public function get_post_or_zero($name)
	{
		$name = ee()->input->get_post($name);
		return ($this->is_positive_intlike($name) ? $name : 0);
	}
	//END get_post_or_zero


	// --------------------------------------------------------------------

	/**
	 * Install/Update Our Extension for Module
	 *
	 * Tells ExpressionEngine what extension hooks
	 * we wish to use for this module.  If an extension
	 * is part of a module, then it is the module's class
	 * name with the '_extension' (1.x) or '_ext' 2.x
	 * suffix added on to it.
	 *
	 * @access	public
	 * @return	null
	 */

	public function update_extension_hooks()
	{
		if ( ! is_array($this->hooks) OR
			 count($this->hooks) == 0)
		{
			return TRUE;
		}

		//fix EE 1.x extension names
		ee()->db->update(
			'exp_extensions',
			array(
				'class'		=> $this->extension_name,
				'enabled'	=> 'y'
			),
			array(
				'class'		=> $this->class_name . '_extension'
			)
		);

		// --------------------------------------------
		//  Determine Existing Methods
		// --------------------------------------------

		$exists	= array();

		if ($this->settings == '')
		{
			ee()->db->select('settings');
		}

		$query = ee()->db
					->select('method')
					->where('class', $this->extension_name)
					->get('extensions');

		foreach ( $query->result_array() AS $row )
		{
			$exists[] = $row['method'];

			if ($this->settings == '' AND ! empty($row['settings']))
			{
				ee()->load->helper('string');
				$this->settings = strip_slashes(unserialize($row['settings']));
			}
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
		//  Find Missing and Insert
		// --------------------------------------------

		$current_methods = array();

		foreach($this->hooks as $data)
		{
			// Default exp_extension fields, overwrite with any from array
			$data = array_merge($this->extension_defaults, $data);

			$current_methods[] = $data['method'];

			if ( ! in_array($data['method'], $exists))
			{
				// Every so often, EE can accidentally send empty
				// $settings argument to the constructor, so
				// our new hooks will not have any settings,
				// so we have to fix that here.

				if ($data['settings'] == '' OR $data['settings'] == 's:0:"";')
				{
					$data['settings'] = serialize($this->settings);
				}

				ee()->db->insert(
					'extensions',
					$data
				);
			}
			else
			{
				unset($data['settings']);

				ee()->db->update(
					'extensions',
					$data,
					array(
						'class' 	=> $data['class'],
						'method' 	=> $data['method']
					)
				);
			}
		}

		// --------------------------------------------
		//  Remove Old Hooks
		// --------------------------------------------

		$old_hooks = array_diff($exists, $current_methods);

		if ( ! empty($old_hooks))
		{
			ee()->db
				->where_in('method', $old_hooks)
				->where('class', $this->extension_name)
				->delete('extensions');
		}
	}
	// END update_extension_hooks()


	// --------------------------------------------------------------------

	/**
	 * Remove Extension Hooks
	 *
	 * Removes all of the extension hooks that will be called for this module
	 *
	 * @access	public
	 * @return	null
	 */

	public function remove_extension_hooks()
	{
		ee()->db
			->where('class', $this->extension_name)
			->delete('extensions');

		// --------------------------------------------
		//  Remove from $EE->extensions->extensions array
		// --------------------------------------------

		foreach(ee()->extensions->extensions as $hook => $calls)
		{
			foreach($calls as $priority => $class_data)
			{
				foreach($class_data as $class => $data)
				{
					if ($class == $this->class_name OR
						$class == $this->extension_name)
					{
						unset(
							ee()->extensions
								->extensions[$hook][$priority][$class]
						);
					}
				}
			}
		}
	}
	// END remove_extension_hooks


	// --------------------------------------------------------------------

	/**
	 *	Fetch Language File
	 *
	 *	Two known extensions sessions_end and sessions_start are
	 *	called prior to Language being instantiated, so we wrote
	 *	our own little method here that removes the
	 *	ee()->session->userdata check and still loads the
	 *	language file for the extension, if required...
	 *
	 *	With this method, we can add items to the lang files
	 *	without having to add to the ee()->lang->is_loaded
	 *	array and cause issues with foreign lang files that need to
	 *	be loaded _after_ sessions_end
	 *
	 * @access	public
	 * @param	string  $which	name of langauge file
	 * @param	boolean $object	session object
	 * @param	boolean $add_to_ee_lang	session object
	 * @return	null
	 */

	public function fetch_language_file($which = '', $object = FALSE, $add_to_ee_lang = TRUE)
	{
		if ($which == '')
		{
			return FALSE;
		}

		if ( ! $object AND isset(ee()->session))
		{
			$object =& ee()->session;
		}

		if (is_object($object) AND
			strtolower(get_class($object)) == 'session' AND
			$object->userdata['language'] != '')
		{
			$user_lang = $object->userdata['language'];
		}
		else
		{
			if (ee()->input->cookie('language'))
			{
				$user_lang = ee()->input->cookie('language');
			}
			elseif (ee()->config->item('deft_lang') != '')
			{
				$user_lang = ee()->config->item('deft_lang');
			}
			else
			{
				$user_lang = 'english';
			}
		}

		//no BS
		$user_lang	= ee()->security->sanitize_filename($user_lang);
		$which		= ee()->security->sanitize_filename($which);

		if ( ! in_array($which, $this->is_loaded))
		{
			$options = array(
				$this->addon_path . 'language/'.$user_lang.'/lang.'.$which.'.php',
				$this->addon_path . 'language/'.$user_lang.'/'.$which.'_lang.php',
				$this->addon_path . 'language/english/lang.'.$which.'.php',
				$this->addon_path . 'language/english/'.$which.'_lang.php'
			);

			$success = FALSE;

			foreach($options as $path)
			{
				if ( file_exists($path) AND include $path)
				{
					$success = TRUE;
					break;
				}
			}

			if ($success == FALSE)
			{
				return FALSE;
			}

			if (isset($lang))
			{
				$this->is_loaded[] = $which;

				$this->language = array_merge(
					$this->language,
					$lang
				);

				if ($add_to_ee_lang AND isset(ee()->lang->language))
				{
					ee()->lang->language = array_merge(
						ee()->lang->language,
						$lang
					);
				}

				unset($lang);
			}
		}

		return TRUE;
	}
	// END fetch_language_file


	// --------------------------------------------------------------------

	/**
	 * Line from lang file
	 *
	 * This is not meant to take over EE's lang line, but rather
	 * a way to load and use our own lang lines if EE's isn't available
	 * at the moment, like on some early hook.
	 *
	 * @access	public
	 * @param	string	$which	which lang line you want
	 * @param	string	$label	name of item this is a label for
	 * @return	string			lang line
	 */

	public function line($which = '', $label = '')
	{
		if ($which != '')
		{
			if ( ! isset($this->language[$which]) AND
				( ! function_exists('lang') OR lang($which) == $which)
			)
			{
				$line = $which;
			}
			else
			{
				$line = ( ! isset($this->language[$which])) ?
							lang($which) :
							$this->language[$which];
			}

			if ($label != '')
			{
				$line = '<label for="'.$label.'">'.$line."</label>";
			}

			return stripslashes($line);
		}
		else
		{
			return $which;
		}
	}
	// END Line


	// --------------------------------------------------------------------

	/**
	 * Setup Unit Test Mode
	 *
	 * @access	public
	 * @param	mixed	$test_mock	mock object to send functions to
	 * @return	void
	 */

	public function setup_unit_test_mode($test_mock = FALSE, $do_actions = TRUE)
	{
		$this->unit_test_mode = TRUE;

		if (is_object($test_mock))
		{
			$this->test_mock = $test_mock;
		}
		else if (is_string($test_mock) && class_exists($test_mock))
		{
			$this->test_mock = new $test_mock();
		}

		if ($do_actions &&
			! preg_match("/_actions$/", get_class($this)) &&
			$this->actions() &&
			is_callable(array($this->actions(), 'setup_unit_test_mode'))
		)
		{
			$this->actions()->setup_unit_test_mode($test_mock, FALSE);
		}
	}
	//END setup_unit_test_mode


	// --------------------------------------------------------------------

	/**
	 * Test Object
	 * Sets the test object with a dummy if not already set
	 *
	 * @access	protected
	 * @return	object		test_object instance
	 */

	protected function test_object()
	{
		if ( ! isset($this->test_mock) ||
			 ! is_object($this->test_mock))
		{
			$dummy_class_name = $this->class_name . '_aob_method_mock';

			if ( ! class_exists($dummy_class_name))
			{
				//dummy for testing if mocks aren't needed
				eval(
					'class ' . $dummy_class_name . '
					{
						//function catch all
						public function __call($method, $args)
						{
							return FALSE;
						}
					}'
				);
			}

			$this->test_mock = new $dummy_class_name();
		}

		return $this->test_mock;
	}
	//END test_object


	// --------------------------------------------------------------------

	/**
	 * Fire Test Method
	 *
	 * @access	protected
	 * @param	string	$caller	calling function
	 * @param	string	$method method desired
	 * @param	array	$args   arguments for function (optional)
	 * @return	mixed			function result or array false
	 */
	protected function test_method($caller, $method, $args = array())
	{
		if (is_callable(array($this->test_object(), $method)))
		{
			$this->test_object()->_test_mode_caller = $caller;

			return call_user_func_array(array($this->test_object(), $method), $args);
		}

		return FALSE;
	}
	//END test_method


	// --------------------------------------------------------------------

	/**
	 * Human time changes functions in EE 2.6
	 *
	 * @access	public
	 * @param	mixed	$time	time to set for human output
	 * @return	string			converted time
	 */
	public function human_time($time)
	{
		if (is_callable(array(ee()->localize, 'human_time')))
		{
			return ee()->localize->human_time($time);
		}
		else
		{
			return ee()->localize->set_human_time($time);
		}
	}
	//END human_time


	// --------------------------------------------------------------------

	/**
	 *  Fetch Date Params added due to depracation of Localize one with
	 *  no good reason given *shrug*
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function fetch_date_params($datestr = '')
	{
		if ($datestr == '')
		{
			return;
		}

		if ( ! preg_match_all("/(%\S)/", $datestr, $matches))
		{
			return;
		}

		return $matches[1];
	}
	//END fetch_date_params


	// --------------------------------------------------------------------

	/**
	 * Convert timestamp legacy support
	 * EE 2.6 moves this to format_date
	 *
	 * @access	public
	 * @param	mixed	$time	timestamp to convert
	 * @return	string			converted timestamp
	 */

	public function convert_timestamp($format = '', $time = '', $localize = TRUE)
	{
		if (is_callable(array(ee()->localize,'format_date')))
		{
			$return_str = FALSE;

			if ( ! is_array($format))
			{
				$return_str = TRUE;
				$format = array($format);
			}

			foreach ($format as $var)
			{
				$out[] = ee()->localize->format_date($var, $time, $localize);
			}

			return ($return_str) ? array_pop($out) : $out;
		}
		else
		{
			return ee()->localize->convert_timestamp($format, $time, $localize);
		}
	}
	//END convert_timestamp


	// ------------------------------------------------------------------------

	/**
	 * Get timezone offset from legacy or modern
	 *
	 * @access public
	 * @return int
	 */

	public function timezone_offset()
	{
		ee()->load->helper('date');

		$offset		= 0;
		$timezones	= timezones();
		$timezone	= $this->either_or(
			ee()->config->item('default_site_timezone'),
			ee()->config->item('server_timezone'),
			date_default_timezone_get()
		);

		// Check legacy timezone formats
		if (isset($timezones[$timezone]))
		{
			$offset = $timezones[$timezone] * 3600;
		}
		// Otherwise, get the offset from DateTime, if possible
		else if (class_exists('DateTime'))
		{
			$dt = new DateTime('now', new DateTimeZone($timezone));

			if ($dt)
			{
				$offset = $dt->getOffset();
			}
		}

		return $offset;
	}
	//END timezone_offset


	// --------------------------------------------------------------------

	/**
	 * Get Sites
	 *
	 * @access	public
	 * @return	array	site data
	 */

	protected function get_sites()
	{
		if ( ! empty($this->sites))
		{
			return $this->sites;
		}

		if (isset(ee()->session) AND
			is_object(ee()->session) AND
			isset(ee()->session->userdata['group_id']) AND
			ee()->session->userdata['group_id'] == 1 AND
			isset(ee()->session->userdata['assigned_sites']) AND
			is_array(ee()->session->userdata['assigned_sites']))
		{
			$this->sites = ee()->session->userdata['assigned_sites'];
			return $this->sites;
		}

		//--------------------------------------------
		// Perform the Actual Work
		//--------------------------------------------

		ee()->db
			->select('site_id, site_label')
			->from('exp_sites');

		if (ee()->config->item('multiple_sites_enabled') == 'y')
		{
			ee()->db->order_by('site_label');
		}
		else
		{
			ee()->db->where('site_id', 1);
		}

		$sites_query = ee()->db->get();

		//no need to check here, EE won't even start without
		//these present in the DB
		$this->sites = $this->prepare_keyed_result(
			$sites_query,
			'site_id',
			'site_label'
		);

		return $this->sites;
	}
	//END get_sites


	// --------------------------------------------------------------------

	/**
	 * Class Lib Loader
	 *
	 * Lib loader that cuts down on obnoxious lines like
	 * ee()->load->library('my_addon_name_class_name');
	 * ee()->my_addon_name_class_name->run_long_name();
	 * and turns it to $this->class_lib('lib_name')->method();
	 *
	 * This turns out visually as long as StaticC::autoLoadHelper()
	 * and lets us reuse more code because the class name is auto generated.
	 *
	 * This assumes the root addon name as a prefix and then the name
	 * of what the class is. E,g, Super_Search_notifications would be
	 * $this->lib('notifications')->method();
	 *
	 * @access	public
	 * @param	string $name	singular table name from full model name
	 * @return	object			model instance from EE instance
	 */

	public function lib($name)
	{
		$full_name = $this->lower_name . '_' . $name;

		return $this->_lib_mod_loader($full_name, 'library');
	}
	//END lib


	// --------------------------------------------------------------------

	/**
	 * Model
	 *
	 * Model loader that cuts down on obnoxious lines like
	 * ee()->load->model('my_addon_name_table_name_model');
	 * ee()->my_addon_name_table_name_model->run_long_name();
	 * and turns it to $this->model('table_name')->method();
	 *
	 * This turns out visually as long as StaticC::autoLoadHelper()
	 * and lets us reuse more code because the class name is auto generated.
	 *
	 * This assumes the root addon name as a prefix and '_model' as a postfix
	 * for all model classnames. We should be doing that anyway really to
	 * prevent collision with other addons. There are a lot out there.
	 *
	 * @access	public
	 * @param	string $name	singular table name from full model name
	 * @return	object			model instance from EE instance
	 */

	public function model($name = '')
	{
		$full_name = $this->lower_name . '_' . $name . '_model';

		return $this->_lib_mod_loader($full_name, 'model');
	}
	//END model


	// --------------------------------------------------------------------

	/**
	 * Helper function for library and model loaders
	 *
	 * @access	public
	 * @param	string $name	singular table name from full model name
	 * @param	string $type	type of object for EE to load. model or library
	 * @return	object			model instance from EE instance
	 */

	protected function _lib_mod_loader($object_name, $type = 'library')
	{
				//get out quick if possible
		if (isset(ee()->$object_name))
		{
			return ee()->$object_name;
		}
		else
		{
			ee()->load->$type($object_name);
		}

		//run isset again in cause load fails but doesn't fire
		//a fatal error
		if (isset(ee()->$object_name))
		{
			return ee()->$object_name;
		}
		else
		{
			trigger_error(
				'No ' . ucfirst($type) . ' named "' . $object_name . '" found',
				E_USER_NOTICE
			);
		}
	}
	//END _lib_mod_loader
}
// END Addon_builder Class
