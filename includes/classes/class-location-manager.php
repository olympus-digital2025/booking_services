<?php
/**
 * Location Manager class
 *
 * @package CustomPlugin\Classes
 */

namespace CustomPlugin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Location Manager class for geo-location based services.
 */
class Location_Manager {

	/**
	 * Add worker service location.
	 *
	 * @param int   $worker_id Worker ID.
	 * @param int   $service_id Service ID.
	 * @param array $data Location data (name, latitude, longitude, radius_km).
	 * @return int|false Location ID or false on failure.
	 */
  public static function add_service_location( $worker_id, $service_id, $data ) {
      global $wpdb;

      $insert_data = array(
          'worker_id'         => intval( $worker_id ),
          'service_id'        => intval( $service_id ),
          'location_name'     => isset( $data['location_name'] ) ? sanitize_text_field( $data['location_name'] ) : '',
          'latitude'          => isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : 0,
          'longitude'         => isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : 0,
          'service_radius_km' => isset( $data['service_radius_km'] ) ? floatval( $data['service_radius_km'] ) : 50,
          'is_active'         => 1,
      );

      $result = $wpdb->insert(
        "{$wpdb->prefix}worker_service_locations",
        $insert_data,
        array( '%d', '%d', '%s', '%f', '%f', '%f', '%d' )
      );

    if ( false !== $result ) {
        do_action( 'cp_service_location_added', $wpdb->insert_id, $worker_id, $service_id );
        return $wpdb->insert_id;
    }

      return false;
  }

	/**
	 * Update service location.
	 *
	 * @param int   $location_id Location ID.
	 * @param array $data Location data to update.
	 * @return bool True on success, false on failure.
	 */
  public static function update_service_location( $location_id, $data ) {
      global $wpdb;

      $update_data = array();

    if ( isset( $data['location_name'] ) ) {
        $update_data['location_name'] = sanitize_text_field( $data['location_name'] );
    }
    if ( isset( $data['latitude'] ) ) {
        $update_data['latitude'] = floatval( $data['latitude'] );
    }
    if ( isset( $data['longitude'] ) ) {
        $update_data['longitude'] = floatval( $data['longitude'] );
    }
    if ( isset( $data['service_radius_km'] ) ) {
        $update_data['service_radius_km'] = floatval( $data['service_radius_km'] );
    }
    if ( isset( $data['is_active'] ) ) {
        $update_data['is_active'] = intval( $data['is_active'] );
    }

    if ( empty( $update_data ) ) {
        return false;
    }

      $result = $wpdb->update(
        "{$wpdb->prefix}worker_service_locations",
        $update_data,
        array( 'id' => intval( $location_id ) ),
        null,
        array( '%d' )
      );

      return false !== $result;
  }

	/**
	 * Get worker service locations.
	 *
	 * @param int $worker_id Worker ID.
	 * @return array Array of locations.
	 */
  public static function get_worker_locations( $worker_id ) {
      global $wpdb;

      return $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}worker_service_locations 
				WHERE worker_id = %d AND is_active = 1
				ORDER BY location_name ASC",
          $worker_id
        )
      );
  }

	/**
	 * Get available services near a location.
	 *
	 * @param string $service_type Service category/type.
	 * @param float  $latitude Customer's latitude.
	 * @param float  $longitude Customer's longitude.
	 * @param int    $limit Number of results.
	 * @return array Array of workers and their services.
	 */
  public static function get_nearby_services( $service_type, $latitude, $longitude, $limit = 20 ) {
      global $wpdb;

      $service_type = sanitize_text_field( $service_type );
      $latitude     = floatval( $latitude );
      $longitude    = floatval( $longitude );
      // Using Haversine formula for distance calculation.
      // phpcs:ignore WordPress.DB.PreparedStatement.NotPrepared
      return $wpdb->get_results(
        $wpdb->prepare(
          "SELECT 
					wsl.*,
					u.ID as worker_id,
					u.user_login,
					u.user_email,
					p.ID as service_id,
					p.post_title as service_name,
					(111.111 * DEGREES(ACOS(
						LEAST(
							1,
							COS(RADIANS(%f)) * COS(RADIANS(wsl.latitude)) * COS(RADIANS(%f - wsl.longitude)) +
							SIN(RADIANS(%f)) * SIN(RADIANS(wsl.latitude))
						)
					))) as distance_km
				FROM {$wpdb->prefix}worker_service_locations wsl
				JOIN {$wpdb->users} u ON wsl.worker_id = u.ID
				JOIN {$wpdb->posts} p ON wsl.service_id = p.ID
				WHERE wsl.is_active = 1
				AND (111.111 * DEGREES(ACOS(
					LEAST(
						1,
						COS(RADIANS(%f)) * COS(RADIANS(wsl.latitude)) * COS(RADIANS(%f - wsl.longitude)) +
						SIN(RADIANS(%f)) * SIN(RADIANS(wsl.latitude))
					)
				))) <= wsl.service_radius_km
				ORDER BY distance_km ASC
				LIMIT %d",
          $latitude,
          $longitude,
          $latitude,
          $latitude,
          $longitude,
          $latitude,
          $limit
        )
      );
  }

	/**
	 * Calculate distance between two coordinates (in kilometers).
	 *
	 * @param float $lat1 Latitude 1.
	 * @param float $lon1 Longitude 1.
	 * @param float $lat2 Latitude 2.
	 * @param float $lon2 Longitude 2.
	 * @return float Distance in kilometers.
	 */
  public static function calculate_distance( $lat1, $lon1, $lat2, $lon2 ) {
      $earth_radius = 6371; // Radius of the earth in km.

      $lat_from = deg2rad( $lat1 );
      $lon_from = deg2rad( $lon1 );
      $lat_to   = deg2rad( $lat2 );
      $lon_to   = deg2rad( $lon2 );

      $lat_delta = $lat_to - $lat_from;
      $lon_delta = $lon_to - $lon_from;

      $angle = 2 * asin(
        sqrt(
          pow( sin( $lat_delta / 2 ), 2 ) +
              cos( $lat_from ) * cos( $lat_to ) * pow( sin( $lon_delta / 2 ), 2 )
        )
      );

      return $angle * $earth_radius;
  }
}
