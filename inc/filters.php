<?php
/**
 * CAWeb VIP Plugin Filters
 *
 * @package CAWeb VIP
 */

/* WP Filters */
add_filter( 'the_content', 'caweb_vip_the_content', 99999 );
add_filter( 'css_do_concat', 'caweb_vip_css_do_concat', 10, 2 );

/**
 * Filters the post content adding a version query variable to any src attributes.
 *
 * @param  string $output Content of the current post.
 * @return string
 */
function caweb_vip_the_content( $output ) {
	// Regex match src and document href links.
	preg_match_all( '/src="[^"]*"|href="[^"]*\.(doc|docx|xls|xlsx|ppt|pptx|pdf|)"/', $output, $srcs );

	if ( ! empty( $srcs ) ) {
		$new_srcs = array();

		// iterate thru srcs.
		foreach ( $srcs[0] as $src ) {
			// remove the last double quote.
			$src = substr( $src, 0, strlen( $src ) - 1 );

			// if there are no URL params then use ? otherwise &.
			$src .= false === strpos( $src, '?' ) ? '?' : '&';

			$new_srcs[] = sprintf( '%1$sversion=%2$s"', $src, uniqid() );
		}

		$output = str_replace( $srcs[0], $new_srcs, $output );
	}

	return $output;
}

/**
 * Excludes the Disable Comments style sheet from the WPVIP concatenation script.
 *
 * @link https://docs.wpvip.com/technical-references/vip-platform/file-concatenation-and-minification/
 *
 * @param  bool   $do_concat Whether to concat or not.
 * @param  string $handle Script/Style file handle.
 * @return bool
 */
function caweb_vip_css_do_concat( $do_concat, $handle ) {
	if ( 'disable-comments-style' === $handle ) {
		return false;
	}
	return $do_concat;
}
