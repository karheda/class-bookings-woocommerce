<?php

namespace ClassBooking\Admin\Rest;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

/**
 * REST API Controller for Class Sessions
 * 
 * Handles CRUD operations for class sessions via REST API
 */
final class SessionsRestController extends WP_REST_Controller
{
    private ClassSessionRepository $repository;

    public function __construct()
    {
        $this->namespace = 'class-booking/v1';
        $this->rest_base = 'sessions';
        $this->repository = new ClassSessionRepository();
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void
    {
        // Collection route: GET /sessions, POST /sessions
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => [$this, 'get_items_permissions_check'],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_item'],
                    'permission_callback' => [$this, 'create_item_permissions_check'],
                    'args'                => $this->get_endpoint_args_for_create(),
                ],
            ]
        );

        // Single item routes: GET /sessions/{id}, PUT /sessions/{id}, DELETE /sessions/{id}
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'args' => [
                    'id' => [
                        'description' => __('Unique identifier for the session.', 'class-booking'),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update_item'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => $this->get_endpoint_args_for_update(),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'delete_item'],
                    'permission_callback' => [$this, 'delete_item_permissions_check'],
                ],
            ]
        );

        // Status toggle route: PATCH /sessions/{id}/status
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/status',
            [
                'args' => [
                    'id' => [
                        'description' => __('Unique identifier for the session.', 'class-booking'),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update_status'],
                    'permission_callback' => [$this, 'update_item_permissions_check'],
                    'args'                => [
                        'status' => [
                            'description' => __('Session status.', 'class-booking'),
                            'type'        => 'string',
                            'enum'        => ['active', 'inactive'],
                            'required'    => true,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get a collection of sessions
     */
    public function get_items($request): WP_REST_Response
    {
        $postId = $request->get_param('post_id');
        
        if ($postId) {
            $sessions = $this->repository->findUpcomingByClass((int) $postId);
        } else {
            // TODO: Implement get all sessions if needed
            $sessions = [];
        }

        return new WP_REST_Response($sessions, 200);
    }

    /**
     * Get a single session
     */
    public function get_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        $session = $this->repository->find($id);

        if (!$session) {
            return new WP_Error(
                'session_not_found',
                __('Session not found.', 'class-booking'),
                ['status' => 404]
            );
        }

        return new WP_REST_Response($session, 200);
    }

    /**
     * Create a new session
     */
    public function create_item($request): WP_REST_Response|WP_Error
    {
        $data = $this->prepare_item_for_database($request);

        // Validate no overlapping sessions
        if ($this->repository->hasOverlappingSession(
            $data['post_id'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time']
        )) {
            return new WP_Error(
                'session_overlap',
                __('This session overlaps with an existing one.', 'class-booking'),
                ['status' => 409]
            );
        }

        $this->repository->insert([
            'class_post_id'      => $data['post_id'],
            'session_date'       => $data['session_date'],
            'start_time'         => $data['start_time'],
            'end_time'           => $data['end_time'],
            'capacity'           => $data['capacity'],
            'remaining_capacity' => $data['capacity'],
            'status'             => $data['status'] ?? 'active',
        ]);

        return new WP_REST_Response(
            ['message' => __('Session created successfully.', 'class-booking')],
            201
        );
    }

    /**
     * Update an existing session
     */
    public function update_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        $session = $this->repository->find($id);

        if (!$session) {
            return new WP_Error(
                'session_not_found',
                __('Session not found.', 'class-booking'),
                ['status' => 404]
            );
        }

        $data = $this->prepare_item_for_database($request);

        $success = $this->repository->update($id, $data);

        if (!$success) {
            return new WP_Error(
                'update_failed',
                __('Failed to update session.', 'class-booking'),
                ['status' => 500]
            );
        }

        return new WP_REST_Response(
            ['message' => __('Session updated successfully.', 'class-booking')],
            200
        );
    }

    /**
     * Delete a session
     */
    public function delete_item($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        $session = $this->repository->find($id);

        if (!$session) {
            return new WP_Error(
                'session_not_found',
                __('Session not found.', 'class-booking'),
                ['status' => 404]
            );
        }

        $success = $this->repository->delete($id);

        if (!$success) {
            return new WP_Error(
                'delete_failed',
                __('Failed to delete session.', 'class-booking'),
                ['status' => 500]
            );
        }

        return new WP_REST_Response(
            ['message' => __('Session deleted successfully.', 'class-booking')],
            200
        );
    }

    /**
     * Update session status
     */
    public function update_status($request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        $status = sanitize_text_field($request['status']);

        $session = $this->repository->find($id);

        if (!$session) {
            return new WP_Error(
                'session_not_found',
                __('Session not found.', 'class-booking'),
                ['status' => 404]
            );
        }

        $success = $this->repository->updateStatus($id, $status);

        if (!$success) {
            return new WP_Error(
                'update_failed',
                __('Failed to update session status.', 'class-booking'),
                ['status' => 500]
            );
        }

        return new WP_REST_Response(
            ['message' => __('Session status updated successfully.', 'class-booking')],
            200
        );
    }

    /**
     * Prepare item for database
     */
    protected function prepare_item_for_database($request): array
    {
        $data = [];

        if (isset($request['post_id'])) {
            $data['post_id'] = absint($request['post_id']);
        }

        if (isset($request['session_date'])) {
            $date = sanitize_text_field($request['session_date']);
            // Validate date format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $data['session_date'] = $date;
            }
        }

        if (isset($request['start_time'])) {
            $time = sanitize_text_field($request['start_time']);
            // Validate time format (HH:MM or HH:MM:SS)
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                $data['start_time'] = $time;
            }
        }

        if (isset($request['end_time'])) {
            $time = sanitize_text_field($request['end_time']);
            // Validate time format (HH:MM or HH:MM:SS)
            if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
                $data['end_time'] = $time;
            }
        }

        if (isset($request['capacity'])) {
            $data['capacity'] = max(1, absint($request['capacity']));
        }

        if (isset($request['status'])) {
            $status = sanitize_text_field($request['status']);
            // Only allow valid statuses
            if (in_array($status, ['active', 'inactive'], true)) {
                $data['status'] = $status;
            }
        }

        return $data;
    }

    /**
     * Get endpoint args for create
     */
    protected function get_endpoint_args_for_create(): array
    {
        return [
            'post_id' => [
                'description' => __('Booking post ID.', 'class-booking'),
                'type'        => 'integer',
                'required'    => true,
            ],
            'session_date' => [
                'description' => __('Session date (YYYY-MM-DD).', 'class-booking'),
                'type'        => 'string',
                'format'      => 'date',
                'required'    => true,
            ],
            'start_time' => [
                'description' => __('Start time (HH:MM:SS).', 'class-booking'),
                'type'        => 'string',
                'required'    => true,
            ],
            'end_time' => [
                'description' => __('End time (HH:MM:SS).', 'class-booking'),
                'type'        => 'string',
                'required'    => true,
            ],
            'capacity' => [
                'description' => __('Session capacity.', 'class-booking'),
                'type'        => 'integer',
                'minimum'     => 1,
                'required'    => true,
            ],
            'status' => [
                'description' => __('Session status.', 'class-booking'),
                'type'        => 'string',
                'enum'        => ['active', 'inactive'],
                'default'     => 'active',
            ],
        ];
    }

    /**
     * Get endpoint args for update
     */
    protected function get_endpoint_args_for_update(): array
    {
        $args = $this->get_endpoint_args_for_create();

        // Make all fields optional for update
        foreach ($args as $key => $arg) {
            $args[$key]['required'] = false;
        }

        return $args;
    }

    /**
     * Check permissions for getting items
     */
    public function get_items_permissions_check($request): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Check permissions for getting a single item
     */
    public function get_item_permissions_check($request): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Check permissions for creating an item
     */
    public function create_item_permissions_check($request): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Check permissions for updating an item
     */
    public function update_item_permissions_check($request): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Check permissions for deleting an item
     */
    public function delete_item_permissions_check($request): bool
    {
        return current_user_can('delete_posts');
    }
}

