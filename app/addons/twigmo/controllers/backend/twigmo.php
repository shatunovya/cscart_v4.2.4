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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Twigmo\Core\Functions\Order\TwigmoOrder;
use Twigmo\Core\Api;
use Twigmo\Core\TwigmoConnector;
use Tygh\Session;
use Tygh\Navigation\LastView;
use Twigmo\Core\Functions\Lang;
use Twigmo\Api\ApiData;
use Twigmo\Core\TwigmoSettings;

$format = !empty($_REQUEST['format']) ? $_REQUEST['format'] : TWG_DEFAULT_DATA_FORMAT;
$api_version = !empty($_REQUEST['api_version']) ? $_REQUEST['api_version'] : TWG_DEFAULT_API_VERSION;
$response = new ApiData($api_version, $format);

if (!empty($_REQUEST['callback'])) {
    $response->setCallback($_REQUEST['callback']);
}

$object = !empty($_REQUEST['object']) ? $_REQUEST['object'] : '';
$lang_code = DESCR_SL;
$action = $_REQUEST['action'];

if (empty($action) || !fn_twg_check_permissions($object, $action, $auth)) {
    fn_twg_throw_error_denied($response);
}

$data = '';

if (!empty($_REQUEST['data'])) {
    $data = ApiData::parseDocument(base64_decode(rawurldecode($_REQUEST['data'])), $format);
}

$update_actions = array('update', 'update_status', 'update_info', 'delete');

if (($_SERVER['REQUEST_METHOD'] == 'POST' || $format == 'jsonp') &&  in_array($action, $update_actions)) {

    if (empty($data)) {
        $response->addError('ERROR_WRONG_DATA', __('twgadmin_wrong_api_data'));
    }

    if ($mode == 'post') {
        if ($object == 'profile') {
            $user_data = fn_twg_get_api_data($response, $format);

            $user_data['ship_to_another'] = empty($user_data['copy_address']) ? 'Y' : '';
            if (empty($user_data['ship_to_another'])) {
                $profile_fields = fn_get_profile_fields('O');
                fn_fill_address($user_data, $profile_fields);
            }
            if (isset($user_data['fields']) && is_array($user_data['fields'])) {
                $user_data['fields'] = array_filter($user_data['fields'], 'fn_twg_filter_profile_fields');
            }
            $old_user_data = fn_get_user_info($user_data['user_id']);
            if (isset($old_user_data['company_id'])) {
                $user_data['company_id'] = $old_user_data['company_id'];
            }

            $result = fn_update_user($user_data['user_id'], $user_data, $auth, $user_data['ship_to_another'], false);

            if ($result) {
                fn_set_notification('N', '', fn_twg_get_lang_var('twgadmin_saved'));
            } else {
                if (!fn_twg_set_internal_errors($response, 'ERROR_FAIL_CREATE_USER')) {
                    $response->addError('ERROR_FAIL_CREATE_USER', __('twgadmin_fail_create_user'));
                }
                $response->returnResponse();
            }
            $profile = fn_twg_get_user_info($user_data['user_id']);
            $response->setData($profile);

        } elseif  ($object == 'orders' && !empty($data['order_id'])) {

            if ($action == 'update') {
                if (!fn_twg_check_permissions('orders', 'update_status', $auth) && isset($data['status'])) {
                    unset($data['status']);
                }
                TwigmoOrder::apiUpdateOrder($data, $response);

            } elseif ($action == 'update_status' && !empty($data['status'])) {
                TwigmoOrder::apiUpdateOrder(array('order_id' => $data['order_id'], 'status' => $data['status']), $response);

            } elseif ($action == 'update_info') {
                $order_data = array('order_id' => $data['order_id'], 'details' => $data['details'], 'notes' => $data['notes']);
                TwigmoOrder::apiUpdateOrder($order_data, $response);
            }
            fn_set_notification('N', '', fn_twg_get_lang_var('twgadmin_saved'));

        } elseif ($object == 'products' && $action == 'update') {
            foreach ($data as $product) {
                if (!empty($product['product_id'])) {
                    $_REQUEST['update_all_vendors'] = $product['update_all_vendors'];
                    fn_update_product($product, $product['product_id'], $lang_code);
                    fn_set_notification('N', '', fn_twg_get_lang_var('twgadmin_saved'));
                } else {
                    $response->addError('ERROR_WRONG_OBJECT_DATA', str_replace('[object]', 'products', __('twgadmin_wrong_api_object_data')));
                }
            }

        } elseif ($object == 'images' && $action == 'delete') {
            foreach ($data as $image) {
                if (empty($image['pair_id'])) {
                    $response->addError('ERROR_WRONG_OBJECT_DATA', str_replace('[object]', 'images', __('twgadmin_wrong_api_object_data')));
                    continue;
                }
                fn_delete_image_pair($image['pair_id'], 'product');
            }
        }

        $response->returnResponse();
    }
}

