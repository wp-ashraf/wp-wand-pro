<?php
/**
 * Plugin Name: Action Scheduler High Volume
 * Plugin URI: https://github.com/prospress/action-scheduler-high-volume
 * Description: Increase Action Scheduler batch size, concurrency and timeout period to process large queues of actions more quickly on servers with more server resources.
 * Author: Prospress Inc.
 * Author URI: http://prospress.com/
 * Version: 1.1.0
 *
 * Copyright 2018 Prospress, Inc.  (email : freedoms@prospress.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author	Brent Shepherd
 * @since	1.0
 */


/** 
 * Action scheduler claims a batch of actions to process in each request. It keeps the batch
 * fairly small (by default, 25) in order to prevent errors, like memory exhaustion.
 *
 * This method increases it so that more actions are processed in each queue, which speeds up the
 * overall queue processing time due to latency in requests and the minimum 1 minute between each
 * queue being processed.
 *
 * For more details, see: https://actionscheduler.org/perf/#increasing-batch-size
 */
function wpwand_proincrease_queue_batch_size( $batch_size ) {
	return $batch_size * 4;
}
add_filter( 'action_scheduler_queue_runner_batch_size', 'wpwand_proincrease_queue_batch_size' );

/** 
 * Action scheduler processes queues of actions in parallel to speed up the processing of large numbers
 * If each queue takes a long time, this will result in multiple PHP processes being used to process actions,
 * which can prevent PHP processes being available to serve requests from visitors. This is why it defaults to
 * only 5. However, on high volume sites, this can be increased to speed up the processing time for actions.
 *
 * This method hextuples the default so that more queues can be processed concurrently. Use with caution as doing
 * this can take down your site completely depending on your PHP configuration.
 *
 * For more details, see: https://actionscheduler.org/perf/#increasing-concurrent-batches
 */
function wpwand_proincrease_concurrent_batches( $concurrent_batches ) {
	return $concurrent_batches * 2;
}
add_filter( 'action_scheduler_queue_runner_concurrent_batches', 'wpwand_proincrease_concurrent_batches' );

/**
 * Action scheduler reset actions claimed for more than 5 minutes. Because we're increasing the batch size, we
 * also want to increase the amount of time given to queues before reseting claimed actions.
 */
function wpwand_proincrease_timeout( $timeout ) {
	return $timeout * 3;
}
add_filter( 'action_scheduler_timeout_period', 'wpwand_proincrease_timeout' );
add_filter( 'action_scheduler_failure_period', 'wpwand_proincrease_timeout' );

/**
 * Action scheduler initiates one queue runner every time the 'action_scheduler_run_queue' action is triggered.
 *
 * Because this action is only triggered at most once every minute, that means it would take 30 minutes to spin
 * up 30 queues. To handle high volume sites with powerful servers, we want to initiate additional queue runners
 * whenever the 'action_scheduler_run_queue' is run, so we'll kick off secure requests to our server to do that.
 */
function wpwand_prorequest_additional_runners() {

	// allow self-signed SSL certificates
	add_filter( 'https_local_ssl_verify', '__return_false', 100 );

	for ( $i = 0; $i < 5; $i++ ) {
		$response = wp_remote_post( admin_url( 'admin-ajax.php' ), array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => false,
			'headers'     => array(),
			'body'        => array(
				'action'     => 'wpwand_procreate_additional_runners',
				'instance'   => $i,
				'wpwand_prononce' => wp_create_nonce( 'wpwand_proadditional_runner_' . $i ),
			),
			'cookies'     => array(),
		) );
	}
}
add_action( 'action_scheduler_run_queue', 'wpwand_prorequest_additional_runners', 0 );

/**
 * Handle requests initiated by wpwand_prorequest_additional_runners() and start a queue runner if the request is valid.
 */
function wpwand_procreate_additional_runners() {

	if ( isset( $_POST['wpwand_prononce'] ) && isset( $_POST['instance'] ) && wp_verify_nonce( $_POST['wpwand_prononce'], 'wpwand_proadditional_runner_' . $_POST['instance'] ) ) {
		ActionScheduler_QueueRunner::instance()->run();
	}

	wp_die();
}
add_action( 'wp_ajax_nopriv_wpwand_procreate_additional_runners', 'wpwand_procreate_additional_runners', 0 );

/**
 * Action Scheduler provides a default maximum of 30 seconds in which to process actions. Increase this to 120
 * seconds for hosts like Pantheon which support such a long time limit, or if you know your PHP and Apache, Nginx
 * or other web server configs support a longer time limit.
 *
 * Note, WP Engine only supports a maximum of 60 seconds - if using WP Engine, this will need to be decreased to 60.
 */
function wpwand_proincrease_time_limit() {
	return 60;
}
add_filter( 'action_scheduler_queue_runner_time_limit', 'wpwand_proincrease_time_limit' );