<?php


if (!defined('ABSPATH')) {
    exit;
}




function bulk_price_updater_update_prices($percentage) {
    if (!class_exists('WooCommerce')) {
        echo '<div class="notice notice-error"><p>WooCommerce is not active. Please activate WooCommerce first.</p></div>';
        return;
    }

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'any', // Include all post statuses
    ];

    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;
        $product_obj = wc_get_product($product_id);

        if ($product_obj) {
            // Handle Variable Products
            if ($product_obj->is_type('variable')) {
                $variations = $product_obj->get_children();
                foreach ($variations as $variation_id) {
                    $variation_obj = wc_get_product($variation_id);

                    if ($variation_obj) {
                        // Update Regular Price
                        $regular_price = floatval($variation_obj->get_regular_price());
                        if ($regular_price > 0) {
                            $new_regular_price = $regular_price + ($regular_price * ($percentage / 100));
                            $new_regular_price = ceil($new_regular_price);
                            $variation_obj->set_regular_price($new_regular_price);
                        }

                        // Update Sale Price
                        $sale_price = floatval($variation_obj->get_sale_price());
                        if ($sale_price > 0) {
                            $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                            $new_sale_price = ceil($new_sale_price);
                            $variation_obj->set_sale_price($new_sale_price);
                        }

                        $variation_obj->save();
                    }
                }
            } else {
                // Handle Simple Products
                // Update Regular Price
                $regular_price = floatval($product_obj->get_regular_price());
                if ($regular_price > 0) {
                    $new_regular_price = $regular_price + ($regular_price * ($percentage / 100));
                    $new_regular_price = ceil($new_regular_price);
                    $product_obj->set_regular_price($new_regular_price);
                }

                // Update Sale Price
                $sale_price = floatval($product_obj->get_sale_price());
                if ($sale_price > 0) {
                    $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                    $new_sale_price = ceil($new_sale_price);
                    $product_obj->set_sale_price($new_sale_price);
                }

                $product_obj->save();
            }
        }
    }

    echo '<div class="notice notice-success"><p>Prices updated successfully for all products, including all post statuses!</p></div>';
}



