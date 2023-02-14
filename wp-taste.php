<?php

// Namespace
namespace TasteWP\Integration;

// Exit on direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * TasteWP Integration Module
 * Allows communication with dashboard
 * Sees notices about maintenances
 * Includes into about expiration time
 */
class TasteWP_Internal_Integration {

  /* WEBSITE AUTHENTICATION START */
  private $siteUsername = 'mamata';
  private $sitePassword = 'UjEK9_C1N9w';
  private $integrationVersion = '3.4.4';
  private $plannedExpiration = '1676449630000';
  /* WEBSITE AUTHENTICATION END */

  /**
   * Static variables
   */
  private $pluginCoreIncluded = false;
  private $activatedPlugins = [];
  private $knownPlugins = [
    "backup-backup/backup-backup.php",
    "redirect-redirection/redirect-redirection.php",
    "copy-delete-posts/copy-delete-posts.php",
    "pop-up-pop-up/pop-up-pop-up.php",
    "wp-file-manager/file_folder_manager.php",
    "wp-console/wp-console.php"
  ];

  /**
   * __construct - Integration Initializer
   *
   * @return @self
   */
  function __construct() {

    // Activation of Plugins
    add_action('init', [$this, 'verifyActivation']);

    // Update database schema version
    add_action('init', [$this, 'updateDatabaseVersion'], -50);

    // AJAX Handler
    add_action('init', [$this, 'ajaxHandlers'], -40);

    // Intro modules (website overview)
    add_action('init', [$this, 'initializeIntroActions'], -30);

    // Password less login module
    add_action('init', [$this, 'loginPageRedirectionWhileLogged'], -20);
    add_action('init', [$this, 'passwordLessLoginModule'], -10);

    // Automatic redirection (onboarding)
    add_action('admin_init', [$this, 'initializeRedirectionModule'], 100000);
    add_action('tastewp_do_base_plugin_redirect', [$this, 'performSmartRedirection']);

    // Head constants
    add_action('admin_head', [$this, 'headContants']);

    // Integration in admin bar
    add_action('admin_bar_menu', [$this, 'adminBarMenuIntegration'], 80);

    // WordPress fixes
    add_action('init', [$this, 'wordPressFixes']);

  }

  /**
   * coreInitialization - Initializes core of TasteWP Integration
   *
   * @return void
   */
  public function coreInitialization() {

    if (!get_option('__tastewp_initialized', false)) {

      update_option('__tastewp_initialized', true);
      

      if (!file_exists(ABSPATH . 'active.html')) {
        $this->makePluginInitializeCall();
        $this->skipOnboarding();
      }

      do_action('tastewp_activation_hook');
      do_action('__tastewp_initialized_after_plugins');
    }

  }

  /**
   * verifyActivation - Processes request
   *
   * @return void
   */
  public function verifyActivation() {

    // Handle Plugin Initialization SUB-REQUEST
    if (isset($_GET['tastewp_activation']) && $_GET['tastewp_activation'] == 'plugins' && !get_option('__tastewp_sub_requested', false)) {

      update_option('__tastewp_sub_requested', true);

      $this->initializePlugins();
      $this->adjustPluginConfigurations();

      sleep(1);
      echo 'Activation of plugins finished.';

      exit;

    }

  }

  public function adjustPluginConfigurations() {

    // Fix for AutoLogin with SiteGuard
    if (is_plugin_active('siteguard/siteguard.php')) {
      $sgConfig = get_option('siteguard_config', false);
      if ($sgConfig && is_array($sgConfig) && isset($sgConfig['renamelogin_enable'])) {
        $sgConfig['renamelogin_enable'] = 0;
        update_option('siteguard_config', $sgConfig);
      }
    }

  }

  /**
   * makePluginInitializeCall - Makes HTTP call to sub request for plugin activation
   *
   * @return void
   */
  public function makePluginInitializeCall() {

    $url = home_url() . '?tastewp_activation=plugins';

    $response = wp_remote_get($url, ['redirection' => 0]);
    $body = wp_remote_retrieve_body($response);

  }

  /**
   * includePluginFunctions - Includes plugins.php functions (wp core)
   *
   * @return void
   */
  public function includePluginFunctions() {

    if ($this->pluginCoreIncluded) return;

    if (!defined('WP_ADMIN')) {
      define('WP_ADMIN', true);
    }

    if (!function_exists('activate_plugin')) {
      require_once (ABSPATH . 'wp-admin/includes/plugin.php');

      $this->pluginCoreIncluded = true;
    }

  }

