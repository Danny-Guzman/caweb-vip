<?php
/**
 * CAWeb VIP Helper Functions
 *
 * @package CAWeb VIP
 */

/**
 * Load Minified Version of a file
 *
 * @param  string $f File to load.
 * @param  mixed  $ext Extension of file, default css.
 *
 * @return string
 */
function caweb_vip_get_min_file( $f, $ext = 'css' ) {
	// if a minified version exists.
	if ( file_exists( CAWEB_VIP_PLUGIN_DIR . str_replace( ".$ext", ".min.$ext", $f ) ) ) {
		return CAWEB_VIP_PLUGIN_URL . str_replace( ".$ext", ".min.$ext", $f );
	} else {
		return CAWEB_VIP_PLUGIN_URL . $f;
	}
}


/**
 * Get User Profile Color
 *
 * @return array
 */
function caweb_vip_get_user_color() {
	global $_wp_admin_css_colors;

	$admin_color = get_user_option( 'admin_color' );

	return $_wp_admin_css_colors[ $admin_color ];
}

