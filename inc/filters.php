<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */


/* WP Filters */
add_filter( 'the_content', 'caweb_vip_the_content', 10 );

/**
 * Filters the post content.
 *
 * @param  string $output Content of the current post.
 * @return string
 */
function caweb_vip_the_content( $output ) {
	preg_match_all( '/src="([\w\S]*)"/', $output, $srcs );

	if( ! empty( $srcs ) ){
		$new_srcs = array_map(function($src){
			return "$src?version=" . uniqid();
		}, $srcs[1]);

	}

	$output = str_replace($srcs[1], $new_srcs, $output);

	return $output;
}
