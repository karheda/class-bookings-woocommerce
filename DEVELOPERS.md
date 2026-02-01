# Class Booking Plugin - Developer Documentation

## Overview

Custom WordPress/WooCommerce booking system for fixed-time classes with limited capacity. Built with domain-driven design principles.

**Key Concept**: One class = one reservable unit. Capacity is the source of truth, not WooCommerce stock.

## Development Environment

### Docker Setup

```bash
# Start environment
make up

# Stop environment
make down

# Access PHP container shell
make ssh

# Rebuild containers
make build
```

- **PHP Container**: `zarapita_wp` (PHP 8.3)
- **Database Container**: `zarapita_db` (MariaDB 10.6)
- **WordPress URL**: http://localhost:8080
- **Database Port**: 3336

### Composer Commands (inside container)

```bash
# Install dependencies
composer install

# Install production only
composer install --no-dev

# Run tests
vendor/bin/phpunit --testdox
```

### Build & Distribution

The build script runs tests and security checks before generating the release ZIP.

```bash
# Full build (recommended)
./build.sh

# Skip tests (not recommended)
./build.sh --skip-tests

# Skip security checks
./build.sh --skip-security

# Show help
./build.sh --help
```

**Output**: `dist/class-booking-{version}.zip`

#### Build Steps

| Step | Description | Blocking |
|------|-------------|----------|
| 1/6 | Check Docker container is running | ✓ |
| 2/6 | Run PHPUnit tests | ✓ |
| 3/6 | Security checks | ✓ |
| 4/6 | Prepare build directory | - |
| 5/6 | Copy files & install dependencies | - |
| 6/6 | Create ZIP archive | - |

#### Security Checks

| Check | Description | Blocking |
|-------|-------------|----------|
| ABSPATH | All PHP files have `defined('ABSPATH') \|\| exit;` | ✓ |
| Input sanitization | Detects `$_GET/$_POST` without sanitization | ⚠ Warning |
| Nonce verification | Form handlers verify nonces | ✓ |
| REST permissions | All endpoints have `permission_callback` | ✓ |
| SQL injection | Detects queries without `prepare()` | ⚠ Warning |
| PHP syntax | No syntax errors in PHP files | ✓ |

**Note**: Warnings (⚠) are displayed but don't block the build. Blocking checks (✓) will abort the build if they fail.

## Architecture

```
src/
├── Activation/           # Plugin activation/deactivation
│   └── Activator.php
├── Admin/                # WordPress admin UI
│   ├── Handler/          # Form handlers
│   ├── Metabox/          # Custom metaboxes
│   │   ├── BookingPriceMetabox.php
│   │   └── BookingSessionsMetabox.php
│   ├── Notice/           # Admin notices
│   ├── PostType/         # CPT registration
│   │   └── BookingPostType.php
│   ├── Rest/             # REST API endpoints
│   │   └── SessionsRestController.php
│   └── Taxonomy/         # Custom taxonomies
│       └── BookingCategoryTaxonomy.php
├── Domain/               # Business logic
│   └── Service/
│       ├── ClassSessionSyncService.php
│       └── WooCommerceProductSyncService.php
├── Elementor/            # Elementor widgets
│   ├── BookingListWidget.php
│   └── ClassBookingWidget.php
├── Front/                # Public-facing
│   ├── Ajax/             # AJAX handlers
│   │   └── GetSessionsByDateHandler.php
│   ├── Handler/          # Form handlers
│   │   └── ReserveClassHandler.php
│   └── Shortcode/        # Shortcodes
│       ├── BookingListShortcode.php
│       └── ClassSessionsShortcode.php
├── Infrastructure/       # Database layer
│   ├── Database/
│   │   ├── Migration.php
│   │   └── Schema.php
│   └── Repository/
│       └── ClassSessionRepository.php
├── Plugin.php            # Main bootstrap
├── WooCommerce/          # WooCommerce integration
│   └── Hooks/
│       ├── AddToCartValidation.php
│       ├── ClassSessionSaveHook.php
│       ├── DisableCartQuantity.php
│       └── OrderCompleted.php
└── assets/               # CSS/JS assets
    ├── admin-booking.js
    ├── admin-sessions.css
    ├── admin-sessions.js
    ├── frontend-calendar.css
    └── frontend-calendar.js
```

