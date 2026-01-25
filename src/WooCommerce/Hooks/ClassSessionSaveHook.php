<?php

namespace ClassBooking\WooCommerce\Hooks;

use ClassBooking\Domain\Service\ClassSessionSyncService;
use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class ClassSessionSaveHook
{
    public static function register(): void
    {
        add_action('save_post_class_session', [self::class, 'handle'], 20, 2);
    }

    public static function handle(int $postId, WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $service = new ClassSessionSyncService(
            new ClassSessionRepository()
        );

        $service->syncFromPost($post);
    }
}
