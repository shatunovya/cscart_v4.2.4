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

if ( !defined('BOOTSTRAP') )    { die('Access denied');    }

use Tygh\Registry;
use Twigmo\Core\TwigmoConnector;

require_once(Registry::get('config.dir.addons') . 'twigmo/Twigmo/Core/phpqrcode/qrlib.php');

if ($mode == 'show_qr') {

    $access_id = fn_twg_get_connected_access_id($auth);

    if (!$access_id) {
        die();
    }

    $user_info = Registry::get('user_info');
    $login = Registry::get('settings.General.use_email_as_login') == 'Y' ? $user_info['email'] : $user_info['user_login'];

    // outputs image directly into browser, as PNG stream
    $url = TwigmoConnector::getAdminUrl(false);

    QRcode::png($url . '_' . $login . '_' . $access_id);

} elseif ($mode == 'view') {
    Registry::get('view')->assign('connected_access_id', fn_twg_get_connected_access_id($auth));

}
