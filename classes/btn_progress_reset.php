<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Private Data Migration Class
 *
 * @since      1.0.0
 * @package    btn-learndash-reset
 * @subpackage btn-learndash-reset/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */

class btn_learndash_reset_btn_progress_reset extends WP_Background_Process {

	const PROGRESS_KEY = 'btn/ld/user/progress';
	protected $action = 'update_ld_user_progress';
	protected static $quiz_list;
	protected static $assignment_list;
	protected function task( $data ) {

		$user_id = isset($data['user_id']) ? $data['user_id'] : 0;
		$progress = get_option(self::PROGRESS_KEY);
		$progress['processed'] = (int)$progress['processed'] + 1;

		if( (int)$user_id < 1 ){
			return false;
		}
		$courses = learndash_user_get_enrolled_courses( $user_id );
		foreach($courses as $course_id){
			if($course_id > 0){
				self::reset_course_progress( $user_id, $course_id );
			}
		}
		if(!in_array(124707, $courses )){
			$course_id = 124707;
			self::reset_course_progress( $user_id, $course_id );
		}
		update_option(self::PROGRESS_KEY, $progress, false);
		$log = "\n\nTime = " . date('Y-m-d j:i:s') . "\n";
		$log .= 'User ID = ' . $user_id . "\n";
		$log .= 'Activities = ' . $activity_count . "\n";
		file_put_contents(BTN_LEARNDASH_DIR . '/user_progress_process.txt', $log, FILE_APPEND);
		return false;
    }


	/**
	 * Complete
	 *
	 */
	protected function complete() {

		$progress_key = self::PROGRESS_KEY;
		$progress = get_option($progress_key);
		$progress['status'] = 'completed';
		$progress['completed'] = time();
		update_option($progress_key, $progress, false);

		parent::complete();
	}

	/**
	 *
	 * Reset Course Progress
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	protected static function reset_course_progress( $user_id, $course_id, $option = 'all', $will_unenroll = false ) {
		$user_id = intval( $user_id );
		$course_id = intval( $course_id );
		if ( '-1' !== $course_id ) {

			$is_course_progress_deleted = self::delete_course_progress( $user_id, $course_id );
			$is_user_activity_deleted = self::delete_user_activity( $user_id, $course_id );
			$is_quiz_progress_reset = self::reset_quiz_progress( $user_id, $course_id );
			$is_assignment_deleted = self::delete_assignments();

			if ( class_exists( '\UCTINCAN\Database\Admin' ) ) {
				self::reset_tincanny_data( $user_id, $course_id );
			}
		}
	}

	/**
	 *
	 * Delete course progress from Usermeta Table
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	protected static function delete_course_progress( $user_id, $course_id ) {
		$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
		if( isset( $usermeta ) && ! empty( $usermeta ) && $usermeta != '' ) {
			unset( $usermeta[ $course_id ] );
			update_user_meta( $user_id, '_sfwd-course_progress', $usermeta );
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * Delete course related meta keys from user meta table and delete all activity related to a course.
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	protected static function delete_user_activity( $user_id, $course_id ) {
		global $wpdb;
		delete_user_meta( $user_id, 'completed_' . $course_id );
		delete_user_meta( $user_id, 'course_completed_' . $course_id );
		delete_user_meta( $user_id, 'learndash_course_expired_' . $course_id );
		$activity_ids = $wpdb->get_results( 'SELECT activity_id FROM ' . $wpdb->prefix . 'learndash_user_activity WHERE course_id = ' . $course_id . ' AND user_id = ' . $user_id );
		if ( $activity_ids ) {
			foreach ( $activity_ids as $activity_id ) {
				$wpdb->query( "DELETE FROM  {$wpdb->prefix}learndash_user_activity_meta WHERE activity_id = {$activity_id->activity_id}" );
				$wpdb->query( "DELETE FROM {$wpdb->prefix}learndash_user_activity WHERE activity_id = {$activity_id->activity_id}" );
			}
		}
	}

	/**
	 * Delete assignments of course, related to lessons / topics
	 */
	protected static function delete_assignments() {
		global $wpdb;
		$is_deleted = false;
		$assignments = self::$assignment_list;
		if ( $assignments ) {
			foreach ( $assignments as $assignment ) {
				$is_deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID = {$assignment}" );
				$is_deleted = $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id = {$assignment}" );
			}
		}
		return $is_deleted;
	}

