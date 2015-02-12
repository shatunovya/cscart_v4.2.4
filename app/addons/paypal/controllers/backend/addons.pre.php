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
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update' && $_REQUEST['addon'] == 'paypal' && (!empty($_REQUEST['pp_settings']) || !empty($_REQUEST['paypal_logo_image_data']))) {
        $pp_settings = isset($_REQUEST['pp_settings']) ? $_REQUEST['pp_settings'] : array();
        fn_update_paypal_settings($pp_settings);
    }
}

if ($mode == 'update') {
    if ($_REQUEST['addon'] == 'paypal') {
        Registry::get('view')->assign('pp_settings', fn_get_paypal_settings());
    }
}
