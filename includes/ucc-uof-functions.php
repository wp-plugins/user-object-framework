<?php


if ( !defined( 'ABSPATH' ) ) exit;


if ( !function_exists( 'ucc_uof_object_reference' ) ) {
function ucc_uof_object_reference() {
	$object_reference = apply_filters( 'ucc_uof_object_reference', array(
		10 => 'comment',
		20 => 'post',
		30 => 'user',
		100 => 'bp_activity' // bp_activity requires some special handling.
	) );
	return $object_reference;
} }


if ( !function_exists( 'ucc_uof_add_relationship' ) ) {
function ucc_uof_add_relationship( $user_id = 0, $user_ip = 0, $object_id, $object_ref ) {
	if ( !$object_id || !$object_ref )
		return false;

	if ( !( $user_id == 0 || $user_id = absint( $user_id ) ) )
		return false;

	if ( !( $user_ip == 0 || $user_ip = absint( $user_ip ) ) )
		return false;

	if ( !$object_id = absint( $object_id ) )
		return false;

	if ( !$object_ref = absint( $object_ref ) ) 
		return false;

	global $wpdb;

	$table = $wpdb->prefix . 'uof_user_object';

	// A logged in user_id trumps an anonymous IP address. 
	if ( $user_id > 0 )
		$relationship = $wpdb->get_var( $wpdb->prepare( "SELECT relationship_id FROM {$table} WHERE user_id = %d AND object_id = %d AND object_ref = %d", $user_id, $object_id, $object_ref ) );
	else
		$relationship = $wpdb->get_var( $wpdb->prepare( "SELECT relationship_id FROM {$table} WHERE user_ip = %d AND object_id = %d AND object_ref = %d", $user_ip, $object_id, $object_ref ) );

	// Relationship already exists; add fails.
	if ( $relationship ) {
		return $relationship;
	} else {
		$result = $wpdb->insert(
			$wpdb->prefix . 'uof_user_object',
			array(
				'user_id' => $user_id,
				'user_ip' => $user_ip,
				'object_id' => $object_id,
				'object_ref' => $object_ref
			) );
		if ( !$result )
			return false;
		else
			return $wpdb->insert_id;
	}
} }


if ( !function_exists( 'ucc_uof_delete_relationship' ) ) {
function ucc_uof_delete_relationship( $user_id = 0, $user_ip = 0, $object_id, $object_ref ) {
	if ( !$object_id || !$object_ref )
		return false;

	if ( !( $user_id == 0 || $user_id = absint( $user_id ) ) )
		return false;

	if ( !( $user_ip == 0 || $user_ip = absint( $user_ip ) ) )
		return false;
		
	if ( !$object_id = absint( $object_id ) )
		return false;
		
	if ( !$object_ref = absint( $object_ref ) )
		return false;

	global $wpdb;

	$table = $wpdb->prefix . 'uof_user_object';
	$relationship = ucc_uof_get_relationship( $user_id, $user_ip, $object_id, $object_ref );
	if ( $relationship ) {
		$meta = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE relationship_id = %d", $relationship ) );
		// Don't delete relationship if metas exist for it.
		if ( $meta > 0 ) {
			return false;
		} else {
			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM %s WHERE relationship_id = %d",
				$wpdb->prefix . 'uof_user_object', $relationship ) );
			if ( $result )
				return true;
			else
				return false;
		}
	} else {
		return false;
	}
} }


if ( !function_exists( 'ucc_uof_get_relationship' ) ) {
function ucc_uof_get_relationship( $user_id = 0, $user_ip = 0, $object_id, $object_ref ) {
	if ( !$object_id || !$object_ref )
		return false;

	if ( !( $user_id == 0 || $user_id = absint( $user_id ) ) )
		return false;

	if ( !( $user_ip == 0 || $user_ip = absint( $user_ip ) ) )
		return false;

	if ( !$object_id = absint( $object_id ) )
		return false;

	if ( !$object_ref = absint( $object_ref ) )
		return false;

	$key = "_ucc_uof_{$user_id}_{$user_ip}_{$object_id}_{$object_ref}";
	$relationship = wp_cache_get( $key, 'ucc_uof_relationship' );
	if ( $relationship === false ) {
		global $wpdb;

		$table = $wpdb->prefix . 'uof_user_object';
		if ( $user_id > 0 )
			$relationship = $wpdb->get_var( $wpdb->prepare( "SELECT relationship_id FROM {$table} WHERE user_id = %d AND object_id = %d AND object_ref = %d", $user_id, $object_id, $object_ref ) );
		else
			$relationship = $wpdb->get_var( $wpdb->prepare( "SELECT relationship_id FROM {$table} WHERE user_ip = %d AND object_id = %d AND object_ref = %d", $user_ip, $object_id, $object_ref ) );
		wp_cache_set( $key, $relationship, 'ucc_uof_relationship' );
	}
	return $relationship;
} }


// Helper functions.
if ( !function_exists( 'ucc_uof_get_user_id' ) ) {
function ucc_uof_get_user_id() {
	global $current_user;
	get_currentuserinfo();

	if ( $current_user->ID > 0 )
		return absint( $current_user->ID );
	else
		return 0;
} }


if ( !function_exists( 'ucc_uof_get_user_ip' ) ) {
function ucc_uof_get_user_ip() {
	global $current_user;
	get_currentuserinfo();

	if ( $current_user->ID > 0 )
		return 0;
	else
		return ip2long( $_SERVER['REMOTE_ADDR'] );
} }
