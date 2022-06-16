<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */

/* WP Filters */
add_filter( 'the_content', 'caweb_vip_the_content', 10 );
add_filter( 'wpforms_upload_root', 'caweb_vip_upload_root', 10, 1 );

// disable JS & CSS concatenation
// https://cdtp2.wordpress.com/2022/03/15/divi-builder-new-version-does-not-load/
add_filter( 'js_do_concat', '__return_false' );
add_filter( 'css_do_concat', '__return_false' );

/**
 * Filters the post content adding a version query variable to any src attributes.
 *
 * @param  string $output Content of the current post.
 * @return string
 */
function caweb_vip_the_content( $output ) {
	preg_match_all( '/src="([\w\S]*)"/', $output, $srcs );

	if ( ! empty( $srcs ) ) {
		$new_srcs = array_map(
			function( $src ) {
				return "$src?version=" . uniqid();
			},
			$srcs[1]
		);

		$output = str_replace( $srcs[1], $new_srcs, $output );
	}

	return $output;
}

// disable VIP's srcset feature to make local browser cache busting for enable-media-replace more streamline.
// @see https://docs.wpvip.com/technical-references/vip-go-files-system/responsive-images/ 
// @see https://wordpressvip.zendesk.com/agent/tickets/143840 
// add_filter( 'vip_go_srcset_enabled', '__return_false' );

// add a cache busting query string for enable-media-replace to media based on attachments post_modified date.
// add_filter('wp_get_attachment_image_src', 'caweb_vip_add_cache_bust_query_for_media_replace', PHP_INT_MAX );
// add_filter('wp_get_attachment_url', 'caweb_vip_add_cache_bust_query_for_media_replace', PHP_INT_MAX );

/* function caweb_vip_get_attachment_version_number( $src ) {
   // not on VIP. As attachment_url_to_postid is expensive outside of VIP, just random ID it.
   if ( empty( $src ) || ! function_exists('wpcom_vip_attachment_url_to_postid') )
       return uniqid();

   // We don't want all attachment requests to bypass page cache like uniqid() does to bust. 
   // Instead find the current "version" of a file by leveraging core's post_modified_gmt 
   // value, this date changes when an attachment is replaced via the enable-media-replace plugin.
   // @see https://docs.wpvip.com/technical-references/caching/uncached-functions/
   $attachment_post_id = wpcom_vip_attachment_url_to_postid( $src );
   
   // can't find post from the URL.
   if ( ! $attachment_post_id )
       return uniqid();
   
   // exposing the full date may be undesired by the content authors, and a long hash hurts the readability of the DOM.
   return substr( md5( get_post( $attachment_post_id )->post_modified_gmt ), 0, 6);
   
}

function caweb_vip_add_cache_bust_query_for_media_replace( $url ) {
	return add_query_arg( 'emrc', caweb_vip_get_attachment_version_number( $url ),  $url);
}
*/

/**
 * Change the path where file uploads are stored in WPForms.
 *
 * @link    https://wpforms.com/developers/wpforms_upload_root/
 * @param   string $path  root path of where file uploads will be stored.
 * @return  string
 */
function caweb_vip_upload_root( $path ) {

	// if WPForms is doing an export, assume ajax request
	if( wp_doing_ajax() ){
	// otherwise assume WPForms submission
	}else{
		// Define the path for your file uploads here.
		// $path = wp_get_upload_dir()['basedir'] . '/wpforms';
	}
	$path = get_temp_dir();

	return $path;

}