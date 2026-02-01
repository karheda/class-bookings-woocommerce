<?php

namespace ClassBooking\Front\Ajax;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

/**
 * AJAX handler for getting sessions by date
 */
final class GetSessionsByDateHandler
{
    public static function register(): void
    {
        add_action('wp_ajax_cb_get_sessions_by_date', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_cb_get_sessions_by_date', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'cb_calendar_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token.'], 403);
        }

        $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';

        if (!$classId || !$date) {
            wp_send_json_error(['message' => 'Missing required parameters.'], 400);
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(['message' => 'Invalid date format.'], 400);
        }

        $repository = new ClassSessionRepository();
        $sessions = $repository->getSessionsByDate($classId, $date);

        wp_send_json_success($sessions);
    }
}

