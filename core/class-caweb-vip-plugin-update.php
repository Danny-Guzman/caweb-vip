<?php
/**
 * CAWeb VIP
 *
 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/update.php
 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/class-theme-upgrader.php
 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/class-wp-upgrader.php
 *
 * @package CAWeb VIP
 */

if ( ! class_exists( 'CAWeb_VIP_Plugin_Update' ) ) {
	/**
	 * CAWeb VIP Plugin Upgrader
	 */
	class CAWeb_VIP_Plugin_Update {

		/**
		 * Member Variable
		 *
		 * @var array $plugin_name Plugin name.
		 */
		protected $plugin_name;
		/**
		 * Member Variable
		 *
		 * @var string $current_version Current Plugin Version.
		 */
		protected $current_version;

		/**
		 * Member Variable
		 *
		 * @var string $slug Plugin Slug name.
		 */
		protected $slug;

		/**
		 * Member Variable
		 *
		 * @var string $plugin_file Plugin filename.
		 */
		protected $plugin_file;

		/**
		 * Member Variable
		 *
		 * @var string $transient_name Name of update transient.
		 */
		protected $transient_name = 'caweb_update_plugins';

		/**
		 * Member Variable
		 *
		 * @var array $args Contains Header arguments used during the update process.
		 */
		protected $args;

		/**
		 * Initialize a new instance of the WordPress Auto-Update class
		 *
		 * @param string $plugin_slug Plugin slug name.
		 */
		public function __construct( $plugin_slug ) {
			$plugin_data = get_plugin_data( sprintf( '%1$s/%2$s/%2$s.php', WP_PLUGIN_DIR, $plugin_slug ) );

			// Set the class public variables.
			$this->current_version = $plugin_data['Version'];
			$this->plugin_name     = $plugin_data['Name'];
			$this->slug            = $plugin_slug;
			$this->plugin_file     = sprintf( '%1$s/%1$s.php', $plugin_slug );

			$this->args = array(
				'headers' => array(
					'User-Agent' => 'WP-' . $this->plugin_name,
					'Accept:'    => 'application/vnd.github.v3+json',
					'application/vnd.github.VERSION.raw',
					'application/octet-stream',
				),
			);

			if ( get_site_option( 'caweb_vip_private_plugin_enabled', false ) ) {
				$this->args['headers']['Authorization'] = 'Basic ' . base64_encode( ':' . get_site_option( 'caweb_vip_password', '' ) );
			}

			// define the alternative API for plugin update checking.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'caweb_check_plugin_update' ) );

			// Define the alternative response for information checking.
			add_filter( 'site_transient_update_plugins', array( $this, 'caweb_add_plugins_to_update_notification' ) );

			add_filter( 'plugins_api', array( $this, 'caweb_update_plugins_changelog' ), 20, 3 );

			// Define the alternative response for download_package which gets called during theme upgrade.
			add_filter( 'upgrader_pre_download', array( $this, 'download_package' ), 10, 3 );

			// Define the alternative response for upgrader_pre_install.
			add_filter( 'upgrader_source_selection', array( $this, 'caweb_admin_upgrader_source_selection' ), 10, 4 );

		}

		/**
		 * Alternative theme download for the WordPress Updater
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/class-wp-upgrader.php#L265
		 *
		 * @param  bool        $reply Whether to bail without returning the package. Default false.
		 * @param  string      $package The package file name.
		 * @param  WP_Upgrader $upgrader The WP_Upgrader instance.
		 *
		 * @return string
		 */
		public function download_package( $reply, $package, $upgrader ) {
			if ( isset( $upgrader->skin->plugin_info ) && $upgrader->skin->plugin_info['Name'] === $this->plugin_name ) {
				$theme = wp_remote_retrieve_body( wp_remote_get( $package, array_merge( $this->args, array( 'timeout' => 60 ) ) ) );
				global $wp_filesystem;
				$filename = sprintf( '%1$s/%2$s.zip', plugin_dir_path( __DIR__ ), $this->slug );
				$wp_filesystem->put_contents( $filename, $theme );

				return sprintf( '%1$s/%2$s.zip', plugin_dir_path( __DIR__ ), $this->slug );
			}
			return $reply;
		}

		/**
		 * Alternative API for checking for plugin updates.
		 *
		 * @param  array|Object $update_transient Transient containing plugin updates.
		 *
		 * @return array
		 */
		public function caweb_check_plugin_update( $update_transient ) {
			if ( ! isset( $update_transient->checked ) ) {
				return $update_transient;
			}

			$plugins = $update_transient->checked;

			$last_update = new stdClass();

			$payload = wp_remote_get( sprintf( 'https://api.github.com/repos/%1$s/%2$s/releases/latest', get_site_option( 'caweb_vip_username', '' ), str_replace( ' ', '-', $this->plugin_name ) ), $this->args );

			if ( ! is_wp_error( $payload ) && wp_remote_retrieve_response_code( $payload ) === 200 ) {
				$payload = json_decode( wp_remote_retrieve_body( $payload ) );

				if ( ! empty( $payload ) && version_compare( $payload->tag_name, $this->current_version, '>' ) ) {
					$obj                 = new StdClass();
					$obj->name           = $this->plugin_name;
					$obj->slug           = $this->slug;
					$obj->plugin         = $this->plugin_file;
					$obj->new_version    = $payload->tag_name;
					$obj->published_date = ( new DateTime( $payload->published_at ) )->format( 'm/d/Y' );
					$obj->package        = str_replace( 'zipball', 'zipball/refs/tags', $payload->zipball_url );
					$obj->tested         = '5.8.0';

					$theme_response = array( $this->plugin_file => $obj );

					$update_transient->response = array_merge( ! empty( $update_transient->response ) ? $update_transient->response : array(), $theme_response );

					$last_update->checked  = $plugins;
					$last_update->response = $theme_response;
				} else {
					delete_site_transient( $this->transient_name );
				}
			}

			$last_update->last_checked = time();
			set_site_transient( $this->transient_name, $last_update );

			return $update_transient;
		}

		/**
		 * Adds the CAWeb Plugin Update Notification to List of Available Updates.
		 *
		 * @param  array $update_transient Transient containing plugin updates.
		 *
		 * @return array
		 */
		public function caweb_add_plugins_to_update_notification( $update_transient ) {
			$caweb_update_plugins = get_site_transient( $this->transient_name );
			if ( ! is_object( $caweb_update_plugins ) || ! isset( $caweb_update_plugins->response ) ) {
				return $update_transient;
			}
			// Fix for warning messages on Dashboard / Updates page.
			if ( ! is_object( $update_transient ) ) {
				$update_transient = new stdClass();
			}

			$update_transient->response = array_merge(
				! empty( $update_transient->response ) ? $update_transient->response : array(),
				$caweb_update_plugins->response
			);

			return $update_transient;
		}

		/**
		 * Filters the response for the current WordPress.org Plugin Installation API request.
		 *
		 * @param  false|object|array $result The result object or array. Default false.
		 * @param  string             $action The type of information being requested from the Plugin Installation API.
		 * @param  object             $args Plugin API arguments.
		 *
		 * @return false|object|array
		 */
		public function caweb_update_plugins_changelog( $result, $action, $args ) {
			if ( isset( $args->slug ) && $args->slug === $this->slug ) {
				$caweb_update_plugins = get_site_transient( $this->transient_name );
				if ( isset( $caweb_update_plugins->response ) && isset( $caweb_update_plugins->response[ $this->plugin_file ] ) ) {
					$tmp = $this->plugin_details();

					$tmp['version']      = $caweb_update_plugins->response[ $this->plugin_file ]->new_version;
					$tmp['last_updated'] = $caweb_update_plugins->response[ $this->plugin_file ]->published_date;

					$tmp['sections']['Changelog'] = $this->caweb_vip_get_plugin_changelog( $tmp['version'] );

					return (object) $tmp;
				}
			}
			return $result;
		}

		/**
		 * Retrieve Plugin Changelog from repository
		 *
		 * @param  string $ver Repository branch.
		 *
		 * @return string
		 */
		public function caweb_vip_get_plugin_changelog( $ver = 'master' ) {
			$logurl = sprintf( '%1$s/contents/changelog.txt?ref=%2$s', $this->repo, $ver );

			$changelog = wp_remote_get( $logurl, $this->args );

			if ( ! is_wp_error( $changelog ) && 200 === wp_remote_retrieve_response_code( $changelog ) ) {
				return sprintf( '<pre style="white-space: pre-line !important;">%1$s</pre>', base64_decode( json_decode( wp_remote_retrieve_body( $changelog ) )->content ) );
			} else {
				return 'No Changelog Available';
			}
		}

		/**
		 * Filters the source file location for the upgrade package.
		 *
		 * @see https://github.com/WordPress/WordPress/blob/master/wp-admin/includes/class-wp-upgrader.php#L524
		 *
		 * @param  string      $src File source location.
		 * @param  string      $rm_src Remote file source location.
		 * @param  WP_Upgrader $upgr WP_Upgrader instance.
		 * @param  array       $options Extra arguments passed to hooked filters.
		 *
		 * @return string
		 */
		public function caweb_admin_upgrader_source_selection( $src, $rm_src, $upgr, $options ) {

			if ( ! isset( $options['plugin'] ) || $options['plugin'] !== $this->plugin_file ) {
				return $src;
			}

			$tmp = explode( '/', $src );
			array_shift( $tmp );
			array_pop( $tmp );
			$tmp[ count( $tmp ) - 1 ] = $tmp[ count( $tmp ) - 2 ];
			$tmp                      = sprintf( '/%1$s/', implode( '/', $tmp ) );

			rename( $src, $tmp );

			return $tmp;
		}

		/**
		 * Plugin Details
		 *
		 * @return array
		 */
		public function plugin_details() {
			$view_details = array(
				'slug'     => plugin_basename( plugin_dir_path( __DIR__ ) ),
				'author'   => 'Jesus D. Guzman',
				'name'     => sprintf( '<img src="%1$s/%2$s/logo.png" class="caweb-vip-plugin-update-logo"> CAWeb VIP', WP_PLUGIN_URL, plugin_basename( plugin_dir_path( __DIR__ ) ) ),
				'sections' => array(
					'Description' => '<p>A CAWeb Utility Plugin that allows for extra mime-type support not supported by WordPress. Also allows for mapping registered domains to their appropriate urls.</p><p>The following extra mime-types have been registered:</p><ul><li>application/x-mspublisher</li><li>application/vnd.ms-infopath</li><li>text/xml</li><li>application/vnd.google-earth.kml+xml</li><li>application/vnd.google-earth.kmz</li><li>x-image/x-icon</li></ul>',
				),
				'requires' => '4.7.0',
			);

			return $view_details;
		}

	}
}

new CAWeb_VIP_Plugin_Update( plugin_basename( plugin_dir_path( __DIR__ ) ) );

