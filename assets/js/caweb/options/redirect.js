/* CAWeb NetAdmin Alias Redirect Javascript */
jQuery(document).ready(function($) {
	$('.new-alias-redirect').click(function(e){
		e.preventDefault();
		var redirectList = $('#caweb-netadmin-alias-redirects');
		var li = document.createElement('LI');
		var group_a = document.createElement('DIV');
		var group_a_label = document.createElement('H4');
		var group_a_input = document.createElement('INPUT');

		var group_b = document.createElement('DIV');
		var group_b_label = document.createElement('H4');
		var group_b_input = document.createElement('SELECT');
		var group_b_remove = document.createElement('BUTTON');

		// List Item
		$(li).addClass('py-2');
		
		// Group A
		$(group_a).addClass('form-group d-inline-block mr-2 mb-0 align-middle');

		$(group_a_label).addClass('d-inline mr-1');
		$(group_a_label).html('Alias');

		$(group_a_input).addClass('form-control-sm');
		$(group_a_input).attr('name', 'alias[]');
		$(group_a_input).attr('type', 'text');

		$(group_a).append(group_a_label);
		$(group_a).append(group_a_input);

		// Group B
		$(group_b).addClass('form-group d-inline-block mb-0 align-middle');

		$(group_b_label).addClass('d-inline mr-1');
		$(group_b_label).html('CAWeb Site');

		$(group_b_input).addClass('form-control-sm mr-1');
		$(group_b_input).attr('name', 'alias_url[]');

		Object.keys( caweb_netadmin_args.sitesdata ).forEach( function(val){
			var o = document.createElement('OPTION');
			$(o).val(caweb_netadmin_args.sitesdata[val]['siteurl']);
			$(o).html(caweb_netadmin_args.sitesdata[val]['siteurl']);
			$(group_b_input).append(o);
		});

		$(group_b_remove).addClass('btn btn-sm btn-primary align-bottom ml-2 remove-alias-redirect');
		$(group_b_remove).html('Remove');
		group_b_remove.addEventListener('click', remove_alias_redirect );

		$(group_b).append(group_b_label);
		$(group_b).append(group_b_input);
		$(group_b).append(group_b_remove);

		// Redirect List
		$(li).append(group_a);
		$(li).append(group_b);

		$(redirectList).append(li);
	});

	$('.remove-alias-redirect').click( remove_alias_redirect );

	function remove_alias_redirect( e ){
		e.preventDefault();
		$(this).parent().parent().remove();
	}
});