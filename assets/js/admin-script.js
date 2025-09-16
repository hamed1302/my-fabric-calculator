jQuery(function ($) {
    'use strict';

    const checkbox = $('#_mfc_is_fabric_product');
    const inventoryFields = $('.mfc-inventory-field');

    function toggleInventoryFields() {
        inventoryFields.toggle(checkbox.is(':checked'));
    }

    toggleInventoryFields();
    checkbox.on('change', toggleInventoryFields);
});