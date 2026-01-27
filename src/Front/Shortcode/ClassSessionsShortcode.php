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
                $currentQty = 0;

                if (WC()->cart) {
                    foreach (WC()->cart->get_cart() as $item) {
                        if ((int)$item['product_id'] === (int)$session['product_id']) {
                            $currentQty = (int)$item['quantity'];
                        }
                    }
                }
                $remainingQuantity = (int)$session['remaining_capacity'] - $currentQty;
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

                    <?php if ($remainingQuantity > 0): ?>
                        <p>
                            <strong>Remaining spots:</strong>
                            <?php echo $remainingQuantity; ?>
                        </p>

                        <form method="post" action="<?php echo esc_url(wc_get_cart_url()); ?>">
                            <input
                                    type="hidden"
                                    name="class_booking_action"
                                    value="reserve"
                            />
                            <input
                                    type="hidden"
                                    name="add-to-cart"
                                    value="<?php echo (int)$session['product_id']; ?>"
                            />

                            <input
                                    type="hidden"
                                    name="class_booking_product_id"
                                    value="<?php echo (int) $session['product_id']; ?>"
                            />

                            <label>
                                Persons:
                                <input
                                        type="number"
                                        name="quantity"
                                        min="1"
                                        max="<?php echo $remainingQuantity; ?>"
                                        value="1"
                                        required
                                />
                            </label>

                            <button type="submit" class="button">
                                Reserve
                            </button>
                        </form>

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