	/**
	 *
	 * Get lesson quiz list
	 * Get Lesson assignment list
	 * Delete quiz progress, related to course, quiz etc
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	protected static function reset_quiz_progress( $user_id, $course_id, $is_assignment = false ) {
		$lessons = learndash_get_lesson_list( $course_id, array( 'num' => 0 ) );
		foreach ( $lessons as $lesson ) {
			self::get_topics_quiz( $user_id, $lesson->ID, $course_id );
			$lesson_quiz_list = learndash_get_lesson_quiz_list( $lesson->ID, $user_id, $course_id );
			if ( $lesson_quiz_list ) {
				foreach ( $lesson_quiz_list as $ql ) {
					self::$quiz_list[ $ql['post']->ID ] = 0;
				}
			}

			// get lesson related assignments
			$assignments = get_posts( [
				'post_type'      => 'sfwd-assignment',
				'posts_per_page' => 999,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => 'lesson_id',
						'value'   => $lesson->ID,
						'compare' => '=',
					],
					[
						'key'     => 'course_id',
						'value'   => $course_id,
						'compare' => '=',
					],
					[
						'key'     => 'user_id',
						'value'   => $user_id,
						'compare' => '=',
					]
				]
			] );

			if ( $assignments ) {
				foreach ( $assignments as $assignment ) {
					self::$assignment_list[] = $assignment->ID;
				}
			}
		}

		if( ! $is_assignment ) {
			return self::delete_quiz_progress( $user_id, $course_id );
		}
	}

	/**
	 *
	 * Get topic quiz + assignment list
	 *
	 * @param $user_id
	 * @param $lesson_id
	 * @param $course_id
	 */
	protected static function get_topics_quiz( $user_id, $lesson_id, $course_id ) {
		$topic_list = learndash_get_topic_list( $lesson_id, $course_id );
		if ( $topic_list ) {
			foreach ( $topic_list as $topic ) {
				$topic_quiz_list = learndash_get_lesson_quiz_list( $topic->ID, $user_id, $course_id );
				if ( $topic_quiz_list ) {
					foreach ( $topic_quiz_list as $ql ) {
						self::$quiz_list[ $ql['post']->ID ] = 0;
					}
				}

				$assignments = get_posts( [
					'post_type'      => 'sfwd-assignment',
					'posts_per_page' => 999,
					'meta_query'     => [
						'relation' => 'AND',
						[
							'key'     => 'lesson_id',
							'value'   => $topic->ID,
							'compare' => '=',
						],
						[
							'key'     => 'course_id',
							'value'   => $course_id,
							'compare' => '=',
						],
						[
							'key'     => 'user_id',
							'value'   => $user_id,
							'compare' => '=',
						]
					]
				] );

				if ( $assignments ) {
					foreach ( $assignments as $assignment ) {
						self::$assignment_list[] = $assignment->ID;
					}
				}
			}
		}
	}


	/**
	 *
	 * Actually deleting quiz data from user meta and pro quiz activity table
	 *
	 * @param      $user_id
	 * @param null $course_id
	 */
	protected static function delete_quiz_progress( $user_id, $course_id = null ) {
		$quizzes = learndash_get_course_quiz_list( $course_id, $user_id );
		if ( $quizzes ) {
			foreach ( $quizzes as $quiz ) {
				self::$quiz_list[ $quiz['post']->ID ] = 0;
			}
		}
		global $wpdb;

		$quizz_progress = [];
		if ( ! empty( self::$quiz_list ) ) {
			$usermeta       = get_user_meta( $user_id, '_sfwd-quizzes', true );
			$quizz_progress = empty( $usermeta ) ? array() : $usermeta;
			foreach ( $quizz_progress as $k => $p ) {
				if ( key_exists( $p['quiz'], self::$quiz_list ) ) {
					$statistic_ref_id = $p['statistic_ref_id'];
					unset( $quizz_progress[ $k ] );
					if ( ! empty( $statistic_ref_id ) ) {
						if ( class_exists( '\LDLMS_DB' ) ) {
							$pro_quiz_stat_table   = \LDLMS_DB::get_table_name( 'quiz_statistic' );
							$pro_quiz_stat_ref_table = \LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
						} else {
							$pro_quiz_stat_table   = $wpdb->prefix . 'wp_pro_quiz_statistic';
							$pro_quiz_stat_ref_table = $wpdb->prefix . 'wp_pro_quiz_statistic_ref';
						}
						$wpdb->query( "DELETE FROM $pro_quiz_stat_table WHERE statistic_ref_id = {$statistic_ref_id}" );
						$wpdb->query( "DELETE FROM $pro_quiz_stat_ref_table WHERE statistic_ref_id = {$statistic_ref_id}" );
					}
				}
			}
		}

		return update_user_meta( $user_id, '_sfwd-quizzes', $quizz_progress );
	}

	/**
	 * Delete TinCanny data on reset.
	 *
	 * @param $user_id
	 * @param $course_id
	 */
	public static function reset_tincanny_data( $user_id, $course_id ) {
		global $wpdb;
		$table_reporting = \UCTINCAN\Database\Admin::TABLE_REPORTING;
		$table_resume    = \UCTINCAN\Database\Admin::TABLE_RESUME;
		$query           = sprintf( "
			DELETE FROM %s%s
				WHERE `user_id` = %s
				AND `course_id` = %s;
			",
			$wpdb->prefix,
			$table_reporting,
			$user_id,
			$course_id
		);

		$wpdb->query( $query );

	}

}
