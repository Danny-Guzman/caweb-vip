<?php
/**
 * Plugin Name: CAWeb VIP Integration Plugin
 * Plugin URI: "https://github.com/CA-CODE-Works/CAWeb-VIP/"
 * Description: CAWeb VIP Environment Integration Plugin
 * Author: California Department of Technology
 * Author URI: "https://github.com/Danny-Guzman"
 * Version: 1.0.0
 * Network: true
 *
 * @package CAWeb VIP
 */

define( 'CAWEB_VIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CAWEB_VIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'init', 'caweb_vip_init' );
add_action( 'admin_init', 'caweb_vip_admin_init' );
add_action( 'admin_enqueue_scripts', 'caweb_vip_enqueue_scripts_styles' );

/**
 * CAWeb VIP Admin Initialization
 *
 * Triggered before any other hook when a user accesses the admin area.
 * Note, this does not just run on user-facing admin screens.
 * It runs on admin-ajax.php and admin-post.php as well.
 *
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/admin_init
 * @return void
 */
function caweb_vip_init() {
	/* Include CAWeb VIP Functionality */
	foreach ( glob( __DIR__ . '/inc/*.php' ) as $file ) {
		require_once $file;
	}
}

/**
 * CAWeb VIP Admin Init
 *
 * Triggered before any other hook when a user accesses the admin area.
 * Note, this does not just run on user-facing admin screens.
 * It runs on admin-ajax.php and admin-post.php as well.
 *
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/admin_init
 * @return void
 */
function caweb_vip_admin_init() {
	require_once CAWEB_VIP_PLUGIN_DIR . 'core/class-caweb-vip-plugin-update.php';
}

/**
 * CAWeb VIP Admin Enqueue Scripts and Styles
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
 *
 * @param  string $hook The current admin page.
 *
 * @return void
 */
function caweb_vip_enqueue_scripts_styles( $hook ) {

	// Load only on ?page=caweb-vip.
	if ( false !== strpos( $hook, 'caweb-vip' ) ) {
		$version   = get_plugin_data( __FILE__ )['Version'];
		$admin_css = caweb_vip_get_min_file( 'css/admin.css' );
		$admin_js  = caweb_vip_get_min_file( 'js/admin.js', 'js' );

		wp_enqueue_script( 'jquery' );

		wp_register_script( 'caweb-vip-admin-scripts', $admin_js, array( 'jquery' ), $version, true );

		wp_enqueue_script( 'caweb-vip-admin-scripts' );

		wp_enqueue_style( 'caweb-vip-admin-styles', $admin_css, array(), $version );

	}

}