  /**
   * skipOnboarding - Skips known onboarding redirections
   * Also flushes rewrite rules
   *
   * @return void
   */
  public function skipOnboarding() {

    // Elementor
    update_option('elementor_onboarded', true);

    // WooCommerce
    update_option('woocommerce_version', '10.0.0');
    update_option('woocommerce_onboarding_profile', ["skipped" => true]);
    update_option('woocommerce_task_list_welcome_modal_dismissed', 'yes');
    delete_transient('_wc_activation_redirect');

    // Analyst Module (subplugin)
    update_option('analyst_cache', array());

    // Redirect Redirection
    update_option('irrp_activation_redirect', false);

    // Backup Migration
    delete_option('_bmi_redirect');

    // Copy Delete Posts
    delete_option('_cdp_redirect');

    // MyPopUps
    delete_option('wp_mypopups_do_activation_redirect');

    // WP Clone
    delete_option('wpa_wpc_plugin_do_activation_redirect');

    // Brave builder
    delete_transient('_fl_builder_activation_admin_notice');

    // Blocksy
    update_option('blc_activation_redirect', false);

    // Polylang
    delete_transient('pll_activation_redirect');

    // Flush rewrite rules
    flush_rewrite_rules();

  }

  /**
   * isPluginActive - Checks if particular plugin was activated
   *
   * @param  {string} $slug slug of the plugin
   * @return array of bool [is active in db, is active in php]
   */
  public function isPluginActive($slug) {

    $this->includePluginFunctions();

    $plugins = get_option('active_plugins');
    foreach ($plugins as $key => $value) {

      if (strpos($value, $slug) !== false) {
        return [$value, is_plugin_active($value)];
      }

    }

    return [false, false];

  }

  /**
   * getAdditionalPlugins - Checks if there is any unknown plugin included
   *
   * @return array
   */
  public function getAdditionalPlugins() {

    $this->includePluginFunctions();

    $plugins = array_keys(get_plugins());
    usort($plugins, function ($a, $b) { return strlen($a) - strlen($b); });
    $plugins = array_values($plugins);

    $finalPlugins = array_values(array_diff($plugins, $this->knownPlugins));

    return $finalPlugins;

  }

  /**
   * maybeActivatePlugin - Tries to activate particular plugins
   * Also tries to find proper order of activation (extensions)
   *
   * @param  array $plugins               list of plugins to be activated (slugs)
   * @param  array $failed_plugins = []   local variable of failed plugins
   * @return array                        results
   */
  public function maybeActivatePlugin($plugins, $failed_plugins = []) {

    $this->includePluginFunctions();

    $plugins_copy = array_values($plugins);
    $should_continue = false;
    $activated_plugins = [];
    $failed_plugins = $failed_plugins;

    for ($i = 0; $i < sizeof($plugins_copy); ++$i) {

      $plugin_name = $plugins_copy[$i];
      $shouldActivate = true;

      if (empty($plugin_name)) {
        $shouldActivate = false;
      } else if (strpos($plugin_name, '/') === false) {
        $shouldActivate = false;
      }

      if ($shouldActivate) {

        try {

          if (!function_exists('validate_plugin_requirements') || validate_plugin_requirements($plugin_name)) {

            echo 'Trying to activate plugin: ' . $plugin_name . "\n";
            $resultWP = activate_plugin($plugin_name, '', is_multisite());
            $this->skipOnboarding();

            $activated_plugins[] = $plugin_name;
            $this->activatedPlugins[] = $plugin_name;

            $should_continue = true;
            break;

          } else {

            if (!in_array($plugin_name, $failed_plugins)) {
              $failed_plugins[] = $plugin_name;
            }

          }

        } catch (\Exception $e) {

          if (!in_array($plugin_name, $failed_plugins)) {

            $failed_plugins[] = $plugin_name;
            error_log($e);

          }

        } catch (\Throwable $e) {

          if (!in_array($plugin_name, $failed_plugins)) {

            $msg = $e->getMessage();
            if (strpos($msg, 'add_rule()') != false || strpos($msg, 'rewrite.php:143') != false) {

              $activated_plugins[] = $plugin_name;
              error_log($e);

            } else {

              $failed_plugins[] = $plugin_name;
              error_log($e);

            }

          }

        }

      }

    }

    return [ 'failed' => $failed_plugins, 'active' => $activated_plugins, 'should_continue' => $should_continue ];

  }

