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

namespace Installer;

use Tygh\BlockManager\Location;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Languages\Languages;
use Tygh\Tools\Url;

class Setup
{
    const HTTP = 'http';
    const HTTPS = 'https';
    const DEMO_COMPANY_URL = 'acme';

    private $_cart_settings = array();
    private $_server_settings = array();
    private $_database_settings = array();
    private $_install_demo = true;

    public function __construct($cart_settings = array(), $server_settings = array(), $database_settings = array(), $install_demo = false)
    {
        $this->_cart_settings = $cart_settings;
        $this->_server_settings = $server_settings;
        $this->_database_settings = $database_settings;
        $this->_install_demo = $install_demo;
    }

    /**
     * Returns list of available languages to install
     *
     * @return array List of languages
     */
    public static function getLanguages()
    {
        $languages = array();

        $langs = fn_get_dir_contents(Registry::get('config.dir.install') . APP::DB_LANG, true, false);

        if (!empty($langs)) {
            foreach ($langs as $lang_code) {
                $meta = Languages::getLangPacksMeta(Registry::get('config.dir.install') . APP::DB_LANG . '/' . $lang_code . '/', $lang_code . '.po', false, false);
                $languages[$lang_code] = $meta['name'];
            }
        }

        return $languages;
    }

    /**
     * Returns list of supported DB types
     *
     * @return array List of DB types
     */
    public static function getSupportedDbTypes()
    {
        $supported = array();

        $exts  = get_loaded_extensions();
        $mysqli_support = in_array('mysqli', $exts) ? true : false;
        $pdo_support = in_array('pdo_mysql', $exts) ? true : false;

        if ($mysqli_support) {
            $supported['mysqli'] = 'MySQLi';
        }

        if ($pdo_support) {
            $supported['pdo'] = 'PDO';
        }

        return $supported;
    }

    /**
     * Imports database scheme
     *
     * @return bool Always true
     */
    public function setupScheme()
    {
        $this->_parseSql(Registry::get('config.dir.install') . App::DB_SCHEME, 'creating_scheme');

        $this->_resetParsedFilesCache();

        return true;
    }

    /**
     * Imports database data
     *
     * @return bool Always true
     */
    public function setupData()
    {
        $this->_parseSql(Registry::get('config.dir.install') . App::DB_DATA, 'importing_data');

        $this->_createAdminAccount($this->_cart_settings['email'], $this->_cart_settings['password']);

        $this->_setupAutoFeedback(!empty($this->_cart_settings['feedback_auto']));

        $this->_setupDefaultLanguages($this->_cart_settings['main_language']);

        $this->_resetParsedFilesCache();

        return true;
    }

    /**
     * Imports database demo catalog
     *
     * @return bool Always true
     */
    public function setupDemo()
    {
        $this->_parseSql(Registry::get('config.dir.install') . App::DB_DEMO, 'creating_demo');
        $this->_resetParsedFilesCache();

        return true;
    }

    /**
     * Cleans database. Need be executed if demo catalog not installed
     *
     * @return bool Always true
     */
    public function clean()
    {
        return true;
    }

    /**
     * Returns available themes from repository
     *
     * @return array List of themes with preview images
     */
    public function getAvailableThemes()
    {
        $themes = array();

        $repo_themes = fn_get_dir_contents(Registry::get('config.dir.root') . '/var/themes_repository');

        if (!empty($repo_themes)) {
            foreach ($repo_themes as $theme_name) {
                $themes[$theme_name] = 'var/themes_repository/' . $theme_name . '/customer_screenshot.png';
            }
        }

        return $themes;
    }

    /**
     * Setup themes
     *
     * @return bool True on success, false otherwise
     */
    public function setupThemes()
    {
        if (!empty($this->_cart_settings['theme_name'])) {
            if (fn_allowed_for('ULTIMATE')) {
                fn_install_theme($this->_cart_settings['theme_name'], 1);
            } else {
                fn_install_theme($this->_cart_settings['theme_name'], 0);
            }

            return true;
        }

        return false;
    }

