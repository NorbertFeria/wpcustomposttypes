<?php

/**
 * Fired during plugin activation
 *
 * @link       https://mandare.net
 * @since      1.0.0
 *
 * @package    Cpt
 * @subpackage Cpt/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Cpt
 * @subpackage Cpt/includes
 * @author     Norbert Feria <norbert.feria@gmail.com>
 */
class Cpt_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if(!current_user_can('activate_plugins' )) return;
		global $wpdb;
		$table_name = $wpdb->prefix . 'custom_posttypes';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {		
			$sql = "CREATE TABLE `".$table_name."` (
			  `cpt_ID` bigint(10) NOT NULL AUTO_INCREMENT,
			  `cpt_slug` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_name` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_single` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `cpt_plural` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `urlprefixslug` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `use_custom_categories` int(1) NOT NULL DEFAULT '0',
			  `taxonomy_label` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `taxonomy_urlprefix` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `ispublic` int(1) NOT NULL DEFAULT '1',
			  `excludefromsearch` int(1) NOT NULL DEFAULT '1',
			  `taxonomies` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `isshowui` int(1) NOT NULL DEFAULT '1',
			  `showinnavmenus` int(1) NOT NULL DEFAULT '1',
			  `capabilitytype` varchar(40) CHARACTER SET utf8 NOT NULL,
			  `hierarchical` int(1) NOT NULL DEFAULT '0',
			  `menuposition` int(3) NOT NULL DEFAULT '5',
			  `edit_link` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `hasarchive` int(1) NOT NULL DEFAULT '1',
			  `withfront` int(1) NOT NULL DEFAULT '1',
			  `builtin` int(1) NOT NULL DEFAULT '0',
			  `queryvar` int(1) NOT NULL DEFAULT '1',
			  `supports` varchar(250) CHARACTER SET utf8 NOT NULL,
			  `enabled` INT( 1 ) NOT NULL DEFAULT '0',
			  `posttype_description` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			  PRIMARY KEY (`cpt_ID`)
			)";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}#if no table
	}

}
