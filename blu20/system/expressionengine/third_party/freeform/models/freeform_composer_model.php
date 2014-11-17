<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Freeform - Composer Model
 *
 * @package		Solspace:Freeform
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2013, Solspace, Inc.
 * @link		http://solspace.com/docs/freeform
 * @license		http://www.solspace.com/license_agreement
 * @filesource	freeform/models/freeform_composer_model.php
 */

if ( ! class_exists('Freeform_Model'))
{
	require_once 'freeform_model.php';
}

class Freeform_composer_model extends Freeform_Model 
{
	//intentional because we will need to set every incoming table
	public	$_table				= 'freeform_composer_layouts';
	public	$id					= 'composer_id';

	public	$before_insert 		= array('before_insert_add');
	public	$before_update 		= array('before_update_add');


	// --------------------------------------------------------------------
	
	/**
	 * Cleans up day old previews
	 * 
	 * @access	public
	 * @return	object	$this
	 */
	
	public function clean ()
	{
		$this->delete(array(
			'preview'		=> 'y',
			'entry_date <'	=> $this->localize->now - 86400
		));

		return $this;
	}
	//END clean


	// --------------------------------------------------------------------
	
	/**
	 * Before insert add
	 * 
	 * adds site id and entry date to insert data
	 * 
	 * @access protected
	 * @param  array $data array of insert data
	 * @return array       affected data
	 */
	
	protected function before_insert_add ($data)
	{
		if ( ! isset($data['entry_date']))
		{
			$data['entry_date'] = $this->localize->now;
		}

		if ( ! isset($data['site_id']))
		{
			$data['site_id'] = $this->config->item('site_id');
		}

		return $data;
	}
	//END before_insert_add


	// --------------------------------------------------------------------
	
	/**
	 * Before update add
	 * 
	 * adds site id and edit date to update data
	 * 
	 * @access protected
	 * @param  array $data array of update data
	 * @return array       affected data
	 */
	
	protected function before_update_add ($data)
	{
		if ( ! isset($data['edit_date']))
		{
			$data['edit_date'] = $this->localize->now;
		}

		return $data;
	}
	//END before_update_add
}
//END Freeform_composer_model