if ($mode == 'post') {
    if ($action == 'auth.svc') {
        $connector = new TwigmoConnector();
        $request = $connector->parseResponse($_REQUEST['data']);
        if (!$connector->responseIsOk($request) || empty($request['data']['user_login']) || empty($request['data']['password'])) {
            $connector->onError();
        }

        $_POST = $_REQUEST = array_merge($_REQUEST, $request['data']);

        list($status, $user_data, $user_login, $password, $salt) = fn_auth_routines($_REQUEST, $auth);
        $redirect_to_mv_url = fn_twg_check_for_vendor_url($status, $user_data);
        if ($redirect_to_mv_url) {
            $status = true;
        }

        $is_ok = !empty($user_data) && !empty($password) && fn_generate_salted_password($password, $salt) == $user_data['password'];

        if ($status === false || !$is_ok) {
            $connector->onError();
        }
        $response_data = array(
            'redirect_to_mv_url' => $redirect_to_mv_url,
            'company_id' => $user_data['company_id'],
            'can_view_orders' => fn_check_user_access($user_data['user_id'], 'view_orders')
        );
        $connector->respond($response_data);

    } elseif ($action == 'auth.app') {
        $_POST['password'] = $_REQUEST['password'];
        list($status, $user_data, $user_login, $password, $salt) = fn_auth_routines($_REQUEST, $auth);
        $redirect_to_mv_url = fn_twg_check_for_vendor_url($status, $user_data);
        if ($redirect_to_mv_url) {
            $response->setData(array('status' => 'ok'));
            $response->setData(array('redirect_to_mv_url' => $redirect_to_mv_url));
            $response->returnResponse();
        }

        $is_ok = !empty($user_data) && !empty($password) && fn_generate_salted_password($password, $salt) == $user_data['password'];

        if ($status === false || !$is_ok) {
            fn_twg_throw_error_denied($response, 'error_incorrect_login');
        }

        if ($user_data['user_type'] == 'A' && $user_data['company_id']) {
            $store_access_id = TwigmoSettings::get('customer_connections.' . $user_data['company_id'] . '.access_id');
            if (!$store_access_id || $store_access_id != $_REQUEST['access_id']) {
                fn_twg_throw_error_denied($response, 'twgadmin_auth_fail_access_id');
            }
        }

        // Regenerate session_id for security reasons
        Session::regenerateId();
        fn_login_user($user_data['user_id']);
        fn_set_session_data(AREA . '_user_id', $user_data['user_id'], COOKIE_ALIVE_TIME);
        fn_set_session_data(AREA . '_password', $user_data['password'], COOKIE_ALIVE_TIME);
        // Set last login time
        db_query("UPDATE ?:users SET ?u WHERE user_id = ?i", array('last_login' => TIME), $user_data['user_id']);

        $_SESSION['auth']['this_login'] = TIME;
        $_SESSION['auth']['ip'] = $_SERVER['REMOTE_ADDR'];
        $auth = $_SESSION['auth'];

        // Log user successful login
        fn_log_event('users', 'session', array(
            'user_id' => $user_data['user_id'],
            'company_id' => fn_get_company_id('users', 'user_id', $user_data['user_id']),
        ));
        fn_init_company_id($_REQUEST);
        fn_init_company_data($_REQUEST);
        $response->setData(array('status' => 'ok'));
        $response->setData(array('settings' => fn_twg_get_admin_settings($auth)));
        $response->returnResponse();

    } elseif ($action == 'get') {
        $object_name = '';
        $condition = array();
        $options = array('lang_code' => $lang_code);
        $result = array();
        $is_paginate = false;
        $total_items = 0;
        $items_per_page = !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : TWG_RESPONSE_ITEMS_LIMIT;

        if ($object == 'timeline') {
            list($logs, $pagination_params) = fn_twg_get_logs($_REQUEST);
            $response->setData($logs);
            fn_twg_set_response_pagination($response, $pagination_params);

        } elseif ($object == 'dashboard') {
            $permissions = fn_twg_get_admin_permissions($auth);
            $data = array();
            if ($permissions['view_catalog']) {
                list($data['products']) = fn_twg_api_get_products(array('amount_to' => 10), 7, $lang_code);
                $data['products_stats'] = fn_twg_get_product_stats();
            }
            if ($permissions['view_orders']) {
                $data['orders'] = fn_twg_get_latest_orders($lang_code);
            }
            if ($permissions['view_reports']) {
                $data['summary_stats'] = fn_twg_get_summary_stats();
            }
            if ($permissions['view_users']) {
                $data['users_stats'] = fn_twg_get_user_stats();
            }
            if ($permissions['view_logs']) {
                list($logs, $pagination_params) = fn_twg_get_logs();
                $data['timeline'] = array($logs, $pagination_params);
            }
            $response->setData($data);
            $is_paginate = true;

        } elseif ($object == 'users') {
            $_REQUEST['user_type'] = 'C';

            if (empty($_REQUEST['page'])) {
                $_REQUEST['page'] = 1;
            }

            list($users, $search) = fn_get_users($_REQUEST, $auth, $items_per_page);

            $total_items = $search['total_items'];

            if (empty($users)) {
                $response->returnResponse();
            }

            $response->setResponseList(Api::getAsList($object, $users));
            $is_paginate = true;

        } elseif ($object == 'orders') {
            $_REQUEST['compact'] = 'Y';
            if (!empty($_REQUEST['sname'])) {
                $_REQUEST['cname'] = $_REQUEST['email'] = $_REQUEST['order_id'] = $_REQUEST['sname'];
            }

            if (!empty($_REQUEST['status'])) {
                $_REQUEST['status'] = unserialize($_REQUEST['status']);
            }

            list($orders, $search) = fn_get_orders($_REQUEST, $items_per_page);
            $total_items = $search['total_items'];

            if (empty($orders)) {
                $response->returnResponse();
            }

            $response->setResponseList(TwigmoOrder::getOrdersAsApiList($orders, $lang_code));
            $is_paginate = true;

        } elseif ($object == 'products') {
            $params = $_REQUEST;
            if (isset($params['view_id']) && $params['view_id'] == -1) { // Low stock
                unset($params['view_id']);
                $params['amount_to'] = 10;
            }
            fn_twg_set_response_products($response, $params, $items_per_page, $lang_code);
        }

        if ($is_paginate) {
            if (empty($pagination_params)) {
                $pagination_params = array(
                    'items_per_page' => !empty($items_per_page)? $items_per_page : TWG_RESPONSE_ITEMS_LIMIT,
                    'page' => !empty($_REQUEST['page'])? $_REQUEST['page'] : 1,
                    'total_items' => !empty($total_items)? $total_items : 0
                );
            }

            fn_twg_set_response_pagination($response, $pagination_params);
        }

        $response->returnResponse($object);
    }

    if ($action == 'details') {

        if (empty($_REQUEST['id'])) {
            $response->addError('ERROR_WRONG_OBJECT_DATA', str_replace('[object]', $object, __('twgadmin_wrong_api_object_data')));
            $response->returnResponse();
        }

        if ($object == 'orders') {
            $order = TwigmoOrder::getOrderInfo($_REQUEST['id']);
            if (empty($order)) {
                $response->addError('ERROR_OBJECT_WAS_NOT_FOUND', str_replace('[object]', $object, __('twgadmin_object_was_not_found')));
                $response->returnResponse();
            }

            $response->setData($order);
            $response->returnResponse('order');

        } elseif ($object == 'products') {
            $product = fn_twg_get_api_product_data($_REQUEST['id'], $lang_code);

            if (empty($product)) {
                $response->addError('ERROR_OBJECT_WAS_NOT_FOUND', str_replace('[object]', $object, __('twgadmin_object_was_not_found')));
                $response->returnResponse();
            }

            $response->setData($product);
            $response->returnResponse('product');

        }  elseif ($object == 'users') {
            if (fn_allowed_for('ULTIMATE')) {
                $controller = 'profiles';
                Registry::set('runtime.controller', 'profiles');
                if (!fn_ult_check_store_permission(array('user_id' => $_REQUEST['id']), $controller)) {
                    $notification = reset(fn_get_notifications());
                    $response->addError('ERROR_OBJECT_WAS_NOT_FOUND', $notification['message']);
                    $response->returnResponse();
                }
            }
            $user_data = fn_twg_get_user_info($_REQUEST['id']);
            $response->setData($user_data);
            $response->returnResponse();
        }
    }
}

