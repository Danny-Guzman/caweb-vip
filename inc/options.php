<?php
/**
 * Main CAWeb VIP Options File
 *
 * @package CAWeb VIP
 */

add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'caweb_vip_plugin_menu' );

/**
 * CAWeb VIP Administration Menu Setup
 * Fires before the administration menu loads in the admin.
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_menu/
 * @return void
 */
function caweb_vip_plugin_menu() {
	$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
	add_menu_page( 'CAWeb VIP', 'CAWeb VIP', $cap, 'caweb-vip', 'caweb_vip_plugin_options', CAWEB_VIP_PLUGIN_URL . 'logo.png' );

	add_submenu_page( 'caweb-vip', 'CAWeb VIP', 'CAWeb VIP', $cap, 'caweb-vip', 'caweb_vip_plugin_options' );

}

/**
 * Setup CAWeb VIP Options
 *
 * @return void
 */
function caweb_vip_plugin_options() {

	$nonce = wp_create_nonce( 'caweb_vip_settings' );

	if ( ! wp_verify_nonce( $nonce, 'caweb_vip_settings' ) ) {
		exit();
	}

	if ( isset( $_POST['caweb_vip_submit'] ) ) {
		caweb_vip_save_plugin_settings();
	}
	global $wp;

	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'general';

	$hide_save    = in_array( $page, array(), true ) ? ' invisible' : '';

	$user_color = caweb_vip_get_user_color()->colors[2];

	?>
	<style>
		.menu-list li.list-group-item,
		.menu-list li.list-group-item:hover {
			background-color: 
			<?php
			print esc_attr( $user_color );
			?>
			!important;
		}

		.menu-list li.list-group-item:not(.selected) a {
			color: 
			<?php
			print esc_attr( $user_color );
			?>
			!important;
		}
	</style>
	<div class="container-fluid mt-4 d-grid">
		<form id="caweb-vip-options-form" action="<?php print esc_url( add_query_arg( home_url( $wp->request ), $wp->query_vars ) ); ?>" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="caweb_vip_settings_nonce" value="<?php print esc_attr( $nonce ); ?>" />

			<div class="row pr-3">
				<div class="col-12 bg-white border pt-2" id="caweb-vip-settings">
					<?php
					$display_function = sprintf( 'caweb_vip_display_%1$s', str_replace( '-', '_', $page ) );
					if ( function_exists( $display_function ) ) {
						call_user_func( $display_function );
					} else {
						caweb_vip_display_general();
					}
					?>
					<input type="hidden" name="caweb_vip_submit">
				</div>
			</div>
			<div class="row">
				<input type="submit" name="caweb_options_submit" class="button button-primary mt-2<?php print esc_attr( $hide_save ); ?>" value="Save Changes">
			</div>
		</form>
	</div>
	<?php
}

/**
 * Display General Settings for the current instance.
 *
 * @param  array $site_listing Array of Sites and various options with values.
 * @return void
 */
function caweb_vip_display_general( $site_listing = array() ) {
	caweb_vip_display_mime_types();
}

/**
 * Display Mime types enabled.
 *
 * @return void
 */
function caweb_vip_display_mime_types() {
	?>
	<!--div class="p-2 border-bottom border-secondary mb-2">
		<div class="form-row">
			<div class="form-group">
				<h2 class="d-inline">Mime Types</h2>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<h4 class="d-inline">WordPress</h4>
				<input type="text" class="form-control" readonly value="<?php print ''; ?>" />
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<h4 class="d-inline">CAWeb</h4>
				<input type="text" class="form-control" readonly value="<?php print ''; ?>" />
			</div>
		</div>
	</div-->

	<?php
}

/**
 * Save CAWeb VIP Plugin Settings
 *
 * @return void
 */
function caweb_vip_save_plugin_settings() {
	$verified = isset( $_POST['caweb_vip_settings_nonce'] ) &&
	wp_verify_nonce( sanitize_key( $_POST['caweb_vip_settings_nonce'] ), 'caweb_vip_settings' );

	$page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$blank = false;

	if ( $verified && ! empty( $page ) ) {
		switch ( $page ) {
			case 'alias-redirect':
				$aliases       = isset( $_POST['alias'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['alias'] ) ) : array();
				$aliases_url   = isset( $_POST['alias_url'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['alias_url'] ) ) : array();
				$registrations = array();

				foreach ( $aliases as $a => $name ) {
					if ( ! empty( trim( $name ) ) ) {
						$registrations[ $name ] = $aliases_url[ $a ];
					} else {
						$blank = true;
					}
				}

				update_site_option( 'registered_sites', $registrations );

				break;
			case 'api':
				$privated_enabled = isset( $_POST['caweb_vip_private_plugin_enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['caweb_vip_private_plugin_enabled'] ) ) : false;
				$privated_enabled = 'on' === $privated_enabled ? true : false;
				$username         = isset( $_POST['caweb_vip_username'] ) ? sanitize_text_field( wp_unslash( $_POST['caweb_vip_username'] ) ) : 1;
				$password         = isset( $_POST['caweb_vip_password'] ) ? sanitize_text_field( wp_unslash( $_POST['caweb_vip_password'] ) ) : 1;

				update_site_option( 'caweb_vip_private_plugin_enabled', $privated_enabled );
				update_site_option( 'caweb_vip_username', $username );
				update_site_option( 'caweb_vip_password', $password );

				break;
			case 'disc-estimator':
				$datacost = isset( $_POST['datacost'] ) ? sanitize_text_field( wp_unslash( $_POST['datacost'] ) ) : '2.00';
				$datafree = isset( $_POST['datafree'] ) ? sanitize_text_field( wp_unslash( $_POST['datafree'] ) ) : 1;

				update_site_option( 'caweb_datacost', $datacost );
				update_site_option( 'caweb_datafree', $datafree );

				break;
			case 'site-freeze':
				$frozen      = isset( $_POST['frozen_url'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['frozen_url'] ) ) : array();
				$frozen_urls = array();

				foreach ( $frozen as $i => $f ) {
					$start = isset( $_POST['frozen_url_start'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['frozen_url_start'][ $i ] ) ) : '';
					$end   = isset( $_POST['frozen_url_end'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['frozen_url_end'][ $i ] ) ) : '';

					$frozen_urls[ $f ] = array(
						'startdate'  => $start,
						'enddate'    => $end,
					);
				}

				update_site_option( 'frozen_sites', $frozen_urls );

				break;
			default:
				$limit = isset( $_POST['revision_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['revision_limit'] ) ) : 0;
				$region = isset( $_POST['default_region'] ) ? true : false;

				update_site_option( 'caweb_vip_default_region', $region );
				update_site_option( 'caweb_vip_revision_limit', $limit );
				break;
		}
		caweb_vip_mime_option_notices( $blank );
	} else {
		caweb_vip_mime_option_notices( true );
	}

}

/**
 * CAWeb VIP message hook
 *
 * @param  bool $error If there were any errors.
 *
 * @return void
 */
function caweb_vip_mime_option_notices( $error = false ) {
	if ( true === $error ) {
		print '<div class="notice notice-error is-dismissible"><p><strong>CAWeb VIP</strong> some changes could not be saved.</p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	} else {
		print '<div class="notice notice-success is-dismissible"><p><strong>CAWeb VIP</strong> changes updated successfully.</p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
	}
}
