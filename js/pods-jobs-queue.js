/**
 * Pods Jobs Queue processor
 *
 * @type {{queue: Array, process_next: process_next, process_job: process_job}}
 */
const Pods_Jobs_Queue_Processor = {

	/**
	 * Store current queue of jobs to process
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
		status : null, progress : null, progress_label : null
	},

	/**
	 * Process the next job from the queue
	 */
	process_next : function () {
		if ( null === Pods_Jobs_Queue_Processor.progress_indicator.progress ) {
			Pods_Jobs_Queue_Processor.progress_indicator.status = jQuery( '#pods-jobs-queue-progress-status' );
			Pods_Jobs_Queue_Processor.progress_indicator.progress = jQuery( '#pods-jobs-queue-progress-indicator' );
			Pods_Jobs_Queue_Processor.progress_indicator.progress_label = jQuery( '#pods-jobs-queue-progress-label' );

			Pods_Jobs_Queue_Processor.progress_indicator.progress.show();

			const progressBarConfig = {
				value       : 0, max : 200, change : function () {
					const value = Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( 'value' );

					// Value / 2 because max is 200 and we want 0-100% format
					Pods_Jobs_Queue_Processor.progress_indicator.progress_label.text( (value / 2) + '%' );
				}, complete : function () {
					Pods_Jobs_Queue_Processor.progress_indicator.progress_label.text( '100%' );
				}
			};

			Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( progressBarConfig );
		}

		// Check if there are jobs in the queue
		if ( Pods_Jobs_Queue_Processor.queue.length ) {
			// Get the next job in line
			job = Pods_Jobs_Queue_Processor.queue.shift();

			if ( 'undefined' != typeof job.job_id && 'undefined' != typeof job.nonce ) {
				// Check for valid job data, then load the job
				Pods_Jobs_Queue_Processor.process_job( job.job_id, job.nonce );
			}
			else {
				// If invalid, process next job from the queue
				Pods_Jobs_Queue_Processor.process_next();
			}
		}
		else {
			Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( {value : 200} );

			let status_text = pods_jobs_queue_config.status_complete;

			if ( 1 < Pods_Jobs_Queue_Processor.total ) {
				status_text = pods_jobs_queue_config.status_complete_plural;
			}

			status_text += ' (' + Pods_Jobs_Queue_Processor.total + ' total)';

			Pods_Jobs_Queue_Processor.progress_indicator.status.text( status_text );
		}
	},

	/**
	 * Load Pods Jobs Queue
	 *
	 * @param job_id
	 * @param nonce
	 */
	process_job : function ( job_id, nonce ) {
		const pods_jobs_queue_url = pods_jobs_queue_config.ajax_url + '?action=pods_jobs_queue_process_job';

		// Get current progress based on 0-100%
		const progress_value = ((Pods_Jobs_Queue_Processor.total - Pods_Jobs_Queue_Processor.queue.length) * 100) / Pods_Jobs_Queue_Processor.total;

		// Only do special calculation for first run to indicate progress is happening
		if ( 0 === Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( 'value' ) ) {
			// Set value to x*2 because progress is 0-100% format, but progressbar is tracking 0-200 for pre/loaded indication
			// We use (x*2)-1 because we want to show the indication of it getting ready to load
			Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( {value : (Math.round( progress_value * 2 ) - 1)} );
		}

		const ajaxConfig = {
			type       : 'POST', dataType : 'html', url : pods_jobs_queue_url, cache : false, data : {
				pods_jobs_queue_job_id : job_id, pods_jobs_queue_nonce : nonce
			}, success : function () {

				// Update progress indicator
				Pods_Jobs_Queue_Processor.progress_indicator.progress.progressbar( {value : Math.round( progress_value * 2 )} );

				// Trigger events for advanced functionality
				jQuery( document ).trigger( 'pods-jobs-queue-loaded', job_id );

				// Process next job from the queue
				Pods_Jobs_Queue_Processor.process_next();

			}, error   : function ( jqXHR, textStatus, errorThrown ) {

				// Log error if console is available
				if ( window.console ) {
					console.log( 'Pods Jobs Queue Error: ' + errorThrown + ' (' + job_id + ')' );
				}

				// Process next job from the queue
				Pods_Jobs_Queue_Processor.process_next();

			}
		};

		jQuery.ajax( ajaxConfig );
	}
};

jQuery( function () {
	// Check if defined and has values
	if ( 'undefined' !== typeof pods_jobs_queue && {} != pods_jobs_queue ) {
		// Send to queue
		Pods_Jobs_Queue_Processor.queue = Pods_Jobs_Queue_Processor.queue.concat( pods_jobs_queue );
		Pods_Jobs_Queue_Processor.total = Pods_Jobs_Queue_Processor.queue.length;

		// Start processing
		Pods_Jobs_Queue_Processor.process_next();
	}
} );
