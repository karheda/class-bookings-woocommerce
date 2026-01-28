<?php

namespace ClassBooking\Admin\Handler;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class AddBookingSessionHandler
{
    public static function handle(int $postId, WP_Post $post, bool $update): void
    {
        if ($post->post_type !== 'booking') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        if (
            !isset($_POST['add_booking_session']) ||
            $_POST['add_booking_session'] !== '1'
        ) {
            return;
        }

        if (
            !isset($_POST['add_booking_session_nonce']) ||
            !wp_verify_nonce($_POST['add_booking_session_nonce'], 'add_booking_session')
        ) {
            return;
        }

        $sessionDate = sanitize_text_field($_POST['session_date'] ?? '');
        $startTime   = sanitize_text_field($_POST['start_time'] ?? '');
        $endTime     = sanitize_text_field($_POST['end_time'] ?? '');
        $capacity    = (int) ($_POST['capacity'] ?? 0);

        if (!$sessionDate || !$startTime || !$endTime || $capacity < 1) {
            return;
        }

        $repository = new ClassSessionRepository();

        if ($repository->hasOverlappingSession(
            $postId,
            $sessionDate,
            $startTime,
            $endTime
        )) {
            update_post_meta($postId, '_booking_error', 'session_overlap');
            error_log('Booking session overlap detected for post ID ' . $postId);
            error_log(
                'META AFTER SAVE: ' .
                print_r(get_post_meta($postId, '_booking_error', true), true)
            );
            return;
        }

        $repository->insert([
            'class_post_id'      => $postId,
            'session_date'       => $sessionDate,
            'start_time'         => $startTime,
            'end_time'           => $endTime,
            'capacity'           => $capacity,
            'remaining_capacity' => $capacity,
            'status'             => 'active',
        ]);
    }
}
