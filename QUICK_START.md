# Quick Start Guide - Booking Services Plugin

## 5-Minute Setup

### 1. Test the Plugin Immediately

After activation, verify everything works:

1. Check **Services** menu in WordPress admin appears ✓
2. Check database tables created by going to a tool like phpMyAdmin and looking for:
   - `wp_service_bookings`
   - `wp_worker_service_locations`
   - `wp_service_ratings`

### 2. Create Service Categories

1. Go: **Dashboard → Services → Service Categories**
2. Add these example categories:
   - ✏️ Plumbing
   - ✏️ Electrical
   - ✏️ Painting
   - ✏️ Gardening
   - ✏️ Catering
   - ✏️ Repairs

### 3. Create Test Users

#### Test Worker:
```
Dashboard → Users → Add New

Username: plumber_john
Email: john@example.com
Password: (generate secure)
Role: Service Worker
```

#### Test Customer:
```
Dashboard → Users → Add New

Username: customer_alice
Email: alice@example.com
Password: (generate secure)
Role: Service Customer
```

### 4. Create a Test Service (as Worker)

1. Logout and login as `plumber_john`
2. Go: **Services → Add New Service**
3. Fill in:
   ```
   Title: Professional Plumbing Services
   Description: Reliable plumbing for homes and businesses
   Category: Plumbing
   Price: 75.00
   Duration: 2
   ```
4. Upload a featured image
5. Click **Publish**

### 5. Create Frontend Pages

Create these WordPress pages:

#### Page 1: Service Discovery
```
Title: Find Services
Content: [cp_service_browser]
Publish
- Get URL and share with customers
- Example: /find-services/
```

#### Page 2: Worker Dashboard
```
Title: My Dashboard
Content: [cp_worker_dashboard]
Publish
- For workers to see their stats
- Example: /my-dashboard/
```

#### Page 3: My Bookings
```
Title: My Bookings
Content: [cp_customer_bookings]
Publish
- For customers to track bookings
- Example: /my-bookings/
```

#### Page 4: My Services
```
Title: My Services
Content: [cp_worker_services]
Publish
- For workers to manage services
- Example: /my-services/
```

### 6. Test the Workflow

**As Customer:**
1. Open `/find-services/` page
2. Click "Use My Location" (allow browser location)
3. Click "Search"
4. You should see plumber_john's service
5. Click "Book Service"
6. Fill in booking form and submit

**As Worker:**
1. Go to **Services → Bookings Dashboard**
2. You should see the pending booking
3. Click "Accept" (or view details)
4. Update status as service progresses

**As Customer:**
1. Go to `/my-bookings/` page
2. See booking status updates
3. After completion, leave a review

## Common Issues & Solutions

### Issue: No Services Show in Search
**Solution:**
1. Verify worker created a service and published it
2. Check service status is "Publish"
3. Clear browser cache
4. Check browser allows location access

### Issue: Location Services Not Working
**Solution:**
1. Ensure site uses HTTPS (required for geolocation)
2. Check browser location permissions
3. Allow location in browser settings
4. Check browser console for errors (F12)

### Issue: Admin Pages Not Showing
**Solution:**
1. Check user role is set correctly
2. Verify plugin is activated
3. Go to **Dashboard → Services** menu should appear
4. Try deactivating and reactivating

## Admin Pages Reference

| Page | Path | Access |
|------|------|--------|
| Services List | Services → Services | All |
| Add Service | Services → Add New | Workers |
| Categories | Services → Service Categories | All |
| Manage Services | Services → Manage Services | Workers |
| Bookings Dashboard | Services → Bookings Dashboard | Workers, Admins |
| Worker Management | Services → Worker Management | Admins Only |

## User Roles

### Service Worker
**Capabilities:**
- Create/edit own services
- View own bookings
- Accept/decline bookings
- Update booking status
- Receive ratings

### Service Customer
**Capabilities:**
- Search services by location
- Book services
- View own bookings
- Leave reviews and ratings

