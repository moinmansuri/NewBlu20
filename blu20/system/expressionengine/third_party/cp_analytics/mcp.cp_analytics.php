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

class Cp_analytics_mcp {

	var $radio_settings = '';
	var $text_settings = '';
	var $debug = FALSE;
	
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(PATH_THIRD.PACKAGE_NAME);
		$this->EE->load->model(PACKAGE_NAME.'_model');
		
		$this->radio_settings = array(
			'homepage_chart',
			'homepage_chart_labels',
			'cache_sparklines'
		);

		$this->text_settings = array(
			'cache_path',
			'cache_url'
		);
		
		$nav = array(
			'cp_analytics_settings' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME,
			'cp_analytics_clear_cache' => BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME.AMP.'method=clear_cache'
		);
		if($this->debug == TRUE)
		{
			$nav['cp_analytics_debug'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME.AMP.'method=debug';
		}
		$this->EE->cp->set_right_nav($nav);
		
		
	}

	function index()
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cp_analytics_settings'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME, $this->EE->lang->line('cp_analytics_module_name'));	

		$vars = array();

		if($code = $this->EE->input->get_post('auth_code'))
		{
			$token = $this->EE->cp_analytics_model->exchange_authorization_for_token($code);
			if(!empty($token['error']))
			{
				$vars['auth_code_error'] = $this->EE->lang->line($token['error']);
			}
		}
				
		$vars['oauth_url'] = $this->EE->cp_analytics_model->get_oauth_url();
		$vars['current'] = array(
			'profile' => $this->EE->cp_analytics_model->get_profile(),
			'settings' => $this->EE->cp_analytics_model->get_settings(),
			'refresh_token' => $this->EE->cp_analytics_model->get_refresh_token()
		);
		
		$profiles = $this->EE->cp_analytics_model->get_profile_list();
		if(!empty($profiles['error']))
		{
			$vars['profile_error'] = $this->EE->lang->line($profiles['error']);
		}
		else
		{
			$vars['profiles'] = $profiles;
		}

		$vars['radio_settings'] = $this->radio_settings;
		$vars['text_settings'] = $this->text_settings;
								
		return $this->EE->load->view('settings', $vars, TRUE);
				
	}
	

	function clear_cache()
	{
		$data = array(
			'hourly_cache' => '',
			'daily_cache' => ''
		);
		
		$this->EE->cp_analytics_model->save_settings($data);
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('cp_analytics_cache_cleared'));			
		$this->_redirect();
	}
	
	
	function debug()
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('cp_analytics_debug'));
		$this->EE->cp->set_breadcrumb(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME, $this->EE->lang->line('cp_analytics_module_name'));
		
		$vars = array(
			'current_date' => $this->EE->cp_analytics_model->current_date,
			'current_time' => $this->EE->cp_analytics_model->current_time,
			'yesterday' => $this->EE->cp_analytics_model->yesterday,
			'last_month' => $this->EE->cp_analytics_model->last_month
		);
		return $this->EE->load->view('debug', $vars, TRUE);
	}
	
		
	function save()
	{
		$data = array();
		$settings = array_merge($this->radio_settings, $this->text_settings);
		foreach($settings as $v)
		{
			if($setting = $this->EE->input->post($v))
			{
				$data['settings'][$v] = $setting;
			}
		}
		if($profile = $this->EE->input->post('profile'))
		{
			$data['profile']['id'] = $profile;
			$data['profile']['name'] = $this->EE->input->post('profile_name_'.$profile);
			$data['profile']['segment'] = $this->EE->input->post('profile_segment_'.$profile);
		}
		
		$this->EE->cp_analytics_model->save_settings($data);
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));			
		$this->_redirect();
	}
	
	
	function reset()
	{
		$this->EE->cp_analytics_model->reset_authentication();
		$this->_redirect();
	}
	
	
	function _redirect()
	{
		$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME);
		exit();
	}

}