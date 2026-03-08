<?php
/**
 * Shortcodes handler
 *
 * @package CustomPlugin\Frontend
 */

namespace CustomPlugin\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes class
 */
class Shortcodes {

	/**
	 * Initialize shortcodes
	 */
  public static function init() {
      add_shortcode( 'cp_service_browser', array( __CLASS__, 'service_browser' ) );
      add_shortcode( 'cp_worker_dashboard', array( __CLASS__, 'worker_dashboard' ) );
      add_shortcode( 'cp_customer_bookings', array( __CLASS__, 'customer_bookings' ) );
      add_shortcode( 'cp_worker_services', array( __CLASS__, 'worker_services' ) );
  }

	/**
	 * Service browser shortcode - for customers to find services
	 *
	 * @return string HTML
	 */
  public static function service_browser() {
    if ( ! is_user_logged_in() ) {
        return '<div class="cp-alert">Please <a href="' . wp_login_url() . '">login</a> to browse services.</div>';
    }

      ob_start();
    ?>
		<div class="cp-service-browser">
			<h2>Find Services Near You</h2>
			
			<div class="cp-search-form">
				<input type="text" id="cp-service-search" placeholder="Search services (e.g., Plumber, Painter)">
				<button id="cp-get-location" class="button button-primary">Use My Location</button>
				<button id="cp-search-services" class="button button-secondary">Search</button>
			</div>

			<div id="cp-services-list" class="cp-services-grid">
				<!-- Services will be loaded here -->
			</div>
		</div>

		<style>
			.cp-service-browser {
				max-width: 1200px;
				margin: 0 auto;
			}

			.cp-search-form {
				display: flex;
				gap: 10px;
				margin: 20px 0;
				flex-wrap: wrap;
			}

			.cp-search-form input {
				flex: 1;
				min-width: 200px;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}

			.cp-services-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
				gap: 20px;
			}

			.cp-service-card {
				border: 1px solid #ddd;
				border-radius: 8px;
				overflow: hidden;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				transition: transform 0.2s;
			}

			.cp-service-card:hover {
				transform: translateY(-4px);
				box-shadow: 0 4px 16px rgba(0,0,0,0.15);
			}

			.cp-service-card-image {
				width: 100%;
				height: 200px;
				object-fit: cover;
			}

			.cp-service-card-content {
				padding: 15px;
			}

			.cp-service-card-title {
				font-size: 18px;
				font-weight: bold;
				margin-bottom: 10px;
			}

			.cp-service-card-worker {
				color: #666;
				font-size: 14px;
				margin-bottom: 8px;
			}

			.cp-service-card-rating {
				color: #ff9800;
				font-size: 14px;
				margin-bottom: 10px;
			}

			.cp-service-card-distance {
				color: #2196f3;
				font-size: 12px;
				margin-bottom: 15px;
			}

			.cp-service-card button {
				width: 100%;
			}

