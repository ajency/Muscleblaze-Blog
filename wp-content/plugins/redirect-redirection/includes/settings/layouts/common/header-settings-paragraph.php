<?php
if (!defined("ABSPATH")) {
    exit();
}
?>
<p class="header__paragraph ir-settings-paragraph">
    <?php
    $defaultSettingsSerialized = maybe_serialize($this->getDefaultSettings());
    $userSettingsSerialized = maybe_serialize($this->getData());
    if (md5($defaultSettingsSerialized) === md5($userSettingsSerialized)) {
        ?>
        <span><?php _e( "…with the ", "redirect-redirection" ); ?></span>
        <span role="button" tabindex="1" class="highlighted ir-default-settings-show"><?php _e( "default settings", "redirect-redirection" ); ?></span>
    <?php } else { ?>
        <span><?php _e( "…with your ", "redirect-redirection" ); ?></span>
        <span role="button" tabindex="1" class="highlighted ir-default-settings-show"><?php _e( "tailored settings", "redirect-redirection" ); ?></span>
    <?php } ?>
</p>