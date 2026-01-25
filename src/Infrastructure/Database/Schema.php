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

    price DECIMAL(10,2) NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    weekday VARCHAR(10) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    status VARCHAR(20) NOT NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id),
    UNIQUE KEY product_id (product_id)
) {$charsetCollate};
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