function fn_twg_get_logs($params = array())
{
    $items_per_page = TWG_RESPONSE_ITEMS_LIMIT;

    $page = empty($params['page']) ? 1 : $params['page'];

    $condition = db_quote(" WHERE type IN('users',  'products',  'orders') AND action != 'session'");
    if (Registry::get('runtime.company_id')) {
        $condition .= db_quote(" AND ?:logs.company_id = ?i", Registry::get('runtime.company_id'));
    }

    $limit = '';
    if (!empty($items_per_page)) {
        $total = db_get_field("SELECT COUNT(DISTINCT(?:logs.log_id)) FROM ?:logs ?p", $condition);
        $limit = db_paginate($page, $items_per_page);
    }

    $data = db_get_array("SELECT * FROM ?:logs ?p ORDER BY log_id desc $limit", $condition);

    foreach ($data as $k => $v) {
        $data[$k]['backtrace'] = !empty($v['backtrace']) ? unserialize($v['backtrace']) : array();
        $data[$k]['content'] = !empty($v['content']) ? unserialize($v['content']) : array();
    }

    $params = array(
        'page' => $page,
        'items_per_page' => $items_per_page,
        'total_items' => $total,
        'total_pages' => ceil((int)$total / $items_per_page)
    );

    return array($data, $params);
}

