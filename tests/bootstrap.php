<?php
/**
 * PHPUnit bootstrap file for Class Booking plugin tests.
 *
 * For integration tests, we load WordPress directly.
 * The tests run inside the Docker container with access to the database.
 */

// Load WordPress - we're running inside the Docker container
$wpLoadPath = '/var/www/html/wp-load.php';

if (file_exists($wpLoadPath)) {
    echo "Loading WordPress from {$wpLoadPath}\n";
    require_once $wpLoadPath;
} else {
    echo "WordPress not found at {$wpLoadPath}\n";
    echo "Make sure to run tests inside the Docker container:\n";
    echo "docker exec zarapita_wp bash -c \"cd /var/www/html/wp-content/plugins/class-booking && vendor/bin/phpunit\"\n";
    exit(1);
}

// Composer autoloader (for test classes)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load test base classes
require_once __DIR__ . '/TestCase.php';

echo "Bootstrap complete. Running tests...\n\n";

