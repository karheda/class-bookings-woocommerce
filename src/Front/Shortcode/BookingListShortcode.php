<?php

namespace ClassBooking\Front\Shortcode;

use ClassBooking\Admin\Taxonomy\BookingCategoryTaxonomy;
use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class BookingListShortcode
{
    private static int $instanceCount = 0;

    public static function register(): void
    {
        add_shortcode('booking_list', [self::class, 'render']);
    }

    public static function render(array $attrs = []): string
    {
        $attrs = shortcode_atts([
            'category' => '',
        ], $attrs);

        $categorySlug = sanitize_text_field($attrs['category']);

        if (empty($categorySlug)) {
            return '<p class="cb-error">Please specify a category attribute.</p>';
        }

        // Get bookings in this category
        $bookings = get_posts([
            'post_type' => 'booking',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => BookingCategoryTaxonomy::TAXONOMY,
                    'field' => 'slug',
                    'terms' => $categorySlug,
                ],
            ],
        ]);

        if (empty($bookings)) {
            return '<p class="cb-no-bookings">No classes found in this category.</p>';
        }

        self::enqueueAssets();

        ob_start();
        ?>
        <div class="cb-booking-list">
            <?php foreach ($bookings as $booking): ?>
                <?php echo self::renderBookingCard($booking); ?>
            <?php endforeach; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function renderBookingCard(\WP_Post $booking): string
    {
        self::$instanceCount++;
        $containerId = 'cb-booking-' . self::$instanceCount;

        $price = get_post_meta($booking->ID, '_price', true) ?: 0;
        $thumbnail = get_the_post_thumbnail_url($booking->ID, 'medium');
        $excerpt = wp_trim_words($booking->post_content, 20, '...');

        $repository = new ClassSessionRepository();
        $availableDates = $repository->getAvailableDates($booking->ID);
        $hasAvailability = !empty($availableDates);

        // Add inline script for this booking's calendar
        if ($hasAvailability) {
            self::addCalendarInitScript($containerId, $booking->ID, $availableDates, (float) $price);
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr($containerId); ?>" class="cb-booking-card" data-booking-id="<?php echo esc_attr($booking->ID); ?>">
            <div class="cb-card-header" role="button" tabindex="0" aria-expanded="false">
                <?php if ($thumbnail): ?>
                    <div class="cb-card-image">
                        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($booking->post_title); ?>" />
                    </div>
                <?php endif; ?>
                <div class="cb-card-info">
                    <h3 class="cb-card-title"><?php echo esc_html($booking->post_title); ?></h3>
                    <?php if ($excerpt): ?>
                        <p class="cb-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <div class="cb-card-meta">
                        <span class="cb-card-price"><?php echo wc_price($price); ?></span>
                        <?php if (!$hasAvailability): ?>
                            <span class="cb-card-status cb-no-availability">No dates available</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cb-card-toggle">
                    <svg class="cb-toggle-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>

            <?php if ($hasAvailability): ?>
                <div class="cb-card-content" aria-hidden="true">
                    <?php echo self::renderCalendarContent($containerId, $booking, $price); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function renderCalendarContent(string $containerId, \WP_Post $booking, float $price): string
    {
        ob_start();
        ?>
        <div class="cb-booking-widget cb-accordion-widget">
            <div class="cb-booking-body">
                <!-- Calendar Section -->
                <div class="cb-calendar-section">
                    <h4>Select a Date</h4>
                    <div class="cb-calendar">
                        <div class="cb-calendar-header">
                            <span class="cb-calendar-title"></span>
                            <div class="cb-calendar-nav">
                                <button type="button" class="cb-nav-prev" aria-label="Previous month">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </button>
                                <button type="button" class="cb-nav-next" aria-label="Next month">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="cb-calendar-weekdays">
                            <span class="cb-calendar-weekday">Mon</span>
                            <span class="cb-calendar-weekday">Tue</span>
                            <span class="cb-calendar-weekday">Wed</span>
                            <span class="cb-calendar-weekday">Thu</span>
                            <span class="cb-calendar-weekday">Fri</span>
                            <span class="cb-calendar-weekday">Sat</span>
                            <span class="cb-calendar-weekday">Sun</span>
                        </div>
                        <div class="cb-calendar-days"></div>
                    </div>
                </div>

                <!-- Sessions Section -->
                <div class="cb-sessions-section">
                    <h4>Available Times</h4>
                    <div class="cb-sessions-list"></div>
                    <div class="cb-sessions-placeholder">
                        <span>Select a date to see available times</span>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <form class="cb-booking-form" method="post" style="display: none;">
                <input type="hidden" name="class_booking_action" value="reserve" />
                <input type="hidden" name="session_id" value="" />
                <?php wp_nonce_field('cb_reserve_session', 'cb_reserve_nonce'); ?>

                <div class="cb-form-row">
                    <div class="cb-form-group">
                        <label>Number of Persons</label>
                        <div class="cb-quantity-selector">
                            <button type="button" class="cb-quantity-btn minus">−</button>
                            <input type="number" name="class_booking_quantity" class="cb-quantity-input" value="1" min="1" max="10" />
                            <button type="button" class="cb-quantity-btn plus">+</button>
                        </div>
                    </div>
                </div>

                <div class="cb-booking-summary">
                    <div class="cb-summary-details"></div>
                    <div class="cb-summary-total"></div>
                </div>

                <button type="submit" class="cb-submit-btn" disabled>
                    Add to Cart
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function addCalendarInitScript(string $containerId, int $bookingId, array $availableDates, float $price): void
    {
        $initScript = sprintf(
            'jQuery(document).ready(function($) { if (typeof ClassBookingCalendar !== "undefined") { ClassBookingCalendar.init(%s, %d, %s, %s); } });',
            json_encode($containerId),
            $bookingId,
            json_encode($availableDates),
            json_encode($price)
        );

        wp_add_inline_script('class-booking-frontend-calendar', $initScript);
    }

    private static function enqueueAssets(): void
    {
        // Enqueue CSS
        wp_enqueue_style(
            'class-booking-frontend-calendar',
            plugin_dir_url(dirname(__DIR__)) . 'assets/frontend-calendar.css',
            [],
            '1.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'class-booking-frontend-calendar',
            plugin_dir_url(dirname(__DIR__)) . 'assets/frontend-calendar.js',
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script with config
        wp_localize_script('class-booking-frontend-calendar', 'cbCalendarConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_calendar_nonce'),
        ]);
    }
}
