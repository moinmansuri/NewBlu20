/* Remove text from input types and textarea on Mouse click */
function myFocus(element) 
{
	if (element.value == element.defaultValue) {
		element.value = '';
	}
}
function myBlur(element) {
	if (element.value == '') {
		element.value = element.defaultValue;
	}
}

$(document).ready(function(e) {
    $('#checkpoints').click(function(e) {
        $('.loginform').hide('fast');
		$('.checkpointsform').show('fast');
    });
	
	$('#login').click(function(e) {
        $('.loginform').show('fast');
		$('.checkpointsform').hide('fast');
    });
});
