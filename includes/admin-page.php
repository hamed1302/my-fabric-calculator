<?php
/**
 * All functions related to the admin settings page and product edit page fields.
 *
 * @package My_Fabric_Calculator
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ایجاد صفحه تنظیمات در منوی "تنظیمات" وردپرس
 */
add_action( 'admin_menu', 'mfc_create_settings_page' );
function mfc_create_settings_page() {
    add_options_page(
        __( 'تنظیمات ماشین حساب پارچه', 'my-fabric-calculator' ),
        __( 'ماشین حساب پارچه', 'my-fabric-calculator' ),
        'manage_options',
        'mfc-fabric-calculator-settings',
        'mfc_render_settings_page_html'
    );
}

/**
 * رندر کردن HTML کلی صفحه تنظیمات
 */
function mfc_render_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'mfc_settings_group' );
            do_settings_sections( 'mfc-fabric-calculator-settings' );
            submit_button( __( 'ذخیره تغییرات', 'my-fabric-calculator' ) );
            ?>
        </form>
    </div>
    <?php
}

/**
 * ثبت تنظیمات، بخش‌ها و فیلدها با استفاده از Settings API
 */
add_action( 'admin_init', 'mfc_register_settings' );
function mfc_register_settings() {
    register_setting(
        'mfc_settings_group',
        'mfc_options',
        array(
            'sanitize_callback' => 'mfc_sanitize_options',
        )
    );

    add_settings_section(
        'mfc_general_settings_section',
        __( 'تنظیمات اصلی', 'my-fabric-calculator' ),
        null,
        'mfc-fabric-calculator-settings'
    );

    add_settings_field(
        'mfc_default_inventory_field',
        __( 'موجودی اولیه پیش‌فرض (متر)', 'my-fabric-calculator' ),
        'mfc_render_default_inventory_field',
        'mfc-fabric-calculator-settings',
        'mfc_general_settings_section'
    );

    add_settings_field(
        'mfc_display_condition_field',
        __( 'شرط نمایش ماشین حساب', 'my-fabric-calculator' ),
        'mfc_render_display_condition_field',
        'mfc-fabric-calculator-settings',
        'mfc_general_settings_section'
    );

    add_settings_field(
        'mfc_categories_field',
        __( 'انتخاب دسته‌بندی‌ها', 'my-fabric-calculator' ),
        'mfc_render_categories_field',
        'mfc-fabric-calculator-settings',
        'mfc_general_settings_section'
    );
}

/**
 * تابع پاک‌سازی برای تنظیمات افزونه
 */
function mfc_sanitize_options( $input ) {
    $sanitized_input = array();
    if ( isset( $input['default_inventory'] ) ) {
        $sanitized_input['default_inventory'] = absint( $input['default_inventory'] );
    }
    if ( isset( $input['display_condition'] ) ) {
        $sanitized_input['display_condition'] = sanitize_text_field( $input['display_condition'] );
    }
    if ( isset( $input['display_categories'] ) && is_array( $input['display_categories'] ) ) {
        $sanitized_input['display_categories'] = array_map( 'absint', $input['display_categories'] );
    }
    return $sanitized_input;
}


/**
 * توابع رندر کردن هر فیلد در صفحه تنظیمات
 */
function mfc_render_default_inventory_field() {
    $options = get_option( 'mfc_options' );
    $value = isset( $options['default_inventory'] ) ? absint( $options['default_inventory'] ) : 20;
    echo '<input type="number" name="mfc_options[default_inventory]" value="' . esc_attr( $value ) . '" min="0" /> ' . esc_html__( 'متر', 'my-fabric-calculator' );
    echo '<p class="description">' . esc_html__( 'اگر برای محصولی موجودی دستی وارد نشود، این مقدار به عنوان موجودی پیش‌فرض در نظر گرفته می‌شود.', 'my-fabric-calculator' ) . '</p>';
}

function mfc_render_display_condition_field() {
    $options = get_option( 'mfc_options' );
    $value = isset( $options['display_condition'] ) ? $options['display_condition'] : 'per_product';
    ?>
    <select name="mfc_options[display_condition]" id="mfc_display_condition">
        <option value="per_product" <?php selected( $value, 'per_product' ); ?>><?php esc_html_e( 'بر اساس تنظیمات هر محصول (تیک دستی)', 'my-fabric-calculator' ); ?></option>
        <option value="in_categories" <?php selected( $value, 'in_categories' ); ?>><?php esc_html_e( 'فقط در دسته‌بندی‌های انتخاب شده', 'my-fabric-calculator' ); ?></option>
        <option value="all_products" <?php selected( $value, 'all_products' ); ?>><?php esc_html_e( 'فعال برای تمام محصولات', 'my-fabric-calculator' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'انتخاب کنید ماشین حساب در کجا نمایش داده شود.', 'my-fabric-calculator' ); ?></p>
    <script>
        jQuery(document).ready(function($) {
            function toggleCategoryField() {
                var condition = $('#mfc_display_condition').val();
                var categoryFieldRow = $('#mfc_categories_field').closest('tr');
                if (condition === 'in_categories') {
                    categoryFieldRow.show();
                } else {
                    categoryFieldRow.hide();
                }
            }
            toggleCategoryField();
            $('#mfc_display_condition').on('change', toggleCategoryField);
        });
    </script>
    <?php
}

