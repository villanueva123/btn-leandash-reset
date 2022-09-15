<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * BTN LMS Main Admin
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
class btn_learndash_reset_admin {

	// Post Type Slug
	private $module_slug;
	private $footer_data = [];
	function init(){
		add_action('init', [$this, 'add_wp_hooks']);
	}

	// Add WP Actions / Filters
    function add_wp_hooks(){

		if (current_user_can('manage_options')) {
			// Actions
			add_action('admin_menu', [$this, 'wp_admin_menu']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		}

    }


	// Enqueue Scripts
	function enqueue_scripts( $hook ){
		$ns  = 'btn-learndash-reset';
		$v   = BTN_LEARNDASH_VERSION;
		$url = BTN_LEARNDASH_ASSETS_URL . 'admin/styles.css';
		wp_enqueue_style("{$ns}-admin-style", $url, [], BTN_LEARNDASH_VERSION, 'all');

		$url = BTN_LEARNDASH_ASSETS_URL . 'admin/admin.js';
		$dep = apply_filters( "btn/learndash/json/data/{$hook}", ['jquery'] );
		wp_register_script("{$ns}-admin-script", $url, $dep, $v, 'all');

		// btnDirSelect2 fork
		$url = BTN_LEARNDASH_ASSETS_URL . 'btn-select2';
		wp_enqueue_style("btn-select2-css", "{$url}.css", false, '5.7.2');
		wp_register_script("btn-select2-js", "{$url}.js", ['jquery'], '1.0.4');
		add_action('admin_footer', [$this, 'print_scripts']);
    }


	function print_scripts(){
		if(!empty($this->footer_data)){
			wp_enqueue_script("btn-select2-js");
			wp_enqueue_script("btn-learndash-reset-admin-script");

		}
    }

	// Populate Data For Admin Javascript
    function set_footer_data($key, $value = false, $index = false) {
		if ($value) {
            // Append to array
            if( !empty($index) ){
                if( !isset($this->footer_data[$key]) ){
                    $this->footer_data[$key] = [];
                }
                $this->footer_data[$key][$index] = $value;
            }
            else{
                $this->footer_data[$key] = $value;
            }
		}
		else {
			unset($this->footer_data[$key]);
		}
	}

    // Get Data For Admin Javascript
	function get_footer_data($key = false) {
		if ($key) {
			return (isset($this->footer_data[$key])) ? $this->footer_data[$key] : null;
		}
		else {
			return $this->footer_data;
		}
	}

	function wp_admin_menu(){
				add_options_page(
		            'Reset User Course Progress',
								'Reset User Course Progress',
		            'manage_options',
		            'user_learndash_progress_reset',
		            ['btn_learndash_reset_user_progress_screen','show']
		        );

	}

	function get_course($course_ids){
		$option = '';
		$query_args = array(
			'post_type'         =>   'sfwd-courses',
			'orderby'           =>   'title',
			'order'             =>   'ASC',
			'nopaging'          =>   true    // Turns OFF paging logic to get ALL courses
		);

		$query = new WP_Query( $query_args );
		if ( $query instanceof WP_Query) {
			foreach($query->posts as $post){
				if(!empty($course_ids)){
					$selected = (in_array($post->ID, $course_ids))? "selected": "";
				}
				$option .= '<option value="'.$post->ID.'" '.$selected.'>'.$post->post_title.'</option>';
			}
		}
		return $option;
	}

	function get_groups($group_ids){

		$option = '';

		$query = learndash_get_groups();
		if ( !empty($query)) {
			foreach($query as $post){
				if(!empty($group_ids)){
					$selected = (in_array($post->ID, $group_ids))? "selected": "";
				}
				$option .= '<option value="'.$post->ID.'" '.$selected.'>'.$post->post_title.'</option>';
			}
		}
		return $option;
	}
    // Returns the instance
	static $instance;
	private function __construct(){}
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
			//self::$instance->module_slug = btn_lms()->module_slug();
        }
        return self::$instance;
    }

}
