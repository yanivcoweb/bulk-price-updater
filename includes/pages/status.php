<?php 







function bulk_price_updater_display_all_products() {
    global $wpdb;
    $processed_table_name = $wpdb->prefix . 'processed_products';

    // Get all products
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'any', // Include all product statuses
    ];
    $products = get_posts($args);

    ?>
    <div class="wrap">
        <h1>All Products Status</h1>
        <form id="not-processed-form">
            <label for="percentage">Percentage Change (%)</label>
            <input type="number" id="percentage" name="percentage" step="0.1" required style="width: 100px;">
            <p class="description">Enter a positive value to increase prices or a negative value to decrease them.</p>
            <button id="start-update" type="button" class="button button-primary">Update Not Processed Products</button>
        </form>
        <div id="progress" style="margin-top: 20px;">
            <div id="progress-bar" style="width: 0%; height: 20px; background: green;"></div>
        </div>
        <p id="status-message"></p>
        <table class="widefat fixed" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Link</th>
                    <th>Processed Log ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)) : ?>
                    <tr>
                        <td colspan="3">No products found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($products as $product) : ?>
                        <?php
                        // Check if the product exists in the processed log table
                        $processed_log = $wpdb->get_row($wpdb->prepare(
                            "SELECT id FROM $processed_table_name WHERE product_id = %d",
                            $product->ID
                        ));
                        ?>
                        <tr>
                            <td><?php echo esc_html($product->ID); ?></td>
                            <td><a href="<?php echo esc_url(get_permalink($product->ID)); ?>" target="_blank"><?php echo esc_html(get_permalink($product->ID)); ?></a></td>
                            <td><?php echo $processed_log ? esc_html($processed_log->id) : 'Not Processed'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        (function($) {
            let batchSize = 50; // Adjust batch size if needed
            let offset = 0;

            function updateNotProcessedProducts(percentage) {
                $.post(ajaxurl, {
                    action: 'update_not_processed_products',
                    offset: offset,
                    batch_size: batchSize,
                    percentage: percentage
                }, function(response) {
                    if (response.success) {
                        offset += batchSize;

                        // Update progress bar
                        let progress = (response.data.total_done / response.data.total_products) * 100;
                        $('#progress-bar').css('width', progress + '%');

                        // Check if more products remain
                        if (response.data.remaining > 0) {
                            updateNotProcessedProducts(percentage);
                        } else {
                            $('#status-message').text('All "Not Processed" products have been updated!');
                        }
                    } else {
                        $('#status-message').text('An error occurred: ' + response.data.message);
                    }
                });
            }

            $('#start-update').on('click', function() {
                const percentage = parseFloat($('#percentage').val());
                if (isNaN(percentage)) {
                    alert('Please enter a valid percentage.');
                    return;
                }
                $('#status-message').text('Updating "Not Processed" products... Please wait.');
                updateNotProcessedProducts(percentage);
            });
        })(jQuery);
    </script>
    <?php
}