function mfc_render_categories_field() {
    $options = get_option( 'mfc_options' );
    $selected_cats = isset( $options['display_categories'] ) ? (array) $options['display_categories'] : array();
    $categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
    
    echo '<div id="mfc_categories_field">';
    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        echo '<select name="mfc_options[display_categories][]" multiple="multiple" style="min-width:300px; height: 150px;">';
        foreach ( $categories as $category ) {
            $selected = in_array( $category->term_id, $selected_cats ) ? 'selected' : '';
            echo '<option value="' . esc_attr( $category->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $category->name ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'اگر شرط نمایش را "فقط در دسته‌بندی‌ها" انتخاب کرده‌اید، دسته‌بندی‌های مورد نظر را انتخاب کنید. (برای انتخاب چندگانه، کلید Ctrl یا Cmd را نگه دارید)', 'my-fabric-calculator' ) . '</p>';
    } else {
        echo '<p>' . esc_html__( 'هیچ دسته‌بندی محصولی یافت نشد.', 'my-fabric-calculator' ) . '</p>';
    }
    echo '</div>';
}

/**
 * اضافه کردن فیلدهای سفارشی به تب "انبار" در صفحه ویرایش محصول
 */
add_action( 'woocommerce_product_options_inventory_product_data', 'mfc_add_custom_fields_to_products' );
function mfc_add_custom_fields_to_products() {
    echo '<div class="options_group mfc-custom-fields-wrapper">';
    wp_nonce_field( 'mfc_save_product_meta', 'mfc_meta_nonce' );

    woocommerce_wp_checkbox( array(
        'id'            => '_mfc_is_fabric_product',
        'label'         => __( 'فعال‌سازی ماشین حساب پارچه', 'my-fabric-calculator' ),
        'description'   => __( 'برای فعال کردن فروش متراژی این گزینه را تیک بزنید (این گزینه تنها در صورتی کار می‌کند که شرط نمایش "بر اساس تنظیمات هر محصول" باشد).', 'my-fabric-calculator' ),
        'desc_tip'      => true,
    ) );
    woocommerce_wp_text_input( array(
        'id'                => '_mfc_inventory_meter',
        'label'             => __( 'متر موجودی دستی', 'my-fabric-calculator' ),
        'type'              => 'number',
        'custom_attributes' => array( 'step' => '1', 'min'  => '0' ),
        'wrapper_class'     => 'mfc-inventory-field',
    ) );
    woocommerce_wp_text_input( array(
        'id'                => '_mfc_inventory_centimeter',
        'label'             => __( 'سانتی‌متر موجودی دستی', 'my-fabric-calculator' ),
        'type'              => 'number',
        'custom_attributes' => array( 'step' => '5', 'min'  => '0', 'max'  => '95' ),
        'wrapper_class'     => 'mfc-inventory-field',
    ) );
    echo '</div>';
}

/**
 * ذخیره مقادیر فیلدهای سفارشی محصول
 */
add_action( 'woocommerce_process_product_meta', 'mfc_save_custom_fields' );
function mfc_save_custom_fields( $post_id ) {
    if ( ! isset( $_POST['mfc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mfc_meta_nonce'] ) ), 'mfc_save_product_meta' ) ) {
        return;
    }

    $is_fabric_product = isset( $_POST['_mfc_is_fabric_product'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_mfc_is_fabric_product', sanitize_text_field( $is_fabric_product ) );

    $meter_value = isset( $_POST['_mfc_inventory_meter'] ) ? trim( wp_unslash( $_POST['_mfc_inventory_meter'] ) ) : '';
    $cm_value_raw = isset( $_POST['_mfc_inventory_centimeter'] ) ? trim( wp_unslash( $_POST['_mfc_inventory_centimeter'] ) ) : '';

    if ( '' !== $meter_value || '' !== $cm_value_raw ) {
        $meters = absint( $meter_value );
        
        $cm_value = absint( $cm_value_raw );
        $centimeters = floor( $cm_value / 5 ) * 5;

        $total_cm = ( $meters * 100 ) + $centimeters;
        update_post_meta( $post_id, '_mfc_inventory_cm', $total_cm );
        update_post_meta( $post_id, '_mfc_inventory_meter', $meters );
        update_post_meta( $post_id, '_mfc_inventory_centimeter', $centimeters );
    } else {
        delete_post_meta( $post_id, '_mfc_inventory_cm' );
        delete_post_meta( $post_id, '_mfc_inventory_meter' );
        delete_post_meta( $post_id, '_mfc_inventory_centimeter' );
    }
}

/**
 * افزودن اسکریپت ادمین برای صفحه ویرایش محصول
 */
add_action( 'admin_enqueue_scripts', 'mfc_enqueue_admin_assets' );
function mfc_enqueue_admin_assets( $hook ) {
    global $post;
    if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && isset( $post->post_type ) && 'product' === $post->post_type ) {
        wp_enqueue_script( 'mfc-admin-script', MFC_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery' ), MFC_VERSION, true );
    }
}