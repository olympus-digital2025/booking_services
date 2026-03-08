<?php
/**
 * Booking Manager class
 *
 * @package CustomPlugin\Classes
 */

namespace CustomPlugin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Booking Manager class
 */
class Booking_Manager {

	/**
	 * Create a new booking.
	 *
	 * @param array $data Booking data.
	 * @return int|false Booking ID or false on failure.
	 */
  public static function create_booking( $data ) {
    global $wpdb;

    $booking_id = 'BK-' . gmdate( 'YmdHis' ) . '-' . wp_generate_password( 6, false, false );

    $insert_data = array(
        'booking_id'        => $booking_id,
        'service_id'        => isset( $data['service_id'] ) ? intval( $data['service_id'] ) : 0,
        'worker_id'         => isset( $data['worker_id'] ) ? intval( $data['worker_id'] ) : 0,
        'customer_id'       => isset( $data['customer_id'] ) ? intval( $data['customer_id'] ) : 0,
        'scheduled_date'    => isset( $data['scheduled_date'] ) ? sanitize_text_field( $data['scheduled_date'] ) : '',
        'service_location'  => isset( $data['service_location'] ) ? sanitize_text_field( $data['service_location'] ) : '',
        'service_latitude'  => isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : 0,
        'service_longitude' => isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : 0,
        'total_amount'      => isset( $data['total_amount'] ) ? floatval( $data['total_amount'] ) : 0,
        'notes'             => isset( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '',
        'status'            => 'pending',
    );

    $result = $wpdb->insert(
      "{$wpdb->prefix}service_bookings",
      $insert_data,
      array( '%s', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s' )
    );

    if ( false !== $result ) {
        do_action( 'cp_booking_created', $wpdb->insert_id, $data );
        return $wpdb->insert_id;
    }

    return false;
  }
  // phpcs:enable Generic.Metrics.CyclomaticComplexity

	/**
	 * Get booking by ID.
	 *
	 * @param int $booking_id Booking ID.
	 * @return object|null Booking object or null.
	 */
  public static function get_booking( $booking_id ) {
      global $wpdb;

      return $wpdb->get_row(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}service_bookings WHERE id = %d",
          $booking_id
        )
      );
  }

	/**
	 * Update booking status.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status New status.
	 * @return bool True on success, false on failure.
	 */
  public static function update_booking_status( $booking_id, $status ) {
      global $wpdb;

      $allowed_statuses = array( 'pending', 'accepted', 'in_progress', 'completed', 'cancelled' );

    if ( ! in_array( $status, $allowed_statuses, true ) ) {
        return false;
    }

      $result = $wpdb->update(
        "{$wpdb->prefix}service_bookings",
        array( 'status' => $status ),
        array( 'id' => intval( $booking_id ) ),
        array( '%s' ),
        array( '%d' )
      );

    if ( false !== $result ) {
        do_action( 'cp_booking_status_updated', $booking_id, $status );
    }

      return false !== $result;
  }

	/**
	 * Get bookings by worker.
	 *
	 * @param int    $worker_id Worker ID.
	 * @param string $status Optional status filter.
	 * @return array Array of bookings.
	 */
  public static function get_worker_bookings( $worker_id, $status = '' ) {
      global $wpdb;

      $worker_id = intval( $worker_id );

    if ( ! empty( $status ) ) {
        $status = sanitize_text_field( $status );
        // phpcs:ignore WordPress.DB.PreparedStatement.NotPrepared
        return $wpdb->get_results(
          $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d AND status = %s ORDER BY scheduled_date DESC",
            $worker_id,
            $status
          )
        );
    }

      // phpcs:ignore WordPress.DB.PreparedStatement.NotPrepared
      return $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d ORDER BY scheduled_date DESC",
          $worker_id
        )
      );
  }

	/**
	 * Get bookings by customer.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $status Optional status filter.
	 * @return array Array of bookings.
	 */
  public static function get_customer_bookings( $customer_id, $status = '' ) {
      global $wpdb;

      $customer_id = intval( $customer_id );

    if ( ! empty( $status ) ) {
        $status = sanitize_text_field( $status );
        // phpcs:ignore WordPress.DB.PreparedStatement.NotPrepared
        return $wpdb->get_results(
          $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}service_bookings WHERE customer_id = %d AND status = %s ORDER BY scheduled_date DESC",
            $customer_id,
            $status
          )
        );
    }

      // phpcs:ignore WordPress.DB.PreparedStatement.NotPrepared
      return $wpdb->get_results(
        $wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}service_bookings WHERE customer_id = %d ORDER BY scheduled_date DESC",
          $customer_id
        )
      );
  }

	/**
	 * Add rating/review.
	 *
	 * @param array $data Rating data.
	 * @return int|false Rating ID or false on failure.
	 */
  public static function add_rating( $data ) {
      global $wpdb;

      $insert_data = array(
          'booking_id'  => isset( $data['booking_id'] ) ? intval( $data['booking_id'] ) : 0,
          'customer_id' => isset( $data['customer_id'] ) ? intval( $data['customer_id'] ) : 0,
          'worker_id'   => isset( $data['worker_id'] ) ? intval( $data['worker_id'] ) : 0,
          'service_id'  => isset( $data['service_id'] ) ? intval( $data['service_id'] ) : 0,
          'rating'      => isset( $data['rating'] ) ? intval( $data['rating'] ) : 5,
          'review'      => isset( $data['review'] ) ? wp_kses_post( $data['review'] ) : '',
      );

      $result = $wpdb->insert(
        "{$wpdb->prefix}service_ratings",
        $insert_data,
        array( '%d', '%d', '%d', '%d', '%d', '%s' )
      );

    if ( false !== $result ) {
        do_action( 'cp_rating_added', $wpdb->insert_id, $data );
        return $wpdb->insert_id;
    }

      return false;
  }

	/**
	 * Get worker ratings average.
	 *
	 * @param int $worker_id Worker ID.
	 * @return float Average rating.
	 */
  public static function get_worker_average_rating( $worker_id ) {
      global $wpdb;

      $result = $wpdb->get_var(
        $wpdb->prepare(
          "SELECT AVG(rating) FROM {$wpdb->prefix}service_ratings WHERE worker_id = %d",
          $worker_id
        )
      );

      return $result ? floatval( $result ) : 0;
  }

	/**
	 * Get worker total completed bookings.
	 *
	 * @param int $worker_id Worker ID.
	 * @return int Total completed bookings.
	 */
  public static function get_worker_completed_count( $worker_id ) {
      global $wpdb;

      $result = $wpdb->get_var(
        $wpdb->prepare(
          "SELECT COUNT(*) FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d AND status = %s",
          $worker_id,
          'completed'
        )
      );

      return intval( $result );
  }
}
