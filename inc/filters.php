<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */

add_filter( 'the_content', 'caweb_vip_the_content', 10 );
add_filter( 'vip_go_srcset_enabled', '__return_false' );
add_filter('wp_get_attachment_image_src', 'caweb_vip_add_cache_bust_query', PHP_INT_MAX );
add_filter('wp_get_attachment_url', 'caweb_vip_add_cache_bust_query', PHP_INT_MAX );

add_filter( 'wpforms_upload_root', 'caweb_vip_upload_root', 10, 1 );

// disable JS & CSS concatenation
// https://cdtp2.wordpress.com/2022/03/15/divi-builder-new-version-does-not-load/
add_filter( 'js_do_concat', '__return_false' );
add_filter( 'css_do_concat', '__return_false' );

/**
 * Restrict unpublished files
 * @see https://docs.wpvip.com/technical-references/restricting-site-access/access-controlled-files/
 */
//add_filter( 'pre_option_vip_files_acl_restrict_unpublished_enabled' );

/**
 * Better function for Divi getting an attachments ID from its URL
 * @see https://wordpressvip.zendesk.com/hc/en-us/requests/155684
 */
add_filter('et_get_attachment_id_by_url_pre', 'caweb_vip_get_attachment_id', 10, 2 );


/**
 * Filters the post content adding the vip cache busting query variable to any src/href attributes.
 *
 * @see caweb_vip_add_cache_bust_query 
 * 
 * @param  string $output Content of the current post.
 * @return string
 */
function caweb_vip_the_content( $output ) {
	$mime_types = array_keys(get_allowed_mime_types());
	$extensions = join("|",$mime_types);

	/**
	 * Changing the delimiter used for the regex pattern
	 * @link https://www.php.net/manual/en/regexp.reference.delimiters.php
	 */
	preg_match_all( sprintf('~src="(%1$s[\w\d\S]+)"|href="(%1$s[\w\d\S]+\.(%2$s))"~', get_site_url(), $extensions), $output, $matches );

	if ( ! empty( $matches ) ) {
		$srcs = array_filter($matches[1]);
		$hrefs = array_filter($matches[2]);

		$urls = array_unique($srcs + $hrefs);
		ksort($urls);

		$changes = array_map(
			function( $url ) {
				return ! empty( $url ) ? caweb_vip_add_cache_bust_query( $url ) : false;
			},
			$urls
		);

		foreach(array_unique($matches[0]) as $i => $match){
			$changes[$i] = str_replace($urls[$i], $changes[$i], $match);
		}

		$output = str_replace(array_unique($matches[0]) , $changes, $output);
	}

	return $output;
}

/**
 * VIP Cache Busting
 *  
 * In VIP, any media uploaded to the /wp-content/uploads/ directory is cached with Nginx. 
 * For uploaded images, we need to either change the file name when a file is updated or add a query string where the URL to the image is referenced.
 * We also have to disable VIP's srcset feature to make local browser cache busting for Enable Media Replace more streamline.
 * 
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/140009
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/143840
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/149275
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2155
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2259
 * 
 * @param  string $url Attachment URL.
 * @return void
 */
function caweb_vip_add_cache_bust_query( $url ) {
	$url_to_bust = is_array( $url ) ? $url[0] : $url;

    $url_busted = add_query_arg( 'emrc', caweb_vip_get_attachment_version_number( $url_to_bust ),  $url_to_bust);

	if ( is_array( $url ) ) {
	    $url[0] = $url_busted;
    	return $url;
    } else {
		return $url_busted;
    }
}

 /**
  * Returns a version number for attachment urls.
  *
  * @see https://docs.wpvip.com/technical-references/caching/uncached-functions/
  *
  * @param  string $src Attachment urls.
  * @return void
  */
  function caweb_vip_get_attachment_version_number( $src ) {
	// not on VIP. As attachment_url_to_postid is expensive outside of VIP, just random ID it.
	if ( empty( $src ) || ! function_exists('wpcom_vip_attachment_url_to_postid') )
		return uniqid();
 
	// We don't want all attachment requests to bypass page cache like uniqid() does to bust. 
	// Instead find the current "version" of a file by leveraging core's post_modified_gmt 
	// value, this date changes when an attachment is replaced via the enable-media-replace plugin.
	$attachment_post_id = wpcom_vip_attachment_url_to_postid( $src );
	
	// can't find post from the URL.
	if ( ! $attachment_post_id )
		return uniqid();
	
	// exposing the full date may be undesired by the content authors, and a long hash hurts the readability of the DOM.
	return substr( md5( get_post( $attachment_post_id )->post_modified_gmt ), 0, 6);
	
 }
   
/**
 * Change the path where file uploads are stored in WPForms.
 *
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/147872
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/155753
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2196
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2261
 * 
 * @link    https://wpforms.com/developers/wpforms_upload_root/
 * @param   string $path  root path of where file uploads will be stored.
 * @return  void
 */
function caweb_vip_upload_root( $path ) {

	// wpforms entry exports aren't working on wp_get_upload_dir() paths for currently-unknown reasons
	// @TODO don't use /tmp for export location as the user may need to re-click the export button many times until the ajax request lands on the correct container, see VIP ticket 155753
	// this did not work
	//return is_admin() ? get_temp_dir() : wp_get_upload_dir()['basedir'] . '/wpforms';
	
	return get_temp_dir();

}

/**
 * Improved Response Time
 * 
 * There are an abundance of queries taking nearly a second on many pages, most originate from Divi's et_get_attachment_id_by_url() function.
 * This operation is notoriously expensive as it attempts to scan the entire wp_*_posts table by the unindexed guid column to retrieve an attachments ID.
 * VIP has solved for this function already by creating their own wpcom_vip_attachment_url_to_postid() function that saves the query results in the very fast object cache for later calls. 
 * This stops all these non-performant queries from needing to be ran each origin request.
 * 
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/155684
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2256
 * 
 * @param string
 * @return void
 */
function caweb_vip_get_attachment_id( $value, $url ) {
	if ( empty($url) )
		return false;

	// remove query parameters to better match whats on the guid column
	$url_parts = parse_url($url);
	$reconstructed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];

	if( function_exists('wpcom_vip_attachment_url_to_postid') )
		return wpcom_vip_attachment_url_to_postid( $reconstructed_url );

	return false;
}

/**
 * Restrict access to unpublished files
 * @see https://docs.wpvip.com/technical-references/restricting-site-access/access-controlled-files/
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2267/
 */
/*function caweb_vip_restrict_access_to_unpublished_files ( $value ) {
	return 1;

}*/