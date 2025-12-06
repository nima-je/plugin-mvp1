<?php
namespace WCSD;

use WCSD\Dashboard;
use WCSD\Products;
use WCSD\Coupons;
use WCSD\Stats;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    /** @var Plugin */
    private static $instance;

    /**
     * Get singleton instance.
     *
     * @return Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks.
     */
    public function init() {
        $this->load_textdomain();

        register_activation_hook( WCSD_PLUGIN_FILE, array( $this, 'activate' ) );
        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Initialize modules.
        Dashboard::get_instance();
        Products::get_instance();
        Coupons::get_instance();
        Stats::get_instance();
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'wc-seller-dashboard', false, dirname( plugin_basename( WCSD_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Activation hook: create db table for product views.
     */
    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name      = $wpdb->prefix . 'wcsd_product_views';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            referrer VARCHAR(191) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Register shortcode for dashboard.
     */
    public function register_shortcode() {
        add_shortcode( 'store_owner_dashboard', array( Dashboard::get_instance(), 'render_dashboard' ) );
    }

    /**
     * Enqueue assets on shortcode pages.
     */
    public function enqueue_assets() {
        if ( ! is_singular() ) {
            return;
        }

        global $post;
        if ( ! has_shortcode( $post->post_content, 'store_owner_dashboard' ) ) {
            return;
        }

        wp_enqueue_style( 'wcsd-dashboard', WCSD_PLUGIN_URL . 'assets/css/dashboard.css', array(), '1.0.0' );
        wp_enqueue_script( 'wcsd-dashboard', WCSD_PLUGIN_URL . 'assets/js/dashboard.js', array( 'jquery' ), '1.0.0', true );

        wp_localize_script( 'wcsd-dashboard', 'wcsd_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wcsd_nonce' ),
        ) );
    }
}
