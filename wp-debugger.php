<?php
/**
 * Plugin Name: Debugger for WordPress
 * Plugin URI: https://#
 * Description: Assistant for finding errors and optimizing scripts. wpdebug():var_dump|log|phpinfo ?wp-debugger=1| ?wp-debugger=phpinfo|?wp-debugger=pagedata
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: PavloBorysenko
 * License: GPLv2 or later
 * Text Domain: wp_debugger
 *
 * @package    WpHelper
 */

define( 'WPDEBUG_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPDEBUG_LINK', plugin_dir_url( __FILE__ ) );
define( 'WPDEBUG_PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( 'WPDEBUG_LOG_PATH', WPDEBUG_PATH . 'data/' );

require_once WPDEBUG_PATH . 'classes/class-wp-debugger.php';


$page_data = 0;  // 0 - do nothing  1 - write  to  log    2 - draw on  page
$do_ping = 0;  // 1 - add  info  in  log (file  and  line)


$GLOBALS['wp_debugger'] = new WpHelper\Debugger\WP_Debugger( $page_data, $do_ping );

/**
 * Get instance
 */
function wpdebug(): WpHelper\Debugger\WP_Debugger {
	global $wp_debugger;
	return $wp_debugger;
}
