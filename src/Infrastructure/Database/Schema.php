<?php

namespace ClassBooking\Infrastructure\Database;

defined('ABSPATH') || exit;

final class Schema
{
    public static function createTables(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'class_sessions';

        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE {$table} (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,

    capacity INT NOT NULL,
    remaining_capacity INT NOT NULL,

    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    status VARCHAR(20) NOT NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE (post_id, session_date, start_time, end_time),
    KEY product_id (product_id)
) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
