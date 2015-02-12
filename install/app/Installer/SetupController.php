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

use Tygh\Session;
use Tygh\Registry;

class SetupController
{
    /**
     * Setup index action
     *
     * @return array List of prepared variables
     */
    public function actionIndex()
    {
        // Check requirements
        $validator = new Validator;

        $cart_settings['main_language'] = App::DEFAULT_LANGUAGE;
        $setup = new Setup($cart_settings);
        $app = App::instance();

        if (!$app->getFromStorage('license_agreement')) {
            $params['dispatch'] = 'license';

            $app->run($params);
            exit(0);
        }

        $checking_result = array(
            'mod_secure' => $validator->isModeSecurityDisabled(),
            'file_upload' => $validator->isFileUploadsSupported(),
            'safe_mode' => $validator->isSafeModeDisabled(),
            'curl_supported' => $validator->isCurlSupported(),
            'mysql_supported' => $validator->isMysqlSupported(),
            'php_version_supported' => $validator->isPhpVersionSupported(),
            'session_auto_start' => $validator->isSessionAutostartDisabled(),
            'file_system_writable' => $validator->isFilesystemWritable(),
            'json_available' => $validator->isJsonAvailable(),
            'register_globals_disabled' => $validator->isGlobalsDisabled(),
            'phar_data_available' => $validator->isPharDataAvailable(),
            'func_overload_acceptable' => $validator->isFuncOverloadAcceptable(),
        );

        $validator->isModRewriteEnabled();
        $validator->isZipArchiveAvailable();

        foreach ($checking_result as $validator_result) {
            if (empty($validator_result)) {
                $app->setNotification('E', $app->t('error'), $app->t('server_requirements_do_not_meet'), true, 'server_requirements');
                break;
            }
        }

        if (empty($checking_result['file_system_writable'])) {
            $app->setNotification('E', $app->t('error'), $app->t('check_files_and_folders_permissions'), true, 'file_permissions_section');
        }

        $this->_prepareHttpData();

        $languages = $setup->getLanguages();
        $available_themes = $setup->getAvailableThemes();
        $db_types = $setup->getSupportedDbTypes();

        $return = array(
            'checking_result' => $checking_result,
            'show_requirements_section' => !$validator_result,
            'languages' => $languages,
            'available_themes' => $available_themes,
            'db_types' => $db_types,
            'cart_settings' => $cart_settings,
        );

        return $return;
    }

    /**
     * Setup complete action
     *
     * @param array $params Request variables
     *
     * @return bool Always true
     */
    public function actionComplete($params = array())
    {
        $validator = new Validator;
        $app = App::instance();

        fn_define('CART_LANGUAGE', $app->getCurrentLangCode());
        fn_define('DESCR_SL', $app->getCurrentLangCode());

        $database = $app->getFromStorage('database_settings');

        if (!empty($database)) {
            $result = $validator->isMysqlSettingsValid($database['host'], $database['name'], $database['user'], $database['password'], $database['table_prefix'], $database['database_backend'], false);

            if ($result) {
                // Delete installer after store was installed.
                fn_rm(Registry::get('config.dir.root') . '/install');

                session_destroy();

                $this->_prepareHttpData();

                Session::init($params);

                $user_data = array (
                    'user_id' => 1,
                    'user_type' => 'A',
                    'area' => 'A',
                    'login' => 'admin',
                    'is_root' => 'Y',
                    'company_id' => 0
                );
                $_SESSION['auth'] = fn_fill_auth($user_data, array(), false, 'A');

                if (is_file(Registry::get('config.dir.root') . '/install/index.php')) {
                    $_SESSION['notifications']['installer'] = array(
                        'type' => 'W',
                        'title' => 'warning',
                        'message' => 'delete_install_folder',
                        'message_state' => 'S',
                        'new' => true,
                        'extra' => '',
                        'init_message' => true,
                    );
                }

                $redirect_url = Registry::get('config.http_location') . '/' . Registry::get('config.admin_index') . '?welcome';
                fn_redirect($redirect_url);
            }
        }

        fn_redirect('install/index.php');

        return true;
    }

