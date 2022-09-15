<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Main Plugin Class
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */

class btn_learndash_reset {

	// Post Type Slug
	const MODULES = 'btn_lms_modules';

	// Migrate Hub Data
	private $migrate_hub_data;
	// Migrate BB Activty Data
	private $migrate_bb_activities;
	// Migrate Private BB Group Activty Data
	private $migrate_bb_private;

	private $migrate_user_nickname;


	// Init Plugin
	function init(){
		$this->add_wp_hooks();
	}

    // Add WP Filters and Actions
	function add_wp_hooks() {

		// Temp Migration
		add_action( 'plugins_loaded', [ $this, 'migration_init' ] );
		add_action( 'init', [ $this, 'process_handler'] );

		if( is_admin() ){
			$this->admin()->init();
		}


	}

	// Get Instance of Admin
	function admin(){
		static $admin = null;
		if( is_null($admin) ){
			$admin = btn_learndash_reset_admin::get_instance();
		}
		return $admin;
	}

	function migration_init(){
		/* @link https://github.com/A5hleyRich/wp-background-processing */
		require_once BTN_LEARNDASH_DIR . 'vendor/background-process/wp-async-request.php';
		require_once BTN_LEARNDASH_DIR . 'vendor/background-process/wp-background-process.php';
		$this->learndash_progress_reset = new btn_learndash_reset_btn_progress_reset();
	}

	// Process handler
	public function process_handler() {
		$filter_nonce = isset($_POST['btn_learndash_reset_progress_filter']) ? $_POST['btn_learndash_reset_progress_filter'] : false;
		if($filter_nonce){
			$process = $_POST['btn_learndash_reset_progress_filter'];
			if($process === 'btn_learndash_reset_filter' ){
				$course_ids = (isset($_POST['btn-ld-reset-courses']))? $_POST['btn-ld-reset-courses'] : false ;
				$group_ids = (isset($_POST['btn-ld-reset-groups']))? $_POST['btn-ld-reset-groups'] : false ;
				$this->fetch_user_ids($course_ids,$group_ids);
			}
		}
		else{
			if ( ! isset($_POST['btn_learndash_reset_progress_process']) ) {
				return;
			}

			$process = $_POST['btn_learndash_reset_progress_process'];
			$update_nickname_nonce = isset($_POST['btn_learndash_reset_progress_process']) ? $_POST['btn_learndash_reset_progress_process'] : false;
			if($update_nickname_nonce){
				if( $process === 'btn_learndash_reset_bb_nickname' ){
					$course_ids = (isset($_POST['btn-ld-reset-courses']))? $_POST['btn-ld-reset-courses'] : false;
					$group_ids = (isset($_POST['btn-ld-reset-groups']))? $_POST['btn-ld-reset-groups'] : false;
					$this->handle_ld_user_reset_progress($course_ids,$group_ids);
					return;
				}
			}
		}
		return;
	}


	function handle_ld_user_reset_progress($course_ids = [], $group_ids = []){
		$results = $this->fetch_user_ids($course_ids,$group_ids);
		if( ! $results ){
			return false;
		}

		$total = count($results);
		update_option('btn/ld/user/progress', [
			'status' 	=> 'running',
			'count'		=> $total,
			'processed' => 0,
			'started'	=> time(),
			'completed'	=> 0
		], false);

		if( !empty($results) ){
			foreach ($results as $user => $user_id) {
				$this->learndash_progress_reset->push_to_queue( [
					'user_id'  => $user_id
				] );
			}
		}
		$this->learndash_progress_reset->save()->dispatch();
	}


