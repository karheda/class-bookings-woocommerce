<?php

namespace ClassBooking\Front\Shortcode;

use ClassBooking\Infrastructure\Repository\ClassSessionRepository;

defined('ABSPATH') || exit;

final class ClassSessionsShortcode
{
    public static function register(): void
    {
        add_shortcode('class_sessions', [self::class, 'render']);
    }

    public static function render(): string
    {
        $repository = new ClassSessionRepository();
        $sessions = $repository->findActive();

        if (empty($sessions)) {
            return '<p>No classes available at the moment.</p>';
        }

        ob_start();
        ?>
        <div class="class-sessions">
            <?php foreach ($sessions as $session): ?>
                <?php
                $addToCartUrl = esc_url(
                        add_query_arg(
                                'add-to-cart',
                                $session['product_id'],
                                wc_get_cart_url()
                        )
                );
                ?>
                <div class="class-session">
                    <h3>
                        <?php echo esc_html(ucfirst($session['weekday'])); ?>
                        ·
                        <?php echo esc_html(substr($session['start_time'], 0, 5)); ?>
                        –
                        <?php echo esc_html(substr($session['end_time'], 0, 5)); ?>
                    </h3>

                    <p>
                        <strong>Price:</strong>
                        <?php echo esc_html(number_format($session['price'], 2)); ?> €
                    </p>

                    <?php if ((int)$session['remaining_capacity'] > 0): ?>
                        <p>
                            <strong>Remaining spots:</strong>
                            <?php echo (int)$session['remaining_capacity']; ?>
                        </p>
                        <a href="<?php echo $addToCartUrl; ?>" class="button reserve-disabled">
                            Reserve
                        </a>
                    <?php else: ?>
                        <p class="sold-out">
                            Sold out
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php

        return ob_get_clean();
    }
}
