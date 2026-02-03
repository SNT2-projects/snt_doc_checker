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

function upgrade_module_1_0_7($module)
{
    try {
        // Générer la clé de sécurité pour le CRON si elle n'existe pas
        if (!Configuration::hasKey('SNT_DOC_CHECKER_CRON_SECURE_KEY') || !Configuration::get('SNT_DOC_CHECKER_CRON_SECURE_KEY')) {
            $secureKey = md5(uniqid(rand(), true));
            Configuration::updateValue('SNT_DOC_CHECKER_CRON_SECURE_KEY', $secureKey);
        }

        // Configuration par défaut pour l'automatisation
        if (!Configuration::hasKey('SNT_DOC_CHECKER_DAILY_CHECK')) {
            Configuration::updateValue('SNT_DOC_CHECKER_DAILY_CHECK', false);
        }

        // Logger la mise à jour
        PrestaShopLogger::addLog(
            'SNT Doc Checker: Module upgraded to v1.0.7 with cron functionality. Secure key generated.',
            1,
            null,
            'SntDocChecker'
        );

        $module->version = '1.0.7';
        return true;
        
    } catch (Exception $e) {
        // En cas d'erreur, ne pas faire échouer la mise à jour mais logger l'erreur
        PrestaShopLogger::addLog(
            'SNT Doc Checker: Error during upgrade to v1.0.7: ' . $e->getMessage(),
            3,
            null,
            'SntDocChecker'
        );
        
        // On continue quand même la mise à jour
        $module->version = '1.0.7';
        return true;
    }
} 