<?php
    use Tygh\Registry;
    use Installer\App;

    require 'design/templates/common/header.php';
?>

<div id="content">
    <?php echo $this->getNotificationsHtmlCode();?>

    <h3><?php echo $this->t('installation'); ?></h3>
    <p class="muted"><?php echo $this->t('installation_instructions'); ?></p>

    <?php if (!empty($_tpl_vars['show_requirements_section'])): ?>
        <form name="setup_step_form" method="post" action="index.php" class="form-horizontal cm-ajax cm-comet">

        <h3><?php echo $this->t('checking_requirements'); ?></h3>
        <?php
            echo $this->getNotificationsHtmlCode('server_requirements');
        ?>

        <?php
            $status = false;
            foreach ($_tpl_vars['checking_result'] as $key => $value) {
                if ($key != 'file_system_writable' && empty($value)) {
                    $status = true;
                    break;
                }
            }
        ?>

        <?php if ($status): ?>
            <div class="settings checking-result">
                <?php if (empty($_tpl_vars['checking_result']['mod_secure'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('mod_secure_enabled'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('mod_secure_must_be_disabled'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['file_upload'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('file_uploads'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('file_uploads_must_be_enabled'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['safe_mode'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('safe_mode_enabled'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('safe_mode_must_be_disabled'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['curl_supported'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('curl_support'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('curl_support_must_be_enabled'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['mysql_supported'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('mysql_support'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('mysql_support_must_be_enabled'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['php_version_supported'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('php_version_not_support'); ?></strong><br>
                        <p class="summary"><?php echo $this->t('text_php_version_not_support'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['json_available'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('text_json_notice'); ?></strong><br>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['register_globals_disabled'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('text_register_globals_notice'); ?></strong><br>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['session_auto_start'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('text_session_auto_start_notice'); ?></strong><br>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['phar_data_available'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('text_phar_data_notice'); ?></strong><br>
                    </div>
                <?php endif; ?>

                <?php if (empty($_tpl_vars['checking_result']['func_overload_acceptable'])): ?>
                    <div class="requirement">
                        <span class="label label-important pull-right"><?php echo $this->t('fail'); ?></span>
                        <strong><?php echo $this->t('text_func_overload_notice'); ?></strong><br>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
            echo $this->getNotificationsHtmlCode('file_permissions_section');
            $notifications = $this->getNotifications('file_permissions');
            if (!empty($notifications)) {
        ?>
        <div class="settings">
            <?php
                foreach ($notifications as $notification) {
                    echo $notification['message'] . '<br>';
                }
            ?>
        </div>
        <a class="btn cm-no-ajax cm-dialog-opener cm-dialog-auto-size" id="opener_correct_permissions" data-ca-target-id="correct_permissions"><?php echo $this->t('correct_permissions'); ?></a>
        <?php
            }
        ?>
        <br><br>
        <div class="modal-footer">
            <input type="submit" class="btn btn-primary pull-left cm-no-ajax" name="dispatch[setup.recheck]" value="<?php echo $this->t('recheck'); ?>">
        </div>

        </form>
    <?php endif; ?>

<div class="hidden" id="correct_permissions" title="<?php echo $this->t('title_ftp_options'); ?>">
    <form name="correct_permissions_2" action="index.php" class="cm-ajax form-horizontal" method="post">
        <div class="settings">
            <div class="requirement">
                <div class="control-group">
                    <label class="control-label cm-required" for="correct_permissions_hostname"><?php echo $this->t('correct_permissions_hostname'); ?>:</label>
                    <div class="controls">
                        <input type="text" name="ftp_settings[ftp_hostname]" id="correct_permissions_hostname" placeholder="<?php echo $this->t('correct_permissions_placeholder'); ?>" value="<?php echo $this->prepareVar($_tpl_vars, 'ftp_settings.ftp_hostname'); ?>">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="correct_permissions_username"><?php echo $this->t('correct_permissions_username'); ?>:</label>
                    <div class="controls">
                        <input type="text" name="ftp_settings[ftp_username]" id="correct_permissions_username" value="<?php echo $this->prepareVar($_tpl_vars, 'ftp_settings.ftp_username'); ?>">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="correct_permissions_pass"><?php echo $this->t('correct_permissions_pass'); ?>:</label>
                    <div class="controls">
                        <input type="text" name="ftp_settings[ftp_password]" id="correct_permissions_pass" value="<?php echo $this->prepareVar($_tpl_vars, 'ftp_settings.ftp_password'); ?>">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="correct_permissions_path"><?php echo $this->t('correct_permissions_path'); ?>:</label>
                    <div class="controls">
                        <input type="text" name="ftp_settings[ftp_directory]" id="correct_permissions_path" value="<?php echo $this->prepareVar($_tpl_vars, 'ftp_settings.ftp_path', 'string', Registry::get('config.http_path')); ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="buttons-container">
            <input type="submit" class="btn btn-primary pull-left cm-no-ajax" name="dispatch[setup.correct_permissions]" value="<?php echo $this->t('correct_permissions'); ?>">
        </div>
    </form>
</div>

<form name="setup_step_form" method="post" action="index.php" class="form-horizontal cm-ajax cm-comet">
<input type="hidden" name="dispatch" value="setup.next_step">
<input type="hidden" name="database_settings[notify]" value="1">
<input type="hidden" name="database_settings[allow_override]" value="N">
<input type="hidden" name="server_settings[correct_permissions]" value="0">

    <h3><?php echo $this->t('server_configuration'); ?></h3>
    <?php echo $this->getNotificationsHtmlCode('server_configuration'); ?>

    <div class="setting-wrap">
        <div class="settings">
            <div class="requirement">
                <div class="control-group control-group-last">
                    <label class="control-label"><?php echo $this->t('store_url'); ?></label>
                    <div class="controls">
                        <div class="label-value">
                            <?php echo 'http://' . Tygh\Tools\Url::decode(Registry::get('config.http_location')); ?>
                            <input type="hidden" name="server_settings[http_host]" value="<?php echo Tygh\Tools\Url::decode($this->prepareVar($_tpl_vars, 'server_settings.http_host', 'string', Registry::get('config.http_host'))); ?>">
                            <input type="hidden" name="server_settings[http_path]" value="<?php echo $this->prepareVar($_tpl_vars, 'server_settings.http_path', 'string', Registry::get('config.http_path')); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="requirement">
                <a href="javascript:void(0);" data-toggle="collapse" data-target="#server_config_advanced_options" class="advanced-options-title collapsed"><span><?php echo $this->t('advanced'); ?></span><b class="caret"></b></a>
                <div id="server_config_advanced_options" class="collapse">
                    <div class="control-group">
                        <label class="control-label"><?php echo $this->t('secure_server_host_name'); ?></label>
                        <div class="controls">
                            <input type="text" name="server_settings[https_host]" value="<?php echo Tygh\Tools\Url::decode($this->prepareVar($_tpl_vars, 'server_settings.https_host', 'string', Registry::get('config.http_host'))); ?>">
                        </div>
                    </div>
                    <div class="control-group control-group-last">
                        <label class="control-label"><?php echo $this->t('secure_server_host_directory'); ?></label>
                        <div class="controls">
                            <input type="text" name="server_settings[https_path]" value="<?php echo $this->prepareVar($_tpl_vars, 'server_settings.https_path', 'string', Registry::get('config.http_path')); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings">
            <div class="requirement">
                <div class="control-group">
                    <label class="control-label cm-required" for="database_settings_host"><?php echo $this->t('mysql_server_host'); ?></label>
                    <div class="controls">
                        <input type="text" name="database_settings[host]" id="database_settings_host" value="<?php echo $this->prepareVar($_tpl_vars, 'database_settings.host', 'string', 'localhost'); ?>">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="database_settings_name"><?php echo $this->t('mysql_database_name'); ?></label>
                    <div class="controls">
                        <input type="text" name="database_settings[name]" id="database_settings_name" value="<?php echo $this->prepareVar($_tpl_vars, 'database_settings.name'); ?>">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label cm-required" for="database_settings_user"><?php echo $this->t('mysql_user'); ?></label>
                    <div class="controls">
                        <input type="text" name="database_settings[user]" id="database_settings_user" value="<?php echo $this->prepareVar($_tpl_vars, 'database_settings.user'); ?>">
                    </div>
                </div>
                <div class="control-group control-group-last">
                    <label class="control-label" for="database_settings_password"><?php echo $this->t('mysql_password'); ?></label>
                    <div class="controls">
                        <input type="text" name="database_settings[password]" id="database_settings_password" value="<?php echo $this->prepareVar($_tpl_vars, 'database_settings.password'); ?>">
                    </div>
                </div>
            </div>

            <div class="requirement">
                <a href="javascript:void(0);" data-toggle="collapse" data-target="#server_config_prefix_advanced_options" class="advanced-options-title collapsed"><span><?php echo $this->t('advanced'); ?></span><b class="caret"></b></a>
                <div id="server_config_prefix_advanced_options" class="collapse">
                    <div class="control-group control-group-last">
                        <label class="control-label"><?php echo $this->t('table_prefix'); ?></label>
                        <div class="controls">
                            <input type="text" name="database_settings[table_prefix]" value="<?php echo $this->prepareVar($_tpl_vars, 'database_settings.table_prefix', 'string', App::DEFAULT_PREFIX); ?>"> (<?php echo $this->t('optional'); ?>)
                        </div>
                    </div>

                    <?php
                        if (!empty($_tpl_vars['db_types'])):
                    ?>
                    <div class="control-group control-group-last">
                        <label class="control-label"><?php echo $this->t('database_backend'); ?></label>
                        <div class="controls">
                            <select name="database_settings[database_backend]">
                                <?php foreach ($_tpl_vars['db_types'] as $type => $type_descr): ?>
                                    <option value="<?php echo $type;?>" <?php if ($this->prepareVar($_tpl_vars, 'database_settings.database_backend', 'string') == $type): ?>selected="selected" <?php endif; ?>><?php echo $type_descr; ?></option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>
                    <?php
                        endif;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <h3><?php echo $this->t('administration_settings'); ?></h3>
    <?php echo $this->getNotificationsHtmlCode('administration_settings'); ?>

    <div class="setting-wrap">
        <div class="settings">
            <div class="requirement">
                <div class="control-group">
                    <label class="control-label cm-required cm-email" for="cart_settings_email"><?php echo $this->t('administrator_email'); ?></label>
                    <div class="controls">
                        <input type="text" name="cart_settings[email]" id="cart_settings_email" value="<?php echo $this->prepareVar($_tpl_vars, 'cart_settings.email'); ?>">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label cm-required" for="cart_settings_password"><?php echo $this->t('administrator_password'); ?></label>
                    <div class="controls">
                        <input type="password" name="cart_settings[password]" id="cart_settings_password" value="<?php echo $this->prepareVar($_tpl_vars, 'cart_settings.password'); ?>">
                    </div>
                </div>

                <div class="control-group control-group-last">
                    <label class="control-label"><?php echo $this->t('main_language'); ?></label>
                    <div class="controls">
                        <select name="cart_settings[main_language]" class="cm-main-language">
                            <?php foreach ($_tpl_vars['languages'] as $lang_code => $language): ?>
                                <option value="<?php echo $lang_code;?>" <?php if ($this->prepareVar($_tpl_vars, 'cart_settings.main_language', 'string') == $lang_code): ?>selected="selected" <?php endif; ?>><?php echo $language; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="cart_settings[secret_key]" value="<?php echo substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 10); ?>">
            </div>
            <div class="requirement">
                <a href="javascript:void(0);" data-toggle="collapse" data-target="#administration_settings_advanced_options" class="advanced-options-title collapsed"><span><?php echo $this->t('advanced'); ?></span><b class="caret"></b></a>
                <div id="administration_settings_advanced_options" class="collapse">
                    <div class="control-group control-group-last language-choice">
                        <label class="control-label"><?php echo $this->t('additional_languages'); ?></label>

                        <input class="cm-additional-lang-main" type="hidden" name="cart_settings[languages][]" value="<?php
                            echo $this->prepareVar($_tpl_vars, 'cart_settings.main_language', 'string');
                        ?>">

                        <div class="controls">
                            <?php foreach ($_tpl_vars['languages'] as $lang_code => $language): ?>
                                <label class="checkbox"><input class="cm-additional-lang-item" id="additional_lang_<?php echo $lang_code;?>" type="checkbox" name="cart_settings[languages][]" value="<?php echo $lang_code;?>"<?php if ($this->prepareVar($_tpl_vars, 'cart_settings.main_language', 'string') == $lang_code): ?>checked="checked" data-ca-auto-ticked="true" disabled="disabled" <?php endif; ?>><?php echo $language; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings">
            <div class="requirement">
                <label class="checkbox">
                    <input type="checkbox" name="cart_settings[demo_catalog]" value="Y">
                    <?php echo $this->t('install_demo_data'); ?>
                    <span class="muted annotate"><?php echo $this->t('install_demo_data_text'); ?></span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="cart_settings[feedback_auto]" value="Y" checked="checked">
                    <?php echo $this->t('help_us_improve_cart', array('product_name' => $this->getProductName())); ?>
                    <span class="muted annotate"><?php echo $this->t('help_us_improve_cart_text', array('product_name' => $this->getProductName())); ?></span>
                </label>
            </div>
        </div>
    </div>

    <?php
    // Disable theme selector, base theme should be installed always
    ?>
    <?php if (0 && count($_tpl_vars['available_themes']) > 1): ?>
        <h3><?php echo $this->t('select_storefront_theme'); ?></h3>
        <div class="theme-gallery">
            <?php $themes_iteration = 0; ?>
            <?php foreach ($_tpl_vars['available_themes'] as $theme_name => $theme_preview_image): ?>
                <?php $themes_iteration++; ?>
                <?php if ($themes_iteration % 3 == 1 || $themes_iteration == 1):?>
                    <div class="gallery-line cm-theme-item<?php if ($themes_iteration > 3) {echo " hidden";} ?>">
                <?php endif;?>
                <div class="item <?php if ($theme_name == App::THEME_NAME) {echo ' item-checked';} ?>">
                    <img src="../<?php echo $theme_preview_image; ?>" alt="<?php echo $theme_name; ?>" width="200" class="cm-bootstrap-item">
                    <span class="title"><?php echo $this->t('selected'); ?></span>
                    <input type="hidden" name="cart_settings[theme_name]" <?php if ($theme_name != App::THEME_NAME) {echo 'disabled="disabled"';} ?> value="<?php echo $theme_name; ?>">
                </div>
                <?php if ($themes_iteration % 3 == 0 || $themes_iteration == count($_tpl_vars['available_themes'])):?>
                    </div>
                <?php endif;?>
            <?php endforeach; ?>
        </div>
        <?php if (count($_tpl_vars['available_themes']) > 3): ?>
            <button class="btn btn-block cm-load-themes"><?php echo $this->t('load_more_themes'); ?></button>
        <?php endif; ?>
    <?php else: ?>
        <input type="hidden" name="cart_settings[theme_name]" value="<?php echo App::THEME_NAME ?>">
    <?php endif; ?>
</div>

<div class="modal-footer">
    <input type="submit" class="btn btn-primary btn-large" name="dispatch[setup.next_step]" value="<?php echo $this->t('install'); ?>">
</div>

</form>
<?php
    require 'design/templates/common/footer.php';
