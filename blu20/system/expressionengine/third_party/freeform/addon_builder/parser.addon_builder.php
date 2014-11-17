<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Addon Builder - Abstracted Template Parser
 *
 * Augments EE templating capability. Does not replace it.
 * Portions of this code are derived from core.template.php.
 * They are used with the permission of EllisLab, Inc.
 *
 *
 * @package		Solspace:Addon Builder
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/
 * @license		http://www.solspace.com/license_agreement/
 * @version		1.4.4
 * @filesource 	addon_builder/parser.addon_builder.php
 */

get_instance()->load->library('template');

if ( ! class_exists('Addon_builder_freeform'))
{
	require_once 'addon_builder.php';
}

class Addon_builder_parser_freeform extends EE_Template
{
	private $old_get = '';

	/**
	 * Addon builder instance for helping
	 *
	 * @var object
	 */
	protected $aob;

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

		$this->EE =& get_instance();
		$this->aob = new Addon_builder_freeform();

		// --------------------------------------------
		//  Solves the problem of redirect links (?URL=)
		//  being added by Typography in a CP request
		// --------------------------------------------

		if (REQ == 'CP')
		{
			$this->old_get = (isset($_GET['M'])) ? $_GET['M'] : '';
			$_GET['M'] = 'send_email';
		}

		// --------------------------------------------
		//  ExpressionEngine only loads snippets
		//  on PAGE and ACTION requests
		// --------------------------------------------

		// load up any Snippets
		ee()->db->select('snippet_name, snippet_contents');
		ee()->db->where(
			'(site_id = ' . ee()->config->item('site_id').' OR site_id = 0)'
		);
		$fresh = ee()->db->get('snippets');

		if ($fresh->num_rows() > 0)
		{
			$snippets = array();

			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}

			$var_keys = array();

			foreach (ee()->config->_global_vars as $k => $v)
			{
				$var_keys[] = LD.$k.RD;
			}

			foreach ($snippets as $name => $content)
			{
				$snippets[$name] = str_replace(
					$var_keys,
					array_values(ee()->config->_global_vars),
					$content
				);
			}

