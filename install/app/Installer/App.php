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

use Tygh\Database;
use Tygh\Registry;
use Tygh\ClassLoader;
use Tygh\Bootstrap;

class App
{
    const DEFAULT_PREFIX = 'cscart_';
    const DEFAULT_LANGUAGE = 'en';
    const DB_SCHEME = 'database/scheme.sql';
    const DB_DATA = 'database/data.sql';
    const DB_DEMO = 'database/demo.sql';
    const DB_LANG = 'database/lang';
    const DB_LANG_DATA = 'lang.sql';
    const DB_LANG_DEMO = 'lang_demo.sql';
    const REQUIRED_PHP_VERSION = '5.3.0';
    const LOG_FILE = 'var/install.log';
    const THEME_NAME = 'responsive';

    /**
     * Type of run. If true application run from console
     *
     * @var bool $_console
     */
    private $_console = false;

    /**
     * Status of current database connection
     *
     * @var bool $_db_connection
     */
    private $_db_connection = null;

    /**
     * Current installer language code
     *
     * @var string $_current_lang_code
     */
    private $_current_lang_code = 'en';

    /**
     * List of language values
     *
     * @var array $lang_data
     */
    private $_lang_data;

    /**
     * Instance of App
     *
     * @var App $_instance
     */
    private static $_instance;

    /**
     * Returns true if application runned with console flag
     * @return bool
     */
    public function isConsole()
    {
        return $this->_console;
    }

    /**
     * Init's applicaion // FIXME: Bad method...
     *
     * @param  array $params Params for initiate installer
     * @return bool  Always true
     */
    public function init($params = array())
    {
        if (defined('INSTALLER_INITED')) {
            return true;
        }

        $config = array();

        define('AREA', 'A');
        define('ACCOUNT_TYPE' ,'admin');

        date_default_timezone_set('Europe/Moscow');

        $base_path = isset($params['base_path']) ? $params['base_path'] : './..';

        // Register autoloader
        $classLoader = require($base_path . '/app/lib/vendor/autoload.php');
        $classLoader->add('Tygh', realpath($base_path . '/app'));

        // Prepare environment and process request vars
        list($_REQUEST, $_SERVER) = Bootstrap::initEnv($_GET, $_POST, $_SERVER, $base_path);
        if (defined('CONSOLE')) {
            chdir(getcwd() . '/install');
        }

        // Get config data
        $config = require(DIR_ROOT . '/config.php');

        if (isset($_REQUEST['version'])) {
            die(PRODUCT_NAME . ': version <b>' . PRODUCT_VERSION . ' ' . PRODUCT_EDITION . (PRODUCT_STATUS != '' ? (' (' . PRODUCT_STATUS . ')') : '') . (PRODUCT_BUILD != '' ? (' ' . PRODUCT_BUILD) : '') . '</b>');
        }

        // Callback: verifies if https works
        if (isset($_REQUEST['check_https'])) {
            die(defined('HTTPS') ? 'OK' : '');
        }

        // Load core functions
        $fn_list = array(
            'fn.addons.php',
            'fn.companies.php',
            'fn.database.php',
            'fn.fs.php',
            'fn.cms.php',
            'fn.cart.php',
            'fn.common.php',
            'fn.control.php',
            'fn.init.php',
            'fn.users.php',
            'fn.images.php',
            'fn.log.php',
        );

        if (PRODUCT_EDITION == 'MULTIVENDOR' || PRODUCT_EDITION == 'ULTIMATE') {
            $fn_list[] = 'fn.' . strtolower(PRODUCT_EDITION) . '.php';
        }

        foreach ($fn_list as $file) {
            require($config['dir']['functions'] . $file);
        }

        $config['dir']['install_themes'] = is_dir($config['dir']['root'] . '/var/themes_repository') ? $config['dir']['root'] . '/var/themes_repository' : $config['dir']['root'] . '/themes';
        $config['dir']['install'] = $config['dir']['root'] . '/install/';

        $classLoader->add('', $config['dir']['install'] . 'app/');
        Registry::set('class_loader', $classLoader);
        Registry::set('config', $config);

        $session_id = session_id();
        if (empty($session_id)) {
            session_start();
        }

        fn_init_ajax();

        // Init storage
        Registry::set('runtime.storage', array(
            'storage' => 'file'
        ));

        if (!empty($params['sl'])) {
            $this->setCurrentLangCode($params['sl']);
        } elseif ($this->getFromStorage('sl')) {
            $this->setCurrentLangCode($this->getFromStorage('sl'));
        } else {
            $this->setCurrentLangCode(self::DEFAULT_LANGUAGE);
        }

        $this->_loadLanguageVariables();

        //define DEFAULT_LANGUAGE for correct addon installing
        if (!defined('DEFAULT_LANGUAGE')) {
            define('DEFAULT_LANGUAGE', self::DEFAULT_LANGUAGE);
        }

        define('INSTALLER_INITED', true);
        unset($config);
    }

