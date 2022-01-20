/* CAWeb NetAdmin Disc Estimator Javascript */
jQuery(document).ready(function($) {
	$('#recalc').click(function(){
		$('#discs tbody').empty();
		$('#discs tbody').append('<tr><td colspan="4">Calculating Disc Usage...</td></tr>');

		var data = {
		  'action': 'caweb_netadmin_recalc_disc_estimate',
		  'cost' : $('input[name="datacost"]').val(),
		  'free' : $('input[name="datafree"]').val(),
		  'nonce' : $('input[name="caweb_netadmin_settings_nonce"]').val()
		};
	  
		jQuery.post(ajaxurl, data, function(response) {
			$('#discs tbody').empty();
	  
			Object.keys( response ).forEach(function(id){
				var overage = 0 !== response[id].folder_info.overage ? 'overcharge' : '';
				var row = document.createElement('TR');
				var blogname = document.createElement('TD');
				var blogname_link = document.createElement('A');
				var size = document.createElement('TD');
				var datafree = document.createElement('TD');
				var datacost = document.createElement('TD');

				$(row).addClass(overage);

				$(blogname_link).html(response[id].blogname);
				$(blogname_link).attr('href', response[id].siteurl);
				
				$(size).html( response[id].folder_info.size + response[id].folder_info.label );
				$(size).addClass('text-center');

				$(datafree).html( $('input[name="datafree"]').val() + 'gb / ' + response[id].folder_info.overage + response[id].folder_info.overage_label);
				$(datafree).addClass('text-center');
				
				$(datacost).html( '$' + response[id].folder_info.overage_cost);
				$(datacost).addClass('text-center');

				$(blogname).append(blogname_link);

				$(row).append(blogname);
				$(row).append(size);
				$(row).append(datafree);
				$(row).append(datacost);

				$('#discs tbody').append(row);
			});

			var newHref = $('#caweb_netadmin_download_disc_report')[0].href;
			newHref = newHref.replace(/free=[\d.]+/g, 'free=' + $('input[name="datafree"]').val() );
			newHref = newHref.replace(/cost=[\d.]+/g, 'cost=' + $('input[name="datacost"]').val() ); 
			$('#caweb_netadmin_download_disc_report').attr('href', newHref);

		});
	});

	$('#datacost, #datafree').on('input', function(){
		var filterFloat = function(value) {
			if (/([A-z])/.test(value))
				value = value.replace(/([A-z])/g,""); 
				
			if (/\.(\d){3,}/.test(value) || /\d*(\.)\d*(\.)/.test(value) )
				return value.slice(0, value.length - 1 ); 
				
			return value;
		}
		$(this)[0].value = filterFloat($(this).val());
	});
});
