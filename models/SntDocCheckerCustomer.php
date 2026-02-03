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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Model for customer information specific to document checking
 * Stores birth date linked to customer (not to individual documents)
 */
class SntDocCheckerCustomer extends ObjectModel
{
    public $id_customer_info;
    public $id_customer;
    public $birth_date;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'snt_doc_checker_customer',
        'primary' => 'id_customer_info',
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'required' => true),
            'birth_date' => array('type' => self::TYPE_DATE, 'required' => false),
            'date_add' => array('type' => self::TYPE_DATE),
            'date_upd' => array('type' => self::TYPE_DATE),
        ),
    );

    /**
     * Get customer info by customer ID
     *
     * @param int $id_customer
     * @return array|false
     */
    public static function getByCustomerId($id_customer)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('snt_doc_checker_customer');
        $sql->where('id_customer = ' . (int)$id_customer);

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Get or create customer info record
     * Creates a new record with NULL birth_date if doesn't exist
     *
     * @param int $id_customer
     * @return array
     */
    public static function getOrCreate($id_customer)
    {
        $existing = self::getByCustomerId($id_customer);

        if ($existing) {
            return $existing;
        }

        // Create new record with NULL birth_date
        Db::getInstance()->insert('snt_doc_checker_customer', array(
            'id_customer' => (int)$id_customer,
            'birth_date' => null,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ));

        return self::getByCustomerId($id_customer);
    }

    /**
     * Update birth date for customer
     *
     * @param int $id_customer
     * @param string $birth_date (format: Y-m-d)
     * @return bool
     */
    public static function updateBirthDate($id_customer, $birth_date)
    {
        // Ensure record exists
        self::getOrCreate($id_customer);

        return Db::getInstance()->update(
            'snt_doc_checker_customer',
            array(
                'birth_date' => pSQL($birth_date),
                'date_upd' => date('Y-m-d H:i:s')
            ),
            'id_customer = ' . (int)$id_customer
        );
    }

    /**
     * Check if customer has birth date filled
     *
     * @param int $id_customer
     * @return bool
     */
    public static function hasBirthDate($id_customer)
    {
        $info = self::getByCustomerId($id_customer);

        return $info && !empty($info['birth_date']) && $info['birth_date'] !== '0000-00-00';
    }

    /**
     * Get birth date for customer
     *
     * @param int $id_customer
     * @return string|null
     */
    public static function getBirthDate($id_customer)
    {
        $info = self::getByCustomerId($id_customer);

        if ($info && !empty($info['birth_date']) && $info['birth_date'] !== '0000-00-00') {
            return $info['birth_date'];
        }

        return null;
    }

    /**
     * Get formatted birth date for display
     *
     * @param int $id_customer
     * @param string $format Date format (default: d/m/Y)
     * @return string|null
     */
    public static function getFormattedBirthDate($id_customer, $format = 'd/m/Y')
    {
        $birthDate = self::getBirthDate($id_customer);

        if ($birthDate) {
            $date = DateTime::createFromFormat('Y-m-d', $birthDate);
            if ($date) {
                return $date->format($format);
            }
        }

        return null;
    }
}
