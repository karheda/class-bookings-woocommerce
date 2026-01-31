<?php

namespace ClassBooking\Infrastructure\Database;

defined('ABSPATH') || exit;

/**
 * Database migration handler
 * Handles database schema updates without requiring plugin reactivation
 */
final class Migration
{
    private const VERSION_OPTION = 'class_booking_db_version';
    private const CURRENT_VERSION = '1.1.0'; // Incrementar con cada migración

    /**
     * Run pending migrations
     */
    public static function run(): void
    {
        $currentVersion = get_option(self::VERSION_OPTION, '0.0.0');

        if (version_compare($currentVersion, self::CURRENT_VERSION, '<')) {
            self::migrate($currentVersion);
            update_option(self::VERSION_OPTION, self::CURRENT_VERSION);
        }
    }

    /**
     * Execute migrations based on current version
     */
    private static function migrate(string $fromVersion): void
    {
        global $wpdb;

        // Migration 1.1.0: Add product_id field
        if (version_compare($fromVersion, '1.1.0', '<')) {
            self::migration_1_1_0($wpdb);
        }

        // Future migrations go here
        // if (version_compare($fromVersion, '1.2.0', '<')) {
        //     self::migration_1_2_0($wpdb);
        // }
    }

    /**
     * Migration 1.1.0: Add product_id column to class_sessions table
     */
    private static function migration_1_1_0(\wpdb $wpdb): void
    {
        $table = $wpdb->prefix . 'class_sessions';

        // Check if column already exists
        $column = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s 
                 AND TABLE_NAME = %s 
                 AND COLUMN_NAME = 'product_id'",
                DB_NAME,
                $table
            )
        );

        if (empty($column)) {
            $wpdb->query(
                "ALTER TABLE {$table} 
                 ADD COLUMN product_id BIGINT UNSIGNED NULL AFTER post_id,
                 ADD KEY product_id (product_id)"
            );

            error_log('Class Booking: Migration 1.1.0 completed - Added product_id column');
        }
    }

    /**
     * Force re-run all migrations (for development/debugging)
     */
    public static function reset(): void
    {
        delete_option(self::VERSION_OPTION);
        self::run();
    }
}

