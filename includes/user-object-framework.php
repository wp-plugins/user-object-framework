<?php


if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'UCC_User_Object_Framework' ) ) {
class UCC_User_Object_Framework {
	public function __construct() {
		// Deal with BuddyPress table bp_activity_meta inconsistencies with metadata table format.
		add_filter( 'add_bp_activity_metadata', array( &$this, 'add_bp_activity_metadata' ), 10, 5 );
		add_filter( 'update_bp_activity_metadata', array( &$this, 'update_bp_activity_metadata' ), 10, 5 );
		add_filter( 'delete_bp_activity_metadata', array( &$this, 'delete_bp_activity_metadata' ), 10, 5 );
		add_filter( 'get_bp_activity_metadata', array( &$this, 'get_bp_activity_metadata' ), 10, 4 );
	}

	public function add_bp_activity_metadata( $check, $object_id, $meta_key, $meta_value, $unique ) {
		if ( !$meta_key )
			return false;
	
		if ( !$object_id = absint( $object_id ) )
			return false;

		$meta_type = 'bp_activity';
		if ( !$table = _get_meta_table( $meta_type ) )
			return false;

		global $wpdb;

		$column = esc_sql( 'activity_id' );

		// expected_slashed( $meta_key )
		$meta_key = stripslashes( $meta_key );
		$meta_value = stripslashes_deep( $meta_value );
		$meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type );

		if ( $unique && $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
			$meta_key, $object_id ) ) )
			return false;

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );

		do_action( "add_{$meta_type}_meta", $object_id, $meta_key, $_meta_value );

		$result = $wpdb->insert( $table, array(
			$column => $object_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		) );

		if ( !$result )
			return false;

		$mid = (int) $wpdb->insert_id;

		wp_cache_delete( $object_id, $meta_type . '_meta' );

		do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value );

		return $mid;
	}

	public function update_bp_activity_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( !$meta_key )
			return false;

		if ( !$object_id = absint( $object_id ) )
			return false;

		$meta_type = 'bp_activity';
		if ( !$table = _get_meta_table( $meta_type ) )
			return false;

		global $wpdb;
		$column = esc_sql( 'activity_id');
		$id_column = 'id';

		// expected_slashed( $meta_key )
		$meta_key = stripslashes( $meta_key );
		$passed_value = $meta_value;
		$meta_value = stripslashes_deep( $meta_value );
		$meta_value = sanitize_meta( $meta_key, $meta_value, $meta_type );

		if ( !$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s AND $column = %d", $meta_key, $object_id ) ) )
			return add_metadata( $meta_type, $object_id, $meta_key, $passed_value );

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = get_metadata( $meta_type, $object_id, $meta_key );
			if ( count( $old_value ) == 1 ) {
				if ( $old_value[0] === $meta_value )
					return false;
			}
		}

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );
	
		$data  = compact( 'meta_value' );
		$where = array( $column => $object_id, 'meta_key' => $meta_key );
	
		if ( !empty( $prev_value ) ) {
			$prev_value = maybe_serialize( $prev_value );
			$where['meta_value'] = $prev_value;
		}

		do_action( "update_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		$wpdb->update( $table, $data, $where );

		wp_cache_delete( $object_id, $meta_type . '_meta' );

		do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );

		return true;
	}

	public function delete_bp_activity_metadata( $check, $object_id, $meta_key, $meta_value, $delete_all ) {
		if ( !$meta_key )
			return false;
	
		if ( ( !$object_id = absint( $object_id ) ) && !$delete_all )
			return false;
	
		$meta_type = 'bp_activity';
		if ( !$table = _get_meta_table( $meta_type ) )
			return false;
	
		global $wpdb;
	
		$type_column = esc_sql( 'activity_id');
		$id_column = 'id';
		// expected_slashed( $meta_key )
		$meta_key = stripslashes( $meta_key );
		$meta_value = stripslashes_deep( $meta_value );
	
		$_meta_value = $meta_value;
		$meta_value = maybe_serialize( $meta_value );
	
		$query = $wpdb->prepare( "SELECT $id_column FROM $table WHERE meta_key = %s", $meta_key );
	
		if ( !$delete_all )
			$query .= $wpdb->prepare(" AND $type_column = %d", $object_id );
	
		if ( $meta_value )
			$query .= $wpdb->prepare(" AND meta_value = %s", $meta_value );
	
		$meta_ids = $wpdb->get_col( $query );
		if ( !count( $meta_ids ) )
			return false;
	
		if ( $delete_all )
			$object_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $type_column FROM $table WHERE meta_key = %s", $meta_key ) );
	
		do_action( "delete_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );
	
		$query = "DELETE FROM $table WHERE $id_column IN( " . implode( ',', $meta_ids ) . " )";
	
		$count = $wpdb->query( $query );
	
		if ( !$count )
			return false;
	
		if ( $delete_all ) {
			foreach ( (array) $object_ids as $o_id ) {
				wp_cache_delete( $o_id, $meta_type . '_meta' );
			}
		} else {
			wp_cache_delete( $object_id, $meta_type . '_meta' );
		}
	
		do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );
	
		return true;
	}

	public function get_bp_activity_metadata( $check, $object_id, $meta_key, $single ) {
		if ( !$object_id = absint( $object_id ) )
			return false;
	
		$meta_type = 'bp_activity';
		$meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

		if ( !$meta_cache ) {
			$meta_cache = $this->update_bp_activity_meta_cache( $meta_type, array( $object_id ) );
			$meta_cache = $meta_cache[$object_id];
		}

		if ( !$meta_key )
			return $meta_cache;

		if ( isset( $meta_cache[$meta_key] ) ) {
			if ( $single )
				return maybe_unserialize( $meta_cache[$meta_key][0] );
			else
				return array_map('maybe_unserialize', $meta_cache[$meta_key]);
		}
	
		if ( $single )
			return '';
		else
			return array();
	}

	public function update_bp_activity_meta_cache( $meta_type, $object_ids ) {
		if ( empty( $object_ids ) )
			return false;
	
		if ( !$table = _get_meta_table( $meta_type ) )
			return false;
	
		$column = esc_sql( 'activity_id' );
	
		global $wpdb;
	
		if ( !is_array( $object_ids ) ) {
			$object_ids = preg_replace( '|[^0-9,]|', '', $object_ids );
			$object_ids = explode( ',', $object_ids );
		}
	
		$object_ids = array_map( 'intval', $object_ids );
	
		$cache_key = $meta_type . '_meta';
		$ids = array();
		$cache = array();
		foreach ( $object_ids as $id ) {
			$cached_object = wp_cache_get( $id, $cache_key );
			if ( false === $cached_object )
				$ids[] = $id;
			else
				$cache[$id] = $cached_object;
		}
	
		if ( empty( $ids ) )
			return $cache;
	
		// Get meta info
		$id_list = join( ',', $ids );
		$meta_list = $wpdb->get_results( $wpdb->prepare( "SELECT $column, meta_key, meta_value FROM $table WHERE $column IN ($id_list)",
			$meta_type ), ARRAY_A );
	
		if ( !empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = intval( $metarow[$column] );
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];
	
				// Force subkeys to be array type:
				if ( !isset( $cache[$mpid] ) || !is_array( $cache[$mpid] ) )
					$cache[$mpid] = array();
				if ( !isset( $cache[$mpid][$mkey] ) || !is_array( $cache[$mpid][$mkey] ) )
					$cache[$mpid][$mkey] = array();
	
				// Add a value to the current pid/key:
				$cache[$mpid][$mkey][] = $mval;
			}
		}
	
		foreach ( $ids as $id ) {
			if ( !isset( $cache[$id] ) )
				$cache[$id] = array();
			wp_cache_add( $id, $cache[$id], $cache_key );
		}
	
		return $cache;
	}
} }
