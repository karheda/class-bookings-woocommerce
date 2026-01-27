<?php

namespace ClassBooking\WooCommerce\Hooks;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class AddToCartValidation
{
    public static function register(): void
    {
        add_filter(
            'woocommerce_add_to_cart_validation',
            [self::class, 'validate'],
            10,
            2
        );
    }

    public static function validate(
        bool $passed,
        int $productId,
        int $quantity = 1
    ): bool
    {
        $repo = new ClassSessionRepository();
        $session = $repo->findByProductId($productId);

        if (!$session) {
            return $passed;
        }

        $remaining = (int) $session['remaining_capacity'];
        $alreadyInCart = self::getQuantityInCart($productId);

        if ($quantity + $alreadyInCart > $remaining) {
            wc_add_notice(
                sprintf(
                    __('Only %d spots are available for this class.', 'class-booking'),
                    max(0, $remaining - $alreadyInCart)
                ),
                'error'
            );
            return false;
        }

        return true;
    }

    private static function getQuantityInCart(int $productId): int
    {
        if (!WC()->session) {
            return 0;
        }

        $cart = WC()->session->get('cart');

        if (!is_array($cart)) {
            return 0;
        }

        $qty = 0;

        foreach ($cart as $item) {
            if ((int) $item['product_id'] === $productId) {
                $qty += (int) $item['quantity'];
            }
        }

        return $qty;
    }
}
