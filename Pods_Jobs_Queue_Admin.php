<?php
/**
 * Class Pods_Jobs_Queue_Admin
 */
class Pods_Jobs_Queue_Admin {

	/**
	 * Setup admin hooks
	 */
	public static function init() {

		// Register assets
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_assets' ) );

		// Admin UI
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// Admin AJAX callback for Processing Job
		add_action( 'wp_ajax_pods_jobs_queue_process_job', array( __CLASS__, 'admin_ajax_process_job' ) );

		// Admin AJAX callback for Processing Queue
		add_action( 'wp_ajax_pods_jobs_queue_process_queue', array( __CLASS__, 'admin_ajax_process_queue' ) );
		add_action( 'wp_ajax_nopriv_pods_jobs_queue_process_queue', array( __CLASS__, 'admin_ajax_process_queue' ) );

	}

	/**
	 * Register assets for Pods Jobs Queue
	 */
	public function register_assets() {

		// Register JS script for Pods Jobs Queue processing
		wp_register_script( 'pods-jobs-queue', plugins_url( 'js/pods-jobs-queue.js', __FILE__ ), array( 'jquery' ), PODS_JOBS_QUEUE_VERSION, true );

		// Setup config values for reference
		$config = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'version' => PODS_JOBS_QUEUE_VERSION,
			'status_complete' => __( 'Pods Jobs Queue completed successfully', 'pods-jobs-queue' ),
			'status_stopped' => __( 'Pods Jobs Queue encountered an issue', 'pods-jobs-queue' )
		);

