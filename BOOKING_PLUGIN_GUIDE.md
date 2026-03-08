# Booking Services Plugin - Complete Documentation

## Overview

The Booking Services Plugin is a comprehensive WordPress solution that enables service-based businesses to create a two-sided marketplace where:
- **Service Workers** can list their services and manage bookings
- **Service Customers** can discover nearby services and make bookings

## Features

### Core Features

✅ **Worker-side Management**
- Create and manage multiple services
- Set service pricing and duration
- Manage service locations and service radius
- Track bookings and earnings
- Receive and respond to booking requests
- Build reputation through customer ratings/reviews

✅ **Customer-side Features**
- Search services by location
- Filter services by category
- View worker ratings and reviews
- Book services with address and preferred time
- Manage booking history
- Leave ratings and reviews

✅ **Location-based Search**
- Geo-location enabled service discovery
- Distance calculation using Haversine formula
- Service radius management per location
- Nearby services filtering

✅ **Booking Management**
- Complete booking lifecycle (pending → accepted → completed)
- Booking status tracking
- Customer-worker communication via notes
- Booking history

✅ **Ratings & Reviews**
- Customer can rate workers after service completion
- Average rating display
- Completed job count tracking

✅ **REST API**
- Full REST API for mobile app integration
- Location-based service endpoints
- Booking management endpoints

## Installation & Setup

### 1. Plugin Activation
The plugin is already installed in `/wp-content/plugins/custom-plugin/`

On activation, the following occur automatically:
- Custom database tables are created
- Custom post types are registered (Service, Booking)
- Custom roles are created (Service Worker, Service Customer)
- Rewrite rules are flushed

### 2. Create Test Users

#### Worker User:
1. Go to **WordPress Admin → Users → Add New**
2. Create a user with username: `worker1`
3. Set password and email
4. Assign role: **Service Worker**
5. Click **Add New User**

#### Customer User:
1. Create another user with username: `customer1`
2. Assign role: **Service Customer**

### 3. Create Service Categories

1. Navigate to **Services → Service Categories**
2. Create categories like:
   - Plumbing
   - Electrical
   - Painting
   - Gardening
   - Catering
   - Shoe Repair
   - House Cleaning
   - etc.

## Worker Workflow

### Step 1: Create a Service

1. Login as a service worker
2. Navigate to **Services → Add New Service**
3. Fill in the following:
   - **Title**: Service name (e.g., "Professional Plumbing")
   - **Description**: Detailed service description
   - **Featured Image**: Service photo
   - **Service Category**: Select one or more categories
   - **Price**: Set the service rate
   - **Duration**: Estimated hours needed
   - **Excerpt**: Brief service summary

4. Click **Publish**

### Step 2: Set Service Locations

After creating a service, add the areas where you provide service:

#### Option A: Via Admin Dashboard
1. Go to **Services → Manage Services**
2. Find your service and click "Edit"
3. Scroll to service meta and save

#### Option B: Via Worker Dashboard
1. Create a page with shortcode: `[cp_worker_services]`
2. Worker can manage locations from there

#### Adding Location Details:
- Include your service radius (e.g., 50km)
- Set your latitude/longitude (use GPS coordinates or location lookup)
- Name the location area

### Step 3: Manage Bookings

1. Go to **Services → Bookings Dashboard**
2. View all pending bookings for your services
3. Actions:
   - **Accept**: Click to accept a booking
   - **View Details**: See customer location and notes
   - **Mark Complete**: When service is done
   - **Cancel**: If you need to decline

## Customer Workflow

### Step 1: Access Service Browser

Create a page with the shortcode:
```
[cp_service_browser]
```

This provides a full search and booking interface.

### Step 2: Search Services

1. Click **"Use My Location"** to enable location access
2. Optionally search by service type (e.g., "plumber")
3. Click **"Search"** to find nearby services
4. Results show:
   - Service name and worker
   - Distance from your location
   - Worker rating
   - Price per hour

### Step 3: Book a Service

1. Click **"Book Service"** on any service card
2. Fill in the modal form:
   - **Preferred Date & Time**: When you need the service
   - **Service Location**: Your address where service will be done
   - **Notes**: Any special requests
3. Click **"Confirm Booking"**
4. Booking is created with "Pending" status
5. Worker will accept or decline

### Step 4: Track Bookings

Create a page with shortcode:
```
[cp_customer_bookings]
```

This shows:
- All customer bookings
- Booking status
- Service details
- Amount paid

### Step 5: Leave Reviews

After service completion (status = "completed"):
1. Go to **My Bookings** page
2. Click on completed booking
3. Click **"Leave Review"** button
4. Rate 1-5 stars
5. Write review text
6. Submit

## Admin/Dashboard Features

### Worker Management
**Path**: Services → Worker Management

Shows:
- All service workers
- Number of completed jobs per worker
- Average rating
- Quick edit link

### Bookings Dashboard
**Path**: Services → Bookings Dashboard

Shows:
- Real-time booking stats
- Pending/accepted/completed counts
- Full booking history with filters
- Status badges

### Manage Services
**Path**: Services → Manage Services

Shows:
- All services in the system
- Category assignment
- Creator info
- Quick edit/view links

## Available Shortcodes

### For Customers:

```
[cp_service_browser]
```
Displays service search and discovery interface with:
- Location-based search
- Service cards with ratings
- Booking modal

```
[cp_customer_bookings]
```
Shows customer's booking history and status

### For Workers:

```
[cp_worker_dashboard]
```
Worker dashboard showing:
- Jobs completed count
- Average rating
- Quick links to manage services

