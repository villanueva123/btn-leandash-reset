<?php
/**
 * Admin Notice with paramaters.
 *
 * Defines the title, class, and message for an admin error
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */

class btn_learndash_reset_admin_notice {

	/**
	 * CSS Class name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $class    The CSS Class For The Notice ( error | warning | success | info ).
	 */
	private $class;

	/**
	 * Notice Title.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $title    The Title For The Notice.
	 */
	private $title;

	/**
	 * Notice Title.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $message   HTML Message String.
	 */
	private $message;

	/**
	 * Dismissable Bol.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bol    $dismissable   Adds Dismissable Class.
	 */
	private $dismissable;

	/**
	  * Define Variables and Add Admin Notice Action.
	  *
	  * @since    1.0.0
	 */
	function __construct( $class, $title, $message, $dismissable = false ) {
		$this->class = $class;
		$this->title = $title;
		$this->message = $message;
		$this->dismissable = $dismissable;
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	  * Render The Admin Notice.
	  *
	  * @since    1.0.0
	 */
	function render() {
		$dismissableClass = ( $this->dismissable ) ? 'is-dismissible' : '';
		printf( '<div class="notice notice-%s %s"><p><strong>%s:</strong> %s</p></div>', $this->class, $dismissableClass, $this->title, $this->message );
	}
}
