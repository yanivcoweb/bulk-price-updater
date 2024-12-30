<?php

if (!defined('ABSPATH')) {
    exit;
}

// Add admin menus
add_action('admin_menu', 'bulk_price_updater_menu');

function bulk_price_updater_menu() {
    add_menu_page(
        'Bulk Price Updater',
        'Price Updater',
        'manage_options',
        'bulk-price-updater',
        'bulk_price_updater_page',
        'dashicons-admin-generic',
        1
    );

    add_submenu_page(
        'bulk-price-updater',
        'Processed Products Log',
        'Processed Products Log',
        'manage_options',
        'processed-products-log',
        'bulk_price_updater_display_log'
    );
	
	add_submenu_page(
        'bulk-price-updater',          // Parent slug
        'All Products Status',         // Page title
        'All Products Status',         // Menu title
        'manage_options',              // Capability
        'all-products-status',         // Menu slug
        'bulk_price_updater_display_all_products' // Callback function
    );
}




