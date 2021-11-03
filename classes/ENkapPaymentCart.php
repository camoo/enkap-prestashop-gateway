<?php
/**
 * 2021 Camoo Sarl
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please email license@prestashop.com, so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @author    Camoo Sarl <prestashop@camoo.sarl>
 * @copyright 2021 Camoo Sarl
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class ENkapPaymentCart extends ObjectModel
{
    public $id_cart;

    public $id_order;

    public $merchant_reference_id;

    public $order_transaction_id;

    public $status;

    public $order_total;

    public $date_status;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'e_nkap_payments',
        'primary' => 'id_enkap_payment',
        'fields' => array(
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'merchant_reference_id' => array('type' => self::TYPE_STRING, 'required' => true),
            'order_transaction_id' => array('type' => self::TYPE_STRING, 'required' => true),
            'status' => array('type' => self::TYPE_STRING, 'required' => false),
            'order_total' => array('type' => self::TYPE_FLOAT),
            'date_status' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'remote_ip' => array('type' => self::TYPE_STRING, 'required' => false),
        ),
    );

    public static function getIdByIdCart($id_cart)
    {
        return Db::getInstance()->getValue(
            'SELECT `id_enkap_payment` FROM `' . _DB_PREFIX_ . self::$definition['table'] .
            '` WHERE `id_cart` = ' . (int)$id_cart
        );
    }

    public static function getByIdCart($id_cart)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] .
            '` WHERE `id_cart` = ' . (int)$id_cart);
    }

    public static function getByMerchantReference($merchant_reference_id)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] .
            '` WHERE `merchant_reference_id` = "' . pSQL($merchant_reference_id) . '"');
    }

    public static function applyStatusChange(string $status, string $transactionId): bool
    {
        $remoteIp = Tools::getRemoteAddr();
        $setData = [
            'status_date' => date('Y-m-d H:i:s'),
            'status' => pSQL($status)
        ];
        if ($remoteIp) {
            $setData['remote_ip'] = pSQL($remoteIp);
        }
        return Db::getInstance()->update(
            self::$definition['table'],
            $setData,
            "order_transaction_id = '" . pSQL($transactionId) . "'"
        );
    }

    public static function logPayment(
        int    $orderId,
        int    $cartId,
        string $merchantReferenceId,
        string $orderTransactionId,
        $amount
    ): bool {
        $insertData = [
            'id_cart' => $cartId,
            'id_order' => $orderId,
            'order_transaction_id' => pSQL($orderTransactionId),
            'merchant_reference_id' => pSQL($merchantReferenceId),
            'order_total' => $amount
        ];

        return Db::getInstance()->insert(
            self::$definition['table'],
            $insertData
        );
    }
}
