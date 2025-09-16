<?php
// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * اعتبارسنجی مقادیر متر و سانتی‌متر قبل از افزودن به سبد خرید
 */
add_filter( 'woocommerce_add_to_cart_validation', 'mfc_validate_meter_cm_selection', 10, 3 );
function mfc_validate_meter_cm_selection( $passed, $product_id, $quantity ) {
    // بررسی Nonce برای امنیت
    if ( ! isset( $_POST['mfc_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mfc_nonce_field'] ) ), 'mfc_add_to_cart_action' ) ) {
        wc_add_notice( __( 'خطای امنیتی. لطفاً صفحه را رفرش کرده و دوباره تلاش کنید.', 'my-fabric-calculator' ), 'error' );
        return false;
    }

    if ( isset( $_POST['mfc_meters'] ) ) {
        $meters      = absint( $_POST['mfc_meters'] );
        $centimeters = absint( $_POST['mfc_centimeters'] );
        $total_cm    = ( $meters * 100 ) + $centimeters;

        if ( $total_cm < 1 ) { // حداقل 1 سانتی متر
            wc_add_notice( __( 'لطفاً یک مقدار معتبر انتخاب کنید.', 'my-fabric-calculator' ), 'error' );
            $passed = false;
        }

        // خواندن موجودی با در نظر گرفتن تنظیمات پیش‌فرض
        $inventory_cm_meta = get_post_meta( $product_id, '_mfc_inventory_cm', true );
        $mfc_options = get_option('mfc_options');
        $default_inventory = isset($mfc_options['default_inventory']) ? absint($mfc_options['default_inventory']) : 20;
        $inventory_cm = ($inventory_cm_meta === '') ? ($default_inventory * 100) : absint($inventory_cm_meta);


        if ( $total_cm > $inventory_cm ) {
            $inventory_m      = floor( $inventory_cm / 100 );
            $inventory_cm_rem = $inventory_cm % 100;
            $message          = sprintf( esc_html__( 'مقدار درخواستی موجود نیست. حداکثر متراژ قابل سفارش %d متر و %d سانتی‌متر است.', 'my-fabric-calculator' ), $inventory_m, $inventory_cm_rem );
            wc_add_notice( $message, 'error' );
            $passed = false;
        }
    }
    return $passed;
}

/**
 * ذخیره متراژ سفارشی به عنوان اطلاعات آیتم سبد خرید
 */
add_filter( 'woocommerce_add_cart_item_data', 'mfc_add_custom_length_to_cart_item', 10, 2 );
function mfc_add_custom_length_to_cart_item( $cart_item_data, $product_id ) {
    if ( isset( $_POST['mfc_meters'] ) && isset( $_POST['mfc_centimeters'] ) ) {
        $total_cm = ( absint( $_POST['mfc_meters'] ) * 100 ) + absint( $_POST['mfc_centimeters'] );
        $cart_item_data['mfc_custom_length_cm'] = $total_cm;
        $cart_item_data['unique_key'] = md5( $product_id . $total_cm );
    }
    return $cart_item_data;
}

/**
 * محاسبه مجدد قیمت بر اساس متراژ سفارشی در سبد خرید
 */
add_action( 'woocommerce_before_calculate_totals', 'mfc_calculate_price_based_on_length' );
function mfc_calculate_price_based_on_length( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['mfc_custom_length_cm'] ) && $cart_item['mfc_custom_length_cm'] > 0 ) {
            $product         = $cart_item['data'];
            // از get_price('edit') استفاده می‌کنیم تا قیمت خام و بدون فیلتر را بگیریم
            $price_per_meter = $product->get_price( 'edit' ); 
            $price_per_cm    = $price_per_meter / 100;
            $final_price     = $cart_item['mfc_custom_length_cm'] * $price_per_cm;
            $cart_item['data']->set_price( $final_price );
        }
    }
}