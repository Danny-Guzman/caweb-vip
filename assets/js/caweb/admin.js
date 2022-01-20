/* CAWeb NetAdmin Options Javascript */
jQuery(document).ready(function($) {
	"use strict";
	var changeMade = false;

	$(window).on('beforeunload', function(){
		if( changeMade )
				return 'Are you sure you want to leave?';
	});
	
	$('#caweb-netadmin-options-form button').on('click', function(){  
		changeMade = true;  
	});
		
	$('#caweb-netadmin-options-form').submit(function( e){
		e.preventDefault();
		var errors = false;

		// Check all Alias Redirects
		$('input[name="alias[]"]').each(function(i){
			var li = $(this).parent().parent();

			if( ! $(this).val().trim() ){
				$(li).addClass('bg-wp-error');

				if( ! errors ){
					if( ! $('a[href="#redirect"]').parent().hasClass('selected') )
						$('a[href="#redirect"]').click(); 

					alert( 'The Alias field can not be blank in Alias Redirects.' );
					errors = true;
				}
			}else{
				$(li).removeClass('bg-wp-error');
			}
		});
		
		// Check to ensure all Freeze sites have valid dates
		$('#caweb-netadmin-site-freeze li').each(function(i){
			var fstartdate = $(this).find('input[name="frozen_url_start[]"]');
			var estartdate = $(this).find('input[name="frozen_url_end[]"]');
			var result = dateCheck( fstartdate, estartdate );

			if( result ){
				$(this).removeClass('bg-wp-error');
			}else{
				$(this).addClass('bg-wp-error');

				if( ! errors ){
					if( ! $('a[href="#freeze"]').parent().hasClass('selected') )
						$('a[href="#freeze"]').click(); 

					alert( "End Date can not come before Start Date." );
					errors = true;
				}
			}

		});

		if( ! errors ){
			changeMade = false;
			this.submit();
		}
		
	});

	function dateCheck(s, e){

		// if start and end date picker are not empty, and the end date comes before the start date
		if( $(s).val().trim() && $(e).val().trim() && 
			new Date($(e).val()) <  new Date($(s).val()) ){
			return false;
		}
		return true;
	}
	
});