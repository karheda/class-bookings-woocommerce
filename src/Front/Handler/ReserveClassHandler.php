<?php

namespace ClassBooking\Front\Handler;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class ReserveClassHandler
{

    private const PRODUCT_ID = 123; // TODO: real ID

    public static function handle(): void
    {
        if (
            !isset($_POST['class_booking_action']) ||
            $_POST['class_booking_action'] !== 'reserve'
        ) {
            return;
        }

        if (
            !isset(
                $_POST['class_booking_session_id'],
                $_POST['class_booking_quantity']
            )
        ) {
            return;
        }

        if (!WC()->cart) {
            return;
        }

        $sessionId = (int) $_POST['class_booking_session_id'];
        $persons   = (int) $_POST['class_booking_quantity'];

        if ($persons < 1) {
            wc_add_notice(
                __('Invalid number of persons.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $repo = new ClassSessionRepository();
        $session = $repo->find($sessionId);

        if (!$session || $session['status'] !== 'active') {
            wc_add_notice(
                __('This session is no longer available.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        if ($persons > (int) $session['remaining_capacity']) {
            wc_add_notice(
                __('Not enough available spots for this session.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        WC()->cart->empty_cart();

        WC()->cart->add_to_cart(
            self::PRODUCT_ID,
            1, // sold individually
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
