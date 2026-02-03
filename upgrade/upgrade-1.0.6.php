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

function upgrade_module_1_0_6($module)
{
    try {
        // Synchroniser les dates d'expiration entre documents liés existants
        // Récupérer toutes les associations existantes
        $associations = Db::getInstance()->executeS('
            SELECT a.id_document_principal, a.id_document_associe,
                   p.dte_expiration as principal_expiration,
                   s.dte_expiration as associated_expiration
            FROM ' . _DB_PREFIX_ . 'snt_doc_checker_doc_association a
            INNER JOIN ' . _DB_PREFIX_ . 'snt_doc_checker_data p ON p.id_data = a.id_document_principal
            INNER JOIN ' . _DB_PREFIX_ . 'snt_doc_checker_data s ON s.id_data = a.id_document_associe
        ');

        foreach ($associations as $association) {
            $principalExpiration = $association['principal_expiration'];
            $associatedExpiration = $association['associated_expiration'];
            
            // Si le document principal a une date d'expiration valide, l'appliquer au document associé
            if (!empty($principalExpiration) && $principalExpiration !== '0000-00-00') {
                // Mettre à jour le document associé avec la date du principal
                Db::getInstance()->update('snt_doc_checker_data', 
                    array('dte_expiration' => pSQL($principalExpiration)),
                    'id_data = ' . (int)$association['id_document_associe']
                );
            }
            // Si le document principal n'a pas de date mais le document associé en a une, copier vers le principal
            elseif ((empty($principalExpiration) || $principalExpiration === '0000-00-00') && 
                    !empty($associatedExpiration) && $associatedExpiration !== '0000-00-00') {
                // Mettre à jour le document principal avec la date de l'associé
                Db::getInstance()->update('snt_doc_checker_data', 
                    array('dte_expiration' => pSQL($associatedExpiration)),
                    'id_data = ' . (int)$association['id_document_principal']
                );
            }
        }

        $module->version = '1.0.6';
        return true;
        
    } catch (Exception $e) {
        // En cas d'erreur, ne pas faire échouer la mise à jour mais logger l'erreur
        PrestaShopLogger::addLog(
            'Erreur lors de la synchronisation des dates d\'expiration: ' . $e->getMessage(),
            3,
            null,
            'SntDocChecker'
        );
        
        $module->version = '1.0.6';
        return true;
    }
} 