

//Surround this with jQuery(document).ready() if this isn't in the footer
(function($) {
    var data = {

    };
	$.post(ajaxurl, data, function(resp){
        if(typeof resp == 'string'){
            resp = JSON.parse(resp);
        }
        console.log(resp);
    })
	// $ Works! You can test it with next line if you like
	// console.log($);
	
})( jQuery );