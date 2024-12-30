<?php

/*
Plugin Name: Bulk Product Price Updater
Description: Update all WooCommerce product prices by a percentage.
Version: 1.01
Author: Yaniv Sasson
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include core files
require_once plugin_dir_path(__FILE__) . 'includes/db-setup.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/main.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/log.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/status.php';







?>