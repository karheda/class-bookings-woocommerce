<?php

namespace ClassBooking\Admin\Metabox;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class BookingSessionsMetabox
{
    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'addMetabox']);
    }

    public static function addMetabox(): void
    {
        add_meta_box(
            'booking_sessions',
            __('Booking Sessions', 'class-booking'),
            [self::class, 'render'],
            'booking',
            'normal',
            'high'
        );
    }

    public static function render(\WP_Post $post): void
    {
        $repository = new ClassSessionRepository();
        $sessions = $repository->findUpcomingByClass($post->ID);

        wp_nonce_field('class_booking_sessions_nonce', 'class_booking_sessions_nonce');
        ?>

        <div class="class-booking-sessions-metabox">
            <div class="sessions-header">
                <h3><?php _e('Class Sessions', 'class-booking'); ?></h3>
                <button type="button" class="button button-primary" id="cb-add-session-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Add New Session', 'class-booking'); ?>
                </button>
            </div>

            <div class="sessions-list" id="cb-sessions-list" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <?php if (empty($sessions)): ?>
                    <div class="no-sessions-message">
                        <p><?php _e('No sessions created yet. Click "Add New Session" to get started.', 'class-booking'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="widefat striped cb-sessions-table">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'class-booking'); ?></th>
                                <th><?php _e('Time', 'class-booking'); ?></th>
                                <th><?php _e('Capacity', 'class-booking'); ?></th>
                                <th><?php _e('Booked', 'class-booking'); ?></th>
                                <th><?php _e('Status', 'class-booking'); ?></th>
                                <th><?php _e('Actions', 'class-booking'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session):
                                $booked = (int)$session['capacity'] - (int)$session['remaining_capacity'];
                                $status_class = $session['status'] === 'active' ? 'status-active' : 'status-inactive';
                            ?>
                                <tr class="session-row" data-session-id="<?php echo esc_attr($session['id']); ?>">
                                    <td class="session-date">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($session['session_date']))); ?>
                                    </td>
                                    <td class="session-time">
                                        <?php
                                        echo esc_html(date_i18n(get_option('time_format'), strtotime($session['start_time'])));
                                        echo ' – ';
                                        echo esc_html(date_i18n(get_option('time_format'), strtotime($session['end_time'])));
                                        ?>
                                    </td>
                                    <td class="session-capacity">
                                        <?php echo (int) $session['capacity']; ?>
                                    </td>
                                    <td class="session-booked">
                                        <strong><?php echo $booked; ?></strong> / <?php echo (int) $session['remaining_capacity']; ?> <?php _e('available', 'class-booking'); ?>
                                    </td>
                                    <td class="session-status">
                                        <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html(ucfirst($session['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="session-actions">
                                        <button type="button"
                                                class="button button-small cb-edit-session"
                                                data-session-id="<?php echo esc_attr($session['id']); ?>"
                                                title="<?php esc_attr_e('Edit session', 'class-booking'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>

                                        <button type="button"
                                                class="button button-small cb-toggle-status"
                                                data-session-id="<?php echo esc_attr($session['id']); ?>"
                                                data-current-status="<?php echo esc_attr($session['status']); ?>"
                                                title="<?php esc_attr_e('Toggle status', 'class-booking'); ?>">
                                            <span class="dashicons dashicons-<?php echo $session['status'] === 'active' ? 'hidden' : 'visibility'; ?>"></span>
                                        </button>

                                        <button type="button"
                                                class="button button-small button-link-delete cb-delete-session"
                                                data-session-id="<?php echo esc_attr($session['id']); ?>"
                                                title="<?php esc_attr_e('Delete session', 'class-booking'); ?>"
                                                <?php echo $booked > 0 ? 'disabled' : ''; ?>>
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Loading spinner -->
            <div class="cb-loading" style="display: none;">
                <span class="spinner is-active"></span>
            </div>
        </div>

        <!-- Session Modal -->
        <div id="cb-session-modal" class="cb-modal" style="display: none;">
            <div class="cb-modal-overlay"></div>
            <div class="cb-modal-content">
                <div class="cb-modal-header">
                    <h2 id="cb-modal-title"><?php _e('Add New Session', 'class-booking'); ?></h2>
                    <button type="button" class="cb-modal-close" aria-label="<?php esc_attr_e('Close', 'class-booking'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="cb-modal-body">
                    <div id="cb-session-form">
                        <input type="hidden" id="cb-session-id" name="session_id" value="">
                        <input type="hidden" id="cb-post-id" name="post_id" value="<?php echo esc_attr($post->ID); ?>">

                        <div class="cb-form-row">
                            <div class="cb-form-field">
                                <label for="cb-session-date">
                                    <?php _e('Date', 'class-booking'); ?> <span class="required">*</span>
                                </label>
                                <input type="text"
                                       id="cb-session-date"
                                       name="session_date"
                                       class="cb-datepicker"
                                       placeholder="<?php esc_attr_e('Select date', 'class-booking'); ?>">
                            </div>
                        </div>

                        <div class="cb-form-row cb-form-row-2col">
                            <div class="cb-form-field">
                                <label for="cb-start-time">
                                    <?php _e('Start Time', 'class-booking'); ?> <span class="required">*</span>
                                </label>
                                <input type="text"
                                       id="cb-start-time"
                                       name="start_time"
                                       class="cb-timepicker"
                                       placeholder="<?php esc_attr_e('Select start time', 'class-booking'); ?>">
                            </div>

                            <div class="cb-form-field">
                                <label for="cb-end-time">
                                    <?php _e('End Time', 'class-booking'); ?> <span class="required">*</span>
                                </label>
                                <input type="text"
                                       id="cb-end-time"
                                       name="end_time"
                                       class="cb-timepicker"
                                       placeholder="<?php esc_attr_e('Select end time', 'class-booking'); ?>">
                            </div>
                        </div>

                        <div class="cb-form-row">
                            <div class="cb-form-field">
                                <label for="cb-capacity">
                                    <?php _e('Capacity', 'class-booking'); ?> <span class="required">*</span>
                                </label>
                                <input type="number"
                                       id="cb-capacity"
                                       name="capacity"
                                       min="1"
                                       placeholder="<?php esc_attr_e('Number of available spots', 'class-booking'); ?>">
                                <p class="description">
                                    <?php _e('Maximum number of people that can book this session.', 'class-booking'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="cb-form-row">
                            <div class="cb-form-field">
                                <label for="cb-status">
                                    <?php _e('Status', 'class-booking'); ?>
                                </label>
                                <select id="cb-status" name="status">
                                    <option value="active"><?php _e('Active', 'class-booking'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'class-booking'); ?></option>
                                </select>
                                <p class="description">
                                    <?php _e('Inactive sessions won\'t be visible to customers.', 'class-booking'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="cb-modal-error" id="cb-modal-error" style="display: none;"></div>
                    </div>
                </div>

                <div class="cb-modal-footer">
                    <button type="button" class="button cb-modal-cancel">
                        <?php _e('Cancel', 'class-booking'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="cb-save-session">
                        <span class="cb-btn-text"><?php _e('Save Session', 'class-booking'); ?></span>
                        <span class="spinner" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>

        <?php
    }
}
