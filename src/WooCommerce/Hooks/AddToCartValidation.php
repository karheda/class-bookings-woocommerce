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

    public static function validate(bool $passed, int $productId): bool
    {
        $repo = new ClassSessionRepository();
        $session = $repo->findByProductId($productId);

        if (!$session) {
            return $passed;
        }

        if ((int) $session['remaining_capacity'] <= 0) {
            wc_add_notice(
                __('This class is fully booked.', 'class-booking'),
                'error'
            );
            return false;
        }

        return true;
    }
}
