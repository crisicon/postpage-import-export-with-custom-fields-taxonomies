<?php
/**
 * Plugin Name: WPPIE – Post/Page Import Export (Maintained by Cris Iconomu)
 * Plugin URI: https://github.com/crisicon/wppie
 * Description: Secure, audited fork for importing/exporting posts and pages via JSON. Supports featured images, taxonomies, and custom fields.
 * Version: 2.0.0
 * Author: WPSpins (original), maintained by Cris Iconomu
 * Author URI: https://crisicon.com
 * Text Domain: wppie
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/crisicon/wppie
 * GitHub Branch: main
 * Forked from: https://wordpress.org/plugins/postpage-import-export-with-custom-fields-taxonomies/
 */


 use Inc\Base\PP_IMPORT_EXPORT_WPSPIN_Activate;
 use Inc\Base\PP_IMPORT_EXPORT_WPSPIN_Deactivate;
 
 // If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'PP_IMPORT_EXPORT_WPSPIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PP_IMPORT_EXPORT_WPSPIN_VERSION', '2.0.0' );

/**
 * Activate Post/Page import/export with custom fields & taxonomies plugin.
 *
 * @return void
 */
function pp_wpspin_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/Base/class-pp-wpspin-activate.php';
	PP_IMPORT_EXPORT_WPSPIN_Activate::activate();
}
register_activation_hook( __FILE__, 'pp_wpspin_activate' );

/**
 * Deactivate Post/Page import/export with custom fields & taxonomies plugin.
 *
 * @return void
 */
function pp_wpspin_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/Base/class-pp-wpspin-deactivate.php';
	PP_IMPORT_EXPORT_WPSPIN_Deactivate::deactivate();
}
register_deactivation_hook( __FILE__, 'pp_wpspin_deactivate' );

add_action( 'plugins_loaded', 'pp_wpspin_plugin_loader', 1000 );

/**
 * Initialize Post/Page import/export with custom fields & taxonomies main classes
 *
 * @return void
 */
function pp_wpspin_plugin_loader() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/class-pp-wpspin-init.php';
	PP_IMPORT_EXPORT_WPSPIN_Init::register_services();
}

/**
 * Create custom log file.
 *
 * @param  mixed $message massage.
 * @param  mixed $log_file log file location.
 * @since  1.0.0
 * @return void
 */
function custom_logs( $message, $log_file = 'import' ) {
	if ( is_array( $message ) ) {
		$message = wp_json_encode( $message );
	}
	if ( ! file_exists( ABSPATH . 'log' ) ) {
		mkdir( ABSPATH . 'log', 0777, true );
	}
	$file = fopen( ABSPATH . 'log/' . $log_file . '.log', 'a' );
	echo esc_attr( fwrite( $file, "\n" . gmdate( 'Y-m-d h:i:s' ) . ' :: ' . $message ) );
	fclose( $file );
}
