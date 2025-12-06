<?php
/**
 * Plugin Name: فروشنده ووکامرس
 * Description: داشبورد مدیریتی ساده برای فروشگاه‌دار جهت مدیریت آمار، محصولات و کدهای تخفیف ووکامرس از سمت کاربر.
 * Version: 1.0.0
 * Author: ChatGPT
 * Text Domain: wc-seller-dashboard
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WCSD_PLUGIN_FILE', __FILE__ );
define( 'WCSD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCSD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

autoload_wcsd_includes();

/**
 * Simple autoloader for plugin classes.
 */
function autoload_wcsd_includes() {
    $files = array(
        'includes/class-wcsd-plugin.php',
        'includes/class-wcsd-dashboard.php',
        'includes/class-wcsd-products.php',
        'includes/class-wcsd-coupons.php',
        'includes/class-wcsd-stats.php',
    );

    foreach ( $files as $file ) {
        $path = WCSD_PLUGIN_DIR . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

/**
 * Initialize plugin.
 */
function wcsd_init_plugin() {
    $plugin = \WCSD\Plugin::get_instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'wcsd_init_plugin' );
