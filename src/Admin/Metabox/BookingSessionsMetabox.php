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
        ?>

        <p>
            <strong><?php _e('Existing sessions', 'class-booking'); ?></strong>
        </p>

        <?php if (empty($sessions)): ?>
        <p><?php _e('No sessions yet.', 'class-booking'); ?></p>
    <?php else: ?>
        <table class="widefat">
            <thead>
            <tr>
                <th><?php _e('Date'); ?></th>
                <th><?php _e('Time'); ?></th>
                <th><?php _e('Capacity'); ?></th>
                <th><?php _e('Remaining'); ?></th>
                <th><?php _e('Status'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?php echo esc_html($session['session_date']); ?></td>
                    <td>
                        <?php echo esc_html($session['start_time']); ?>
                        –
                        <?php echo esc_html($session['end_time']); ?>
                    </td>
                    <td><?php echo (int) $session['capacity']; ?></td>
                    <td><?php echo (int) $session['remaining_capacity']; ?></td>
                    <td><?php echo esc_html($session['status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>


        <h4><?php _e('Add new session', 'class-booking'); ?></h4>
        <?php wp_nonce_field('add_booking_session', 'add_booking_session_nonce'); ?>

        <input type="hidden" name="add_booking_session" value="1">

        <p>
            <label>
                Date<br>
                <input type="date" name="session_date">
            </label>
        </p>

        <p>
            <label>
                Start time<br>
                <input type="time" name="start_time">
            </label>
        </p>

        <p>
            <label>
                End time<br>
                <input type="time" name="end_time">
            </label>
        </p>

        <p>
            <label>
                Capacity<br>
                <input type="number" name="capacity" min="1">
            </label>
        </p>

        <p>
            <button type="submit" class="button button-primary">
                Add session
            </button>
        </p>

        <hr />
        <p>
            <em><?php _e('Session creation will be handled here in the next step.', 'class-booking'); ?></em>
        </p>

        <?php
    }
}
