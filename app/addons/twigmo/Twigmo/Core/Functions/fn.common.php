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


if ( !defined('AREA') ) { die('Access denied'); }

function fn_twigmo_init_secure_controllers(&$controllers) { // Hook
    $controllers['twigmo'] = 'passive';
}

function fn_twg_check_requirements()
{
    $errors = array();
    if (!function_exists('hash_hmac')){
        $errors[] = str_replace('[php_module_name]', 'Hash', fn_twg_get_lang_var('twgadmin_phpmod_required'));
    }
    return $errors;
}

// Check if the twigmo can render page with the specific dispatch
function fn_twg_is_supported_dispatch($dispatch)
{
    $supported_dispatches = array(
        'index.index',
        'categories.view',
        'products.view',
        'checkout.cart',
        'checkout.checkout',
        'orders.search',
        'orders.details',
        'checkout.complete',
        'profiles.add',
        'profiles.update',
        'products.search',
        'pages.view',
        'reward_points.userlog'
    );
    return in_array($dispatch, $supported_dispatches);
}

function fn_twg_get_languages()
{
    $include_hidden = AREA == 'A';
    if (function_exists('fn_get_languages')) {
        $languages = fn_get_languages($include_hidden);
    } else {
        $languages = Languages::getAvailable(AREA, $include_hidden);
    }
    foreach ($languages as &$language) {
        $language['value'] = $language['lang_code'];
        $language['label'] = $language['name'];
    }
    return array_values($languages);

}

function fn_twg_get_searches($auth)
{
    $query = "SELECT view_id, object, name FROM ?:views WHERE object IN ('orders', 'products', 'users') AND user_id = ?i";
    $objects = db_get_hash_multi_array($query, array('object', 'view_id'), $auth['user_id']);

    // orders
    if (!isset($objects['orders'])) {
        $objects['orders'] = array();
    }

    array_unshift($objects['orders'], array('name' => fn_twg_get_lang_var('all')));

    // products
    if (!isset($objects['products'])) {
        $objects['products'] = array();
    }
    array_unshift($objects['products'], array('name' => fn_twg_get_lang_var('all')));
    array_push($objects['products'], array('name' => fn_twg_get_lang_var('twapp_low_stock'), 'view_id' => -1));

    // users
    if (!isset($objects['users'])) {
        $objects['users'] = array();
    }
    array_unshift($objects['users'], array('name' => fn_twg_get_lang_var('all')));
    return $objects;
}

function fn_twg_send_order_push_notification($order_id, $connector)
{
    $twigmo_requirements_errors = fn_twg_check_requirements();
    if (!empty($twigmo_requirements_errors) || !$connector->frontendIsConnected()) {
        return;
    }

    $order_data = db_get_row('SELECT is_parent_order, company_id FROM ?:orders WHERE order_id=?i', $order_id);
    if (empty($order_data) || $order_data['is_parent_order'] == 'Y') {
        return;
    }

    $connector->show_notifications = false;
    $twigmo_is_used = (isset($_SESSION ['twg_state']) && $_SESSION['twg_state']['twg_is_used']) ? 'Y' : 'N';
    $data = array(
        'access_id' => $connector->getAccessID('C'),
        'order_id' => $order_id,
        'twg_is_used' => $twigmo_is_used,
        'company_id' => $order_data['company_id']
    );
    $meta = array('access_id' => $connector->getAccessID('A'));
    $connector->send('order.placed', $data, $meta);
}

