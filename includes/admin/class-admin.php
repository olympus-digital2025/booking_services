<?php
/**
 * Admin pages handler
 *
 * @package CustomPlugin\Admin
 */

namespace CustomPlugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class for handling admin pages
 */
class Admin {

	/**
	 * Initialize admin pages
	 */
  public static function init() {
      add_action( 'admin_menu', array( __CLASS__, 'add_admin_pages' ) );
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
  }

	/**
	 * Add admin pages
	 */
  public static function add_admin_pages() {
      // Add Service Manager page.
      add_submenu_page(
        'edit.php?post_type=service',
        'Manage Services',
        'Manage Services',
        'manage_services',
        'cp-manage-services',
        array( __CLASS__, 'render_manage_services_page' )
      );

      // Add Bookings Dashboard page.
      add_submenu_page(
        'edit.php?post_type=service',
        'Bookings Dashboard',
        'Bookings Dashboard',
        'manage_service_bookings',
        'cp-bookings-dashboard',
        array( __CLASS__, 'render_bookings_dashboard' )
      );

      // Add Worker Management page (admin only).
    if ( current_user_can( 'manage_options' ) ) {
        add_submenu_page(
          'edit.php?post_type=service',
          'Worker Management',
          'Worker Management',
          'manage_options',
          'cp-worker-management',
          array( __CLASS__, 'render_worker_management_page' )
        );
    }
  }