		// Setup variable for output when JS enqueued
		wp_localize_script( 'pods-jobs-queue', 'pods_jobs_queue_config', $config );

	}

	/**
	 * Add options page to menu
	 */
	public static function admin_menu() {

		if ( Pods_Jobs_Queue::is_compatible() && pods_is_admin( 'pods', 'pods_jobs_queue' ) ) {
			add_options_page( __( 'Pods Jobs Queue', 'pods-jobs-queue' ), __( 'Pods Jobs Queue', 'pods-jobs-queue' ), 'read', 'pods-jobs-queue', array( __CLASS__, 'admin_page' ) );
		}

	}

	/**
	 * Output admin page
	 */
	public static function admin_page() {

		include_once 'Pods_Jobs_Queue_API.php';

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$table = Pods_Jobs_Queue_API::table();

		Pods_Jobs_Queue_API::install();

		$ui = array(
			'item' => __( 'Job', 'pods-jobs-queue' ),
			'items' => __( 'Jobs', 'pods-jobs-queue' ),
			'header' => array(
				'view' => __( 'View Job Info', 'pods-jobs-queue' )
			),
			'sql' => array(
				'table' => $table,
				'field_id' => 'id',
				'field_index' => 'callback'
			),
			'orderby' => '( `t`.`status` = "queued" ) DESC, ( `t`.`status` = "completed" ) DESC, `t`.`date_queued` DESC, `t`.`date_completed` DESC',
			'fields' => array(
				'manage' => array(
					/*
				`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`callback` VARCHAR(255) NOT NULL,
				`arguments` LONGTEXT NOT NULL,
				`blog_id` BIGINT(20) NOT NULL,
				`memo` VARCHAR(255) NOT NULL,
				`group` VARCHAR(255) NOT NULL,
				`status` VARCHAR(10) NOT NULL,
				`date_queued` DATETIME NOT NULL,
				`date_started` DATETIME NOT NULL,
				`date_completed` DATETIME NOT NULL,*/
					'callback' =>  array(
						'name' => 'callback',
						'label' => 'Callback',
						'type' => 'text'
					),
					'memo' =>  array(
						'name' => 'memo',
						'label' => 'Memo',
						'type' => 'text'
					),
					'blog_id' => array(
						'name' => 'blog_id',
						'label' => 'Blog ID',
						'type' => 'number',
						'options' => array(
							'number_format_type' => '9999.99',
							'number_decimals' => 0
						),
						'width' => '5%'
					),
					'status' => array(
						'name' => 'avg_time',
						'label' => 'Average Load Time (seconds)',
						'type' => 'number',
						'options' => array(
							'number_decimals' => 3
						),
						'width' => '12%'
					),
					'date_queued' => array(
						'name' => 'total_calls',
						'label' => 'Total Calls',
						'type' => 'datetime'
					),
					'date_started' => array(
						'name' => 'total_calls',
						'label' => 'Total Calls',
						'type' => 'datetime'
					),
					'date_completed' => array(
						'name' => 'last_generated',
						'label' => 'Last Generated',
						'type' => 'datetime'
					)
				),
				'search' => array()
			),
			'filters' => array(
				'callback',
				'memo',
				'status',
				'date_queued',
				'date_started',
				'date_completed'
			),
			'filters_enhanced' => true,
			'actions_disabled' => array(
				'add',
				'edit',
				'duplicate',
				'export'
			),
			'actions_custom' => array(
				'process_job' => array(
					'callback' => array( __CLASS__, 'admin_page_process_job' )
				),
				'view' => array(
					'callback' => array( __CLASS__, 'admin_page_view_job' )
				),
				'delete' => array(
					'callback' => array( __CLASS__, 'admin_page_delete_job' )
				)
			),
			'actions_bulk' => array(
				'delete' => array(
					'label' => __( 'Delete', 'pods' )
					// callback not needed, Pods has this built-in for delete
				),
				'process_jobs' => array(
					'callback' => array( __CLASS__, 'admin_page_process_jobs' )
				)
			)
		);

		$ui[ 'fields' ][ 'search' ][ 'callback' ] = $ui[ 'fields' ][ 'manage' ][ 'callback' ];
		$ui[ 'fields' ][ 'search' ][ 'memo' ] = $ui[ 'fields' ][ 'manage' ][ 'memo' ];
		$ui[ 'fields' ][ 'search' ][ 'status' ] = $ui[ 'fields' ][ 'manage' ][ 'status' ];
		$ui[ 'fields' ][ 'search' ][ 'date_queued' ] = $ui[ 'fields' ][ 'manage' ][ 'date_queued' ];
		$ui[ 'fields' ][ 'search' ][ 'date_started' ] = $ui[ 'fields' ][ 'manage' ][ 'date_started' ];
		$ui[ 'fields' ][ 'search' ][ 'date_completed' ] = $ui[ 'fields' ][ 'manage' ][ 'date_completed' ];

		$ui[ 'fields' ][ 'view' ] = $ui[ 'fields' ][ 'manage' ];

		unset( $ui[ 'fields' ][ 'view' ][ 'callback' ] );

		$ui[ 'fields' ][ 'view' ][ 'arguments' ] = array(
			'name' => 'arguments',
			'label' => 'Callback Arguments',
			'type' => 'paragraph'
		);

		$ui[ 'fields' ][ 'view' ][ 'group' ] = array(
			'name' => 'group',
			'label' => 'Callback Group',
			'type' => 'text'
		);

		$ui[ 'fields' ][ 'view' ][ 'log' ] = array(
			'name' => 'log',
			'label' => 'Callback Log',
			'type' => 'text'
		);

		if ( 1 == pods_v( 'deleted_bulk' ) ) {
			unset( $ui[ 'actions_custom' ][ 'delete' ] );
		}

		pods_ui( $ui );

	}

	/**
	 * Handle View action
	 *
	 * @param PodsUI $obj
	 * @param string $id
	 */
	public static function admin_page_view_job( $obj, $id ) {

		$item = $obj->get_row();

		$item = array_map( 'maybe_unserialize', $item );

		include_once 'ui/view-job.php';

	}

	/**
	 * Handle Delete Job action
	 *
	 * @param string $id
	 * @param PodsUI $obj
	 *
	 * @return bool
	 */
	public static function admin_page_delete_job( $id, $obj ) {

		include_once 'Pods_Jobs_Queue_API.php';

		$deleted = Pods_Jobs_Queue_API::delete_job( $id );

		if ( $deleted ) {
			pods_message( sprintf( __( "<strong>Deleted:</strong> %s has been deleted.", 'pods' ), $obj->item ) );
		}

		return $deleted;

	}

	/**
	 * Handle Process Job action
	 *
	 * @param PodsUI $obj
	 * @param string $id
	 */
	public static function admin_page_process_job( $obj, $id ) {

		self::admin_page_process_jobs_ajax( array( $id ) );

		$obj->action = 'manage';
		$obj->id = 0;

		unset( $_GET[ 'action' ] );
		unset( $_GET[ 'id' ] );

		$obj->manage();

	}

	/**
	 * Handle Process Jobs bulk action
	 *
	 * @param array<string> $ids
	 * @param PodsUI $obj
	 */
	public static function admin_page_process_jobs( $ids, $obj ) {

		self::admin_page_process_jobs_ajax( $ids );

		$obj->action_bulk = false;
		unset( $_GET[ 'action_bulk' ] );

		$obj->bulk = array();
		unset( $_GET[ 'action_bulk_ids' ] );

		$obj->manage();

	}

	/**
	 * Handle AJAX processing of jobs
	 *
	 * @param array<string> $ids
	 */
	public static function admin_page_process_jobs_ajax( $ids ) {

		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$ids = array_map( 'absint', $ids );

		$pods_jobs_queue = array();

		foreach ( $ids as $id ) {
			// Build nonce action from request
			$nonce_action = 'pods-jobs-queue-' . $id . '/process';

			// Build nonce from action
			$nonce = wp_create_nonce( $nonce_action );

			// Setup object to push for processing
			$pods_jobs_queue[] = array(
				'job_id' => $id,
				'nonce' => $nonce
			);
		}

		// Enqueue Pods Jobs Queue JS
		wp_enqueue_script( 'pods-jobs-queue' );

		// Queue view to be included via AJAX
		echo '<script>' . "\n"
			. 'var pods_jobs_queue = ' . json_encode( $pods_jobs_queue ) . ';' . "\n"
			. '</script>' . "\n";

		// Enqueue jQuery UI Progressbar
		wp_enqueue_script( 'jquery-ui-progressbar' );

		$message = '<span id="pods-jobs-queue-progress-status">%s</span>'
			. '<div id="pods-jobs-queue-progress-indicator" style="position:relative;max-width:300px;display:none;">'
			. '<div id="pods-jobs-queue-progress-label" style="position:absolute;left:45%%;top:6px;font-weight:bold;text-shadow:1px 1px 0 #FFF;font-size:12px;">%s</div>'
			. '</div>';

		$message = sprintf( $message, _n( 'Processing Job', 'Processing Jobs', count( $pods_jobs_queue ), 'pods-jobs-queue' ), __( 'Loading...', 'pods-jobs-queue' ) );

		pods_message( $message );

	}

	/**
	 * Handle the Admin AJAX request to process a job
	 */
	public static function admin_ajax_process_job() {

		include_once 'Pods_Jobs_Queue_API.php';

		// Check if request is there
		if ( ! empty( $_REQUEST[ 'pods_jobs_queue_job_id' ] ) && ! empty( $_REQUEST[ 'pods_jobs_queue_nonce' ] ) ) {
			$job_id = (int) $_REQUEST[ 'pods_jobs_queue_job_id' ];

			// Build nonce action from request
			$nonce_action = 'pods-jobs-queue-' . $job_id . '/process';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST[ 'pods_jobs_queue_nonce' ], $nonce_action ) ) {
				Pods_Jobs_Queue_API::run_job( (int) $job_id );
			}
		}

		// AJAX must die
		die();

	}

	/**
	 * Handle the Admin AJAX request to process the queue
	 */
	public static function admin_ajax_process_queue() {

		include_once 'Pods_Jobs_Queue_API.php';

		// Check if request uses API key, and if incorrect, don't serve request
		if ( isset( $_REQUEST[ 'pods_jobs_queue_api_key' ] ) ) {
			if ( ! defined( 'PODS_JOBS_QUEUE_API_KEY' ) || PODS_JOBS_QUEUE_API_KEY != $_REQUEST[ 'pods_jobs_queue_api_key' ]  ) {
				die();
			}
		}
		// If user is not logged in or not a Pods admin, don't serve request
		elseif ( ! is_user_logged_in() || ! pods_is_admin( 'pods', 'pods_jobs_queue' ) ) {
			die();
		}

		Pods_Jobs_Queue_API::run_queue();

		// AJAX must die
		die();

	}

}