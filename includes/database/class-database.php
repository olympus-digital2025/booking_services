<?php
/**
 * Database schema and initialization
 *
 * @package CustomPlugin\Database
 */

namespace CustomPlugin\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database class for managing custom tables.
 */
class Database {

	/**
	 * Create necessary tables on plugin activation.
	 */
  public static function create_tables() {
      global $wpdb;

      $charset_collate = $wpdb->get_charset_collate();

      // Bookings table
      $bookings_table = "
		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}service_bookings (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id VARCHAR(50) NOT NULL UNIQUE,
			service_id BIGINT(20) UNSIGNED NOT NULL,
			worker_id BIGINT(20) UNSIGNED NOT NULL,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
			scheduled_date DATETIME,
			service_location VARCHAR(255),
			service_latitude DECIMAL(10, 8),
			service_longitude DECIMAL(11, 8),
			status VARCHAR(50) DEFAULT 'pending',
			total_amount DECIMAL(10, 2),
			notes LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY worker_id (worker_id),
			KEY customer_id (customer_id),
			KEY service_id (service_id),
			KEY status (status)
		) $charset_collate;
		";

      // Worker services location table
      $worker_locations = "
		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}worker_service_locations (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			worker_id BIGINT(20) UNSIGNED NOT NULL,
			service_id BIGINT(20) UNSIGNED NOT NULL,
			location_name VARCHAR(255),
			latitude DECIMAL(10, 8),
			longitude DECIMAL(11, 8),
			service_radius_km DECIMAL(6, 2) DEFAULT 50,
			is_active TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY worker_id (worker_id),
			KEY service_id (service_id),
			UNIQUE KEY worker_service_location (worker_id, service_id)
		) $charset_collate;
		";

      // Worker ratings/reviews
      $ratings_table = "
		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}service_ratings (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT(20) UNSIGNED NOT NULL,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			worker_id BIGINT(20) UNSIGNED NOT NULL,
			service_id BIGINT(20) UNSIGNED NOT NULL,
			rating INT DEFAULT 5,
			review TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY worker_id (worker_id),
			KEY customer_id (customer_id),
			KEY booking_id (booking_id)
		) $charset_collate;
		";

      require_once ABSPATH . 'wp-admin/includes/upgrade.php';
      dbDelta( $bookings_table );
      dbDelta( $worker_locations );
      dbDelta( $ratings_table );
  }

	/**
	 * Drop tables on plugin deactivation.
	 */
  public static function drop_tables() {
      global $wpdb;

      $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}service_bookings" );
      $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}worker_service_locations" );
      $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}service_ratings" );
  }
}
