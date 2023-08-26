<?php
/*
Plugin Name: Debugger for Wordpress
Plugin URI: https://#
Description: Assistant for finding errors and optimizing scripts. ?wp-debugger=1| ?wp-debugger=phpinfo|?wp-debugger=pagedata
Version: 0.0.1
Requires at least: 5.8
Requires PHP: 7.2
Author: PavloBorysenko
License: GPLv2 or later
Text Domain: wp_debugger
*/

define('WPDEBUG_PATH', plugin_dir_path(__FILE__));
define('WPDEBUG_LINK', plugin_dir_url(__FILE__));
define('WPDEBUG_PLUGIN_NAME', plugin_basename(__FILE__));
define('WPDEBUG_LOG_PATH', WPDEBUG_PATH . 'data/');

require_once( WPDEBUG_PATH . 'classes/class.wp-debugger.php' );


$GLOBALS['WpDebugger'] = new WpHelper\Debugger\WpDebugger();

function wpdebug() {
    global $WpDebugger;
    return $WpDebugger;
}

