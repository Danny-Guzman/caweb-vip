/* CAWeb NetAdmin Site Freeze Javascript */
jQuery(document).ready(function($) {
	var output = $('#site-results');

	$('input[name="caweb-netadmin-site-display"]').click(function(){
		$(output).empty();

		switch( $(this).val() ){
			case 'numbered':
				numbered_view();
				break;
			case 'csv':
				csv_view();
				break;
			case 'bulleted':
				bulleted_view();
				break;
		}
	})

	function numbered_view(){
		var u = document.createElement('OL');
		$(u).addClass('ml-0');

		Object.keys( caweb_netadmin_args.sitesdata ).forEach(function(id){
			var l = document.createElement('LI');
			$(l).html(caweb_netadmin_args.sitesdata[id].siteurl);
			$(u).append(l);
		});
		
		$(output).append(u);
	}
	
	function csv_view(){
		var table = document.createElement('TABLE');
		var headers = ['ID', 'Fav Icon', 'Title', 'URL', 'Registered Created', 'Last Updated', 'Template', 'Theme',  'Menu Type', 'Color Scheme',
						'Sticky Navigation', 'Live Drafts', 'Search Engine ID', 'Analytics ID', 'Meta ID', 'Google Translate'];
		var thead = table.createTHead();
		var tbody = document.createElement('tbody') ;
		var trow = thead.insertRow();

		$(table).addClass('table border');

		for(var i = 0; i <  headers.length; i++){
			var col = $(document.createElement('TH')).html( headers[i] );
			$(trow).append(col);
		}

		Object.keys( caweb_netadmin_args.sitesdata ).forEach(function(id){
			var site = caweb_netadmin_args.sitesdata[id];

			var data_row = document.createElement('TR');
			var gtrans = "" !== site.ca_google_trans_enabled ? site.ca_google_trans_enabled : "none";
			var fav_ico = 'Not Set';
			
			gtrans = 1 == gtrans ? "standard" : gtrans;

			if( "" !== site.ca_fav_ico && undefined !== site.ca_fav_ico ){
				fav_ico = document.createElement('IMG');
				$(fav_ico).attr('src', site.ca_fav_ico);
			}
			
			$(data_row.insertCell()).html( site.blog_id ) ;
			$(data_row.insertCell()).html( fav_ico ) ;
			$(data_row.insertCell()).html( site.blogname ) ;
			$(data_row.insertCell()).html( site.siteurl ) ;
			$(data_row.insertCell()).html( site.registered ) ;
			$(data_row.insertCell()).html( site.last_updated ) ;
			$(data_row.insertCell()).html( site.ca_site_version ) ;
			$(data_row.insertCell()).html( site.stylesheet ) ;
			$(data_row.insertCell()).html( site.ca_default_navigation_menu ) ;
			$(data_row.insertCell()).html( site.ca_site_color_scheme ) ;
			$(data_row.insertCell()).html( site.ca_sticky_navigation ? 'on' : 'off' ) ;
			$(data_row.insertCell()).html( site.caweb_live_drafts ? 'on' : 'off' ) ;
			$(data_row.insertCell()).html( site.ca_google_search_id ) ;
			$(data_row.insertCell()).html( site.ca_google_analytic_id ) ;
			$(data_row.insertCell()).html( site.ca_google_meta_id ) ;
			$(data_row.insertCell()).html( gtrans ) ;
						
			if( '1' === site.deleted){
				$(data_row).addClass( 'bg-wp-error' );
			}
			
			$(tbody).append(data_row);
		});

		var download = document.createElement('A');
		$(download).html('Download');
		$(download).addClass('btn btn-primary');
		$(download).attr('href', caweb_netadmin_args.post_url + "&nonce=" + $('input[name="caweb_netadmin_settings_nonce"]').val() );

		$(table).append(tbody);

		$(output).append(table);
		$(output).append(download);
	}

	function bulleted_view(){
		var u = document.createElement('UL');
		$(u).addClass('ml-4');

		Object.keys( caweb_netadmin_args.sitesdata ).forEach(function(id){
			var l = document.createElement('LI');
			$(l).html(caweb_netadmin_args.sitesdata[id].siteurl);
			$(u).append(l);
		});
		
		$(output).append(u);
	}

	
});