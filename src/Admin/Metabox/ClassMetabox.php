<?php

namespace ClassBooking\Admin\Metabox;

defined('ABSPATH') || exit;

final class ClassMetabox
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetabox']);
        add_action('save_post_class', [self::class, 'save'], 10, 2);
    }

    public static function addMetabox(): void
    {
        add_meta_box(
            'class_price',
            __('Class Price', 'class-booking'),
            [self::class, 'render'],
            'class',
            'side',
            'high'
        );
    }

    public static function render(\WP_Post $post): void
    {
        wp_nonce_field('class_price_save', 'class_price_nonce');

        $price = get_post_meta($post->ID, '_price', true);
        ?>

        <p>
            <label for="class_price">
                <?php _e('Price per person (€)', 'class-booking'); ?>
            </label>
        </p>

        <input
            type="number"
            name="class_price"
            id="class_price"
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
            !isset($_POST['class_price_nonce']) ||
            !wp_verify_nonce($_POST['class_price_nonce'], 'class_price_save')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'class') {
            return;
        }

        if (!isset($_POST['class_price'])) {
            return;
        }

        $price = (float) $_POST['class_price'];

        update_post_meta($postId, '_price', $price);
    }
}
