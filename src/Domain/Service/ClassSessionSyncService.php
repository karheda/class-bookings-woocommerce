<?php

namespace ClassBooking\Domain\Service;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Post;

defined('ABSPATH') || exit;

final class ClassSessionSyncService
{
    public function __construct(
        private ClassSessionRepository $repository
    ) {}

    public function syncFromPost(WP_Post $post): void
    {
        if ($post->post_type !== 'class_session') {
            return;
        }

        $isNew = !$this->repository->existsForPost($post->ID);

        $data = [
            'post_id'    => $post->ID,
            'capacity'   => (int) get_post_meta($post->ID, '_capacity', true),
            'session_date' => get_post_meta($post->ID, '_session_date', true),
            'start_time' => get_post_meta($post->ID, '_start_time', true),
            'end_time'   => get_post_meta($post->ID, '_end_time', true),
            'status'     => 'active',
            'updated_at' => current_time('mysql'),
        ];

        if ($isNew) {
            $data['remaining_capacity'] = $data['capacity'];
            $data['created_at'] = current_time('mysql');
        }

        $productService = new WooCommerceProductSyncService();
        $productId = $productService->syncFromClassSession($post);

        $data['product_id'] = $productId;

        $this->repository->upsert($data);
    }
}
