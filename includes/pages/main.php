<?php 


function bulk_price_updater_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Product Price Updater</h1>
        <form id="bulk-price-updater-form">
            <label for="percentage">Percentage Change (%)</label>
            <input type="number" id="percentage" name="percentage" step="0.1" required style="width: 100px;">
            <p class="description">Enter a positive value to increase prices or a negative value to decrease them.</p>
            <button id="start-update" type="button" class="button button-primary">Start Updating Prices</button>
        </form>
        <div id="progress" style="margin-top: 20px;">
            <div id="progress-bar" style="width: 0%; height: 20px; background: green;"></div>
        </div>
        <p id="status-message"></p>
    </div>
    <script>
        (function($) {
            let batchSize = 50; // Adjust batch size as needed
            let percentage = 0;

            function processBatch() {
                $.post(ajaxurl, {
                    action: 'bulk_price_updater',
                    batch_size: batchSize,
                    percentage: percentage
                }, function(response) {
                    if (response.success) {
                        $('#status-message').text(response.data.message);

                        // Continue processing if there are more products
                        if (response.data.message !== 'No more products to process.') {
                            processBatch();
                        } else {
                            $('#status-message').text('All products have been processed.');
                        }
                    } else {
                        $('#status-message').text('An error occurred: ' + response.data.message);
                    }
                });
            }

            $('#start-update').on('click', function() {
                percentage = parseFloat($('#percentage').val());
                if (isNaN(percentage)) {
                    alert('Please enter a valid percentage.');
                    return;
                }

                $('#status-message').text('Processing products... Please wait.');
                processBatch();
            });
        })(jQuery);
    </script>
    <?php
}
