<?php

namespace ClassBooking\Admin\Rest;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Error;

defined('ABSPATH') || exit;

final class BookingRestValidation
{
    public static function register(): void
    {
        add_filter(
            'rest_pre_insert_booking',
            [self::class, 'validate'],
            10,
            2
        );
    }

    public static function validate($preparedPost, $request)
    {
        if (
            empty($request['meta']['add_booking_session']) ||
            $request['meta']['add_booking_session'] !== '1'
        ) {
            return $preparedPost;
        }

        $postId = isset($preparedPost->ID) ? (int) $preparedPost->ID : 0;

        $sessionDate = sanitize_text_field($request['meta']['session_date'] ?? '');
        $startTime   = sanitize_text_field($request['meta']['start_time'] ?? '');
        $endTime     = sanitize_text_field($request['meta']['end_time'] ?? '');
        $capacity    = (int) ($request['meta']['capacity'] ?? 0);

        if (!$sessionDate || !$startTime || !$endTime || $capacity < 1) {
            return new WP_Error(
                'invalid_session_data',
                'Invalid session data.',
                ['status' => 400]
            );
        }

        $repo = new ClassSessionRepository();

        if ($repo->hasOverlappingSession(
            $postId,
            $sessionDate,
            $startTime,
            $endTime
        )) {
            return new WP_Error(
                'session_overlap',
                'This session overlaps with an existing one.',
                ['status' => 409]
            );
        }

        return $preparedPost;
    }
}
