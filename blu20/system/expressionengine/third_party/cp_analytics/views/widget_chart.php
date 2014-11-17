<?php $this->EE =& get_instance(); ?>

<div id="cp_analytics_chart_container">
	<div id="cp_analytics_chart">
		<p><a href="#" onclick="drawChart();"><?=lang('wgt_cp_analytics_chart_reload')?></a></p>
	</div>
</div>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);
var delay = (function(){
	var timer = 0;
	return function(callback, ms)
	{
    	clearTimeout(timer);
    	timer = setTimeout(callback, ms);
	};
})();
$(window).resize( function(){ delay(function(){ drawChart(); }, 500); } );
function drawChart()
{
	frame_width = $('#cp_analytics_chart').innerWidth();
	chart_width = frame_width - 20;	
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'Date');
	data.addColumn('number', 'Visits');
	data.addColumn('number', 'Pageviews');
	data.addRows([
	  <?=$chart;?>
	]);	
	var chart = new google.visualization.AreaChart(document.getElementById('cp_analytics_chart'));
	
	var bg = '#ECF1F4';
	var corporate_blue = '#005EB0';
	var nerdery_blue = '#2D98DB';
	var sassy_green = '#80aa4f';
	var pink = '#D91350';
	var lightGrey = '#96A8B4';
	var medGrey = '#3E4C54';
	var darkGrey = '#27343C';
		
	chart.draw(data, {
		backgroundColor: bg,
		chartArea: {height: 150, left: 10, top: 10, width: chart_width},
		colors: [<?=$accent?>, lightGrey],
		hAxis: {textPosition: '<?=$text_pos;?>', textStyle: {color: medGrey, fontSize: 11}, showTextEvery: <?=$show_every;?>},
		height: 160,
		isStacked: false,
		legend: 'none',
		lineWidth: 2,
		pointSize: 4,
		tooltipTextStyle: {color: medGrey, fontSize: 11},
		vAxis: {baselineColor: lightGrey, gridlineColor: bg, textPosition: '<?=$text_pos;?>', textStyle: {color: medGrey, fontSize: 11}, viewWindowMode: 'pretty'},
		width: frame_width
	});
}
</script>