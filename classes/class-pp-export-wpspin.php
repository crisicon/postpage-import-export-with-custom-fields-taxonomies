<?php
/**
 * Export functions
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    wpx-pp-import-export
 * @subpackage wpx-pp-import-export/classes
 */

if ( ! class_exists( 'PP_EXPORT_WPSPIN' ) && defined( 'ABSPATH' ) ) {
	/**
	 * PP_EXPORT_WPSPIN
	 *
	 * @since      1.0.0
	 */
	class PP_EXPORT_WPSPIN {
		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $version    The current version of this plugin.
		 */
		public $version = '1.1.0';
		/**
		 *  The plugin base name.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $plugin_basename    The plugin base name.
		 */
		public $plugin_basename;
		/**
		 *  The plugin dir.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      string    $plugin_dir    The plugin dir.
		 */
		public $plugin_dir;
		/**
		 *  This class instance.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @var      mix    $instance    This class instance.
		 */
		protected static $_instance = null;

		/**
		 * Main Plugin Instance
		 * Ensures only one instance of plugin is loaded or can be loaded.
		 *
		 * @since    1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Constructor
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

			$this->plugin_dir = plugin_dir_url( __DIR__ );
			$this->define( 'PP_IMPORT_EXPORT_WPSPIN_VERSION', $this->version );

			// load the hooks and functions.
			add_action( 'add_meta_boxes', array( $this, '_pp_wpspin_register_export_custom_meta_box' ) );
			add_action( 'admin_enqueue_scripts', array( $this, '_pp_wpspin_include_js_css' ) );
			add_action( 'wp_ajax_pp_wpspin_export_json', array( $this, '_pp_wpspin_export_json' ) );
		}

		/**
		 * Include custom js & css
		 *
		 * @since    1.0.0
		 */
		public function _pp_wpspin_include_js_css() {
			wp_enqueue_style( 'pp_wpspin_css', $this->plugin_dir . '/assets/css/pp_wpspin_custom_style.css', __FILE__, $this->version );
			wp_register_script( 'pp_wpspin_js', $this->plugin_dir . '/assets/js/pp_wpspin_custom.js', array( 'jquery' ), $this->version );
			wp_localize_script( 'pp_wpspin_js', 'pp_wpspin_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'pp_wpspin_js' );
		}

		/**
		 * Register custom Meta box
		 *
		 * @since    1.0.0
		 */
		public function _pp_wpspin_register_export_custom_meta_box() {
			$screens = array( 'post', 'page' );
			foreach ( $screens as $screen ) {
				$current_screen = get_current_screen();
				add_meta_box(
					'pp_wpspin_box_id',
					__( 'Export current post information' ),
					array( $this, '_pp_wpspin_export_button' ),
					$screen,
					'normal',
					'low'
				);
			}
		}
		/**
		 * Meta box callback function
		 *
		 * @since    1.0.0
		 * @param  mixed $post post.
		 */
		public function _pp_wpspin_export_button( $post ) {
			$post_id = $post->ID;

			printf(
				"<div class='pp_wpspin_download_box_wrap'>
                    <button type='button' id='pp_wpspin_export' data-id='%s' class='pp_wpspin_download'>Export Data</button> 
                </div>",
				esc_html( $post_id )
			);
			wp_nonce_field( basename( __FILE__ ), 'ppwpspinjson_nonce' );
			echo '<div class="pp_wpspin_download_box_wrap">Choose JSON file<input type="file" name="pp_wpspin_json_file" accept="application/JSON"> 
                </div>';
		}
		/**
		 * Register custom ajax
		 *
		 * @since    1.0.0
		 */
		public function _pp_wpspin_export_json() {
			$results = array();
			if ( isset( $_POST['post_id'] ) ) {
				$post_id = intval( wp_unslash( $_POST['post_id'] ) );
			} else {
				$post_id = null;
			}
			$post_type = get_post_type( $post_id );

			/*
			Get post data
			*/
			$post_data            = get_post( $post_id );
			$results['post_data'] = $post_data;

			/*
			Get post meta
			*/
			$post_meta            = get_post_meta( $post_id );
			$results['post_meta'] = $post_meta;

			/*
			Get post taxonomies
			*/
			$taxonomies            = get_object_taxonomies( $post_type );
			$term_list             = wp_get_post_terms( $post_id, $taxonomies, array( 'fields' => 'all' ) );
			$results['taxonomies'] = $term_list;

			/*
			Get post featured image
			*/
			$feature_img            = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
			$results['feature_img'] = $feature_img;

			/*
			Get ACF fields
			*/
			if ( function_exists( 'get_fields' ) ) {
				$acf_fields            = get_fields( $post_id );
				$results['acf_fields'] = $acf_fields;
			} else {
				$results['acf_fields'] = null;
			}

			echo json_encode( $results );
			exit;
		}
		/**
		 * Define constant if not already set
		 *
		 * @param  string      $name name.
		 * @param  string|bool $value value.
		 *
		 * @since    1.0.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

	}

} // class_exists
