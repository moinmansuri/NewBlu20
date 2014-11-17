<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Field Type Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_fieldtype_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_fieldtype_model extends Freeform_Model
{
	//public $before_get 		= array('prep_get');
	public $before_insert 	= array('prep_insert');
	public $before_update 	= array('prep_update');

	// --------------------------------------------------------------------

	/**
	 * prep insert Fieldtype
	 *
	 * @access	public
	 * @param 	array 	insert data
	 * @return	int 	adjusted insert data
	 */

	public function prep_insert ($data)
	{
		$defaults = array(
			'settings' 		=> array(),
			'default_field' => FALSE
		);

		$data = array_merge($defaults, $data);

		if (isset($data['settings']))
		{
			if ( is_array($data['settings']))
			{
				$data['settings'] = json_encode($data['settings']);
			}
		}

		$data = array_merge(array(
			'fieldtype_name'	=> trim(strtolower($data['fieldtype_name'])),
			'default_field'		=> ($data['default_field'] ? 'y' : 'n'),
			'version'			=> $data['version'],
			'settings'			=> $data['settings']
		));

		return $data;
	}
	//END insert


	// --------------------------------------------------------------------

	/**
	 * Update
	 *
	 * @access	public
	 * @param 	string 	name of fieldtype to be updated
	 * @param 	string 	version to update to
	 * @param 	array 	global settings (future update)
	 * @return	int 	affected ID
	 */

	public function prep_update ($data)
	{
		if (isset($data['settings']))
		{
			if ( is_array($data['settings']))
			{
				$data['settings'] = json_encode($data['settings']);
			}
		}

		return $data;
	}
	//END update


	// --------------------------------------------------------------------

	/**
	 * Freeform Fieldtype Installed
	 *
	 * returns all of the installed fieldtypes
	 *
	 * @access	public
	 * @param 	boolean use cache?
	 * @return	array 	returns an array of installed fieldtypes and versions
	 */

	public function installed_fieldtypes ()
	{
		$cache = $this->cacher(func_get_args(), __FUNCTION__);
		if ($cache->is_set()){ $this->deisolate(); return $cache->get();}

		$data = array();

		$this->order_by('fieldtype_name', 'asc');

		$data = $this->key('fieldtype_name')->get();

		$this->deisolate();

		return $cache->set($data);
	}
	//END installed_fieldtypes
}
//END Freeform_form_model