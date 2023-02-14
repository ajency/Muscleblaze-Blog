<?php
if (!defined("ABSPATH")) {
    exit();
}

$logs = $this->dbManager->logGet();

$args = ["count" => true];
$countLogs = $this->dbManager->logGet($args);

$logsPerPage = ($lpp = ((int) apply_filters("irrp_logs_per_page", self::PER_PAGE_LOGS))) > 0 ? $lpp : self::PER_PAGE_LOGS;

$countPages = ceil($countLogs / $logsPerPage);
?>
<?php include_once "redirection-and-404-logs/header.php"; ?>
<!-- redirect-content--empty -->
<div class="redirect-content">
  <!-- <section class="redirect-content redirect-content--empty"> -->
    <?php if ($countLogs) { ?>
        <h2 class="redirect-content__title"><?php esc_html_e("Redirection & 404 logs", "redirect-redirection"); ?></h2>
        <?php include_once "redirection-and-404-logs/actions.php"; ?>
        <?php include_once "redirection-and-404-logs/data-table.php"; ?>        
    <?php } else { ?>
        <h2 class="redirect-content__title redirect-content__title--no-logs___changed"><?php esc_html_e("No logs yet", "redirect-redirection"); ?></h2>
    <?php } ?>   
</div>