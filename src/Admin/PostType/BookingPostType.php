<?php

namespace ClassBooking\Admin\PostType;

defined('ABSPATH') || exit;

final class BookingPostType
{
    public static function register(): void
    {
        register_post_type('booking', [
            'labels' => self::labels(),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-calendar',
            'supports' => [
                'title',
                'editor',
                'thumbnail',
            ],
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'rewrite' => false,
            'show_in_rest' => true,
        ]);
    }

    private static function labels(): array
    {
        return [
            'name'               => __('Bookings', 'class-booking'),
            'singular_name'      => __('Booking', 'class-booking'),
            'menu_name'          => __('Bookings', 'class-booking'),
            'name_admin_bar'     => __('Booking', 'class-booking'),
            'add_new'            => __('Add New', 'class-booking'),
            'add_new_item'       => __('Add New Booking', 'class-booking'),
            'edit_item'          => __('Edit Booking', 'class-booking'),
            'new_item'           => __('New Booking', 'class-booking'),
            'view_item'          => __('View Booking', 'class-booking'),
            'search_items'       => __('Search Bookings', 'class-booking'),
            'not_found'          => __('No bookings found', 'class-booking'),
            'not_found_in_trash' => __('No bookings found in Trash', 'class-booking'),
        ];
    }
}