### Administrator
**Full Access To:**
- All worker management
- View all bookings
- Manage service categories
- Worker reports and statistics

## Database Tables Reference

### Booking Flow
```
Customer searches → Finds service → Books → Creates wp_service_bookings entry

Worker views booking → Accepts → Status changes to 'accepted' → Completes service → 'completed'

Customer rates → Creates wp_service_ratings entry

System calculates avg rating from wp_service_ratings
```

### Location Matching Algorithm
```
Customer Location: (40.7128, -74.0060)
Worker Location: (40.7150, -74.0050) with radius 50km

Distance calculated using Haversine formula:
- If distance <= worker's service_radius_km → Show in results
```

## Shortcodes Cheatsheet

```
// Service Discovery (for customers)
[cp_service_browser]

// Worker Stats Dashboard
[cp_worker_dashboard]

// Customer Bookings
[cp_customer_bookings]

// Worker Services Manager
[cp_worker_services]
```

## REST API Quick Examples

### Get Nearby Services
```bash
curl "https://yoursite.com/wp-json/cp/v1/services/nearby?latitude=40.7128&longitude=-74.0060&limit=20"
```

### Create Booking Via API
```bash
curl -X POST https://yoursite.com/wp-json/cp/v1/bookings \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 123,
    "worker_id": 45,
    "scheduled_date": "2024-03-15 14:30",
    "service_location": "123 Main St",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "total_amount": 75.00
  }'
```

## Next Steps

1. **Customize Colors**: Edit `/assets/css/frontend.css` and `/assets/css/admin.css`
2. **Add Payment Integration**: Hook payment gateway to `cp_booking_created` action
3. **Email Notifications**: Add email on booking status changes
4. **SMS Notifications**: Send SMS via Twilio on status updates
5. **Advanced Filtering**: Filter by service category, price range, etc.

## Key Files

```
/wp-content/plugins/custom-plugin/

├── functions.php                           ← Main plugin file
├── BOOKING_PLUGIN_GUIDE.md                 ← Full documentation
├── QUICK_START.md                          ← This file
│
├── includes/
│   ├── classes/                            ← Business logic
│   │   ├── class-booking-manager.php       ← Booking operations
│   │   ├── class-location-manager.php      ← Location/geo logic
│   │   └── class-roles.php                 ← User roles
│   │
│   ├── post-types/                         ← Post type definitions
│   │   └── class-service-post-type.php
│   │
│   ├── admin/                              ← Admin pages
│   │   └── class-admin.php
│   │
│   ├── frontend/                           ← Customer/worker pages
│   │   ├── class-frontend.php
│   │   └── class-shortcodes.php
│   │
│   ├── api/                                ← REST API
│   │   └── class-rest-api.php
│   │
│   └── database/                           ← Database schema
│       └── class-database.php
│
└── assets/
    ├── css/                                ← Stylesheets
    │   ├── frontend.css
    │   └── admin.css
    └── js/                                 ← JavaScript
        └── frontend.js
```

## Support Queries

**Q: Can I change the service radius?**
A: Yes! Each worker can set their own radius per service location (default: 50km)

**Q: Can I add payment processing?**
A: Yes! Hook into `cp_booking_created` action to integrate Stripe, PayPal, etc.

**Q: Can multiple workers provide same service?**
A: Yes! Multiple workers can create the same service type in different locations.

**Q: How do I handle service cancellations?**
A: Change booking status to "cancelled" in Bookings Dashboard

**Q: Can customers provide ratings before completion?**
A: No! Ratings only show after booking status is "completed"

## File Permissions

Ensure these directories are writable:
```
/wp-content/plugins/custom-plugin/    ✓ (Usually is)
/wp-content/uploads/                  ✓ (Service images)
```

## Performance Tips

1. Use CDN for service images
2. Implement caching for service listings
3. Index booking tables on common queries
4. Limit API results to sensible numbers (max 100)

---

**Version**: 1.0
**Last Updated**: 2024
**Status**: Production Ready
