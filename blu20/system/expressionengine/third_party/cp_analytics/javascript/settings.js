$(document).ready(function()
{
	$(".ga_intro a").click(function()
	{
		window.open($(this).attr('href'), 'google_oauth', 'width=700,height=500,location=no,toolbar=no,scrollbars=no');
		return false;
	});
});