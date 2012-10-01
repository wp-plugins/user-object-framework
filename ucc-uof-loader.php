<?php
/*
Plugin Name: User Object Framework 
Description: Provide a framework for assignment of user-object relationship metadata. 
Version: 0.1
License: GPL
Author: Jennifer M. Dodd
Author URI: http://uncommoncontent.com/
Text Domain: user-object-framework
Domain Path: /languages/

================================================================================

Copyright 2012 Jennifer M. Dodd

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.
	
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'UCC_User_Object_Framework_Loader' ) ) {
class UCC_User_Object_Framework_Loader {
	public static $instance;
	public static $version;
	public static $plugin_dir;
	public static $plugin_url;
	
	public function __construct() {
		self::$instance = $this;
		$this->version = '20120930';
		$this->plugin_dir   = plugin_dir_path( __FILE__ );
		$this->plugin_url   = plugins_url( __FILE__ );

		// Database creation and maintenance.
		register_activation_hook( __FILE__, array( &$this, 'install' ) );
		add_action( 'plugins_loaded', array( &$this, 'update_check' ), 9 );

		// Load class and function definitions.
		add_action( 'plugins_loaded', array( &$this, 'init' ), 10 );
	}

	public function install() {
		global $wpdb;

		$installed_version = get_option( '_ucc_uof_db_version' );
		if ( $installed_version != $this->version ) {
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			if ( !empty( $wpdb->charset ) )
				$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			else
				$charset_collate = "";

			if ( !$wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '{$wpdb->prefix}uof_user_object'" ) ) ) {
				$sql = "CREATE TABLE {$wpdb->prefix}uof_user_object (
						relationship_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						user_id bigint(20) NOT NULL DEFAULT 0,
						user_ip bigint(20) NOT NULL DEFAULT 0,
						object_id bigint(20) NOT NULL,
						object_ref bigint(20) NOT NULL,
					KEY user_id (user_id),
					KEY user_ip (user_ip),
					KEY object (object_id, object_ref),
					UNIQUE KEY relationship (user_id, user_ip, object_id, object_ref)
				) {$charset_collate};";
				dbDelta( $sql );
			}

			if ( !$wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE '{$wpdb->prefix}uof_user_objectmeta'" ) ) ) {
				$sql = "CREATE TABLE {$wpdb->prefix}uof_user_objectmeta (
						meta_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						uof_user_object_id bigint(20) NOT NULL,
						meta_key varchar(255) NOT NULL, 
						meta_value longtext,
						meta_date timestamp ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					KEY relationship (uof_user_object_id),
					KEY meta_key (meta_key)
				) {$charset_collate};";
				dbDelta( $sql );
			}

			update_option( '_ucc_uof_db_version', $this->version );
		}
	}

	public function update_check() {
		if ( get_option( '_ucc_uof_db_version' ) != $this->version ) {
			$this->install();
		}
	}

	public function init() {
		if ( get_option( '_ucc_uof_db_version' ) == $this->version ) { 
			// Add tables to $wpdb for metadata functions.
			global $wpdb;
			$wpdb->uof_user_object = $wpdb->prefix . 'uof_user_object';
			$wpdb->uof_user_objectmeta = $wpdb->prefix . 'uof_user_objectmeta';
			$wpdb->bp_activity = $wpdb->prefix . 'bp_activity';
			$wpdb->bp_activitymeta = $wpdb->prefix . 'bp_activity_meta';

			$this->includes();

			// Backwards compat.
			global $ucc_uof_object_ref;
			$ucc_uof_object_ref = ucc_uof_object_reference();

			new UCC_User_Object_Framework;
		} else {
			// Failed to find tables.
		}
	}

	public function includes() {
		require( $this->plugin_dir . 'includes/user-object-framework.php' );
		require( $this->plugin_dir . 'includes/ucc-uof-functions.php' );
	}
} }


new UCC_User_Object_Framework_Loader;