	/**
	 * Render manage services page
	 */
  public static function render_manage_services_page() {
    ?>
		<div class="wrap">
			<h1>Manage Services</h1>
			
          <?php if ( current_user_can( 'publish_posts' ) && in_array( wp_get_current_user()->roles[0], array( 'service_worker', 'administrator' ), true ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=service' ) ); ?>" class="button button-primary">Add New Service</a>
			<?php endif; ?>

			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th>Service Name</th>
						<th>Category</th>
						<th>Created By</th>
						<th>Date</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
                  <?php
                  $user_id = get_current_user_id();
                  $args    = array(
                      'post_type'      => 'service',
                      'posts_per_page' => 20,
                      'author'         => current_user_can( 'manage_options' ) ? '' : $user_id,
                  );

                  $services = new \WP_Query( $args );

                  if ( $services->have_posts() ) {
                    while ( $services->have_posts() ) {
                        $services->the_post();
                      ?>
							<tr>
								<td><strong><?php the_title(); ?></strong></td>
								<td>
                              <?php
                              $categories = get_the_terms( get_the_ID(), 'service_category' );
                              if ( ! empty( $categories ) ) {
                                  echo esc_html( implode( ', ', wp_list_pluck( $categories, 'name' ) ) );
                              }
                              ?>
								</td>
								<td><?php echo esc_html( get_the_author() ); ?></td>
								<td><?php echo esc_html( get_the_date( 'Y-m-d H:i' ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ) ); ?>" class="button button-small">Edit</a>
									<a href="<?php echo esc_url( get_permalink() ); ?>" class="button button-small" target="_blank">View</a>
								</td>
							</tr>
							<?php
                    }
                      wp_reset_postdata();
                  }
                  ?>
				</tbody>
			</table>
		</div>
		<?php
  }

	/**
	 * Render bookings dashboard
	 */
  public static function render_bookings_dashboard() {
      global $wpdb;

      $user_id = get_current_user_id();
      $user    = get_userdata( $user_id );

    ?>
		<div class="wrap">
			<h1>Bookings Dashboard</h1>

			<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
              <?php
              // Stats for workers.
              if ( in_array( 'service_worker', $user->roles, true ) ) {
                  $pending   = $wpdb->get_var(
                    $wpdb->prepare(
                      "SELECT COUNT(*) FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d AND status = 'pending'",
                      $user_id
                    )
                  );
                  $accepted  = $wpdb->get_var(
                    $wpdb->prepare(
                      "SELECT COUNT(*) FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d AND status = 'accepted'",
                      $user_id
                    )
                  );
                  $completed = $wpdb->get_var(
                    $wpdb->prepare(
                      "SELECT COUNT(*) FROM {$wpdb->prefix}service_bookings WHERE worker_id = %d AND status = 'completed'",
                      $user_id
                    )
                  );
                ?>
					<div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $pending ); ?></div>
						<div>Pending Bookings</div>
					</div>
					<div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $accepted ); ?></div>
						<div>Accepted Bookings</div>
					</div>
					<div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center;">
						<div style="font-size: 24px; font-weight: bold;"><?php echo esc_html( $completed ); ?></div>
						<div>Completed</div>
					</div>
					<div style="background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center;">
						<div style="font-size: 24px; font-weight: bold;">
                          <?php
                          $rating = \CustomPlugin\Classes\Booking_Manager::get_worker_average_rating( $user_id );
                          echo esc_html( number_format( $rating, 1 ) );
                          ?>
						⭐
					</div>
					<div>Rating</div>
				</div>
				<?php
              }
              ?>
			</div>

			<h2>Recent Bookings</h2>
			<?php
			if ( in_array( 'service_worker', $user->roles, true ) ) {
				$bookings = \CustomPlugin\Classes\Booking_Manager::get_worker_bookings( $user_id );
			} elseif ( in_array( 'service_customer', $user->roles, true ) ) {
				$bookings = \CustomPlugin\Classes\Booking_Manager::get_customer_bookings( $user_id );
			} else {
				$bookings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}service_bookings ORDER BY scheduled_date DESC LIMIT 50" );
			}
			?>

			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th>Booking ID</th>
						<th>Service</th>
						<th>Worker</th>
						<th>Customer</th>
						<th>Date</th>
						<th>Status</th>
						<th>Amount</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
                  <?php
                  foreach ( $bookings as $booking ) {
                      $service  = get_post( $booking->service_id );
                      $worker   = get_userdata( $booking->worker_id );
                      $customer = get_userdata( $booking->customer_id );
                    ?>
						<tr>
							<td><strong><?php echo esc_html( $booking->booking_id ); ?></strong></td>
							<td><?php echo esc_html( $service ? $service->post_title : 'N/A' ); ?></td>
							<td><?php echo esc_html( $worker ? $worker->user_login : 'N/A' ); ?></td>
							<td><?php echo esc_html( $customer ? $customer->user_login : 'N/A' ); ?></td>
							<td><?php echo esc_html( $booking->scheduled_date ); ?></td>
							<td><span class="badge" style="background: <?php echo esc_attr( self::get_status_color( $booking->status ) ); ?>; color: white; padding: 5px 10px; border-radius: 3px;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->status ) ) ); ?></span></td>
							<td><?php echo esc_html( '$' . number_format( $booking->total_amount, 2 ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=cp-bookings-dashboard&booking=' . $booking->id . '&view=detail' ) ); ?>" class="button button-small">View</a>
							</td>
						</tr>
					<?php
                  }
                  ?>
				</tbody>
			</table>
		</div>
		<?php
  }

	/**
	 * Render worker management page
	 */
  public static function render_worker_management_page() {
    ?>
		<div class="wrap">
			<h1>Worker Management</h1>

			<p>Manage service workers and their locations.</p>

			<table class="wp-list-table widefat">
				<thead>
					<tr>
						<th>Worker Name</th>
						<th>Email</th>
						<th>Completed Jobs</th>
						<th>Rating</th>
						<th>Locations</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
                  <?php
                  $workers_query = new \WP_User_Query(
                    array(
						'role'   => 'service_worker',
						'number' => 50,
					)
                  );

                  foreach ( $workers_query->get_results() as $worker ) {
                      $completed = \CustomPlugin\Classes\Booking_Manager::get_worker_completed_count( $worker->ID );
                      $rating    = \CustomPlugin\Classes\Booking_Manager::get_worker_average_rating( $worker->ID );
                    ?>
						<tr>
							<td><strong><?php echo esc_html( $worker->user_login ); ?></strong></td>
							<td><?php echo esc_html( $worker->user_email ); ?></td>
							<td><?php echo esc_html( $completed ); ?></td>
							<td><?php echo esc_html( number_format( $rating, 1 ) ); ?> ⭐</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $worker->ID ) ); ?>" class="button button-small">Manage</a>
							</td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $worker->ID ) ); ?>" class="button button-small">Edit</a>
							</td>
					</tr>
                    <?php
                  }
                  ?>
				</tbody>
			</table>
		</div>
		<?php
  }

	/**
	 * Get status color
	 *
	 * @param string $status Status string.
	 * @return string Hex color.
	 */
  private static function get_status_color( $status ) {
      $colors = array(
          'pending'     => '#FFA500',
          'accepted'    => '#4CAF50',
          'in_progress' => '#2196F3',
          'completed'   => '#4CAF50',
          'cancelled'   => '#f44336',
      );

      return isset( $colors[ $status ] ) ? $colors[ $status ] : '#999999';
  }

	/**
	 * Enqueue admin scripts.
	 */
  public static function enqueue_admin_scripts() {
      wp_enqueue_style( 'cp-admin-styles', plugin_dir_url( __FILE__ ) . '../../assets/css/admin.css', array(), '1.0' );
  }
}
