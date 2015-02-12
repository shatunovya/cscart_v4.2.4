<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    require './../../../payments/init_payment.php';
}


if (defined('PAYMENT_NOTIFICATION')) {

    if ($mode == 'cancel') {

        $order_info = fn_get_order_info($_REQUEST['order_id']);

        if ($order_info['status'] == 'O' || $order_info['status'] == 'I') {
            $pp_response['order_status'] = 'I';
            $pp_response["reason_text"] = __('text_transaction_cancelled');
            fn_finish_payment($order_info['order_id'], $pp_response);
        }

        fn_order_placement_routines('route', $_REQUEST['order_id'], false);

    } else {
        $order_id = (!empty($_REQUEST['order_id'])) ? $_REQUEST['order_id'] : 0;
        $token = (!empty($_REQUEST['token'])) ? $_REQUEST['token'] : 0;

        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);
        $processor_data['processor_script'] = 'paypal_express.php';
        $order_info = fn_get_order_info($order_id);

        fn_paypal_complete_checkout($token, $processor_data, $order_info);
    }
}

$mode = (!empty($mode)) ? $mode : (!empty($_REQUEST['mode']) ? $_REQUEST['mode'] : '');

if ($mode == 'express_return') {

    $token = $_REQUEST['token'];
    $payment_id = $_REQUEST['payment_id'];

    $processor_data = fn_get_payment_method_data($payment_id);
    $paypal_checkout_details = fn_paypal_get_express_checkout_details($processor_data, $token);

    if (fn_paypal_ack_success($paypal_checkout_details)) {
        fn_paypal_user_login($paypal_checkout_details);

        $paypal_express_details = array(
            'token' => $token,
            'payment_id' => $payment_id
        );
        $_SESSION['pp_express_details'] = $paypal_express_details;
        $_SESSION['cart']['payment_id'] = $payment_id;
    } else {
        fn_paypal_get_error($paypal_checkout_details);
    }

    fn_order_placement_routines('checkout_redirect');

} elseif ($mode == 'place_order' && !empty($_SESSION['pp_express_details'])) {

    $token = $_SESSION['pp_express_details']['token'];
    fn_paypal_complete_checkout($token, $processor_data, $order_info);

} elseif ($mode == 'place_order' || $mode == 'express' || $mode == 'repay') {

    if (!defined('BOOTSTRAP')) {
        require './init_payment.php';
        $_SESSION['cart'] = empty($_SESSION['cart']) ? array() : $_SESSION['cart'];
    }

    $payment_id = (empty($_REQUEST['payment_id']) ? $_SESSION['cart']['payment_id'] : $_REQUEST['payment_id']);

    if ($mode == 'express') {
        $result = fn_paypal_set_express_checkout($payment_id, 0, array(), $_SESSION['cart']);
        $useraction = 'continue';
    } else {
        $result = fn_paypal_set_express_checkout($payment_id, $order_id, $order_info);
        $useraction = "commit";
    }

    if (fn_paypal_ack_success($result) && !empty($result['TOKEN'])) {

        $processor_data = fn_get_payment_method_data($payment_id);

        fn_paypal_payment_form($processor_data, $result['TOKEN']);
    } else {
        fn_paypal_get_error($result);

        if ($mode == 'express') {
            fn_order_placement_routines('checkout.cart');
        } else {
            fn_order_placement_routines('checkout_redirect');
        }
    }
}
