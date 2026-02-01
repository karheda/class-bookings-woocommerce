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

    public function find(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
             FROM {$this->table}
             WHERE id = %d
             LIMIT 1",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function findUpcomingByClass(int $classPostId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
             FROM {$this->table}
             WHERE post_id = %d
               AND status = 'active'
               AND session_date >= CURDATE()
             ORDER BY session_date, start_time",
                $classPostId
            ),
            ARRAY_A
        );
    }

    public function insert(array $data): void
    {
        global $wpdb;


        $wpdb->insert(
            $this->table,
            [
                'post_id'      => $data['class_post_id'],
                'session_date'       => $data['session_date'],
                'start_time'         => $data['start_time'],
                'end_time'           => $data['end_time'],
                'capacity'           => $data['capacity'],
                'remaining_capacity' => $data['remaining_capacity'],
                'status'             => $data['status'],
                'created_at'         => current_time('mysql'),
                'updated_at'         => current_time('mysql'),
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );
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

    public function hasOverlappingSession(
        int $bookingId,
        string $date,
        string $startTime,
        string $endTime
    ): bool {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
        SELECT COUNT(*)
        FROM {$this->table}
        WHERE post_id = %d
          AND session_date = %s
          AND status = 'active'
          AND start_time < %s
          AND %s < end_time
        ",
            $bookingId,
            $date,
            $endTime,
            $startTime
        );

        return (int) $wpdb->get_var($sql) > 0;
    }

    /**
     * Update an existing session
     *
     * @param int $id Session ID
     * @param array $data Data to update
     * @return bool True if updated successfully, false otherwise
     */
    public function update(int $id, array $data): bool
    {
        global $wpdb;

        // Add updated_at timestamp
        $data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $this->table,
            $data,
            ['id' => $id],
            null, // Let WordPress determine format from data types
            ['%d'] // ID is integer
        );

        return $result !== false;
    }

    /**
     * Delete a session by ID
     *
     * @param int $id Session ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Update session status
     *
     * @param int $id Session ID
     * @param string $status New status (active/inactive)
     * @return bool True if updated successfully, false otherwise
     */
    public function updateStatus(int $id, string $status): bool
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->table,
            [
                'status' => $status,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get dates with available sessions for a class (for calendar)
     *
     * @param int $classPostId Class post ID
     * @return array Array of dates with session count
     */
    public function getAvailableDates(int $classPostId): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    session_date,
                    COUNT(*) as session_count,
                    SUM(remaining_capacity) as total_capacity
                 FROM {$this->table}
                 WHERE post_id = %d
                   AND status = 'active'
                   AND session_date >= CURDATE()
                   AND remaining_capacity > 0
                 GROUP BY session_date
                 ORDER BY session_date",
                $classPostId
            ),
            ARRAY_A
        );
    }

    /**
     * Get sessions for a specific date
     *
     * @param int $classPostId Class post ID
     * @param string $date Date in Y-m-d format
     * @return array Array of sessions
     */
    public function getSessionsByDate(int $classPostId, string $date): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$this->table}
                 WHERE post_id = %d
                   AND session_date = %s
                   AND status = 'active'
                 ORDER BY start_time",
                $classPostId,
                $date
            ),
            ARRAY_A
        );
    }

}
