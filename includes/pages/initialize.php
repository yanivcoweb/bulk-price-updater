<?php

function initialize_processed_table_page() {
    ?>
    <div class="wrap">
        <h1>Initialize Processed Products Table</h1>
        <p>Click the button below to add all WooCommerce product IDs to the `processed_products` table with the status "Not Processed".</p>
        <button id="initialize-table-button" class="button button-primary">Initialize Table</button>
        <p id="status-message" style="margin-top: 20px;"></p>
    </div>
    <script>
        (function($) {
            $('#initialize-table-button').on('click', function() {
                const $button = $(this);
                $button.prop('disabled', true).text('Initializing...');

                $.post(ajaxurl, { action: 'initialize_processed_table' }, function(response) {
                    if (response.success) {
                        $('#status-message').text('Table initialized successfully.');
                    } else {
                        $('#status-message').text('An error occurred: ' + response.data.message);
                    }
                    $button.prop('disabled', false).text('Initialize Table');
                });
            });
        })(jQuery);
    </script>
    <?php
}
