<?php

namespace ClassBooking\Admin\Taxonomy;

defined('ABSPATH') || exit;

final class BookingCategoryTaxonomy
{
    public const TAXONOMY = 'booking_category';

    public static function register(): void
    {
        register_taxonomy(self::TAXONOMY, 'booking', [
            'labels' => self::labels(),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => false,
        ]);
    }

    private static function labels(): array
    {
        return [
            'name'              => __('Categories', 'class-booking'),
            'singular_name'     => __('Category', 'class-booking'),
            'menu_name'         => __('Categories', 'class-booking'),
            'all_items'         => __('All Categories', 'class-booking'),
            'edit_item'         => __('Edit Category', 'class-booking'),
            'view_item'         => __('View Category', 'class-booking'),
            'update_item'       => __('Update Category', 'class-booking'),
            'add_new_item'      => __('Add New Category', 'class-booking'),
            'new_item_name'     => __('New Category Name', 'class-booking'),
            'parent_item'       => __('Parent Category', 'class-booking'),
            'parent_item_colon' => __('Parent Category:', 'class-booking'),
            'search_items'      => __('Search Categories', 'class-booking'),
            'not_found'         => __('No categories found', 'class-booking'),
        ];
    }
}

