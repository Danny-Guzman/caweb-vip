/* CAWeb NetAdmin Site Freeze Javascript */
jQuery(document).ready(function($) {
	$('.new-site-freeze').click(function(e){
		e.preventDefault();
		var freezeList = $('#caweb-netadmin-site-freeze');
		var li = document.createElement('LI');

		var group_a = document.createElement('DIV');
		var group_a_label = document.createElement('H4');
		var group_a_input = document.createElement('SELECT');

		var group_b = document.createElement('DIV');
		var group_b_label = document.createElement('H4');
		var group_b_input = document.createElement('INPUT');

		var group_c = document.createElement('DIV');
		var group_c_label = document.createElement('H4');
		var group_c_input = document.createElement('INPUT');
		var group_c_remove = document.createElement('BUTTON');

		// List Item
		$(li).addClass('py-2');
		
		// Group A
		$(group_a).addClass('form-group');

		$(group_a_label).addClass('d-inline mr-1');
		$(group_a_label).html('Site');

		$(group_a_input).addClass('form-control-sm');
		$(group_a_input).attr('name', 'frozen_url[]');

		$(group_a).append(group_a_label);
		$(group_a).append(group_a_input);

		var data = {
			'action': 'caweb_netadmin_get_unfrozen_sites'
		};

		jQuery.post(ajaxurl, data, function(response) {
			response.forEach( function(val){
				var o = document.createElement('OPTION');
				$(o).val(val);
				$(o).html(val);
				$(group_a_input).append(o);
			});
		});		

		// Group B
		$(group_b).addClass('form-group d-inline-block mr-3');

		$(group_b_label).addClass('d-inline mr-1');
		$(group_b_label).html('Start Date');

		$(group_b_input).addClass('form-control-sm mr-1');
		$(group_b_input).attr('name', 'frozen_url_start[]');
		$(group_b_input).attr('type', 'datetime-local');


		$(group_b).append(group_b_label);
		$(group_b).append(group_b_input);

		// Group C
		$(group_c).addClass('form-group d-inline-block');

		$(group_c_label).addClass('d-inline mr-1');
		$(group_c_label).html('End Date');

		$(group_c_input).addClass('form-control-sm mr-1');
		$(group_c_input).attr('name', 'frozen_url_end[]');
		$(group_c_input).attr('type', 'datetime-local');

		$(group_c_remove).addClass('btn btn-sm btn-primary align-bottom ml-2 remove-alias-redirect');
		$(group_c_remove).html('Remove');
		group_c_remove.addEventListener('click', remove_site_freeze );

		$(group_c).append(group_c_label);
		$(group_c).append(group_c_input);
		$(group_c).append(group_c_remove);

		// Redirect List
		$(li).append(group_a);
		$(li).append(group_b);
		$(li).append(group_c);

		$(freezeList).append(li);
	});

	$('.remove-site-freeze').click( remove_site_freeze );

	function remove_site_freeze( e ){
		e.preventDefault();
		$(this).parent().parent().remove();
	}
});