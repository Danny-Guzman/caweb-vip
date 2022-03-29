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

/**
 * Change the path where file uploads are stored in WPForms.
 *
 * @link    https://wpforms.com/developers/wpforms_upload_root/
 *
 * @param   string $path  root path of where file uploads will be stored.
 *
 * @return  string
 */
function caweb_vip_upload_root( $path ) {

	// Define the path for your file uploads here.
	$path = wp_get_upload_dir()['basedir'] . '/wpforms';

	return $path;

}
