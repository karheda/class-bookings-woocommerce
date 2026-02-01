<?php

namespace ClassBooking\Elementor;

use ClassBooking\Front\Shortcode\ClassSessionsShortcode;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined('ABSPATH') || exit;

/**
 * Elementor Class Booking Calendar Widget.
 *
 * Displays a booking calendar for a selected class.
 */
final class ClassBookingWidget extends Widget_Base
{
    /**
     * Get widget name.
     */
    public function get_name(): string
    {
        return 'class-booking-calendar';
    }

    /**
     * Get widget title.
     */
    public function get_title(): string
    {
        return __('Class Booking Calendar', 'class-booking');
    }

    /**
     * Get widget icon.
     */
    public function get_icon(): string
    {
        return 'eicon-calendar';
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
        return ['booking', 'calendar', 'class', 'reservation', 'schedule'];
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
            'class_id',
            [
                'label' => __('Select Class', 'class-booking'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->getBookingOptions(),
                'default' => '',
                'label_block' => true,
                'description' => __('Select the class to display the booking calendar for.', 'class-booking'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get booking post options for the select control.
     */
    private function getBookingOptions(): array
    {
        $options = ['' => __('— Select a class —', 'class-booking')];

        $bookings = get_posts([
            'post_type' => 'booking',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        foreach ($bookings as $booking) {
            $options[$booking->ID] = $booking->post_title;
        }

        return $options;
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $classId = $settings['class_id'] ?? '';

        if (empty($classId)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="cb-elementor-placeholder">';
                echo '<p>' . esc_html__('Please select a class from the widget settings.', 'class-booking') . '</p>';
                echo '</div>';
            }
            return;
        }

        // Use the existing shortcode render method
        echo ClassSessionsShortcode::render(['class_id' => $classId]);
    }

    /**
     * Render widget output in the editor (live preview disabled).
     */
    protected function content_template(): void
    {
        // Empty - we use PHP render with is_reload_preview_required = true
    }
}

