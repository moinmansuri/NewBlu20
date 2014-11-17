<?php $this->EE =& get_instance(); ?>

<table class="analytics-panel analytics-reports" cellspacing="0">
	<tr>
		<th class="top-left"><?= $this->EE->lang->line('cp_analytics_source')?></th>
		<th class="top-right"><?= $this->EE->lang->line('cp_analytics_visits')?></th>			
	</tr>
<?php foreach($lastmonth['referrers'] as $result): ?>
	<tr>
		<td class="analytics-top-referrer-row"><?=$result['title']?></td>
		<td class="analytics-count"><?=$result['count']?></td>
	</tr>
<?php endforeach; ?>
	<tr>
		<td class="analytics-report-link bottom-left bottom-right cap" colspan="2"><a href="https://www.google.com/analytics/web/#report/trafficsources-overview/<?=$profile['segment']?>" target="_blank"><?=$this->EE->lang->line('cp_analytics_more')?></a></td>
	</tr>
</table>	