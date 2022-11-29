<?php
/**
 * CAWeb VIP NewRelic
 *
 * @package CAWebVIP
 */

 if( ! is_multisite() ){
    return;
 }

add_action( 'init', 'caweb_vip_new_relic', -1 );
add_action( 'network_admin_menu', 'caweb_vip_new_relic_menu', 20 );


/**
 * CAWeb VIP Administration Menu Setup
 * Fires before the administration menu loads in the admin.
 *
 * @link https://developer.wordpress.org/reference/hooks/admin_menu/
 * @return void
 */
function caweb_vip_new_relic_menu(){
	$cap = 'manage_network_options';

	add_submenu_page( 'caweb-vip', 'CAWeb VIP', 'New Relic', $cap, 'caweb-vip-new-relic', 'caweb_vip_new_relic_plugin_options' );

}

/**
 * Setup CAWeb VIP New Relic Options
 *
 * @return void
 */
function caweb_vip_new_relic_plugin_options(){

    if( isset( $_POST['caweb_vip_new_relic_settings'] ) &&
	wp_verify_nonce( sanitize_key( $_POST['caweb_vip_new_relic_settings'] ), 'caweb_vip_new_relic_settings' ) ){
        caweb_vip_save_new_relic_settings();
    }

    $apm_services_ids = get_site_option('caweb_vip_new_relic_apms', array());
    $apm_services = ! empty( $apm_services_ids ) ? get_sites( array('site__in' => $apm_services_ids ) ) : array();

    // available sites, any site not already added, deleted or the main root site.
    $sites = get_sites(array('deleted' => 0, 'site__not_in' => array_merge( $apm_services_ids, array(1) )));

	global $wp;

    $nonce = wp_create_nonce( 'caweb_vip_new_relic_settings' );

?>
	<div class="container-fluid mt-4 d-grid">
		<form id="caweb-vip-options-form" action="<?php print esc_url( add_query_arg( home_url( $wp->request ), $wp->query_vars ) ); ?>" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="caweb_vip_new_relic_settings" value="<?php print esc_attr( $nonce ); ?>" />

			<div class="form-row">
				<div class="form-group col-sm-12">
					<h3 class="d-inline">Add a new APM Service</h3>
					<p class="mb-0"></p>
					<p class="mb-0"><span class="font-weight-bold">Sites</span> <span class="text-danger">*</span></p>
					<select class="form-control d-inline" name="caweb_vip_sites" required>
                        <?php if( ! empty($sites) ) : ?>
						<?php foreach ( $sites as $site ) : ?>
						<option value="<?php print esc_attr( $site->blog_id ); ?>"><?php print esc_url( $site->domain . $site->path ); ?></option>
						<?php endforeach; ?>
                        <?php else: ?>
                        <option>No Sites</option>
                        <?php endif; ?>
					</select>
                    <span id="add-apm-service" class="dashicons dashicons-plus align-middle"></span>
				</div>
			</div>
            
            <!-- any apm services -->
			<div class="form-row">
				<div class="form-group">
					<h3 class="d-inline">New Relic APM Services</h3>
                    <ul id="apm-services">
                    <?php foreach ( $apm_services as $site ) : ?>
						<li>
                            <span class="dashicons dashicons-minus align-middle mr-2 text-danger"></span>
                            <input type="hidden" name="apm-services[]" value="<?php print esc_attr( $site->id ); ?>" />
                            <?php print esc_url( $site->domain . $site->path ); ?>
                        </li>
					<?php endforeach; ?>
                    </ul>
				</div>
			</div>
            
			<div class="form-row">
				<input type="submit" name="caweb_options_submit" class="button button-primary mt-2<?php print esc_attr( $hide_save ); ?>" value="Save Changes">
			</div>

		</form>
	</div>
    <?php
}

/**
 * Save CAWeb VIP New Relic Settings
 *
 * @return void
 */
function caweb_vip_save_new_relic_settings() {

	$services   = isset( $_POST['apm-services'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['apm-services'] ) ) : array();
	
    update_site_option('caweb_vip_new_relic_apms', $services );

    print '<div class="notice notice-success is-dismissible"><p><strong>CAWeb VIP</strong> changes updated successfully.</p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';

}

/**
 * Maybe set an application name for New Relic.
 *
 * Default NR buckets all muti-sites into a single newrelic instance. This is good for a hollistic view, 
 * but not per-site auditing. Specifying an app-name per site will allow auditing of individual sites.
 * Specifying an app-name on only a percentage of requests, should be the best of both worlds.
 *
 * @link: https://docs.wpvip.com/technical-references/new-relic-for-wordpress/#h-separate-apps-out-on-a-per-site-basis-for-multisite
 */
function caweb_vip_new_relic() {
    
    $apm_services_ids = get_site_option('caweb_vip_new_relic_apms', array());
    $apm_services = ! empty( $apm_services_ids ) ? get_sites( array('site__in' => $apm_services_ids ) ) : array();

	$new_relic_apms = array_map(function($val){
		return $val->domain . $val->path;
	}, $apm_services );

    $parsed_site_url = wp_parse_url( site_url() );
    $path            = $parsed_site_url['path'] ?? '';
	$current_site = $_SERVER['HTTP_HOST'] . $path;

    if ( ! is_main_site() && in_array( "$current_site/", $new_relic_apms, true ) ) {
        newrelic_set_appname( $current_site );
    }
}

?>