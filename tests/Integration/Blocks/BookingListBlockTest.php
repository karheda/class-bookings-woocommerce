<?php

namespace ClassBooking\Tests\Integration\Blocks;

use ClassBooking\Blocks\BookingListBlock;
use ClassBooking\Tests\TestCase;

class BookingListBlockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BookingListBlock::registerBlock();
    }

    public function testBlockIsRegistered(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $block = $registry->get_registered('class-booking/booking-list');

        $this->assertNotNull($block, 'Block should be registered');
        $this->assertEquals('class-booking/booking-list', $block->name);
    }

    public function testBlockHasCorrectAttributes(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $block = $registry->get_registered('class-booking/booking-list');

        $this->assertArrayHasKey('category', $block->attributes);
        $this->assertArrayHasKey('primaryColor', $block->attributes);
        $this->assertArrayHasKey('secondaryColor', $block->attributes);
        $this->assertArrayHasKey('textColor', $block->attributes);
        $this->assertArrayHasKey('accentColor', $block->attributes);
    }

    public function testBlockAttributeDefaults(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $block = $registry->get_registered('class-booking/booking-list');

        $this->assertEquals('', $block->attributes['category']['default']);
        $this->assertEquals('#2271b1', $block->attributes['primaryColor']['default']);
        $this->assertEquals('#f0f0f1', $block->attributes['secondaryColor']['default']);
        $this->assertEquals('#1d2327', $block->attributes['textColor']['default']);
        $this->assertEquals('#d63638', $block->attributes['accentColor']['default']);
    }

    public function testRenderReturnsErrorWhenNoCategorySelected(): void
    {
        $output = BookingListBlock::render([
            'category' => '',
            'primaryColor' => '#2271b1',
            'secondaryColor' => '#f0f0f1',
            'textColor' => '#1d2327',
            'accentColor' => '#d63638',
        ]);

        $this->assertStringContainsString('cb-error', $output);
        $this->assertStringContainsString('select a category', strtolower($output));
    }

    public function testRenderReturnsNoBookingsMessageForEmptyCategory(): void
    {
        // Create a category with no bookings
        $term = wp_insert_term('Empty Category', 'booking_category', ['slug' => 'empty-category']);

        $output = BookingListBlock::render([
            'category' => 'empty-category',
            'primaryColor' => '#2271b1',
            'secondaryColor' => '#f0f0f1',
            'textColor' => '#1d2327',
            'accentColor' => '#d63638',
        ]);

        $this->assertStringContainsString('cb-no-bookings', $output);

        // Cleanup
        if (!is_wp_error($term)) {
            wp_delete_term($term['term_id'], 'booking_category');
        }
    }

    public function testRenderIncludesCustomCssVariables(): void
    {
        // Create category and booking
        $term = wp_insert_term('Test Category', 'booking_category', ['slug' => 'test-gutenberg-cat']);
        $postId = wp_insert_post([
            'post_type' => 'booking',
            'post_title' => 'Test Booking',
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($term)) {
            wp_set_object_terms($postId, $term['term_id'], 'booking_category');
        }

        $output = BookingListBlock::render([
            'category' => 'test-gutenberg-cat',
            'primaryColor' => '#ff0000',
            'secondaryColor' => '#00ff00',
            'textColor' => '#0000ff',
            'accentColor' => '#ffff00',
        ]);

        $this->assertStringContainsString('--cb-primary-color: #ff0000', $output);
        $this->assertStringContainsString('--cb-secondary-color: #00ff00', $output);
        $this->assertStringContainsString('--cb-text-color: #0000ff', $output);
        $this->assertStringContainsString('--cb-accent-color: #ffff00', $output);
        $this->assertStringContainsString('cb-gutenberg-block', $output);

        // Cleanup
        wp_delete_post($postId, true);
        if (!is_wp_error($term)) {
            wp_delete_term($term['term_id'], 'booking_category');
        }
    }

    public function testRenderSanitizesColorValues(): void
    {
        $term = wp_insert_term('Sanitize Test', 'booking_category', ['slug' => 'sanitize-test']);
        $postId = wp_insert_post([
            'post_type' => 'booking',
            'post_title' => 'Sanitize Test Booking',
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($term)) {
            wp_set_object_terms($postId, $term['term_id'], 'booking_category');
        }

        // Try to inject malicious content via color attribute
        $output = BookingListBlock::render([
            'category' => 'sanitize-test',
            'primaryColor' => '#ff0000"><script>alert("xss")</script>',
            'secondaryColor' => '#00ff00',
            'textColor' => '#0000ff',
            'accentColor' => '#ffff00',
        ]);

        // Should not contain the script tag
        $this->assertStringNotContainsString('<script>', $output);

        // Cleanup
        wp_delete_post($postId, true);
        if (!is_wp_error($term)) {
            wp_delete_term($term['term_id'], 'booking_category');
        }
    }

    public function testBlockHasRenderCallback(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $block = $registry->get_registered('class-booking/booking-list');

        $this->assertNotNull($block->render_callback);
        $this->assertIsCallable($block->render_callback);
    }
}

