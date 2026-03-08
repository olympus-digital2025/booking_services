<?php
/**
 * REST API endpoints
 *
 * @package CustomPlugin\API
 */

namespace CustomPlugin\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API class.
 */
class REST_API {

	/**
	 * Initialize REST API.
	 */
  public static function init() {
      add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
  }

	/**
	 * Register REST routes.
	 */
  public static function register_routes() {
      // Get nearby services
      register_rest_route(
        'cp/v1',
        '/services/nearby',
        array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_nearby_services' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'latitude'  => array(
					'type'     => 'number',
					'required' => true,
				),
				'longitude' => array(
					'type'     => 'number',
					'required' => true,
				),
				'limit'     => array(
					'type'    => 'integer',
					'default' => 20,
				),
			),
		)
      );

      // Create booking.
      register_rest_route(
        'cp/v1',
        '/bookings',
        array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_booking' ),
			'permission_callback' => array( __CLASS__, 'check_logged_in' ),
		)
      );

      // Get user bookings.
      register_rest_route(
        'cp/v1',
        '/bookings/user',
        array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_user_bookings' ),
			'permission_callback' => array( __CLASS__, 'check_logged_in' ),
		)
      );

      // Update booking status (worker only).
      register_rest_route(
        'cp/v1',
        '/bookings/(?P<id>\d+)/status',
        array(
			'methods'             => 'PUT',
			'callback'            => array( __CLASS__, 'update_booking_status' ),
			'permission_callback' => array( __CLASS__, 'check_logged_in' ),
		)
      );

      // Get worker services.
      register_rest_route(
        'cp/v1',
        '/workers/(?P<id>\d+)/services',
        array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_worker_services' ),
			'permission_callback' => '__return_true',
		)
      );

      // Get worker locations.
      register_rest_route(
        'cp/v1',
        '/workers/(?P<id>\d+)/locations',
        array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_worker_locations' ),
			'permission_callback' => '__return_true',
		)
      );

      // Add service location.
      register_rest_route(
        'cp/v1',
        '/locations',
        array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'add_service_location' ),
			'permission_callback' => array( __CLASS__, 'check_logged_in' ),
		)
      );

      // Add rating.
      register_rest_route(
        'cp/v1',
        '/ratings',
        array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'add_rating' ),
			'permission_callback' => array( __CLASS__, 'check_logged_in' ),
		)
      );
  }

	/**
	 * Check if user is logged in.
	 *
	 * @return bool True if logged in, false otherwise.
	 */
  public static function check_logged_in() {
      return is_user_logged_in();
  }

	/**
	 * Get nearby services.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function get_nearby_services( $request ) {
      $latitude  = $request->get_param( 'latitude' );
      $longitude = $request->get_param( 'longitude' );
      $limit     = $request->get_param( 'limit' );

      $services = \CustomPlugin\Classes\Location_Manager::get_nearby_services( '', $latitude, $longitude, $limit );

      return rest_ensure_response( $services );
  }

	/**
	 * Create booking.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function create_booking( $request ) {
      $params = $request->get_json_params();

      $booking_id = \CustomPlugin\Classes\Booking_Manager::create_booking(
        array(
			'service_id'       => $params['service_id'] ?? 0,
			'worker_id'        => $params['worker_id'] ?? 0,
			'customer_id'      => get_current_user_id(),
			'scheduled_date'   => $params['scheduled_date'] ?? '',
			'service_location' => $params['service_location'] ?? '',
			'latitude'         => $params['latitude'] ?? 0,
			'longitude'        => $params['longitude'] ?? 0,
			'total_amount'     => $params['total_amount'] ?? 0,
			'notes'            => $params['notes'] ?? '',
		)
      );

    if ( $booking_id ) {
        return rest_ensure_response(
          array(
			  'success'    => true,
			  'booking_id' => $booking_id,
			  'message'    => 'Booking created successfully',
		  )
        );
    }

      return new \WP_Error( 'booking_failed', 'Failed to create booking', array( 'status' => 400 ) );
  }

	/**
	 * Get user bookings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function get_user_bookings( $request ) {
      $user_id = get_current_user_id();
      $user    = get_userdata( $user_id );

    if ( in_array( 'service_worker', $user->roles, true ) ) {
        $bookings = \CustomPlugin\Classes\Booking_Manager::get_worker_bookings( $user_id );
    } else {
        $bookings = \CustomPlugin\Classes\Booking_Manager::get_customer_bookings( $user_id );
    }

      return rest_ensure_response( $bookings );
  }

	/**
	 * Update booking status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function update_booking_status( $request ) {
      $booking_id = $request['id'];
      $params     = $request->get_json_params();
      $status     = $params['status'] ?? '';

      $user_id = get_current_user_id();
      $booking = \CustomPlugin\Classes\Booking_Manager::get_booking( $booking_id );

    if ( ! $booking ) {
        return new \WP_Error( 'not_found', 'Booking not found', array( 'status' => 404 ) );
    }

      // Only worker can update their own bookings
    if ( $booking->worker_id !== $user_id ) {
        return new \WP_Error( 'forbidden', 'You cannot update this booking', array( 'status' => 403 ) );
    }

    if ( \CustomPlugin\Classes\Booking_Manager::update_booking_status( $booking_id, $status ) ) {
        return rest_ensure_response(
          array(
			  'success' => true,
			  'message' => 'Booking status updated',
		  )
        );
    }

      return new \WP_Error( 'update_failed', 'Failed to update booking status', array( 'status' => 400 ) );
  }

	/**
	 * Get worker services
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function get_worker_services( $request ) {
      $worker_id = $request['id'];

      $args     = array(
          'post_type'      => 'service',
          'author'         => intval( $worker_id ),
          'posts_per_page' => 50,
      );
      $services = new \WP_Query( $args );

      $result = array();
      while ( $services->have_posts() ) {
          $services->the_post();
          $result[] = array(
              'id'              => get_the_ID(),
              'title'           => get_the_title(),
              'description'     => get_the_content(),
              'thumbnail'       => get_the_post_thumbnail_url(),
              'rating'          => \CustomPlugin\Classes\Booking_Manager::get_worker_average_rating( $worker_id ),
              'completed_count' => \CustomPlugin\Classes\Booking_Manager::get_worker_completed_count( $worker_id ),
          );
      }
      wp_reset_postdata();

      return rest_ensure_response( $result );
  }

	/**
	 * Get worker locations
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function get_worker_locations( $request ) {
      $worker_id = $request['id'];

      $locations = \CustomPlugin\Classes\Location_Manager::get_worker_locations( intval( $worker_id ) );

      return rest_ensure_response( $locations );
  }

	/**
	 * Add service location
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function add_service_location( $request ) {
      $user_id = get_current_user_id();
      $params  = $request->get_json_params();

      $location_id = \CustomPlugin\Classes\Location_Manager::add_service_location(
        $user_id,
        $params['service_id'] ?? 0,
        array(
			'location_name'     => $params['location_name'] ?? '',
			'latitude'          => $params['latitude'] ?? 0,
			'longitude'         => $params['longitude'] ?? 0,
			'service_radius_km' => $params['service_radius_km'] ?? 50,
		)
      );

    if ( $location_id ) {
        return rest_ensure_response(
          array(
			  'success'     => true,
			  'location_id' => $location_id,
			  'message'     => 'Location added successfully',
		  )
        );
    }

      return new \WP_Error( 'location_failed', 'Failed to add location', array( 'status' => 400 ) );
  }

	/**
	 * Add rating
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
  public static function add_rating( $request ) {
      $params = $request->get_json_params();

      $rating_id = \CustomPlugin\Classes\Booking_Manager::add_rating(
        array(
			'booking_id'  => $params['booking_id'] ?? 0,
			'customer_id' => get_current_user_id(),
			'worker_id'   => $params['worker_id'] ?? 0,
			'service_id'  => $params['service_id'] ?? 0,
			'rating'      => $params['rating'] ?? 5,
			'review'      => $params['review'] ?? '',
		)
      );

    if ( $rating_id ) {
        return rest_ensure_response(
          array(
			  'success'   => true,
			  'rating_id' => $rating_id,
			  'message'   => 'Rating added successfully',
		  )
        );
    }

      return new \WP_Error( 'rating_failed', 'Failed to add rating', array( 'status' => 400 ) );
  }
}
