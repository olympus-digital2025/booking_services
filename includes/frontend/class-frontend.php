<?php
/**
 * Frontend pages handler
 *
 * @package CustomPlugin\Frontend
 */

namespace CustomPlugin\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class
 */
class Frontend {

	/**
	 * Initialize frontend.
	 */
  public static function init() {
      add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_scripts' ) );
      add_action( 'wp_ajax_cp_book_service', array( __CLASS__, 'handle_booking' ) );
      add_action( 'wp_ajax_nopriv_cp_get_nearby_services', array( __CLASS__, 'get_nearby_services' ) );
  }

	/**
	 * Enqueue frontend scripts.
	 */
  public static function enqueue_frontend_scripts() {
      wp_enqueue_style( 'cp-frontend-styles', plugin_dir_url( __FILE__ ) . '../../assets/css/frontend.css', array(), '1.0' );
      wp_enqueue_script( 'cp-frontend-script', plugin_dir_url( __FILE__ ) . '../../assets/js/frontend.js', array( 'jquery' ), '1.0', true );

      // Localize AJAX.
      wp_localize_script(
        'cp-frontend-script',
        'cpAjax',
        array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cp-nonce' ),
		)
      );
  }

	/**
	 * Handle booking via AJAX.
	 */
  public static function handle_booking() {
      check_ajax_referer( 'cp-nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'You must be logged in to book a service.' ) );
    }

      $service_id       = isset( $_POST['service_id'] ) ? intval( $_POST['service_id'] ) : 0;
      $worker_id        = isset( $_POST['worker_id'] ) ? intval( $_POST['worker_id'] ) : 0;
      $scheduled_date   = isset( $_POST['scheduled_date'] ) ? sanitize_text_field( $_POST['scheduled_date'] ) : '';
      $service_location = isset( $_POST['service_location'] ) ? sanitize_text_field( $_POST['service_location'] ) : '';
      $latitude         = isset( $_POST['latitude'] ) ? floatval( $_POST['latitude'] ) : 0;
      $longitude        = isset( $_POST['longitude'] ) ? floatval( $_POST['longitude'] ) : 0;
      $notes            = isset( $_POST['notes'] ) ? wp_kses_post( $_POST['notes'] ) : '';
      $total_amount     = isset( $_POST['total_amount'] ) ? floatval( $_POST['total_amount'] ) : 0;

    if ( ! $service_id || ! $worker_id ) {
        wp_send_json_error( array( 'message' => 'Service and worker are required.' ) );
    }

      $booking_id = \CustomPlugin\Classes\Booking_Manager::create_booking(
        array(
			'service_id'       => $service_id,
			'worker_id'        => $worker_id,
			'customer_id'      => get_current_user_id(),
			'scheduled_date'   => $scheduled_date,
			'service_location' => $service_location,
			'latitude'         => $latitude,
			'longitude'        => $longitude,
			'total_amount'     => $total_amount,
			'notes'            => $notes,
		)
      );

    if ( $booking_id ) {
        wp_send_json_success(
          array(
			  'message'    => 'Booking created successfully!',
			  'booking_id' => $booking_id,
		  )
        );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to create booking.' ) );
    }
  }

	/**
	 * Get nearby services AJAX endpoint
	 */
  public static function get_nearby_services() {
      $latitude  = isset( $_GET['latitude'] ) ? floatval( $_GET['latitude'] ) : 0;
      $longitude = isset( $_GET['longitude'] ) ? floatval( $_GET['longitude'] ) : 0;
      $limit     = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 20;

    if ( ! $latitude || ! $longitude ) {
        wp_send_json_error( array( 'message' => 'Location is required.' ) );
    }

      $services = \CustomPlugin\Classes\Location_Manager::get_nearby_services( '', $latitude, $longitude, $limit );

      wp_send_json_success( array( 'services' => $services ) );
  }
}
