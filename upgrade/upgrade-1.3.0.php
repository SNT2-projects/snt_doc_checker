<?php
/**
 * Upgrade script for snt_doc_checker module version 1.3.0
 *
 * Changes:
 * - Added ps_snt_doc_checker_customer table for customer birth dates
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($module)
{
    $sql = array();

    // Create new table for customer birth dates
    // This table stores birth_date linked to customer (not to individual documents)
    // birth_date is NULL by default for existing customers
    $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snt_doc_checker_customer` (
        `id_customer_info` int(11) NOT NULL AUTO_INCREMENT,
        `id_customer` int(11) NOT NULL,
        `birth_date` DATE NULL,
        `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_customer_info`),
        UNIQUE KEY `id_customer` (`id_customer`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    foreach ($sql as $query) {
        if (Db::getInstance()->execute($query) == false) {
            PrestaShopLogger::addLog(
                'snt_doc_checker upgrade 1.3.0: SQL Error - ' . Db::getInstance()->getMsgError(),
                3,
                null,
                'Module',
                $module->id
            );
            return false;
        }
    }

    PrestaShopLogger::addLog(
        'snt_doc_checker upgrade 1.3.0: Successfully created ps_snt_doc_checker_customer table',
        1,
        null,
        'Module',
        $module->id
    );

    $module->version = '1.3.0';
    return true;
}