function fn_twg_get_admin_langvars()
{
    return array('in_stock', 'uc_ok', 'sign_in', 'included', 'twg_msg_field_required', 'twgadmin_access_id', 'select_country',
        'select_state', 'twg_lbl_copy_from_billing', 'twg_billing_is_the_same', 'no_users_found', 'text_no_products_found',
        'text_no_orders', 'users_statistics', 'product_inventory', 'latest_orders', 'view_all_orders', 'this_week', 'this_month',
        'previous_week', 'previous_month', 'twg_msg_fill_required_fields', 'twg_msg_field_required', 'update', 'create', 'change', 'user',
        'order', 'product', 'log_action_failed_login', 'manage_accounts', 'twg_lbl_out_of_stock', 'user_account_info', 'email', 'username',
        'password', 'confirm_password', 'tracking_num', 'tracking_number', 'profile', 'date', 'order_id', 'tax_exempt', 'payment_surcharge',
        'payment_method', 'free_shipping', 'shipping_cost', 'including_discount', 'order_discount', 'taxes', 'order_details',
        'customer_info', 'customer_notes', 'staff_only_notes', 'order', 'name', 'product_code', 'account', 'email', 'login',
        'email_invalid', 'digits_required', 'error_passwords_dont_match', 'yes', 'contact_information', 'billing_address',
        'shipping_address', 'timeline', 'fax', 'zipcode', 'company', 'customers', 'billing', 'shipping', 'total', 'subtotal', 'discount',
        'disabled', 'month', 'save', 'edit', 'create', 'update', 'products', 'store', 'cancel', 'password', 'delete', 'account', 'ok',
        'back', 'stats', 'dashboard', 'add', 'all', 'new', 'currencySymbol', 'active', 'search', 'by', 'orders', 'week', 'day', 'address',
        'information', 'firstName', 'lastName', 'email', 'phone', 'today', 'yesterday', 'configurable', 'downloadable', 'loading', 'title',
        'code', 'category', 'quantity', 'price', 'status', 'city', 'state', 'zip', 'country', 'twgadmin_wrong_api_data', 'no_data', 'or'
    );
}

function fn_twg_throw_error_denied($response, $lang_var = 'access_denied')
{
    $response->addError('ERROR_ACCESS_DENIED', fn_twg_get_lang_var($lang_var));
    $response->returnResponse();
}

function fn_twg_check_user_access($auth, $action)
{
    static $usergroup_privileges;

    $has_access = fn_check_user_access($auth['user_id'], $action);
    if ($has_access && !empty($auth['usergroup_ids'])) {
        if (empty($usergroup_privileges)) {
            $usergroup_privileges = db_get_fields("SELECT privilege FROM ?:usergroup_privileges WHERE usergroup_id IN(?n)", $auth['usergroup_ids']);
            $usergroup_privileges = (empty($usergroup_privileges)) ? 'EMPTY' : 'NOT_EMPTY';
        }
        if ($usergroup_privileges === 'EMPTY') {
            $has_access = false;
        }
    }
    return $has_access;
}

function fn_twg_get_admin_permissions($auth)
{
    $controller_schema = fn_get_schema('twg_permissions', 'controllers');
    $actions = array_unique(array_values($controller_schema));
    $permissions = array();

    foreach($actions as $action) {
        $permissions[$action] = fn_twg_check_user_access($auth, $action);
        if ($action == 'view_logs' && $permissions[$action]) {
            $permissions[$action] = fn_twg_check_company_view_logs_permission();
        }
    }
    return $permissions;
}

// Check if current user has permission for action with object
function fn_twg_check_permissions($object, $action, $auth)
{
    $controller_schema = fn_get_schema('twg_permissions', 'controllers');
    $schema_key = "$object.$action";
    $has_access = false;
    if (isset($controller_schema[$schema_key])) {
        if (!empty($auth['user_id'])) {
            $has_access = fn_twg_check_user_access($auth, $controller_schema[$schema_key]);
            if ($controller_schema[$schema_key] == 'view_logs' && $has_access) {
                $has_access = fn_twg_check_company_view_logs_permission();
            }
        }
    } else {
        $trusted_actions = array('auth.svc', 'auth.app');
        if (in_array($action, $trusted_actions) || !empty($auth['user_id']) && $object == 'dashboard') {
            $has_access = true;
        }
    }
    return $has_access;
}

function fn_twg_get_admin_companies($get_urls, $all_companies_value = 0)
{
    $fields = 'c.company_id AS value, c.company AS name, ts.access_id';
    if ($get_urls) {
        $fields .= ', c.storefront as url';
    }
    $query = 'SELECT ' . $fields . ' FROM ?:companies as c
            LEFT JOIN ?:twigmo_stores AS ts ON ts.company_id = c.company_id AND ts.type = ?s ORDER BY c.company';
    $companies = db_get_array($query, 'C');
    array_unshift($companies, array('name' => fn_twg_get_lang_var('all_vendors'), 'value' => $all_companies_value));
    return $companies;
}

