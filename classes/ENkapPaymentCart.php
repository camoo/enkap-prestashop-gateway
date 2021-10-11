<?php
/**
 * GcPrivilegeCard
 *
 * @author    Grégory Chartier <hello@gregorychartier.fr>
 * @copyright 2018 Grégory Chartier (https://www.gregorychartier.fr)
 * @license   Commercial license see license.txt
 * @category  Prestashop
 * @category  Module
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
        return Db::getInstance()->getValue('SELECT `id_enkap_payment` FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` WHERE `id_cart` = ' . (int)$id_cart);
    }

    public static function getByIdCart($id_cart)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` WHERE `id_cart` = ' . (int)$id_cart);
    }

    public static function getByMerchantReference($merchant_reference_id)
    {
        return Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` WHERE `merchant_reference_id` = "' . $merchant_reference_id . '"');
    }

    public static function applyStatusChange(string $status, string $transactionId): bool
    {
        $remoteIp = Tools::getRemoteAddr();
        $setData = [
            'status_date' => date('Y-m-d H:i:s'),
            'status' => Tools::safeOutput($status)
        ];
        if ($remoteIp) {
            $setData['remote_ip'] = Tools::safeOutput($remoteIp);
        }
        return Db::getInstance()->update(
             self::$definition['table'],
            $setData,
            "order_transaction_id = '".Tools::safeOutput($transactionId)."'"
        );
    }
}
