<?php
/**
 * CAWeb WP Ajax
 *
 * @see https://codex.wordpress.org/AJAX_in_Plugins
 * @package CAWeb VIP
 */

add_action( 'wp_ajax_caweb_vip_clear_cache', 'caweb_vip_clear_cache' );
add_action( 'wp_ajax_caweb_vip_clear_all_cache', 'caweb_vip_clear_all_cache' );

add_action( 'wp_ajax_wpforms_tools_entries_export_step', 'caweb_vip_wp_ajax_wpforms_tools_entries_export_step' );

/**
 * Ajax endpoint for WPForms Entries export processing.
 *
 * @return void
 */
function caweb_vip_wp_ajax_wpforms_tools_entries_export_step() {
    global $wp_filesystem;

    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ) {
        $creds = request_filesystem_credentials( site_url() );
        wp_filesystem( $creds );
    }
}


/**
 * Clears the cache for a specific page or object
 *
 * @return void
 */
function caweb_vip_clear_cache() {
	if ( ! isset( $_POST['nonce'] ) ||
		( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'caweb_vip_settings' ) ) ) {
			wp_die();
	}

	$url  = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'php';

	if ( ! empty( $url ) ) {
		$function = 'php' === $type ? 'wpcom_vip_purge_edge_cache_for_post' : 'wpcom_vip_purge_edge_cache_for_url';

		if ( function_exists( $function ) ) {
			call_user_func( $function, $url );
		}
	}

	wp_send_json( true );
	wp_die(); // this is required to terminate immediately and return a proper response.
}

/**
 * Clears the cache for a whole site
 *
 * @return void
 */
function caweb_vip_clear_all_cache() {
	if ( ! isset( $_POST['nonce'] ) ||
		( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'caweb_vip_settings' ) ) ) {
			wp_die();
	}

	$blog_id = isset( $_POST['blog_id'] ) ? sanitize_text_field( wp_unslash( $_POST['blog_id'] ) ) : '';
	$multi   = is_multisite();

	if ( ! empty( $blog_id ) ) {
		if ( $multi ) {
			switch_to_blog( $blog_id );
		}
		$posts = get_posts(
			array(
				'post_type'   => array( 'page', 'post', 'attachment' ),
				'post_status' => 'publish',
			)
		);

		foreach ( $posts as $post ) {
			$function = 'attachment' === $post->post_type ? 'wpcom_vip_purge_edge_cache_for_url' : 'wpcom_vip_purge_edge_cache_for_post';

			if ( function_exists( $function ) ) {
				call_user_func( $function, $post->guid );
			}
		}

		if ( $multi ) {
			restore_current_blog();
		}
	}

	wp_send_json( true );
	wp_die(); // this is required to terminate immediately and return a proper response.
}
