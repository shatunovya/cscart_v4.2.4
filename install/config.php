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

return array(
    // Add-on names to be installed
    // If empty will be installed only addons included by default
    'addons' => array(),

    'cart_settings' => array (
        'email' => 'admin@example.com',
        'password' => 'admin',
        'secret_key' => 'YOURVERYSECRETCEY',
        'languages' => array (
            'en', 'da', 'de', 'es', 'fr', 'el', 'it', 'nl', 'ro', 'ru', 'bg', 'no', 'sl',
        ),
        'main_language' => 'en',
        'demo_catalog' => true,
        'theme_name' => 'basic',
        'license_number' => 'CART-1111-1111-1111-1111'
    ),
    'database_settings' => array(
        'host' => 'localhost',
        'name' => '%DB_NAME%',
        'user' => '%DB_USER%',
        'password' => '%DB_PASS%',
        'table_prefix' => 'cscart_',
        'database_backend' => 'mysqli',
        'notify' => false,
        'allow_override' => 'Y',
    ),
    'server_settings' => array (
        'http_host' => '%HTTP_HOST%',
        'http_path' => '',
        'https_host' => '%HTTP_HOST%',
        'https_path' => '',
        'correct_permissions' => true,
    ),
);
