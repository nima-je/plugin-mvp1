<?php
namespace WCSD;

use WP_Query;
use WC_Order_Query;
use WC_Coupon;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Stats {
    /** @var Stats */
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'template_redirect', array( $this, 'track_product_views' ) );
    }

    /**
     * Track single product views and referrers.
     */
    public function track_product_views() {
        if ( ! is_product() ) {
            return;
        }

        global $post, $wpdb;
        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
        $domain   = $referrer ? wp_parse_url( $referrer, PHP_URL_HOST ) : __( 'مستقیم', 'wc-seller-dashboard' );
        $domain   = $domain ? $domain : __( 'مستقیم', 'wc-seller-dashboard' );

        $table = $wpdb->prefix . 'wcsd_product_views';
        $wpdb->insert(
            $table,
            array(
                'product_id' => (int) $post->ID,
                'referrer'   => $domain,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s' )
        );
    }

    /**
     * Render stats section.
     */
    public function render_stats_section() {
        $today_jalali = $this->gregorian_to_jalali_string( current_time( 'Y-m-d' ) );
        $start_input  = isset( $_GET['wcsd_start'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsd_start'] ) ) : $today_jalali;
        $end_input    = isset( $_GET['wcsd_end'] ) ? sanitize_text_field( wp_unslash( $_GET['wcsd_end'] ) ) : $today_jalali;

        $start_greg = $this->parse_jalali_date( $start_input );
        $end_greg   = $this->parse_jalali_date( $end_input );

        $stats = $this->calculate_metrics( $start_greg . ' 00:00:00', $end_greg . ' 23:59:59' );
        ?>
        <div class="wcsd-section">
            <form method="get" class="wcsd-date-form">
                <?php
                foreach ( array( 'post_type', 'page_id', 'p' ) as $key ) {
                    if ( isset( $_GET[ $key ] ) ) {
                        printf( '<input type="hidden" name="%1$s" value="%2$s" />', esc_attr( $key ), esc_attr( wp_unslash( $_GET[ $key ] ) ) );
                    }
                }
                ?>
                <label for="wcsd_start"><?php esc_html_e( 'تاریخ شروع (جلالی)', 'wc-seller-dashboard' ); ?></label>
                <input type="text" name="wcsd_start" id="wcsd_start" value="<?php echo esc_attr( $start_input ); ?>" placeholder="1403-01-01" />
                <label for="wcsd_end"><?php esc_html_e( 'تاریخ پایان (جلالی)', 'wc-seller-dashboard' ); ?></label>
                <input type="text" name="wcsd_end" id="wcsd_end" value="<?php echo esc_attr( $end_input ); ?>" placeholder="1403-01-15" />
                <input type="hidden" name="wcsd_tab" value="stats" />
                <button type="submit" class="button">&rarr; <?php esc_html_e( 'اعمال فیلتر', 'wc-seller-dashboard' ); ?></button>
            </form>

            <div class="wcsd-cards">
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'مبالغ ارسال', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value">
                        <?php echo wc_price( $stats['shipping_total'] ); ?>
                    </div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'مجموع فروش', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo wc_price( $stats['total_sales'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'سفارش‌های در حال پردازش', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['processing_orders'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'سفارش‌های تکمیل شده', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['completed_orders'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'سفارش‌های لغو شده', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['cancelled_orders'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'مجموع سفارشات (پردازش+تکمیل)', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['total_orders'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'مشتریان جدید', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['new_customers'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'مشتریان قدیمی', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['old_customers'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'سفارش‌های مشتریان جدید', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['orders_new_customers'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'سفارش‌های مشتریان قدیمی', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['orders_old_customers'] ); ?></div>
                </div>
                <div class="wcsd-card">
                    <div class="wcsd-card-title"><?php esc_html_e( 'کدهای تخفیف فعال', 'wc-seller-dashboard' ); ?></div>
                    <div class="wcsd-card-value"><?php echo esc_html( $stats['active_coupons'] ); ?></div>
                </div>
            </div>

            <h3><?php esc_html_e( 'بازدید محصولات', 'wc-seller-dashboard' ); ?></h3>
            <div class="wcsd-table-wrapper">
                <table class="wcsd-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'محصول', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تعداد بازدید', 'wc-seller-dashboard' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats['product_views'] as $product_id => $count ) : ?>
                            <tr>
                                <td><?php echo esc_html( get_the_title( $product_id ) ); ?></td>
                                <td><?php echo esc_html( $count ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3><?php esc_html_e( 'منابع بازدید', 'wc-seller-dashboard' ); ?></h3>
            <div class="wcsd-table-wrapper">
                <table class="wcsd-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'دامنه', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تعداد', 'wc-seller-dashboard' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats['referrers'] as $referrer => $count ) : ?>
                            <tr>
                                <td><?php echo esc_html( $referrer ); ?></td>
                                <td><?php echo esc_html( $count ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3><?php esc_html_e( 'آخرین سفارش‌ها', 'wc-seller-dashboard' ); ?></h3>
            <div class="wcsd-table-wrapper">
                <table class="wcsd-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'شناسه', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'مشتری', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'وضعیت', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'مبلغ', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تاریخ', 'wc-seller-dashboard' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats['latest_orders'] as $order ) : ?>
                            <tr>
                                <td>#<?php echo esc_html( $order->get_id() ); ?></td>
                                <td><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
                                <td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
                                <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                                <td><?php echo esc_html( $order->get_date_created()->date_i18n( 'Y-m-d H:i' ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Calculate metrics.
     *
     * @param string $start Start datetime.
     * @param string $end   End datetime.
     *
     * @return array
     */
    public function calculate_metrics( $start, $end ) {
        $orders_query = new WC_Order_Query(
            array(
                'limit'        => -1,
                'type'         => 'shop_order',
                'status'       => array( 'processing', 'completed' ),
                'date_created' => $this->get_range_query( $start, $end ),
                'return'       => 'objects',
            )
        );
        $orders        = $orders_query->get_orders();

        $cancelled_query = new WC_Order_Query(
            array(
                'limit'        => -1,
                'status'       => array( 'cancelled' ),
                'date_created' => $this->get_range_query( $start, $end ),
                'return'       => 'ids',
            )
        );
        $cancelled_orders_ids = $cancelled_query->get_orders();

        $shipping_total = 0;
        $sales_total    = 0;
        $new_customers  = array();
        $old_customers  = array();
        $orders_new     = 0;
        $orders_old     = 0;

        foreach ( $orders as $order ) {
            $sales_total    += (float) $order->get_total();
            $shipping_total += (float) $order->get_shipping_total();

            $customer_id = $order->get_user_id();
            if ( $customer_id ) {
                $total_orders = wc_get_customer_order_count( $customer_id );
                if ( 1 === (int) $total_orders ) {
                    $new_customers[ $customer_id ] = true;
                    $orders_new++;
                } elseif ( $total_orders > 1 ) {
                    $old_customers[ $customer_id ] = true;
                    $orders_old++;
                }
            }
        }

        $views_data = $this->get_product_views( $start, $end );

        return array(
            'shipping_total'       => $shipping_total,
            'total_sales'          => $sales_total,
            'processing_orders'    => $this->count_orders_by_status( 'processing', $start, $end ),
            'completed_orders'     => $this->count_orders_by_status( 'completed', $start, $end ),
            'cancelled_orders'     => count( $cancelled_orders_ids ),
            'total_orders'         => count( $orders ),
            'new_customers'        => count( $new_customers ),
            'old_customers'        => count( $old_customers ),
            'orders_new_customers' => $orders_new,
            'orders_old_customers' => $orders_old,
            'product_views'        => $views_data['products'],
            'referrers'            => $views_data['referrers'],
            'active_coupons'       => $this->count_active_coupons( $start, $end ),
            'latest_orders'        => $this->get_latest_orders( $start, $end ),
        );
    }

    private function count_orders_by_status( $status, $start, $end ) {
        $query = new WC_Order_Query(
            array(
                'limit'        => -1,
                'status'       => array( $status ),
                'date_created' => $this->get_range_query( $start, $end ),
                'return'       => 'ids',
            )
        );
        $ids = $query->get_orders();
        return count( $ids );
    }

    private function get_latest_orders( $start, $end ) {
        $query = new WC_Order_Query(
            array(
                'limit'        => 5,
                'status'       => array( 'processing', 'completed', 'cancelled' ),
                'date_created' => $this->get_range_query( $start, $end ),
                'orderby'      => 'date',
                'order'        => 'DESC',
                'return'       => 'objects',
            )
        );
        return $query->get_orders();
    }

    private function get_range_query( $start, $end ) {
        return array(
            'after'     => gmdate( 'Y-m-d H:i:s', strtotime( $start ) ),
            'before'    => gmdate( 'Y-m-d H:i:s', strtotime( $end ) ),
            'inclusive' => true,
        );
    }

    private function get_product_views( $start, $end ) {
        global $wpdb;
        $table = $wpdb->prefix . 'wcsd_product_views';

        $products  = array();
        $referrers = array();

        $prepared = $wpdb->prepare( "SELECT product_id, COUNT(*) as views FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY product_id", $start, $end );
        $rows     = $wpdb->get_results( $prepared );
        if ( $rows ) {
            foreach ( $rows as $row ) {
                $products[ $row->product_id ] = (int) $row->views;
            }
        }

        $ref_query = $wpdb->prepare( "SELECT referrer, COUNT(*) as views FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY referrer", $start, $end );
        $ref_rows  = $wpdb->get_results( $ref_query );
        if ( $ref_rows ) {
            foreach ( $ref_rows as $row ) {
                $referrers[ $row->referrer ] = (int) $row->views;
            }
        }

        return array(
            'products'  => $products,
            'referrers' => $referrers,
        );
    }

    private function count_active_coupons( $start, $end ) {
        $args = array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );
        $query  = new WP_Query( $args );
        $count  = 0;
        $startt = strtotime( $start );
        $endt   = strtotime( $end );

        foreach ( $query->posts as $post ) {
            $coupon = new WC_Coupon( $post->ID );
            $exp    = $coupon->get_date_expires();

            if ( $exp && $exp->getTimestamp() < $startt ) {
                continue;
            }

            $usage_limit = $coupon->get_usage_limit();
            $usage_count = $coupon->get_usage_count();
            if ( $usage_limit && $usage_count >= $usage_limit ) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Parse Jalali date string (Y-m-d) to Gregorian Y-m-d.
     */
    private function parse_jalali_date( $date ) {
        $parts = explode( '-', $date );
        if ( 3 !== count( $parts ) ) {
            return gmdate( 'Y-m-d' );
        }

        $jy = (int) $parts[0];
        $jm = (int) $parts[1];
        $jd = (int) $parts[2];

        $greg = $this->jalali_to_gregorian( $jy, $jm, $jd );
        return sprintf( '%04d-%02d-%02d', $greg[0], $greg[1], $greg[2] );
    }

    /**
     * Convert Jalali date to Gregorian.
     */
    public function jalali_to_gregorian( $jy, $jm, $jd ) {
        $jy += 1595;
        $days = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) ) * 8 + (int) ( ( $jy % 33 + 3 ) / 4 ) + $jd + ( $jm < 7 ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 + 186 ) );

        $gy = 400 * (int) ( $days / 146097 );
        $days %= 146097;

        if ( $days > 36524 ) {
            $gy += 100 * (int) ( --$days / 36524 );
            $days %= 36524;
            if ( $days >= 365 ) {
                $days++;
            }
        }

        $gy += 4 * (int) ( $days / 1461 );
        $days %= 1461;
        if ( $days > 365 ) {
            $gy += (int) ( ( $days - 1 ) / 365 );
            $days = ( $days - 1 ) % 365;
        }

        $gd = $days + 1;
        $sal_a = array( 0, 31, ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
        for ( $gm = 0; $gm < 13; $gm++ ) {
            $v = $sal_a[ $gm ];
            if ( $gd <= $v ) {
                break;
            }
            $gd -= $v;
        }

        return array( $gy, $gm, $gd );
    }

    /**
     * Convert Gregorian Y-m-d to Jalali string.
     */
    public function gregorian_to_jalali_string( $date ) {
        $parts = explode( '-', $date );
        if ( 3 !== count( $parts ) ) {
            return $date;
        }
        $g_y = (int) $parts[0];
        $g_m = (int) $parts[1];
        $g_d = (int) $parts[2];
        $jy  = $this->gregorian_to_jalali( $g_y, $g_m, $g_d );
        return sprintf( '%04d-%02d-%02d', $jy[0], $jy[1], $jy[2] );
    }

    /**
     * Gregorian to Jalali conversion.
     */
    public function gregorian_to_jalali( $gy, $gm, $gd ) {
        $g_d_m = array( 0, 31, ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
        $gy2   = $gy - 1600;
        $gm2   = $gm - 1;
        $gd2   = $gd - 1;

        $g_day_no = 365 * $gy2 + (int) ( $gy2 + 3 ) / 4 - (int) ( $gy2 + 99 ) / 100 + (int) ( $gy2 + 399 ) / 400;

        for ( $i = 0; $i < $gm2; ++$i ) {
            $g_day_no += $g_d_m[ $i + 1 ];
        }
        $g_day_no += $gd2;

        $j_day_no = $g_day_no - 79;
        $j_np     = (int) ( $j_day_no / 12053 );
        $j_day_no %= 12053;

        $jy = 979 + ( 33 * $j_np ) + (int) ( $j_day_no / 12053 * 4 );
        $j_day_no %= 1461;

        if ( $j_day_no >= 366 ) {
            $jy += (int) ( ( $j_day_no - 1 ) / 365 );
            $j_day_no = ( $j_day_no - 1 ) % 365;
        }

        for ( $i = 0; $i < 11 && $j_day_no >= ( $i < 6 ? 31 : 30 ); ++$i ) {
            $j_day_no -= ( $i < 6 ? 31 : 30 );
        }

        $jm = $i + 1;
        $jd = $j_day_no + 1;

        return array( $jy, $jm, $jd );
    }
}