    /**
     * Writes config co config.local.php
     */
    public function writeConfig()
    {
        $config_contents = file_get_contents(Registry::get('config.dir.root') . '/config.local.php');
        if (!empty($config_contents)) {

            $config = array(
                'db_host' => addslashes($this->_database_settings['host']),
                'db_name' => addslashes($this->_database_settings['name']),
                'db_user' => addslashes($this->_database_settings['user']),
                'db_password' => str_replace('$', '\$', addslashes($this->_database_settings['password'])),
                'table_prefix' => addslashes($this->_database_settings['table_prefix']),
                'http_host' => Url::normalizeDomain($this->_server_settings['http_host']),
                'http_path' => rtrim($this->_server_settings['http_path'], '/'),
                'https_host' => Url::normalizeDomain($this->_server_settings['https_host']),
                'https_path' => rtrim($this->_server_settings['https_path'], '/'),
                'database_backend' => addslashes($this->_database_settings['database_backend']),
                'crypt_key' =>  str_replace('$', '\$', addslashes($this->_cart_settings['secret_key']))
            );

            foreach ($config as $paramName => $value) {
                $config_contents = $this->_writeParam($paramName, $value, $config_contents);
            }

            fn_put_contents(Registry::get('config.dir.root') . '/config.local.php', $config_contents);
        }
    }

    /**
     * Changes parameter in content to new value
     *
     * @param  string $paramName Name of needed variable
     * @param  string $value     New value
     * @param  string $config    File content
     * @return string File content
     */
    private function _writeParam($paramName, $value, $config)
    {
        if (strstr($config, '$config[\'' . $paramName . '\'] =')) {
            return preg_replace(
                '/^\$config\[\'' . $paramName . '\'\] =.*;/mi',
                "\$config['" . $paramName . "'] = '" . $value . "';",
                $config
            );
        }

        return $config;
    }

