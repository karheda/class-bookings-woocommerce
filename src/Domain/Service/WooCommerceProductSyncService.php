<?php

namespace ClassBooking\Domain\Service;

use WC_Product_Simple;
use WP_Post;

defined('ABSPATH') || exit;

final class WooCommerceProductSyncService
{
    public function syncFromClassSession(WP_Post $post): int
    {
        $existingProductId = get_post_meta($post->ID, '_product_id', true);

        if ($existingProductId) {
            $product = wc_get_product($existingProductId);
        } else {
            $product = new WC_Product_Simple();
        }

        $product->set_name($post->post_title);
        $product->set_description($post->post_content);
        $product->set_regular_price(
            get_post_meta($post->ID, '_price', true)
        );
        $product->set_virtual(true);
        $product->set_catalog_visibility('hidden');
        $product->set_status('publish');

        $productId = $product->save();

        update_post_meta($post->ID, '_product_id', $productId);

        return $productId;
    }
}
