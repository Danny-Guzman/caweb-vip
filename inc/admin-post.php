<?php
/**
 * CAWeb VIP Post
 *
 * @see https://developer.wordpress.org/reference/hooks/admin_post_action/
 * @package CAWeb VIP
 */

add_action( 'admin_post_caweb_vip_download_csv', 'caweb_vip_download_disc_report' );

/**
 * Downloads Site Disc Report CSV file
 *
 * @return void
 */
function caweb_vip_download_disc_report() {
	try {
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'caweb_vip_disc_estimator_download' ) ) {
			exit();
		}

		$content = 'Name,Overage in Gigabytes,Overage Cost' . PHP_EOL;

		$data_cost = isset( $_GET['cost'] ) ? sanitize_text_field( wp_unslash( $_GET['cost'] ) ) : get_site_option( 'caweb_datacost', 2.00 );
		$free_data = isset( $_GET['free'] ) ? sanitize_text_field( wp_unslash( $_GET['free'] ) ) : get_site_option( 'caweb_datafree', 1 );

		$sites                = caweb_vip_get_sites_listing_options( 0, array( 'siteurl' ), array( 'blogname' ) );
		$site_opts            = array(
			'max_size' => $free_data,
			'cost'     => $data_cost,
		);
		$site_opts['combine'] = is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ? false : true;
		foreach ( $sites as $id => $data ) {
			$sites[ $id ]['folder_info'] = caweb_vip_get_site_folder_size( $id, $site_opts, $sites );
			$content                    .= sprintf( '%1$s,%2$s,$%3$s%4$s', $sites[ $id ]['blogname'], $sites[ $id ]['folder_info']['overage'], $sites[ $id ]['folder_info']['overage_cost'], PHP_EOL );
		}

		header( 'Content-type: text/plain' );
		header( 'Content-Disposition: attachment; filename="caweb_disc_report.csv"' );
		header( 'Content-Length: ' . strlen( $content ) );

		print esc_html( $content );
	} catch ( Exception $e ) {
		print 'Caught exception: ' . esc_html( $e->getMessage() ) . "\n";
	}

	exit();
}