function fn_twg_get_connected_access_id($auth)
{
    $stores = fn_twg_get_stores($auth);
    if (empty($stores)) {
        return '';
    }

    foreach ($stores as $store) {
        if ($store['is_connected']) {
            return $store['access_id'];
        }
    }

    return '';
}

function fn_twg_get_users_search_condition($params)
{
    if (empty($params['twg_search'])) {
        return '';
    }

    $pieces = fn_explode(' ', trim($params['twg_search']));
    $condition = array();
    foreach ($pieces as $piece) {
        if (strlen($piece) == 0) {
            continue;
        }

        $tmp = db_quote("?:users.email LIKE ?l", "%$piece%");
        $tmp .= db_quote(" OR ?:users.user_login LIKE ?l", "%$piece%");
        $tmp .= db_quote(" OR (?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)", "%$piece%", "%$piece%");

        $condition[] = '(' . $tmp . ')';
    }
    return empty($condition) ? '' : ' AND (' . implode(' AND ', $condition) . ') ';
}

function fn_twg_get_reward_points_userlog($params)
{
    $default_params = array (
        'page' => 1,
        'items_per_page' => !empty($params['items_per_page']) ? $params['items_per_page'] : 0
    );

    $params = array_merge($default_params, $params);

    $sortings = array (
        'timestamp' => 'timestamp',
        'amount' => 'amount'
    );

    $sorting = db_twg_sort($params, $sortings, 'timestamp', 'desc');

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:reward_point_changes WHERE user_id = ?i", $params['user_id']);
        $limit = db_twg_paginate($params['page'], $params['items_per_page']);
    }

    $fields = 'change_id, action, timestamp, amount, reason';

    $userlog = db_get_array(
        "SELECT $fields  FROM ?:reward_point_changes WHERE user_id = ?i $sorting $limit",
        $params['user_id']
    );

    return array($userlog, $params);
}

function db_twg_sort(&$params, $sortings, $default_by = '', $default_order = '')
{
    $directions = array (
        'asc' => 'desc',
        'desc' => 'asc',
        'descasc' => 'ascdesc', // when sorting by 2 fields
        'ascdesc' => 'descasc' // when sorting by 2 fields
    );

    if (empty($params['sort_order']) || empty($directions[$params['sort_order']])) {
        $params['sort_order'] = $default_order;
    }

    if (empty($params['sort_by']) || empty($sortings[$params['sort_by']])) {
        $params['sort_by'] = $default_by;
    }

    $params['sort_order_rev'] = $directions[$params['sort_order']];

    if (is_array($sortings[$params['sort_by']])) {
        if ($params['sort_order'] == 'descasc') {
            $order = implode(' desc, ', $sortings[$params['sort_by']]) . ' asc';
        } elseif ($params['sort_order'] == 'ascdesc') {
            $order = implode(' asc, ', $sortings[$params['sort_by']]) . ' desc';
        } else {
            $order = implode(' ' . $params['sort_order'] . ', ', $sortings[$params['sort_by']]) . ' ' . $params['sort_order'];
        }
    } else {
        $order = $sortings[$params['sort_by']] . ' ' . $params['sort_order'];
    }

    return ' ORDER BY ' . $order;
}

/**
 * Paginate query results
 *
 * @param int $page page number
 * @param int $items_per_page items per page
 * @return string SQL substring
 */
function db_twg_paginate($page, $items_per_page)
{
    $page = intval($page);
    if (empty($page)) {
        $page  = 1;
    }

    $items_per_page = intval($items_per_page);

    return ' LIMIT ' . (($page - 1) * $items_per_page) . ', ' . $items_per_page;
}

/**
 * Get simple statuses description (P - Processed, O - Open)
 * @param string $type One letter status type
 * @param boolean $additional_statuses Flag that determines whether additional (hidden) statuses should be retrieved
 * @param boolean $exclude_parent Flag that determines whether parent statuses should be excluded
 * @param string $lang_code Language code
 * @return array Statuses
 */
