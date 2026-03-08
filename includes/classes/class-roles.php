<?php
/**
 * Roles and Capabilities Management
 *
 * @package CustomPlugin\Classes
 */

namespace CustomPlugin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Roles class
 */
class Roles {

	/**
	 * Create custom roles on plugin activation.
	 */
  public static function create_roles() {
      // Create Service Worker role.
      add_role(
        'service_worker',
        'Service Worker',
        array(
			'read'                    => true,
			'edit_posts'              => true,
			'delete_posts'            => true,
			'publish_posts'           => true,
			'upload_files'            => true,
			'manage_service_bookings' => true,
			'edit_services'           => true,
		)
      );

      // Create Customer role.
      add_role(
        'service_customer',
        'Service Customer',
        array(
			'read'                  => true,
			'book_services'         => true,
			'manage_own_bookings'   => true,
			'leave_service_reviews' => true,
		)
      );

      // Add capabilities to Administrator.
      $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->add_cap( 'manage_service_bookings' );
        $admin->add_cap( 'manage_service_workers' );
        $admin->add_cap( 'manage_services' );
        $admin->add_cap( 'view_all_bookings' );
    }
  }

	/**
	 * Remove custom roles on plugin deactivation.
	 */
  public static function remove_roles() {
      remove_role( 'service_worker' );
      remove_role( 'service_customer' );

      // Remove capabilities from Administrator.
      $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin->remove_cap( 'manage_service_bookings' );
        $admin->remove_cap( 'manage_service_workers' );
        $admin->remove_cap( 'manage_services' );
        $admin->remove_cap( 'view_all_bookings' );
    }
  }
}
