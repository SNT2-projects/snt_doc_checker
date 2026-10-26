<?php
/**
 * Upgrade script for snt_doc_checker module version 1.6.0
 *
 * Changes:
 * - Nouvel encart "Commandes du client à approuver" sur la page de validation d'un
 *   document (back-office). Affiche les commandes du client ayant eu le statut
 *   "Demande de pièces : Catégorie C/D", actuellement au statut "Importé vers Oasis v2"
 *   et contenant au moins un article de catégorie C ou D.
 * - Bouton d'application du statut "Commande approuvée [Doc Checker]" en AJAX (sans email).
 * - Deux boutons d'enregistrement : "Enregistrer et rester" et "Enregistrer".
 * - Deux nouvelles clés de configuration :
 *     SNT_DOC_CHECKER_ORDER_STATUS_OASIS    (statut "Importé vers Oasis v2")
 *     SNT_DOC_CHECKER_ORDER_STATUS_APPROVED (statut "Commande approuvée [Doc Checker]")
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_0($module)
{
    // Initialiser les nouvelles clés de configuration si absentes
    if (Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_OASIS') === false) {
        Configuration::updateValue('SNT_DOC_CHECKER_ORDER_STATUS_OASIS', '');
    }
    if (Configuration::get('SNT_DOC_CHECKER_ORDER_STATUS_APPROVED') === false) {
        Configuration::updateValue('SNT_DOC_CHECKER_ORDER_STATUS_APPROVED', '');
    }

    PrestaShopLogger::addLog(
        'snt_doc_checker upgrade 1.6.0: encart commandes client a approuver + statut "Commande approuvee" ajoutes',
        1,
        null,
        'Module',
        $module->id
    );

    return true;
}
