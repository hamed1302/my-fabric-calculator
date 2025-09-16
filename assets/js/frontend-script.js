jQuery(function ($) {
    'use strict';

    const wrapper = $('#mfc-calculator-wrapper');
    if (!wrapper.length) return;

    const meterSelect = $('#mfc_meters');
    const cmSelect = $('#mfc_centimeters');
    const finalPriceSpan = $('#mfc-final-price');
    const warningMessage = $('#mfc-warning-message');
    const addToCartButton = $('button.single_add_to_cart_button');

    const pricePerMeter = parseFloat(wrapper.data('price-per-meter'));
    const inventoryCm = parseInt(wrapper.data('inventory-cm'), 10);
    const currencySymbol = mfc_params.currency_symbol;

    // محاسبه حداکثر متر و سانتی‌متر باقیمانده از موجودی کل
    const inventoryMeters = Math.floor(inventoryCm / 100);
    const inventoryRemainingCm = inventoryCm % 100;

    /**
     * تابع اصلی و هوشمند برای مدیریت کامل ماشین حساب
     */
    function updateCalculatorState() {
        const selectedMeters = parseInt(meterSelect.val(), 10);

        // =================================================================
        // ** بخش جدید و هوشمند: مدیریت گزینه‌های سانتی‌متر **
        // =================================================================
        // اگر کاربر حداکثر متر موجود را انتخاب کرده باشد...
        if (selectedMeters === inventoryMeters) {
            // ... آنگاه گزینه‌های سانتی‌متر را مرور کن
            cmSelect.find('option').each(function () {
                const cmOptionValue = parseInt($(this).val(), 10);
                // اگر مقدار گزینه از سانتی‌متر باقیمانده بیشتر بود، آن را غیرفعال کن
                if (cmOptionValue > inventoryRemainingCm) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });

            // اگر مقدار فعلی سانتی‌متر غیرفعال شده بود، آن را به صفر برگردان
            if (parseInt(cmSelect.val(), 10) > inventoryRemainingCm) {
                cmSelect.val('0').trigger('change'); // trigger change تا قیمت آپدیت شود
                return; // از ادامه تابع خارج شو چون یک آپدیت دیگر در راه است
            }

        } else {
            // اگر کاربر متری کمتر از حداکثر را انتخاب کرده، همه گزینه‌های سانتی‌متر باید فعال باشند
            cmSelect.find('option').prop('disabled', false);
        }
        // =================================================================
        // ** پایان بخش جدید **
        // =================================================================

        const selectedCm = parseInt(cmSelect.val(), 10);
        const totalSelectedCm = (selectedMeters * 100) + selectedCm;

        let isValid = true;
        
        // نمایش خطای کلی (به عنوان پشتیبان)
        if (totalSelectedCm > inventoryCm) {
            const message = mfc_params.error_insufficient.replace('%m', inventoryMeters).replace('%c', inventoryRemainingCm);
            warningMessage.text(message).show();
            isValid = false;
        } else if (totalSelectedCm === 0) {
            warningMessage.text(mfc_params.error_minimum).show();
            isValid = false;
        } else {
            warningMessage.hide().text('');
            isValid = true;
        }

        addToCartButton.prop('disabled', !isValid);

        const pricePerCm = pricePerMeter / 100;
        const finalPrice = totalSelectedCm * pricePerCm;
        const formattedPrice = new Intl.NumberFormat('fa-IR').format(finalPrice);
        
        finalPriceSpan.html(formattedPrice + ' ' + currencySymbol);
    }

    // تابع اصلی را به رویداد "تغییر" هر دو سلکت باکس متصل کن
    meterSelect.on('change', updateCalculatorState);
    cmSelect.on('change', updateCalculatorState);

    // یک بار در ابتدای بارگذاری صفحه تابع را اجرا کن تا وضعیت اولیه تنظیم شود
    updateCalculatorState();
});