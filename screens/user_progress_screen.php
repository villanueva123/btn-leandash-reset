<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Move Private Group Activity Posts to Timeline
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/screens
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
class btn_learndash_reset_user_progress_screen {


	static function show( $syncing ){

		$process_data = get_option('btn/ld/user/progress', false);
		$process_status = ( $process_data && !empty($process_data['status']) ) ? $process_data['status'] : '';
		btn_learndash_reset()->admin()->set_footer_data('ns', 'show' );
		echo '<div class="wrap">';
			echo '<form method="post" class="btn-reset-progress" data-screen="">';
				echo '<h2>'.__( 'Reset Users Course Progress', 'btn-ism' ).'</h2>';
				echo '<div class="wpat_notice_wrap"></div>';
				echo '<div class="wpat_admin_table wpat_tabbed_table">';

					if( $process_status === 'running' ){
						echo '<h3></h3>';
					}
					else {
						echo "<label>Select Courses:</label>";
						$course_ids = (isset($_POST['btn-ld-reset-courses']))? $_POST['btn-ld-reset-courses'] : false;
						echo '<select name="btn-ld-reset-courses[]" class="btn-ld-reset-selector" id="btn-ld-reset-courses" multiple="multiple">';
							echo btn_learndash_reset()->admin()->get_course($course_ids);
						echo '</select>';
						echo "<label>Select Course Groups:</label>";
						$group_ids = (isset($_POST['btn-ld-reset-groups']))? $_POST['btn-ld-reset-groups'] : false;
						echo '<select name="btn-ld-reset-groups[]" class="btn-ld-reset-selector" id="btn-ld-reset-groups" multiple="multiple">';
							echo btn_learndash_reset()->admin()->get_groups($group_ids);
						echo '</select>';
						$process = (isset($_POST['btn_learndash_reset_progress_filter']))? $_POST['btn_learndash_reset_progress_filter']:false;
						if($process === 'btn_learndash_reset_filter' ){
							$course_ids = (isset($_POST['btn-ld-reset-courses']))? $_POST['btn-ld-reset-courses'] : false ;
							$group_ids = (isset($_POST['btn-ld-reset-groups']))? $_POST['btn-ld-reset-groups'] : false ;
							$results = btn_learndash_reset()->fetch_user_ids($course_ids,$group_ids);
							$total_posts = ($results) ? count($results) : 0;
							if( $total_posts > 0 ){
								$s_t = $total_posts > 1 ? 's' : '';
								$total_users = $total_posts;
								$s_u = $total_users > 1 ? 's' : '';
								echo "<br/><br/><br/>Found {$total_posts} User{$s_u} within Progress groups<br/><br/>";
								echo '<div class="btn-ld-reset-scroll"><pre>'.print_r([
									'$results' => $results
								],true).'</pre></div>';
							}
						}

						else {
							//echo '<br/>All private group posts have been moved to users timelines<br/>';
						}
						echo "<div class='btn-ld-reset-wrapper'>";
							echo '<br/><br/><button name="btn_learndash_reset_progress_filter" class="button-primary" value="btn_learndash_reset_filter">Preview Filter</button>';
							wp_nonce_field( 'btn_learndash_reset_progress_process', 'btn_learndash_reset_progress_process' );
							echo '<br/><br/><button name="btn_learndash_reset_progress_process" class="button-primary" value="btn_learndash_reset_bb_nickname">Reset Users Progress</button>';
						echo "</div>";
					}

					if( $process_status > '' ){
						$title = ( $process_data === 'completed' ) ? 'Sync Results' : 'Sync Progress';
						echo '<h4>User Learndash Reset Progress </h4>';
						echo '<ul>';
						echo '<li>Status : '.ucfirst($process_status).'</li>';
						echo '<li>Started : '. wp_date("F j, Y g:i a",$process_data['started']).'</li>';
						if( $process_data === 'completed' ){
							echo '<li>Completed : '. wp_date("F j, Y g:i a",$process_data['completed']).'</li>';
							echo '<li>Runtime : '. self::process_runtime($process_data['started'], $process_data['completed']) .'</li>';
						}
						else{
							echo '<li>Runtime : '. self::process_runtime($process_data['started'], time()) .'</li>';
						}
						echo "<li>Processed : {$process_data['processed']} of {$process_data['count']}</li>";
						echo '</ul>';
					}

				echo '</div>';
			echo '</form>';
		echo '</div>';

	}


	static function process_runtime($start,$end){
	    $datetime1 = new DateTime("@$start");
	    $datetime2 = new DateTime("@$end");
	    $interval = $datetime1->diff($datetime2);
	    return $interval->format('%Hh %Im');
	}

}