    /**
     * Imports nessesared languages
     *
     * @return bool true on success, false otherwise
     */
    public function setupLanguages($demo)
    {
        $languages = $this->_cart_settings['languages'];

        if (!empty($languages)) {
            foreach ($languages as $lang_code) {
                $pack_path = Registry::get('config.dir.install') . App::DB_LANG . "/$lang_code/$lang_code.po";
                $edition_pack_path = Registry::get('config.dir.install') . App::DB_LANG . "/$lang_code/$lang_code" . '_' . fn_get_edition_acronym(PRODUCT_EDITION) . '.po';

                if (!file_exists($pack_path)) {
                    App::instance()->setNotification('W', 'Missing language pack', 'Unable to find: ' . $pack_path . ' (skipped)', true);

                    continue;
                }

                $this->_parseSql(Registry::get('config.dir.install') . App::DB_LANG . "/$lang_code/" . App::DB_LANG_DATA, 'text_installing_additional_language', array('lang_code' => $lang_code));
                if ($demo) {
                    $this->_parseSql(Registry::get('config.dir.install') . App::DB_LANG . "/$lang_code/" . App::DB_LANG_DEMO, 'text_installing_additional_language', array('lang_code' => $lang_code));
                }

                // Install language variables from PO files
                $params = array(
                    'lang_code' => $lang_code,
                );
                $_langs = Languages::get($params);
                $is_exists = count($_langs) > 0 ? true : false;

                Languages::installLanguagePack($pack_path, array('reinstall' => $is_exists));
                if (file_exists($edition_pack_path)) {
                    Languages::installLanguagePack($edition_pack_path, array('reinstall' => true));
                }
            }

            // share all additional languages
            if (fn_allowed_for('ULTIMATE')) {
                db_query(
                    "REPLACE INTO ?:ult_objects_sharing (`share_company_id`, `share_object_id`, `share_object_type`) "
                    . "SELECT ?:companies.company_id, ?:languages.lang_id, 'languages' "
                    . "FROM ?:languages INNER JOIN ?:companies;"
                );
            }

            $languages = db_get_hash_array("SELECT * FROM ?:languages", 'lang_code');
            Registry::set('languages', $languages);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Setups companies
     *
     * @return bool Always true
     */
    public function setupCompanies()
    {
        $this->_setupCompanyEmails($this->_cart_settings['email']);

        if (fn_allowed_for('ULTIMATE')) {
            $url = $this->_getUrlFromArray($this->_server_settings);
            $secure_url = $this->_getUrlFromArray($this->_server_settings, self::HTTPS);
            $this->_updateCompanyURL($url, $secure_url, 1);
        }

        return true;
    }

    public function setSimpleMode()
    {
        $company_count = db_get_field("SELECT COUNT(company_id) FROM ?:companies");
        if ($company_count === '1') {
            Registry::set('runtime.simple_ultimate', true);
        } else {
            Registry::set('runtime.simple_ultimate', false);
        }

        return true;
    }

    /**
     * Setups company emails
     *
     * @return bool Always true
     */
    private function _setupCompanyEmails($email)
    {
        $company_emails = array (
            'company_users_department',
            'company_site_administrator',
            'company_orders_department',
            'company_support_department',
            'company_newsletter_email',
        );

        db_query("UPDATE ?:settings_objects SET value = ?s WHERE name IN (?a)", $email, $company_emails);

        return true;
    }

    /**
     * Returns URL from hash array width $type_host and $type_path
     *
     * @param  array  $params
     * @param  string $type   name of key (http or https)
     * @return string URL
     */
    private function _getUrlFromArray($params, $type = self::HTTP)
    {
        return (!empty($params[$type . '_host']) ? $params[$type . '_host'] : '') . (!empty($params[$type . '_path']) ? $params[$type . '_path'] : '');
    }

    /**
     * Setups auto feedback
     *
     * @param string $feedback_auto If equals 'Y' auto feedback will be enabled
     *
     * @return bool Always true
     */
    private function _setupAutoFeedback($feedback_auto)
    {
        if ($feedback_auto == 'Y') {
            db_query("UPDATE ?:settings_objects SET value = ?s WHERE name = ?s", 'auto', 'feedback_type');
        }

        return true;
    }

    /**
     * Setups default language for frontend and backend
     *
     * @param $lang 2-letters language code, like 'en'
     *
     * @return bool Always true
     */
    private function _setupDefaultLanguages($lang)
    {
        db_query(
            "UPDATE ?:settings_objects SET value = ?s WHERE name IN (?a)",
            $lang,
            array(
                'frontend_default_language',
                'backend_default_language'
            )
        );

        db_query("UPDATE ?:companies SET lang_code = ?s", $lang);

        return true;
    }

    /**
     * Creates admin account
     * @param string $email    Administrator email
     * @param string $password Administrator password
     *
     * @return bool Always true
     */
    private function _createAdminAccount($email, $password)
    {
        // Insert root admin
        $user_data = array(
            'user_id' => 1,
            'status' => 'A',
            'user_type' => 'A',
            'is_root' => 'Y',
            'password' => md5($password),
            'email' => $email,
            'user_login' => 'admin',
            'title' => 'mr',
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'company' => 'Your company',
            'phone' => '55 55 555 5555',
            'lang_code' => $this->_cart_settings['main_language'],
            'profile_name' => 'Main',
        );
        $profile = array(
            'title' => 'mr',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'address' => '44 Main street',
            'address_2' => 'test',
            'city' => 'Boston',
            'county' => '',
            'state' => 'MA',
            'country' => 'US',
            'zipcode' => '02134',
            'phone' => '',
        );

        foreach ($profile as $k => $v) {
            $user_data['b_' . $k] = $v;
            $user_data['s_' . $k] = $v;
        }

        db_query("REPLACE INTO ?:users ?e", $user_data);
        fn_update_user_profile(1, $user_data, 'add');

        return true;
    }

    /**
     * Setup users
     *
     * @return bool Always true
     */
    public function setupUsers()
    {
        db_query("UPDATE ?:users SET `last_login` = 0, `timestamp` = ?i", TIME);

        return true;
    }

    /**
     * Updates company urls
     *
     * @param  string $url        store url
     * @param  string $secure_url secure store url
     * @param  int    $company_id company identifier
     * @return bool   Always true
     */
    private function _updateCompanyURL($url, $secure_url,  $company_id)
    {
        $company_data = array (
            'storefront' => Url::clean($url),
            'secure_storefront' => Url::clean($secure_url)
        );

        db_query('UPDATE ?:companies SET ?u WHERE company_id = ?i', $company_data, $company_id);

        return true;
    }

    /**
     * Parse and import sql file
     *
     * @param  string $filename path to SQL file
     * @param  string $title    Language value that will be showed on import
     * @param  array  $extra    Extra param
     * @return bool   True on success, false otherwise
     */
    private function _parseSql($filename, $title, $extra = array())
    {
        $app = App::instance();
        $title_shown = false;

        $fd = fopen($filename, 'r');
        if ($fd) {
            $_sess_name = md5($filename);
            if (!empty($_SESSION['parse_sql'][$_sess_name])) {
                if ($_SESSION['parse_sql'][$_sess_name] == 'COMPLETED') {
                    fclose($fd);

                    return true;
                }
                fseek($fd, $_SESSION['parse_sql'][$_sess_name]);
            }

            $rest = '';
            $ret = array();
            $counter = 0;
            while (!feof($fd)) {
                $str = $rest.fread($fd, 16384);
                $rest = fn_parse_queries($ret, $str);

                if (!empty($ret)) {
                    if ($title_shown == false) {
                        $app->setNotification('N', '', $app->t($title, $extra), true);
                        $title_shown = true;
                    }

                    foreach ($ret as $query) {
                        $counter ++;
                        if (strpos($query, 'CREATE TABLE') !== false) {
                            preg_match("/CREATE\s+TABLE\s+`(\w*)`/i", $query, $matches);
                            $table_name = str_replace(App::DEFAULT_PREFIX, '', $matches[1]);
                            fn_set_progress('echo', $app->t('creating_table', array('table' => $table_name)));
                        } else {
                            if ($counter > 30 && !App::instance()->isConsole()) {
                                fn_set_progress('echo', '');
                                $counter = 0;
                            }
                        }

                        $query = str_replace(App::DEFAULT_PREFIX, $this->_database_settings['table_prefix'], $query);

                        db_query($query);
                    }
                    $ret = array();
                }

                // Break the connection and re-request
                if (time() - TIME > INSTALL_DB_EXECUTION && !App::instance()->isConsole()) {
                    $pos = ftell($fd);
                    $pos = $pos - strlen($rest);
                    fclose($fd);
                    $_SESSION['parse_sql'][$_sess_name] = $pos;
                    $location = $_SERVER['REQUEST_URI'] . '&no_checking=1';
                    fn_echo("<meta http-equiv=\"Refresh\" content=\"0;URL=$location\" />");
                    die;
                }
            }

            fclose($fd);

            $_SESSION['parse_sql'][$_sess_name] = 'COMPLETED';

            return true;
        }

        return false;
    }

    /**
     * Updates license number into Database
     *
     * @param  string $license_number
     * @return bool   Always true
     */
    public function setupLicense($license_number)
    {
        Settings::instance()->updateValue('license_number', $license_number);

        return true;
    }

    /**
     * Resets parsed sql files cache in session
     *
     * @return bool Always true
     */
    private function _resetParsedFilesCache()
    {
        $_SESSION['parse_sql'] = array();

        return true;
    }
}
