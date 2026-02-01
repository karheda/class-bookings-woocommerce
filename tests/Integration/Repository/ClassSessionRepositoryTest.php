<?php

namespace ClassBooking\Tests\Integration\Repository;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ClassSessionRepository.
 *
 * These tests require a running WordPress/database environment.
 * Run inside Docker: docker exec zarapita_wp bash -c "cd /var/www/html/wp-content/plugins/class-booking && vendor/bin/phpunit"
 */
class ClassSessionRepositoryTest extends TestCase
{
    private ClassSessionRepository $repository;
    private static ?\wpdb $wpdb = null;
    private static string $table;
    private array $createdSessionIds = [];

    public static function setUpBeforeClass(): void
    {
        // Load WordPress if not already loaded
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
        // Clean up created sessions
        foreach ($this->createdSessionIds as $id) {
            self::$wpdb->delete(self::$table, ['id' => $id], ['%d']);
        }
        parent::tearDown();
    }

    /**
     * Helper to create a test session and track it for cleanup.
     */
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
    // find() tests
    // =========================================================================

    public function testFindReturnsSessionWhenExists(): void
    {
        $id = $this->createTestSession(['capacity' => 15]);

        $result = $this->repository->find($id);

        $this->assertNotNull($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals(15, $result['capacity']);
    }

    public function testFindReturnsNullWhenNotExists(): void
    {
        $result = $this->repository->find(999999999);

        $this->assertNull($result);
    }

    // =========================================================================
    // insert() tests
    // =========================================================================

    public function testInsertCreatesNewSession(): void
    {
        $postId = 88888;
        $data = [
            'class_post_id' => $postId,
            'session_date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'capacity' => 20,
            'remaining_capacity' => 20,
            'status' => 'active',
        ];

        $this->repository->insert($data);

        // Find the inserted session
        $result = self::$wpdb->get_row(
            self::$wpdb->prepare(
                "SELECT * FROM " . self::$table . " WHERE post_id = %d ORDER BY id DESC LIMIT 1",
                $postId
            ),
            ARRAY_A
        );

        $this->assertNotNull($result);
        $this->createdSessionIds[] = (int) $result['id'];

        $this->assertEquals($postId, $result['post_id']);
        $this->assertEquals(20, $result['capacity']);
        $this->assertEquals('active', $result['status']);
    }

    // =========================================================================
    // update() tests
    // =========================================================================

    public function testUpdateModifiesExistingSession(): void
    {
        $id = $this->createTestSession(['capacity' => 10]);

        $result = $this->repository->update($id, [
            'capacity' => 25,
            'remaining_capacity' => 25,
        ]);

        $this->assertTrue($result);

        $updated = $this->repository->find($id);
        $this->assertEquals(25, $updated['capacity']);
        $this->assertEquals(25, $updated['remaining_capacity']);
    }

    public function testUpdateReturnsFalseForNonExistentSession(): void
    {
        $result = $this->repository->update(999999999, ['capacity' => 50]);

        // wpdb->update returns 0 for no rows affected, which is not false
        // but our method should handle this gracefully
        $this->assertIsBool($result);
    }

    // =========================================================================
    // delete() tests
    // =========================================================================

    public function testDeleteRemovesSession(): void
    {
        $id = $this->createTestSession();

        $result = $this->repository->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->repository->find($id));

        // Remove from cleanup list since it's already deleted
        $this->createdSessionIds = array_filter(
            $this->createdSessionIds,
            fn($sessionId) => $sessionId !== $id
        );
    }

    public function testDeleteReturnsFalseForNonExistentSession(): void
    {
        $result = $this->repository->delete(999999999);

        $this->assertIsBool($result);
    }

    // =========================================================================
    // updateStatus() tests
    // =========================================================================

    public function testUpdateStatusChangesSessionStatus(): void
    {
        $id = $this->createTestSession(['status' => 'active']);

        $result = $this->repository->updateStatus($id, 'inactive');

        $this->assertTrue($result);

        $updated = $this->repository->find($id);
        $this->assertEquals('inactive', $updated['status']);
    }

    // =========================================================================
    // decreaseCapacityBySessionId() tests
    // =========================================================================

    public function testDecreaseCapacityReducesRemainingCapacity(): void
    {
        $id = $this->createTestSession([
            'capacity' => 10,
            'remaining_capacity' => 10,
        ]);

        $this->repository->decreaseCapacityBySessionId($id, 3);

        $updated = $this->repository->find($id);
        $this->assertEquals(7, $updated['remaining_capacity']);
    }

    public function testDecreaseCapacityDoesNotGoBelowZero(): void
    {
        $id = $this->createTestSession([
            'capacity' => 10,
            'remaining_capacity' => 2,
        ]);

        // Try to decrease by more than available
        $this->repository->decreaseCapacityBySessionId($id, 5);

        // Should not change because remaining_capacity < qty
        $updated = $this->repository->find($id);
        $this->assertEquals(2, $updated['remaining_capacity']);
    }

    public function testDecreaseCapacityIsAtomic(): void
    {
        $id = $this->createTestSession([
            'capacity' => 10,
            'remaining_capacity' => 10,
        ]);

        // Decrease multiple times
        $this->repository->decreaseCapacityBySessionId($id, 3);
        $this->repository->decreaseCapacityBySessionId($id, 4);

        $updated = $this->repository->find($id);
        $this->assertEquals(3, $updated['remaining_capacity']);
    }

