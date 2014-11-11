function amplifyInit() {
	amplify.request.define('getpuppies', 'ajax', {
        url: 'http://mlp.pilrtest.com/api/puppies', //The API call's URL
        dataType: 'json', //Request/response data type
        type: 'GET', //The HTTP method
        contentType: 'application/json; charset=utf-8' //Request/response content type
    });
};

function getpuppies(callbacks, data) {
	return amplify.request({
        resourceId: 'getpuppies',
        data: data,
        success: callbacks.success,
        error: callbacks.error
    });
};

function breedPuppiesVM() {
    var puppies = ko.observableArray();
    
    function getBreedCode(name) {
	    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
	        results = regex.exec(location.search);
	    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}

    var init = function () {
    
    	var breedCode = getBreedCode('BreedCode');
    	
	    getpuppies({
		    	success: function (result) {
		    		puppies(result);
					console.log(puppies());
		    	},
		    	error: function (response) { 
			    	console.log(response);
		    	}
			},
	    	{ BreedCode : breedCode }
	    );
    };
    
    //Reference things like puppiesVM.init();
    return {
	    init: init,
		puppies: puppies
    };
};

$(document).ready(function() {
	ko.bindingHandlers.imgURL = {
	    update: function(element, valueAccessor, allBindings) {
		    var valueUnwrapped = ko.unwrap(valueAccessor());
		    $(element).attr("src", valueUnwrapped)
	    }
	};
	
	amplifyInit();
	
	breedPuppiesVM = new breedPuppiesVM();
	
	breedPuppiesVM.init();
	
	// Activates knockout.js
	ko.applyBindings(breedPuppiesVM);
});


