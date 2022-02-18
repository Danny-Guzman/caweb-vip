/* CAWeb VIP Cache Javascript */
jQuery(document).ready(function($) {
	$('#caweb-vip-options-form #caweb-vip-purge-url').on('click', function(e){
		e.preventDefault();
		var url = $('input[name="caweb_vip_page_cache_url"]')[0];
		var type = $('input[name="caweb_vip_page_cache_type"]:checked')[0];
		var spinner = $(this).next();

		if( ! url.checkValidity() ){
			url.reportValidity();
			return;
		}
		
		if( ! type.checkValidity() ){
			type.reportValidity();
			return;
		}

		$(spinner).removeClass('d-none');

		var data = {
		  'action': 'caweb_vip_clear_cache',
		  'nonce' : $('input[name="caweb_vip_settings_nonce"]').val(),
		  'url' : url.value,
		  'type' : type.value,
		};
	  
		jQuery.post(ajaxurl, data, function(response) {
			$(spinner).addClass('d-none');
			
			if( true === response ){
				alert( "Cache successfully cleared.");
			}else{
				alert( "Cache could not be cleared.");
			}
		});
	});

	$('#caweb-vip-options-form #caweb-vip-purge-site').on('click', function(e){
		e.preventDefault();
		var site = $('select[name="caweb_vip_site_cache_url"] option:selected')[0];
		var confirm = $('input[name="caweb_vip_site_cache_confirm"]')[0];
		var spinner = $(this).next();
		
		if( ! confirm.checkValidity() ){
			confirm.reportValidity();
			return;
		}

		$(spinner).removeClass('d-none');

		var data = {
		  'action': 'caweb_vip_clear_all_cache',
		  'nonce' : $('input[name="caweb_vip_settings_nonce"]').val(),
		  'blog_id' : site.value,
		};
	  
		jQuery.post(ajaxurl, data, function(response) {
			$(spinner).addClass('d-none');

			if( true === response ){
				alert( "Cache successfully cleared.");
			}else{
				alert( "Cache could not be cleared.");
			}
		});
	});

});