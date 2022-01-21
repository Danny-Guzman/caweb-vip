<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */

/* WP Filters */
add_filter( 'the_content', 'caweb_vip_the_content', 10 );
add_filter( 'auth_cookie_expiration', 'caweb_vip_auth_cookie_expiration', 10, 3 );

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
 * Filters the duration of the authentication cookie expiration period.
 *
 * @param  int  $length Duration of the expiration period in seconds.
 * @param  int  $user_id User ID.
 * @param  bool $remember Whether to remember the user login. Default false.
 * @return int
 */
function caweb_vip_auth_cookie_expiration( $length, $user_id, $remember ) {
	// 60 seconds * 30 mins
	return 60 * 30;
}
