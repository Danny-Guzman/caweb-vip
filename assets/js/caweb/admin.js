/* CAWeb NetAdmin Options Javascript */
jQuery(document).ready(function($) {
	"use strict";
	var changeMade = false;

	$(window).on('beforeunload', function(){
		if( changeMade )
				return 'Are you sure you want to leave?';
	});
});