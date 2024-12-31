<?php

if (!defined('ABSPATH')) {
    exit;
}
error_log("db-setup.php");

/*
 ***** ***** ***** ***** ***** *****
****** Run This code One Time *****
 ***** ***** ***** ***** ***** ***** *****

function bulk_price_updater_create_table_temp() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'processed_products';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) NOT NULL,
        product_link VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'Pending',
        date_processed DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    echo 'Table created or already exists.';
}
bulk_price_updater_create_table_temp();
*/