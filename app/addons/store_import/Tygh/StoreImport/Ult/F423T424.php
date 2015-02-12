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

namespace Tygh\StoreImport\Ult;
use Tygh\StoreImport\General;
use Tygh\Registry;
use Tygh\Addons\SchemesManager as AddonSchemesManager;

class F423T424
{
    protected $store_data = array();
    protected $main_sql_filename = 'ult_F423T424.sql';

    public function __construct($store_data)
    {
        $store_data['product_edition'] = 'ULTIMATE';
        $this->store_data = $store_data;
    }

    public function import($db_already_cloned)
    {
        General::setProgressTitle(__CLASS__);
        if (!$db_already_cloned) {
            if (!General::cloneImportedDB($this->store_data)) {
                return false;
            }
        } else {
            General::setEmptyProgressBar(__('importing_data'));
            General::setEmptyProgressBar(__('importing_data'));
        }

        General::connectToOriginalDB(array('table_prefix' => General::formatPrefix()));
        General::processAddons($this->store_data, __CLASS__);

        $main_sql = Registry::get('config.dir.addons') . 'store_import/database/' . $this->main_sql_filename;
        if (is_file($main_sql)) {
            //Process main sql
            if (!db_import_sql_file($main_sql)) {
                return false;
            }
        }

        db_query("INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, `type`) VALUES ('Realex Payments Remote', 'realex_remote.php', 'views/orders/components/payments/cc.tpl', 'realex_remote.tpl', 'N', 'P')");
        db_query("INSERT INTO ?:payment_processors (processor, processor_script, processor_template, admin_template, callback, `type`) VALUES ('Realex Payments Redirect', 'realex_redirect.php', 'views/orders/components/payments/cc_outside.tpl', 'realex_redirect.tpl', 'N', 'P')");
        db_query("UPDATE ?:settings_sections SET `edition_type` = 'ROOT,VENDOR' WHERE `name` = 'Security' AND `type` = 'CORE'");
        General::addIpv6Support();

        $paypal_enabled = db_get_fields("SELECT payment_id FROM ?:payments WHERE processor_id IN (SELECT processor_id FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'payflow_pro.php', 'paypal_advanced.php', 'paypal_express.php', 'paypal_pro.php'))");
        if (!empty($paypal_enabled)) {
            db_query("INSERT INTO ?:addons (`addon`, `status`, `version`, `priority`, `dependencies`, `conflicts`, `separate`, `unmanaged`, `has_icon`) VALUES ('paypal', 'A', '1.0', 100, '', '', 0, 0, 0)");
            db_query("INSERT INTO ?:settings_sections (`parent_id`, `edition_type`, `name`, `position`, `type`) VALUES (0, 'ROOT,ULT:VENDOR', 'paypal', 0, 'ADDON')");
            $section_id = db_get_field("SELECT section_id FROM ?:settings_sections WHERE name = 'paypal' AND type = 'ADDON'");
            db_query("INSERT INTO ?:settings_sections (`parent_id`, `edition_type`, `name`, `position`, `type`) VALUES ($section_id, 'ROOT,ULT:VENDOR', 'general', 0, 'TAB')");
            $parent_id = db_get_field("SELECT section_id FROM ?:settings_sections WHERE parent_id = $section_id");
            db_query("INSERT INTO ?:settings_objects (`edition_type`, `name`, `section_id`, `section_tab_id`, `type`, `value`, `position`, `is_global`, `handler`, `parent_id`) VALUES
                ('ROOT,ULT:VENDOR', 'paypal_ipn_settings', $section_id, $parent_id, 'H', '', 0, 'N', '', 0),
                ('ROOT,ULT:VENDOR', 'override_customer_info', $section_id, $parent_id, 'C', 'Y', 10, 'N', '', 0),
                ('ROOT', 'test_mode', $section_id, $parent_id, 'C', 'N', 20, 'N', '', 0),
                ('ROOT,ULT:VENDOR', 'template', $section_id, $parent_id, 'Z', 'statuses_map.tpl', 30, 'N', '', 0),
                ('ROOT,ULT:VENDOR', 'template', $section_id, $parent_id, 'Z', 'logo_uploader.tpl', 40, 'N', '', 0),
                ('ROOT,ULT:VENDOR', 'pp_statuses', $section_id, $parent_id, 'D', 'a:10:{s:8:\"refunded\";s:1:\"I\";s:9:\"completed\";s:1:\"P\";s:7:\"pending\";s:1:\"O\";s:17:\"canceled_reversal\";s:1:\"I\";s:7:\"created\";s:1:\"O\";s:6:\"denied\";s:1:\"I\";s:7:\"expired\";s:1:\"F\";s:8:\"reversed\";s:1:\"I\";s:9:\"processed\";s:1:\"P\";s:6:\"voided\";s:1:\"P\";}', 50, 'N', '', 0)
            ");
            $pp_standart_params = db_get_field("SELECT processor_params FROM ?:payments WHERE processor_id = (SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'paypal.php')");
            if (!empty($pp_standart_params)) {
                $pp_standart_params = unserialize($pp_standart_params);
                if (!empty($pp_standart_params['statuses'])) {
                    db_query("UPDATE ?:settings_objects SET value = ?s WHERE name = 'pp_statuses'", serialize($pp_standart_params['statuses']));
                    unset($pp_standart_params['statuses']);
                    $pp_standart_params = serialize($pp_standart_params);
                    db_query("UPDATE ?:payments SET processor_params = '$pp_standart_params' WHERE processor_id = (SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'paypal.php')");
                }
            }

            $languages = db_get_fields("SELECT lang_code FROM ?:languages");
            $addon_scheme = AddonSchemesManager::getScheme('paypal');
            $language_variables = $addon_scheme->getLanguageValues();
            if (!empty($language_variables)) {
                db_query('REPLACE INTO ?:language_values ?m', $language_variables);
            }
            $settings_descr = array(
                'paypal_ipn_settings' => 'Instant payment notification settings',
                'override_customer_info' => 'Override customer info',
                'test_mode' => 'Test mode',
            );
            foreach ($languages as $lang_code) {
                $description = $addon_scheme->getDescription($lang_code);
                $addon_name = $addon_scheme->getName($lang_code);
                db_query("INSERT INTO ?:addon_descriptions (addon, name, description, lang_code) VALUES ('paypal', ?s, ?s, ?s) ON DUPLICATE KEY UPDATE ?:addon_descriptions.addon = ?:addon_descriptions.addon", $addon_name, $description, $lang_code);
                foreach ($settings_descr as $setting_name => $descr) {
                    db_query("REPLACE INTO ?:settings_descriptions VALUES ((SELECT object_id FROM ?:settings_objects WHERE name = ?s), 'O', ?s, ?s, '')", $setting_name, $lang_code, $descr);
                }
            }
        } else {
            db_query("DELETE FROM ?:payment_processors WHERE processor_script IN ('paypal.php', 'payflow_pro.php', 'paypal_advanced.php', 'paypal_express.php', 'paypal_pro.php')");
        }

//        General::restoreSettings();
        if (db_get_field("SELECT status FROM ?:addons WHERE addon = 'searchanise'") != 'D') {
            db_query("UPDATE ?:addons SET status = 'D' WHERE addon = 'searchanise'");
            fn_set_notification('W', __('warning'), General::getUnavailableLangVar('uc_searchanise_disabled'));
        }

        General::setActualLangValues();
        General::updateAltLanguages('language_values', 'name');
        General::updateAltLanguages('ult_language_values', array('name', 'company_id'));
        General::updateAltLanguages('settings_descriptions', array('object_id', 'object_type'));

        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        General::setEmptyProgressBar();
        return true;
    }
}
