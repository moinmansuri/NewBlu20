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
	$('html').click(function(e) {
		$('#searchform').hide('fast');
		$('#loginform').hide("fast");
		$('#searchfindbreeds').hide("fast");
		$('.characteristics').hide("fast");
		$('#characteristics .overlay').hide("fast");
	});
	
    $('#search').click(function(e) {
		e.stopPropagation();
        $('#searchform').slideToggle("fast");
		$('#searchform').click(function(e) {
			e.stopPropagation();            
        });
    });
	
	/* Login Form */	
    $('#login').click(function(e) {
		e.stopPropagation();
        $('#loginform').slideToggle("fast");
		$('#loginform').click(function(e) {
			e.stopPropagation();            
        });
    });
	
	/* FULL SEARCH */
	$('#findbreeds').click(function(e) {
		e.stopPropagation();
        $('#searchfindbreeds').slideToggle("fast");
		$('#searchfindbreeds').click(function(e) {
			e.stopPropagation();            
        });
    });
	
	$('#puppy-size-search .next-button').click(function(e) {
		$('#searchfindbreeds ul.tabs li').removeClass("active");
		$('#searchfindbreeds ul.tabs li#puppy-size').addClass("finished");
		$('li#shedding').addClass("active");
        $('#puppy-size-search').slideToggle("fast");
        $('#shedding-search').slideToggle("fast");
    });
	
	$('#shedding-search .next-button').click(function(e) {
		$('#searchfindbreeds ul.tabs li').removeClass("active");
		$('#searchfindbreeds ul.tabs li#shedding').addClass("finished");
		$('li#good-with-dogs').addClass("active");
        $('#shedding-search').slideToggle("fast");
        $('#good-with-dogs-search').slideToggle("fast");
    });
	
	$('#good-with-dogs-search .next-button').click(function(e) {
		$('#searchfindbreeds ul.tabs li').removeClass("active");
		$('#searchfindbreeds ul.tabs li#good-with-dogs').addClass("finished");
		$('li#good-with-kids').addClass("active");
        $('#good-with-dogs-search').slideToggle("fast");
        $('#good-with-kids-search').slideToggle("fast");
    });
	
	$('#good-with-kids-search .next-button').click(function(e) {
		$('#searchfindbreeds ul.tabs li').removeClass("active");
		$('#searchfindbreeds ul.tabs li#good-with-kids').addClass("finished");
		$('li#protective').addClass("active");
        $('#good-with-kids-search').slideToggle("fast");
        $('#protective-search').slideToggle("fast");
    });
	
	/*PUPPY FILTERING */
	$('#featuredpuppies .filter .female').click(function(e) {
		$('#featuredpuppies ul li.male').fadeOut(500, function () {
			$('#featuredpuppies ul li.female').fadeIn(500);
		 });
    });
	
	$('#featuredpuppies .filter .male').click(function(e) {        
		$('#featuredpuppies ul li.female').fadeOut(500, function () {
			$('#featuredpuppies ul li.male').fadeIn(500);
		 });
    });
	
	$('#featuredpuppies .filter .all').click(function(e) {        
		$('#featuredpuppies ul li.female').fadeOut(500, function () {
			$('#featuredpuppies ul li.male').fadeIn(500);
			$('#featuredpuppies ul li.female').fadeIn(500);
		 });
    });
	
	/* PUPPY DETAILS */
	$('#puppy1.puppy #info').click(function(e) {
		$('#puppy1.puppy #infodetails').slideToggle("fast");
		$('#puppy1.puppy #puppy-detailsmore').hide("fast");
		$('#puppy1.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy1.puppy #puppy-details').click(function(e) {
		e.preventDefault();
		$('#puppy1.puppy #puppy-detailsmore').slideToggle("fast");
		$('#puppy1.puppy #infodetails').hide("fast");
		$('#puppy1.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy1.puppy #photos').click(function(e) {
		if ($('#puppy1.puppy #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy1.puppy #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy1.puppy #puppyphotos').css('visibility','hidden');
		}
		$('#puppy1.puppy #puppy-detailsmore').hide("fast");
		$('#puppy1.puppy #infodetails').hide("fast");
	});	
	
	$('#puppy2.puppy #info').click(function(e) {
		$('#puppy2.puppy #infodetails').slideToggle("fast");
		$('#puppy2.puppy #puppy-detailsmore').hide("fast");
		$('#puppy2.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy2.puppy #puppy-details').click(function(e) {
		e.preventDefault();
		$('#puppy2.puppy #puppy-detailsmore').slideToggle("fast");
		$('#puppy2.puppy #infodetails').hide("fast");
		$('#puppy2.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy2.puppy #photos').click(function(e) {
		if ($('#puppy2.puppy #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy2.puppy #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy2.puppy #puppyphotos').css('visibility','hidden');
		}
		$('#puppy2.puppy #puppy-detailsmore').hide("fast");
		$('#puppy2.puppy #infodetails').hide("fast");
	});
	
	$('#puppy3.puppy #info').click(function(e) {
		$('#puppy3.puppy #infodetails').slideToggle("fast");
		$('#puppy3.puppy #puppy-detailsmore').hide("fast");
		$('#puppy3.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy3.puppy #puppy-details').click(function(e) {
		e.preventDefault();
		$('#puppy3.puppy #puppy-detailsmore').slideToggle("fast");
		$('#puppy3.puppy #infodetails').hide("fast");
		$('#puppy3.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy3.puppy #photos').click(function(e) {
		if ($('#puppy3.puppy #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy3.puppy #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy3.puppy #puppyphotos').css('visibility','hidden');
		}
		$('#puppy3.puppy #puppy-detailsmore').hide("fast");
		$('#puppy3.puppy #infodetails').hide("fast");
	});
	
	$('#puppy4.puppy #info').click(function(e) {
		$('#puppy4.puppy #infodetails').slideToggle("fast");
		$('#puppy4.puppy #puppy-detailsmore').hide("fast");
		$('#puppy4.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy4.puppy #puppy-details').click(function(e) {
		e.preventDefault();
		$('#puppy4.puppy #puppy-detailsmore').slideToggle("fast");
		$('#puppy4.puppy #infodetails').hide("fast");
		$('#puppy4.puppy #puppyphotos').css('visibility','hidden');
	});
	
	$('#puppy4.puppy #photos').click(function(e) {
		if ($('#puppy4.puppy #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy4.puppy #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy4.puppy #puppyphotos').css('visibility','hidden');
		}
		$('#puppy4.puppy #puppy-detailsmore').hide("fast");
		$('#puppy4.puppy #infodetails').hide("fast");
	});
	
	/* CATEGORY LIST OF BREEDS */	
	$('#puppy1.breeds #photos').click(function(e) {
		if ($('#puppy1.breeds #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy1.breeds #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy1.breeds #puppyphotos').css('visibility','hidden');
		}
		$('#puppy1.breeds #puppy-detailsmore').hide("fast");
		$('#puppy1.breeds #infodetails').hide("fast");
	});
		
	$('#puppy2.breeds #photos').click(function(e) {
		if ($('#puppy2.breeds #puppyphotos').css('visibility') == 'hidden') {
			$('#puppy2.breeds #puppyphotos').css('visibility','visible');
		}
		else {
			$('#puppy2.breeds #puppyphotos').css('visibility','hidden');
		}
		$('#puppy2.breeds #puppy-detailsmore').hide("fast");
		$('#puppy2.breeds #infodetails').hide("fast");
	});
	
	/*TABBER SECTION ON HOMEPAGE */
	$('#contenttabber ul li a.welcome').click(function(e) {
		$('#contenttabber ul li a').removeClass('active');
		$(this).addClass('active');
		$('#article2tab').hide("fast");
		$('#article3tab').hide("fast");
		$('#welcometab').show("fast");
		$('#article2tab').hide("fast");
		$('#article3tab').hide("fast");
	});
	
	$('#contenttabber ul li a.article2').click(function(e) {
		$('#contenttabber ul li a').removeClass('active');
		$(this).addClass('active');
		$('#welcometab').hide("fast");
		$('#article3tab').hide("fast");
		$('#welcometab').hide("fast");
		$('#article2tab').show("fast");
		$('#article3tab').hide("fast");
	});
	
	$('#contenttabber ul li a.article3').click(function(e) {
		$('#contenttabber ul li a').removeClass('active');
		$(this).addClass('active');
		$('#article2tab').hide("fast");
		$('#welcometab').hide("fast");
		$('#welcometab').hide("fast");
		$('#article2tab').hide("fast");
		$('#article3tab').show("fast");
	});
	
	/* CHANGING LAYOUT LIST & GRID */
	$('.changestyle #listtype').click(function(e) {
		$('ul.breed-listing li').fadeOut(500, function () {
			$('ul.breed-listing li').removeClass('grid');
			$('ul.breed-listing li').fadeIn(500);
		 });	
	});
			
	$('.changestyle #gridtype').click(function(e) {
	   $('ul.breed-listing li').fadeOut(500, function () {
			$('ul.breed-listing li').addClass('grid');
			$('ul.breed-listing li').fadeIn(500);
		 });
	});
	
	/* CHARACTERISTICS */
	$('#characteristics .link').click(function(e) {
		e.stopPropagation();
		$('#characteristics .overlay').slideToggle("normal");
		$('.characteristics').hide("normal");
	});
	
	$('.overlay #country-origin').click(function(e) {
		e.stopPropagation();
		//$('.characteristics').hide('normal');
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').show('normal');
	});
	
	$('.overlay #size').click(function(e) {
		e.stopPropagation();
		//$('.characteristics').hide('normal');
		$('#size-details.characteristics').show('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('.overlay #coat').click(function(e) {
		e.stopPropagation();
		//$('.characteristics').hide('normal');
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').show('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('.overlay #character-temperament').click(function(e) {
		e.stopPropagation();
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').show('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('.overlay #care').click(function(e) {
		e.stopPropagation();
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').show('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('.overlay #training').click(function(e) {
		e.stopPropagation();
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').show('normal');
		$('#activity-details.characteristics').hide('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('.overlay #activity').click(function(e) {
		e.stopPropagation();
		$('#size-details.characteristics').hide('normal');
		$('#coat-details.characteristics').hide('normal');
		$('#character-temperament-details.characteristics').hide('normal');
		$('#care-details.characteristics').hide('normal');
		$('#training-details.characteristics').hide('normal');
		$('#activity-details.characteristics').show('normal');
		$('#country-origin-details.characteristics').hide('normal');
	});
	
	$('#characteristics .overlay').click(function(e) {
		e.stopPropagation();
	});
	
	$('.characteristics').click(function(e) {
		e.stopPropagation();
	});
	
	/* REFINE SEARCH */
	$('.refinesearch .select-style').click(function(e) {
		$('.refinesearch span.plus').toggle("normal");
		$('.refinesearch span.minus').toggle("normal");
		e.stopPropagation();
		$('#accordion').slideToggle("normal");
	});
	
	$('.refinesearch #accordion input#all').click(function(e) {
		if(this.checked) { // check select status
			$('.refinesearch #accordion input').each(function() { //loop through each checkbox
				this.checked = true;  //select all checkboxes with class "checkbox1"               
			});
		}
		else{
			$('.refinesearch #accordion input').each(function() { //loop through each checkbox
				this.checked = false; //deselect all checkboxes with class "checkbox1"                       
			});         
		}
	});
	
	$('#container .breed-listing .trophy').mouseenter(function(e) {
		$('#container .breed-listing .trophy-details').hide(0).delay(0).toggle('slide', {
			direction: 'left'
		}, 500);
	});
	
	$('#container .breed-listing .trophy').mouseleave(function(e) {
		$('#container .breed-listing .trophy-details').hide("normal");
	});
});