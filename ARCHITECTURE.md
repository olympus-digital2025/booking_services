# Plugin Architecture & Developer Guide

## Project Structure Overview

```
custom-plugin/
├── functions.php                           # Main plugin initialization
├── BOOKING_PLUGIN_GUIDE.md                 # User documentation
├── QUICK_START.md                          # Quick setup guide
├── ARCHITECTURE.md                         # This file
│
├── includes/
│   ├── classes/                            # Core business logic classes
│   │   ├── class-booking-manager.php       # Booking CRUD operations
│   │   ├── class-location-manager.php      # Geo-location functionality
│   │   └── class-roles.php                 # User role management
│   │
│   ├── post-types/
│   │   └── class-service-post-type.php     # Custom post types & taxonomies
│   │
│   ├── admin/
│   │   └── class-admin.php                 # Admin pages & dashboard
│   │
│   ├── frontend/
│   │   ├── class-frontend.php              # Frontend AJAX handlers
│   │   └── class-shortcodes.php            # Customer & worker shortcodes
│   │
│   ├── api/
│   │   └── class-rest-api.php              # REST API endpoints
│   │
│   └── database/
│       └── class-database.php              # Database schema & operations
│
├── assets/
│   ├── css/
│   │   ├── frontend.css                    # Customer/worker styles
│   │   └── admin.css                       # Admin pages styles
│   └── js/
│       └── frontend.js                     # Frontend interactions
│
└── vendor/                                 # Composer dependencies
```

## Class Architecture

### 1. Database Class
**File**: `includes/database/class-database.php`

Handles:
- Table creation on plugin activation
- Table removal on deactivation
- Database schema definition

Key Methods:
```php
Database::create_tables()      // Create all tables
Database::drop_tables()        // Drop all tables
```

### 2. Roles Class
**File**: `includes/classes/class-roles.php`

Handles:
- Service Worker role creation
- Service Customer role creation
- Capability management

Key Methods:
```php
Roles::create_roles()          // Create roles on activation
Roles::remove_roles()          // Remove roles on deactivation
```

### 3. Booking Manager Class
**File**: `includes/classes/class-booking-manager.php`

Core booking operations:

```php
Booking_Manager::create_booking($data)             // Create new booking
Booking_Manager::get_booking($id)                  // Get booking by ID
Booking_Manager::update_booking_status($id, $status) // Change status
Booking_Manager::get_worker_bookings($worker_id)   // Get worker's bookings
Booking_Manager::get_customer_bookings($customer_id) // Get customer's bookings
Booking_Manager::add_rating($data)                 // Add review/rating
Booking_Manager::get_worker_average_rating($worker_id) // Get avg rating
Booking_Manager::get_worker_completed_count($worker_id) // Get completed count
```

### 4. Location Manager Class
**File**: `includes/classes/class-location-manager.php`

Geo-location features:

```php
Location_Manager::add_service_location($worker_id, $service_id, $data)
Location_Manager::update_service_location($location_id, $data)
Location_Manager::get_worker_locations($worker_id)
Location_Manager::get_nearby_services($service_type, $latitude, $longitude, $limit)
Location_Manager::calculate_distance($lat1, $lon1, $lat2, $lon2)
```

**Distance Algorithm**: Haversine formula for accurate Earth-distance calculations.

### 5. Post Type Class
**File**: `includes/post-types/class-service-post-type.php`

Registers custom post types:
- `service` - Service provider's offerings
- `service_booking` - Booking records
- `service_category` - Service categories (taxonomy)

### 6. Admin Class
**File**: `includes/admin/class-admin.php`

Admin interface pages:
- Service management
- Bookings dashboard
- Worker management (admin only)

Adds:
- Admin pages
- Meta boxes for service pricing/duration
- Custom columns in admin tables

### 7. Frontend Class
**File**: `includes/frontend/class-frontend.php`

Frontend functionality:
- AJAX handlers for bookings
- Frontend script enqueuing
- Location-based service search