    /**
     * Setup next_step action
     *
     * @param  array $cart_settings     Cart settings
     * @param  array $database_settings Database settings
     * @param  array $server_settings   Server settings
     * @return bool  Always true
     */
    public function actionNextStep($cart_settings, $database_settings, $server_settings)
    {
        $app = App::instance();
        $validator = new Validator;

        if ($validator->validateAll(array_merge($cart_settings, $server_settings, $database_settings))) {
            fn_set_progress('parts', 14);

            set_time_limit(0);

            if ($app->connectToDB(
                $database_settings['host'],
                $database_settings['name'],
                $database_settings['user'],
                $database_settings['password'],
                $database_settings['table_prefix'],
                $database_settings['database_backend']
            )) {

                $app->setToStorage('database_settings', $database_settings);

                define('CART_LANGUAGE', $cart_settings['main_language']);
                define('DESCR_SL', $cart_settings['main_language']);
                define('CART_SECONDARY_CURRENCY', 'NULL'); // Need for cache_level

                $sCart = new Setup($cart_settings, $server_settings, $database_settings, $this->isDemoInstall($cart_settings));
                $sAddons = new AddonsSetup;

                /* Setup Scheme */
                fn_set_progress('title', $app->t('setup_scheme'));
                fn_set_progress('echo', $app->t('processing'), true);
                fn_set_progress('step_scale', 2000);
                $sCart->setupScheme();

                /* Setup Scheme Data */
                fn_set_progress('step_scale', 1);
                fn_set_progress('title', $app->t('setup_data'));
                fn_set_progress('echo', $app->t('processing'), true);
                fn_set_progress('step_scale', 5000);
                $sCart->setupData();

                $sCart->setSimpleMode();

                /* Setup Demo */
                if ($this->isDemoInstall($cart_settings)) {
                    fn_set_progress('step_scale', 1);
                    fn_set_progress('title', $app->t('setup_demo'));
                    fn_set_progress('echo', $app->t('installing_demo_catalog'), true);
                    fn_set_progress('step_scale', 5000);
                    $sCart->setupDemo();
                } else {
                    fn_set_progress('step_scale', 1);
                    fn_set_progress('echo', $app->t('cleaning'), true);
                    $sCart->clean();
                }

                $sCart->setupUsers();

                /* Setup companies */
                fn_set_progress('step_scale', 1);
                fn_set_progress('title', $app->t('setup_companies'));
                fn_set_progress('echo', $app->t('processing'), true);
                $sCart->setupCompanies();

                /* Setup Languages */
                fn_set_progress('step_scale', 1);
                fn_set_progress('title', $app->t('setup_languages'));
                fn_set_progress('echo', $app->t('processing'), true);
                fn_set_progress('step_scale', 1000);
                $sCart->setupLanguages($this->isDemoInstall($cart_settings));

                $sCart->setupThemes();

                /* Setup Add-ons */
                fn_set_progress('title', $app->t('setup_addons'));
                fn_set_progress('echo', $app->t('processing'), true);
                fn_set_progress('step_scale', 100);
                $sAddons->setup($this->isDemoInstall($cart_settings), array());

                /* Write config */
                fn_set_progress('step_scale', 1);
                fn_set_progress('echo', $app->t('writing_config'), true);
                $sCart->writeConfig();

                $this->_prepareHttpData();

                $redirect_url = Registry::get('config.http_location') . '/install/index.php?dispatch=setup.complete';

                if (Registry::get('runtime.comet')) {
                    Registry::get('ajax')->assign('force_redirection', $redirect_url);
                } else {
                    fn_redirect($redirect_url);
                }

                exit();
            }

        } else {
            if (Registry::get('runtime.comet')) {
                exit();

            } else {
                $params['dispatch'] = 'setup.index';
                $params['cart_settings'] = $cart_settings;
                $params['database_settings'] = $database_settings;
                $params['server_settings'] = $server_settings;

                $app->run($params);
            }
        }

        return true;
    }

    /**
     * Setup recheck action
     *
     * @param  array $cart_settings     Cart settings
     * @param  array $database_settings Database settings
     * @param  array $server_settings   Server settings
     * @param  array $addons            List of addons to be installed
     * @return bool  always true
     */
    public function actionRecheck($cart_settings, $database_settings, $server_settings, $addons)
    {
        $app = App::instance();

        $params['dispatch'] = 'setup.index';
        $params['cart_settings'] = $cart_settings;
        $params['database_settings'] = $database_settings;
        $params['server_settings'] = $server_settings;
        $params['addons'] = $addons;

        $app->run($params);

        return true;
    }

