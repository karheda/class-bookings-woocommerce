<?php

namespace ClassBooking\Admin\Metabox;

defined('ABSPATH') || exit;

final class ClassSessionMetabox
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetabox']);
        add_action('save_post_class_session', [self::class, 'save'], 10, 2);
    }

    public static function addMetabox(): void
    {
        add_meta_box(
            'class_session_details',
            __('Class Session Details', 'class-booking'),
            [self::class, 'render'],
            'class_session',
            'normal',
            'high'
        );
    }

    public static function render(\WP_Post $post): void
    {
        wp_nonce_field('class_session_save', 'class_session_nonce');

        $capacity   = get_post_meta($post->ID, '_capacity', true);
        $price      = get_post_meta($post->ID, '_price', true);
        $startDate  = get_post_meta($post->ID, '_start_date', true);
        $endDate    = get_post_meta($post->ID, '_end_date', true);
        $weekday    = get_post_meta($post->ID, '_weekday', true);
        $startTime  = get_post_meta($post->ID, '_start_time', true);
        $endTime    = get_post_meta($post->ID, '_end_time', true);
        ?>

        <table class="form-table">
            <tr>
                <th><label for="capacity"><?php _e('Capacity', 'class-booking'); ?></label></th>
                <td>
                    <input type="number" name="capacity" id="capacity" min="1"
                           value="<?php echo esc_attr($capacity); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="price"><?php _e('Price (€)', 'class-booking'); ?></label></th>
                <td>
                    <input type="number" step="0.01" name="price" id="price"
                           value="<?php echo esc_attr($price); ?>" />
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Active Period', 'class-booking'); ?></label></th>
                <td>
                    <input type="date" name="start_date" value="<?php echo esc_attr($startDate); ?>" />
                    —
                    <input type="date" name="end_date" value="<?php echo esc_attr($endDate); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="weekday"><?php _e('Weekday', 'class-booking'); ?></label></th>
                <td>
                    <select name="weekday" id="weekday">
                        <?php
                        $days = [
                            'monday'    => 'Monday',
                            'tuesday'   => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday'  => 'Thursday',
                            'friday'    => 'Friday',
                            'saturday'  => 'Saturday',
                            'sunday'    => 'Sunday',
                        ];
                        foreach ($days as $key => $label) {
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($key),
                                selected($weekday, $key, false),
                                esc_html($label)
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Time Range', 'class-booking'); ?></label></th>
                <td>
                    <input type="time" name="start_time" value="<?php echo esc_attr($startTime); ?>" />
                    —
                    <input type="time" name="end_time" value="<?php echo esc_attr($endTime); ?>" />
                </td>
            </tr>
        </table>

        <?php
    }

    public static function save(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['class_session_nonce']) ||
            !wp_verify_nonce($_POST['class_session_nonce'], 'class_session_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'class_session') {
            return;
        }

        $fields = [
            '_capacity'    => FILTER_VALIDATE_INT,
            '_price'       => FILTER_VALIDATE_FLOAT,
            '_start_date'  => FILTER_SANITIZE_SPECIAL_CHARS,
            '_end_date'    => FILTER_SANITIZE_SPECIAL_CHARS,
            '_weekday'     => FILTER_SANITIZE_SPECIAL_CHARS,
            '_start_time'  => FILTER_SANITIZE_SPECIAL_CHARS,
            '_end_time'    => FILTER_SANITIZE_SPECIAL_CHARS,
        ];

        foreach ($fields as $key => $filter) {
            $formKey = ltrim($key, '_');
            if (isset($_POST[$formKey])) {
                $value = filter_var($_POST[$formKey], $filter);
                update_post_meta($postId, $key, $value);
            }
        }
    }
}

