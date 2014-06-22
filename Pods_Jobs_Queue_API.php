<?php
/**
 * Class Pods_Jobs_Queue_API
 */
class Pods_Jobs_Queue_API {

	/**
	 * Internal table name for Pods Jobs Queue
	 *
	 * @var string
	 */
	const TABLE = 'podsjobs';

	/**
	 * Get the table for Pods Jobs Queue, based on current table prefix
	 *
	 * @return string
	 */
	public static function table() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		return $wpdb->prefix . self::TABLE;

	}

	/**
	 * Install Pods Jobs Queue
	 */
	public static function install() {

		$table = self::table();

		// Table definitions
		$tables = array();

		$tables[] = "
			CREATE TABLE `{$table}` (
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`callback` VARCHAR(255) NOT NULL,
				`arguments` LONGTEXT NOT NULL,
				`blog_id` BIGINT(20) NOT NULL,
				`memo` VARCHAR(255) NOT NULL,
				`group` VARCHAR(255) NOT NULL,
				`status` VARCHAR(10) NOT NULL,
				`date_queued` DATETIME NOT NULL,
				`date_started` DATETIME NOT NULL,
				`date_completed` DATETIME NOT NULL,
				`log` LONGTEXT NOT NULL,
				PRIMARY KEY (`id`)
			)
		";

		// Create / alter table handling
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $tables );

		// Update version in DB
		update_option( 'pods_jobs_queue_version', PODS_JOBS_QUEUE_VERSION );

	}

	/**
	 * Uninstall Pods Jobs Queue
	 */
	public static function uninstall() {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		// Delete table if it exists
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

	}

	/**
	 * Get the next job from the queue
	 *
	 * @param string $status Status to get job from
	 *
	 * @return object|null The next job as an object, or null if no job found
	 */
	public static function get_next_job( $status = 'queued' ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return null;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$sql = "
			SELECT * FROM `{$table}`
			WHERE `status` = %s
			ORDER BY `date_queued`, `id`
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $status ), ARRAY_A );

		if ( ! empty( $item ) ) {
			$item = (object) array_map( 'maybe_unserialize', $item );
		}
		else {
			$item = null;
		}

		return $item;

	}

	/**
	 * Get the next job from the queue
	 *
	 * @param int $job_id Job ID
	 *
	 * @return object|null The job as an object, or null if job not found
	 */
	public static function get_job( $job_id ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return null;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$sql = "
			SELECT * FROM `{$table}`
			WHERE `id` = %d
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $job_id ), ARRAY_A );

		if ( ! empty( $item ) ) {
			$item = (object) array_map( 'maybe_unserialize', $item );
		}
		else {
			$item = null;
		}

		return $item;

	}

	/**
	 * Queue job to be ran and merge with default job info
	 *
	 * @param array $data
	 *
	 * @return int|bool The new Job ID if it was added, otherwise false
	 */
	public static function queue_job( $data ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return false;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$defaults = array(
			'callback' => '',
			'arguments' => '',
			'blog_id' => (int) ( function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0 ),
			'group' => '',
			'status' => 'queued'
		);

		$data = array_merge( $defaults, $data );

		// Set date_queued to current datetime
		$data[ 'date_queued' ] = current_time( 'mysql' );

		$save_data = array_map( 'maybe_serialize', $data );

		$inserted = $wpdb->replace( $table, $save_data, array_fill( 0, count( $data ), '%s' ) );

		$inserted = ! empty( $inserted );

		$job_id = false;

		if ( $inserted ) {
			$job_id = $wpdb->insert_id;

			do_action( 'pods_jobs_queue_job_queued', $job_id, $data );
		}

		return $job_id;

	}

	/**
	 * @param $job_id
	 *
	 * @return bool If job was started successfully
	 */
	public static function start_job( $job_id ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return false;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$data = array(
			'date_started' => current_time( 'mysql' ),
			'status' => 'processing'
		);

		$data = apply_filters( 'pods_jobs_queue_start_job', $data, $job_id );

		$save_data = array_map( 'maybe_serialize', $data );

		$updated = $wpdb->update(
			 $table,
			 $save_data,
			 array( 'id' => $job_id ),
			 array_fill( 0, count( $data ), '%s' ),
			 array( '%d' )
		);

		$updated = ! empty( $updated );

		if ( $updated ) {
			do_action( 'pods_jobs_queue_job_started', $job_id, $data );
		}

		return $updated;

	}

	/**
	 * @param $job_id
	 * @param null $return
	 *
	 * @return bool If job was completed successfully
	 */
	public static function complete_job( $job_id, $return = null ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return false;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$data = array(
			'date_completed' => current_time( 'mysql' ),
			'status' => 'completed'
		);

		$data = apply_filters( 'pods_jobs_queue_complete_job', $data, $job_id, $return );

		$save_data = array_map( 'maybe_serialize', $data );

		$updated = $wpdb->update(
			 $table,
			 $save_data,
			 array( 'id' => $job_id ),
			 array_fill( 0, count( $data ), '%s' ),
			 array( '%d' )
		);

		$updated = ! empty( $updated );

		if ( $updated ) {
			do_action( 'pods_jobs_queue_job_completed', $job_id, $data, $return );
		}

		return $updated;

	}

	/**
	 * @param $job_id
	 * @param string $log_message
	 *
	 * @return bool If job was stopped successfully
	 */
	public static function stop_job( $job_id, $log_message = '' ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return false;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$data = array(
			'date_completed' => current_time( 'mysql' ),
			'status' => 'failed',
			'log' => $log_message
		);

		$data = apply_filters( 'pods_jobs_queue_stop_job', $data, $job_id, $log_message );

		$save_data = array_map( 'maybe_serialize', $data );

		$updated = $wpdb->update(
			 $table,
			 $save_data,
			 array( 'id' => $job_id ),
			 array_fill( 0, count( $data ), '%s' ),
			 array( '%d' )
		);

		$updated = ! empty( $updated );

		if ( $updated ) {
			do_action( 'pods_jobs_queue_job_stopped', $job_id, $data, $log_message );
		}

		return $updated;

	}

	/**
	 * @param $job_id
	 *
	 * @return bool If job was deleted successfully
	 */
	public static function delete_job( $job_id ) {

		if ( ! Pods_Jobs_Queue::is_compatible() ) {
			return false;
		}

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = self::table();

		$deleted = $wpdb->delete(
			 $table,
			 array( 'id' => $job_id ),
			 array( '%d' )
		);

		$deleted = ! empty( $deleted );

		if ( $deleted ) {
			do_action( 'pods_jobs_queue_job_deleted', $job_id );
		}

		return $deleted;

	}

	public static function run_job( $job_id ) {

		$job = $job_id;

		if ( ! is_object( $job ) ) {
			$job = self::get_job( (int) $job_id );
		}

		if ( ! empty( $job ) ) {
			self::start_job( $job->id );

			if ( is_callable( $job->callback ) ) {
				try {
					ob_start();

					if ( ! empty( $job->arguments ) ) {
						$return = call_user_func_array( $job->callback, $job->arguments );
					}
					else {
						$return = call_user_func( $job->callback );
					}

					$output = trim( ob_get_clean() );

					if ( null === $return && 0 < strlen( $output ) ) {
						$return = $output;
					}

					self::complete_job( $job->id, $return );

					return true;
				}
				catch ( Exception $e ) {
					self::stop_job( $job->id, $e->getMessage() );
				}
			}
			else {
				self::stop_job( $job->id, 'Callback not found' );
			}
		}

		return false;

	}

	public static function run_queue() {

		$count = 0;

		$max_jobs = apply_filters( 'pods_jobs_queue_max_process', 25 );

		while ( $count < $max_jobs && $job = self::get_next_job() ) {
			$count++;

			self::run_job( $job );
		}

	}

}