  /**
   * initializePlugins - Runs plugin initializer (enables all plugins)
   *
   * @return void
   */
  public function initializePlugins() {

    register_shutdown_function([$this, 'executionShutdown']);
    $this->includePluginFunctions();

    wp_clear_auth_cookie();
    wp_set_current_user(1);
    wp_set_auth_cookie(1);
    if (function_exists('grant_super_admin')) grant_super_admin(1);

    $plugins = array_keys(get_plugins());
    usort($plugins, function ($a, $b) { return strlen($a) - strlen($b); });
    $plugins = array_values($plugins);

    $finalPlugins = array_values(array_diff($plugins, $this->knownPlugins));
    $additionalPlugins = array_values(array_diff($plugins, $finalPlugins));

    $plugins = array_values(array_merge($additionalPlugins, $finalPlugins));

    // Activate CF7 plugin in first place if exist
    $cf7plugin = array_search('contact-form-7/wp-contact-form-7.php', $plugins);
    if ($cf7plugin) { unset($plugins[$cf7plugin]); array_unshift($plugins, 'contact-form-7/wp-contact-form-7.php'); }

    // Fix for cf7-grid-layout/cf7-grid-layout.php plugin
    $ai = array_search('cf7-grid-layout/cf7-grid-layout.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'cf7-grid-layout/cf7-grid-layout.php'; }

    // Fix for otp-by-email/otp-by-email.php plugin
    $ai = array_search('otp-by-email/otp-by-email.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'otp-by-email/otp-by-email.php'; }

    // Fix for cf7-google-map/cf7-googleMap.php plugin
    $ai = array_search('cf7-google-map/cf7-googleMap.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'cf7-google-map/cf7-googleMap.php'; }

    // Fix for oliver-pos/oliver-pos.php plugin
    $ai = array_search('oliver-pos/oliver-pos.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'oliver-pos/oliver-pos.php'; }

    // Fix for polylang/polylang.php plugin
    $ai = array_search('polylang/polylang.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'polylang/polylang.php'; }

    // Fix for cf7-polylang/cf7-polylang.php plugin
    $ai = array_search('cf7-polylang/cf7-polylang.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'cf7-polylang/cf7-polylang.php'; }

    // Fix for bp-job-manager/bp-job-manager.php
    $ai = array_search('bp-job-manager/bp-job-manager.php', $plugins);
    if ($ai) { unset($plugins[$ai]); $plugins[] = 'bp-job-manager/bp-job-manager.php'; }

    // Final index fix
    $plugins = array_values($plugins);

    $fullyActive = [];
    $failed_plugins = [];

    $one_more_time = true;
    $try_again = true;

    if (file_exists(WP_CONTENT_DIR . '/.tsw')) {
      return;
    } else {
      file_put_contents(WP_CONTENT_DIR . '/.tsw', '');
    }

    if (array_search('buddypress/bp-loader.php', $plugins) && array_search('wp-job-manager/wp-job-manager.php', $plugins) && array_search('bp-job-manager/bp-job-manager.php', $plugins)) {

      activate_plugins($plugins, self_admin_url('plugins.php?error=true'), is_multisite());

      file_put_contents(ABSPATH . 'active.html', '');
      return;

    }

    while ($try_again) {

      $res = $this->maybeActivatePlugin($plugins, $failed_plugins);

      $fullyActive = array_unique(array_merge($fullyActive, $res['active']));
      $plugins = array_diff($plugins, $res['active']);
      $failed_plugins = array_unique(array_diff(array_merge($failed_plugins, $res['failed']), $fullyActive));

      update_option('active_plugins', $fullyActive);

      $try_again = $res['should_continue'];
      if ($try_again == false && $one_more_time == true) {
        $one_more_time = false;
        $try_again = true;
      }

    }

    $this->skipOnboarding();
    file_put_contents(ABSPATH . 'active.html', '');

  }

  /**
   * executionShutdown - Runs at code execution shutdown
   *
   * @return void
   */
  public function executionShutdown() {

    if (file_exists(WP_CONTENT_DIR . '/.tsw')) {
      return;
    } else {
      file_put_contents(WP_CONTENT_DIR . '/.tsw', '');
    }

    $active = $this->activatedPlugins;
    $plugins = array_keys(get_plugins());
    $diff = array_diff($plugins, $active);

    $activate = array_merge($active, $diff);

    // update_option('active_plugins', $activate);
    $this->skipOnboarding();

    file_put_contents(ABSPATH . 'active.html', '');
    do_action('__tastewp_initialized_after_plugins');

  }

  /**
   * smartRedirectionStyles - Styles of Smart Redirection
   *
   * @return void, prints styles
   */
  public function smartRedirectionStyles() {
    ?>

    <!-- TasteWP Smart Redirection Styles -->
    <style media="screen" id="tastewp_pre_redirect_styles_white">
      #wpadminbar {
        display: none !important;
      }

      html, body {
        pointer-events: none;
        overflow: hidden;
      }

      #preloader-tsw {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        font-family: Arial, Helvetica, sans-serif;
        background: #ECE9E6;
        background: -webkit-linear-gradient(to bottom, #fff, #ECE9E6);
        background: linear-gradient(to bottom, #fff, #ECE9E6);
        min-height: 100vh;
        min-width: 100vw;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
      }

      #preloader-tsw sup {
        font-size: 19px !important;
        color: #666 !important;
        position: relative;
        bottom: 5px;
      }

      @-webkit-keyframes breathing {
        0% {
          -webkit-transform: scale(0.9);
          transform: scale(0.9);
        }

        50% {
          -webkit-transform: scale(1);
          transform: scale(1);
        }

        100% {
          -webkit-transform: scale(0.9);
          transform: scale(0.9);
        }
      }

      @keyframes breathing {
        0% {
          -webkit-transform: scale(0.9);
          -ms-transform: scale(0.9);
          transform: scale(0.9);
        }

        50% {
          -webkit-transform: scale(1);
          -ms-transform: scale(1);
          transform: scale(1);
        }

        100% {
          -webkit-transform: scale(0.9);
          -ms-transform: scale(0.9);
          transform: scale(0.9);
        }
      }

      #center-tsw {
        -webkit-animation: breathing 1.5s ease-out infinite normal;
        animation: breathing 1.5s ease-out infinite normal;
        -webkit-font-smoothing: antialiased;
        text-align: center;
      }
    </style>

    <?php
  }

  /**
   * smartRedirectionScript - JS Script of Smart Redirection
   *
   * @return void, prints JS script
   */
  public function smartRedirectionScript() {
    ?>

    <!-- TasteWP Smart Redirection JS Script -->
    <script type="text/javascript">
      (function () {

        try {

          function getDifferenceInNavItems() {

            let currItems = [];
            document.querySelectorAll('#adminmenu [href]').forEach((item) => {
              currItems.push(item.getAttribute('href'));
            });

            let known = ["index.php","update-core.php","edit.php","post-new.php","edit-tags.php?taxonomy=category","edit-tags.php?taxonomy=post_tag","upload.php","media-new.php","edit.php?post_type=page","post-new.php?post_type=page","edit-comments.php","themes.php","customize.php?return=%2Fwp-admin%2Fplugins.php%3Fplugin_status%3Dall%26paged%3D1%26s","widgets.php","nav-menus.php","customize.php?return=%2Fwp-admin%2Fplugins.php%3Fplugin_status%3Dall%26paged%3D1%26s&autofocus%5Bcontrol%5D=background_image","themes.php?page=custom-background","theme-editor.php","plugins.php","plugin-install.php","plugin-editor.php","users.php","user-new.php","profile.php","tools.php","import.php","export.php","site-health.php","export-personal-data.php","erase-personal-data.php","options-general.php","options-writing.php","options-reading.php","options-discussion.php","options-media.php","options-permalink.php","options-privacy.php","admin.php?page=irrp-redirection","admin.php?page=copy-delete-posts","admin.php?page=backup-migration","admin.php?page=wp-mypopups","admin.php?page=wp_file_manager","admin.php?page=wp_file_manager_settings","admin.php?page=wp_file_manager_root","admin.php?page=wp_file_manager_properties","admin.php?page=wp_file_manager_shortcode_doc","admin.php?page=wpfm-logs","admin.php?page=wpfm-backup","admin.php?page=wp_file_manager_sys_properties","admin.php?page=wp_file_manager_preferences","themes.php?page=custom-header","site-editor.php","site-editor.php?postType=wp_template&postId=twentytwentytwo%2F%2Fhome"]

            let diff = currItems.filter(x => (!known.includes(x) && !x.includes('customize.php?return=')));

            return diff;

          }

          if (!localStorage.getItem('tastewp_smart_redirection')) {
            localStorage.setItem('tastewp_smart_redirection', 'performed');

            let preloader = document.querySelector('#preloader-tsw');
            let bodySibling = document.querySelector('body').nextSibling;
            document.querySelector('html').insertBefore(preloader, bodySibling);

            jQuery.post(ajaxurl, { 'action': 'auto_smart_tastewp_redirect_performed' }, function () {

              let newMenus = getDifferenceInNavItems();
              if (typeof newMenus == 'object' && newMenus.length > 0 && !window.location.href.includes(newMenus[0])) {

                window.location.href = newMenus[0];

              } else {

                document.querySelector('#tastewp_pre_redirect_styles_white').remove();
                document.querySelector('#preloader-tsw').remove();

              }

            }).always(function() {

              setTimeout(function () {
                document.querySelector('#tastewp_pre_redirect_styles_white').remove();
                document.querySelector('#preloader-tsw').remove();
              }, 1000);

            });

          }

        } catch (e) {

          if (document.querySelector('#tastewp_pre_redirect_styles_white')) {
            document.querySelector('#tastewp_pre_redirect_styles_white').remove();
          }

          if (document.querySelector('#preloader-tsw')) {
            document.querySelector('#preloader-tsw').remove();
          }

          console.error(e);

        }

      })();
    </script>

    <?php
  }

  /**
   * smartRedirectionHTML - HTML of Smart Redirection preloader
   *
   * @return void, prints HTML
   */
  public function smartRedirectionHTML() {
    ?>

    <!-- TasteWP Smart Redirection HTML -->
    <div id="preloader-tsw">
      <div id="center-tsw">
        <div>
          <sup><b>Loading selected plugin(s) and making safe redirect...</b></sup>
        </div>
        <img src="https://tastewp.com/assets/svgs/tlogo.svg" height="120px" alt="T Letter">
      </div>
    </div>

    <?php
  }

  /**
   * performSmartRedirection - Smart Redirection Module
   * Allows to find proper menu to redirect (any plugin)
   *
   * @return void
   */
  public function performSmartRedirection() {

    if (!is_admin()) return;

    $this->smartRedirectionStyles();
    $this->smartRedirectionHTML();
    $this->smartRedirectionScript();

  }

  /**
   * useSmartRedirectModule - Enables Smart Redirect Module
   *
   * @return void
   */
  public function useSmartRedirectModule() {

    add_action('admin_notices', function() {
      do_action('tastewp_do_base_plugin_redirect');
    }, -1000);

  }

  /**
   * initializeRedirectionModule - Main Redirection Code
   * Handles Parameters and Predefined Redirections
   *
   * @return void
   */
  public function initializeRedirectionModule() {

    global $pagenow;

    if (!is_admin()) return;
    // if (!($pagenow == 'index.php' || $pagenow == 'plugins.php')) return;

    $menusPath = ABSPATH . 'tmp/menus';
    if (file_exists($menusPath) && is_readable($menusPath)) @unlink($menusPath);

    
    $redirection = 'false_use_menus';
    

    $tswFile = file_exists(WP_CONTENT_DIR . '/.tsw');
    $tswAutoload = get_option('__tastewp_autoload_inited', false);
    $tswRedirected = get_option('__tastewp_redirection_performed', false);

    if (is_user_logged_in() && $tswFile && !$tswRedirected && $_SERVER['REQUEST_METHOD'] === 'GET') {

      if ($tswAutoload == false) {
        update_option('__tastewp_autoload_inited', true);
        $this->skipOnboarding();

        $list = $this->getAdditionalPlugins();
        if (((is_object($list) || is_array($list)) && sizeof($list) <= 0) || !current_user_can('install_plugins')) {
          wp_safe_redirect(admin_url());
          exit;
        } else {
          if ($pagenow != 'plugins.php') {
            wp_safe_redirect(admin_url('/plugins.php'));
            exit;
          }
        }
      }

      $this->skipOnboarding();
      if ($redirection == 'false_use_menus') {

        if (get_option('auto_smart_tastewp_redirect_performed', false) === false) {
          $this->useSmartRedirectModule();
        }

      } else {

        update_option('__tastewp_redirection_performed', true);
        wp_safe_redirect(get_home_url() . '/' . $redirection);
        exit;

      }

    }

  }

  /**
   * updateDatabaseVersion - Updates database version to match schema
   *
   * @return void
   */
  public function updateDatabaseVersion() {

    global $wp_db_version;
    if (isset($_GET['check_updated']) && $_GET['check_updated'] == 'true' && $_GET['plug']) {

      update_option('db_version', $wp_db_version);
      update_option('initial_db_version', $wp_db_version);

      
      wp_clear_auth_cookie();
      wp_set_current_user(1);

      if (isset($_GET['active']) && $_GET['active'] == 'false')  {

          $this->coreInitialization();
          echo 'success';

      }
      

      exit;

    }

  }

  /**
   * assetURL - Prints URL to asset
   *
   * @param  {type} $name        name of the asset
   * @param  {type} $ext = 'svg' extension of the asset
   * @return void - prints URL
   */
  public function assetURL($name, $ext = 'svg') {

    echo esc_url('https://tastewp.com/intro/' . $name . '.' . $ext);

  }

  /**
   * initializeIntro - Initializes Intro Module
   *
   * @return void - prints HTML
   */
  public function initializeIntroActions() {

    // Intro actions
    add_action('tastewp_banners_intro', [$this, 'bigIntroModule']);
    add_action('tastewp_banners_intro_small', [$this, 'compactIntroModule']);

    // Go around for plugins that blocks notices
    add_action('in_admin_header', function () {
      add_action('admin_notices', function () {

        
          update_option('hide_tastewp_notice_small', 1);
        

        do_action('tastewp_banners_intro');

      });
    }, 9999);

    // Intro Script & Styles
    add_action('admin_enqueue_scripts', function () {
      if (!defined('TASTEWP_SCRIPT_VERSION')) {
        define('TASTEWP_SCRIPT_VERSION', $this->integrationVersion);
      }

      wp_enqueue_script('tastewp-intro-js', esc_url('https://tastewp.com/intro/script.js'), ['jquery'], TASTEWP_SCRIPT_VERSION, true);
      wp_enqueue_style('tastewp-intro-css', esc_url('https://tastewp.com/intro/style.css'), [], TASTEWP_SCRIPT_VERSION);
    });

  }

  /**
   * bigIntroModule - Prints Larger Intro HTML
   *
   * @return void - Prints HTML
   */
  public function bigIntroModule() {

    global $pagenow;
    if ($pagenow == 'widgets.php') return;

    $marginTop = '';
    if (function_exists('is_seopress_page')) {
      if (is_seopress_page()) {
        $marginTop = 'margin-top: 100px;';
      }
    }

    $should_hide = false;
    if (get_option('hide_tastewp_notice_small', false) == false) {
      $should_hide = true;
    }

    $username = $this->siteUsername;
    $password = $this->sitePassword;
    $domain = 'muscleblazee.s2-tastewp.com';
    $affiliate = 'QhjVgAwL';
    $affiliatedsite = false;

    $txt1 = 'Your site was set up successfully';

    ?>

    <input type="text" id="TSW_COPY" style="display: none;" hidden value="Copy">
    <input type="text" id="TSW_COPIED" style="display: none;" hidden value="Copied">
    <input type="text" id="TSW_COPYFAIL" style="display: none;" hidden value="Failed to copy">
    <input type="text" id="TSW_FAILED" style="display: none;" hidden value="Failed">
    <input type="text" id="TSW_DONTWANT" style="display: none;" hidden value="Don&#39;t want it to expire?">
    <input type="text" id="TSW_WANTMORE" style="display: none;" hidden value="Want more non-expiring sites?">
    <?php if (get_option('hide_tastewp_notice', false) != false || $should_hide) { ?>
    <input type="text" id="TSW_SHIDE_IN" style="display: none;" hidden value="true">
    <?php } else { ?>
    <input type="text" id="TSW_SHIDE_IN" style="display: none;" hidden value="false">
    <?php } ?>

    <div id="tastewp_intro" style="display: none;<?php echo $marginTop; ?>">
      <div class="tastewp_container">
        <div class="tastewp_header"><?php echo $txt1; ?> <img width="60px" src="<?php $this->assetURL('emoji'); ?>" /></div>
        <div class="tastewp_centred tastewp_flex tastewp__pr-login">
          <div class="tastewp_righted tastewp_relative tastewp_width_1">
            <img width="110px" class="tastewp_green_bg" src="<?php $this->assetURL('bg-small-shape', 'png'); ?>" />
            <img width="75px" class="tastewp_on_green" src="<?php $this->assetURL('computer'); ?>" />
          </div>
          <div class="tastewp_lefted tastewp_width_2" id="tastewp_details">
            <div>
              <b class="tastewp_w600">Login details:</b> <span id="tastewp_copy_btn" class="tastewp_copy">Copy</span>
            </div>
            <div>
              <img width="18px" class="tastewp_icon" src="<?php $this->assetURL('link', 'png'); ?>" />
              Admin area URL: <span class="tastewp_a" data-copy="https://<?php echo $domain; ?>/wp-admin">https://<?php echo $domain; ?>/wp-admin</span>
            </div>
            <div>
              <img width="16px" class="tastewp_icon" src="<?php $this->assetURL('user', 'png'); ?>" />
              Username: <span class="tastewp_a" data-copy="<?php echo $username; ?>"><?php echo $username; ?></span>
            </div>
            <div>
              <img width="16px" class="tastewp_icon" src="<?php $this->assetURL('password', 'png'); ?>" />
              Password: <span class="tastewp_a" data-copy="<?php echo $password; ?>"><?php echo $password; ?></span>
            </div>
          </div>
        </div>
        <div class="tastewp_flex tastewp_centred tastewp__pr-expiry">
          <div class="tastewp_righted tastewp_relative tastewp_width_1">
            <img width="110px" class="tastewp_green_bg" src="<?php $this->assetURL('bg-small-shape', 'png'); ?>" />
            <img width="75px" class="tastewp_on_green" src="<?php $this->assetURL('stoper'); ?>" />
          </div>
          <div class="tastewp_lefted tastewp_width_2">
            <div>
              <div class="tastewp_lh10">
                <b class="tastewp_w600">Expiry:</b>
              </div>
              <div class="tastewp_relative tastewp_inline">
                <div class="tastewp_flex tastewp_time-wrap">
                  <div class="tastewp_width_4">Your site will be automatically deleted in</div>
                  <div class="tastewp_clock">
                    <div class="tastewp_flex">
                      <span>&nbsp;&nbsp;</span>
                      <div id="tastewp_days" class="tastewp_time"><span class="above">00</span><br><span class="tastewp_undermute">Days</span></div>
                      <div class="tastewp_colon">:<br>&nbsp;</div>
                      <div id="tastewp_hours" class="tastewp_time"><span class="above">00</span><br><span class="tastewp_undermute">Hours</span></div>
                      <div class="tastewp_colon">:<br>&nbsp;</div>
                      <div id="tastewp_minutes" class="tastewp_time"><span class="above">00</span><br><span class="tastewp_undermute">Minutes</span></div>
                      <div class="tastewp_colon">:<br>&nbsp;</div>
                      <div id="tastewp_seconds" class="tastewp_time"><span class="above">00</span><br><span class="tastewp_undermute">Seconds</span></div>
                    </div>
                    <img class="tastewp_arrow tastewp_popped_div" src="<?php $this->assetURL('arrow', 'png'); ?>" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tastewp_relative tastewp_popped_div" id="tastewp_green_outbox">
          <div class="tastewp_popped">
            <div class="tastewp_centred">

              <div class="tastewp_header_green">
                Don&#39;t want it to expire?
              </div>
              <div class="tastewp_flex_2">
                <div class="tastewp_green_subbox">
                  <div class="tastewp_subbox_header tastewp_header_share">
                    <div class="tastewp_non_circle_icon_share"></div>
                    Share TasteWP
                  </div>
                  <?php if ($affiliate != 'false' && $affiliate != false) { ?>
                  <div class="tastewp_share_muted">
                    Share the link below with your followers &amp; friends.<br>
                    For every 3 people who sign up, you get one non-expiring site (up to 3)!
                  </div>
                  <div id="tastewp_affiliate_btn" class="tastewp_affiliate tastewp_w600 tastewp_a" data-copy="https://tastewp.com/r/<?php echo $affiliate; ?>" data-text="TasteWP.com/r/<?php echo $affiliate; ?>">
                    TasteWP.com/r/<?php echo $affiliate; ?>
                  </div>
                  <div class="tastewp_small">
                    (Click on button to copy link)
                  </div>
                  <?php } else { ?>
                  <div class="tastewp_share_muted">
                    <a href="https://tastewp.com/?open=login" target="_blank" class="tastewp_w600">Login to TasteWP</a> and then create a new site – you&#39;ll then see a link you
                    can share with your followers &amp; friends. For every 3 people who sign up,
                    you get one non-expiring site (up to 3)!
                  </div>
                  <?php } ?>
                </div>
                <div class="tastewp_middle_side">
                  and / or
                </div>
                <div class="tastewp_green_subbox">
                  <div class="tastewp_subbox_header tastewp_go_pro_header">
                    <div class="tastewp_non_circle_icon"></div>
                    Go Premium
                  </div>
                  <div class="tastewp_flex_3 tastewp_pro_featured_list">
                    <div class="tastewp_pro_featured">
                      <div class="tastewp_circle_icon tastewp_pro_web"></div>
                      No expiry
                    </div>
                    <div class="tastewp_pro_featured">
                      <div class="tastewp_circle_icon tastewp_pro_space"></div>
                      More space
                    </div>
                    <div class="tastewp_pro_featured">
                      <div class="tastewp_circle_icon tastewp_pro_support"></div>
                      Top support
                    </div>
                  </div>
                  <a href="https://tastewp.com/premium-show-checkout" target="_blank" class="tastewp_buy_now">
                    Buy now
                  </a>
                  <div class="tastewp_small">
                    From 2.98 USD/month only – cancel anytime
                  </div>
                  <a href="https://tastewp.com/premium-show" target="_blank" class="tastewp_pro_learn_more">
                    Learn more
                  </a>
                </div>
              </div>

            </div>
          </div>
        </div>
        <div class="tastewp_centred">
          <div id="tastewp_close_btn" class="tastewp_close tastewp_w600">
            Ok, got it!
          </div>
        </div>
        <div class="tastewp_powered_big">
          Powered by&nbsp;<a href="https://tastewp.com" target="_blank">TasteWP.com</a>
        </div>
      </div>
      <div>

      </div>
    </div>

    <?php

  }

  /**
   * compactIntroModule - Prints Compact Intro Module
   *
   * @return void
   */
  public function compactIntroModule() {

    if (get_option('hide_tastewp_notice_small', false) == 1) {
      return;
    }

    ?>

    <div id="tastewp-intro-small">

      <div class="tsw-small-background">

        <div class="tsw-cf-small">
          <div class="tsw-heading-small">Your test site will expire in:</div>
          <div class="tsw-times-btn" id="tsw-close-small">&times;</div>
        </div>

        <div class="tsw-expire-counter tsw-cf-small">

          <div class="tsw-left-float tsw-white-box">
            <div class="tsw-box-content" id="tastewp_days_small">00</div>
            <div class="tsw-box-label">Days</div>
          </div>
          <div class="tsw-left-float tsw-double-dot-separator">:</div>
          <div class="tsw-left-float tsw-white-box">
            <div class="tsw-box-content" id="tastewp_hours_small">00</div>
            <div class="tsw-box-label">Hours</div>
          </div>
          <div class="tsw-left-float tsw-double-dot-separator">:</div>
          <div class="tsw-left-float tsw-white-box">
            <div class="tsw-box-content" id="tastewp_minutes_small">00</div>
            <div class="tsw-box-label">Minutes</div>
          </div>
          <div class="tsw-left-float tsw-double-dot-separator">:</div>
          <div class="tsw-left-float tsw-white-box">
            <div class="tsw-box-content" id="tastewp_seconds_small">00</div>
            <div class="tsw-box-label">Seconds</div>
          </div>
          <div class="tsw-left-float tsw-see-more-button" id="tsw-seemore-small">See more</div>

          <div class="tsw-powered-by">
            <a href="https://tastewp.com" target="_blank" style="color:#aaa;text-decoration:none;">Powered by <span style="color:white;">TasteWP.com</span></a>
          </div>

        </div>

      </div>

    </div>

    <?php

  }

  /**
   * headContants - Prints constants in dashboard HEAD
   *
   * @return void
   */
  public function headContants() {
    ?>
    <script type="text/javascript">
      const TSWP_EXPIRE = <?php echo $this->plannedExpiration; ?>;
      <?php
      $updateFile = ABSPATH . 'tsw-updates.php';
      if (file_exists($updateFile)) {
      ?>
      window.tastewpForcedUpdate = true;
      <?php } else { ?>
      window.tastewpForcedUpdate = false;
      <?php } ?>
    </script>
    <?php
  }

  /**
   * adminBarMenuIntegration - Initializes integration in admin bar
   *
   * @param  array $admin_bar Current Admin Bar contents
   * @return array            Updated $admin_bar
   */
  public function adminBarMenuIntegration($admin_bar) {

    global $pagenow;
    if ($pagenow == 'widgets.php') return;

    if (!is_admin()) return;
    $args = array(
      'id' => 'tastewp_toggle',
      'title' => 'Show Intro – TasteWP',
      'href' => '#',
      'parent' => 'top-secondary'
    );
    $admin_bar->add_menu($args);

  }

  /**
   * ajaxHandlers - AJAX Handling for Intro Actions
   *
   * @return void
   */
  public function ajaxHandlers() {

    add_action('wp_ajax_hide_tastewp_notice', function () {
      update_option('hide_tastewp_notice', 1);
    });

    add_action('wp_ajax_auto_smart_tastewp_redirect_performed', function () {
      update_option('__tastewp_redirection_performed', true);
      update_option('auto_smart_tastewp_redirect_performed', 1);
    });

    add_action('wp_ajax_hide_tastewp_notice_small', function () {
      update_option('hide_tastewp_notice_small', 1);
      update_option('hide_tastewp_notice', 1);
    });

    add_action('wp_ajax_show_tastewp_notice', function () {
      delete_option('hide_tastewp_notice');
    });

  }

  /**
   * wordPressFixes - Fixes UI issues that WordPress can't fix...
   *
   * @return void
   */
  public function wordPressFixes() {

    // Fix for margin while no errors are displayed
    add_action('admin_footer', function () {
      ?>
      <style media="screen">
        .php-error #adminmenuback, .php-error #adminmenuwrap {
          margin-top: 0 !important;
        }
      </style>
      <?php
    });

  }

  /**
   * passwordLessLoginModule - Module that allows password less login.
   *
   * @return void
   */
  public function passwordLessLoginModule() {

    global $pagenow;
    if ($pagenow == 'wp-login.php' && !empty($_GET['autologin']) && $_GET['autologin'] == 'true') {
      if (get_option('first_logged', false) != true) {
        if (get_option('first_logged_before', false) != true) {

          $redirectmenu = '';

          if (isset($_GET['redirect-menu'])) {
            $redirectmenu = '&redirect-menu=' . $_GET['redirect-menu'];
          }

          wp_safe_redirect(home_url('wp-login.php?autologin=true' . $redirectmenu));
          update_option('first_logged_before', true);
          exit;

        }

        if (function_exists('grant_super_admin')) grant_super_admin(1);
        $this->skipOnboarding();

        update_user_meta(1, 'tgmpa_dismissed_notice_oceanwp_theme', 1);

        wp_clear_auth_cookie();
        wp_set_current_user(1);
        wp_set_auth_cookie(1, true);
        update_option('first_logged', true);

        $list = $this->getAdditionalPlugins();

        if (((is_object($list) || is_array($list)) && sizeof($list) <= 0) || !current_user_can('install_plugins')) {

          wp_safe_redirect(admin_url());

        } else {

          wp_safe_redirect(admin_url('/plugins.php'));

        }

        exit;
      }
    }

  }

  /**
   * loginPageRedirectionWhileLogged - Adds redirection to wp-login while logged in
   *
   * @return void
   */
  public function loginPageRedirectionWhileLogged() {

    global $pagenow;

    if (is_user_logged_in() && $pagenow == 'wp-login.php' && empty($_GET['action'])) {

      $list = $this->getAdditionalPlugins();
      if (((is_object($list) || is_array($list)) && sizeof($list) <= 0) || !current_user_can('install_plugins')) {
        wp_safe_redirect(admin_url());
      } else {
        wp_safe_redirect(admin_url('/plugins.php'));
      }

      exit;

    }

  }

}

/**
 * Module Initialization (As Global)
 */
$tastewp = new TasteWP_Internal_Integration();
