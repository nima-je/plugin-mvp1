<?php
namespace WCSD;

use WC_Product_Simple;
use WC_Product_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Products {
    /** @var Products */
    private static $instance;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'admin_post_wcsd_save_product', array( $this, 'handle_save_product' ) );
        add_action( 'admin_post_wcsd_delete_product', array( $this, 'handle_delete_product' ) );
        add_action( 'admin_post_wcsd_toggle_product', array( $this, 'handle_toggle_status' ) );
    }

    /**
     * Render products section.
     */
    public function render_products_section() {
        $paged   = isset( $_GET['wcsd_paged'] ) ? max( 1, (int) $_GET['wcsd_paged'] ) : 1;
        $limit   = 10;
        $args  = array(
            'limit'    => $limit,
            'page'     => $paged,
            'status'   => array( 'publish', 'draft' ),
            'orderby'  => 'date',
            'order'    => 'DESC',
            'paginate' => true,
        );

        $query    = new WC_Product_Query( $args );
        $products = $query->get_products();
        $total    = $query->get_total();
        $pages    = ceil( $total / $limit );

        $edit_id = isset( $_GET['wcsd_edit'] ) ? absint( $_GET['wcsd_edit'] ) : 0;
        $product = $edit_id ? wc_get_product( $edit_id ) : null;
        ?>
        <div class="wcsd-section">
            <h3><?php esc_html_e( 'افزودن / ویرایش محصول ساده', 'wc-seller-dashboard' ); ?></h3>
            <?php $this->render_product_form( $product ); ?>

            <h3><?php esc_html_e( 'لیست محصولات', 'wc-seller-dashboard' ); ?></h3>
            <div class="wcsd-table-wrapper">
                <table class="wcsd-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'شناسه', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تصویر', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'نام', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'قیمت', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'تخفیف', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'موجودی', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'وضعیت', 'wc-seller-dashboard' ); ?></th>
                            <th><?php esc_html_e( 'اقدامات', 'wc-seller-dashboard' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->get_id() ); ?></td>
                                <td><?php echo $item->get_image( array( 40, 40 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                                <td><?php echo esc_html( $item->get_name() ); ?></td>
                                <td><?php echo wp_kses_post( wc_price( $item->get_regular_price() ) ); ?></td>
                                <td><?php echo $item->get_sale_price() ? wp_kses_post( wc_price( $item->get_sale_price() ) ) : '-'; ?></td>
                                <td><?php echo esc_html( $item->get_stock_quantity() ); ?></td>
                                <td><?php echo esc_html( $item->get_status() ); ?></td>
                                <td>
                                    <a class="button" href="<?php echo esc_url( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_edit' => $item->get_id() ) ) ); ?>"><?php esc_html_e( 'ویرایش', 'wc-seller-dashboard' ); ?></a>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wcsd-inline-form" onsubmit="return confirm('<?php echo esc_js( __( 'آیا از حذف این محصول مطمئن هستید؟', 'wc-seller-dashboard' ) ); ?>');">
                                        <?php wp_nonce_field( 'wcsd_delete_product', 'wcsd_nonce' ); ?>
                                        <input type="hidden" name="action" value="wcsd_delete_product" />
                                        <input type="hidden" name="product_id" value="<?php echo esc_attr( $item->get_id() ); ?>" />
                                        <button type="submit" class="button button-secondary"><?php esc_html_e( 'حذف', 'wc-seller-dashboard' ); ?></button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wcsd-inline-form">
                                        <?php wp_nonce_field( 'wcsd_toggle_product', 'wcsd_nonce' ); ?>
                                        <input type="hidden" name="action" value="wcsd_toggle_product" />
                                        <input type="hidden" name="product_id" value="<?php echo esc_attr( $item->get_id() ); ?>" />
                                        <button type="submit" class="button button-link"><?php echo esc_html( 'publish' === $item->get_status() ? __( 'پیش‌نویس', 'wc-seller-dashboard' ) : __( 'انتشار', 'wc-seller-dashboard' ) ); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ( $pages > 1 ) : ?>
                <div class="wcsd-pagination">
                    <?php for ( $i = 1; $i <= $pages; $i++ ) : ?>
                        <a class="wcsd-page <?php echo $i === $paged ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_paged' => $i ) ) ); ?>"><?php echo esc_html( $i ); ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Product form for add/edit.
     */
    private function render_product_form( $product = null ) {
        $edit_id    = $product ? $product->get_id() : 0;
        $categories = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            )
        );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="wcsd-form">
            <?php wp_nonce_field( 'wcsd_save_product', 'wcsd_nonce' ); ?>
            <input type="hidden" name="action" value="wcsd_save_product" />
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $edit_id ); ?>" />
            <input type="hidden" name="wcsd_tab" value="products" />
            <div class="wcsd-field">
                <label for="wcsd_title"><?php esc_html_e( 'عنوان محصول', 'wc-seller-dashboard' ); ?></label>
                <input type="text" name="wcsd_title" id="wcsd_title" value="<?php echo $product ? esc_attr( $product->get_name() ) : ''; ?>" required />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_desc"><?php esc_html_e( 'توضیحات', 'wc-seller-dashboard' ); ?></label>
                <textarea name="wcsd_desc" id="wcsd_desc" rows="4"><?php echo $product ? esc_textarea( $product->get_description() ) : ''; ?></textarea>
            </div>
            <div class="wcsd-field">
                <label for="wcsd_regular"><?php esc_html_e( 'قیمت عادی', 'wc-seller-dashboard' ); ?></label>
                <input type="number" step="0.01" name="wcsd_regular" id="wcsd_regular" value="<?php echo $product ? esc_attr( $product->get_regular_price() ) : ''; ?>" required />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_sale"><?php esc_html_e( 'قیمت فروش ویژه', 'wc-seller-dashboard' ); ?></label>
                <input type="number" step="0.01" name="wcsd_sale" id="wcsd_sale" value="<?php echo $product ? esc_attr( $product->get_sale_price() ) : ''; ?>" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_stock"><?php esc_html_e( 'موجودی', 'wc-seller-dashboard' ); ?></label>
                <input type="number" name="wcsd_stock" id="wcsd_stock" value="<?php echo $product ? esc_attr( $product->get_stock_quantity() ) : 0; ?>" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_category"><?php esc_html_e( 'دسته‌بندی', 'wc-seller-dashboard' ); ?></label>
                <select name="wcsd_category" id="wcsd_category">
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $product && has_term( $cat->term_id, 'product_cat', $product->get_id() ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="wcsd-field">
                <label for="wcsd_image"><?php esc_html_e( 'تصویر اصلی', 'wc-seller-dashboard' ); ?></label>
                <input type="file" name="wcsd_image" id="wcsd_image" accept="image/*" />
            </div>
            <div class="wcsd-field">
                <label for="wcsd_gallery"><?php esc_html_e( 'گالری تصاویر', 'wc-seller-dashboard' ); ?></label>
                <input type="file" name="wcsd_gallery[]" id="wcsd_gallery" accept="image/*" multiple />
            </div>
            <button type="submit" class="button button-primary"><?php echo $edit_id ? esc_html__( 'ذخیره تغییرات', 'wc-seller-dashboard' ) : esc_html__( 'افزودن محصول', 'wc-seller-dashboard' ); ?></button>
        </form>
        <?php
    }

    /**
     * Handle create/update product.
     */
    public function handle_save_product() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیر مجاز', 'wc-seller-dashboard' ) );
        }
        check_admin_referer( 'wcsd_save_product', 'wcsd_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $title      = isset( $_POST['wcsd_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wcsd_title'] ) ) : '';
        $desc       = isset( $_POST['wcsd_desc'] ) ? wp_kses_post( wp_unslash( $_POST['wcsd_desc'] ) ) : '';
        $regular    = isset( $_POST['wcsd_regular'] ) ? wc_format_decimal( wp_unslash( $_POST['wcsd_regular'] ) ) : '';
        $sale       = isset( $_POST['wcsd_sale'] ) ? wc_format_decimal( wp_unslash( $_POST['wcsd_sale'] ) ) : '';
        $stock      = isset( $_POST['wcsd_stock'] ) ? intval( wp_unslash( $_POST['wcsd_stock'] ) ) : 0;
        $category   = isset( $_POST['wcsd_category'] ) ? absint( $_POST['wcsd_category'] ) : 0;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $post_data = array(
            'post_title'   => $title,
            'post_content' => $desc,
            'post_status'  => 'publish',
            'post_type'    => 'product',
        );

        if ( $product_id ) {
            $post_data['ID'] = $product_id;
            wp_update_post( $post_data );
            $product = wc_get_product( $product_id );
        } else {
            $product_id = wp_insert_post( $post_data );
            $product    = new WC_Product_Simple( $product_id );
        }

        wp_set_object_terms( $product_id, 'simple', 'product_type' );

        if ( ! $product ) {
            wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_msg' => rawurlencode( __( 'خطا در ذخیره محصول', 'wc-seller-dashboard' ) ) ), wp_get_referer() ) );
            exit;
        }

        $product->set_price( $regular );
        $product->set_regular_price( $regular );
        $product->set_sale_price( $sale );
        $product->set_manage_stock( true );
        $product->set_stock_quantity( $stock );
        $product->set_stock_status( $stock > 0 ? 'instock' : 'outofstock' );
        $product->save();

        if ( $category ) {
            wp_set_object_terms( $product_id, array( $category ), 'product_cat' );
        }

        // Handle main image.
        if ( ! empty( $_FILES['wcsd_image']['name'] ) ) {
            $attachment_id = media_handle_upload( 'wcsd_image', $product_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                set_post_thumbnail( $product_id, $attachment_id );
            }
        }

        // Gallery images.
        if ( ! empty( $_FILES['wcsd_gallery']['name'][0] ) ) {
            $gallery_ids = array();
            foreach ( $_FILES['wcsd_gallery']['name'] as $index => $name ) {
                if ( ! $name ) {
                    continue;
                }
                $file = array(
                    'name'     => sanitize_file_name( $name ),
                    'type'     => $_FILES['wcsd_gallery']['type'][ $index ],
                    'tmp_name' => $_FILES['wcsd_gallery']['tmp_name'][ $index ],
                    'error'    => $_FILES['wcsd_gallery']['error'][ $index ],
                    'size'     => $_FILES['wcsd_gallery']['size'][ $index ],
                );
                $_FILES['wcsd_single_gallery'] = $file;
                $attachment_id                = media_handle_upload( 'wcsd_single_gallery', $product_id );
                if ( ! is_wp_error( $attachment_id ) ) {
                    $gallery_ids[] = $attachment_id;
                }
            }
            if ( ! empty( $gallery_ids ) ) {
                $product->set_gallery_image_ids( $gallery_ids );
                $product->save();
            }
        }

        $message = $product_id ? __( 'ویرایش محصول با موفقیت انجام شد', 'wc-seller-dashboard' ) : __( 'افزودن محصول با موفقیت انجام شد', 'wc-seller-dashboard' );
        wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_msg' => rawurlencode( $message ) ), wp_get_referer() ) );
        exit;
    }

    public function handle_delete_product() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیر مجاز', 'wc-seller-dashboard' ) );
        }
        check_admin_referer( 'wcsd_delete_product', 'wcsd_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( $product_id ) {
            wp_trash_post( $product_id );
        }

        wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_msg' => rawurlencode( __( 'محصول حذف شد', 'wc-seller-dashboard' ) ) ), wp_get_referer() ) );
        exit;
    }

    public function handle_toggle_status() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'دسترسی غیر مجاز', 'wc-seller-dashboard' ) );
        }
        check_admin_referer( 'wcsd_toggle_product', 'wcsd_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( $product_id ) {
            $status = get_post_status( $product_id );
            $new    = 'publish' === $status ? 'draft' : 'publish';
            wp_update_post(
                array(
                    'ID'          => $product_id,
                    'post_status' => $new,
                )
            );
        }

        wp_safe_redirect( add_query_arg( array( 'wcsd_tab' => 'products', 'wcsd_msg' => rawurlencode( __( 'وضعیت محصول بروزرسانی شد', 'wc-seller-dashboard' ) ) ), wp_get_referer() ) );
        exit;
    }
}
