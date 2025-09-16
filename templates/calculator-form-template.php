<?php
/**
 * The template for displaying the fabric calculator form.
 *
 * @package My_Fabric_Calculator
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

// خواندن موجودی با در نظر گرفتن تنظیمات پیش‌فرض
$inventory_cm_meta = get_post_meta( $product->get_id(), '_mfc_inventory_cm', true );
$mfc_options = get_option( 'mfc_options' );
$default_inventory = isset( $mfc_options['default_inventory'] ) ? absint( $mfc_options['default_inventory'] ) : 20;
$inventory_cm = ( '' === $inventory_cm_meta ) ? ( $default_inventory * 100 ) : absint( $inventory_cm_meta );

$max_meters = floor( $inventory_cm / 100 );
?>
<div id="mfc-calculator-wrapper" class="mfc-calculator-wrapper" 
    data-price-per-meter="<?php echo esc_attr( $product->get_price( 'edit' ) ); ?>"
    data-inventory-cm="<?php echo esc_attr( $inventory_cm ); ?>">

    <div class="mfc-inputs-row">
        <div class="mfc-field-group">
            <label for="mfc_meters"><?php esc_html_e( 'متر', 'my-fabric-calculator' ); ?></label>
            <select name="mfc_meters" id="mfc_meters">
                <?php for ( $i = 0; $i <= $max_meters; $i++ ) : ?>
                    <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( number_format_i18n( $i ) ); ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mfc-field-group">
            <label for="mfc_centimeters"><?php esc_html_e( 'سانتی‌متر', 'my-fabric-calculator' ); ?></label>
            <select name="mfc_centimeters" id="mfc_centimeters">
                <?php for ( $i = 0; $i <= 99; $i += 5 ) : ?>
                    <option value="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( number_format_i18n( $i ) ); ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    
    <div id="mfc-price-display" class="mfc-price-display">
        <span class="mfc-price-label"><?php esc_html_e( 'قیمت نهایی:', 'my-fabric-calculator' ); ?></span>
        <span id="mfc-final-price" class="mfc-final-price"></span>
    </div>

    <p id="mfc-warning-message" class="mfc-warning" style="display:none;"></p>
    
    <?php wp_nonce_field( 'mfc_add_to_cart_action', 'mfc_nonce_field' ); ?>
</div>