<?php

namespace ClassBooking\Activation;

use src\Infrastructure\Database\Schema;

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
