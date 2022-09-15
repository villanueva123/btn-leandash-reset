<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Autoloader
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
spl_autoload_register(['btn_learndash_reset_autoloader', 'load']);

final class btn_learndash_reset_autoloader {

	private static $classes = false;
	private static $paths   = false;

	private static $prefix_length = 20;

	private static function init() {

		self::$classes = [
			'btn_learndash_reset'					=> BTN_LEARNDASH_CLASS_DIR . 'btn-learndash-reset',
			'btn_learndash_reset_admin_notice'		=> BTN_LEARNDASH_CLASS_DIR . 'admin-notice',
			'btn_learndash_reset_user_progress_screen'	=> BTN_LEARNDASH_DIR . 'screens/user_progress_screen',
		];

		self::$paths = [
			BTN_LEARNDASH_CLASS_DIR,
			BTN_LEARNDASH_DIR . 'screens/',
		];
	}

	public static function load( $class ) {
		if ( ! self::$classes ) {
			self::init();
		}

		$class = strtolower( trim( $class ) );
		if ( array_key_exists( $class, self::$classes ) && file_exists( self::$classes[$class] . '.php' ) ) {
			include_once self::$classes[$class] . '.php';
		}
		else {
			foreach(self::$paths as $path) {
				$file = $path . substr($class,self::$prefix_length) . '.php';
				if (file_exists($file)) {
					include_once $file;
				}
			}
		}

		if (substr($class, 0, self::$prefix_length) <> 'btn_learndash_reset') {
			return;
		}
	}

	public static function register( $class, $file ) {
		$class = strtolower( trim( $class ) );
		if ( ! array_key_exists( $class, self::$classes ) ) {
			self::$classes[$class] = $file;
		}
	}

	public static function register_path( $path ) {
		$class = trim($path);

		if (! in_array($path, self::$paths)) {
			self::$paths[] = $path;
		}
	}

}
