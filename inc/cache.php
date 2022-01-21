<?php
/**
 * CAWeb VIP Helper Functions
 *
 * @package CAWeb VIP
 */

add_action( 'update_option_caweb_external_css', 'caweb_vip_clear_caweb_external_css_cache', 10, 3 );
add_action( 'update_option_ca_custom_css', 'caweb_vip_clear_caweb_external_css_cache', 10, 3 );

add_action( 'update_option_caweb_external_js', 'caweb_vip_clear_caweb_external_js_cache', 10, 3 );
add_action( 'update_option_ca_custom_js', 'caweb_vip_clear_caweb_external_js_cache', 10, 3 );

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