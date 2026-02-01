<?php

namespace ClassBooking\Elementor;

use ClassBooking\Admin\Taxonomy\BookingCategoryTaxonomy;
use ClassBooking\Front\Shortcode\BookingListShortcode;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined('ABSPATH') || exit;

/**
 * Elementor Booking List Widget.
 *
 * Displays a list of bookings from a category with accordion calendars.
 */
final class BookingListWidget extends Widget_Base
{
    /**
     * Get widget name.
     */
    public function get_name(): string
    {
        return 'booking-list';
    }

    /**
     * Get widget title.
     */
    public function get_title(): string
    {
        return __('Booking List', 'class-booking');
    }

    /**
     * Get widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-post-list';
    }

    /**
     * Get widget categories.
     */
    public function get_categories(): array
    {
        return ['general'];
    }

    /**
     * Get widget keywords.
     */
    public function get_keywords(): array
    {
        return ['booking', 'list', 'category', 'classes', 'courses'];
    }

    /**
     * Whether the reload preview is required.
     */
    public function is_reload_preview_required(): bool
    {
        return true;
    }

    /**
     * Register widget controls.
     */
    protected function register_controls(): void
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'class-booking'),
            ]
        );

        $this->add_control(
            'category',
            [
                'label' => __('Select Category', 'class-booking'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->getCategoryOptions(),
                'default' => '',
                'label_block' => true,
                'description' => __('Select the booking category to display.', 'class-booking'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get booking category options for the select control.
     */
    private function getCategoryOptions(): array
    {
        $options = ['' => __('— Select a category —', 'class-booking')];

        $categories = get_terms([
            'taxonomy' => BookingCategoryTaxonomy::TAXONOMY,
            'hide_empty' => false,
        ]);

        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $options[$category->slug] = $category->name;
            }
        }

        return $options;
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $category = $settings['category'] ?? '';

        if (empty($category)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="cb-elementor-placeholder">';
                echo '<p>' . esc_html__('Please select a category from the widget settings.', 'class-booking') . '</p>';
                echo '</div>';
            }
            return;
        }

        // Use the existing shortcode render method
        echo BookingListShortcode::render(['category' => $category]);
    }

    /**
     * Render widget output in the editor (live preview disabled).
     */
    protected function content_template(): void
    {
        // Empty - we use PHP render with is_reload_preview_required = true
    }
}

