<?php

namespace ClassBooking\Blocks;

use ClassBooking\Admin\Taxonomy\BookingCategoryTaxonomy;
use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class BookingListBlock
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerBlock']);
    }

    public static function registerBlock(): void
    {
        register_block_type('class-booking/booking-list', [
            'api_version' => 3,
            'editor_script' => 'class-booking-blocks-editor',
            'editor_style' => 'class-booking-blocks-editor-style',
            'style' => 'class-booking-frontend-calendar',
            'render_callback' => [self::class, 'render'],
            'attributes' => [
                'category' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'primaryColor' => [
                    'type' => 'string',
                    'default' => '#2271b1',
                ],
                'secondaryColor' => [
                    'type' => 'string',
                    'default' => '#f0f0f1',
                ],
                'textColor' => [
                    'type' => 'string',
                    'default' => '#1d2327',
                ],
                'accentColor' => [
                    'type' => 'string',
                    'default' => '#d63638',
                ],
            ],
        ]);

        // Register editor assets
        self::registerEditorAssets();
    }

    private static function registerEditorAssets(): void
    {
        // Editor script
        wp_register_script(
            'class-booking-blocks-editor',
            plugin_dir_url(dirname(__FILE__)) . 'assets/blocks/booking-list-block.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data'],
            CLASS_BOOKING_VERSION,
            true
        );

        // Pass categories to editor
        $categories = get_terms([
            'taxonomy' => BookingCategoryTaxonomy::TAXONOMY,
            'hide_empty' => false,
        ]);

        $categoryOptions = [];
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $categoryOptions[] = [
                    'label' => $category->name,
                    'value' => $category->slug,
                ];
            }
        }

        wp_localize_script('class-booking-blocks-editor', 'cbBlockData', [
            'categories' => $categoryOptions,
            'previewUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_block_preview'),
        ]);

        // Editor styles
        wp_register_style(
            'class-booking-blocks-editor-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/blocks/booking-list-block-editor.css',
            ['wp-edit-blocks'],
            CLASS_BOOKING_VERSION
        );
    }

    public static function render(array $attributes): string
    {
        $category = sanitize_text_field($attributes['category'] ?? '');
        $primaryColor = sanitize_hex_color($attributes['primaryColor'] ?? '#2271b1');
        $secondaryColor = sanitize_hex_color($attributes['secondaryColor'] ?? '#f0f0f1');
        $textColor = sanitize_hex_color($attributes['textColor'] ?? '#1d2327');
        $accentColor = sanitize_hex_color($attributes['accentColor'] ?? '#d63638');

        if (empty($category)) {
            return '<p class="cb-error">' . esc_html__('Please select a category in the block settings.', 'class-booking') . '</p>';
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
                    'terms' => $category,
                ],
            ],
        ]);

        if (empty($bookings)) {
            return '<p class="cb-no-bookings">' . esc_html__('No classes found in this category.', 'class-booking') . '</p>';
        }

        self::enqueueAssets();

        // Build custom CSS variables
        $customStyles = sprintf(
            '--cb-primary-color: %s; --cb-secondary-color: %s; --cb-text-color: %s; --cb-accent-color: %s;',
            esc_attr($primaryColor),
            esc_attr($secondaryColor),
            esc_attr($textColor),
            esc_attr($accentColor)
        );

        ob_start();
        ?>
        <div class="cb-booking-list cb-gutenberg-block" style="<?php echo esc_attr($customStyles); ?>">
            <?php foreach ($bookings as $booking): ?>
                <?php echo self::renderBookingCard($booking); ?>
            <?php endforeach; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function renderBookingCard(\WP_Post $booking): string
    {
        static $instanceCount = 0;
        $instanceCount++;
        $containerId = 'cb-gutenberg-booking-' . $instanceCount;

        $price = get_post_meta($booking->ID, '_price', true) ?: 0;
        $thumbnail = get_the_post_thumbnail_url($booking->ID, 'medium');
        $excerpt = wp_trim_words($booking->post_content, 20, '...');

        $repository = new ClassSessionRepository();
        $availableDates = $repository->getAvailableDates($booking->ID);
        $hasAvailability = !empty($availableDates);

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
                            <span class="cb-card-status cb-no-availability"><?php esc_html_e('No dates available', 'class-booking'); ?></span>
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
                <div class="cb-calendar-section">
                    <h4><?php esc_html_e('Select a Date', 'class-booking'); ?></h4>
                    <div class="cb-calendar">
                        <div class="cb-calendar-header">
                            <span class="cb-calendar-title"></span>
                            <div class="cb-calendar-nav">
                                <button type="button" class="cb-nav-prev" aria-label="<?php esc_attr_e('Previous month', 'class-booking'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </button>
                                <button type="button" class="cb-nav-next" aria-label="<?php esc_attr_e('Next month', 'class-booking'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="cb-calendar-weekdays">
                            <span class="cb-calendar-weekday"><?php esc_html_e('Mon', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Tue', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Wed', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Thu', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Fri', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Sat', 'class-booking'); ?></span>
                            <span class="cb-calendar-weekday"><?php esc_html_e('Sun', 'class-booking'); ?></span>
                        </div>
                        <div class="cb-calendar-days"></div>
                    </div>
                </div>

                <div class="cb-sessions-section">
                    <h4><?php esc_html_e('Available Times', 'class-booking'); ?></h4>
                    <div class="cb-sessions-list"></div>
                    <div class="cb-sessions-placeholder">
                        <span><?php esc_html_e('Select a date to see available times', 'class-booking'); ?></span>
                    </div>
                </div>
            </div>

            <form class="cb-booking-form" method="post" style="display: none;">
                <input type="hidden" name="class_booking_action" value="reserve" />
                <input type="hidden" name="session_id" value="" />
                <?php wp_nonce_field('cb_reserve_session', 'cb_reserve_nonce'); ?>

                <div class="cb-form-row">
                    <div class="cb-form-group">
                        <label><?php esc_html_e('Number of Persons', 'class-booking'); ?></label>
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
                    <?php esc_html_e('Add to Cart', 'class-booking'); ?>
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
            wp_json_encode($containerId),
            $bookingId,
            wp_json_encode($availableDates),
            wp_json_encode($price)
        );

        wp_add_inline_script('class-booking-frontend-calendar', $initScript);
    }

    private static function enqueueAssets(): void
    {
        wp_enqueue_style(
            'class-booking-frontend-calendar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/frontend-calendar.css',
            [],
            CLASS_BOOKING_VERSION
        );

        wp_enqueue_script(
            'class-booking-frontend-calendar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/frontend-calendar.js',
            ['jquery'],
            CLASS_BOOKING_VERSION,
            true
        );

        wp_localize_script('class-booking-frontend-calendar', 'cbCalendarConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_calendar_nonce'),
        ]);
    }
}

