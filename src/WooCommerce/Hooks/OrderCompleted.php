<?php

namespace ClassBooking\WooCommerce\Hooks;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class OrderCompleted
{
    public static function register(): void
    {
        add_action(
            'woocommerce_order_status_completed',
            [self::class, 'handle']
        );
    }

    public static function handle(int $orderId): void
    {
        $order = wc_get_order($orderId);
        $repo = new ClassSessionRepository();

        foreach ($order->get_items() as $item) {
            $productId = $item->get_product_id();
            $qty = $item->get_quantity();

            $repo->decreaseCapacity($productId, $qty);
        }
    }
}
