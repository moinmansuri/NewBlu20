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

class Cp_analytics_acc {

	var $name			= 'CP Analytics';
	var $id				= 'cp_analytics_acc';
	var $version		= CP_ANALYTICS_VERSION;
	var $description	= 'Display your Google Analytics stats in the EE control panel.';
	var $sections		= array();
	var $theme			= 'default';


	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->add_package_path(PATH_THIRD.PACKAGE_NAME);
		$this->EE->lang->loadfile(PACKAGE_NAME);
		$this->EE->load->model(PACKAGE_NAME.'_model');

		$this->theme = $this->EE->session->userdata['cp_theme'];
		
		// Always load the default CSS
		$this->EE->cp->load_package_css('default');
				
		switch($this->theme)
		{
			// Don't need to add anything if we're using the default theme
			case 'default':
				break;
			// Add tweaks for Corporate theme
			case 'corporate':
				$this->EE->cp->load_package_css('corporate');
				break;
			// Allow overrides from custom themes as well
			default:
				if(file_exists(PATH_THIRD.PACKAGE_NAME.'/css/'.$this->theme.'.css'))
				{
					$this->EE->cp->load_package_css($this->theme);
				}
		}
	}


	function set_sections()
	{
		$this->name = $this->EE->lang->line('cp_analytics_accessory_tab');

		$profile = $this->EE->cp_analytics_model->get_profile();
		$refresh_token = $this->EE->cp_analytics_model->get_refresh_token();
		$settings = $this->EE->cp_analytics_model->get_settings();
		
		if(empty($profile) || empty($refresh_token))
		{
			$this->sections[$this->EE->lang->line('cp_analytics_not_configured')] = 
				'<p><a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module='.PACKAGE_NAME.'">'.
				$this->EE->lang->line('cp_analytics_not_configured_message').
				'</a></p>';
		}
		else
		{
			$today = $this->EE->cp_analytics_model->get_hourly_stats();
			$today['hourly_updated'] = $this->EE->localize->decode_date('%g:%i%a', $today['cache_time'], FALSE);
			
			$daily = $this->EE->cp_analytics_model->get_daily_stats();
			$daily['daily_updated'] = $daily['cache_date'];
			$daily['profile'] = $profile;
			
			$recently = array_merge($today, $daily);
						
			if($this->EE->input->get('D') == 'cp'
					&& $this->EE->input->get('C') == 'homepage' 
					&& isset($settings['homepage_chart'])
					&& $settings['homepage_chart'] == 'y'
					&& !empty($daily['lastmonth_chart']))
				{
					$this->homepage_chart($daily['lastmonth_chart'], $settings['homepage_chart_labels']);
				}

			$this->sections[$this->EE->lang->line('cp_analytics_recently')] = $this->EE->load->view('recent', $recently, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_lastmonth')] = $this->EE->load->view('lastmonth', $daily, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_top_content')] = $this->EE->load->view('content', $daily, TRUE);
			$this->sections[$this->EE->lang->line('cp_analytics_sources')] = $this->EE->load->view('referrers', $daily, TRUE);
		}		
	}
	
	
	function homepage_chart($data, $labels)
	{
		$vars = array(
			'chart' => $data,
			'text_pos' => ($labels == 'y') ? 'in' : 'none'
		);
		$vars['accent'] = $this->_theme_accent();
		$this->EE->cp->add_to_foot($this->EE->load->view('homepage_chart', $vars, TRUE));
	}
	
	
	function widget_chart_vars($days)
	{
		$data = $this->EE->cp_analytics_model->get_daily_stats();
		
		$vars = array();
		$vars['chart'] = ($days == 14) ? $data['14day_chart'] : $data['lastmonth_chart'];
		$vars['accent'] = $this->_theme_accent();
		return $vars;
	}
	
	
	function _theme_accent()
	{
		switch($this->theme)
		{
			case 'corporate' :
				$r = 'corporate_blue';
				break;
			case 'nerdery' : 
				$r = 'nerdery_blue';
				break;
			case 'sassy_cp' : 
				$r = 'sassy_green';
				break;
			default :
				$r = 'pink';
		}
		return $r;
	}

}