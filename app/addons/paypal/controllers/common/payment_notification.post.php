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

use Tygh\Http;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'paypal_ipn') {
        if (!empty($_REQUEST['custom'])) {
            unset($_REQUEST['dispatch']);
            
            $result = '';
            $_REQUEST['cmd'] = '_notify-validate';
            $data = array_merge(array('cmd' => '_notify-validate'), $_REQUEST);
            //the txn_type variable absent in case of refund
            if (isset($data['txn_type']) && in_array($data['txn_type'], array('cart', 'express_checkout', 'web_accept')) || !isset($data['txn_type'])) {
                $url = (Registry::get('addons.paypal.test_mode') == 'Y') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
                $result = Http::post($url, $data);
            }

            if ($result == 'VERIFIED') {
                fn_define('ORDER_MANAGEMENT', true);
                $order_ids = fn_pp_get_ipn_order_ids($data);
                foreach($order_ids as $order_id) {
                    fn_process_paypal_ipn($order_id, $data);
                }
            }
        }
        exit;
    }
}
