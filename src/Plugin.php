<?php

namespace ClassBooking;

use ClassBooking\Admin\Metabox\ClassSessionMetabox;
use ClassBooking\Admin\PostType\ClassSessionPostType;
use ClassBooking\Front\Handler\ReserveClassHandler;
use ClassBooking\Front\Shortcode\ClassSessionsShortcode;
use ClassBooking\WooCommerce\Hooks\AddToCartValidation;
use ClassBooking\WooCommerce\Hooks\ClassSessionSaveHook;
use ClassBooking\WooCommerce\Hooks\DisableCartQuantity;
use ClassBooking\WooCommerce\Hooks\OrderCompleted;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function init(): void
    {
        add_action('init', [ClassSessionPostType::class, 'register']);
        add_action('init', [ReserveClassHandler::class, 'handle']);

        ClassSessionMetabox::register();
        ClassSessionSaveHook::register();

        ClassSessionsShortcode::register();
        AddToCartValidation::register();
        OrderCompleted::register();
        DisableCartQuantity::register();
    }
}