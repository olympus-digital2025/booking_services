<?php
/**
 * Plugin Name: Custom Plugin
 * Plugin URI: https://github.com/MBNDEV/custom-plugin
 * Description: Custom Plugin for MBN
 * Version: 1.0.7
 * Author: My Biz Niche
 * Author URI: https://www.mybizniche.com/
 * License: GPL2
 * Text Domain: custom-plugin
 *
 * @package CustomPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
  require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
PucFactory::buildUpdateChecker(
  'https://github.com/MBNDEV/custom-plugin',
  __FILE__,
  'custom-plugin'
);

// Load plugin classes
require_once plugin_dir_path( __FILE__ ) . 'includes/database/class-database.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-booking-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-location-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/post-types/class-service-post-type.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/class-frontend.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/class-shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-rest-api.php';

use CustomPlugin\Database\Database;
use CustomPlugin\Classes\Roles;
use CustomPlugin\PostTypes\Service_Post_Type;
use CustomPlugin\Admin\Admin;
use CustomPlugin\Frontend\Frontend;
use CustomPlugin\Frontend\Shortcodes;
use CustomPlugin\API\REST_API;

/**
 * Plugin activation hook
 */
function cp_on_activation() {
	Database::create_tables();
	Roles::create_roles();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cp_on_activation' );

/**
 * Plugin deactivation hook
 */
function cp_on_deactivation() {
	Roles::remove_roles();
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cp_on_deactivation' );

/**
 * Plugin initialization
 */
function cp_init() {
	// Register post types.
	Service_Post_Type::register();
	Service_Post_Type::register_category_taxonomy();
	Service_Post_Type::register_booking();

	// Initialize admin.
	Admin::init();

	// Initialize frontend.
	Frontend::init();
	Shortcodes::init();

	// Initialize REST API.
	REST_API::init();

	// Flush rewrite rules on first activation.
  if ( get_option( 'cp_flush_rules' ) ) {
      flush_rewrite_rules();
      delete_option( 'cp_flush_rules' );
  }
}
add_action( 'init', 'cp_init' );

/**
 * Set flush rewrite rules on activation
 */
add_action(
  'cp_on_activation',
  function () {
	add_option( 'cp_flush_rules', 1 );
  }
);

/**
 * Add meta boxes for service post type
 */
function cp_add_service_meta_boxes() {
	add_meta_box(
      'cp-service-price',
      'Service Price',
      'cp_render_service_price_meta_box',
      'service',
      'normal',
      'high'
	);

	add_meta_box(
      'cp-service-duration',
      'Service Duration',
      'cp_render_service_duration_meta_box',
      'service',
      'normal',
      'high'
	);
}
add_action( 'add_meta_boxes', 'cp_add_service_meta_boxes' );

/**
 * Render service price meta box
 *
 * @param \WP_Post $post Post object.
 */
function cp_render_service_price_meta_box( $post ) {
	wp_nonce_field( 'cp_service_nonce', 'cp_service_nonce' );
	$price = get_post_meta( $post->ID, '_service_price', true );
  ?>
	<label for="cp-price">Price ($):</label>
	<input type="number" id="cp-price" name="cp_service_price" value="<?php echo esc_attr( $price ); ?>" step="0.01" style="width: 100%; padding: 8px;">
	<?php
}

/**
 * Render service duration meta box
 *
 * @param \WP_Post $post Post object.
 */
function cp_render_service_duration_meta_box( $post ) {
	$duration = get_post_meta( $post->ID, '_service_duration', true );
  ?>
	<label for="cp-duration">Duration (hours):</label>
	<input type="number" id="cp-duration" name="cp_service_duration" value="<?php echo esc_attr( $duration ); ?>" step="0.5" style="width: 100%; padding: 8px;">
	<?php
}

/**
 * Save service meta box data
 *
 * @param int $post_id Post ID.
 */
function cp_save_service_meta( $post_id ) {
  if ( ! isset( $_POST['cp_service_nonce'] ) || ! wp_verify_nonce( $_POST['cp_service_nonce'], 'cp_service_nonce' ) ) {
      return;
  }

  if ( ! current_user_can( 'edit_post', $post_id ) ) {
      return;
  }

  if ( isset( $_POST['cp_service_price'] ) ) {
      update_post_meta( $post_id, '_service_price', floatval( $_POST['cp_service_price'] ) );
  }

  if ( isset( $_POST['cp_service_duration'] ) ) {
      update_post_meta( $post_id, '_service_duration', floatval( $_POST['cp_service_duration'] ) );
  }
}
add_action( 'save_post_service', 'cp_save_service_meta' );

/**
 * Add columns to service post type
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function cp_add_service_columns( $columns ) {
	$columns['service_price']    = 'Price';
	$columns['service_duration'] = 'Duration';
	return $columns;
}
add_filter( 'manage_service_posts_columns', 'cp_add_service_columns' );

/**
 * Display service column values
 *
 * @param string $column Column name.
 * @param int    $post_id Post ID.
 */
function cp_display_service_column( $column, $post_id ) {
  if ( 'service_price' === $column ) {
      $price = get_post_meta( $post_id, '_service_price', true );
      echo '$' . esc_html( number_format( $price, 2 ) );
  } elseif ( 'service_duration' === $column ) {
      $duration = get_post_meta( $post_id, '_service_duration', true );
      echo esc_html( $duration . ' hours' );
  }
}
add_action( 'manage_service_posts_custom_column', 'cp_display_service_column', 10, 2 );

/**
 * Load plugin translations
 */
function cp_load_textdomain() {
	load_plugin_textdomain( 'custom-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'cp_load_textdomain' );
