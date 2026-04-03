<?php
/**
 * Upgrade script for snt_doc_checker module version 1.5.1
 *
 * Changes:
 * - FX Airguns products now follow their C/D categorization:
 *   - FX Airguns + Category C => "Demande de pieces : Categorie C"
 *   - FX Airguns + Category D => "Demande de pieces : Categorie D" (always, even if adult)
 *   - FX Airguns without category => No action
 * - Previously, all FX Airguns products were treated as Category C
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_1($module)
{
    // No database changes required
    // This is a logic-only update

    PrestaShopLogger::addLog(
        'snt_doc_checker upgrade 1.5.1: FX Airguns logic updated - now follows C/D categorization with mandatory manual control',
        1,
        null,
        'Module',
        $module->id
    );

    return true;
}
