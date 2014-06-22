<?php
/*
Plugin Name: Pods Jobs Queue
Plugin URI: http://pods.io/
Description: Queue callbacks to be ran with arguments, unlike wp_cron which is scheduled jobs, these are queued and run concurrently as needed
Version: 0.1.6
Author: The Pods Framework Team
Author URI: http://pods.io/
*/

// Pods Jobs Queue version
define( 'PODS_JOBS_QUEUE_VERSION', '0.1.6' );

// Include class
include_once 'Pods_Jobs_Queue.php';

// On plugins loaded, run our init
add_action( 'plugins_loaded', array( 'Pods_Jobs_Queue', 'init' ) );

// Activation / Deactivation hooks
register_activation_hook( __FILE__, array( 'Pods_Jobs_Queue', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pods_Jobs_Queue', 'deactivate' ) );

/**
 * Queue job to be ran by Pods Jobs Queue
 *
 * @param array $data
 *
 * @return int|bool The new Job ID if it was added, otherwise false
 */
function pods_queue_job( array $data ) {

	include_once 'Pods_Jobs_Queue_API.php';

	return Pods_Jobs_Queue_API::queue_job( $data );

}

function pods_jobs_queue_run() {



}