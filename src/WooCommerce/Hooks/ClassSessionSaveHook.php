<?php

namespace ClassBooking\WooCommerce\Hooks;

use ClassBooking\Domain\Service\ClassSessionSyncService;
use ClassBooking\Domain\Service\WooCommerceProductSyncService;
use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class ClassSessionSaveHook
{
    public static function register(): void
    {
        add_action('save_post_booking', [self::class, 'handle'], 20, 2);
    }

    public static function handle(int $postId, WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Sync class session data to custom table
        $sessionService = new ClassSessionSyncService(
            new ClassSessionRepository()
        );
        $sessionService->syncFromPost($post);

        // Sync/create WooCommerce product for this booking
        $productService = new WooCommerceProductSyncService();
        $productService->syncFromClassSession($post);
    }
}
