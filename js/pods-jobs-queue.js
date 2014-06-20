/**
 * Pods Jobs Queue processor
 *
 * @type {{queue: Array, process_next: process_next, load_view: load_view}}
 */
var pods_jobs_queue_Processor = {

	/**
	 * Store current queue of views to process
	 */
	queue : [],

	/**
	 * Store total number of original queue
	 */
	total : 0,

	/**
	 * Store progress indicator objects
	 */
	progress_indicator : {
		status : null,
		progress : null,
		progress_label : null
	},

	/**
	 * Process the next view from the queue
	 */
	process_next : function() {

		if ( pods_jobs_queue_config.is_admin && null === pods_jobs_queue_Processor.progress_indicator.progress ) {
			pods_jobs_queue_Processor.progress_indicator.status = jQuery( '#pods-jobs-queue-progress-status' );
			pods_jobs_queue_Processor.progress_indicator.progress = jQuery( '#pods-jobs-queue-progress-indicator' );
			pods_jobs_queue_Processor.progress_indicator.progress_label = jQuery( '#pods-jobs-queue-progress-label'  );

			pods_jobs_queue_Processor.progress_indicator.progress.show();

			pods_jobs_queue_Processor.progress_indicator.progress.progressbar( {
				value : 0,
				max : 200,
				change : function() {

					var value = pods_jobs_queue_Processor.progress_indicator.progress.progressbar( 'value' );

					// Value / 2 because max is 200 and we want 0-100% format
					pods_jobs_queue_Processor.progress_indicator.progress_label.text( ( value / 2 ) + '%' );

				},
				complete : function() {

					pods_jobs_queue_Processor.progress_indicator.progress_label.text( '100%' );

				}
			} );
		}

		// Check if there are views in the queue
		if ( pods_jobs_queue_Processor.queue.length ) {
			// Get the next view in line
			view = pods_jobs_queue_Processor.queue.shift();

			// Check for valid view data, then load the view
			if ( 'undefined' != typeof view.cache_key && 'undefined' != typeof view.cache_mode && 'undefined' != typeof view.nonce ) {
				pods_jobs_queue_Processor.load_view( view.cache_key, view.cache_mode, view.nonce );
			}
			// If invalid, process next view from the queue
			else {
				pods_jobs_queue_Processor.process_next();
			}
		}
		else if ( pods_jobs_queue_config.is_admin ) {
			pods_jobs_queue_Processor.progress_indicator.progress.progressbar( { value : 200 } );

			var status_text = pods_jobs_queue_config.status_complete;

			if ( 1 < pods_jobs_queue_Processor.total ) {
				status_text = pods_jobs_queue_config.status_complete_plural;
			}

			pods_jobs_queue_Processor.progress_indicator.status.text( status_text );
		}

	},

	/**
	 * Load Pods Jobs Queue
	 *
	 * @param cache_key
	 * @param cache_mode
	 * @param nonce
	 */
	load_view : function( cache_key, cache_mode, nonce ) {

		// Get view container(s)
		var $view_container = jQuery( 'div.pods-jobs-queue-loader-' + nonce );

		// If view container found (and not already processed by another view in the queue)
		if ( $view_container.length || pods_jobs_queue_config.is_admin ) {
			var pods_jobs_queue_url = document.location.href;

			pods_jobs_queue_url = pods_jobs_queue_url.replace( '?pods_jobs_queue_refresh=1', '' );
			pods_jobs_queue_url = pods_jobs_queue_url.replace( '&pods_jobs_queue_refresh=1', '' );

			// Get current progress based on 0-100%
			var progress_value = ( ( pods_jobs_queue_Processor.total - pods_jobs_queue_Processor.queue.length ) * 100 ) / pods_jobs_queue_Processor.total;

			if ( pods_jobs_queue_config.is_admin ) {
				ajax_url = pods_jobs_queue_config.ajax_url + '?action=pods_jobs_queue_regenerate';

				// Only do special calculation for first run to indicate progress is happening
				if ( 0 === pods_jobs_queue_Processor.progress_indicator.progress.progressbar( 'value' ) ) {
					// Set value to x*2 because progress is 0-100% format, but progressbar is tracking 0-200 for pre/loaded indication
					// We use (x*2)-1 because we want to show the indication of it getting ready to load
					pods_jobs_queue_Processor.progress_indicator.progress.progressbar( { value : ( Math.round( progress_value * 2 ) - 1 ) } );
				}
			}

			jQuery.ajax( {
				type : 'POST',
				dataType : 'html',
				url : pods_jobs_queue_url,
				cache : false,
				data : {
					pods_jobs_queue_job_id : cache_key,
					pods_jobs_queue_mode : cache_mode,
					pods_jobs_queue_nonce : nonce
				},
				success : function ( content ) {

					// Update progress indicator
					if ( pods_jobs_queue_config.is_admin ) {
						pods_jobs_queue_Processor.progress_indicator.progress.progressbar( { value : Math.round( progress_value * 2 ) } );
					}
					// Replace temporary container with the real content
					else {
						$view_container.replaceWith( content );
					}

					// Trigger events for advanced functionality
					jQuery( document ).trigger( 'pods-jobs-queue-loaded', [ cache_key, cache_mode, content ] );
					jQuery( document ).trigger( 'pods-jobs-queue-loaded-' + cache_key + '-' + cache_mode, [ cache_key, cache_mode, content ] );

					// Process next view from the queue
					pods_jobs_queue_Processor.process_next();

				},
				error : function ( jqXHR, textStatus, errorThrown ) {

					// Log error if console is available
					if ( window.console ) {
						console.log( 'Pods Jobs Queue Error: ' + errorThrown + ' (' + cache_key + ')' );
					}

					// Hide the container, subsequent containers can still be processed if successful
					$view_container.hide();

					// Process next view from the queue
					pods_jobs_queue_Processor.process_next();

				}
			} );
		}

	}

};

jQuery( function() {

	// Check if defined and has values
	if ( 'undefined' != typeof pods_jobs_queue && {} != pods_jobs_queue ) {
		// Send to queue
		pods_jobs_queue_Processor.queue = pods_jobs_queue_Processor.queue.concat( pods_jobs_queue );
		pods_jobs_queue_Processor.total = pods_jobs_queue_Processor.queue.length;

		// Start processing
		pods_jobs_queue_Processor.process_next();
	}

} );