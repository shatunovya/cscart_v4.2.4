<?php
    require 'design/templates/common/header.php';
?>

<form name="license_step_form" action="index.php" method="post">

<div id="content">
    <?php
        echo $this->getNotificationsHtmlCode();
    ?>

    <p class="muted"><?php echo $this->t('installation_agreement'); ?></p>

    <h3><?php echo $this->t('license_agreement'); ?></h3>
    <textarea cols="70" rows="12" class="license-agreement" readonly="readonly">
        <?php
            if (file_exists('../copyright_' . strtolower(PRODUCT_EDITION) . '.txt')) {
                readfile('../copyright_' . strtolower(PRODUCT_EDITION) . '.txt');
            } elseif (file_exists('../copyright.txt')) {
                readfile('../copyright.txt');
            }
        ?>
    </textarea>
    <p class="muted right"><?php echo $this->t('installation_instructions'); ?></p>

    <input type="hidden" name="dispatch" value="license.next_step">
</div>

<div class="modal-footer">
        <div class="agreements pull-left">
            <input id="license_agreement" class="pull-left" type="checkbox" name="license_agreement" value="Y" <?php if ($this->prepareVar($_tpl_vars, 'license_agreement', 'bool')): ?>checked="checked" <?php endif; ?>>
            <label class="license-agreement-checkbox pull-left cm-required" for="license_agreement">
                &nbsp;<?php echo $this->t('accept_license', array('product_name' => $this->getProductName())); ?>
            </label>
        </div>
    <input type="submit" name="dispatch[license.next_step]" class="btn btn-primary btn-large" value="<?php echo $this->t('next_step'); ?>">
</div>

</form>

<?php
    require 'design/templates/common/footer.php';
