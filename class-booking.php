<?php
/**
 * Plugin Name: Class Booking
 * Description: Custom booking system for classes with capacity control.
 * Version: 0.1.0
 * Author: Your Name
 * Text Domain: class-booking
 */

defined('ABSPATH') || exit;

define('CLASS_BOOKING_PATH', plugin_dir_path(__FILE__));
define('CLASS_BOOKING_URL', plugin_dir_url(__FILE__));
define('CLASS_BOOKING_VERSION', '0.1.0');

require_once CLASS_BOOKING_PATH . 'vendor/autoload.php';

use ClassBooking\Plugin;
use ClassBooking\Activation\Activator;

register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Activator::class, 'deactivate']);

add_action('plugins_loaded', static function () {
    Plugin::init();
});
