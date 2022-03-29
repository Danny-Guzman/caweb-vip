<?php
/**
 * Main CAWeb VIP Options File
 *
 * @package CAWeb VIP
 */

add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'caweb_vip_plugin_menu', 15 );
add_action( 'admin_menu', 'caweb_vip_remove_admin_menus', 999 );

/**
 * CAWeb VIP Administration Menu Setup
 * Fires before the administration menu loads in the admin.
 *
 * @link   https://developer.wordpress.org/reference/hooks/admin_menu/
 * @return void
 */
function caweb_vip_plugin_menu() {
	$cap = is_multisite() ? 'manage_network_options' : 'manage_options';

	add_menu_page( 'CAWeb VIP', 'CAWeb VIP', $cap, 'caweb-vip', 'caweb_vip_plugin_options', CAWEB_VIP_PLUGIN_URL . 'logo.png' );

}

/**
 * CAWeb VIP Administration Menu Removal Setup
 * Fires before the administration menu loads in the admin.
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_menu/
 * @return void
 */
function caweb_vip_remove_admin_menus() {

	/* If Multisite instance & user is not a Network Admin */
	if ( is_multisite() && ! current_user_can( 'manage_network_options' ) ) {
		// Remove JetPack.
		remove_menu_page( 'jetpack' );
	}
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

	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'caweb-vip';

	$hide_save = in_array( $page, array( 'caweb-vip' ), true ) ? ' invisible' : '';

	$user_color = caweb_vip_get_user_color()->colors[2];

	?>
	<style>
		.menu-list li.list-group-item,
		.menu-list li.list-group-item:hover {
			background-color: 	<?php print esc_attr( $user_color ); ?> !important;
		}

		.menu-list li.list-group-item:not(.selected) a {
			color: <?php print esc_attr( $user_color ); ?> !important;
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
 * @return void
 */
function caweb_vip_display_general() {
	if ( is_multisite() ) {
		$sites = get_sites();
	} else {
		$sites = array(
			(object) array(
				'blog_id' => 1,
				'domain'  => get_site_url(),
			),
		);
	}

	?>
<div class="p-2 mb-2 border-bottom border-secondary">
		<div class="form-row">
			<div class="form-group">
				<h2 class="d-inline">Purge By URL</h2>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<p class="mb-0">1) Enter <strong>URL</strong> to be purged.</p>
				<p class="mb-0 ml-3"><span class="font-weight-bold">URL: <span class="text-danger">*</span></span> 
					<input type="text" class="form-control" name="caweb_vip_page_cache_url" placeholder="https://example.ca.gov/" required>
				</p>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<p class="mb-0">2) Select <strong>Cache Type</strong> <span class="text-danger">*</span></p>
				<p class="mb-0 ml-3">
					<label class="d-block" for="caweb_vip_page_cache_type_php">
						<input type="radio" checked="" class="form-control" name="caweb_vip_page_cache_type" required id="caweb_vip_page_cache_type_php" value="php">
						PHP
					</label>
					<label for="caweb_vip_page_cache_type_static">
						<input type="radio" class="form-control" name="caweb_vip_page_cache_type" required id="caweb_vip_page_cache_type_static" value="static">
						Static
					</label>
				</p>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<input type="button" id="caweb-vip-purge-url" class="ml-3 btn btn-primary" value="Purge">
				<div class="spinner-border d-none align-middle" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</div>
	</div>
	<div class="p-2 mb-2">
		<div class="form-row">
			<div class="form-group">
				<h2 class="d-inline">Purge ALL Site Cache</h2>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<p class="mb-0">1) Select a site.</p>
				<p class="mb-0 ml-3"><span class="font-weight-bold">Site</span> <span class="text-danger">*</span></p>
				<select class="ml-3 form-control" name="caweb_vip_site_cache_url" required>
					<?php foreach ( $sites as $site ) : ?>
					<option value="<?php print esc_attr( $site->blog_id ); ?>"><?php print esc_url( $site->domain ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<p class="mb-0 ml-3">
					<label class="d-block" for="caweb_vip_site_cache_confirm">
						<input type="checkbox" required="" class="form-control" name="caweb_vip_site_cache_confirm" id="caweb_vip_site_cache_confirm">
						Acknowledgement. By checking this box I acknowledge that I am going to purge ALL cache objects for the site listed above. <span class="text-danger">*</span>
					</label>
				</p>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				<input type="button" id="caweb-vip-purge-site" class="ml-3 btn btn-primary" value="Purge All">
				<div class="spinner-border d-none align-middle" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>
		</div>
	</div>
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

	$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	$notice = false;

	if ( $verified && ! empty( $page ) ) {
		switch ( $page ) {
			default:
				break;
		}
	}

	// caweb_vip_mime_option_notices( $notice ).
}

/**
 * CAWeb VIP message hook
 *
 * @param bool $error If there were any errors.
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
