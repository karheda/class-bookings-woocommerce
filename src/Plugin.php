<?php

namespace ClassBooking;

use ClassBooking\Admin\Handler\AddBookingSessionHandler;
use ClassBooking\Admin\Metabox\BookingPriceMetabox;
use ClassBooking\Admin\Metabox\BookingSessionsMetabox;
use ClassBooking\Admin\Notice\BookingAdminNotices;
use ClassBooking\Admin\PostType\BookingPostType;
use ClassBooking\Admin\Rest\SessionsRestController;
use ClassBooking\Front\Ajax\GetSessionsByDateHandler;
use ClassBooking\Front\Handler\ReserveClassHandler;
use ClassBooking\Front\Shortcode\ClassSessionsShortcode;
use ClassBooking\Infrastructure\Database\Migration;
use ClassBooking\WooCommerce\Hooks\AddToCartValidation;
use ClassBooking\WooCommerce\Hooks\ClassSessionSaveHook;
use ClassBooking\WooCommerce\Hooks\DisableCartQuantity;
use ClassBooking\WooCommerce\Hooks\OrderCompleted;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function init(): void
    {
        // Run database migrations
        add_action('plugins_loaded', [Migration::class, 'run']);

        add_action('init', [BookingPostType::class, 'register']);
        add_action('init', [ReserveClassHandler::class, 'handle']);
        add_action('save_post_booking', [AddBookingSessionHandler::class, 'handle'], 10, 3);
        add_action('admin_notices', [BookingAdminNotices::class, 'display']);

        add_action('init', function () {
            register_post_meta('booking', '_booking_error', [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ]);
        });

        add_filter('use_block_editor_for_post_type', function ($useBlockEditor, $postType) {
            if ($postType === 'booking') {
                return false;
            }
            return $useBlockEditor;
        }, 10, 2);

        BookingPriceMetabox::register();
        BookingSessionsMetabox::register();
        ClassSessionSaveHook::register();

        // Register REST API endpoints
        add_action('rest_api_init', function () {
            $controller = new SessionsRestController();
            $controller->register_routes();
        });

        ClassSessionsShortcode::register();
        GetSessionsByDateHandler::register();
        AddToCartValidation::register();
        OrderCompleted::register();
        DisableCartQuantity::register();

        add_action('admin_enqueue_scripts', function ($hook) {
            global $post;

            if (!$post || $post->post_type !== 'booking') {
                return;
            }

            // Enqueue Flatpickr (date/time picker library)
            wp_enqueue_style(
                'flatpickr',
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
                [],
                '4.6.13'
            );

            wp_enqueue_script(
                'flatpickr',
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
                [],
                '4.6.13',
                true
            );

            // Flatpickr Spanish locale
            wp_enqueue_script(
                'flatpickr-es',
                'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js',
                ['flatpickr'],
                '4.6.13',
                true
            );

            // Enqueue sessions management styles
            wp_enqueue_style(
                'class-booking-admin-sessions',
                plugin_dir_url(__FILE__) . 'assets/admin-sessions.css',
                ['flatpickr'],
                '1.0.1'
            );

            // Enqueue sessions management script
            wp_enqueue_script(
                'class-booking-admin-sessions',
                plugin_dir_url(__FILE__) . 'assets/admin-sessions.js',
                ['jquery', 'flatpickr', 'flatpickr-es'],
                '1.0.1',
                true
            );

            // Localize script with translations and config
            wp_localize_script('class-booking-admin-sessions', 'classBookingAdmin', [
                'nonce' => wp_create_nonce('wp_rest'),
                'apiUrl' => rest_url('class-booking/v1/sessions'),
                'i18n' => [
                    'addSession' => __('Add New Session', 'class-booking'),
                    'editSession' => __('Edit Session', 'class-booking'),
                    'saveSuccess' => __('Session saved successfully.', 'class-booking'),
                    'saveError' => __('Failed to save session.', 'class-booking'),
                    'loadError' => __('Failed to load session data.', 'class-booking'),
                    'deleteSuccess' => __('Session deleted successfully.', 'class-booking'),
                    'deleteError' => __('Failed to delete session.', 'class-booking'),
                    'statusSuccess' => __('Session status updated successfully.', 'class-booking'),
                    'statusError' => __('Failed to update session status.', 'class-booking'),
                    'confirmDelete' => __('Are you sure you want to delete this session? This action cannot be undone.', 'class-booking'),
                    'confirmToggle' => __('Are you sure you want to change the status of this session?', 'class-booking'),
                ],
            ]);

            // Legacy script (keep for compatibility)
            wp_enqueue_script(
                'class-booking-admin',
                plugin_dir_url(__FILE__) . 'assets/admin-booking.js',
                ['wp-data', 'wp-notices'],
                '1.0',
                true
            );
        });
    }
}