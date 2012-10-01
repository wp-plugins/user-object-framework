=== User Object Framework ===
Contributors: jmdodd
Tags: user, object, relationship, framework, metadata
Requires at least: WordPress 3.4
Tested up to: 3.4.2
Stable tag: 0.1

Provide a framework for assignment of user-object relationship metadata.

== Description ==

This plugin creates a user-object relationship table and a user-object relationship metadata
table. Relationships between a user (logged-in or IP) are created and added to the relationship
table; metadata (ex. votes, flags, and ratings) is attached to that particular relationship
via the metadata table. This is a framework; it is intended for use in voting, flagging,
and similar user applications where a user-affiliated metadata is attached to an object.
Supported object tables include wp_posts, wp_comments, wp_users, and wp_bp_activity.

Functions are provided to add/get/delete relationships; metadata is added via the native WordPress
metadata-handling functions.

== Installation ==

1. Upload the directory `user-object-framework` and its contents to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 = 
* Initial release.

== Other Notes ==

Functions provided by this framework include:

* `ucc_uof_object_reference()` returns an array of object-integer assignments. Use this to look up the 
appropriate value for $object_ref based on the object table (wp_posts, wp_comments, wp_users, 
wp_bp_activity are currently supported) to which you are attaching a user relationship.
* `ucc_uof_add_relationship( $user_id = 0, $user_ip = 0, $object_id, $object_ref )` adds a relationship
to the relationship table if none exists and returns the relationship id for that user/object pairing.
* `ucc_uof_delete_relationship( $user_id = 0, $user_ip = 0, $object_id, $object_ref )` will delete a 
relationship if there are no metadata entries left for that relationship.
* `ucc_uof_get_relationship()` returns the relationship id for that user/object pairing.
* `ucc_uof_get_user_id()` returns the current user id or 0 if not logged in.
* `ucc_uof_get_user_ip()` returns 0 if the current user is logged in, or an ip2long() if anonymous.

Example code:

	// Create or get the user-object relationship.
	$relationship = ucc_uof_get_relationship( $user_id, $user_ip, $object_id, $object_ref );
	if ( empty( $relationship ) )
		$relationship = ucc_uof_add_relationship( $user_id, $user_ip, $object_id, $object_ref );

	// Add user_object_meta.
	if ( $mode == 'delete' )
		delete_metadata( 'uof_user_object', $relationship, '_your_meta_key' );
	else
		update_metadata( 'uof_user_object', $relationship, '_your_meta_key', 'your meta key value' );


