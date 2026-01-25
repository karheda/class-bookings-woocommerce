# Class Booking Plugin

Custom booking system for classes and events built on top of **WordPress** and **WooCommerce**, designed to handle **fixed-time classes with limited capacity** without relying on third-party booking plugins.

This plugin is built as a **domain-driven, extensible solution**, focused on clarity, control, and long-term maintainability.

---

## ✨ Features (Current)

- Custom Post Type: **Class Session**
- Admin metaboxes for:
  - Capacity
  - Price
  - Active period (start / end date)
  - Weekday
  - Time range
- Custom database table for bookings domain logic
- Automatic sync:
  - Class Session ⇄ Database table
  - Class Session ⇄ WooCommerce Product
- Frontend listing via shortcode
- WooCommerce integration:
  - Add to cart validation (capacity check)
  - Atomic capacity reduction on order completion
- No calendars
- No time slot generators
- No external booking plugins

---

## 🧠 Design Principles

- **One class = one reservable unit**
- **Capacity is the source of truth**, not WooCommerce stock
- **WooCommerce is used for payments only**
- Clear separation of concerns:
  - Admin (CMS)
  - Domain (business rules)
  - Infrastructure (database)
  - Frontend (presentation)
  - WooCommerce integration (hooks)

---

## 🏗 Architecture Overview

```bash
class-booking/
├─ class-booking.php # Plugin bootstrap
├─ composer.json # Autoload & dev dependencies
├─ src/
│ ├─ Admin/ # CPTs, metaboxes, admin UI
│ ├─ Domain/ # Business logic & services
│ ├─ Infrastructure/ # Database schema & repositories
│ ├─ Front/ # Shortcodes & frontend logic
│ └─ WooCommerce/ # WooCommerce hooks & integration
├─ templates/ # Frontend / admin templates
├─ tests/ # Unit & integration tests
└─ vendor/ # Composer dependencies (ignored)
```

---

## 📦 Requirements

- PHP **8.1+** (developed with PHP 8.3)
- WordPress **6.x**
- WooCommerce **8.x**
- MariaDB **10.6+** (dev) / **11.x** (production)
- Composer

---

## 🚀 Installation (Development)

1. Clone the repository into:

```bash
wp-content/plugins/class-booking
```

2. Inside the PHP container (or local environment):

```bash
composer install
```

3. Activate the plugin from **WordPress Admin → Plugins**

4. On activation:
- The custom database table is created automatically

---

## 🧪 Development Setup

This project is designed to run inside a **Dockerized WordPress environment**.

- The PHP container is used to:
- run Composer
- execute tests
- develop the plugin
- No local PHP or Node setup is required

---

## 🖥 Usage

### Admin

1. Go to **Class Sessions**
2. Create a new session:
- Set title and description
- Fill in capacity, price, dates, weekday and time
3. Save

A hidden WooCommerce product is automatically created and kept in sync.

---

### Frontend

Use the shortcode:

```bash
[class_sessions]
```

This renders a list of available class sessions with a **Reserve** button.

---

## 🛒 Booking Flow

1. User clicks **Reserve**
2. Product is added to cart
3. Capacity is validated before checkout
4. On order completion:
   - Remaining capacity is reduced atomically
5. Overbooking is prevented at database level

---

## ⚠️ Current Limitations (By Design)

- Quantity selector (number of persons) not implemented yet
- No rollback on refunds or cancellations yet
- No calendar UI
- No recurring session engine

These features are planned and tracked.

---

## 🗺 Roadmap (Short Term)

- Quantity selector (multiple persons per booking)
- Capacity rollback on order cancellation / refund
- Filtering sessions by month or period
- Improved frontend markup and styling
- Unit and integration tests

---

## 🧩 Why Not WooCommerce Bookings?

This plugin was created to avoid:

- Complex calendar interfaces
- Generated time slots
- Over-engineered booking logic
- Expensive yearly licenses

It is tailored specifically for **academies, classes, and fixed-schedule events**.

---

## 📄 License

Proprietary (for now).

---

## 👨‍💻 Author

Developed as a custom solution with a strong focus on:

- Clean architecture
- Real-world booking constraints
- Long-term maintainability

