<?php
namespace WCSD;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Dashboard {
    /** @var Dashboard */
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_shortcode( 'store_owner_dashboard', array( $this, 'render_dashboard' ) );
    }

    /**
     * Check capability for dashboard access.
     *
     * @return bool
     */
    public function current_user_can_access() {
        return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
    }

    /**
     * Render dashboard shortcode.
     */
    public function render_dashboard() {
        if ( ! is_user_logged_in() || ! $this->current_user_can_access() ) {
            return '<div class="wcsd-error">' . esc_html__( 'شما دسترسی لازم برای مشاهده این بخش را ندارید.', 'wc-seller-dashboard' ) . '</div>';
        }

        ob_start();
        $this->render_layout();
        return ob_get_clean();
    }

    /**
     * Layout for dashboard.
     */
    private function render_layout() {
        $active_tab = isset( $_GET['wcsd_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsd_tab'] ) ) : 'stats';
        $message    = ! empty( $_GET['wcsd_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsd_msg'] ) ) : '';
        ?>
        <div class="wcsd-container" dir="rtl">
            <div class="wcsd-nav">
                <button class="wcsd-tab-button" data-target="wcsd-stats" aria-label="آمار" aria-pressed="<?php echo $active_tab === 'stats' ? 'true' : 'false'; ?>"><?php esc_html_e( 'آمار فروشگاه', 'wc-seller-dashboard' ); ?></button>
                <button class="wcsd-tab-button" data-target="wcsd-products" aria-label="محصولات" aria-pressed="<?php echo $active_tab === 'products' ? 'true' : 'false'; ?>"><?php esc_html_e( 'محصولات', 'wc-seller-dashboard' ); ?></button>
                <button class="wcsd-tab-button" data-target="wcsd-coupons" aria-label="کدهای تخفیف" aria-pressed="<?php echo $active_tab === 'coupons' ? 'true' : 'false'; ?>"><?php esc_html_e( 'کدهای تخفیف', 'wc-seller-dashboard' ); ?></button>
            </div>

            <?php if ( $message ) : ?>
                <div class="wcsd-notice"><?php echo esc_html( $message ); ?></div>
            <?php endif; ?>

            <div class="wcsd-tab-content <?php echo $active_tab === 'stats' ? 'is-active' : ''; ?>" id="wcsd-stats">
                <?php Stats::get_instance()->render_stats_section(); ?>
            </div>

            <div class="wcsd-tab-content <?php echo $active_tab === 'products' ? 'is-active' : ''; ?>" id="wcsd-products">
                <?php Products::get_instance()->render_products_section(); ?>
            </div>

            <div class="wcsd-tab-content <?php echo $active_tab === 'coupons' ? 'is-active' : ''; ?>" id="wcsd-coupons">
                <?php Coupons::get_instance()->render_coupons_section(); ?>
            </div>
        </div>
        <?php
    }
}