	function fetch_user_ids($course_ids = [], $group_ids=[]){
		global $wpdb;
		$user = $wpdb->prefix . 'users';
		$user_meta = $wpdb->prefix . 'usermeta';
		//$sql = "SELECT id as id from {$user} WHERE ID = 3248";
		$courses = '';
		$groups  = '';
		if(!empty($course_ids)){
			$c = 0;
			$numItems = count($course_ids);
			foreach($course_ids as $course_id){
				$course_enrolled = "course_{$course_id}_access_from";
				if(++$c === $numItems) {
				    $courses .="m.meta_key = '{$course_enrolled}' AND m.user_id = a.id";
				}
				else{
					$courses .="m.meta_key = '{$course_enrolled}' AND m.user_id = a.id OR ";
				}
			}
		}
		if(!empty($group_ids)){
			$g = 0;
			$numItems = count($group_ids);
			foreach($group_ids as $group_id){
				$group_enrolled = "learndash_group_users_{$group_id}";
				if(++$g === $numItems) {
				    $groups .="m.meta_key = '{$group_enrolled}' AND m.user_id = a.id";
				}
				else{
					$groups .="m.meta_key = '{$group_enrolled}' AND m.user_id = a.id OR ";
				}
			}
		}

		$sql = "SELECT
				a.id AS id
				 FROM {$user} AS a
				INNER JOIN {$user_meta} AS m
					ON {$courses} {$groups}
			";
		/*$sql = "SELECT
				a.id AS id
				 FROM {$user} AS a
				INNER JOIN {$user_meta} AS m
					ON m.meta_key = 'learndash_group_users_66018' AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66017'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66016'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66015' AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66014'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66010' AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_66009'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_65977' AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_65149'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_65146'	AND m.user_id = a.id
					OR m.meta_key = 'learndash_group_users_65128'	AND m.user_id = a.id
			";*/
			//$email_list = "a.user_email LIKE '%ameyer@penskeautomotive.com'";
			/*$email_list = "a.user_email LIKE '%ameyer@penskeautomotive.com'
			OR a.user_email LIKE '%AzHabib@PenskeAutomotive.com'
			OR a.user_email LIKE '%BHernandez@PenskeAutomotive.com'
			OR a.user_email LIKE '%ckibbe@penskeautomotive.com'
			OR a.user_email LIKE '%PPendley@PenskeAutomotive.com'
			OR a.user_email LIKE '%charford@penskeautomotive.com'
			OR a.user_email LIKE '%dustin.kim@penskeautomotive.com'
			OR a.user_email LIKE '%joglesby@penskeautomotive.com'
			OR a.user_email LIKE '%JMercado@penskeautomotive.com'
			OR a.user_email LIKE '%jose.gonzalez@penskeautomotive.com'
			OR a.user_email LIKE '%ltapia@penskeautomotive.com'
			OR a.user_email LIKE '%Luis.Sanchez@penskeautomotive.com'
			OR a.user_email LIKE '%naragon@penskeautomotive.com'
			OR a.user_email LIKE '%pchiapperini@penskeautomotive.com'
			OR a.user_email LIKE '%RCamacho@PenskeAutomotive.com'
			OR a.user_email LIKE '%sletcher@penskeautomotive.com'
			OR a.user_email LIKE '%AGillman@PenskeAutomotive.com'
			OR a.user_email LIKE '%anation@penskeautomotive.com'
			OR a.user_email LIKE '%BBeltz@penskeautomotive.com'
			OR a.user_email LIKE '%ghall@penskeautomotive.com'
			OR a.user_email LIKE '%jparker@penskeautomotive.com'
			OR a.user_email LIKE '%JMartin@penskeautomotive.com'
			OR a.user_email LIKE '%rowolabi@penskeautomotive.com'
			OR a.user_email LIKE '%rperry@penskeautomotive.com'
			OR a.user_email LIKE '%TPark@PenskeAutomotive.com'
			OR a.user_email LIKE '%SFaem@PenskeAutomotive.com'
			OR a.user_email LIKE '%wallahdini@penskeautomotive.com'
			OR a.user_email LIKE '%ARibeiro@Penskeautomotive.com'
			OR a.user_email LIKE '%Andres.Lara@penskeautomotive.com'
			OR a.user_email LIKE '%amorales2@penskeautomotive.com'
			OR a.user_email LIKE '%cestrada@penskeautomotive.com'
			OR a.user_email LIKE '%CCarias@PenskeAutomotive.com'
			OR a.user_email LIKE '%cecastillo@penskeautomotive.com'
			OR a.user_email LIKE '%cpierce@penskeautomotive.com'
			OR a.user_email LIKE '%gostolaza@penskeautomotive.com'
			OR a.user_email LIKE '%hramsanai@penskeautomotive.com'
			OR a.user_email LIKE '%jesus.amaya@penskeautomotive.com'
			OR a.user_email LIKE '%jrivas@penskeautomotive.com'
			OR a.user_email LIKE '%Nicolas.Sanchez@penskeautomotive.com'
			OR a.user_email LIKE '%ogiraldo@penskeautomotive.com'
			OR a.user_email LIKE '%Ralfonzo@penskeautomotive.com'
			OR a.user_email LIKE '%svaccaro@penskeautomotive.com'
			OR a.user_email LIKE '%saguirre2@penskeautomotive.com'
			OR a.user_email LIKE '%svarela@penskeautomotive.com'
			OR a.user_email LIKE '%keiland@penskeautomotive.com'
			OR a.user_email LIKE '%lshepherd@penskeautomotive.com'
			OR a.user_email LIKE '%ablakley@penskeautomotive.com'
			OR a.user_email LIKE '%clor@penskeautomotive.com'
			OR a.user_email LIKE '%isiah.jones@penskeautomotive.com'
			OR a.user_email LIKE '%jleistiko@penskeautomotive.com'
			OR a.user_email LIKE '%jsergi@penskeautomotive.com'
			OR a.user_email LIKE '%ngoranson@penskeautomotive.com'
			OR a.user_email LIKE '%rbailey@penskeautomotive.com'
			OR a.user_email LIKE '%sgrajales@penskeautomotive.com'
			OR a.user_email LIKE '%sperales@penskeautomotive.com'
			OR a.user_email LIKE '%avelez@penskeautomotive.com'
			OR a.user_email LIKE '%AMugaburu@penskeautomotive.com'
			OR a.user_email LIKE '%Ariana.Lopez@penskeautomotive.com'
			OR a.user_email LIKE '%CHarrison@penskeautomotive.com'
			OR a.user_email LIKE '%Jimmy.Clark@PenskeAutomotive.com'
			OR a.user_email LIKE '%jclizbe@penskeautomotive.com'
			OR a.user_email LIKE '%Jeff.Jackson@PenskeAutomotive.com'
			OR a.user_email LIKE '%jsonger@penskeautomotive.com'
			OR a.user_email LIKE '%khill@penskeautomotive.com'
			OR a.user_email LIKE '%NMaynard@PenskeAutomotive.com'
			OR a.user_email LIKE '%rsado@penskeautomotive.com'
			OR a.user_email LIKE '%TLyden@penskeautomotive.com'
			OR a.user_email LIKE '%bavra@penskeautomotive.com'
			OR a.user_email LIKE '%David.Reed@penskeautomotive.com'
			OR a.user_email LIKE '%sdown@penskeautomotive.com'
			OR a.user_email LIKE '%jose.orellana@penskeautomotive.com'
			OR a.user_email LIKE '%mjordan@penskeautomotive.com'
			OR a.user_email LIKE '%rkeck@penskeautomotive.com'
			OR a.user_email LIKE '%cnagle@penskeautomotive.com'
			OR a.user_email LIKE '%gpoduch@penskeautomotive.com'
			OR a.user_email LIKE '%htinoco@penskeautomotive.com'
			OR a.user_email LIKE '%lminerath@penskeautomotive.com'
			OR a.user_email LIKE '%ebouphavan@penskeautomotive.com'
			OR a.user_email LIKE '%akelm@penskeautomotive.com'
			OR a.user_email LIKE '%cthompson@penskeautomotive.com'
			OR a.user_email LIKE '%ehuard@penskeautomotive.com'
			OR a.user_email LIKE '%halbasri@penskeautomotive.com'
			OR a.user_email LIKE '%lvanduinen@penskeautomotive.com'
			OR a.user_email LIKE '%mphelps@penskeautomotive.com'
			OR a.user_email LIKE '%nkrane@penskeautomotive.com'
			OR a.user_email LIKE '%rlarsen@penskeautomotive.com'
			OR a.user_email LIKE '%rstulberg@penskeautomotive.com'
			OR a.user_email LIKE '%sross@penskeautomotive.com'
			OR a.user_email LIKE '%tommy.lee@penskeautomotive.com'
			OR a.user_email LIKE '%tly@penskeautomotive.com'
			OR a.user_email LIKE '%brotter@penskeautomotive.com'
			OR a.user_email LIKE '%jylitalo@penskeautomotive.com'
			OR a.user_email LIKE '%anthony.ruiz@penskeautomotive.com'
			OR a.user_email LIKE '%ddavila@penskeautomotive.com'
			OR a.user_email LIKE '%ERICK.DAVILA@PENSKEAUTOMOTIVE.COM'
			OR a.user_email LIKE '%ECabucoParchman@penskeautomotive.com'
			OR a.user_email LIKE '%ESattar@PenskeAutomotive.com'
			OR a.user_email LIKE '%fvilaomat@penskeautomotive.com'
			OR a.user_email LIKE '%fcortte@penskeautomotive.com'
			OR a.user_email LIKE '%gansupo@penskeautomotive.com'
			OR a.user_email LIKE '%hhajjar@penskeautomotive.com'
			OR a.user_email LIKE '%johuan.thompson@penskeautomotive.com'
			OR a.user_email LIKE '%jose.ruiz@penskeautomotive.com'
			OR a.user_email LIKE '%mario.maldonado@penskeautomotive.com'
			OR a.user_email LIKE '%mlanda@penskeautomotive.com'
			OR a.user_email LIKE '%MBrito@penskeautomotive.com'
			OR a.user_email LIKE '%rmarchinares@penskeautomotive.com'
			OR a.user_email LIKE '%caleb.ward@penskeautomotive.com'
			OR a.user_email LIKE '%critzi@penskeautomotive.com'
			OR a.user_email LIKE '%dlewandowski@penskeautomotive.com'
			OR a.user_email LIKE '%keith.jones@penskeautomotive.com'
			OR a.user_email LIKE '%tmargison@penskeautomotive.com'
			OR a.user_email LIKE '%sbodenhamer@penskeautomotive.com'
			OR a.user_email LIKE '%aeddingfield@penskeautomotive.com'
			OR a.user_email LIKE '%alarios@penskeautomotive.com'
			OR a.user_email LIKE '%bbaldini@penskeautomotive.com'
			OR a.user_email LIKE '%cmccrickard@penskeautomotive.com'
			OR a.user_email LIKE '%chaywood@penskeautomotive.com'
			OR a.user_email LIKE '%dday@penskeautomotive.com'
			OR a.user_email LIKE '%emathews@penskeautomotive.com'
			OR a.user_email LIKE '%jenna.thompson@penskeautomotive.com'
			OR a.user_email LIKE '%dcostin@penskeautomotive.com'
			OR a.user_email LIKE '%nhoover@penskeautomotive.com'
			OR a.user_email LIKE '%spurvis@penskeautomotive.com'
			OR a.user_email LIKE '%tgullery@penskeautomotive.com'
			OR a.user_email LIKE '%cmoberg@penskeautomotive.com'
			OR a.user_email LIKE '%jenry.hernandez@penskeautomotive.com'
			OR a.user_email LIKE '%lcisneros@penskeautomotive.com'
			OR a.user_email LIKE '%jbolivar@penskeautomotive.com'
			OR a.user_email LIKE '%Mark.Wright@penskeautomotive.com'
			OR a.user_email LIKE '%RKAHN@PENSKEAUTOMOTIVE.COM'
			OR a.user_email LIKE '%chris.williams@penskeautomotive.com'
			OR a.user_email LIKE '%jarcher@penskeautomotive.com'
			OR a.user_email LIKE '%jreynolds@penskeautomotive.com'
			OR a.user_email LIKE '%j.reeves@penskeautomotive.com'
			OR a.user_email LIKE '%areed@penskeautomotive.com'
			OR a.user_email LIKE '%aalfaro@penskeautomotive.com'
			OR a.user_email LIKE '%bwhite@penskeautomotive.com'
			OR a.user_email LIKE '%bryanna.sanders@penskeautomotive.com'
			OR a.user_email LIKE '%EYoung@penskeautomotive.com'
			OR a.user_email LIKE '%JSISON@PENSKEAUTOMOTIVE.COM'
			OR a.user_email LIKE '%john.valencia@penskeautomotive.com'
			OR a.user_email LIKE '%LWalsh@PenskeAutomotive.com'
			OR a.user_email LIKE '%MManfre@PenskeAutomotive.com'
			OR a.user_email LIKE '%mgandee@penskeautomotive.com'
			OR a.user_email LIKE '%nking@penskeautomotive.com'
			OR a.user_email LIKE '%pcrisafulli@penskeautomotive.com'
			OR a.user_email LIKE '%rlocadia@penskeautomotive.com'
			OR a.user_email LIKE '%rsheridan@penskeautomotive.com'
			OR a.user_email LIKE '%thanh.dang@penskeautomotive.com'
			OR a.user_email LIKE '%BLeonard@penskeautomotive.com'
			OR a.user_email LIKE '%bnazary@penskeautomotive.com'
			OR a.user_email LIKE '%CTorres@PenskeAutomotive.com'
			OR a.user_email LIKE '%c.brawner@penskeautomotive.com'
			OR a.user_email LIKE '%CTomassi@PenskeAutomotive.com'
			OR a.user_email LIKE '%d.jones@penskeautomotive.com'
			OR a.user_email LIKE '%gbarker@penskeautomotive.com'
			OR a.user_email LIKE '%jeffrey.brown@penskeautomotive.com'
			OR a.user_email LIKE '%LRagsdale@PenskeAutomotive.com'
			OR a.user_email LIKE '%Monta.Jones@penskeautomotive.com'
			OR a.user_email LIKE '%TKang@PenskeAutomotive.com'
			OR a.user_email LIKE '%tdonovan@penskeautomotive.com'
			OR a.user_email LIKE '%ZGinn@PenskeAutomotive.com'";
			$sql = "SELECT
					a.id AS id
					 FROM {$user} AS a WHERE {$email_list}
				";*/
		$user_data = $wpdb->get_results( $sql , OBJECT_K );
  	//$private_activies = $wpdb->get_results( $sql , OBJECT_K );
		$bb_user_nickname = [];
		foreach ($user_data as $id => $result) {
			$bb_user_nickname[] = $id;
		}
		return ( is_array($bb_user_nickname) && !empty($bb_user_nickname) ) ? $bb_user_nickname : false;
	}

	// Module Post Type Slug
	function module_slug(){
		return self::MODULES;
	}

	// Returns the instance
	private static $instance;
	private function __construct(){}
    public static function get_instance() {
	    if ( is_null( self::$instance ) ) {
	        self::$instance = new self;
	    }
	    return self::$instance;
    }
}
