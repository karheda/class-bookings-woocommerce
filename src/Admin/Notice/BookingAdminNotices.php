<?php

namespace ClassBooking\Admin\Notice;

final class BookingAdminNotices
{

    public static function display(): void
    {
        global $post;

        if (
            !$post ||
            $post->post_type !== 'booking'
        ) {
            return;
        }

        $error = get_post_meta($post->ID, '_booking_error', true);

        if ($error !== 'session_overlap') {
            return;
        }

        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Session overlaps with an existing one.</strong></p>';
        echo '<p>Please choose a different time range.</p>';
        echo '</div>';

        delete_post_meta($post->ID, '_booking_error');
    }
}