function fn_twg_get_summary_stats()
{
    $periods = array(
        'day' => array(
            'periods' => array(),
            'totals' => array('current' => 0, 'previous' => 0),
            'percentage' => 0,
            'intervals' => 6,
        ),
        'week' => array(
            'periods' => array(),
            'totals' => array('current' => 0, 'previous' => 0),
            'percentage' => 0,
            'intervals' => 7,
        ),
        'month' => array(
            'periods' => array(),
            'totals' => array('current' => 0, 'previous' => 0),
            'percentage' => 0,
            'intervals' => 5,
        ),
    );

    $shifts = array(
        'day' => 14400,   // 4 hours
        'week' => 86400,  // 1 day
        'month' => 604800 // 1 week
    );

    $boundaries = array(
        'day' => array(
            'current' => strtotime('midnight', TIME),
            'previous' => strtotime('yesterday midnight', TIME)
        ),
        'week' => array(
            'current' => strtotime('this week midnight', TIME),
            'previous' => strtotime('previous week midnight', TIME)
        ),
        'month' => array(
            'current' => strtotime('first day of this month midnight', TIME),
            'previous' => strtotime('first day of previous month midnight', TIME)
        )
    );

    $query = "SELECT SUM(IF(status IN('C', 'P'), total, 0)) as total_paid, "
        . "SUM(total) as total, COUNT(order_id) as order_amount "
        . "FROM ?:orders WHERE timestamp >= ?i AND timestamp <= ?i"
        . fn_get_company_condition('?:orders.company_id');
    foreach ($periods as $period_name => &$period_data) {
        for ($i = 0; $i < $period_data['intervals']; $i++) {
            $current = db_get_row($query, $boundaries[$period_name]['current'], $boundaries[$period_name]['current'] + $shifts[$period_name]);
            $previous = db_get_row($query, $boundaries[$period_name]['previous'], $boundaries[$period_name]['previous'] + $shifts[$period_name]);

            $boundaries[$period_name]['current'] += $shifts[$period_name];
            $boundaries[$period_name]['previous'] += $shifts[$period_name];

            $period_data['periods'][$i] = array(
                'current' => $current,
                'previous' => $previous
            );
            $period_data['totals']['current'] += $current['total_paid'];
            $period_data['totals']['previous'] += $previous['total_paid'];
        }

        $totals = $period_data['totals'];
        if ($totals['current'] == 0 && $totals['previous'] == 0) {
            continue;
        }

        $negative = $totals['previous'] > $totals['current'];
        if ($negative) {
            $max = $totals['previous'];
            $min = $totals['current'];
        } else {
            $min = $totals['previous'];
            $max = $totals['current'];
        }

        if ($min == 0) {
            $period_data['percentage'] = $negative ? '-100' : '100';
        } else {
            $percentage = 100 - ($max * 100 / $min);
            if (($negative && $percentage > 0) || (!$negative && $percentage < 0)) {
                $percentage = -$percentage;
            }
            $period_data['percentage'] = "$percentage";
        }

    }

    return $periods;
}

