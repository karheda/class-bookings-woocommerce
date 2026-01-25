<?php

namespace ClassBooking\Admin\PostType;

defined('ABSPATH') || exit;

final class ClassSessionPostType
{
    public static function register(): void
    {
        register_post_type('class_session', [
            'labels' => self::labels(),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_admin_bar' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => [
                'title',
                'editor',
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
            'name'               => __('Class Sessions', 'class-booking'),
            'singular_name'      => __('Class Session', 'class-booking'),
            'menu_name'          => __('Class Sessions', 'class-booking'),
            'name_admin_bar'     => __('Class Session', 'class-booking'),
            'add_new'            => __('Add New', 'class-booking'),
            'add_new_item'       => __('Add New Class Session', 'class-booking'),
            'edit_item'          => __('Edit Class Session', 'class-booking'),
            'new_item'           => __('New Class Session', 'class-booking'),
            'view_item'          => __('View Class Session', 'class-booking'),
            'search_items'       => __('Search Class Sessions', 'class-booking'),
            'not_found'          => __('No class sessions found', 'class-booking'),
            'not_found_in_trash' => __('No class sessions found in Trash', 'class-booking'),
        ];
    }
}
