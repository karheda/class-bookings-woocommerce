<?php

namespace ClassBooking;

use ClassBooking\Admin\Handler\AddBookingSessionHandler;
use ClassBooking\Admin\Metabox\BookingPriceMetabox;
use ClassBooking\Admin\Metabox\BookingSessionsMetabox;
use ClassBooking\Admin\Notice\BookingAdminNotices;
use ClassBooking\Admin\PostType\BookingPostType;
use ClassBooking\Front\Handler\ReserveClassHandler;
use ClassBooking\Front\Shortcode\ClassSessionsShortcode;
use ClassBooking\WooCommerce\Hooks\AddToCartValidation;
use ClassBooking\WooCommerce\Hooks\ClassSessionSaveHook;
use ClassBooking\WooCommerce\Hooks\DisableCartQuantity;
use ClassBooking\WooCommerce\Hooks\OrderCompleted;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function init(): void
    {
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

        ClassSessionsShortcode::register();
        AddToCartValidation::register();
        OrderCompleted::register();
        DisableCartQuantity::register();

        add_action('admin_enqueue_scripts', function ($hook) {
            global $post;

            if (!$post || $post->post_type !== 'booking') {
                return;
            }

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