    /**
     * Runs controller by dispatch and returns it result
     *
     * @param  string     $dispatch String with CONTROLLER.MODE
     * @param  array      $params   List of params for controller
     * @param  bool       $console  Console run
     * @return bool|mixed Controller result
     */
    public function dispatch($dispatch, $params, $console = false)
    {
        $_REQUEST['dispatch'] = $dispatch;

        $this->_console = $console;
        $dispatch = $this->_divideDispatch($dispatch);

        $controller_result = array();
        $controller = $this->_getObjectByDispatch($dispatch);
        if ($controller !== null) {
            $reflection_method = new \ReflectionMethod($controller, $this->_generateMethodName($dispatch['mode']));
            $accepted_params = $reflection_method->getParameters();
            $call_params = array ();

            foreach ($accepted_params  as $param) {
                $param_name = $param->getName();

                if (isset($params[$param_name])) {
                    $call_params[$param_name] = $params[$param_name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $call_params[$param_name] = $param->getDefaultValue();
                } else {
                    $call_params[$param_name] = array();
                }
            }

            $controller_result = (array) $reflection_method->invokeArgs($controller, $call_params);
        } else {

        }

        $controller_result = array_merge($params, $controller_result);

        return $controller_result;
    }

    /**
     * Divides dispatch to controller and mode
     *
     * @param  string $dispatch
     * @return array  Dispatch data by keys ('controller', 'mode')
     */
    private function _divideDispatch($dispatch)
    {
        $dispatch = explode('.', $dispatch);

        return array (
            'controller' => $dispatch[0],
            'mode' => !empty($dispatch[1]) ? $dispatch[1] : 'index', // By default use index method
        );
    }

    /**
     * Returns object instance name by dispatch or null if no controller class or methods for this dispatch
     *
     * @param  array       $dispatch Divided dispatch @see InstallerApp::_divideDispatch()
     * @return object|null Object instance on success, false otherwise
     */
    private function _getObjectByDispatch($dispatch)
    {
        $controller_object = null;

        if (!empty($dispatch['controller']) && !empty($dispatch['mode'])) {
            $class_name = $this->_generateClassName($dispatch['controller']);

            if (class_exists($class_name)) {
                if (method_exists($class_name, $this->_generateMethodName($dispatch['mode']))) {
                    $controller_object = new $class_name;
                } else {
                    die('<strong>' . $this->_generateMethodName($dispatch['mode']) . '</strong> method of <strong>' . $class_name . '</strong> class is not implemented');
                }

            } else {
                die('Class <strong>' . $class_name . '</strong> not exists');
            }
        }

        return $controller_object;
    }

    /**
     * Returns method name for mode
     *
     * @param  string $mode Mode name
     * @return string Class method name
     */
    private function _generateMethodName($mode)
    {
        $mode = preg_replace_callback("/_([a-zA-Z])/", function($m) {
            return strtoupper($m[1]);
        }, $mode);

        return 'action' . ucfirst($mode);
    }

    /**
     * Returns class name for controller
     *
     * @param  string $controller Controller name
     * @return string Controller class name
     */
    private function _generateClassName($controller)
    {
        return '\\Installer\\' . ucfirst($controller) . 'Controller';
    }

    /**
     * Returns current selected lang_code
     *
     * @return string Language code
     */
    public function getCurrentLangCode()
    {
        return $this->_current_lang_code;
    }

    /**
     * Sets current selected lang_code
     *
     * @param  string $lang_code 2 letters language code
     * @return string Language code
     */
    public function setCurrentLangCode($lang_code)
    {
        // Check if new lang code can be used.
        $available_langs = $this->getAvailableLangs();

        if (isset($available_langs[$lang_code])) {
            $this->_current_lang_code = $lang_code;

            $this->setToStorage('sl', $lang_code);
        }

        return $this->_current_lang_code;
    }

    /**
     * Runs application
     */
    public function run(&$params)
    {
        $dispatch = !isset($params['dispatch']) ? '' : $params['dispatch'];
        if (is_array($dispatch)) {
            $dispatch = key($dispatch);
        }
        $params['dispatch'] = $dispatch;

        if (empty($dispatch)) {
            $dispatch = 'license';
        }

        $_tpl_vars = $this->dispatch($dispatch, $params);
        $_tpl_vars['installer_languages'] = $this->getAvailableLangs();
        $_tpl_vars['current_language'] = $this->_current_lang_code;
        $_tpl_vars['dispatch'] = $dispatch;

        // Run PHP template for selected dispatch
        $dispatch = $this->_divideDispatch($dispatch);

        $template = Registry::get('config.dir.root') . '/install/design/templates/' . $dispatch['controller'] . '/' . $dispatch['mode'] . '.php';
        if (file_exists($template)) {
            $notifications = $this->getNotifications();
            include ($template);
        }

        return true;
    }

    /**
     * Sets system notification and/or add record to LOG file
     *
     * @param  string $type       notification type (E - error, W - warning, N - notice)
     * @param  string $title      notification title
     * @param  string $message    notification message
     * @param  bool   $add_to_log Add notification to log or no
     * @param  string $section_id Section on wich notification will be showed
     * @return bool   always true
     */
    public function setNotification($type, $title, $message, $add_to_log = false, $section_id = 'general', $non_closable = false)
    {
        $notifications = Registry::get('runtime.notifications');

        $key = md5($type . $title . $message);
        if (!isset($notifications[$key])) {
            $notifications[$key] = array(
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'section_id' => $section_id,
                'non_closable' => $non_closable,
            );
        }

        $message_state = $non_closable ? 'E' : 'K';

        fn_set_notification($type, $title, $message, $message_state, $section_id);

        if ($add_to_log) {
            $this->logMessage($type, $title, $message);
        }

        if ($this->_console) {
            echo $this->_formatLogMessage($type, $title, $message);
        }

        Registry::set('runtime.notifications', $notifications);

        return true;
    }

    /**
     * Returns list of setted notifications
     *
     * @param  string $section_id Section on wich notification will be showed
     * @return array  List of notifications
     */
    public function getNotifications($section_id = '')
    {
        $notifications = Registry::get('runtime.notifications');

        if (empty($notifications) || empty($section_id)) {
            return $notifications;
        }

        $_notifications = array();
        foreach ($notifications as $key => $notification) {
            if ($notification['section_id'] == $section_id) {
                $_notifications[$key] = $notification;
            }
        }

        return $_notifications;
    }

    /**
     * Returns HTML code of all messages selected by section id
     *
     * @param  string $section_id Name of messages group
     * @return string HTML code
     */
    public function getNotificationsHtmlCode($section_id = 'general')
    {
        $html_code = '<a name="section_' . $section_id . '"></a>';
        $notifications = Registry::get('runtime.notifications');

        if (empty($notifications)) {
            return $html_code;
        }

        foreach ($notifications as $notification) {
            if ($notification['section_id'] == $section_id) {
                ob_start();

                include (Registry::get('config.dir.root') . '/install/design/templates/common/notification.php');
                $html_code .= ob_get_contents();

                ob_end_clean();
            }
        }

        return $html_code;
    }

    /**
     * Returns the value of requested variable in needed format
     *
     * @param  array  $vars          List of variables
     * @param  string $var_name      Needed variable name (E.g.: config.http_path)
     * @param  string $type          Output type of variable (SET: string, int. float, bool)
     * @param  mixed  $default_value Default value of variable if it was not found in Varables list
     * @return mixed  Formatted string
     */
    public function prepareVar($vars, $var_name, $type = 'string', $default_value = null)
    {
        $value = $vars;
        $var_name = explode('.', $var_name);
        foreach ($var_name as $var) {
            if (isset($value[$var])) {
                $value = $value[$var];
            } else {
                $value = null;
                break;
            }
        }

        if ($value == null) {
            if ($default_value != null) {
                $value = $default_value;
            }

            return $value;
        }

        switch ($type) {
            case 'string':
                $value = (string) $value;
                break;

            case 'int':
                $value = intval($value);
                break;

            case 'float':
                $value = floatval($value);
                break;

            case 'bool':
                if (!empty($value)) {
                    $value = true;
                } else {
                    $value = false;
                }
                break;

            default: $value = null;
        }

        return $value;
    }

    /**
     * Sets value to Session storage
     *
     * @param  string $name  Variable name
     * @param  mixed  $value Variable value
     * @return bool   Always true
     */
    public function setToStorage($name, $value)
    {
        $_SESSION['installer_storage'][$name] = $value;

        return true;
    }

    /**
     * Gets value from Session storage
     *
     * @param string $name Variable name
     *
     * @return mixed Variable value of null if not found
     */
    public function getFromStorage($name)
    {
        if (isset($_SESSION['installer_storage'][$name])) {
            return $_SESSION['installer_storage'][$name];
        } else {
            return null;
        }
    }

    /**
     * Add a new record to LOG file.
     *
     * @param  string $type    notification type (E - error, W - warning, N - notice)
     * @param  string $title   notification title
     * @param  string $message notification message
     * @return bool   true if record was added
     */
    public function logMessage($type, $title, $message)
    {
        $log_wrote = false;

        $file_path = Registry::get('config.dir.root') . '/' . self::LOG_FILE;

        // Create file if not exists
        if (!file_exists($file_path)) {
            fn_put_contents($file_path, '');
        }

        if (is_file($file_path) && is_writable($file_path)) {
            file_put_contents($file_path, $this->_formatLogMessage($type, $title, $message), FILE_APPEND);
            $log_wrote = true;
        }

        return $log_wrote;
    }

    /**
     * Formats log message
     * @param  string $type    notification type (E - error, W - warning, N - notice)
     * @param  string $title   notification title
     * @param  string $message notification message
     * @return string Formatted string
     */
    private function _formatLogMessage($type, $title, $message)
    {
        return '[' . date('r') . '] (' . $type . ')' . $title . '; ' . $message . "\r\n";
    }

    /**
     * Returns translation for language variable
     *
     * @param  string $lang_var
     * @return string
     */
    public function getLanguageVariable($lang_var)
    {
        return !empty($this->_lang_data[$this->_current_lang_code][$lang_var]) ?
            $this->_lang_data[$this->_current_lang_code][$lang_var] : '_' . $lang_var;
    }

    /**
     * Returns product name
     *
     * @return string Formatted product name
     */
    public function getProductName()
    {
        return PRODUCT_NAME;
    }

    /**
     * Returns product version
     *
     * @return string Formatted product version (E.g.: Version: 3.1.0)
     */
    public function getProductVersion()
    {
        return $this->t('version') . ': ' . PRODUCT_VERSION;
    }

    /**
     * Gets language variable and replace some values if needed
     *
     * @param  string $lang_var     Language value to get
     * @param  array  $replacements Array of replacments
     * @return string Translated string
     */
    public function t($lang_var, $replacements = array())
    {
        $text = $this->getLanguageVariable($lang_var);
        foreach ($replacements as $search => $replace) {
            $text = str_replace('[' . $search . ']', $replace, $text);
        }

        return $text;
    }

    /**
     * Returns list of available languages
     *
     * @return array List of available languages
     */
    public function getAvailableLangs()
    {
        $lang_files = fn_get_dir_contents(Registry::get('config.dir.install') . 'langs/translations' , false, true, '.json');

        $languages = array();

        foreach ($lang_files as $lang_file) {
            $lang_code = str_replace('.json', '', $lang_file);
            $languages[$lang_code] = $this->getLanguageVariable($lang_code);
        }

        return $languages;
    }

    /**
     * Loads language variables from JSON file
     *
     * @return bool true on success, false otherwise
     */
    private function _loadLanguageVariables()
    {
        $lang_code = $this->_current_lang_code;

        if (empty($lang_code)) {
            $this->setNotification('E', $this->getLanguageVariable('error'), $this->getLanguageVariable('lang_code_not_defined'));

            return false;
        }

        $langs = $this->getAvailableLangs();
        if (!isset($langs[$lang_code])) {
            $this->setNotification('E', $this->getLanguageVariable('error'), $this->getLanguageVariable('selected_language_not_available'));

            return false;
        }

        if (empty($this->_lang_data[$lang_code])) {
            $general_lang_vars = file_get_contents(Registry::get('config.dir.install') . '/langs/general.json');
            $general_lang_vars = json_decode($general_lang_vars, true);

            $json_lang_vars = file_get_contents(Registry::get('config.dir.install') . '/langs/translations/' . $lang_code . '.json');

            $this->_lang_data[$lang_code] = array_merge($general_lang_vars, json_decode($json_lang_vars, true));

            if ($this->_lang_data[$lang_code] == null) {
               $this->logMessage('E', 'Error', 'Parse json language file');
            }
        }

        return true;
    }

    /**
     * Connects to database
     *
     * @param  string $host         Database host
     * @param  string $name         Database name
     * @param  string $user         Database user
     * @param  string $password     Database password
     * @param  string $table_prefix Database table prefix
     * @param  string $names        Database names for SET NAMES
     * @return bool   Tue on succes connection or already connected, false otherwise
     */
    public function connectToDB($host, $name, $user, $password, $table_prefix, $database_backend, $names = 'utf8')
    {
        $connected = true;

        if ($this->_db_connection == null) {
            Registry::set('config.table_prefix', $table_prefix);
            Registry::set('config.database_backend', $database_backend);

            $connected = Database::connect($user, $password, $host, '', array(
                'table_prefix' => $table_prefix
            ));

            if (!$connected) {
                $this->setNotification('E', '', $this->t('could_not_connect_to_database'), true, 'server_configuration');
            } elseif (!Database::changeDb($name)) {
                // CREATE TABLE SQL command will throw the Fatal error if user does not have the CREATE permissions
                Registry::set('runtime.database.skip_errors', true);

                if (!Database::createDb($name)) {
                    $this->setNotification('E', '', $this->t('could_not_create_database'), true, 'server_configuration');
                    $connected = false;
                } else {
                    Database::changeDb($name);
                }
                Registry::set('runtime.database.skip_errors', false);
            }

            if ($connected) {
                db_query("SET NAMES ?s", $names);
                db_query("SET sql_mode = ''");
            }

            $this->_db_connection = $connected;
        }

        return $connected;
    }

    /**
     * Returns instance of InstallerApp
     *
     * @return App
     */
    public static function instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct()
    {

    }
}