    /**
     * Corrects permissions of store files and folders
     *
     * @param  array $cart_settings     Cart settings
     * @param  array $database_settings Database settings
     * @param  array $server_settings   Server settings
     * @param  array $ftp_settings      FTP connection settings
     * @param  array $addons            List of addons to be installed
     * @return bool  Always true
     */
    public function actionCorrectPermissions($cart_settings, $database_settings, $server_settings, $ftp_settings, $addons)
    {
        $app = App::instance();
        $validator = new Validator;

        if (!empty($ftp_settings['ftp_hostname']) && !empty($ftp_settings['ftp_username']) && !empty($ftp_settings['ftp_password'])) {
            if (fn_ftp_connect($ftp_settings)) {
                $files = array (
                    'config.local.php' => 0666,
                    'images' => 0777,
                    'design' => 0777,
                    'var' => 0777
                );

                foreach ($files as $file => $perm) {
                    fn_ftp_chmod_file($file, $perm, true);
                }
            }
        }

        $validator->isFilesystemWritable(true);

        $params['dispatch'] = 'setup.index';
        $params['cart_settings'] = $cart_settings;
        $params['database_settings'] = $database_settings;
        $params['server_settings'] = $server_settings;
        $params['addons'] = $addons;

        $app->run($params);

        return true;
    }

    /**
     * Setup console action
     *
     * @param  array $cart_settings     Cart settings
     * @param  array $database_settings Database settings
     * @param  array $server_settings   Server settings
     * @param  array $addons            List of addons to be installed
     * @return bool  Result of setup
     */
    public function actionConsole($cart_settings, $database_settings, $server_settings, $addons = array())
    {
        $app = App::instance();

        $setup_result = 1; // return code for cli
        $validator = new Validator;

        if ($validator->validateAll(array_merge($cart_settings, $server_settings, $database_settings, $addons))) {
            if ($app->connectToDB(
                $database_settings['host'],
                $database_settings['name'],
                $database_settings['user'],
                $database_settings['password'],
                $database_settings['table_prefix'],
                $database_settings['database_backend']
            )) {
                define('CART_LANGUAGE', $cart_settings['main_language']);
                define('DESCR_SL', $cart_settings['main_language']);
                define('CART_SECONDARY_CURRENCY', 'NULL'); // Need for cache_level

                set_time_limit(0);

                $sCart = new Setup($cart_settings, $server_settings, $database_settings, $this->isDemoInstall($cart_settings));
                $sAddons = new AddonsSetup;

                $sCart->setupScheme();
                $sCart->setupData();

                $sCart->setSimpleMode();

                if ($this->isDemoInstall($cart_settings)) {
                    $sCart->setupDemo();
                } else {
                    $sCart->clean();
                }

                $sCart->setupUsers();

                $sCart->setupCompanies();

                $sCart->setupLanguages($this->isDemoInstall($cart_settings));

                $sCart->setupThemes();

                $sAddons->setup($this->isDemoInstall($cart_settings), $addons);

                $license_number = !empty($cart_settings['license_number']) ? $cart_settings['license_number'] : '';
                $sCart->setupLicense($license_number);

                $sCart->writeConfig();

                $app->setNotification('N', '', $app->t('successfully_finished'), true);

                $setup_result = 0;
            }
        }

        return $setup_result;
    }

    /**
     * Returns flag of checking is demo require to be installed or not
     *
     * @param  array $cart_settings Cart settings
     * @return bool  True if demo require to be installed
     */
    public function isDemoInstall($cart_settings)
    {
        return (isset($cart_settings['demo_catalog']) && $cart_settings['demo_catalog'] == 'Y') ? true : false;
    }

    /**
     * Fills config array in Registry
     *
     * @return bool Always true
     */
    private function _prepareHttpData()
    {
        Registry::set('config.http_host', $_SERVER['HTTP_HOST']);
        Registry::set('config.http_path', preg_replace('#/install$#', '', dirname($_SERVER['SCRIPT_NAME'])));
        Registry::set('config.http_location', 'http://' . Registry::get('config.http_host') . Registry::get('config.http_path'));

        return true;
    }
}
