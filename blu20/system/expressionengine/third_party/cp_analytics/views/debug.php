<?php
    $this->EE =& get_instance();
	$this->EE->load->library('table');
	$this->EE->table->set_template($cp_pad_table_template);
	
	$this->EE->table->set_heading(
		$this->EE->lang->line('cp_analytics_variable'),
		$this->EE->lang->line('cp_analytics_value')
	);
	
	$this->EE->table->add_row(
		$this->EE->lang->line('cp_analytics_current_date'),
		$current_date
	);		

	$this->EE->table->add_row(
		$this->EE->lang->line('cp_analytics_current_time'),
		$current_time
	);
	
	$this->EE->table->add_row(
		$this->EE->lang->line('cp_analytics_yesterday'),
		$yesterday
	);
	
	$this->EE->table->add_row(
		$this->EE->lang->line('cp_analytics_last_month'),
		$last_month
	);
																			
	echo $this->EE->table->generate();
?>