### 8. Shortcodes Class
**File**: `includes/frontend/class-shortcodes.php`

User-facing shortcodes:
- `[cp_service_browser]` - Service discovery
- `[cp_worker_dashboard]` - Worker stats
- `[cp_customer_bookings]` - Booking history
- `[cp_worker_services]` - Service management

### 9. REST API Class
**File**: `includes/api/class-rest-api.php`

REST endpoints for mobile/API integration:
- GET/POST bookings
- Service location management
- Ratings endpoints
- Worker profile endpoints

## Data Flow Diagrams

### Customer Booking Flow
```
1. Customer loads [cp_service_browser]
   ↓
2. Browser requests geolocation
   ↓
3. JavaScript calls cp_get_nearby_services AJAX
   ↓
4. Location_Manager::get_nearby_services() queries DB
   ↓
5. Returns services within radius (Haversine calculation)
   ↓
6. JavaScript renders service cards
   ↓
7. Customer clicks "Book Service"
   ↓
8. Modal form appears
   ↓
9. Customer submits cp_book_service AJAX
   ↓
10. Booking_Manager::create_booking() inserts record
    ↓
11. Booking created with status = "pending"
    ↓
12. Email notification sent (if implemented)
```

### Worker Management Flow
```
1. Worker logs in
   ↓
2. Goes to Services → Add New Service
   ↓
3. Service post type created
   ↓
4. Set price & duration via meta boxes
   ↓
5. Publish service
   ↓
6. Worker adds location via REST API or form
   ↓
7. Location_Manager::add_service_location() stores in DB
   ↓
8. Service now visible in customer searches
   ↓
9. Customer books
   ↓
10. Worker sees booking in Bookings Dashboard
    ↓
11. Worker updates status: pending → accepted → in_progress → completed
    ↓
12. Customer can then leave rating
```

### Rating Flow
```
1. Booking status = "completed"
   ↓
2. Customer clicks "Leave Review" on completed booking
   ↓
3. Rating form appears
   ↓
4. Customer submits rating (1-5 stars) + review text
   ↓
5. Booking_Manager::add_rating() inserts to wp_service_ratings
   ↓
6. Worker's average rating recalculated
   ↓
7. Rating displayed on worker's profile
```

## Key Implementation Details

### Security Implementation

**1. User Capability Checks**
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized' );
}
```

**2. Nonce Verification**
```php
check_ajax_referer( 'cp-nonce' );
wp_verify_nonce( $_POST['cp_service_nonce'], 'cp_service_nonce' );
```

**3. Data Sanitization**
```php
intval()              // Integer validation
floatval()            // Float validation
sanitize_text_field() // Text input
wp_kses_post()        // HTML allowed for posts
```

**4. SQL Injection Prevention**
```php
$wpdb->prepare()      // All queries use placeholders
$wpdb->insert()       // Safe insert operations
$wpdb->update()       // Safe update operations
```

### Database Query Patterns

**Haversine Distance Calculation**
```php
// In Location_Manager::get_nearby_services()
$distance = "(111.111 * DEGREES(ACOS(
    LEAST(1,
        COS(RADIANS(%f)) * COS(RADIANS(wsl.latitude)) * 
        COS(RADIANS(%f - wsl.longitude)) +
        SIN(RADIANS(%f)) * SIN(RADIANS(wsl.latitude))
    )
)))"
```

**Prepared Statement Pattern**
```php
$wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}service_bookings 
     WHERE id = %d AND status = %s",
    $booking_id,
    'completed'
)
```

## Extending the Plugin

### Adding a New Feature

**Example: Payment Integration**

1. Create new class file:
```bash
includes/classes/class-payment-manager.php
```

2. Define the class:
```php
namespace CustomPlugin\Classes;

class Payment_Manager {
    public static function process_payment($booking_id, $amount) {
        // Stripe/PayPal integration
    }
}
```

3. Hook into booking creation:
```php
// In functions.php
add_action( 'cp_booking_created', function( $booking_id, $data ) {
    Payment_Manager::process_payment( $booking_id, $data['total_amount'] );
}, 10, 2 );
```

### Adding a Custom Action Hook

```php
// Trigger action when booking status changes
do_action( 'cp_booking_status_updated', $booking_id, $new_status );

