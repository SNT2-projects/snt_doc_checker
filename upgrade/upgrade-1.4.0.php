<?php
/**
 * Upgrade script for snt_doc_checker module version 1.4.0
 *
 * Changes:
 * - Replace actionValidateOrder hook with actionOrderStatusPostUpdate
 *   (ensures order status is added AFTER payment status, not before)
 * - Support for separate order statuses for Category C and Category D
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($module)
{
    $success = true;

    // Unregister old hooks (if registered by previous versions)
    $module->unregisterHook('actionValidateOrder');
    $module->unregisterHook('actionOrderStatusPostUpdate');

    // Register the new hook - called AFTER order history entry is saved to database
    // This ensures our status is added after the payment module's status
    $result = $module->registerHook('actionObjectOrderHistoryAddAfter');

    if ($result) {
        PrestaShopLogger::addLog(
            'snt_doc_checker upgrade 1.4.0: Successfully registered actionObjectOrderHistoryAddAfter hook',
            1,
            null,
            'Module',
            $module->id
        );
    } else {
        PrestaShopLogger::addLog(
            'snt_doc_checker upgrade 1.4.0: Failed to register actionObjectOrderHistoryAddAfter hook',
            3,
            null,
            'Module',
            $module->id
        );
        $success = false;
    }

    // Migrate old config value to new ones (if exists)
    $oldStatusPieces = Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_PIECES');
    if ($oldStatusPieces) {
        // If old config exists, copy it to both C and D (user can adjust later)
        if (!Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C')) {
            Configuration::updateValue('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_C', $oldStatusPieces);
        }
        if (!Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D')) {
            Configuration::updateValue('SNT_DOC_CHECKER_ORDER_STATUS_PIECES_D', $oldStatusPieces);
        }
        // Remove old config
        Configuration::deleteByName('SNT_DOC_CHECKER_ORDER_STATUS_PIECES');

        PrestaShopLogger::addLog(
            'snt_doc_checker upgrade 1.4.0: Migrated SNT_DOC_CHECKER_ORDER_STATUS_PIECES to C and D variants',
            1,
            null,
            'Module',
            $module->id
        );
    }

    if ($success) {
        $module->version = '1.4.0';
    }

    return $success;
}
