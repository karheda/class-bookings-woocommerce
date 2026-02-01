<?php

namespace ClassBooking\WooCommerce\Hooks;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class DisableCartQuantity
{
    public static function register(): void
    {
        // Display session data in cart and checkout
        add_filter(
            'woocommerce_get_item_data',
            [self::class, 'addSessionDataToCart'],
            10,
            2
        );

        // Save session data to order item meta
        add_action(
            'woocommerce_checkout_create_order_line_item',
            [self::class, 'saveSessionDataToOrderItem'],
            10,
            4
        );
    }

    public static function addSessionDataToCart(
        array $itemData,
        array $cartItem
    ): array {
        if (!isset($cartItem['class_booking_session_id'])) {
            return $itemData;
        }

        $sessionId = (int) $cartItem['class_booking_session_id'];
        $repository = new ClassSessionRepository();
        $session = $repository->find($sessionId);

        if ($session) {
            // Format date
            $date = date_i18n(
                get_option('date_format'),
                strtotime($session['session_date'])
            );

            // Format time
            $startTime = date_i18n(
                get_option('time_format'),
                strtotime($session['start_time'])
            );
            $endTime = date_i18n(
                get_option('time_format'),
                strtotime($session['end_time'])
            );

            $itemData[] = [
                'name'  => __('Date', 'class-booking'),
                'value' => $date,
            ];

            $itemData[] = [
                'name'  => __('Time', 'class-booking'),
                'value' => $startTime . ' - ' . $endTime,
            ];
        }

        if (isset($cartItem['class_booking_persons'])) {
            $itemData[] = [
                'name'  => __('Persons', 'class-booking'),
                'value' => (int) $cartItem['class_booking_persons'],
            ];
        }

        return $itemData;
    }

    /**
     * Save session data to order item meta (for order details and emails)
     */
    public static function saveSessionDataToOrderItem(
        \WC_Order_Item_Product $item,
        string $cartItemKey,
        array $cartItem,
        \WC_Order $order
    ): void {
        if (!isset($cartItem['class_booking_session_id'])) {
            return;
        }

        $sessionId = (int) $cartItem['class_booking_session_id'];
        $repository = new ClassSessionRepository();
        $session = $repository->find($sessionId);

        if ($session) {
            // Save session ID for later use (e.g., capacity reduction)
            $item->add_meta_data('_class_booking_session_id', $sessionId, true);

            // Format and save date
            $date = date_i18n(
                get_option('date_format'),
                strtotime($session['session_date'])
            );
            $item->add_meta_data(__('Date', 'class-booking'), $date, true);

            // Format and save time
            $startTime = date_i18n(
                get_option('time_format'),
                strtotime($session['start_time'])
            );
            $endTime = date_i18n(
                get_option('time_format'),
                strtotime($session['end_time'])
            );
            $item->add_meta_data(__('Time', 'class-booking'), $startTime . ' - ' . $endTime, true);
        }

        if (isset($cartItem['class_booking_persons'])) {
            $item->add_meta_data(__('Persons', 'class-booking'), (int) $cartItem['class_booking_persons'], true);
        }
    }
}