```
[cp_worker_services]
```
List worker's created services with edit/view options

## Database Schema

### wp_service_bookings
- `id`: Primary key
- `booking_id`: Unique booking ID (BK-YYYYMMDDHHMMSS-XXXXX)
- `service_id`: Post ID of service
- `worker_id`: User ID of service worker
- `customer_id`: User ID of customer
- `scheduled_date`: When service is booked for
- `service_location`: Customer's service address
- `service_latitude`: Customer's service location latitude
- `service_longitude`: Customer's service location longitude
- `status`: pending|accepted|in_progress|completed|cancelled
- `total_amount`: Price for this booking
- `notes`: Customer special requests
- `created_at`: Booking creation timestamp

### wp_worker_service_locations
- `id`: Primary key
- `worker_id`: Worker user ID
- `service_id`: Service post ID
- `location_name`: Area name (e.g., "Downtown")
- `latitude`: Worker's service location latitude
- `longitude`: Worker's service location longitude
- `service_radius_km`: Service coverage radius
- `is_active`: Active/inactive status

### wp_service_ratings
- `id`: Primary key
- `booking_id`: Booking ID
- `customer_id`: Customer user ID
- `worker_id`: Worker user ID
- `service_id`: Service post ID
- `rating`: 1-5 star rating
- `review`: Review text
- `created_at`: Review creation timestamp

## REST API Endpoints

### Available Endpoints

#### Get Nearby Services
```
GET /wp-json/cp/v1/services/nearby?latitude=40.7128&longitude=-74.0060&limit=20
```
Returns services within radius of given coordinates

#### Create Booking
```
POST /wp-json/cp/v1/bookings
Body: {
  "service_id": 123,
  "worker_id": 45,
  "scheduled_date": "2024-03-15 14:30",
  "service_location": "123 Main St",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "total_amount": 50.00,
  "notes": "Please bring tools"
}
```

#### Get User Bookings
```
GET /wp-json/cp/v1/bookings/user
```
Returns current user's bookings (worker or customer)

#### Update Booking Status
```
PUT /wp-json/cp/v1/bookings/{id}/status
Body: {"status": "accepted"}
```
Allowed statuses: pending, accepted, in_progress, completed, cancelled

#### Get Worker Services
```
GET /wp-json/cp/v1/workers/{id}/services
```
Returns all services by a specific worker

#### Get Worker Locations
```
GET /wp-json/cp/v1/workers/{id}/locations
```
Returns service locations for a worker

#### Add Service Location
```
POST /wp-json/cp/v1/locations
Body: {
  "service_id": 123,
  "location_name": "Downtown",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "service_radius_km": 50
}
```

#### Add Rating
```
POST /wp-json/cp/v1/ratings
Body: {
  "booking_id": 456,
  "worker_id": 45,
  "service_id": 123,
  "rating": 5,
  "review": "Excellent service!"
}
```

## Configuration

### Service Categories
Categories are created via **Services → Service Categories**

Suggested categories:
- Plumbing
- Electrical Work
- House Painting
- Gardening/Landscaping
- Catering/Cooking
- Shoe Repair
- House Cleaning
- HVAC Services
- Carpentry
- Moving Services

### Settings Notes
- Service workers can set their own service radius (default: 50km)
- Bookings use Haversine formula for accurate distance calculation
- All prices stored in decimal format (currency agnostic)
- Booking status flow: pending → accepted → in_progress → completed

## Security Features

✅ **Role-based Access Control**
- Only Service Workers can create/edit services
- Only workers can view their own bookings
- Customers can only view their bookings

✅ **AJAX & Form Security**
- Nonce verification on all AJAX requests
- Capability checks on all actions
- Sanitization of all input data
- XSS protection with escaping

✅ **Database Security**
- Prepared statements for all queries
- User ID validation
- Data type enforcement

## Troubleshooting

### Location Not Working
1. Check browser permissions for location access
2. Ensure HTTPS is enabled (required for geolocation)
3. Allow location permission in browser settings

### Bookings Not Appearing
1. Verify worker has created service locations
2. Check service status is published
3. Ensure worker user role is set correctly

### Database Errors
1. Check MySQL database tables exist:
   - `wp_service_bookings`
   - `wp_worker_service_locations`
   - `wp_service_ratings`
2. Reactivate plugin to recreate tables
3. Check WordPress debug.log

## Advanced Usage

### Mobile App Integration
Use REST API endpoints to build native iOS/Android apps:
```
// Example: Get nearby services
fetch('https://yoursite.com/wp-json/cp/v1/services/nearby?latitude=40.7128&longitude=-74.0060')
  .then(r => r.json())
  .then(data => console.log(data));
```

### Custom Filters
Developers can hook into the following actions:
- `cp_booking_created` - When booking is created
- `cp_booking_status_updated` - When status changes
- `cp_rating_added` - When review is added
- `cp_service_location_added` - When location is added

## Support & Development

For modifications or custom features, the plugin is structured for easy extension:

```
/includes/
  /classes/      - Core business logic
  /post-types/   - Custom post type definitions
  /admin/        - Admin interface
  /frontend/     - Customer & worker interfaces
  /api/          - REST API endpoints
  /database/     - Database operations
/assets/
  /css/          - Stylesheets
  /js/           - JavaScript functionality
```

## Version

- **Plugin Version**: 1.0.0
- **Minimum WordPress Version**: 5.0
- **PHP Version**: 7.4+
- **Author**: My Biz Niche

## License

GPL v2 or later
