<?php

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

class Wgt_cp_analytics_chart
{
	var $title;
	var $settings;
	var $wclass;
	
	public function __construct()
	{	
		$this->EE =& get_instance();
		
		$this->settings = array(
			'title' => lang('wgt_cp_analytics_chart_widget_title'),
			'days' => '30',
			'labels' => 'n'
		);
		$this->title  	= lang('wgt_cp_analytics_chart_widget_title');
		$this->wclass 	= 'cp_analytics_chart_widget';			
	}
	

	public function permissions()
	{
		$groups = $this->EE->db->query("SELECT member_groups FROM exp_accessories WHERE class = 'Cp_analytics_acc' LIMIT 1");
		if($groups->num_rows() > 0)
		{
			$groups = explode('|', $groups->row('member_groups'));
			if(in_array($this->EE->session->userdata('group_id'), $groups))
			{
				return true;
			}
		}
		return false;
	}


	public function index($settings = NULL)
	{
		if(!class_exists('Cp_analytics_acc'))
		{
			require(PATH_THIRD.'cp_analytics/acc.cp_analytics.php');
		}
		$acc = new Cp_analytics_acc();
		$vars = $acc->widget_chart_vars($settings->days);
		$vars['show_every'] = ($settings->days == '30') ? '6' : '4';
		$vars['text_pos'] = ($settings->labels == 'y') ? 'in' : 'none';
		$this->title = $settings->title;
		return $this->EE->load->view('widget_chart', $vars, TRUE);
	}
	
	
	public function settings_form($settings)
	{
		return form_open('', array('class' => 'dashForm')).
			
			'<p>'.
				form_label(lang('wgt_cp_analytics_chart_title')).
				form_input('title', $settings->title)
			.'</p>

			<p>'.
				form_label(lang('wgt_cp_analytics_chart_days')).
				form_radio('days', '30', ($settings->days == '30') ? TRUE : FALSE).NBS.'30 days'.NBS.NBS.
				form_radio('days', '14', ($settings->days == '14') ? TRUE : FALSE).NBS.'14 days'
			.'</p>
			
			<p>'.
				form_label(lang('wgt_cp_analytics_chart_labels')).
				form_radio('labels', 'y', ($settings->labels == 'y') ? TRUE : FALSE).NBS.'Yes'.NBS.NBS.
				form_radio('labels', 'n', ($settings->labels == 'n') ? TRUE : FALSE).NBS.'No'
			.'</p>
			
			<p>'.form_submit('save', lang('save')).'</p>
			
			'.form_close();
	}

}