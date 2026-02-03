<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_1($module)
{
    $sql = array();

    // Table contenant les documents
    $sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'snt_doc_checker_data (
        id_data int(11) NOT NULL AUTO_INCREMENT,
        dte_upload              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        link_document           TEXT(300) NOT NULL,
        type_document           VARCHAR(100) NOT NULL,
        statut_document         VARCHAR(100) NOT NULL,
        comment_document        VARCHAR(100),
        dte_update              DATE,
        id_document             int(11) NOT NULL,
        id_customer             int(11) NOT NULL,
        PRIMARY KEY  (`id_data`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    $sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'snt_doc_checker_message (
        id_message int(11)  NOT NULL AUTO_INCREMENT,
        type_message  VARCHAR(100) NOT NULL,
        value_message TEXT(300) NOT NULL,
        PRIMARY KEY  (`id_message`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    // Execute SQL queries
    foreach ($sql as $query) {
        if (!Db::getInstance()->execute($query)) {
            return false;
        }
    }

    $module->installTab('AdminParentModulesSf', 'AdminMenuTabsDocChecker', 'Doc Checker');
    $module->version = '1.0.1';

    return true;
}
