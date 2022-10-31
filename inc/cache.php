<?php
/**
 * CAWeb VIP Helper Functions
 *
 * @package CAWeb VIP
 */

add_action( 'update_option_caweb_external_css', 'caweb_vip_clear_caweb_external_css_cache', 10, 3 );
add_action( 'update_option_caweb_external_js', 'caweb_vip_clear_caweb_external_js_cache', 10, 3 );
add_action( 'wp', 'caweb_vip_cache_maxage' );


/**
 * Changes the WPVIP TTL 
 * 
 * Fires once the WordPress environment has been set up.
 * 
 * @param  WP $wp Current WordPress environment instance (passed by reference).
 * @return void
 */
function caweb_vip_cache_maxage( $wp ) {
	$caweb_vip_ttl  = get_site_option('caweb_vip_cache_maxage', 30);
    // $change_tll_on_this_template = is_singular() || is_page() || is_front_page() || is_home() || is_archive() || is_404();

    if ( ! is_user_logged_in() ){
        header( 'Cache-Control: max-age=' . ($caweb_vip_ttl  * 60) );
    }
}

/**
 * Clears the cache for CAWeb Custom CSS files option after its value is updated.
 *
 * @link https://developer.wordpress.org/reference/hooks/pre_update_site_option_option/
 *
 * @param  mixed $old_value Old value of the network option.
 * @param  mixed $value New value of the network option.
 * @param  mixed $option Option name.
 *
 * @return string
 */
function caweb_vip_clear_caweb_external_css_cache( $old_value, $value, $option ){
    if( ! function_exists('wpcom_vip_purge_edge_cache_for_url') || ! defined('CAWEB_EXTERNAL_DIR') || ! defined('CAWEB_EXTERNAL_URI') ){
        return;
    }

    $id = get_current_blog_id();
    $dir = sprintf('%1$scss/%2$s/*.css', CAWEB_EXTERNAL_DIR, $id);

    foreach( glob($dir) as $file ){
        $fn = basename( $file );
    	$url = sprintf('%1$s/css/%2$s/%3$s',CAWEB_EXTERNAL_URI, $id, $fn);

        wpcom_vip_purge_edge_cache_for_url( $url );
    }
}

/**
 * Clears the cache for CAWeb Custom JS files option after its value is updated.
 *
 * @link https://developer.wordpress.org/reference/hooks/pre_update_site_option_option/
 *
 * @param  mixed $old_value Old value of the network option.
 * @param  mixed $value New value of the network option.
 * @param  mixed $option Option name.
 *
 * @return string
 */
function caweb_vip_clear_caweb_external_js_cache( $old_value, $value, $option ){
    if( ! function_exists('wpcom_vip_purge_edge_cache_for_url') || ! defined('CAWEB_EXTERNAL_DIR') || ! defined('CAWEB_EXTERNAL_URI') ){
        return;
    }

    $id = get_current_blog_id();
    $dir = sprintf('%1$sjs/%2$s/*.js', CAWEB_EXTERNAL_DIR, $id);

    foreach( glob($dir) as $file ){
        $fn = basename( $file );
    	$url = sprintf('%1$s/js/%2$s/%3$s',CAWEB_EXTERNAL_URI, $id, $fn);

        wpcom_vip_purge_edge_cache_for_url( $url );
    }
}