<?php

namespace ClassBooking\WooCommerce\Hooks;

defined('ABSPATH') || exit;

final class DisableCartQuantity
{
    public static function register(): void
    {
        add_filter(
            'woocommerce_get_item_data',
            [self::class, 'disableQuantityInput'],
            10,
            3
        );
    }

    public static function disableQuantityInput(
        array $itemData,
        array $cartItem
    ): array {
        if (isset($cartItem['class_booking_persons'])) {
            $itemData[] = [
                'name'  => __('Persons', 'class-booking'),
                'value' => (int) $cartItem['class_booking_persons'],
            ];
        }
        return $itemData;
    }
}
