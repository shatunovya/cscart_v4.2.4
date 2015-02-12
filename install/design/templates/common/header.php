<!DOCTYPE html>
<html>
<head>
    <title><?php echo $this->t('title', array('product_name' => $this->getProductName())); ?></title>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="chrome=1">

    <link href="../install/design/css/lib/twitterbootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../install/design/css/styles.css" rel="stylesheet">
    <link href="../design/backend/css/lib/ui/jqueryui.css" rel="stylesheet">

    <script src="../js/lib/jquery/jquery.min.js"></script>
    <script src="../js/tygh/core.js"></script>
    <script src="../js/tygh/ajax.js"></script>
    <script src="../js/tygh/history.js"></script>

    <script src="../js/lib/jqueryui/jquery-ui.custom.min.js"></script>
    <script src="../js/tygh/editors/_empty.editor.js"></script>

    <script src="../js/lib/appear/jquery.appear-1.1.1.js"></script>
    <script src="../js/lib/tools/tooltip.min.js"></script>

    <script src="../js/lib/twitterbootstrap/bootstrap.min.js"></script>
    <script src="../install/design/js/installer.js"></script>
    <script src="../js/lib/autonumeric/autoNumeric.js"></script>

    <script type="text/javascript">
    //<![CDATA[
    (function(_, $) {
        _.tr({
            error_validator_email: '<?php echo $this->t('error_validator_email'); ?>',
            error_validator_required: '<?php echo $this->t('error_validator_required'); ?>'
        });

        $(document).ready(function(){
            $.runCart('C');
        });

    }(Tygh, Tygh.$));
    //]]>
    </script>

    <meta http-equiv="Content-Language" content="<?php echo $_tpl_vars['current_language']; ?>" />
</head>
<body>

<!-- COMET container -->
<a id="comet_container_controller" data-backdrop="static" data-keyboard="false" href="#comet_control" data-toggle="modal" class="hide"></a>

<div class="modal hide fade" id="comet_control" tabindex="-1" role="dialog" aria-labelledby="comet_title" aria-hidden="true">
    <div class="modal-header">
        <h3 id="comet_title"><?php echo $this->t('processing'); ?></h3>
    </div>
    <div class="modal-body">
        <p></p>
        <div class="progress progress-striped active">
            <div class="bar" style="width: 0%;"></div>
        </div>
    </div>
</div>

<div id="main">
    <div id="comet_container" title="<?php echo $this->t('processing'); ?>"></div>

    <div class="navbar navbar-inverse navbar-install-header">
        <div class="navbar-inner">
            <div class="pull-left">
                <span class="name"><?php echo $this->getProductName(); ?></span>
                <span class="version"><?php echo $this->getProductVersion(); ?></span>
            </div>

            <ul class="nav pull-right">
                <li class="dropdown dropdown-top-menu-item">
                    <a href="javascript:void(0);" data-toggle="dropdown">
                        <?php echo $_tpl_vars['installer_languages'][$_tpl_vars['current_language']]; ?>
                        <b class="caret"></b>
                    </a>
                    <ul class="dropdown-menu">
                        <?php foreach ($_tpl_vars['installer_languages'] as $lang_code => $language): ?>
                            <li class="<?php if ($_tpl_vars['current_language'] == $lang_code) {echo 'active';} ?>"><a href="index.php?dispatch=<?php echo $_tpl_vars['dispatch']; ?>&sl=<?php echo $lang_code; ?>"><?php echo $language; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
