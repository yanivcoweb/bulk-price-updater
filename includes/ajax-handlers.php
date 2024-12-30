<?php

if (!defined('ABSPATH')) {
    exit;
}


add_action('wp_ajax_bulk_price_updater', 'bulk_price_updater_ajax_handler');
function bulk_price_updater_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'processed_products';
    
    $offset = intval($_POST['offset']);
    $batch_size = intval($_POST['batch_size']);
    $percentage = floatval($_POST['percentage']);

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'any', // Include all product statuses
    ];

    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;

        // Skip if the product is already processed
        $is_processed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE product_id = %d",
            $product_id
        ));
        
        if ($is_processed) {
            continue; // Skip this product
        }

        $product_obj = wc_get_product($product_id);

        if ($product_obj) {
            $updated = false;

            // Update Variable Products
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
                            $updated = true;
                        }

                        // Update Sale Price
                        $sale_price = floatval($variation_obj->get_sale_price());
                        if ($sale_price > 0) {
                            $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                            $new_sale_price = ceil($new_sale_price);
                            $variation_obj->set_sale_price($new_sale_price);
                            $updated = true;
                        }

                        $variation_obj->save(); // Save the updated variation
                    }
                }
            } else {
                // Update Simple Products
                $regular_price = floatval($product_obj->get_regular_price());
                if ($regular_price > 0) {
                    $new_regular_price = $regular_price + ($regular_price * ($percentage / 100));
                    $new_regular_price = ceil($new_regular_price);
                    $product_obj->set_regular_price($new_regular_price);
                    $updated = true;
                }

                $sale_price = floatval($product_obj->get_sale_price());
                if ($sale_price > 0) {
                    $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                    $new_sale_price = ceil($new_sale_price);
                    $product_obj->set_sale_price($new_sale_price);
                    $updated = true;
                }

                $product_obj->save();
            }

            // Log the product as processed if it was updated
            if ($updated) {
                $product_link = get_permalink($product_id);

                $wpdb->insert(
                    $table_name,
                    [
                        'product_id'   => $product_id,
                        'product_link' => $product_link,
                    ],
                    [
                        '%d',
                        '%s',
                    ]
                );
            }
        }
    }

    // Get total product count for progress tracking
    $total_products = wp_count_posts('product')->publish;

    // Send response
    wp_send_json_success([
        'remaining'      => $total_products - ($offset + $batch_size),
        'total_done'     => $offset + $batch_size,
        'total_products' => $total_products,
    ]);
}


add_action('wp_ajax_update_not_processed_products', 'update_not_processed_products_ajax_handler');
function update_not_processed_products_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized.']);
    }

    global $wpdb;
    $processed_table_name = $wpdb->prefix . 'processed_products';

    $offset = intval($_POST['offset']);
    $batch_size = intval($_POST['batch_size']);
    $percentage = floatval($_POST['percentage']);

	// Get total product count for progress tracking
	$total_products = (new WP_Query([
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]))->found_posts;

    // Get products that are not processed
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'any',
    ];
    $products = get_posts($args);

    foreach ($products as $product) {
        $product_id = $product->ID;

        // Check if the product exists in the processed log table
        $is_processed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $processed_table_name WHERE product_id = %d",
            $product_id
        ));

        if ($is_processed) {
            continue; // Skip already processed products
        }

        $product_obj = wc_get_product($product_id);

        if ($product_obj) {
            $updated = false;

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
                            $updated = true;
                        }

                        // Update Sale Price
                        $sale_price = floatval($variation_obj->get_sale_price());
                        if ($sale_price > 0) {
                            $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                            $new_sale_price = ceil($new_sale_price);
                            $variation_obj->set_sale_price($new_sale_price);
                            $updated = true;
                        }

                        $variation_obj->save(); // Save the updated variation
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
                    $updated = true;
                }

                // Update Sale Price
                $sale_price = floatval($product_obj->get_sale_price());
                if ($sale_price > 0) {
                    $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                    $new_sale_price = ceil($new_sale_price);
                    $product_obj->set_sale_price($new_sale_price);
                    $updated = true;
                }

                $product_obj->save();
            }

            // Log the product as processed if it was updated
			error_log('$updated');
			error_log($updated);
            if ($updated) {
				error_log('$product_id');
				error_log($product_id);
                $product_link = get_permalink($product_id);

                $wpdb->insert(
                    $processed_table_name,
                    [
                        'product_id'   => $product_id,
                        'product_link' => $product_link,
                    ],
                    [
                        '%d',
                        '%s',
                    ]
                );
            }
        }
    
	}

  

    wp_send_json_success([
        'remaining'      => $total_products - ($offset + $batch_size),
        'total_done'     => $offset + $batch_size,
        'total_products' => $total_products,
    ]);
	
}


