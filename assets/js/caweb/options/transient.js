/* CAWeb NetAdmin Transient Javascript */
jQuery(document).ready(function($) {
	$('#resetTransient').click(function( e ){
		e.preventDefault();
		var modified = $(this).next();
		var fd = new FormData();
		fd.append( 'action', 'caweb_netadmin_reset_transients' );
		fd.append( 'nonce', $('input[name="caweb_netadmin_settings_nonce"]').val() );

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function( last_modified_date ){e
				$(modified).html(last_modified_date );
			}
		});
	});
});
