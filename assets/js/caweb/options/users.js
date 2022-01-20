/* CAWeb NetAdmin Disc Estimator Javascript */
jQuery(document).ready(function($) {
	$('.caweb-netadmin-site-list').on( 'change', function(){
		var newHref = $($(this).next()).attr('href');
		newHref = newHref.replace(/blog_id=[-]?\d*/g, 'blog_id=' + $(this).val() );
		$($(this).next()).attr('href', newHref);
	});

});
