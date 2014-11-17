<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
    This file is part of CP Analytics add-on for ExpressionEngine.

    CP Analytics is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CP Analytics is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    Read the terms of the GNU General Public License
    at <http://www.gnu.org/licenses/>.
    
    Copyright 2012 Derek Hogue
*/

require(PATH_THIRD.'cp_analytics/config.php');

class Cp_analytics_upd {
	
	var $version = CP_ANALYTICS_VERSION;
	var $module_name = 'Cp_analytics';


	public function __construct()
	{
		$this->EE =& get_instance();
	}
	

	public function install()
	{
		$mod_data = array(
			'module_name'			=> $this->module_name,
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'y',
			'has_publish_fields'	=> 'n'
		);
		
		$this->EE->db->insert('modules', $mod_data);

		$this->EE->load->dbforge();
				
		$this->EE->dbforge->add_field(array(
			'site_id' => array('type' => 'int', 'constraint' => '5', 'unsigned' => TRUE, 'null' => FALSE),
			'refresh_token' => array('type' => 'varchar', 'constraint' => '255'),
			'profile' => array('type' => 'text'),
			'settings' => array('type' => 'text'),
			'hourly_cache' => array('type' => 'text'),
			'daily_cache' => array('type' => 'text')
		));
		$this->EE->dbforge->add_key(array('site_id'), TRUE);
		$this->EE->dbforge->create_table('cp_analytics');		
		
		return TRUE;
	}


	public function uninstall()
	{
		$mod_id = $this->EE->db->select('module_id')->get_where('modules', array('module_name'	=> $this->module_name))->row('module_id');
		$this->EE->db->where('module_id', $mod_id)->delete('module_member_groups');
		$this->EE->db->where('module_name', $this->module_name)->delete('modules');

		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table('cp_analytics');
		
		return TRUE;
	}
	

	public function update($current = '')
	{
		return TRUE;
	}
	
}