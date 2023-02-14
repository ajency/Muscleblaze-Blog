<?php
/**
 * The base configuration for WordPress by TasteWP.com
 */

define('DB_NAME',     "mb_new");
define('DB_USER',     "root");
define('DB_PASSWORD', "");
define('DB_HOST',     "localhost:3307");
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  '');

define('AUTH_KEY',         '3HwnnAPo4WQm1HF5tJEwH3QScSRA4J60pdoNnnF1cIzSsRzz4jRxTCMhGw9XADcf');
define('SECURE_AUTH_KEY',  'RqdWeJIeM4d5hTYNmf5oSak7ESvws2WjV3tgMCHp3IPEuOXvxOB4ZNMewd0IgZn6');
define('LOGGED_IN_KEY',    'rRRXWPYbhto9RreCOQ3EdcNoLmZuW9dmAWlVUovLAX2GtuzmxIBVNKkr8EN92DWQ');
define('NONCE_KEY',        't0rFZEhI1B1xd1nzwPuc0vuhpgmOaAv2UFwikAurqCu8UB7isP1Ty8O8QlqA5c1i');
define('AUTH_SALT',        '5beDNcFMF7idx8amPPw4uqB3TzWKpylsUNP4bNSuod3HGPllmbv7b7exfbEfNVQA');
define('SECURE_AUTH_SALT', 'sAJSXCrRPCmykxmOUaJCOlJqAJ1Inzusws2HHKXke5zvQQDyy5ZJBOk6n0mUSnFm');
define('LOGGED_IN_SALT',   'huSlpXOx2qQLDjzFFOv5XOATjqCCjX95F9eTU3AVusc0ZJcyqpbQNn0iXl59EVmW');
define('NONCE_SALT',       'Izth65zJK9llKmuHSV237iqGKcKHXBJ5coCdRmwThCLrxrNOADqWNvjYdZ5PD2PT');

$table_prefix = 'wp_';

define( 'WP_TEMP_DIR', 'C:/xampp/htdocs/mb_blogs/tmp' );
define( 'WP_MEMORY_LIMIT', '96M' );
define( 'WP_MAX_MEMORY_LIMIT', '96M' );
define('FS_METHOD', 'direct');

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

define( 'WP_DEBUG_DISPLAY', true );
define( 'CONCATENATE_SCRIPTS', true );







if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
define( 'WP_SITEURL', 'http://localhost:81/mb_blogs/' );
require_once ABSPATH . 'wp-settings.php';
