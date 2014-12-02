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
    $('#footer .ss-icon').click(function(e) {
		$("#footerform").show('slide', {direction: 'down'}, 300);
		$('#footer .form').slideToggle('slow');
		$("#header").css("z-index","0");
    });
	
	$('#footerform .ss-icon').click(function(e) {
		$("#footerform").hide('slide', {direction: 'down'}, 300);
		$('#footer .form').slideToggle('slow');
		$("#header").css("z-index","99999");
    });	
	
	$('#menu .icon-menu').click(function(e) {
		$('.menu').show('slide', {direction: 'right'}, 300);
    });
			
	$('.menu .icon-close').click(function(e) {
		$('.menu').hide('slide', {direction: 'right'}, 300);
    });	
			
	$('.menu .button.slideinleft').click(function(e) {
		$('.menu').hide('slide', {direction: 'right'}, 300);
    });	
	
	$('#capabilities').click(function(e) {
		$(this).addClass("active");
		$('#our-process').removeClass("active");
        $('#capabilities-items').show("fast");
		$('#process-items').hide("fast");
	});	
	
	$('#our-process').click(function(e) {
		$(this).addClass("active");
		$('#capabilities').removeClass("active");
        $('#capabilities-items').hide("fast");
		$('#process-items').show("fast");
	});
	
	/* PROCESS DETAILS BOX */
	$('#process-items .discovery').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#discovery-item.detailsbox").show("fast");		
    });
	
	$('#process-items .research').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#research-item.detailsbox").show("fast");		
    });
	
	$('#process-items .strategy').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#strategy-item.detailsbox").show("fast");		
    });
	
	$('#process-items .prototype').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#prototype-item.detailsbox").show("fast");		
    });
	
	$('#process-items .iterate').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#iterate-item.detailsbox").show("fast");		
    });
	
	$('#process-items .launch').click(function(e) {
        $('.detailsbox').hide("fast");
		$("#launch-item.detailsbox").show("fast");		
    });
	
	$('.detailsbox .close').click(function(e) {
        $('.detailsbox').hide("fast");
    });
});

$(document).ready(function(){	
	$('#nav li a').click(function(){
		var el = $(this).attr('href');
		var elWrapped = $(el);
		scrollToDiv(elWrapped,40);
		return false;
	});
	
	function scrollToDiv(element,navheight){
		var offset = element.offset();
		var offsetTop = offset.top;
		var totalScroll = offsetTop - $("header").outerHeight() + "px";
		$('body,html').animate({scrollTop: totalScroll}, 700);
	}
	
	var openNavigation = false;	
	$('#menu .icon-menu').click(function(e) {
		if(!openNavigation){	
			$('.menu').show('slide', {direction: 'right'}, 300);
			$('#menu .icon-menu').addClass('close');
			openNavigation = true;
		}
		else {
			$('.menu').hide('slide', {direction: 'right'}, 300);
			$('#menu .icon-menu').removeClass('close');
			openNavigation = false;	
		}
	});
	
	
	$('.addDeliverables').click(function(e) {
        $('.extradeliverables').append('<input name="date_allow_empty" type="text" id="deliverable-4" value="More Deliverable" class="deliverable ss-icon date demo_allow_empty" />');
	});
	
});

$(window).scroll(function(){
	var windscroll = $(window).scrollTop();
    if (windscroll >= 530) {
		$('#header.innerpage').addClass("fixed");
	}
	
	else {		
		$('#header.innerpage').removeClass("fixed");
	}
});