			ee()->config->_global_vars = array_merge(
				ee()->config->_global_vars,
				$snippets
			);
		}

		unset($snippets);
		unset($fresh);
	}
	/* END constructor() */


	// --------------------------------------------------------------------

	/**
	 * Skeletor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __destruct()
	{
		if (REQ == 'CP')
		{
			$_GET['M'] = $this->old_get;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Process Template
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @param	string|integer
	 * @return	null
	 */

	public function process_string_as_template($str)
	{
		// standardize newlines
		$str	= preg_replace("/(\015\012)|(\015)|(\012)/", "\n", $str);

		ee()->load->helper('text');

		// convert high ascii
		$str	= (
			ee()->config->item('auto_convert_high_ascii') == 'y'
		) ? ascii_to_entities($str): $str;

		// -------------------------------------
		//  Prepare for Processing
		// -------------------------------------

		$this->template_type	= 'webpage';

		$this->template			= $this->convert_xml_declaration(
			$this->remove_ee_comments($str)
		);

		$this->log_item("Template Type: ".$this->template_type);

		// --------------------------------------------------
		//  Parse 'Site' variables
		// --------------------------------------------------

		$this->log_item("Parsing Site Variables");

		foreach (array('site_id', 'site_label', 'site_short_name') as $site_var)
		{
			$this->global_vars[$site_var] = stripslashes(
				ee()->config->item($site_var)
			);
		}

		// Parse {last_segment} variable
		$seg_array = ee()->uri->segment_array();

		ee()->config->_global_vars['last_segment'] = end($seg_array);;

		// --------------------------------------------
		//  Parse Global Vars - EE 2.x
		// --------------------------------------------

		$this->log_item(
			"Snippets (Keys): " .
			implode('|', array_keys(ee()->config->_global_vars))
		);
		$this->log_item(
			"Snippets (Values): " .
			trim(implode('|', ee()->config->_global_vars))
		);

		foreach (ee()->config->_global_vars as $key => $val)
		{
			$this->template = str_replace(LD.$key.RD, $val, $this->template);
		}

		// in case any of these variables have EE comments of their own
		$this->template = $this->remove_ee_comments($this->template);

		// -------------------------------------
		//  Parse Global Vars - Solspace Modules
		//  (which use this for setting own globals)
		// -------------------------------------

		if (count($this->global_vars) > 0)
		{
			$this->log_item(
				"Global Path.php Variables (Keys): " .
				implode('|', array_keys($this->global_vars))
			);
			$this->log_item(
				"Global Path.php Variables (Values): " .
				trim(implode('|', $this->global_vars))
			);

			foreach ($this->global_vars as $key => $val)
			{
				$this->template = str_replace(LD.$key.RD, $val, $this->template);
			}
		}

		// -------------------------------------
		//  Parse URI segments
		// -------------------------------------

		for ($i = 1; $i < 10; $i++)
		{
			$this->template = str_replace(
				LD.'segment_'.$i.RD,
				ee()->uri->segment($i),
				$this->template
			);
			$this->segment_vars['segment_'.$i] = ee()->uri->segment($i);
		}

		/** -------------------------------------
		/**  Parse date format string "constants"
		/** -------------------------------------*/

		$date_constants	= array(
			'DATE_ATOM'		=>	'%Y-%m-%dT%H:%i:%s%Q',
			'DATE_COOKIE'	=>	'%l, %d-%M-%y %H:%i:%s UTC',
			'DATE_ISO8601'	=>	'%Y-%m-%dT%H:%i:%s%O',
			'DATE_RFC822'	=>	'%D, %d %M %y %H:%i:%s %O',
			'DATE_RFC850'	=>	'%l, %d-%M-%y %H:%m:%i UTC',
			'DATE_RFC1036'	=>	'%D, %d %M %y %H:%i:%s %O',
			'DATE_RFC1123'	=>	'%D, %d %M %Y %H:%i:%s %O',
			'DATE_RFC2822'	=>	'%D, %d %M %Y %H:%i:%s %O',
			'DATE_RSS'		=>	'%D, %d %M %Y %H:%i:%s %O',
			'DATE_W3C'		=>	'%Y-%m-%dT%H:%i:%s%Q'
		);

		$this->log_item("Parse Date Format String Constants");

		foreach ($date_constants as $key => $val)
		{
			$this->template = str_replace(LD.$key.RD, $val, $this->template);
		}

		// --------------------------------------------------
		//  Current time {current_time format="%Y %m %d %H:%i:%s"}
		// --------------------------------------------------

		$this->log_item("Parse Current Time Variables");

		$this->template = str_replace(
			LD.'current_time'.RD,
			ee()->localize->now,
			$this->template
		);

		if (strpos($this->template, LD.'current_time') !== FALSE AND
			preg_match_all(
				"/".LD."current_time\s+format=([\"\'])([^\\1]*?)\\1".RD."/",
				$this->template,
				$matches
			))
		{
			for ($j = 0; $j < count($matches['0']); $j++)
			{
				//EE2.6+ support
				$func = (is_callable(array(ee()->localize, 'format_date'))) ?
							'format_date' :
							'decode_date';

				$this->template = preg_replace(
					"/".preg_quote($matches['0'][$j], '/')."/",
					ee()->localize->$func(
						$matches['2'][$j],
						ee()->localize->now
					),
					$this->template,
					1
				);
			}
		}

		// --------------------------------------------
		//  Remove White Space from Variables
		//		- Prevents errors apparently,
		//		particularly when PHP is used in a template.
		// --------------------------------------------

		$this->template = preg_replace(
			"/".LD."\s*(\S+)\s*".RD."/U",
			LD."\\1".RD,
			$this->template
		);

		// -------------------------------------
		//  Parse Input Stage PHP
		// -------------------------------------

		if ($this->parse_php == TRUE AND $this->php_parse_location == 'input')
		{
			$this->log_item("Parsing PHP on Input");
			$this->template = $this->parse_template_php($this->template);
		}

		// -------------------------------------
		//  Smite Our Enemies:  Conditionals
		// -------------------------------------

		$this->log_item("Parsing Segment, Embed, and Global Vars Conditionals");

		$this->template = $this->parse_simple_segment_conditionals($this->template);
		$this->template = $this->simple_conditionals($this->template, $this->embed_vars);
		$this->template = $this->simple_conditionals($this->template, ee()->config->_global_vars);

		// -------------------------------------
		//  Set global variable assignment
		// -------------------------------------

		if (strpos($this->template, LD.'assign_variable:') !== FALSE AND
			preg_match_all("/".LD."assign_variable:(.+?)=([\"\'])([^\\2]*?)\\2".RD."/i", $this->template, $matches))
		{
			$this->log_item("Processing Assigned Variables: ".trim(implode('|', $matches['1'])));

			for ($j = 0; $j < count($matches['0']); $j++)
			{
				$this->template = str_replace($matches['0'][$j], "", $this->template);
				$this->template = str_replace(LD.$matches['1'][$j].RD, $matches['3'][$j], $this->template);
			}
		}

		// -------------------------------------
		//  Replace Forward Slashes with Entity
		//  because of silliness about pre_replace errors.
		// -------------------------------------

		if (strpos($str, '{&#47;exp:') !== FALSE)
		{
			$this->template = str_replace('&#47;', '/', $this->template);
		}

		// --------------------------------------------
		//  Fetch Installed Modules and Plugins
		// --------------------------------------------

		$this->fetch_addons();

		// --------------------------------------------
		//  Parse Template's Tags!
		// --------------------------------------------

		$this->log_item(" - Beginning Tag Processing - ");

		while (is_int(strpos($this->template, LD.'exp:')))
		{
			// Initialize values between loops
			$this->tag_data 	= array();
			$this->var_single	= array();
			$this->var_cond		= array();
			$this->var_pair		= array();
			$this->loop_count 	= 0;

			$this->log_item("Parsing Tags in Template");

			// Run the template parser
			$this->parse_tags();

			$this->log_item("Processing Tags");

			// Run the class/method handler
			$this->process_tags();

			if ($this->cease_processing === TRUE)
			{
				return;
			}
		}

		$this->log_item(" - End Tag Processing - ");

		// --------------------------------------------
		//  Convert Slash Entity Back
		// --------------------------------------------

		$this->template = str_replace(SLASH, '/', $this->template);

		// -------------------------------------
		//  Parse Output Stage PHP
		// -------------------------------------

		if ($this->parse_php == TRUE AND $this->php_parse_location == 'output')
		{
			$this->log_item("Parsing PHP on Output");
			$this->template = $this->parse_template_php($this->template);
		}

		// -------------------------------------
		//  Parse Our Uncacheable Forms
		// -------------------------------------

		$this->template = $this->parse_nocache($this->template);

		// -------------------------------------
		//  Smite Our Enemies:  Advanced Conditionals
		// -------------------------------------

		if (stristr($this->template, LD.'if'))
		{
			$this->log_item("Processing Advanced Conditionals");
			$this->template = $this->advanced_conditionals($this->template);
		}

		// -------------------------------------
		//  Build finalized template
		// -------------------------------------

		// The sub-template routine will insert embedded
		// templates into the master template

		$this->final_template = $this->template;
		$this->process_sub_templates($this->template);

		// --------------------------------------------
		//  Finish with Global Vars and Return!
		// --------------------------------------------

		return $this->parse_globals($this->final_template);
	 }
	// END process_string_as_template


	// --------------------------------------------------------------------

	/**
	 * Class Handler
	 *
	 * @access	public
	 * @return	null
	 */

	public function class_handler()
	{
		$classes = array();

		// Fill an array with the names of all the classes
		// that we previously extracted from the tags

		for ($i = 0, $s = count($this->tag_data); $i < $s; $i++)
		{
			// Should we use the tag cache file?

			if ($this->tag_data[$i]['cache'] == 'CURRENT')
			{
				// If so, replace the marker in the tag with the cache data

				$this->log_item("Tag Cached and Cache is Current");

				$this->replace_marker(
					$i,
					$this->get_cache_file($this->tag_data[$i]['cfile'])
				);

				continue;
			}

			// --------------------------------------------
			//  Module or Plugin Being Called?
			// --------------------------------------------

			$class	= $this->tag_data[$i]['class'];
			$method	= $this->tag_data[$i]['method'];

			if ( ! in_array($class, $this->modules))
			{
				if ( ! in_array($class, $this->plugins))
				{
					$this->log_item("Invalid Tag");

					if (ee()->config->item('debug') < 1)
					{
						return FALSE;
					}

					if ($this->tag_data[$i]['tagparts'][0] == $this->tag_data[$i]['tagparts'][1] AND
						! isset($this->tag_data[$i]['tagparts'][2]))
					{
						unset($this->tag_data[$i]['tagparts'][1]);
					}

					$error  = lang('error_tag_syntax');
					$error .= '<br /><br />';
					$error .= htmlspecialchars(LD);
					$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);
					$error .= htmlspecialchars(RD);
					$error .= '<br /><br />';
					$error .= lang('error_fix_syntax');

					ee()->output->fatal_error($error);
				}
				else
				{
					$classes[] = 'pi.'.$this->tag_data[$i]['class'];
					$this->log_item("Plugin Tag: ".ucfirst($class).'/'.$method);
				}
			}
			else
			{
				$classes[] = $this->tag_data[$i]['class'];
				$this->log_item("Module Tag: ".ucfirst($class).'/'.$method);
			}
		}

		// --------------------------------------------
		//  Remove Duplicates and Fresh Order
		// --------------------------------------------

		$classes = array_values(array_unique($classes));

		// --------------------------------------------
		//  Load Files for Classes
		// --------------------------------------------

		$this->log_item("Including Files for Tag and Modules");

		for ($i = 0; $i < count($classes); $i++)
		{
			if (class_exists($classes[$i]))
			{
				continue;
			}

			if (substr($classes[$i], 0, 3) == 'pi.' AND
				file_exists(PATH_PI.$classes[$i].EXT))
			{
				require_once PATH_PI.$classes[$i].EXT;
			}
			else if (file_exists(PATH_MOD.$classes[$i].'/mod.'.$classes[$i].EXT))
			{
				require_once PATH_MOD.$classes[$i].'/mod.'.$classes[$i].EXT;
			}
		}

		// -----------------------------------
		//  Only Retrieve Data if Not Done Before and Modules Being Called
		// -----------------------------------

		if (count($this->module_data) == 0 AND
			count(array_intersect($this->modules, $classes)) > 0)
		{
			$query = ee()->db->query(
				"SELECT module_version, module_name FROM exp_modules"
			);

			foreach($query->result_array() as $row)
			{
				$this->module_data[$row['module_name']] = array('version' => $row['module_version']);
			}
		}

		// --------------------------------------------
		//  Final Data Processing - Loop Through and Parse
		// --------------------------------------------

		$this->log_item("Beginning Final Tag Data Processing");

		reset($this->tag_data);

		for ($i = 0, $s = count($this->tag_data); $i < $s; $i++)
		{
			if ($this->tag_data[$i]['cache'] == 'CURRENT')
			{
				continue;
			}

			$lower_class_name	= strtolower($this->tag_data[$i]['class']);
			$class_name			= ucfirst($lower_class_name);
			$method				= $this->tag_data[$i]['method'];

			$this->log_item("Calling Class/Method: ".$class_name."/".$method);

			// --------------------------------------------
			//  Plugin as Parameter
			//  - Example: channel="{exp:some_plugin}"
			//  - A bit of a hidden feature.  Has been tested
			//  but not quite ready to say it is
			//  ready for prime time as I might want to move
			//  it to earlier in processing so that
			//  if there are multiple plugins being used as
			//  parameters it is only called
			//  once instead of for every single parameter.
			// --------------------------------------------

			if (substr_count($this->tag_data[$i]['tag'], LD.'exp') > 1 AND
				isset($this->tag_data[$i]['params']['parse']) AND
				$this->tag_data[$i]['params']['parse'] == 'inward')
			{
				foreach($this->tag_data[$i]['params'] as $name => $param)
				{
					if (stristr($this->tag_data[$i]['params'][$name], LD.'exp'))
					{
						$this->log_item("Plugin in Parameter, Processing Plugin First");

						$this->tag_data[$i]['params'][$name] = $this->nested_processing(
							$this->tag_data[$i]['params'][$name]
						);
					}
				}
			}

			// --------------------------------------------
			//  Nested Plugins
			// --------------------------------------------

			if (in_array($lower_class_name, $this->plugins) AND
				strpos($this->tag_data[$i]['block'], LD.'exp:') !== false)
			{
				if ( ! isset($this->tag_data[$i]['params']['parse']) OR
					$this->tag_data[$i]['params']['parse'] != 'inward')
				{
					$this->log_item("Nested Plugins in Tag, Parsing Outward First");

					$this->tag_data[$i]['block'] = $this->nested_processing(
						$this->tag_data[$i]['block']
					);
				}
			}

			// --------------------------------------------
			//  Assign Class Variables for Parsing
			// --------------------------------------------

			// We moved the no_results_block here because of nested tags.
			// The first parsed tag has priority for that conditional.

			$this->tagdata   		= str_replace(
				$this->tag_data[$i]['no_results_block'],
				'',
				$this->tag_data[$i]['block']
			);
			$this->tagparams 		= $this->tag_data[$i]['params'];
			$this->tagchunk  		= $this->tag_data[$i]['chunk'];
			$this->tagproper		= $this->tag_data[$i]['tag'];
			$this->tagparts			= $this->tag_data[$i]['tagparts'];
			$this->no_results		= $this->tag_data[$i]['no_results'];
			$this->search_fields	= $this->tag_data[$i]['search_fields'];

			// -------------------------------------
			//  Assign Sites for Tag
			// -------------------------------------

			$this->_fetch_site_ids();

			// -------------------------------------
			//  Find Relationship Data in Weblog
			//  Entries and Search Results tags
			// -------------------------------------

			// NOTE: This needs to happen before extracting the
			// variables in the tag so it doesn't
			// get confused as to which entry the variables belong to.

			if (version_compare($this->aob->ee_version, '2.6.0', '<') AND
				(
					($lower_class_name == 'weblog' AND $method == 'entries') OR
					($lower_class_name == 'search' AND $method == 'search_results'))
				)
			{
				$this->tagdata = $this->assign_relationship_data($this->tagdata);
			}

			// --------------------------------------------
			//  Assign Variables for Tags - Improve!
			// --------------------------------------------

			$vars = ee()->functions->assign_variables(
				$this->tag_data[$i]['block']
			);

			// Related Fields should be a Single Variable for Tag Parsing
			foreach ($this->related_markers as $mkr)
			{
				$vars['var_single'][$mkr] = $mkr;
			}

			$this->var_single	= $vars['var_single'];
			$this->var_pair		= $vars['var_pair'];

			// --------------------------------------------
			//  Assign Conditional Variables for Non-Native Modules
			// --------------------------------------------

			if ( ! in_array($lower_class_name, $this->native_modules))
			{
				$this->var_cond = ee()->functions->assign_conditional_variables(
					$this->tag_data[$i]['block'],
					SLASH,
					LD,
					RD
				);
			}

			// --------------------------------------------
			//  Instantiate and Process
			// --------------------------------------------

			if (in_array($lower_class_name, $this->modules) AND ! isset($this->module_data[$class_name]))
			{
				$this->log_item("Problem Processing Module: Module Not Installed: ".$class_name);
			}
			else
			{
				$this->log_item(" -> Class Called: ".$class_name);

				$EE = new $class_name();
			}

			// ----------------------------------
			//  Send Error if Module Not Installed or Invalid Method
			// ----------------------------------

			if ((in_array($lower_class_name, $this->modules) AND ! isset($this->module_data[$class_name])) OR ! method_exists($EE, $method))
			{
				$this->log_item("Tag Not Processed: Method Non-Existent or Module Not Installed");

				if (ee()->config->item('debug') < 1)
				{
					return FALSE;
				}

				if ($this->tag_data[$i]['tagparts']['0'] == $this->tag_data[$i]['tagparts']['1'] AND ! isset($this->tag_data[$i]['tagparts']['2']))
				{
					unset($this->tag_data[$i]['tagparts']['1']);
				}

				$error  = lang('error_tag_module_processing');
				$error .= '<br /><br />';
				$error .= htmlspecialchars(LD);
				$error .= 'exp:'.implode(':', $this->tag_data[$i]['tagparts']);
				$error .= htmlspecialchars(RD);
				$error .= '<br /><br />';
				$error .= str_replace('%x', $lower_class_name, str_replace('%y', $method, lang('error_fix_module_processing')));

				ee()->output->fatal_error($error);
			}

			/*

			OK, lets grab the data returned from the class.

			First, however, lets determine if the tag has one or two segments.
			If it only has one, we don't want to call the constructor again since
			it was already called during instantiation.

			Note: If it only has one segment, only the object constructor will be called.
			Since constructors can't return a value just by initialializing the object
			the output of the class must be assigned to a variable called $this->return_data

			*/

			$this->log_item(" -> Method Called: ".$method);

			if (strtolower($class_name) == $method)
			{
				$return_data = (isset($EE->return_data)) ? $EE->return_data : '';
			}
			else
			{
				$return_data = $EE->$method();
			}

			// ----------------------------------
			//  404 Page Triggered, Cease All Processing of Tags From Now On
			// ----------------------------------

			if ($this->cease_processing === TRUE)
			{
				return;
			}

			$this->log_item(" -> Data Returned");

			// Write cache file if needed

			if ($this->tag_data[$i]['cache'] == 'EXPIRED')
			{
				$this->write_cache_file($this->tag_data[$i]['cfile'], $return_data);
			}

			// Replace the temporary markers we added earlier with the fully parsed data

			$this->replace_marker($i, $return_data);

			// Initialize data in case there are susequent loops

			$this->var_single		= array();
			$this->var_cond			= array();
			$this->var_pair			= array();
			$this->related_markers	= array();

			unset($lower_class_name, $return_data, $class_name, $method, $EE);
		}
	}
	// END class_handler


	// --------------------------------------------------------------------

	/**
	 *	Fetch Add-Ons for Instllation
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function fetch_addons()
	{
		$this->fetch_modules();
		$this->fetch_plugins();

		ee()->load->helper('directory');
		$ext_len = strlen(EXT);

		if (($map = directory_map(PATH_THIRD)) !== FALSE)
		{
			foreach ($map as $pkg_name => $files)
			{
				if ( ! is_array($files))
				{
					$files = array($files);
				}

				foreach ($files as $file)
				{
					if (is_array($file) OR substr($file, -$ext_len) != EXT)
					{
						continue;
					}

					// Module
					if (strncasecmp($file, 'mod.', 4) == 0 AND strlen($file) >= 9) // strlen('mod.a.php');
					{
						$file = substr($file, 4, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->modules[] = $file;
						}
					}

					// Plugin
					elseif (strncasecmp($file, 'pi.', 3) == 0 AND strlen($file) >= 8) // strlen('pi.a.php');
					{
						$file = substr($file, 3, -$ext_len);

						if ($file == $pkg_name)
						{
							$this->plugins[] = $file;
						}
					}
				}
			}
		}
	}
	/* END fetch_addons() */


	// --------------------------------------------------------------------

	/**
	 * Fetch Currently Available Modules
	 *
	 * @access	public
	 * @return	null
	 */

	public function fetch_modules()
	{
		if (count($this->modules) > 0)
		{
			return;
		}

		if ( isset(ee()->session->cache['modules']['morsel']['template']['fetch_modules']))
		{
			$this->modules = ee()->session->cache['modules']['morsel']['template']['fetch_modules'];
			return;
		}

		foreach(array(PATH_MOD) as $directory)
		{
			if ($fp = @opendir($directory))
			{
				while (false !== ($file = readdir($fp)))
				{
					if ( is_dir($directory.$file) AND ! preg_match("/[^a-z\_0-9]/", $file))
					{
						$this->modules[] = $file;
					}
				}

				closedir($fp);
			}
		}

		$this->modules = ee()->session->cache['modules']['morsel']['template']['fetch_modules']= array_unique($this->modules);
	}
	/* END fetch_modules() */


	// --------------------------------------------------------------------

	/**
	 * Fetch Currently Available Plugins
	 *
	 * @access	public
	 * @return	null
	 */

	public function fetch_plugins()
	{
		if (count($this->plugins) > 0)
		{
			return;
		}

		if ( isset(ee()->session->cache['modules']['morsel']['template']['fetch_plugins']))
		{
			$this->plugins = ee()->session->cache['modules']['morsel']['template']['fetch_plugins'];
			return;
		}

		foreach(array(PATH_PI) as $directory)
		{
			if ($fp = @opendir($directory))
			{
				while (false !== ($file = readdir($fp)))
				{
					if ( preg_match("/pi\.[a-z\_0-9]+?".preg_quote(EXT, '/')."$/", $file))
					{
						$this->plugins[] = substr($file, 3, -strlen(EXT));
					}
				}

				closedir($fp);
			}
		}

		$this->plugins = ee()->session->cache['modules']['morsel']['template']['fetch_plugins'] = array_unique($this->plugins);
	}
	/* END fetch_plugins() */

	// --------------------------------------------------------------------

	/**
	 * Nested Processing Abstraction
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */

	public function nested_processing($parse_string)
	{
		$TMPL2 = ee()->functions->clone_object($this);

		while (is_int(strpos($parse_string, LD.'exp:')))
		{
			unset($TMPL, $GLOBALS['TMPL']);

			ee()->TMPL = $GLOBALS['TMPL'] = $TMPL = new Template();
			ee()->TMPL->start_microtime = $this->start_microtime;
			ee()->TMPL->template = $parse_string;
			ee()->TMPL->tag_data	= array();
			ee()->TMPL->var_single = array();
			ee()->TMPL->var_cond	= array();
			ee()->TMPL->var_pair	= array();
			ee()->TMPL->plugins = $TMPL2->plugins;
			ee()->TMPL->modules = $TMPL2->modules;

			ee()->TMPL->parse_tags();
			ee()->TMPL->process_tags();

			ee()->TMPL->loop_count = 0;
			$parse_string = ee()->TMPL->template;
			$TMPL2->log = array_merge($TMPL2->log, ee()->TMPL->log);
		}

		foreach (get_object_vars($TMPL2) as $key => $value)
		{
			$this->$key = $value;
		}

		unset($TMPL2);

		ee()->TMPL = $GLOBALS['TMPL'] = $TMPL = $this;

		return $parse_string;
	}
	/* END nested_processing() */
}
// END parser
