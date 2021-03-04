=== Pods Jobs Queue ===
Contributors: sc0ttkclark
Donate link: http://pods.io/friends-of-pods/
Tags: pods, queued jobs, cronjobs
Requires at least: 4.9
Tested up to: 5.7
Stable tag: 1.1
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

**Requires PHP 5.6+, WordPress 4.9+, and Pods Framework 2.7+**

Queue callbacks to be ran with arguments, unlike wp_cron which is scheduled jobs, these are queued and run concurrently as needed.

== Usage ==

You can queue jobs to be run by calling:

`pods_queue_job( $data );`

Set your `$data` to an array of information that the job will use when it runs:

`
$data = [
	/*
	 * The function to callback when running the job.
	 *
	 * Don't pass things like [ $this, 'some_method' ], use a string like: 'SomeClass::some_method' instead.
	 */
	'callback'  => 'your_function',

	/*
	 * Whatever data you want to pass to the job to run. This is optional but you can set it to an array of data to pass through.
	 *
	 * If this is provided as an array, the job will be run using: call_user_func_array( $callback, $args ).
	 *
	 * If this is empty, the job will be run using: call_user_func( $callback ).
	 *
	 * It will be serialized if necessary.
	 */
	'arguments' => [
		'argument1' => 'Some value',
	],

	/*
	 * The blog ID the job should run on (for multisite support). This defaults to the current blog ID or 0.
	 */
	'blog_id'   => 1234,

	/*
	 * Specify a group for your job so you can categorize it if you've got lots of different jobs doing different segments of things.
	 */
	'group'     => 'my-job-group',

	/*
	 * Override the current status, this is will default to 'queued' so you never really have to set this.
	 */
	'status'    => 'queued',
];
`

== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Contributors ==

Check out our GitHub for a list of contributors, or search our GitHub issues to see everyone involved in adding features, fixing bugs, or reporting issues/testing.

[github.com/pods-framework/pods-jobs-queue/graphs/contributors](https://github.com/pods-framework/pods-jobs-queue/graphs/contributors)


== Changelog ==

= 1.0 - June 20, 2014 =
* First official release!
* Found a bug? Have a great feature idea? Get on GitHub and tell us about it and we'll get right on it: [github.com/pods-framework/pods-jobs-queue/issues/new](https://github.com/pods-framework/pods-jobs-queue/issues/new)
