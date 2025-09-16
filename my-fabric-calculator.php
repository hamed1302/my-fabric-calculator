<?php
/**
 * Plugin Name:       ماشین حساب پارچه ووکامرس
 * Plugin URI:        https://your-website.com/
 * Description:       افزونه‌ای برای محاسبه و فروش پارچه بر اساس متر و سانتی‌متر با محدودیت موجودی برای ووکامرس.
 * Version:           1.3.0
 * Author:            نام شما
 * Author URI:        https://your-website.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-fabric-calculator
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * Requires PHP: 7.4
 */

// جلوگیری از دسترسی مستقیم به فایل
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// تعریف ثابت‌ها برای مسیرها و نسخه
define( 'MFC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MFC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MFC_VERSION', '1.3.0' );

// اعلام سازگاری با سیستم ذخیره‌سازی سفارشات با کارایی بالا (HPOS)
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

/**
 * بارگذاری فایل‌های اصلی افزونه پس از بارگذاری کامل وردپرس و ووکامرس
 */
function mfc_include_plugin_files() {
    // ابتدا بررسی می‌کنیم که ووکامرس فعال باشد
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'mfc_woocommerce_not_active_notice' );
        return;
    }

    // بارگذاری فایل‌های منطقی و عملکردی
    require_once MFC_PLUGIN_PATH . 'includes/frontend-functions.php';
    require_once MFC_PLUGIN_PATH . 'includes/backend-logic.php';
    require_once MFC_PLUGIN_PATH . 'includes/admin-page.php';
}
add_action( 'plugins_loaded', 'mfc_include_plugin_files' );

/**
 * نمایش پیغام خطا در صورتی که ووکامرس فعال نباشد
 */
function mfc_woocommerce_not_active_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e( 'افزونه "ماشین حساب پارچه" برای کار کردن نیاز به نصب و فعال بودن ووکامرس دارد.', 'my-fabric-calculator' ); ?></p>
    </div>
    <?php
}

/**
 * افزودن لینک "تنظیمات" به صفحه افزونه‌ها
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mfc_add_settings_action_link' );
function mfc_add_settings_action_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=mfc-fabric-calculator-settings' ) . '">' . esc_html__( 'تنظیمات', 'my-fabric-calculator' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}