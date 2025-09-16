<?php
/**
 * Functions related to the frontend display and interactions.
 *
 * @package My_Fabric_Calculator
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * تابع کمکی برای بررسی شرط نمایش ماشین حساب بر اساس تنظیمات.
 *
 * @param int $product_id شناسه محصول.
 * @return bool              True اگر ماشین حساب باید نمایش داده شود، در غیر این صورت False.
 */
function mfc_should_display_calculator( $product_id ) {
    $product = wc_get_product( $product_id );

    // شرط ۱: محصول باید معتبر و موجود در انبار باشد
    if ( ! $product || ! $product->is_in_stock() ) {
        return false;
    }

    // شرط ۲: بررسی تنظیمات نمایش
    $options = get_option( 'mfc_options' );
    $condition = isset( $options['display_condition'] ) ? $options['display_condition'] : 'per_product';

    if ( 'all_products' === $condition ) {
        return true;
    }

    if ( 'in_categories' === $condition ) {
        $selected_cats = isset( $options['display_categories'] ) ? (array) $options['display_categories'] : array();
        if ( empty( $selected_cats ) ) {
            return false;
        }
        return has_term( $selected_cats, 'product_cat', $product_id );
    }

    // حالت پیش‌فرض: بر اساس تیک هر محصول
    return 'yes' === get_post_meta( $product_id, '_mfc_is_fabric_product', true );
}

/**
 * افزودن فایل‌های CSS و JavaScript به صورت بهینه.
 */
add_action( 'wp_enqueue_scripts', 'mfc_enqueue_frontend_assets' );
function mfc_enqueue_frontend_assets() {
    if ( ! is_product() ) {
        return;
    }

    global $product;
    if ( ! is_a( $product, 'WC_Product' ) || ! mfc_should_display_calculator( $product->get_id() ) ) {
        return;
    }

    wp_enqueue_style( 'mfc-frontend-style', MFC_PLUGIN_URL . 'assets/css/frontend-style.css', array(), MFC_VERSION );
    wp_enqueue_script( 'mfc-frontend-script', MFC_PLUGIN_URL . 'assets/js/frontend-script.js', array( 'jquery' ), MFC_VERSION, true );
    wp_localize_script(
        'mfc-frontend-script',
        'mfc_params',
        array(
            'currency_symbol'    => get_woocommerce_currency_symbol(),
            'error_insufficient' => __( 'موجودی کافی نیست. حداکثر متراژ قابل سفارش %m متر و %c سانتی‌متر است.', 'my-fabric-calculator' ),
            'error_minimum'      => __( 'لطفاً متراژ مورد نظر را انتخاب کنید.', 'my-fabric-calculator' ),
        )
    );
}

/**
 * نمایش فرم ماشین حساب در صفحه محصول.
 */
add_action( 'woocommerce_before_add_to_cart_button', 'mfc_display_calculator_form', 10 );
function mfc_display_calculator_form() {
    global $product;
    if ( mfc_should_display_calculator( $product->get_id() ) ) {
        $template_path = MFC_PLUGIN_PATH . 'templates/calculator-form-template.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }
}

/**
 * تابع کمکی برای تبدیل سانتی‌متر به رشته متنی خوانا.
 *
 * @param int $total_cm کل سانتی‌متر.
 * @return string          رشته فرمت شده (مثال: ۲ متر و ۵۰ سانتی‌متر).
 */
function mfc_get_formatted_length_string( $total_cm ) {
    $total_cm    = absint( $total_cm );
    $meters      = floor( $total_cm / 100 );
    $centimeters = $total_cm % 100;
    $display_value = '';

    if ( $meters > 0 ) {
        $display_value .= sprintf( _n( '%s متر', '%s متر', $meters, 'my-fabric-calculator' ), number_format_i18n( $meters ) );
    }
    if ( $centimeters > 0 ) {
        $display_value .= ' ' . sprintf( _n( '%s سانتی‌متر', '%s سانتی‌متر', $centimeters, 'my-fabric-calculator' ), number_format_i18n( $centimeters ) );
    }
    return trim( $display_value );
}

/**
 * نمایش متراژ انتخاب شده در صفحه سبد خرید و پرداخت.
 */
add_filter( 'woocommerce_get_item_data', 'mfc_display_custom_length_in_cart', 10, 2 );
function mfc_display_custom_length_in_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['mfc_custom_length_cm'] ) ) {
        $item_data[] = array(
            'key'     => __( 'متراژ انتخابی', 'my-fabric-calculator' ),
            'value'   => mfc_get_formatted_length_string( $cart_item['mfc_custom_length_cm'] ),
        );
    }
    return $item_data;
}

/**
 * افزودن متادیتای سفارشی به آیتم سفارش پس از پرداخت.
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'mfc_add_custom_data_to_order_items', 10, 4 );
function mfc_add_custom_data_to_order_items( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['mfc_custom_length_cm'] ) ) {
        $formatted_length = mfc_get_formatted_length_string( $values['mfc_custom_length_cm'] );
        if ( ! empty( $formatted_length ) ) {
            $item->add_meta_data( __( 'متراژ انتخابی', 'my-fabric-calculator' ), $formatted_length );
        }
    }
}

/**
 * افزودن پسوند "/ متر" به قیمت محصولات متراژی با اولویت بالا.
 */
add_filter( 'woocommerce_get_price_html', 'mfc_add_price_suffix_for_fabric', 9999, 2 );
function mfc_add_price_suffix_for_fabric( $price_html, $product ) {
    if ( is_admin() || ! is_a( $product, 'WC_Product' ) ) {
        return $price_html;
    }
    if ( '' === $product->get_price() ) {
        return $price_html;
    }

    if ( mfc_should_display_calculator( $product->get_id() ) ) {
        $suffix = ' <span class="mfc-price-suffix">/ ' . esc_html__( 'متر', 'my-fabric-calculator' ) . '</span>';
        if ( strpos( $price_html, 'mfc-price-suffix' ) === false ) {
            return $price_html . $suffix;
        }
    }
    return $price_html;
}