function fn_twg_get_simple_statuses($type = STATUSES_ORDER, $additional_statuses = false, $exclude_parent = false, $lang_code = DESCR_SL)
{
    $statuses = db_get_hash_single_array(
        "SELECT a.status, b.description"
        . " FROM ?:statuses as a"
        . " LEFT JOIN ?:status_descriptions as b ON b.status = a.status AND b.type = a.type AND b.lang_code = ?s"
        . " WHERE a.type = ?s",
        array('status', 'description'),
        $lang_code, $type
    );
    if ($type == STATUSES_ORDER && !empty($additional_statuses)) {
        $statuses['N'] = fn_get_lang_var('incompleted', '', $lang_code);
        if (empty($exclude_parent)) {
            $statuses[STATUS_PARENT_ORDER] = fn_get_lang_var('parent_order', '', $lang_code);
        }
    }

    return $statuses;
}

// Reward points
function fn_twg_calculate_product_price_in_points(&$product, &$auth, $get_point_info = true)
{
    if (isset($product['exclude_from_calculate']) || floatval($product['price']) == 0 || $get_point_info == false) {
        return false;
    }

    if (isset($product['subtotal'])) {
        if (Registry::get('addons.reward_points.auto_price_in_points') == 'Y' && $product['is_oper'] == 'N') {
            $per = Registry::get('addons.reward_points.point_rate');

            if (Registry::get('addons.reward_points.price_in_points_with_discounts') == 'Y' && !empty($product['subtotal'])) {
                $subtotal = $product['subtotal'];
            } else {
                $subtotal = $product['price'] * $product['amount'];
            }
        } else {
            $per = (!empty($product['original_price']) && floatval($product['original_price'])) ? fn_get_price_in_points($product['product_id'], $auth) / $product['original_price'] : 0;
            $subtotal = $product['original_price'] * $product['amount'];
        }
    } else {
        if (Registry::get('addons.reward_points.auto_price_in_points') == 'Y' && $product['is_oper'] == 'N') {
            $per = Registry::get('addons.reward_points.point_rate');

            if (Registry::get('addons.reward_points.price_in_points_with_discounts') == 'Y' && isset($product['discounted_price'])) {
                $subtotal = $product['discounted_price'];
            } else {

                $subtotal = $product['price'];
            }
        } else {
            $per = (!empty($product['price']) && floatval($product['price'])) ? fn_get_price_in_points($product['product_id'], $auth) / $product['price'] : 0;
            $subtotal = $product['price'];
        }
    }

    $product['points_info']['raw_price'] = $per * $subtotal;
    $product['points_info']['price'] = round($product['points_info']['raw_price']);
}

