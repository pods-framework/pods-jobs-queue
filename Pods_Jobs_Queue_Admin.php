<?php

/**
 * Class Pods_Jobs_Queue_Admin
 */
class Pods_Jobs_Queue_Admin {

	/**
	 * Setup admin hooks
	 */
	public static function init() {
		// Admin UI
		add_filter( 'pods_admin_components_menu', [ __CLASS__, 'admin_menu' ] );

		// Register assets
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register_assets' ] );

		// Admin AJAX callback for Processing Job
		add_action( 'wp_ajax_pods_jobs_queue_process_job', [ __CLASS__, 'admin_ajax_process_job' ] );

		// Admin AJAX callback for Processing Queue
		add_action( 'wp_ajax_pods_jobs_queue_process_queue', [ __CLASS__, 'admin_ajax_process_queue' ] );
		add_action( 'wp_ajax_nopriv_pods_jobs_queue_process_queue', [ __CLASS__, 'admin_ajax_process_queue' ] );
	}

	/**
	 * Add options page to menu
	 *
	 * @since 0.0.1
	 *
	 * @param array $admin_menus The submenu items in Pods Admin menu.
	 *
	 * @return mixed
	 *
	 */
	public static function admin_menu( $admin_menus ) {
		$admin_menus['Jobs Queue'] = [
			'menu_page'  => 'pods-jobs-queue',
			'page_title' => __( 'Pods Jobs Queue', 'pods-jobs-queue' ),
			'capability' => 'manage_options',
			'callback'   => [ __CLASS__, 'admin_page' ],
		];

		return $admin_menus;
	}

	/**
	 * Register assets for Pods Jobs Queue
	 */
	public static function register_assets() {
		// Register JS script for Pods Jobs Queue processing
		wp_register_script( 'pods-jobs-queue', plugins_url( 'js/pods-jobs-queue.js', __FILE__ ), [ 'jquery' ], PODS_JOBS_QUEUE_VERSION, true );

		// Setup config values for reference
		$config = [
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'version'         => PODS_JOBS_QUEUE_VERSION,
			'status_complete' => __( 'Pods Jobs Queue completed successfully', 'pods-jobs-queue' ),
			'status_stopped'  => __( 'Pods Jobs Queue encountered an issue', 'pods-jobs-queue' ),
		];

		// Setup variable for output when JS enqueued
		wp_localize_script( 'pods-jobs-queue', 'pods_jobs_queue_config', $config );
	}

