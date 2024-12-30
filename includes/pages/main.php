<?php 


// Display plugin page
function bulk_price_updater_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Product Price Updater</h1>
        <p>Enter the percentage by which you want to update the prices and click the button to start the process.</p>
        <form id="price-updater-form">
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
            let offset = 0;
            let batchSize = 50;

            function updateBatch(percentage) {
                $.post(ajaxurl, {
                    action: 'bulk_price_updater',
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
                            updateBatch(percentage);
                        } else {
                            $('#status-message').text('All products have been updated!');
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
                $('#status-message').text('Updating prices... Please wait.');
                updateBatch(percentage);
            });
        })(jQuery);
    </script>
    <?php
}
