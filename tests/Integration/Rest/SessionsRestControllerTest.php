<?php

namespace ClassBooking\Tests\Integration\Rest;

use ClassBooking\Admin\Rest\SessionsRestController;
use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

/**
 * Integration tests for SessionsRestController.
 */
class SessionsRestControllerTest extends TestCase
{
    private SessionsRestController $controller;
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

        // Set current user to admin for permission checks
        wp_set_current_user(1);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SessionsRestController();
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
    // get_item() tests
    // =========================================================================

    public function testGetItemReturnsSessionWhenExists(): void
    {
        $id = $this->createTestSession(['capacity' => 15]);

        $request = new WP_REST_Request('GET', '/class-booking/v1/sessions/' . $id);
        $request->set_param('id', $id);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        $data = $response->get_data();
        $this->assertEquals($id, $data['id']);
        $this->assertEquals(15, $data['capacity']);
    }

    public function testGetItemReturns404WhenNotExists(): void
    {
        $request = new WP_REST_Request('GET', '/class-booking/v1/sessions/999999999');
        $request->set_param('id', 999999999);

        $response = $this->controller->get_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('session_not_found', $response->get_error_code());
    }

    // =========================================================================
    // create_item() tests
    // =========================================================================

    public function testCreateItemCreatesNewSession(): void
    {
        $postId = 88888;
        $date = date('Y-m-d', strtotime('+20 days'));

        $request = new WP_REST_Request('POST', '/class-booking/v1/sessions');
        $request->set_param('post_id', $postId);
        $request->set_param('session_date', $date);
        $request->set_param('start_time', '14:00:00');
        $request->set_param('end_time', '15:00:00');
        $request->set_param('capacity', 20);

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(201, $response->get_status());

        // Find and track the created session for cleanup
        $session = self::$wpdb->get_row(
            self::$wpdb->prepare(
                "SELECT * FROM " . self::$table . " WHERE post_id = %d AND session_date = %s",
                $postId,
                $date
            ),
            ARRAY_A
        );

        $this->assertNotNull($session);
        $this->createdSessionIds[] = (int) $session['id'];
        $this->assertEquals(20, $session['capacity']);
    }

    public function testCreateItemRejectsOverlappingSession(): void
    {
        $postId = 77777;
        $date = date('Y-m-d', strtotime('+21 days'));

        // Create existing session
        $this->createTestSession([
            'post_id' => $postId,
            'session_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Try to create overlapping session
        $request = new WP_REST_Request('POST', '/class-booking/v1/sessions');
        $request->set_param('post_id', $postId);
        $request->set_param('session_date', $date);
        $request->set_param('start_time', '11:00:00');
        $request->set_param('end_time', '13:00:00');
        $request->set_param('capacity', 10);

        $response = $this->controller->create_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('session_overlap', $response->get_error_code());
    }

    // =========================================================================
    // update_item() tests
    // =========================================================================

    public function testUpdateItemModifiesSession(): void
    {
        $id = $this->createTestSession(['capacity' => 10]);

        $request = new WP_REST_Request('PUT', '/class-booking/v1/sessions/' . $id);
        $request->set_param('id', $id);
        $request->set_param('capacity', 25);

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        $updated = $this->repository->find($id);
        $this->assertEquals(25, $updated['capacity']);
    }

    public function testUpdateItemReturns404WhenNotExists(): void
    {
        $request = new WP_REST_Request('PUT', '/class-booking/v1/sessions/999999999');
        $request->set_param('id', 999999999);
        $request->set_param('capacity', 50);

        $response = $this->controller->update_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('session_not_found', $response->get_error_code());
    }

    // =========================================================================
    // delete_item() tests
    // =========================================================================

    public function testDeleteItemRemovesSession(): void
    {
        $id = $this->createTestSession();

        $request = new WP_REST_Request('DELETE', '/class-booking/v1/sessions/' . $id);
        $request->set_param('id', $id);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        $this->assertNull($this->repository->find($id));

        // Remove from cleanup list
        $this->createdSessionIds = array_filter(
            $this->createdSessionIds,
            fn($sessionId) => $sessionId !== $id
        );
    }

    public function testDeleteItemReturns404WhenNotExists(): void
    {
        $request = new WP_REST_Request('DELETE', '/class-booking/v1/sessions/999999999');
        $request->set_param('id', 999999999);

        $response = $this->controller->delete_item($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('session_not_found', $response->get_error_code());
    }

    // =========================================================================
    // update_status() tests
    // =========================================================================

    public function testUpdateStatusChangesSessionStatus(): void
    {
        $id = $this->createTestSession(['status' => 'active']);

        $request = new WP_REST_Request('PATCH', '/class-booking/v1/sessions/' . $id . '/status');
        $request->set_param('id', $id);
        $request->set_param('status', 'inactive');

        $response = $this->controller->update_status($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());

        $updated = $this->repository->find($id);
        $this->assertEquals('inactive', $updated['status']);
    }

    public function testUpdateStatusReturns404WhenNotExists(): void
    {
        $request = new WP_REST_Request('PATCH', '/class-booking/v1/sessions/999999999/status');
        $request->set_param('id', 999999999);
        $request->set_param('status', 'inactive');

        $response = $this->controller->update_status($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertEquals('session_not_found', $response->get_error_code());
    }

    // =========================================================================
    // Permission tests
    // =========================================================================

    public function testPermissionsRequireEditPostsCapability(): void
    {
        $request = new WP_REST_Request('GET', '/class-booking/v1/sessions');

        // Admin user should have permission
        $this->assertTrue($this->controller->get_items_permissions_check($request));
        $this->assertTrue($this->controller->create_item_permissions_check($request));
        $this->assertTrue($this->controller->update_item_permissions_check($request));
    }
}

