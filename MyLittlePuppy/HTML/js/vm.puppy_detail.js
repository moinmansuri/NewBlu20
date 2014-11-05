function amplifyInit() {
	amplify.request.define('getpuppy', 'ajax', {
        url: 'http://mlp.pilrtest.com/api/puppies', //The API call's URL
        dataType: 'json', //Request/response data type
        type: 'GET', //The HTTP method
        contentType: 'application/json; charset=utf-8' //Request/response content type
    });
};

function getpuppy(callbacks, data) {
	return amplify.request({
        resourceId: 'getpuppy',
        data: data,
        success: callbacks.success,
        error: callbacks.error
    });
};

function puppyDetailVM() {
    var puppy = ko.observableArray();
    
    function getPuppyID(name) {
	    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
	        results = regex.exec(location.search);
	    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}

    var init = function () {
    
    	var puppyID = getPuppyID('PuppyID');
    	
	    getpuppy({
		    	success: function (result) {
		    		puppy(result);
					console.log(puppy());
		    	},
		    	error: function (response) { 
			    	console.log(response);
		    	}
			},
	    	{ PuppyID : puppyID }
	    );
    };
    
    //Reference things like puppiesVM.init();
    return {
	    init: init,
		puppy: puppy
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
	
	puppyDetailVM = new puppyDetailVM();
	
	puppyDetailVM.init();
	
	// Activates knockout.js
	ko.applyBindings(puppyDetailVM);
});


