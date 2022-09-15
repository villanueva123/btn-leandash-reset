<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://businesstechninjas.com
 * @since             1.0.0
 * @package           btn-lms
 *
 * @wordpress-plugin
 * Plugin Name:       Business Tech Ninjas - Reset Learndash Course progress
 * Plugin URI:        https://businesstechninjas.com
 * Description:       Help Functions to Reset Learndash Course progress for penske group
 * Version:           1.0.0
 * Author:            Business Tech Ninjas
 * Author URI:        https://businesstechninjas.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       btn-mama-migrate
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

$btn_learndash_reset_min_php_version = '7.0';
if (phpversion() >= $btn_learndash_reset_min_php_version) {

	// Set Constants
	define('BTN_LEARNDASH_VERSION', '1.0.11');
	define('BTN_LEARNDASH_PLUGIN', __FILE__);
	define('BTN_LEARNDASH_DIR', __DIR__ . '/');
	define('BTN_LEARNDASH_CLASS_DIR', BTN_LEARNDASH_DIR . 'classes/');
	$btn_learndash_reset_dir = plugins_url('', __FILE__);
	define('BTN_LEARNDASH_URL', $btn_learndash_reset_dir . '/');
	define('BTN_LEARNDASH_ASSETS_URL', BTN_LEARNDASH_URL . 'assets/');
	if( ! defined('BTN_LEARNDASH_DEBUG') ){
		define('BTN_LEARNDASH_DEBUG', 0);
	}
	if( BTN_LEARNDASH_DEBUG > 0 ){

		if( ! defined('BTN_LEARNDASH_DEBUG_LOG') ){
			define('BTN_LEARNDASH_DEBUG_LOG', 'error_log:');
		}

	}

	// Autoloader
	include_once BTN_LEARNDASH_CLASS_DIR . 'autoloader.php';
	// Init The Plugin
	btn_learndash_reset()->init();
}
else {
	if ( is_admin() ) {
		// Add Notice
		require_once __DIR__ . '/classes/admin-notice.php';
		$title = 'Business Tech Ninjas MAMA Migrate Installation Alert';
		$msg = '<p style="color:red;font-weight:bold;">Business Tech Ninjas MAMA Migrate has been temporarily disabled.</p>';
		if ( version_compare( phpversion() . $btn_learndash_reset_min_php_version . '<' ) ) {
			$msg .= '<p><strong>PHP ' . $btn_learndash_reset_min_php_version . ' or newer is required.</strong>';
			$msg .= ' You are running PHP v' . phpversion() . '.</p>';
		}
		new btn_learndash_reset_admin_notice( 'error', $title, $msg, true );
	}
}


/**
 * Gets the instance of the `btn_learndash_reset` class.
*/
function btn_learndash_reset(){
    return btn_learndash_reset::get_instance();
}
