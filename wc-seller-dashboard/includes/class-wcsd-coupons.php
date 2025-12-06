<?php
namespace WCSD;

use WP_Query;
use WC_Coupon;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Coupons {
    /** @var Coupons */
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'admin_post_wcsd_save_coupon', array( $this, 'handle_save_coupon' ) );
        add_action( 'admin_post_wcsd_delete_coupon', array( $this, 'handle_delete_coupon' ) );
    }

    public function render_coupons_section() {
        $paged   = isset( $_GET['wcsd_cpaged'] ) ? max( 1, (int) $_GET['wcsd_cpaged'] ) : 1;
        $limit   = 10;
        $args    = array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'post_status'    => 'publish',
        );
        $query   = new WP_Query( $args );
        $coupons = $query->posts;

        $edit_id = isset( $_GET['wcsd_edit_coupon'] ) ? absint( $_GET['wcsd_edit_coupon'] ) : 0;
        $coupon  = $edit_id ? new WC_Coupon( $edit_id ) : null;
        ?>
        <div class="wcsd-section">
            <h3><?php esc_html_e( 'افزودن / ویرایش کد تخفیف', 'wc-seller-dashboard' ); ?></h3>
            <?php $this->render_coupon_form( $coupon ); ?>

            <h3><?php esc_html_e( 'لیست کدهای تخفیف', 'wc-seller-dashboard' ); ?></h3>
            <div class="wcsd-table-wrapper">
                <table class="wcsd-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'کد', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'نوع', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'مبلغ', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تاریخ انقضا', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تعداد استفاده', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'محدودیت', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'وضعیت', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'اقدامات', 'wc-seller-dashboard' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $coupons as $post ) :
                            $c = new WC_Coupon( $post->ID );
                            $exp = $c->get_date_expires();
                            ?>
                            <tr>
                                <td><?php echo esc_html( $c->get_code() ); ?></td>
                                <td><?php echo esc_html( $c->get_discount_type() ); ?></td>
                                <td><?php echo esc_html( wc_price( $c->get_amount() ) ); ?></td>
                                <td><?php echo $exp ? esc_html( $exp->date_i18n( 'Y-m-d' ) ) : '-'; ?></td>
                                <td><?php echo esc_html( $c->get_usage_count() ); ?></td>
                                <td><?php echo $c->get_usage_limit() ? esc_html( $c->get_usage_limit() ) : '-'; ?></td>
                                <td><?php echo $exp && $exp->getTimestamp() < time() ? esc_html__( 'منقضی', 'wc-seller-dashboard' ) : esc_html__( 'فعال', 'wc-seller-dashboard' ); ?></td>
                                <td>
                                    <a class="button" href="<?php echo esc_url( add_query_arg( array( 'wcsd_tab' => 'coupons', 'wcsd_edit_coupon' => $post->ID ) ) ); ?>"><?php esc_html_e( 'ویرایش', 'wc-seller-dashboard' ); ?></a>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wcsd-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'حذف کد تخفیف؟', 'wc-seller-dashboard' ) ); ?>');">
                                        <?php wp_nonce_field( 'wcsd_delete_coupon', 'wcsd_nonce' ); ?>
                                        <input type="hidden" name="action" value="wcsd_delete_coupon" />
                                        <input type="hidden" name="coupon_id" value="<?php echo esc_attr( $post->ID ); ?>" />
                                        <button type="submit" class="button button-secondary"><?php esc_html_e( 'حذف', 'wc-seller-dashboard' ); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="wcsd-pagination">
                    <?php for ( $i = 1; $i <= $query->max_num_pages; $i++ ) : ?>
                        <a class="wcsd-page <?php echo $i === $paged ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'wcsd_tab' => 'coupons', 'wcsd_cpaged' => $i ) ) ); ?>"><?php echo esc_html( $i ); ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_coupon_form( $coupon = null ) {
        $code         = $coupon ? $coupon->get_code() : '';
        $discount     = $coupon ? $coupon->get_discount_type() : 'percent';
        $amount       = $coupon ? $coupon->get_amount() : '';
        $expiry       = $coupon && $coupon->get_date_expires() ? $coupon->get_date_expires()->date_i18n( 'Y-m-d' ) : '';
        $min          = $coupon ? $coupon->get_minimum_amount() : '';
        $max          = $coupon ? $coupon->get_maximum_amount() : '';
        $usage_limit  = $coupon ? $coupon->get_usage_limit() : '';
        $usage_user   = $coupon ? $coupon->get_usage_limit_per_user() : '';
        $free_shipping = $coupon ? $coupon->get_free_shipping() : false;
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wcsd-form">
            <?php wp_nonce_field( 'wcsd_save_coupon', 'wcsd_nonce' ); ?>
            <input type="hidden" name="action" value="wcsd_save_coupon" />
            <input type="hidden" name="coupon_id" value="<?php echo $coupon ? esc_attr( $coupon->get_id() ) : 0; ?>" />
            <div class="wcsd-field">
                <label for="wcsd_coupon_code"><?php esc_html_e( 'کد تخفیف', 'wc-seller-dashboard' ); ?></label>
                <input type="text" name="wcsd_coupon_code" id="wcsd_coupon_code" value="<?php echo esc_attr( $code ); ?>" required />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_coupon_type"><?php esc_html_e( 'نوع تخفیف', 'wc-seller-dashboard' ); ?></label>
                <select name="wcsd_coupon_type" id="wcsd_coupon_type">
                    <option value="percent" <?php selected( 'percent', $discount ); ?>><?php esc_html_e( 'درصدی', 'wc-seller-dashboard' ); ?></option>
                    <option value="fixed_cart" <?php selected( 'fixed_cart', $discount ); ?>><?php esc_html_e( 'مبلغ ثابت سبد', 'wc-seller-dashboard' ); ?></option>
                </select>
            </div>
            <div class="wcsd-field">
                <label for="wcsd_coupon_amount"><?php esc_html_e( 'مبلغ تخفیف', 'wc-seller-dashboard' ); ?></label>
                <input type="number" step="0.01" name="wcsd_coupon_amount" id="wcsd_coupon_amount" value="<?php echo esc_attr( $amount ); ?>" required />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_coupon_expiry"><?php esc_html_e( 'تاریخ انقضا (جلالی)', 'wc-seller-dashboard' ); ?></label>
                <input type="text" name="wcsd_coupon_expiry" id="wcsd_coupon_expiry" value="<?php echo esc_attr( $expiry ); ?>" placeholder="1403-02-01" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_min_spend"><?php esc_html_e( 'حداقل خرید', 'wc-seller-dashboard' ); ?></label>
                <input type="number" step="0.01" name="wcsd_min_spend" id="wcsd_min_spend" value="<?php echo esc_attr( $min ); ?>" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_max_spend"><?php esc_html_e( 'حداکثر خرید', 'wc-seller-dashboard' ); ?></label>
                <input type="number" step="0.01" name="wcsd_max_spend" id="wcsd_max_spend" value="<?php echo esc_attr( $max ); ?>" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_usage_limit"><?php esc_html_e( 'محدودیت استفاده کلی', 'wc-seller-dashboard' ); ?></label>
                <input type="number" name="wcsd_usage_limit" id="wcsd_usage_limit" value="<?php echo esc_attr( $usage_limit ); ?>" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_usage_user"><?php esc_html_e( 'محدودیت استفاده هر کاربر', 'wc-seller-dashboard' ); ?></label>
                <input type="number" name="wcsd_usage_user" id="wcsd_usage_user" value="<?php echo esc_attr( $usage_user ); ?>" />
            </div>
            <div class="wcsd-field">
                <label>
                    <input type="checkbox" name="wcsd_free_shipping" value="1" <?php checked( $free_shipping ); ?> />
                    <?php esc_html_e( 'ارسال رایگان', 'wc-seller-dashboard' ); ?>
                </label>
            </div>
            <button type="submit" class="button button-primary"><?php echo $coupon ? esc_html__( 'ذخیره تغییرات', 'wc-seller-dashboard' ) : esc_html__( 'افزودن کد تخفیف', 'wc-seller-dashboard' ); ?></button>
        </form>
        <?php
    }

    public function handle_save_coupon() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیر مجاز', 'wc-seller-dashboard' ) );
        }
        check_admin_referer( 'wcsd_save_coupon', 'wcsd_nonce' );

        $coupon_id = isset( $_POST['coupon_id'] ) ? absint( $_POST['coupon_id'] ) : 0;
        $code      = isset( $_POST['wcsd_coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['wcsd_coupon_code'] ) ) : '';
        $type      = isset( $_POST['wcsd_coupon_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wcsd_coupon_type'] ) ) : 'percent';
        $amount    = isset( $_POST['wcsd_coupon_amount'] ) ? wc_format_decimal( wp_unslash( $_POST['wcsd_coupon_amount'] ) ) : '0';
        $expiry    = isset( $_POST['wcsd_coupon_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['wcsd_coupon_expiry'] ) ) : '';
        $min       = isset( $_POST['wcsd_min_spend'] ) ? wc_format_decimal( wp_unslash( $_POST['wcsd_min_spend'] ) ) : '';
        $max       = isset( $_POST['wcsd_max_spend'] ) ? wc_format_decimal( wp_unslash( $_POST['wcsd_max_spend'] ) ) : '';
        $usage     = isset( $_POST['wcsd_usage_limit'] ) ? absint( $_POST['wcsd_usage_limit'] ) : 0;
        $usage_user = isset( $_POST['wcsd_usage_user'] ) ? absint( $_POST['wcsd_usage_user'] ) : 0;
        $free_ship  = isset( $_POST['wcsd_free_shipping'] ) ? true : false;

        $post_id = $coupon_id;
        if ( $coupon_id ) {
            $post_id = $coupon_id;
        } else {
            $post_id = wp_insert_post(
                array(
                    'post_title'  => $code,
                    'post_status' => 'publish',
                    'post_type'   => 'shop_coupon',
                )
            );
        }

        wp_update_post(
            array(
                'ID'         => $post_id,
                'post_title' => $code,
            )
        );

        $coupon = new WC_Coupon( $post_id );
        $coupon->set_code( $code );
        $coupon->set_discount_type( $type );
        $coupon->set_amount( $amount );
        $coupon->set_free_shipping( $free_ship );
        $coupon->set_minimum_amount( $min );
        $coupon->set_maximum_amount( $max );
        if ( $usage ) {
            $coupon->set_usage_limit( $usage );
        }
        if ( $usage_user ) {
            $coupon->set_usage_limit_per_user( $usage_user );
        }

        if ( $expiry ) {
            $parts = explode( '-', $expiry );
            if ( 3 === count( $parts ) ) {
                $g = Stats::get_instance()->jalali_to_gregorian( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
                $coupon->set_date_expires( gmmktime( 23, 59, 59, $g[1], $g[2], $g[0] ) );
            }
        } else {
            $coupon->set_date_expires( null );
        }

        $coupon->save();

        $message = $coupon_id ? __( 'کد تخفیف بروزرسانی شد', 'wc-seller-dashboard' ) : __( 'کد تخفیف ایجاد شد', 'wc-seller-dashboard' );
        wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'coupons', 'wcsd_msg' => rawurlencode( $message ) ), wp_get_referer() ) );
        exit;
    }

    public function handle_delete_coupon() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیر مجاز', 'wc-seller-dashboard' ) );
        }
        check_admin_referer( 'wcsd_delete_coupon', 'wcsd_nonce' );

        $coupon_id = isset( $_POST['coupon_id'] ) ? absint( $_POST['coupon_id'] ) : 0;
        if ( $coupon_id ) {
            wp_trash_post( $coupon_id );
        }

        wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'coupons', 'wcsd_msg' => rawurlencode( __( 'کد تخفیف حذف شد', 'wc-seller-dashboard' ) ) ), wp_get_referer() ) );
        exit;
    }
}
