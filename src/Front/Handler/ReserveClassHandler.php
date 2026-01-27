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

        if (
            !isset(
                $_POST['class_booking_product_id'],
                $_POST['class_booking_quantity']
            )
        ) {
            return;
        }

        if (!WC()->cart) {
            return;
        }

        $productId = (int) $_POST['class_booking_product_id'];
        $qtyRequested = (int) $_POST['class_booking_quantity'];

        if ($qtyRequested < 1) {
            wc_add_notice(
                __('Invalid number of persons selected.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }


        $repo = new ClassSessionRepository();
        $session = $repo->findByProductId($productId);

        if (!$session) {
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        $remaining = (int) $session['remaining_capacity'];

        $alreadyInCart = 0;
        foreach (WC()->cart->get_cart() as $item) {
            if ((int) $item['product_id'] === $productId) {
                $alreadyInCart = (int) $item['quantity'];
            }
        }

        if ($qtyRequested > ($remaining + $alreadyInCart)) {
            wc_add_notice(
                __('Not enough available spots for this class.', 'class-booking'),
                'error'
            );
            wp_safe_redirect(wc_get_cart_url());
            exit;
        }

        $cart = WC()->cart;
        $cartItemKey = $cart->find_product_in_cart(
            $cart->generate_cart_id($productId)
        );

        if ($cartItemKey) {
            $cart->set_quantity($cartItemKey, $qtyRequested);
        } else {
            $cart->add_to_cart($productId, $qtyRequested);
        }

        // PRG pattern
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
}
