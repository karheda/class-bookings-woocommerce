<?php

namespace ClassBooking\Tests\Integration\WooCommerce;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use ClassBooking\WooCommerce\Hooks\DisableCartQuantity;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for DisableCartQuantity hook.
 */
class DisableCartQuantityTest extends TestCase
{
    private ClassSessionRepository $repository;
    private static ?\wpdb $wpdb = null;
    private static string $table;
    private array $createdSessionIds = [];

    public static function setUpBeforeClass(): void
    {
        if (!function_exists('add_action')) {
            require_once '/var/www/html/wp-load.php';
        }

        global $wpdb;
        self::$wpdb = $wpdb;
        self::$table = $wpdb->prefix . 'class_sessions';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ClassSessionRepository();
        $this->createdSessionIds = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdSessionIds as $id) {
            self::$wpdb->delete(self::$table, ['id' => $id], ['%d']);
        }
        parent::tearDown();
    }

    private function createTestSession(array $overrides = []): int
    {
        $data = array_merge([
            'post_id' => 99999,
            'session_date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'capacity' => 10,
            'remaining_capacity' => 10,
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ], $overrides);

        self::$wpdb->insert(self::$table, $data);
        $id = (int) self::$wpdb->insert_id;
        $this->createdSessionIds[] = $id;

        return $id;
    }

    // =========================================================================
    // addSessionDataToCart() tests
    // =========================================================================

    public function testAddSessionDataToCartAddsDateAndTime(): void
    {
        $sessionId = $this->createTestSession([
            'session_date' => '2026-03-15',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
        ]);

        $cartItem = [
            'class_booking_session_id' => $sessionId,
        ];

        $result = DisableCartQuantity::addSessionDataToCart([], $cartItem);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that date and time are added
        $hasDate = false;
        $hasTime = false;

        foreach ($result as $item) {
            if (isset($item['name']) && $item['name'] === __('Date', 'class-booking')) {
                $hasDate = true;
            }
            if (isset($item['name']) && $item['name'] === __('Time', 'class-booking')) {
                $hasTime = true;
            }
        }

        $this->assertTrue($hasDate, 'Date should be added to cart item data');
        $this->assertTrue($hasTime, 'Time should be added to cart item data');
    }

    public function testAddSessionDataToCartReturnsOriginalDataWhenNoSessionId(): void
    {
        $originalData = [
            ['name' => 'Test', 'value' => 'Value'],
        ];

        $cartItem = [
            'product_id' => 123,
            // No class_booking_session_id
        ];

        $result = DisableCartQuantity::addSessionDataToCart($originalData, $cartItem);

        $this->assertEquals($originalData, $result);
    }

    public function testAddSessionDataToCartHandlesInvalidSessionId(): void
    {
        $cartItem = [
            'class_booking_session_id' => 999999999, // Non-existent
        ];

        $result = DisableCartQuantity::addSessionDataToCart([], $cartItem);

        // Should return empty array since session doesn't exist
        $this->assertIsArray($result);
    }

    public function testAddSessionDataToCartPreservesExistingData(): void
    {
        $sessionId = $this->createTestSession();

        $existingData = [
            ['name' => 'Existing', 'value' => 'Data'],
        ];

        $cartItem = [
            'class_booking_session_id' => $sessionId,
        ];

        $result = DisableCartQuantity::addSessionDataToCart($existingData, $cartItem);

        // Should contain both existing and new data
        $this->assertGreaterThan(1, count($result));

        $hasExisting = false;
        foreach ($result as $item) {
            if (isset($item['name']) && $item['name'] === 'Existing') {
                $hasExisting = true;
                break;
            }
        }

        $this->assertTrue($hasExisting, 'Existing data should be preserved');
    }
}