	/**
	 * Output admin page
	 */
	public static function admin_page() {
		include_once 'Pods_Jobs_Queue_API.php';

		/**
		 * @var $wpdb wpdb
		 */ global $wpdb;

		$table = Pods_Jobs_Queue_API::table();

		Pods_Jobs_Queue_API::install();

		$ui = [
			'item'             => __( 'Job', 'pods-jobs-queue' ),
			'items'            => __( 'Jobs', 'pods-jobs-queue' ),
			'header'           => [
				'view' => __( 'View Job Info', 'pods-jobs-queue' ),
			],
			'sql'              => [
				'table'       => $table,
				'field_id'    => 'id',
				'field_index' => 'callback',
			],
			'orderby'          => '( `t`.`status` = "processing" ) DESC, ( `t`.`status` = "queued" ) DESC, ( `t`.`status` = "failed" ) DESC, ( `t`.`status` = "completed" ) DESC, `t`.`date_queued` DESC, `t`.`date_completed` DESC',
			'fields'           => [
				'manage' => [
					'callback'       => [
						'name'  => 'callback',
						'label' => 'Callback',
						'type'  => 'text',
					],
					'group'          => [
						'name'  => 'group',
						'label' => 'Group',
						'type'  => 'text',
					],
					'memo'           => [
						'name'  => 'memo',
						'label' => 'Memo',
						'type'  => 'text',
					],
					'status'         => [
						'name'        => 'status',
						'label'       => 'Status',
						'type'        => 'pick',
						'pick_object' => 'custom-simple',
						'data'        => [
							'queued'     => __( 'Queued', 'pods-jobs-queue' ),
							'processing' => __( 'Processing', 'pods-jobs-queue' ),
							'completed'  => __( 'Completed', 'pods-jobs-queue' ),
							'failed'     => __( 'Failed', 'pods-jobs-queue' ),
						],
					],
					'date_queued'    => [
						'name'    => 'date_queued',
						'label'   => 'Time Queued',
						'type'    => 'datetime',
						'options' => [
							'datetime_allow_empty' => 1,
						],
					],
					'date_started'   => [
						'name'    => 'date_started',
						'label'   => 'Time Started',
						'type'    => 'datetime',
						'options' => [
							'datetime_allow_empty' => 1,
						],
					],
					'date_completed' => [
						'name'    => 'date_completed',
						'label'   => 'Time Completed',
						'type'    => 'datetime',
						'options' => [
							'datetime_allow_empty' => 1,
						],
					],
				],
				'search' => [],
			],
			'filters'          => [
				'callback',
				'memo',
				'group',
				'status',
				'date_queued',
				'date_started',
				'date_completed',
			],
			'filters_enhanced' => true,
			'actions_disabled' => [
				'add',
				'edit',
				'duplicate',
				'export',
			],
			'actions_custom'   => [
				'process_job' => [
					'callback' => [ __CLASS__, 'admin_page_process_job' ],
				],
				'view'        => [
					'callback' => [ __CLASS__, 'admin_page_view_job' ],
				],
				'delete'      => [
					'callback' => [ __CLASS__, 'admin_page_delete_job' ],
				],
			],
			'actions_bulk'     => [
				'delete'       => [
					'label' => __( 'Delete', 'pods' )
					// callback not needed, Pods has this built-in for delete
				],
				'process_jobs' => [
					'callback' => [ __CLASS__, 'admin_page_process_jobs' ],
				],
			],
		];

		$ui['fields']['search']['callback']       = $ui['fields']['manage']['callback'];
		$ui['fields']['search']['memo']           = $ui['fields']['manage']['memo'];
		$ui['fields']['search']['group']          = $ui['fields']['manage']['group'];
		$ui['fields']['search']['status']         = $ui['fields']['manage']['status'];
		$ui['fields']['search']['date_queued']    = $ui['fields']['manage']['date_queued'];
		$ui['fields']['search']['date_started']   = $ui['fields']['manage']['date_started'];
		$ui['fields']['search']['date_completed'] = $ui['fields']['manage']['date_completed'];

		$ui['fields']['view'] = $ui['fields']['manage'];

		unset( $ui['fields']['view']['callback'] );

		$ui['fields']['view']['arguments'] = [
			'name'  => 'arguments',
			'label' => 'Callback Arguments',
			'type'  => 'paragraph',
		];

		$ui['fields']['view']['log'] = [
			'name'  => 'log',
			'label' => 'Callback Log',
			'type'  => 'text',
		];

		$ui['fields']['view']['blog_id'] = [
			'name'    => 'blog_id',
			'label'   => 'Blog ID',
			'type'    => 'number',
			'options' => [
				'number_format_type' => '9999.99',
				'number_decimals'    => 0,
			],
			'width'   => '5%',
		];

		if ( ! defined( 'PODS_JOBS_QUEUE_GROUPS' ) || ! PODS_JOBS_QUEUE_GROUPS ) {
			unset( $ui['fields']['manage']['group'], $ui['fields']['manage']['search'], $ui['fields']['manage']['view'] );

			unset( $ui['filters'][ array_search( 'group', $ui['filters'] ) ] );
		}

		if ( 1 === (int) pods_v( 'deleted_bulk' ) ) {
			unset( $ui['actions_custom']['delete'] );
		}

		// Run full queue
		if ( 'process_jobs' == pods_v( 'action_bulk' ) && empty( $_GET['action_bulk_ids'] ) ) {
			$max_jobs = apply_filters( 'pods_jobs_queue_max_full_process', - 1 );

			$ids = Pods_Jobs_Queue_API::get_queue( $max_jobs );

			self::admin_page_process_jobs_ajax( $ids );
		}

		// Custom select
		if ( 'manage' == pods_v( 'action', 'get', 'manage' ) ) {
			$ui['sql']['select'] = '`t`.`' . implode( '`,`t`.`', array_keys( $ui['fields']['manage'] ) ) . '`';
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
		self::admin_page_process_jobs_ajax( [ $id ] );

		$obj->action = 'manage';
		$obj->id     = 0;

		unset( $_GET['action'] );
		unset( $_GET['id'] );

		$obj->manage();
	}

	/**
	 * Handle Process Jobs bulk action
	 *
	 * @param array<string> $ids
	 * @param PodsUI        $obj
	 */
	public static function admin_page_process_jobs( $ids, $obj ) {
		self::admin_page_process_jobs_ajax( $ids );

		$obj->action_bulk = false;
		unset( $_GET['action_bulk'] );

		$obj->bulk = [];
		unset( $_GET['action_bulk_ids'] );

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
		 */ global $wpdb;

		$ids = array_map( 'absint', $ids );

		$pods_jobs_queue = [];

		foreach ( $ids as $id ) {
			// Build nonce action from request
			$nonce_action = 'pods-jobs-queue-' . $id . '/process';

			// Build nonce from action
			$nonce = wp_create_nonce( $nonce_action );

			// Setup object to push for processing
			$pods_jobs_queue[] = [
				'job_id' => $id,
				'nonce'  => $nonce,
			];
		}

		// Enqueue Pods Jobs Queue JS
		wp_enqueue_script( 'pods-jobs-queue' );

		// Queue view to be included via AJAX
		echo '<script>' . "\n" . 'var pods_jobs_queue = ' . json_encode( $pods_jobs_queue ) . ';' . "\n" . '</script>' . "\n";

		// Enqueue jQuery UI Progressbar
		wp_enqueue_script( 'jquery-ui-progressbar' );

		$message = '<span id="pods-jobs-queue-progress-status">%s</span>' . '<div id="pods-jobs-queue-progress-indicator" style="position:relative;max-width:300px;display:none;">' . '<div id="pods-jobs-queue-progress-label" style="position:absolute;left:45%%;top:6px;font-weight:bold;text-shadow:1px 1px 0 #FFF;font-size:12px;">%s</div>' . '</div>';

		$total_jobs = count( $pods_jobs_queue );

		$message = sprintf( $message, sprintf( _n( 'Processing %s Job', 'Processing %s Jobs', $total_jobs, 'pods-jobs-queue' ), number_format_i18n( $total_jobs ) ), __( 'Loading...', 'pods-jobs-queue' ) );

		pods_message( $message );
	}

	/**
	 * Handle the Admin AJAX request to process a job
	 */
	public static function admin_ajax_process_job() {
		include_once 'Pods_Jobs_Queue_API.php';

		// Check if request is there
		if ( ! empty( $_REQUEST['pods_jobs_queue_job_id'] ) && ! empty( $_REQUEST['pods_jobs_queue_nonce'] ) ) {
			define( 'PODS_JOBS_DOING', true );

			$job_id = (int) $_REQUEST['pods_jobs_queue_job_id'];

			// Build nonce action from request
			$nonce_action = 'pods-jobs-queue-' . $job_id . '/process';

			// Verify nonce is correct
			if ( false !== wp_verify_nonce( $_REQUEST['pods_jobs_queue_nonce'], $nonce_action ) ) {
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
		if ( isset( $_REQUEST['pods_jobs_queue_api_key'] ) ) {
			if ( ! defined( 'PODS_JOBS_QUEUE_API_KEY' ) || PODS_JOBS_QUEUE_API_KEY != $_REQUEST['pods_jobs_queue_api_key'] ) {
				die();
			}
		} // If user is not logged in or not a Pods admin, don't serve request
		elseif ( ! is_user_logged_in() || ! pods_is_admin( 'pods', 'pods_jobs_queue' ) ) {
			die();
		}

		define( 'PODS_JOBS_DOING', true );

		$jobs = Pods_Jobs_Queue_API::run_queue();

		$total_jobs = count( $jobs );

		echo sprintf( 'Completed %d %s: %s', $total_jobs, _n( 'job', 'jobs', $total_jobs ), implode( ', ', $jobs ) );

		// AJAX must die
		die();
	}

}