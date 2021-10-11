<?php
/**
 * 2007-2021 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com, so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use Enkap\OAuth\Model\Status;
use Symfony\Component\HttpFoundation\JsonResponse;

class E_nkapNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * Do whatever you have to before redirecting the customer on the website of your payment processor.
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('order_ref') === false) {
            throw new PrestaShopException('Invalid Request');
        }
        $merchant_reference_id = Tools::getValue('order_ref');
        $en_payment = ENkapPaymentCart::getByMerchantReference($merchant_reference_id);

        if (empty($en_payment) || empty($en_payment['order_transaction_id'])) {
            return new JsonResponse([
                'status' => 'KO',
                'message' => 'Bad Request'
            ], 400);
        }

        $requestBody = Tools::file_get_contents('php://input');
        $bodyData = json_decode($requestBody, true);

        $status = $bodyData['status'];

        if (empty($status) || !in_array(Tools::safeOutput($status), Status::getAllowedStatus())) {
            return new JsonResponse([
                'status' => 'KO',
                'message' => 'Bad Request'
            ], 400);
        }

        $id_order_state = false;
        $extra_vars = ['transaction_id' => $en_payment['order_transaction_id']];
        $order = new Order((int)$en_payment['id_order']);

        switch ($status) {
            case Status::IN_PROGRESS_STATUS :
            case Status::CREATED_STATUS :
            case Status::INITIALISED_STATUS :
                $id_order_state = (int)Configuration::get('PS_OS_E_NKAP');
                break;
            case Status::CONFIRMED_STATUS :
                $id_order_state = (int)Configuration::get('PS_OS_E_NKAP_ACCEPTED');
                break;
            case Status::CANCELED_STATUS :
                $id_order_state = (int)Configuration::get('PS_OS_CANCELED');
                break;
            case Status::FAILED_STATUS :
                $id_order_state = (int)Configuration::get('PS_OS_ERROR');
                break;
            default :
                break;
        }

        if ($id_order_state && $id_order_state !== $order->getCurrentOrderState()->id) {
            $new_history = new OrderHistory();
            $new_history->id_order = (int)$order->id;
            $new_history->changeIdOrderState((int)$id_order_state, $order, true);
            $new_history->addWithemail(true, $extra_vars);


            $order_payments = OrderPayment::getByOrderReference($order->reference);
            foreach ($order_payments as $p) {
                if ($this->module->displayName === $p->payment_method) {
                    $p->transaction_id = $extra_vars['transaction_id'];
                    $p->update();
                }
            }

        }
        ENkapPaymentCart::applyStatusChange($status, $en_payment['order_transaction_id']);
        return new JsonResponse([
            'status' => 'OK',
            'message' => sprintf('Status Updated To %s', $status)
        ], 200);

    }


}
