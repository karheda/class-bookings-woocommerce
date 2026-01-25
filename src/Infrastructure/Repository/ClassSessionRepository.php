<?php

namespace ClassBooking\Infrastructure\Repository;

defined('ABSPATH') || exit;

final class ClassSessionRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'class_sessions';
    }

    public function upsert(array $data): void
    {
        global $wpdb;

        $existingId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE post_id = %d",
                $data['post_id']
            )
        );

        if ($existingId) {
            $wpdb->update(
                $this->table,
                $data,
                ['post_id' => $data['post_id']]
            );
        } else {
            $wpdb->insert($this->table, $data);
        }
    }

    public function existsForPost(int $postId): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1
             FROM {$this->table}
             WHERE post_id = %d
             LIMIT 1",
                $postId
            )
        );

        return (bool) $result;
    }


    public function findActive(): array
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT *
         FROM {$this->table}
         WHERE status = 'active'
         ORDER BY weekday, start_time",
            ARRAY_A
        );
    }

    public function findByProductId(int $productId): ?array
    {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
             FROM {$this->table}
             WHERE product_id = %d
             LIMIT 1",
                $productId
            ),
            ARRAY_A
        );

        return $result ?: null;
    }


    public function decreaseCapacity(int $productId, int $qty): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
             SET remaining_capacity = remaining_capacity - %d
             WHERE product_id = %d
             AND remaining_capacity >= %d",
                $qty,
                $productId,
                $qty
            )
        );
    }

}
