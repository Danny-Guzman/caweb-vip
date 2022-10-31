<?php
/**
 * CAWeb VIP Cache
 *
 * @package CAWebVIP
 */

add_action( 'update_option_caweb_external_css', 'caweb_vip_clear_caweb_external_css_cache', 10, 3 );
add_action( 'update_option_caweb_external_js', 'caweb_vip_clear_caweb_external_js_cache', 10, 3 );
add_action( 'wp', 'caweb_vip_cache_maxage' );
add_action( 'restrict_site_access_ip_match ', 'caweb_vip_restrict_site_access_ip_match' );


/**
 * Change WPVIP Cache TTL
 *
 * Default TTL on VIP is 30 minutes, we would like it set to 5 minutes.
 *
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/154091
 * @p2 https://cdtp2.wordpress.com/2022/04/28/change-page-cache-ttl/
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2187
 *
 * @param  WP $wp Current WordPress environment instance (passed by reference).
 * @return void
 */
function caweb_vip_cache_maxage( $wp ) {
	$caweb_vip_ttl               = get_site_option( 'caweb_vip_cache_maxage', 30 );
	$change_tll_on_this_template = is_singular() || is_front_page() || is_archive();

	if ( ! is_user_logged_in() && $change_tll_on_this_template ) {
		header( 'Cache-Control: max-age=' . ( $caweb_vip_ttl * 60 ) );
	}
}

/**
 * Clear cache for CAWeb customer provided CSS/JS files
 *
 * Whenever CAWeb customers upload Custom CSS/JS files these files get stuck in cache and have to programmatically be flushed.
 *
 * @zendesk No Zendesk ticket submitted since this has to be done programmatically and CAWeb has to implement it themselves.
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2173
 *
 * @param  mixed $old_value Old value of the network option.
 * @param  mixed $value New value of the network option.
 * @param  mixed $option Option name.
 *
 * @return string
 */
function caweb_vip_clear_caweb_external_css_cache( $old_value, $value, $option ) {
	if ( ! function_exists( 'wpcom_vip_purge_edge_cache_for_url' ) || ! defined( 'CAWEB_EXTERNAL_DIR' ) || ! defined( 'CAWEB_EXTERNAL_URI' ) ) {
		return;
	}

	$id  = get_current_blog_id();
	$dir = sprintf( '%1$scss/%2$s/*.css', CAWEB_EXTERNAL_DIR, $id );

	foreach ( glob( $dir ) as $file ) {
		$fn  = basename( $file );
		$url = sprintf( '%1$s/css/%2$s/%3$s', CAWEB_EXTERNAL_URI, $id, $fn );

		wpcom_vip_purge_edge_cache_for_url( $url );
	}
}

/**
 * Clears the cache for CAWeb Custom JS files option after its value is updated.
 *
 * @see caweb_vip_clear_caweb_external_css_cache
 *
 * @param  mixed $old_value Old value of the network option.
 * @param  mixed $value New value of the network option.
 * @param  mixed $option Option name.
 *
 * @return string
 */
function caweb_vip_clear_caweb_external_js_cache( $old_value, $value, $option ) {
	if ( ! function_exists( 'wpcom_vip_purge_edge_cache_for_url' ) || ! defined( 'CAWEB_EXTERNAL_DIR' ) || ! defined( 'CAWEB_EXTERNAL_URI' ) ) {
		return;
	}

	$id  = get_current_blog_id();
	$dir = sprintf( '%1$sjs/%2$s/*.js', CAWEB_EXTERNAL_DIR, $id );

	foreach ( glob( $dir ) as $file ) {
		$fn  = basename( $file );
		$url = sprintf( '%1$s/js/%2$s/%3$s', CAWEB_EXTERNAL_URI, $id, $fn );

		wpcom_vip_purge_edge_cache_for_url( $url );
	}
}

/**
 * Locking Down WordPress for Intranet sites
 *
 * We are assisting the Restricted Site Access plugin to bypass the WPVIP cache.
 * 
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/155787
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2266
 * 
 * @param  mixed $remote_ip The remote IP address being checked.
 * @param  mixed $line The matched masked IP address.
 * @return void
 */
function caweb_vip_restrict_site_access_ip_match( $remote_ip, $line ) {
    session_start();

    nocache_headers();
}
