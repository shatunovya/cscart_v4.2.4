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

$schema = array (
    array (
        'option_id' => 1,
        'name' => 'yandex_payment_type',
        'description' => __('select_yandex_payment'),
        'value' => '',
        'option_type' =>  'S',
        'position' => 10,
        'option_variants' => array(
            array(
                'variant_id' => 1,
                'variant_name' => 'pc',
                'description' => __('yandex_payment_yandex'),
                'position' => 1
            ),
            array(
                'variant_id' => 2,
                'variant_name' => 'ac',
                'description' => __('yandex_payment_card'),
                'position' => 2
            ),
            array(
                'variant_id' => 3,
                'variant_name' => 'gp',
                'description' => __('yandex_payment_terminal'),
                'position' => 3
            ),
            array(
                'variant_id' => 4,
                'variant_name' => 'mc',
                'description' => __('yandex_payment_phone'),
                'position' => 4
            ),
            array(
                'variant_id' => 5,
                'variant_name' => 'nv',
                'description' => __('yandex_payment_webmoney'),
                'position' => 5
            ),
        ),
        'required' => true,
    ),
);

return $schema;
