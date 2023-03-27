<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */

add_filter( 'vip_go_srcset_enabled', '__return_false' );

add_filter( 'wpforms_upload_root', 'caweb_vip_upload_root', 10, 1 );

// disable JS & CSS concatenation
// https://cdtp2.wordpress.com/2022/03/15/divi-builder-new-version-does-not-load/
add_filter( 'js_do_concat', '__return_false' );
add_filter( 'css_do_concat', '__return_false' );


/**
 * Better function for Divi getting an attachments ID from its URL
 * @see https://wordpressvip.zendesk.com/hc/en-us/requests/155684
 */
add_filter('et_get_attachment_id_by_url_pre', 'caweb_vip_get_attachment_id', 10, 2 );


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
	
	//return get_temp_dir();
	return wp_get_upload_dir()['basedir'] . '/wpforms';
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

	// Check for returned scheme, so we can separately handle relative background image paths.
	if ( array_key_exists( 'scheme', $url_parts ) ) {
		$reconstructed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
	} else {
		$reconstructed_url = get_site_url() . $url_parts['path'];
	}	

	if( function_exists('wpcom_vip_attachment_url_to_postid') )
		return wpcom_vip_attachment_url_to_postid( $reconstructed_url );

	return false;
}