function fn_twg_gather_reward_points_data(&$product, &$auth, $get_point_info = true)
{
    // Check, if the product has any option points modifiers
    if (empty($product['options_update']) && !empty($product['product_options'])) {
        foreach ($product['product_options'] as $_id => $option) {
            if (!empty($product['product_options'][$_id]['variants'])) {
                foreach ($product['product_options'][$_id]['variants'] as $variant) {
                    if (!empty($variant['point_modifier']) && floatval($variant['point_modifier'])) {
                        $product['options_update'] = true;
                        break 2;
                    }
                }
            }
        }
    }

    if (isset($product['exclude_from_calculate']) || (isset($product['points_info']['reward']) && !(CONTROLLER == 'products' && MODE == 'options')) || $get_point_info == false) {
        return false;
    }

    $main_category = db_get_field("SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'M'", $product['product_id']);
    $candidates = array(
        PRODUCT_REWARD_POINTS => $product['product_id'],
        CATEGORY_REWARD_POINTS => $main_category,
        GLOBAL_REWARD_POINTS => 0
    );

    $reward_points = array();
    foreach ($candidates as $object_type => $object_id) {
        $_reward_points = fn_get_reward_points($object_id, $object_type, $auth['usergroup_ids']);

        if ($object_type == CATEGORY_REWARD_POINTS && !empty($_reward_points)) {
            // get the "override point" setting
            $category_is_op = db_get_field("SELECT is_op FROM ?:categories WHERE category_id = ?i", $_reward_points['object_id']);
        }
        if ($object_type == CATEGORY_REWARD_POINTS && (empty($_reward_points) || $category_is_op != 'Y')) {
            // if there is no points of main category of the "override point" setting is disabled
            // then get point of secondary categories
            $secondary_categories = db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'A'", $product['product_id']);

            if (!empty($secondary_categories)) {
                $secondary_categories_points = array();
                foreach ($secondary_categories as $value) {
                    $_rp = fn_get_reward_points($value, $object_type, $auth['usergroup_ids']);
                    if (isset($_rp['amount'])) {
                        $secondary_categories_points[] = $_rp;
                    }
                    unset($_rp);
                }

                if (!empty($secondary_categories_points)) {
                    $sorted_points = fn_sort_array_by_key($secondary_categories_points, 'amount', (Registry::get('addons.reward_points.several_points_action') == 'min') ? SORT_ASC : SORT_DESC);
                    $_reward_points = array_shift($sorted_points);
                }
            }

            if (!isset($_reward_points['amount'])) {
                if (Registry::get('addons.reward_points.higher_level_extract') == 'Y' && !empty($candidates[$object_type])) {
                    $id_path = db_get_field("SELECT REPLACE(id_path, '{$candidates[$object_type]}', '') FROM ?:categories WHERE category_id = ?i", $candidates[$object_type]);
                    if (!empty($id_path)) {
                        $c_ids = explode('/', trim($id_path, '/'));
                        $c_ids = array_reverse($c_ids);
                        foreach ($c_ids as $category_id) {
                            $__reward_points = fn_get_reward_points($category_id, $object_type, $auth['usergroup_ids']);
                            if (!empty($__reward_points)) {
                                // get the "override point" setting
                                $_category_is_op = db_get_field("SELECT is_op FROM ?:categories WHERE category_id = ?i", $__reward_points['object_id']);
                                if ($_category_is_op == 'Y') {
                                    $category_is_op = $_category_is_op;
                                    $_reward_points = $__reward_points;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($_reward_points) && (($object_type == GLOBAL_REWARD_POINTS) || ($object_type == PRODUCT_REWARD_POINTS && $product['is_op'] == 'Y') || ($object_type == CATEGORY_REWARD_POINTS && (!empty($category_is_op) && $category_is_op == 'Y')))) {
            // if global points or category points (and override points is enabled) or product points (and override points is enabled)
            $reward_points = $_reward_points;
            break;
        }
    }

    if (isset($reward_points['amount'])) {
        if ((defined('ORDER_MANAGEMENT') || CONTROLLER == 'checkout' || CONTROLLER == 'twigmo') && isset($product['subtotal']) && isset($product['original_price'])) {
            if (Registry::get('addons.reward_points.points_with_discounts') == 'Y' && $reward_points['amount_type'] == 'P' && !empty($product['discounts'])) {
                $product['discount'] = empty($product['discount']) ? 0 : $product['discount'];
                $reward_points['coefficient'] = (floatval($product['price'])) ? (($product['price'] * $product['amount'] - $product['discount']) / $product['price'] * $product['amount']) / pow($product['amount'], 2) : 0;
            } else {
                $reward_points['coefficient'] = 1;
            }
        } else {
            $reward_points['coefficient'] = (Registry::get('addons.reward_points.points_with_discounts') == 'Y' && $reward_points['amount_type'] == 'P' && isset($product['discounted_price'])) ? $product['discounted_price'] / $product['price'] : 1;
        }

        if (isset($product['extra']['configuration'])) {
            if ($reward_points['amount_type'] == 'P') {
                // for configurable product calc reward points only for base price
                $price = $product['original_price'];
                if (!empty($product['discount'])) {
                    $price -= $product['discount'];
                }
                $reward_points['amount'] = $price * $reward_points['amount'] / 100;
            } else {
                $points_info = Registry::get("runtime.product_configurator.points_info");
                if (!empty($points_info[$product['product_id']])) {
                    $reward_points['amount'] = $points_info[$product['product_id']]['reward'];
                    $reward_points['coefficient'] = 1;
                }
            }
        } else {
            if ($reward_points['amount_type'] == 'P') {
                $reward_points['amount'] = $product['price'] * $reward_points['amount'] / 100;
            }
        }

        $reward_points['raw_amount'] = $reward_points['coefficient'] * $reward_points['amount'];
        $reward_points['raw_amount'] = !empty($product['selected_options']) ? fn_apply_options_modifiers($product['selected_options'], $reward_points['raw_amount'], POINTS_MODIFIER_TYPE) : $reward_points['raw_amount'];

        $reward_points['amount'] = round($reward_points['raw_amount']);
        $product['points_info']['reward'] = $reward_points;
    }

    fn_twg_calculate_product_price_in_points($product, $auth, $get_point_info);
}

// /Reward points