			.cp-alert {
				background: #fff3cd;
				border: 1px solid #ffc107;
				color: #856404;
				padding: 12px;
				border-radius: 4px;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				// Get user location
				$('#cp-get-location').click(function() {
					if (navigator.geolocation) {
						navigator.geolocation.getCurrentPosition(function(position) {
							$('#cp-latitude').val(position.coords.latitude);
							$('#cp-longitude').val(position.coords.longitude);
							alert('Location obtained: ' + position.coords.latitude + ', ' + position.coords.longitude);
						});
					} else {
						alert('Geolocation is not supported by this browser.');
					}
				});

				// Search services
				$('#cp-search-services').click(function() {
					let latitude = $('#cp-latitude').val();
					let longitude = $('#cp-longitude').val();

					if (!latitude || !longitude) {
						alert('Please get your location first');
						return;
					}

					$.ajax({
						url: cpAjax.ajaxUrl,
						method: 'GET',
						data: {
							action: 'cp_get_nearby_services',
							latitude: latitude,
							longitude: longitude,
							limit: 20,
						},
						success: function(response) {
							renderServices(response.data.services);
						},
						error: function() {
							alert('Error loading services');
						}
					});
				});

				function renderServices(services) {
					let html = '';
					if (services.length === 0) {
						html = '<p>No services found near you.</p>';
					} else {
						services.forEach(function(service) {
							html += `
								<div class="cp-service-card">
									<div class="cp-service-card-content">
										<div class="cp-service-card-title">${service.service_name}</div>
										<div class="cp-service-card-worker">Worker: ${service.user_login}</div>
										<div class="cp-service-card-rating">${service.rating || 'N/A'} ⭐</div>
										<div class="cp-service-card-distance">${parseFloat(service.distance_km).toFixed(1)} km away</div>
										<button class="button button-primary cp-book-btn" data-service-id="${service.service_id}" data-worker-id="${service.worker_id}">Book Service</button>
									</div>
								</div>
							`;
						});
					}
					$('#cp-services-list').html(html);
				}

				// Hidden location fields
				$('body').append('<input type="hidden" id="cp-latitude">');
				$('body').append('<input type="hidden" id="cp-longitude">');
			});
		</script>
      <?php
      return ob_get_clean();
  }

	/**
	 * Worker dashboard shortcode
	 *
	 * @return string HTML
	 */
  public static function worker_dashboard() {
    if ( ! is_user_logged_in() ) {
        return '<div class="cp-alert">Please <a href="' . wp_login_url() . '">login</a> to access your dashboard.</div>';
    }

      $user = wp_get_current_user();
    if ( ! in_array( 'service_worker', $user->roles, true ) ) {
        return '<div class="cp-alert">You must be a service worker to access this page.</div>';
    }

      ob_start();
    ?>
		<div class="cp-worker-dashboard">
			<h2>Worker Dashboard</h2>

			<div class="cp-stats">
              <?php
              $user_id   = get_current_user_id();
              $completed = \CustomPlugin\Classes\Booking_Manager::get_worker_completed_count( $user_id );
              $rating    = \CustomPlugin\Classes\Booking_Manager::get_worker_average_rating( $user_id );
              ?>

				<div class="cp-stat-card">
					<div class="cp-stat-number"><?php echo esc_html( $completed ); ?></div>
					<div class="cp-stat-label">Jobs Completed</div>
				</div>

				<div class="cp-stat-card">
					<div class="cp-stat-number"><?php echo esc_html( number_format( $rating, 1 ) ); ?>⭐</div>
					<div class="cp-stat-label">Rating</div>
				</div>
			</div>

			<h3>Your Service Locations</h3>
			<p>Manage the areas where you provide services.</p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=cp-manage-services' ) ); ?>" class="button button-primary">Manage Services & Locations</a>
		</div>

		<style>
			.cp-worker-dashboard {
				max-width: 1000px;
				margin: 0 auto;
			}

			.cp-stats {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 20px;
				margin: 30px 0;
			}

			.cp-stat-card {
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 8px;
				padding: 20px;
				text-align: center;
			}

			.cp-stat-number {
				font-size: 32px;
				font-weight: bold;
				color: #2196f3;
			}

			.cp-stat-label {
				font-size: 14px;
				color: #666;
				margin-top: 10px;
			}
		</style>
		<?php
		return ob_get_clean();
  }

	/**
	 * Customer bookings shortcode
	 *
	 * @return string HTML
	 */
  public static function customer_bookings() {
    if ( ! is_user_logged_in() ) {
        return '<div class="cp-alert">Please <a href="' . wp_login_url() . '">login</a> to view your bookings.</div>';
    }

      $user = wp_get_current_user();
    if ( ! in_array( 'service_customer', $user->roles, true ) && ! in_array( 'administrator', $user->roles, true ) ) {
        return '<div class="cp-alert">You must be a customer to view this page.</div>';
    }

      $bookings = \CustomPlugin\Classes\Booking_Manager::get_customer_bookings( get_current_user_id() );

      ob_start();
    ?>
		<div class="cp-customer-bookings">
			<h2>My Bookings</h2>

          <?php if ( empty( $bookings ) ) : ?>
				<p>You haven't booked any services yet. <a href="<?php echo '#'; ?>">Browse services</a></p>
			<?php else : ?>
				<table class="cp-bookings-table">
					<thead>
						<tr>
							<th>Booking ID</th>
							<th>Service</th>
							<th>Date</th>
							<th>Status</th>
							<th>Amount</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $bookings as $booking ) {
							$service = get_post( $booking->service_id );
                          ?>
							<tr>
								<td><?php echo esc_html( $booking->booking_id ); ?></td>
								<td><?php echo esc_html( $service ? $service->post_title : 'N/A' ); ?></td>
								<td><?php echo esc_html( $booking->scheduled_date ); ?></td>
								<td><span class="cp-status cp-status-<?php echo esc_attr( $booking->status ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $booking->status ) ) ); ?></span></td>
								<td><?php echo esc_html( '$' . number_format( $booking->total_amount, 2 ) ); ?></td>
								<td><a href="<?php echo '#'; ?>" class="button button-small">Details</a></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<style>
			.cp-customer-bookings {
				max-width: 1000px;
				margin: 0 auto;
			}

			.cp-bookings-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 20px;
			}

			.cp-bookings-table th,
			.cp-bookings-table td {
				padding: 12px;
				text-align: left;
				border-bottom: 1px solid #ddd;
			}

			.cp-bookings-table th {
				background: #f5f5f5;
				font-weight: bold;
			}

			.cp-status {
				padding: 4px 8px;
				border-radius: 4px;
				font-size: 12px;
				font-weight: bold;
			}

			.cp-status-pending {
				background: #fff3cd;
				color: #856404;
			}

			.cp-status-accepted,
			.cp-status-completed {
				background: #d4edda;
				color: #155724;
			}

			.cp-status-cancelled {
				background: #f8d7da;
				color: #721c24;
			}
		</style>
		<?php
		return ob_get_clean();
  }

	/**
	 * Worker services shortcode
	 *
	 * @return string HTML
	 */
  public static function worker_services() {
    if ( ! is_user_logged_in() ) {
        return '<div class="cp-alert">Please <a href="' . wp_login_url() . '">login</a></div>';
    }

      ob_start();
    ?>
		<div class="cp-worker-services">
			<h2>My Services</h2>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=service' ) ); ?>" class="button button-primary">Create New Service</a>

          <?php
          $args     = array(
              'post_type'      => 'service',
              'posts_per_page' => 20,
              'author'         => get_current_user_id(),
          );
          $services = new \WP_Query( $args );

          if ( $services->have_posts() ) {
              echo '<div class="cp-services-list">';
            while ( $services->have_posts() ) {
                $services->the_post();
              ?>
					<div class="cp-service-item">
                    <?php if ( has_post_thumbnail() ) : ?>
							<div class="cp-service-thumb">
								<?php the_post_thumbnail( 'medium' ); ?>
							</div>
						<?php endif; ?>
						<div class="cp-service-info">
							<h3><?php the_title(); ?></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ) ); ?>" class="button button-small">Edit</a>
							<a href="<?php the_permalink(); ?>" class="button button-small" target="_blank">View</a>
						</div>
					</div>
                  <?php
            }
              echo '</div>';
              wp_reset_postdata();
          } else {
              echo '<p>No services created yet.</p>';
          }
          ?>
		</div>

		<style>
			.cp-services-list {
				display: grid;
				gap: 20px;
				margin-top: 20px;
			}

			.cp-service-item {
				display: flex;
				gap: 20px;
				border: 1px solid #ddd;
				border-radius: 8px;
				overflow: hidden;
			}

			.cp-service-thumb {
				flex: 0 0 200px;
				height: 200px;
				overflow: hidden;
			}

			.cp-service-thumb img {
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			.cp-service-info {
				flex: 1;
				padding: 20px;
		}</style>
		<?php
		return ob_get_clean();
  }
}
