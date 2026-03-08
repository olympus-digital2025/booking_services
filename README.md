# Booking Services Plugin

A comprehensive WordPress plugin for managing two-sided service bookings with geolocation support, worker management, and customer reviews.

## Overview

The **Booking Services Plugin** provides a complete marketplace solution for service-based businesses (plumbing, painting, cleaning, etc.). It features a dual-user system where workers can manage service locations and availability, and customers can discover nearby services and book workers.

**Key Features:**
- 👥 **Dual User Roles**: Service Workers and Service Customers
- 📍 **Geolocation-Based Discovery**: Find services within a specified radius
- 📅 **Booking Management**: Full lifecycle booking system (pending → accepted → completed)
- ⭐ **Rating System**: Customers can review and rate workers
- 🛠️ **Admin Dashboard**: Manage all services, bookings, workers, and ratings
- 🔌 **REST API**: 8 endpoints for mobile app integration
- 🔒 **Security**: Nonce verification, role-based capabilities, data sanitization
- 📊 **Database**: Optimized schema with custom tables for bookings, locations, and ratings

---

## Table of Contents

1. [Installation & Setup](#installation--setup)
2. [Plugin Structure](#plugin-structure)
3. [Core Features](#core-features)
4. [Usage Guide](#usage-guide)
5. [REST API Endpoints](#rest-api-endpoints)
6. [Admin Dashboard](#admin-dashboard)
7. [Vendor Packages & Autoload](#vendor-packages--autoload)
8. [Linting & Code Quality](#linting--code-quality)

---

## Installation & Setup

1. Download or clone the plugin into `wp-content/plugins/custom-plugin/`
2. Run `composer install` to install dependencies
3. Activate the plugin from the WordPress admin panel
4. The plugin automatically creates:
   - Custom user roles (Service Worker, Service Customer)
   - Custom post type for Services
   - Three database tables for bookings, locations, and ratings

---

## Plugin Structure

```
custom-plugin/
├── includes/
│   ├── admin/              # Admin dashboard pages
│   ├── api/                # REST API endpoints
│   ├── classes/            # Core business logic
│   ├── database/           # Database schema
│   ├── frontend/           # Frontend AJAX and shortcodes
│   └── post-types/         # Custom post types
├── assets/
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript files
├── functions.php           # Main plugin file
├── phpcs.xml              # Code standards configuration
├── composer.json          # Dependencies
└── README.md              # This file
```

---

## Core Features

### 1. Service Management
- Register and manage service offerings
- Assign services to worker locations
- Track service availability and radius

### 2. Booking System
- Create, track, and update bookings
- Status workflow: pending → accepted → in_progress → completed
- Support for scheduling and location-based bookings

### 3. Geolocation Services
- Add multiple service locations with coordinates
- Discover nearby workers within custom radius
- Calculate distance between customer and worker

### 4. Rating & Reviews
- Leave reviews and ratings after service completion
- View worker statistics and ratings
- Build worker reputation system

### 5. User Roles & Capabilities
- **Service Worker**: Manage bookings and service locations
- **Service Customer**: Book services and leave reviews
- **Administrator**: Full access to all management features

---

## Usage Guide

### For Service Workers
1. Set up service locations with address and coordinates
2. Define service radius for your operating area
3. Accept or decline incoming bookings
4. Update booking status as work progresses
5. View customer reviews and ratings

### For Service Customers
1. Browse available services by category
2. Search for nearby service workers
3. View worker profiles and ratings
4. Book a service at preferred date/time
5. Track booking status
6. Leave a review after service completion

### For Administrators
1. Access **Manage Services** page to view all registered services
2. Use **Bookings Dashboard** to monitor all bookings
3. Manage **Service Workers** and their locations
4. Track **Ratings & Reviews**
5. Generate reports and analytics

---

## REST API Endpoints

The plugin provides 8 REST API endpoints for mobile apps and third-party integrations:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/cp/v1/services/nearby` | GET | Find services near coordinates |
| `/cp/v1/bookings` | POST | Create a new booking |
| `/cp/v1/bookings/user` | GET | Get user's bookings |
| `/cp/v1/bookings/{id}/status` | PUT | Update booking status |
| `/cp/v1/workers/{id}/services` | GET | Get worker's services |
| `/cp/v1/workers/{id}/locations` | GET | Get worker's locations |
| `/cp/v1/locations` | POST | Add a service location |
| `/cp/v1/ratings` | POST | Add rating/review |

All endpoints require authentication except `nearby` and worker info endpoints.

---

## Admin Dashboard

The plugin adds menu items under **Services**:

- **Manage Services**: CRUD interface for service management
- **Bookings Dashboard**: Monitor and manage bookings
- **Worker Management**: View and manage service workers
- **Ratings & Reviews**: View all customer reviews

---

## Database Schema

### wp_service_bookings
- ID, booking reference, service, worker, customer
- Scheduled date, status, location coordinates
- Amount, notes, timestamps

### wp_worker_service_locations
- Worker ID, service ID, location name
- Latitude, longitude, service radius
- Status tracking

### wp_service_ratings
- Booking ID, customer, worker, service
- Rating (1-5), review text, timestamp

---

## Vendor Packages & Autoload

This plugin uses Composer and loads `vendor/autoload.php` for:

- **yahnis-elsts/plugin-update-checker** (PucFactory) – update checks from GitHub.

### Adding or changing vendor packages

1. Run `composer require <package>` or edit `composer.json` and run `composer update`.
2. For packages that might be loaded by other plugins (e.g. PUC), keep the `class_exists()` guard before `require_once ... vendor/autoload.php` so only one autoload runs.

---

## Linting & Code Quality

This plugin follows **WordPress PHP Coding Standards** using PHP CodeSniffer (PHPCS).

### Available Commands

- **Conditional lint** (runs PHPCS only when plugin code has functions, methods, classes, or `use` statements): `composer run lint`
- **Unconditional lint**: `composer run lint:all` or `./vendor/bin/phpcs`
- **Auto-fix**: `composer run lint:fix` or `./vendor/bin/phpcbf`
- **Fix twice then lint** (all-in-one): `composer run lint:run`

### Branch Protection

To block merges to any branch when lint fails, enable branch protection and require the **Lint** status check; see [.github/BRANCH_PROTECTION.md](.github/BRANCH_PROTECTION.md).
