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

function upgrade_module_1_0_8($module)
{
    try {
        // Supprimer l'ancienne configuration des IPs CRON (plus nécessaire avec la clé de sécurité)
        if (Configuration::hasKey('SNT_DOC_CHECKER_CRON_IPS')) {
            Configuration::deleteByName('SNT_DOC_CHECKER_CRON_IPS');
        }

        // Supprimer l'ancienne configuration des IPs autorisées pour les documents (non utilisée)
        if (Configuration::hasKey('SNT_DOC_CHECKER_ALLOWED_DOCUMENT_IPS')) {
            Configuration::deleteByName('SNT_DOC_CHECKER_ALLOWED_DOCUMENT_IPS');
        }

        PrestaShopLogger::addLog(
            'SNT Doc Checker: Upgrade 1.0.8 - Configurations obsolètes supprimées (CRON_IPS et ALLOWED_DOCUMENT_IPS)',
            1,
            null,
            'SntDocChecker'
        );

        return true;

    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'SNT Doc Checker: Erreur upgrade 1.0.8 - ' . $e->getMessage(),
            3,
            null,
            'SntDocChecker'
        );
        return false;
    }
} 