function fn_twg_check_for_vendor_url($status, $user_data)
{
    $mv_url = '';
    if (fn_allowed_for('MULTIVENDOR') && !$status  && !empty($user_data['status']) && $user_data['user_type'] == 'V' && $user_data['status'] == 'A') {
        // It is failed mv auth - redirect it to the mv url
        $mv_url = fn_url('twigmo.post', 'V');
    }
    return $mv_url;
}

function fn_twg_get_product_stats_by_params($params = array())
{
    $default_params = array(
        'only_short_fields' => true,
        'extend' => array('companies', 'sharing'),
        'get_conditions' => true
    );
    $params = array_merge($default_params, $params);
    list($fields, $join, $condition) = fn_get_products($params);
    if (isset($params['product_type'])) {
        $condition .= db_quote(' AND products.product_type=?s', $params['product_type']);
    }
    db_query('SELECT SQL_CALC_FOUND_ROWS 1 FROM ?:products AS products' . $join . ' WHERE 1 ' . $condition . 'GROUP BY products.product_id');
    return db_get_found_rows();
}

function fn_twg_get_product_stats()
{
    $product_stats = array();

    $product_stats['total'] = fn_twg_get_product_stats_by_params();
    $product_stats['disabled'] = fn_twg_get_product_stats_by_params(array('status' => 'D'));
    $product_stats['active'] = $product_stats['total'] - $product_stats['disabled'];

    $product_stats['configurable'] = fn_twg_get_product_stats_by_params(array('product_type' => 'C'));
    $product_stats['downloadable'] = fn_twg_get_product_stats_by_params(array('downloadable' => 'Y'));
    $product_stats['free_shipping'] = fn_twg_get_product_stats_by_params(array('free_shipping' => 'Y'));

    return $product_stats;
}

