<?php
    $this->EE =& get_instance();
	$this->EE->cp->load_package_css('settings');
	$this->EE->cp->load_package_js('settings');
	$this->EE->load->helper('form');
	$this->EE->load->library('table');
	
	if(!empty($auth_code_error)) : ?>
		<p class="ga_error"><?= $auth_code_error ?></p>
	<?php endif;
	
	if(!isset($current['refresh_token'])) : ?>
		
		<p class="ga_intro"><?= $this->EE->lang->line('cp_analytics_instructions_1') ?> <a href="<?= $oauth_url ?>"><?= $this->EE->lang->line('cp_analytics_instructions_2') ?></a></p>
		
		<p><?= $this->EE->lang->line('cp_analytics_instructions_3') ?></p>
		
		<?= form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=cp_analytics', array('id' => 'auth_code_form')); ?>
		<p>
			<?= form_label($this->EE->lang->line('cp_analytics_auth_code'), 'auth_code'); ?>
			<?= form_input('auth_code', '', 'id="auth_code"'); ?>
		</p>
		<?= form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('cp_analytics_save_code'), 'class' => 'submit')); ?>
		<?= form_close(); ?>
		
	<?php else :

		if(!empty($profile_error)) : ?>
			<p class="ga_error"><?= $profile_error ?></p>
		<?php endif;

		echo form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=cp_analytics'.AMP.'method=save');
		
		$this->EE->table->set_template($cp_pad_table_template);
		
		$this->EE->table->set_heading(
			array('data'=> NBS), array('data'=> NBS)
		);
		
		$this->EE->table->add_row(
			$this->EE->lang->line('cp_analytics_authentication'),
			$this->EE->lang->line('cp_analytics_authenticated').
				' &nbsp; (<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=cp_analytics'.AMP.'method=reset">'.
				$this->EE->lang->line('cp_analytics_reset').'</a>)'
		);		
	
		if(isset($profiles))
		{
			$this->EE->table->add_row(
				form_label($this->EE->lang->line('cp_analytics_profile'), 'profile'),
				form_dropdown('profile', $profiles['profiles'], (isset($current['profile']['id'])) ? $current['profile']['id'] : '', 'id="profile"')
			);		
		}
		else
		{
			$this->EE->table->add_row(
				$this->EE->lang->line('cp_analytics_profile'),
				'<span class="failure">'.$this->EE->lang->line('cp_analytics_no_accounts').'</span>'
			);			
		}
		
		foreach($radio_settings as $setting)
		{
			$this->EE->table->add_row(
				form_label($this->EE->lang->line('cp_analytics_'.$setting)),
				form_label(form_radio($setting, 'y', (isset($current['settings'][$setting]) && $current['settings'][$setting] == 'y') ? TRUE : FALSE).NBS.$this->EE->lang->line('yes')).NBS.NBS.form_label(form_radio($setting, 'n', ( (isset($current['settings'][$setting]) && $current['settings'][$setting] == 'n') || !isset($current['settings'][$setting]) ) ? TRUE : FALSE).NBS.$this->EE->lang->line('no'))
			);
		}

		foreach($text_settings as $setting)
		{
			$extra = 'id="'.$setting.'"';
			if($v = $this->EE->config->item('cp_analytics_'.$setting))
			{
				$extra = ' disabled="disabled"';
			}
			elseif(isset($current['settings'][$setting]) && !empty($current['settings'][$setting]))
			{
				$v = $current['settings'][$setting];
			}
			else
			{
				$v = ($setting == 'cache_path') ?
					rtrim(PATH_THEMES, '/').'/third_party/cp_analytics/' : 
					rtrim($this->EE->config->item('theme_folder_url'), '/').'/third_party/cp_analytics/';
			}
			$this->EE->table->add_row(
				form_label($this->EE->lang->line('cp_analytics_'.$setting), $setting),
				form_input($setting, $v, $extra)
			);
		}
																			
		echo $this->EE->table->generate();
		
		if(isset($profiles['segments']))
		{
			foreach($profiles['segments'] as $k => $v)
			{
				echo form_hidden('profile_segment_'.$k, $v);
			}
		}
		if(isset($profiles['names']))
		{
			foreach($profiles['names'] as $k => $v)
			{
				echo form_hidden('profile_name_'.$k, $v);
			}
		}
		
		echo form_submit(array('name' => 'submit', 'value' => $this->EE->lang->line('cp_analytics_save_settings'), 'class' => 'submit'));
		echo form_close();
		
	endif;
?>