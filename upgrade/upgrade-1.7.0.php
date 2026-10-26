<?php
/**
 * Upgrade script for snt_doc_checker module version 1.7.0
 *
 * Changes:
 * - Le bouton de l'encart "Commandes du client à approuver" débloque désormais la
 *   commande via l'API de l'ERP (Oasis) au lieu d'appliquer un statut PrestaShop :
 *     1. GET  {erp_url}/io/api/v1/orders?filter[prestashop_id]={ref_web}&display=[reference]
 *        (en-tête x-api-key) pour récupérer la référence ERP (Oasis).
 *     2. POST {erp_url}/api/{token}/json/DebloquerCommande (num_commande={ref_oasis}).
 * - Nouvel onglet de configuration "ERP / API" avec deux nouvelles clés :
 *     SNT_DOC_CHECKER_ERP_URL   (URL de base de l'ERP, sans slash final)
 *     SNT_DOC_CHECKER_ERP_TOKEN (clé API x-api-key / token)
 * - Nouvelle table ps_snt_doc_checker_erp_logs : journalise chaque appel API ERP
 *   (URL, code HTTP, cible de redirection, corps de réponse, erreur). Un bouton
 *   "Purger les logs" (TRUNCATE) est disponible dans l'onglet "ERP / API".
 * - Nouvelle table ps_snt_doc_checker_unblocked_orders : mémorise les commandes
 *   débloquées dans l'ERP afin de ne plus les proposer dans l'encart
 *   "Commandes du client à approuver" (le statut Presta ne change pas).
 * - Le statut "Commande approuvée [Doc Checker]" (SNT_DOC_CHECKER_ORDER_STATUS_APPROVED)
 *   et l'action AJAX applyApprovedStatus sont conservés mais ne sont plus utilisés
 *   par le bouton.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_0($module)
{
    // Initialiser les nouvelles clés de configuration ERP si absentes
    if (Configuration::get('SNT_DOC_CHECKER_ERP_URL') === false) {
        Configuration::updateValue('SNT_DOC_CHECKER_ERP_URL', '');
    }
    if (Configuration::get('SNT_DOC_CHECKER_ERP_TOKEN') === false) {
        Configuration::updateValue('SNT_DOC_CHECKER_ERP_TOKEN', '');
    }

    // Créer la table de logs des appels API ERP
    $sqlErpLogs = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snt_doc_checker_erp_logs` (
        `id_erp_log` int(11) NOT NULL AUTO_INCREMENT,
        `call_type` varchar(32) NOT NULL,
        `id_order` int(11) NOT NULL DEFAULT 0,
        `presta_reference` varchar(64) NOT NULL DEFAULT \'\',
        `oasis_reference` varchar(64) DEFAULT NULL,
        `request_url` text,
        `http_code` int(11) NOT NULL DEFAULT 0,
        `redirect_url` text,
        `response_body` text,
        `error_message` text,
        `success` tinyint(1) NOT NULL DEFAULT 0,
        `id_employee` int(11) NOT NULL DEFAULT 0,
        `employee_name` varchar(255) NOT NULL DEFAULT \'\',
        `date_add` datetime NOT NULL,
        PRIMARY KEY (`id_erp_log`),
        KEY `id_order` (`id_order`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    if (!Db::getInstance()->execute($sqlErpLogs)) {
        return false;
    }

    // Créer la table des commandes débloquées dans l'ERP
    $sqlUnblocked = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snt_doc_checker_unblocked_orders` (
        `id_unblocked` int(11) NOT NULL AUTO_INCREMENT,
        `id_order` int(11) NOT NULL,
        `presta_reference` varchar(64) NOT NULL DEFAULT \'\',
        `oasis_reference` varchar(64) DEFAULT NULL,
        `id_employee` int(11) NOT NULL DEFAULT 0,
        `employee_name` varchar(255) NOT NULL DEFAULT \'\',
        `date_add` datetime NOT NULL,
        PRIMARY KEY (`id_unblocked`),
        UNIQUE KEY `id_order` (`id_order`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    if (!Db::getInstance()->execute($sqlUnblocked)) {
        return false;
    }

    PrestaShopLogger::addLog(
        'snt_doc_checker upgrade 1.7.0: deblocage commande via API ERP + onglet config "ERP / API"',
        1,
        null,
        'Module',
        $module->id
    );

    return true;
}