function fn_twg_get_user_stats()
{
    $users_company_condition = fn_get_company_condition('?:users.company_id');
    $sql = db_quote('SELECT COUNT(*) FROM ?:users WHERE 1 ?p ', $users_company_condition);
    $users_stats = array(
        'customers' =>  db_get_field($sql . 'AND user_type = ?s', 'C'),
        'total' =>      db_get_field($sql),
        'disabled' =>   db_get_field($sql . 'AND status = ?s', 'D')
    );
    return $users_stats;
}

function fn_twg_get_latest_orders($lang_code)
{
    list($orders, $search) = fn_get_orders(array('sort_by' => 'date', 'sort_order' => 'desc'), 7);
    $orders = TwigmoOrder::getOrdersAsApiList($orders, $lang_code);
    return array('orders' => $orders, 'total' => $search['total_items']);
}

function fn_twg_get_statuses()
{
    $status_types = array(
        'orders' =>     fn_get_statuses(STATUSES_ORDER),
        'products' =>   fn_twg_api_get_base_statuses(true),
        'categories' => fn_twg_api_get_base_statuses(true),
        'users' =>      fn_twg_api_get_base_statuses(false)
    );

    foreach ($status_types as &$status_type) {
        foreach ($status_type as &$status) {
            if (isset($status['color'])) {
                $color = $status['color'];
            } elseif (isset($status['params']['color'])) {
                $color = str_replace('#', '', $status['params']['color']);
            } else {
                $color = '666666';
            }
            $status = array(
                'label' => $status['description'],
                'value' => $status['status'],
                'color' => $color
            );
        }
    }

    return $status_types;
}

function fn_twg_check_company_view_logs_permission()
{
    return true;
}

function fn_twg_get_admin_settings($auth)
{
    $settings = array();

    $needed_langvars = fn_twg_get_admin_langvars();

    $settings['lang'] = array();
    foreach ($needed_langvars as $needed_langvar) {
        $settings['lang'][$needed_langvar] = __($needed_langvar);
    }
    $settings['lang'] = array_merge($settings['lang'], Lang::getLangVarsByPrefix('twapp'));
    $settings['lang'] = fn_twg_process_langvars($settings['lang']);

    $settings['statuses'] = fn_twg_get_statuses();

    $settings['profileFields'] = fn_twg_prepare_profile_fields(fn_get_profile_fields('O'), false);

    list($settings['countries']) = fn_get_countries(array('only_avail' => true));
    $settings['states'] = fn_twg_get_states();
    $settings['titles'] = array();
    $settings['saved_searches'] = fn_twg_get_searches($auth);
    $settings = array_merge($settings, fn_twg_get_checkout_settings());
    $settings['use_email_as_login'] = Registry::get('settings.General.use_email_as_login');
    $settings['currency'] = Registry::get('currencies.' . CART_PRIMARY_CURRENCY);
    $settings['use_email_as_login'] = Registry::get('settings.General.use_email_as_login');
    $settings['time_format'] = Registry::get('settings.Appearance.time_format');
    $settings['date_format'] = Registry::get('settings.Appearance.date_format');
    $settings['languages'] = fn_twg_get_languages();
    $settings['cart_language'] = CART_LANGUAGE;
    $settings['descr_sl'] = DESCR_SL;
    $settings['permissions'] = fn_twg_get_admin_permissions($auth);
    $settings['runtime_company_id'] = Registry::get('runtime.company_id');
    $settings['user_company_id'] = isset($auth['company_id']) ? $auth['company_id'] : $settings['user_company_id'];
    $settings_company_name = Registry::get('settings.Company.company_name');
    $runtime_company_name = Registry::get('runtime.company_data.company');
    $settings['company_name'] =  $runtime_company_name ? $runtime_company_name : $settings_company_name;
    $settings['storefront_url'] =  Registry::get('runtime.company_data.storefront');
    if (Registry::get('runtime.companies_available_count') > 1) {
        $settings['companies'] = fn_twg_get_admin_companies(PRODUCT_EDITION == 'ULTIMATE', 0);
    } else {
        $settings['companies'] = false;
    }

    return $settings;

}
