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
    id_document             VARCHAR(100) NOT NULL,
    dte_expiration          DATE,
    id_customer             int(11) NOT NULL,
    PRIMARY KEY  (`id_data`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'snt_doc_checker_message (
    id_message int(11)  NOT NULL AUTO_INCREMENT,
    type_message  VARCHAR(100) NOT NULL,
    value_message TEXT(300) NOT NULL,
    PRIMARY KEY  (`id_message`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'snt_doc_checker_doc_type (
    id_doc_type int(11) NOT NULL AUTO_INCREMENT,
    doc_type_identifier VARCHAR(100) NOT NULL,
    doc_type_name VARCHAR(100) NOT NULL,
    doc_type_description TEXT(300) NOT NULL,
    doc_type_status TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY  (`id_doc_type`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


// Table pour les logs d'activité
$sql[] = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'snt_doc_checker_logs (
    `id_log` int(11) NOT NULL AUTO_INCREMENT,
    `id_employee` int(11) NOT NULL,
    `employee_name` varchar(255) NOT NULL,
    `id_document` int(11) NOT NULL,
    `id_customer` int(11) NOT NULL,
    `action` varchar(50) NOT NULL,
    `details` text,
    `date_add` datetime NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    PRIMARY KEY (`id_log`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


// Table pour les associations de documents (recto/verso)
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snt_doc_checker_doc_association` (
    `id_association` int(11) NOT NULL AUTO_INCREMENT,
    `id_document_principal` int(11) NOT NULL,
    `id_document_associe` int(11) NOT NULL,
    `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_association`),
    KEY `id_document_principal` (`id_document_principal`),
    KEY `id_document_associe` (`id_document_associe`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
