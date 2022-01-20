<?php
/**
 * CAWeb WP Ajax
 *
 * @see https://codex.wordpress.org/AJAX_in_Plugins
 * @package CAWeb VIP
 */

add_action( 'wp_ajax_caweb_vip_get_unfrozen_sites', 'caweb_vip_get_unfrozen_sites' );
add_action( 'wp_ajax_caweb_vip_recalc_disc_estimate', 'caweb_vip_recalc_disc_estimate' );
add_action( 'wp_ajax_caweb_vip_reset_transients', 'caweb_vip_reset_transients' );
add_action( 'wp_ajax_caweb_vip_gather_analytics', 'caweb_vip_gather_analytics' );

/**
 * Returns an JSON of sites not frozen
 *
 * @return void
 */
function caweb_vip_get_unfrozen_sites() {
	$sites    = caweb_vip_get_sites_listing_options( 0, array(), array( 'domain' ) );
	$froze    = get_site_option( 'frozen_sites', array() );
	$unfrozen = array();

	foreach ( $sites as $d ) {
		if ( ! array_key_exists( $d['domain'], $froze ) ) {
			$unfrozen[] = $d['domain'];
		}
	}

	wp_send_json( $unfrozen );
	wp_die(); // this is required to terminate immediately and return a proper response.
}

/**
 * Recalculates Site Disc Estimates
 *
 * @return void
 */
function caweb_vip_recalc_disc_estimate() {
	if ( ! isset( $_POST['nonce'] ) ||
		( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'caweb_vip_settings' ) ) ) {
			wp_die();
	}

	$sites = caweb_vip_get_sites_listing_options( 0, array( 'siteurl' ), array( 'blogname' ) );
	$free  = isset( $_POST['free'] ) ? sanitize_text_field( wp_unslash( $_POST['free'] ) ) : '';
	$cost  = isset( $_POST['cost'] ) ? sanitize_text_field( wp_unslash( $_POST['cost'] ) ) : '';

	foreach ( $sites as $id => $data ) {
		$sites[ $id ]['folder_info'] = caweb_vip_get_site_folder_size(
			$id,
			array(
				'max_size' => $free,
				'cost'     => $cost,
			)
		);
	}

	wp_send_json( $sites );
	wp_die(); // this is required to terminate immediately and return a proper response.
}

/**
 * Resets CAWeb Theme Feed Transient
 *
 * @return void
 */
function caweb_vip_reset_transients() {
	if ( function_exists( 'caweb_refresh_news_feed' ) ) {
		delete_site_transient( 'caweb_news_feed' );
		caweb_refresh_news_feed();
	}
	$h                  = get_blog_option( 1, 'gmt_offset' );
	$expiration         = get_site_option( '_site_transient_timeout_caweb_news_feed', '' );
	$expiration_date    = ! empty( $expiration ) ? sprintf( 'Expiration: %1$s', gmdate( 'M d, Y @ g:i:sa', $expiration + ( $h * 3600 ) ) ) : 'Expiration: Not Set';
	$last_modified_date = ! empty( $expiration ) ? sprintf( 'Last Set: %1$s', gmdate( 'M d, Y @ g:i:sa', ( $expiration + ( $h * 3600 ) ) - 86400 ) ) : 'Last Set: Not Set';

	print esc_html( "( $last_modified_date, $expiration_date )" );
	wp_die(); // this is required to terminate immediately and return a proper response.
}

/**
 * Gather Module Analytics
 *
 * @return void
 */
function caweb_vip_gather_analytics() {
	try {
		if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'caweb_vip_export_analytics' ) ) {
			exit();
		}

		$blog = isset( $_POST['blog'] ) ? sanitize_text_field( wp_unslash( $_POST['blog'] ) ) : 1;

		caweb_vip_get_module_analytics( $blog, $module_total );

		arsort( $module_total );

		wp_send_json( $module_total );
		wp_die(); // this is required to terminate immediately and return a proper response.

	} catch ( Exception $e ) {
		print 'Caught exception: ' . esc_html( $e->getMessage() ) . "\n";
	}

	exit();
}
