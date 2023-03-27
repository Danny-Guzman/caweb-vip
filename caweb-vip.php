<?php
/**
 * Plugin Name: CAWeb WPVIP Integration Plugin
 * Plugin URI: "https://github.com/CA-CODE-Works/CAWeb-VIP/"
 * Description: Resolves several WPVIP Environment issues for the CAWebPublishing Service
 * Author: California Department of Technology
 * Author URI: "https://github.com/Danny-Guzman"
 * Version: 1.0.11
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
add_filter( 'et_core_cache_wpfs_args', 'caweb_vip_wpfs_credentials');

// If New Relic Extension is loaded.
if( extension_loaded( 'newrelic' ) ){
	require_once CAWEB_VIP_PLUGIN_DIR . 'core/newrelic.php';
}

/**
 * Divi Builder (New Version) does not load
 *
 * The New Divi Builder does not load properly due to the WPVIP FileSystem setup, Divi has applied the requested filter to their theme.
 *
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/144876
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/151700
 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2178
 * @p2 https://cdtp2.wordpress.com/2022/03/15/divi-builder-new-version-does-not-load/
 * 
 * @wp_filter add_filter( 'et_cache_wpfs_credentials', 'caweb_vip_wpfs_credentials');
 * @return bool|array
 */
function caweb_vip_wpfs_credentials(){
	return request_filesystem_credentials( site_url() );
}

/**
 * Divi Builder (New Version) does not load
 * 
 * @see caweb_vip_wpfs_credentials
 * @wp_action add_action( 'after_setup_theme', 'caweb_vip_after_setup_theme', 1 );
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
 * @wp_action add_action( 'init', 'caweb_vip_init', 9 );
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

	/**
	 * Restrict site access 
	 * 
	 * @see https://docs.wpvip.com/technical-references/restricting-site-access/access-controlled-files/
	 * 
	 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/149577
	 * @azure https://cawebpublishing.visualstudio.com/CAWeb/_workitems/edit/2267/
	 */
	add_filter('vip_files_acl_file_visibility', function(){
		global $wp;

        // Set conditions for private network sites.
        if ( '2' === get_option( 'blog_public' ) ) {
			// Suppress rsa messages.
			remove_action( 'admin_notices', 'Automattic\VIP\Blog_Public\notice' );

            // Allow access to files for logged-in users.
			if ( is_user_logged_in() ) {
                return Automattic\VIP\Files\Acl\FILE_IS_PRIVATE_AND_ALLOWED;
            }

			
            // Allow access to files if client IP is in the allowlist.
            if ( class_exists('Restricted_Site_Access') && null === Restricted_Site_Access::restrict_access_check( $wp ) ) {
                return Automattic\VIP\Files\Acl\FILE_IS_PRIVATE_AND_ALLOWED;
            }
 
            // Default to denying access to files for private network sites.
            return Automattic\VIP\Files\Acl\FILE_IS_PRIVATE_AND_DENIED;
 
		}

        // Default to files being public.
        return Automattic\VIP\Files\Acl\FILE_IS_PUBLIC;

	}, 20 );
	
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

	caweb_vip_allow_filename_lookup();

	if ( ! is_super_admin() ) {
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
		remove_all_actions( 'admin_notices' );

		add_action( 'admin_notices', 'wpcom_vip_two_factor_admin_notice' );

		if ( '2' === get_option( 'blog_public' ) ) {
			// Suppress rsa messages.
			remove_action( 'admin_notices', 'Automattic\VIP\Blog_Public\notice' );
		}
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

/**
 * Remove VIP action preventing core from doing filename lookups for media search.
 * 
 * @zendesk https://wordpressvip.zendesk.com/hc/en-us/requests/156784
 * 
 * @return void
 */
function caweb_vip_allow_filename_lookup(){
	remove_action( 'pre_get_posts', 'vip_filter_query_attachment_filenames' );
}
