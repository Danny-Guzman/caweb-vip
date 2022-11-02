<?php
/**
 * Plugin Name: CAWeb WPVIP Integration Plugin
 * Plugin URI: "https://github.com/CA-CODE-Works/CAWeb-VIP/"
 * Description: Resolves several WPVIP Environment issues for the CAWebPublishing Service
 * Author: California Department of Technology
 * Author URI: "https://github.com/Danny-Guzman"
 * Version: 1.0.5
 * Network: true
 *
 * @package CAWebVIP
 */

define( 'CAWEB_VIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CAWEB_VIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'init', 'caweb_vip_init', 9 );
add_action( 'admin_init', 'caweb_vip_admin_init' );
add_action( 'admin_enqueue_scripts', 'caweb_vip_enqueue_scripts_styles' );
add_action( 'admin_head', 'caweb_vip_admin_head', 999 );
add_action( 'after_setup_theme', 'caweb_vip_after_setup_theme', 1 );

add_filter( 'option_jetpack_active_modules', 'caweb_vip_disable_jetpack_modules' );

/**
 * Divi Builder (New Version) does not load
 * 
 * @see caweb_vip_wpfs_credentials
 * @category add_action( 'after_setup_theme', 'caweb_vip_after_setup_theme', 1 );
 * @return void
 */
function caweb_vip_after_setup_theme() {
	define( 'ET_CORE_CACHE_DIR', wp_get_upload_dir()['basedir'] . '/et-cache' );
	define( 'ET_CORE_CACHE_DIR_URL', wp_get_upload_dir()['baseurl'] . '/et-cache' );
}

/**
 * Updates to Geo Login Restriction
 *
 * Our security plugin no longer works on the WPVIP platform, so we've added functionality to prevent international logins.
 *
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/146026
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2128
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2176
 * 
 * @category add_action( 'init', 'caweb_vip_init', 9 );
 * @return void
 */
function caweb_vip_init() {
	global $pagenow;

	// Allow all VIP Support requests through their proxy for global support.
	if ( 'wp-login.php' === $pagenow ) {
		$region_lock  = get_site_option( 'caweb_netadmin_default_region', true );
		$country_code = isset( $_SERVER['GEOIP_COUNTRY_CODE'] ) && ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) ? $_SERVER['GEOIP_COUNTRY_CODE'] : '';
		$is_vip       = function_exists( 'is_proxied_request' ) && is_proxied_request();

		// if user logged in from anywhere other than the US.
		// if user is not WPVIP.
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
 * Notification Suppression
 *
 * The system level notifications were displaying for Administrators, we are correcting this behaviour by removing those notifications for all but the Network Administrators.
 *
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/149577
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/149578
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/151228
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2209
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2219
 * 
 * @return void
 */
function caweb_vip_admin_init() {
	require_once CAWEB_VIP_PLUGIN_DIR . 'core/class-caweb-vip-plugin-update.php';

	if ( ! is_super_admin() ) {
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'admin_notices' );

		add_action( 'admin_notices', 'wpcom_vip_two_factor_admin_notice' );

		/*
		Remove notice if Restricted site access is set
		if ( '1' == get_option( 'blog_public' ) ) {
			add_action( 'admin_notices', 'Automattic\VIP\Blog_Public\notice' );
		}
		*/
	}

}

/**
 * CAWeb VIP Admin Enqueue Scripts and Styles
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
 * @param  string $hook The current admin page.
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
 * Disable JetPack
 *
 * WPVIP forces that the activation of the JetPack plugin, we've had to make the following fixes:<br />
 * <ul>
 * <li>Block access to users that are not Network Administrators</li>
 * <li>Disable Feedback</li>
 * <li>Prevent WordPress.com user login</li>
 * <li>Removed Dashboard Widget</li>
 * </ul>
 *
 * @zendesk No Zendesk ticket submitted due to JetPack Plugin being a WPVIP requirement.
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2160
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2161
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2162
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2163
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2171
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2175
 * 
 * @param  array $modules JetPack Modules.
 * @return array
 */
function caweb_vip_disable_jetpack_modules( $modules ) {
	/*
		JetPack reference links
		https://jetpack.com/support/module-overrides/
		https://github.com/Automattic/vip-go-mu-plugins/blob/e1802e01acd8be4bf95b87fe6be55597bf7ad88f/vip-jetpack/jetpack-mandatory.php#L20-L21
	 */
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
 * @see caweb_vip_disable_jetpack_modules( $modules )
 * 
 * @return void
 */
function caweb_vip_admin_head() {
	?>
		<style>
			div#jetpack_summary_widget.postbox{ display: none; }
		</style>
	<?php

}

