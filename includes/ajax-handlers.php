<?php

if (!defined('ABSPATH')) {
    exit;
}


add_action('wp_ajax_initialize_processed_table', 'initialize_processed_table');

function initialize_processed_table() {
    global $wpdb;

    $processed_table_name = $wpdb->prefix . 'processed_products';

    // Get all product IDs, including out-of-stock products
    $products = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'any', // Include all product statuses
        'fields'         => 'ids', // Retrieve only IDs
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '=', // Include "In Stock" products
            ],
            [
                'key'     => '_stock_status',
                'value'   => 'outofstock',
                'compare' => '=', // Include "Out of Stock" products
            ],
        ],
    ]);

    foreach ($products as $product_id) {
        $product_link = get_permalink($product_id);

        // Insert the product into the processed_products table if not already added
        $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO $processed_table_name (product_id, product_link, status, date_processed)
                 VALUES (%d, %s, %s, %s)",
                $product_id,
                $product_link,
                'Not Processed',
                null
            )
        );
    }

    wp_send_json_success(['message' => 'Table initialized successfully.']);
}

add_action('wp_ajax_bulk_price_updater', 'bulk_price_updater_ajax_handler');

function bulk_price_updater_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized.']);
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'processed_products';
    $batch_size = intval($_POST['batch_size']);
    $percentage = floatval($_POST['percentage']);

    // Fetch products from the processed_products table that are not yet updated
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT product_id FROM $table_name WHERE status = %s LIMIT %d",
            'Not Processed',
            $batch_size
        )
    );

    if (empty($products)) {
        wp_send_json_success(['message' => 'No more products to process.']);
    }

    foreach ($products as $row) {
        $product_id = $row->product_id;
        $product_obj = wc_get_product($product_id);

        if (!$product_obj) {
            // Mark as failed if the product object can't be loaded
            $wpdb->update(
                $table_name,
                ['status' => 'Failed', 'date_processed' => current_time('mysql')],
                ['product_id' => $product_id],
                ['%s', '%s'],
                ['%d']
            );
            continue;
        }

        $updated = false;

        // Handle variable products
        if ($product_obj->is_type('variable')) {
            foreach ($product_obj->get_children() as $variation_id) {
                $variation_obj = wc_get_product($variation_id);

                if ($variation_obj) {
                    $regular_price = floatval($variation_obj->get_regular_price());
                    if ($regular_price > 0) {
                        $new_regular_price = $regular_price + ($regular_price * ($percentage / 100));
                        $variation_obj->set_regular_price(ceil($new_regular_price));
                        $updated = true;
                    }

                    $sale_price = floatval($variation_obj->get_sale_price());
                    if ($sale_price > 0) {
                        $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                        $variation_obj->set_sale_price(ceil($new_sale_price));
                        $updated = true;
                    }

                    $variation_obj->save();
                }
            }
        } else {
            // Handle simple products
            $regular_price = floatval($product_obj->get_regular_price());
            if ($regular_price > 0) {
                $new_regular_price = $regular_price + ($regular_price * ($percentage / 100));
                $product_obj->set_regular_price(ceil($new_regular_price));
                $updated = true;
            }

            $sale_price = floatval($product_obj->get_sale_price());
            if ($sale_price > 0) {
                $new_sale_price = $sale_price + ($sale_price * ($percentage / 100));
                $product_obj->set_sale_price(ceil($new_sale_price));
                $updated = true;
            }

            $product_obj->save();
        }

        // Update the status in the processed_products table
        $wpdb->update(
            $table_name,
            ['status' => $updated ? 'Updated' : 'Failed', 'date_processed' => current_time('mysql')],
            ['product_id' => $product_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    wp_send_json_success(['message' => 'Batch processed successfully.']);
}

/*
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

        // Insert placeholder record into the database
        $wpdb->insert(
            $table_name,
            [
                'product_id'   => $product_id,
                'product_link' => get_permalink($product_id),
            ],
            [
                '%d',
                '%s',
            ]
        );

        $product_obj = wc_get_product($product_id);
        $updated = false;

        if ($product_obj) {
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
        }

        // Update the status in the processed_products table
        $wpdb->update(
            $table_name,
            ['status' => $updated ? 'Updated' : 'Failed'], // Add a status column to indicate success or failure
            ['product_id' => $product_id], // Where condition
            ['%s'], // Data type for status
            ['%d']  // Data type for product_id
        );
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

*/


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


