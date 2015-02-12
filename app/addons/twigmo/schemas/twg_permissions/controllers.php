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


$schema = array(
    'products.update' =>    'manage_catalog',
    'images.delete' =>      'manage_catalog',

    'products.get' =>       'view_catalog',
    'products.details' =>   'view_catalog',

    'profile.update' =>     'manage_users',
    'users.get' =>          'view_users',
    'users.details' =>      'view_users',

    'orders.update' =>          'edit_order',
    'orders.update_status' =>   'change_order_status',
    'orders.update_info' =>     'view_orders',
    'orders.get' =>             'view_orders',
    'orders.details' =>         'view_orders',

    'timeline.get' =>       'view_logs',

    'none' =>               'view_reports' # there is no separate request for the stat charts
);

return $schema;
