<?php

/**
 * Class Pods_Jobs_Queue
 */
class Pods_Jobs_Queue {

	/**
	 * @var bool $compatible Whether plugin is compatible with Pods install
	 */
	public static $compatible = null;

	/**
	 * Setup default constants, add hooks
	 */
	public static function init() {
		if ( is_admin() && self::is_compatible() ) {
			include_once 'Pods_Jobs_Queue_Admin.php';

			// Init admin
			add_action( 'init', [ 'Pods_Jobs_Queue_Admin', 'init' ] );
		}
	}

	/**
	 * Check if plugin is compatible with Pods install
	 *
	 * @return bool
	 */
	public static function is_compatible() {
		// See if compatible has been checked yet, if not, check it and set it
		if ( null === self::$compatible ) {
			// Default compatible is false
			self::$compatible = false;

			// Check if Pods is installed, that it's 2.7+, and that pods_view exists
			if ( defined( 'PODS_VERSION' ) && version_compare( '2.7', PODS_VERSION, '<=' ) ) {
				// Set compatible to true for future reference
				self::$compatible = true;

				// Setup plugin if not yet setup
				if ( PODS_JOBS_QUEUE_VERSION !== get_option( 'pods_jobs_queue_version' ) ) {
					self::activate();
				}
			}
		}

		return self::$compatible;
	}

	/**
	 * Activate plugin routine
	 */
	public static function activate() {
		include_once 'Pods_Jobs_Queue_API.php';

		Pods_Jobs_Queue_API::install();
	}

	/**
	 * Deactivate plugin routine
	 */
	public static function deactivate() {
		include_once 'Pods_Jobs_Queue_API.php';

		Pods_Jobs_Queue_API::uninstall();
	}

}