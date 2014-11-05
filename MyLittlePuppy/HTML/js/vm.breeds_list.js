function amplifyInit() {
	amplify.request.define('getBreeds', 'ajax', {
        url: 'http://mlp.pilrtest.com/api/breeds', //The API call's URL
        dataType: 'json', //Request/response data type
        type: 'GET', //The HTTP method
        contentType: 'application/json; charset=utf-8' //Request/response content type
    });
};

function getBreeds(callbacks) {
	return amplify.request({
        resourceId: 'getBreeds',
        success: callbacks.success,
        error: callbacks.error
    });
};

function breedsListVM() {
    var breeds = ko.observableArray();
    
    var init = function () {
	    getBreeds({
	    	success: function (result) {
	    		breeds(result);
				console.log(breeds());
	    	},
	    	error: function (response) { 
		    	console.log(response);
	    	}
	    });
    };
    
    //Reference things like breedsListVM.init();
    return {
	    init: init,
		breeds: breeds
    };
};

$(document).ready(function() {
	amplifyInit();
	
	breedsListVM = new breedsListVM();
	
	breedsListVM.init();
	
	// Activates knockout.js
	ko.applyBindings(breedsListVM);
});


