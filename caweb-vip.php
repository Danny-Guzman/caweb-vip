<?php
/**
 * Plugin Name: CAWeb WPVIP Integration Plugin
 * Plugin URI: "https://github.com/CA-CODE-Works/CAWeb-VIP/"
 * Description: Resolves several WPVIP Environment issues for the CAWebPublishing Service
 * Author: California Department of Technology
 * Author URI: "https://github.com/Danny-Guzman"
 * Version: 1.0.4
 * Network: true
 *
 * @package CAWeb VIP
 */

define( 'CAWEB_VIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CAWEB_VIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'init', 'caweb_vip_init', 9 );
add_action( 'admin_init', 'caweb_vip_admin_init' );
add_action( 'admin_enqueue_scripts', 'caweb_vip_enqueue_scripts_styles' );
add_action( 'admin_head', 'caweb_vip_admin_head', 999);
add_action( 'after_setup_theme', 'caweb_vip_after_setup_theme', 1 );

add_filter( 'et_cache_wpfs_credentials', 'caweb_vip_wpfs_credentials');

/**
 * Returns credentials to interact with wp_filesystem
 *
 * @return bool|array
 */
function caweb_vip_wpfs_credentials(){
	return request_filesystem_credentials( site_url() );
}

// Disable non-mandatory Jetpack Modules.
add_filter( 'option_jetpack_active_modules', 'caweb_vip_disable_jetpack_modules' );

/**
 * Sets up Divi Cache Constants.
 *
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/after_setup_theme
 * @return void
 */
function caweb_vip_after_setup_theme() {
    define('ET_CORE_CACHE_DIR', wp_get_upload_dir()['basedir'].'/et-cache');
    define('ET_CORE_CACHE_DIR_URL', wp_get_upload_dir()['baseurl'].'/et-cache');
}

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
	global $pagenow;

	// Allow all VIP Support requests through their proxy for global support.

	if ( 'wp-login.php' === $pagenow ) {
		$region_lock = get_site_option( 'caweb_netadmin_default_region', true );
		$country_code = isset( $_SERVER['GEOIP_COUNTRY_CODE'] ) && ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) ? $_SERVER['GEOIP_COUNTRY_CODE']  : '';
	    $is_vip = function_exists( 'is_proxied_request' ) && is_proxied_request();

		// if user logged in from anywhere other than the US.
		// if user is not WPVIP
		if ( ! $is_vip && $region_lock && 'US' !== $country_code ) {
			wp_logout();
			wp_safe_redirect( get_site_url() );
			exit;
		}

	}

	/* Include CAWeb VIP Functionality */
	foreach ( glob( CAWEB_VIP_PLUGIN_DIR . 'inc/*.php' ) as $file ) {
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

	if ( ! is_super_admin() ) {
		remove_all_actions( 'network_admin_notices' );
	    remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'admin_notices' );

		add_action( 'admin_notices', 'wpcom_vip_two_factor_admin_notice' );

		/*if ( '1' == get_option( 'blog_public' ) ) {
			add_action( 'admin_notices', 'Automattic\VIP\Blog_Public\notice' );
		}*/
	}

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

/**
 * Disable non-mandatory Jetpack Modules
 * note this disables the defaults `json-api`, `enhanced-distribution`, `notes`, `sso`, etc.
 *
 * @see https://jetpack.com/support/module-overrides/
 *
 * @param  array $modules JetPack Modules.
 * @return array
 */
function caweb_vip_disable_jetpack_modules( $modules ) {
	// @see https://github.com/Automattic/vip-go-mu-plugins/blob/e1802e01acd8be4bf95b87fe6be55597bf7ad88f/vip-jetpack/jetpack-mandatory.php#L20-L21
	$allowed_modules = array(
		'vaultpress',
		'stats',
	);
	foreach ( $modules as $module_key => $module_slug ) {
		if ( ! in_array( $module_slug, $allowed_modules, true ) ) {
			unset( $modules[ $module_key ] );
		}
	}
	return $modules;
}

/**
 * Fires in head section for all admin pages.
 *
 * @see https://developer.wordpress.org/reference/hooks/admin_head/
 * @return void
 */
function caweb_vip_admin_head(){
	?>
		<style>
			div#jetpack_summary_widget.postbox{ display: none; }
		</style>
	<?php
	
}

