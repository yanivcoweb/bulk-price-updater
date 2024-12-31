<?php

function bulk_price_updater_not_processed_page() {
    global $wpdb;

    // Table names
    $processed_table_name = $wpdb->prefix . 'processed_products';

    // Get all product IDs that are logged as processed
    $processed_product_ids = $wpdb->get_col("SELECT product_id FROM $processed_table_name");

    // Get all products that are not processed
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'post__not_in'   => $processed_product_ids,
    ];
    $not_processed_products = get_posts($args);

    ?>
    <div class="wrap">
        <h1>Not Processed Products</h1>
        <?php if (empty($not_processed_products)) : ?>
            <p>All products have been processed.</p>
        <?php else : ?>
            <table class="widefat fixed" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product ID</th>
                        <th>Product Link</th>
                        <th>Status</th>
                        <th>Date Processed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $line_number = 1; // Initialize the line number
                    foreach ($not_processed_products as $product) :
                        // Fetch product details from the processed log
                        $processed_log = $wpdb->get_row($wpdb->prepare(
                            "SELECT status, date_processed FROM $processed_table_name WHERE product_id = %d",
                            $product->ID
                        ));
                        ?>
                        <tr>
                            <td><?php echo esc_html($line_number++); ?></td>
                            <td><?php echo esc_html($product->ID); ?></td>
                            <td><a href="<?php echo esc_url(get_permalink($product->ID)); ?>" target="_blank">Link<?php //echo esc_html(get_permalink($product->ID)); ?></a></td>
                            <td><?php echo $processed_log ? esc_html($processed_log->status) : 'Not Processed'; ?></td>
                            <td><?php echo $processed_log ? esc_html($processed_log->date_processed) : 'Not Processed'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