// In another class, listen for it:
add_action( 'cp_booking_status_updated', function( $booking_id, $status ) {
    if ( 'completed' === $status ) {
        // Send completion email
    }
}, 10, 2 );
```

### Adding a Custom Filter Hook

```php
// In Booking_Manager::get_nearby_services()
$services = apply_filters( 'cp_nearby_services_results', $services, $latitude, $longitude );

// In another plugin/theme hook it:
add_filter( 'cp_nearby_services_results', function( $services, $lat, $lng ) {
    // Filter or modify results
    return array_filter( $services, function( $service ) {
        return $service->rating >= 4.0; // Only show 4+ stars
    });
}, 10, 3 );
```

## Performance Optimization Tips

### Database Indexing
Already configured in schema:
```sql
KEY worker_id (worker_id)
KEY customer_id (customer_id)
KEY service_id (service_id)
KEY status (status)
```

### Caching Strategy
```php
// Cache worker's average rating
$rating = wp_cache_get( "worker_{$worker_id}_rating" );
if ( false === $rating ) {
    $rating = Booking_Manager::get_worker_average_rating( $worker_id );
    wp_cache_set( "worker_{$worker_id}_rating", $rating, '', 3600 );
}
```

### Query Optimization
- Use LIMIT to reduce result set size
- Index frequently searched columns
- Cache complex calculations

## Testing Checklist

### Unit Tests
- [ ] Booking creation with valid data
- [ ] Invalid booking rejection
- [ ] Status update validation
- [ ] Distance calculation accuracy
- [ ] Role-based access control

### Integration Tests
- [ ] End-to-end booking flow
- [ ] Permission checks
- [ ] Database consistency
- [ ] REST API responses

### Frontend Tests
- [ ] Geolocation access
- [ ] Form validation
- [ ] AJAX request handling
- [ ] Modal functionality
- [ ] Responsive design

## Coding Standards

### Naming Conventions
```php
// Classes
class Booking_Manager { }

// Methods
public function create_booking() { }

// Variables
$booking_id
$service_location
$is_active

// Constants
define( 'CP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
```

### PHPDoc Standards
```php
/**
 * Get booking information
 *
 * @param int $booking_id The booking ID.
 * @return object|null Booking object or null if not found.
 */
public static function get_booking( $booking_id ) {
    // ...
}
```

### File Organization
- One class per file
- Descriptive filenames matching class names
- Consistent directory structure
- Clear separation of concerns

## Debugging

### Enable WordPress Debug
```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Log Debug Messages
```php
if ( WP_DEBUG ) {
    error_log( 'Booking created: ' . print_r( $booking_data, true ) );
}
```

### Check Database Queries
```php
global $wpdb;
error_log( 'Last query: ' . $wpdb->last_query );
error_log( 'Affected rows: ' . $wpdb->rows_affected );
```

## Version History

- **1.0.0**: Initial release
  - Core booking functionality
  - Location-based search
  - Rating system
  - REST API
  - Admin dashboard
  - Frontend shortcodes

## Future Enhancements

- [ ] Payment processing (Stripe/PayPal)
- [ ] Email/SMS notifications
- [ ] Advanced search filters (price, rating, category)
- [ ] Calendar integration for scheduling
- [ ] Mobile app
- [ ] Video consultation support
- [ ] Insurance verification
- [ ] Background check integration
- [ ] Subscription plans for workers
- [ ] Commission/referral system

## Support & Maintenance

For issues or questions:
1. Check debug.log in wp-content/
2. Verify all required database tables exist
3. Check user roles and capabilities
4. Test with default WordPress theme
5. Disable other plugins to check conflicts

---

**Last Updated**: 2024
**Plugin Version**: 1.0.0
**Status**: Production Ready
