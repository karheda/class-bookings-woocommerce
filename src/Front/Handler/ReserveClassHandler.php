<?php

namespace ClassBooking\Front\Handler;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class ReserveClassHandler
{
    public static function handle(): void
    {
        if (
            !isset($_POST['class_booking_action']) ||
            $_POST['class_booking_action'] !== 'reserve'
        ) {
            return;
        }

        // Check for session_id (new form) or class_booking_session_id (legacy)
        $sessionId = 0;
        if (isset($_POST['session_id'])) {
            $sessionId = (int) $_POST['session_id'];
        } elseif (isset($_POST['class_booking_session_id'])) {
            $sessionId = (int) $_POST['class_booking_session_id'];
        }

        $persons = isset($_POST['class_booking_quantity']) ? (int) $_POST['class_booking_quantity'] : 1;

        if (!$sessionId) {
            return;
        }

        // Verify nonce if present (new form)
        if (isset($_POST['cb_reserve_nonce'])) {
            if (!wp_verify_nonce($_POST['cb_reserve_nonce'], 'cb_reserve_session')) {
                wc_add_notice(
                    __('Security check failed. Please try again.', 'class-booking'),
                    'error'
                );
                wp_safe_redirect(wp_get_referer() ?: home_url());
                exit;
            }
        }

        if (!WC()->cart) {
            return;
        }

        if ($persons < 1) {
            wc_add_notice(
                __('Invalid number of persons.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wp_get_referer() ?: home_url());
            exit;
        }

        $repo = new ClassSessionRepository();
        $session = $repo->find($sessionId);

        if (!$session || $session['status'] !== 'active') {
            wc_add_notice(
                __('This session is no longer available.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wp_get_referer() ?: home_url());
            exit;
        }

        if ($persons > (int) $session['remaining_capacity']) {
            wc_add_notice(
                __('Not enough available spots for this session.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wp_get_referer() ?: home_url());
            exit;
        }

        // Get the WooCommerce product ID from the booking post
        $productId = get_post_meta($session['post_id'], '_product_id', true);

        if (!$productId) {
            wc_add_notice(
                __('This class is not properly configured for booking.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wp_get_referer() ?: home_url());
            exit;
        }

        WC()->cart->empty_cart();

        WC()->cart->add_to_cart(
            (int) $productId,
            $persons,
            0,
            [],
            [
                'class_booking_session_id' => $sessionId,
                'class_booking_persons'    => $persons,
            ]
        );

        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
}