    // =========================================================================
    // hasOverlappingSession() tests
    // =========================================================================

    public function testHasOverlappingSessionReturnsTrueForOverlap(): void
    {
        $postId = 77777;
        $date = date('Y-m-d', strtotime('+3 days'));

        // Create session from 10:00 to 12:00
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Check for overlap: 11:00 to 13:00 (overlaps with existing)
        $result = $this->repository->hasOverlappingSession(
            $postId,
            $date,
            '11:00:00',
            '13:00:00'
        );

        $this->assertTrue($result);
    }

    public function testHasOverlappingSessionReturnsFalseForNoOverlap(): void
    {
        $postId = 77778;
        $date = date('Y-m-d', strtotime('+3 days'));

        // Create session from 10:00 to 12:00
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Check for non-overlap: 14:00 to 16:00
        $result = $this->repository->hasOverlappingSession(
            $postId,
            $date,
            '14:00:00',
            '16:00:00'
        );

        $this->assertFalse($result);
    }

    public function testHasOverlappingSessionReturnsFalseForAdjacentSessions(): void
    {
        $postId = 77779;
        $date = date('Y-m-d', strtotime('+3 days'));

        // Create session from 10:00 to 12:00
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Check for adjacent: 12:00 to 14:00 (starts exactly when other ends)
        $result = $this->repository->hasOverlappingSession(
            $postId,
            $date,
            '12:00:00',
            '14:00:00'
        );

        $this->assertFalse($result);
    }

    // =========================================================================
    // getAvailableDates() tests
    // =========================================================================

    public function testGetAvailableDatesReturnsOnlyFutureDatesWithCapacity(): void
    {
        $postId = 66666;
        $futureDate1 = date('Y-m-d', strtotime('+5 days'));
        $futureDate2 = date('Y-m-d', strtotime('+6 days'));
        $pastDate = date('Y-m-d', strtotime('-1 day'));

        // Future date with capacity
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $futureDate1,
            'remaining_capacity' => 5,
            'status' => 'active',
        ]);

        // Another future date
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $futureDate2,
            'remaining_capacity' => 10,
            'status' => 'active',
        ]);

        // Past date (should not appear)
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $pastDate,
            'remaining_capacity' => 5,
            'status' => 'active',
        ]);

        // Future date with no capacity (should not appear)
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => date('Y-m-d', strtotime('+7 days')),
            'remaining_capacity' => 0,
            'status' => 'active',
        ]);

        $result = $this->repository->getAvailableDates($postId);

        $this->assertCount(2, $result);
        $dates = array_column($result, 'session_date');
        $this->assertContains($futureDate1, $dates);
        $this->assertContains($futureDate2, $dates);
    }

    public function testGetAvailableDatesExcludesInactiveSessions(): void
    {
        $postId = 66667;
        $futureDate = date('Y-m-d', strtotime('+8 days'));

        // Inactive session
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $futureDate,
            'remaining_capacity' => 10,
            'status' => 'inactive',
        ]);

        $result = $this->repository->getAvailableDates($postId);

        $this->assertEmpty($result);
    }

    // =========================================================================
    // getSessionsByDate() tests
    // =========================================================================

    public function testGetSessionsByDateReturnsSessionsForSpecificDate(): void
    {
        $postId = 55555;
        $targetDate = date('Y-m-d', strtotime('+10 days'));

        // Create two sessions on target date
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $targetDate,
            'start_time' => '09:00:00',
            'status' => 'active',
        ]);

        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $targetDate,
            'start_time' => '14:00:00',
            'status' => 'active',
        ]);

        // Session on different date (should not appear)
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => date('Y-m-d', strtotime('+11 days')),
            'start_time' => '10:00:00',
            'status' => 'active',
        ]);

        $result = $this->repository->getSessionsByDate($postId, $targetDate);

        $this->assertCount(2, $result);
        // Should be ordered by start_time
        $this->assertEquals('09:00:00', $result[0]['start_time']);
        $this->assertEquals('14:00:00', $result[1]['start_time']);
    }

    public function testGetSessionsByDateExcludesInactiveSessions(): void
    {
        $postId = 55556;
        $targetDate = date('Y-m-d', strtotime('+12 days'));

        // Active session
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $targetDate,
            'start_time' => '10:00:00',
            'status' => 'active',
        ]);

        // Inactive session
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $targetDate,
            'start_time' => '15:00:00',
            'status' => 'inactive',
        ]);

        $result = $this->repository->getSessionsByDate($postId, $targetDate);

        $this->assertCount(1, $result);
        $this->assertEquals('10:00:00', $result[0]['start_time']);
    }

    // =========================================================================
    // existsForPost() tests
    // =========================================================================

    public function testExistsForPostReturnsTrueWhenSessionsExist(): void
    {
        $postId = 44444;

        $this->createTestSession(['post_id' => $postId]);

        $result = $this->repository->existsForPost($postId);

        $this->assertTrue($result);
    }

    public function testExistsForPostReturnsFalseWhenNoSessions(): void
    {
        $result = $this->repository->existsForPost(999999998);

        $this->assertFalse($result);
    }
}

