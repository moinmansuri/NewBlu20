

jQuery(document).ready(function() {	
								
/*
	var id = $('a[name=modal1]').attr('href');
	
		//Get the screen height and width
		var maskHeight = $(window).height();
		var maskWidth = $(window).width();
	
		//Set heigth and width to mask to fill up the whole screen
		$('#mask').css({'width':maskWidth,'height':maskHeight});
		
		//transition effect		
		$('#mask').fadeIn(1000);	
		$('#mask').fadeTo("slow",0.8);	
	
		//Get the window height and width
		var winH = $(window).height();
		var winW = $(window).width();
              
		//Set the popup window to center
		$(id).css('top',  winH/2-$(id).height()/2);
		$(id).css('left', winW/2-$(id).width()/2);
	
		//transition effect
		$(id).fadeIn(2000); 
	
*/		
		
	//select all the a tag with name equal to modal
	jQuery('a[name=modal]').click(function(e) {
		//Cancel the link behavior
		e.preventDefault();
		
		//Get the A tag
		var id = jQuery(this).attr('href');
	
		//Get the screen height and width
		var maskHeight = jQuery(window).height();
		var maskWidth = jQuery(window).width();
	
		//Set heigth and width to mask to fill up the whole screen
		jQuery('#mask').css({'width':maskWidth,'height':maskHeight});
		
		//transition effect		
		jQuery('#mask').fadeIn(1000);	
		jQuery('#mask').fadeTo("slow",0.87);	
	
		//Get the window height and width
		var winH = jQuery(window).height();
		var winW = jQuery(window).width();
		//Set the popup window to center
		if(id == '#dialog2'){
			jQuery(id).css('top', winH/2-jQuery(id).height()/2);
			jQuery(id).css('left', winW/2-jQuery(id).width()/2);
			
			}else{
				
			
		jQuery(id).css('top',  winH/2-jQuery(id).height()/2);
		jQuery(id).css('left', winW/2-jQuery(id).width()/2);
	}
		//transition effect
		jQuery(id).fadeIn(2000); 
	
	});
	
	jQuery('.menu li.button a[name=modal]').click(function(e) {
		//Cancel the link behavior
		e.preventDefault();
		
		//Get the A tag
		var id = jQuery(this).attr('href');
	
		//Get the screen height and width
		var maskHeight = jQuery(window).height();
		var maskWidth = jQuery(window).width();
	
		//Set heigth and width to mask to fill up the whole screen
		jQuery('#mask.white').css({'width':maskWidth,'height':maskHeight});
		
		//transition effect		
		jQuery('#mask.white').fadeIn(1000);	
		jQuery('#mask.white').fadeTo("slow",0.87);	
	
		//Get the window height and width
		var winH = jQuery(window).height();
		var winW = jQuery(window).width();
		//Set the popup window to center
		if(id == '#dialog2'){
			jQuery(id).css('top', winH/2-jQuery(id).height()/2);
			jQuery(id).css('left', winW/2-jQuery(id).width()/2);
			
			}else{
				
			
		jQuery(id).css('top',  winH/2-jQuery(id).height()/2);
		jQuery(id).css('left', winW/2-jQuery(id).width()/2);
	}
		//transition effect
		jQuery(id).fadeIn(2000); 
	
	});
	
	//if close button is clicked
	jQuery('.window .close').click(function (e) {
		//Cancel the link behavior
		e.preventDefault();
		
		jQuery('#mask').hide();
		jQuery('#mask.white').hide();
		jQuery('.window').hide();
	});	
	
	//if mask is clicked
	jQuery('#dialog1').click(function (e) {
		//e.preventDefault();
		
		//jQuery('#mask').hide();
		//jQuery('#dialog1').hide();
		//jQuery('.window').hide();
	});
	
	jQuery('#dialog2').click(function (e) {
		e.preventDefault();
		
		//jQuery('#mask').hide();
		//jQuery('#dialog1').hide();
		//jQuery('.window').hide();
	});			
	
});