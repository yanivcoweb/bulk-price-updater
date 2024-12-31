<?php 


function bulk_price_updater_display_log() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'processed_products';

    // Get all processed products
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");

    ?>
    <div class="wrap">
        <h1>Processed Products Log</h1>
        <?php if (empty($results)) : ?>
            <p>No products have been processed yet.</p>
        <?php else : ?>
            <table class="widefat fixed" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Product ID</th>
                        <th>Product Link</th>
                        <th>Status</th>
                        <th>Date Processed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1; // Initialize counter for the # column
                    foreach ($results as $row) : ?>
                        <tr>
                            <td><?php echo esc_html($counter++); ?></td>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->product_id); ?></td>
                            <td>
                                <a href="<?php echo esc_url($row->product_link); ?>" target="_blank">
                                    <?php //echo esc_html($row->product_link); ?>link
                                </a>
                            </td>
                            <td><?php echo esc_html(isset($row->status) ? $row->status : 'Unknown'); ?></td>
                            <td><?php echo esc_html(isset($row->date_processed) ? $row->date_processed : 'Not Recorded'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

