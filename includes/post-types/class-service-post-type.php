<?php
/**
 * Service post type registration
 *
 * @package CustomPlugin\PostTypes
 */

namespace CustomPlugin\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service Post Type class
 */
class Service_Post_Type {

	/**
	 * Register Service post type
	 */
  public static function register() {
      $args = array(
          'labels'             => array(
              'name'               => 'Services',
              'singular_name'      => 'Service',
              'add_new'            => 'Add New Service',
              'add_new_item'       => 'Add New Service',
              'edit_item'          => 'Edit Service',
              'new_item'           => 'New Service',
              'view_item'          => 'View Service',
              'search_items'       => 'Search Services',
              'not_found'          => 'No services found',
              'not_found_in_trash' => 'No services found in trash',
          ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,
          'rewrite'            => array( 'slug' => 'service' ),
          'capability_type'    => 'post',
          'has_archive'        => true,
          'hierarchical'       => false,
          'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
          'menu_icon'          => 'dashicons-hammer',
          'show_in_rest'       => true,
      );

      register_post_type( 'service', $args );
  }

	/**
	 * Register Service Category taxonomy
	 */
  public static function register_category_taxonomy() {
      $args = array(
          'labels'             => array(
              'name'          => 'Service Categories',
              'singular_name' => 'Service Category',
              'add_new_item'  => 'Add New Category',
              'edit_item'     => 'Edit Category',
          ),
          'public'             => true,
          'publicly_queryable' => true,
          'show_in_menu'       => true,
          'show_in_rest'       => true,
          'hierarchical'       => true,
          'rewrite'            => array( 'slug' => 'service-category' ),
      );

      register_taxonomy( 'service_category', 'service', $args );
  }

	/**
	 * Register Booking post type
	 */
  public static function register_booking() {
      $args = array(
          'labels'             => array(
              'name'          => 'Bookings',
              'singular_name' => 'Booking',
              'add_new'       => 'Add New Booking',
              'add_new_item'  => 'Add New Booking',
              'edit_item'     => 'Edit Booking',
              'new_item'      => 'New Booking',
              'view_item'     => 'View Booking',
              'search_items'  => 'Search Bookings',
              'not_found'     => 'No bookings found',
          ),
          'public'             => false,
          'publicly_queryable' => false,
          'show_ui'            => true,
          'show_in_menu'       => 'edit.php?post_type=service',
          'query_var'          => false,
          'capability_type'    => 'post',
          'has_archive'        => false,
          'hierarchical'       => false,
          'supports'           => array( 'title' ),
          'menu_icon'          => 'dashicons-calendar-alt',
          'show_in_rest'       => true,
      );

      register_post_type( 'service_booking', $args );
  }
}
