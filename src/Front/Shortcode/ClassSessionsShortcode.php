<?php

namespace ClassBooking\Front\Shortcode;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class ClassSessionsShortcode
{
    private static int $instanceCount = 0;

    public static function register(): void
    {
        add_shortcode('class_sessions', [self::class, 'render']);
    }

    public static function render(array $attrs = []): string
    {
        $attrs = shortcode_atts([
            'class_id' => null,
        ], $attrs);

        $classPostId = (int) $attrs['class_id'];

        if (!$classPostId) {
            return '<p class="cb-error">Please specify a class_id attribute.</p>';
        }

        $repository = new ClassSessionRepository();
        $availableDates = $repository->getAvailableDates($classPostId);

        if (empty($availableDates)) {
            return '<p class="cb-no-sessions">No classes are available at the moment.</p>';
        }

        // Get class info
        $classPost = get_post($classPostId);
        $price = get_post_meta($classPostId, '_price', true) ?: 0;
        $className = $classPost ? $classPost->post_title : 'Class';

        // Generate unique ID for this instance
        self::$instanceCount++;
        $containerId = 'cb-calendar-' . self::$instanceCount;

        // Enqueue frontend assets
        self::enqueueAssets($containerId, $classPostId, $availableDates, $price);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($containerId); ?>" class="cb-booking-widget">
            <div class="cb-booking-header">
                <h3><?php echo esc_html($className); ?></h3>
            </div>

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

    private static function enqueueAssets(string $containerId, int $classId, array $availableDates, float $price): void
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

        // Add inline script to initialize this calendar instance
        $initScript = sprintf(
            'jQuery(document).ready(function($) { ClassBookingCalendar.init(%s, %d, %s, %s); });',
            json_encode($containerId),
            $classId,
            json_encode($availableDates),
            json_encode($price)
        );

        wp_add_inline_script('class-booking-frontend-calendar', $initScript);
    }
}