## Database Schema

### Custom Table: `{prefix}_class_sessions`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `post_id` | BIGINT | Booking post ID |
| `capacity` | INT | Total spots |
| `remaining_capacity` | INT | Available spots |
| `session_date` | DATE | Session date |
| `start_time` | TIME | Start time |
| `end_time` | TIME | End time |
| `status` | VARCHAR(20) | active/inactive |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Update timestamp |

**Unique Constraint**: `(post_id, session_date, start_time, end_time)`

## REST API

**Base URL**: `/wp-json/class-booking/v1/sessions`

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/sessions?post_id={id}` | List sessions | `edit_posts` |
| GET | `/sessions/{id}` | Get session | `edit_posts` |
| POST | `/sessions` | Create session | `edit_posts` |
| PUT | `/sessions/{id}` | Update session | `edit_posts` |
| DELETE | `/sessions/{id}` | Delete session | `delete_posts` |
| PATCH | `/sessions/{id}/status` | Toggle status | `edit_posts` |

### Create/Update Payload

```json
{
  "post_id": 123,
  "session_date": "2024-12-25",
  "start_time": "10:00:00",
  "end_time": "11:00:00",
  "capacity": 10,
  "status": "active"
}
```

## Shortcodes

### `[class_sessions]`
Displays calendar for a single class.

```php
[class_sessions class_id="123"]
```

### `[booking_list]`
Displays accordion list of classes by category.

```php
[booking_list category="yoga"]
```

## Elementor Widgets

| Widget | Description |
|--------|-------------|
| **Class Booking Calendar** | Calendar for a single booking |
| **Booking List** | Category-based list with accordions |

## Post Meta Keys

| Key | Description |
|-----|-------------|
| `_price` | Price per person |
| `_product_id` | Linked WooCommerce product ID |
| `_booking_error` | Error messages for admin notices |

## Data Flow

1. **Admin creates booking** → Post saved
2. **Save hook triggers** → `ClassSessionSaveHook`
3. **Sync service runs** → Creates/updates WooCommerce product
4. **Frontend displays** → Calendar via shortcode/widget
5. **User reserves** → Product added to cart
6. **Cart validation** → Capacity check
7. **Order completed** → Capacity reduced atomically

## Testing

```bash
# Run all tests
docker exec zarapita_wp bash -c "cd /var/www/html/wp-content/plugins/class-booking && vendor/bin/phpunit --testdox"

# Run specific suite
vendor/bin/phpunit --testsuite Integration
```

### Test Coverage

| Component | Tests |
|-----------|-------|
| ClassSessionRepository | 20 |
| AddToCartValidation | 7 |
| SessionsRestController | 11 |
| DisableCartQuantity | 4 |
| **Total** | **42** |

## Security

### Implemented Measures

- **Nonces**: All forms and AJAX use WordPress nonces
- **Capability Checks**: REST API requires `edit_posts`/`delete_posts`
- **Sanitization**: All inputs sanitized with `sanitize_text_field()`, `(int)`, etc.
- **Escaping**: All outputs escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- **Prepared Statements**: All SQL uses `$wpdb->prepare()`
- **ABSPATH Check**: All PHP files check `defined('ABSPATH') || exit;`

## Development Guidelines

1. **Business logic** → `Domain/Service/`
2. **Database queries** → `Infrastructure/Repository/`
3. **WooCommerce hooks** → `WooCommerce/Hooks/`
4. **Admin UI** → `Admin/` (metaboxes, handlers)
5. **Frontend** → `Front/` (shortcodes, handlers)

### Patterns

- Repository handles all `$wpdb` queries
- Services contain business logic
- Hooks are static classes with `register()` method
- All classes use `final` and constructor property promotion (PHP 8.1+)
- PSR-4 autoloading via Composer (`ClassBooking\` namespace)

