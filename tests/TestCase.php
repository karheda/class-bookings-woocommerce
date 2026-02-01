<?php

namespace ClassBooking\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for Class Booking plugin tests.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

