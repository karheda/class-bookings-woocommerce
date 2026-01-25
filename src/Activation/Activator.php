<?php

namespace ClassBooking\Activation;

use ClassBooking\Infrastructure\Database\Schema;

defined('ABSPATH') || exit;

final class Activator
{
    public static function activate(): void
    {
        Schema::createTables();
    }

    public static function deactivate(): void
    {
        // Nothing for now
    }
}
