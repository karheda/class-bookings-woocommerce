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
            $sessionId = $item->get_meta('_class_booking_session_id');

            if (!$sessionId) {
                // Fallback to product_id for backwards compatibility
                $productId = $item->get_product_id();
                $repo->decreaseCapacity($productId, $item->get_quantity());
                continue;
            }

            $repo->decreaseCapacityBySessionId((int) $sessionId, $item->get_quantity());
        }
    }
}
