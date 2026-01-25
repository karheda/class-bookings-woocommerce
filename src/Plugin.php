<?php

namespace ClassBooking;

use ClassBooking\Admin\Metabox\ClassSessionMetabox;
use ClassBooking\Admin\PostType\ClassSessionPostType;
use ClassBooking\Front\Shortcode\ClassSessionsShortcode;
use ClassBooking\WooCommerce\Hooks\ClassSessionSaveHook;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function init(): void
    {
        add_action('init', [ClassSessionPostType::class, 'register']);

        ClassSessionMetabox::register();
        ClassSessionSaveHook::register();

        ClassSessionsShortcode::register();
    }
}