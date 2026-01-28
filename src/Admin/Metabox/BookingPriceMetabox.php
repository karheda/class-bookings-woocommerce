<?php

namespace ClassBooking\Admin\Metabox;

defined('ABSPATH') || exit;

final class BookingPriceMetabox
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetabox']);
        add_action('save_post_booking', [self::class, 'save'], 10, 2);
    }

    public static function addMetabox(): void
    {
        add_meta_box(
            'booking_price',
            __('Booking Price', 'class-booking'),
            [self::class, 'render'],
            'booking',
            'side',
            'high'
        );
    }

    public static function render(\WP_Post $post): void
    {
        wp_nonce_field('booking_price_save', 'booking_price_nonce');

        $price = get_post_meta($post->ID, '_price', true);
        ?>

        <p>
            <label for="booking_price">
                <?php _e('Price per person (€)', 'class-booking'); ?>
            </label>
        </p>

        <input
            type="number"
            name="booking_price"
            id="booking_price"
            step="0.01"
            min="0"
            value="<?php echo esc_attr($price); ?>"
            style="width:100%;"
            required
        />

        <?php
    }

    public static function save(int $postId, \WP_Post $post): void
    {
        if (
            !isset($_POST['booking_price_nonce']) ||
            !wp_verify_nonce($_POST['booking_price_nonce'], 'booking_price_save')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'booking') {
            return;
        }

        if (!isset($_POST['booking_price'])) {
            return;
        }

        update_post_meta(
            $postId,
            '_price',
            (float) $_POST['booking_price']
        );
    }
}
