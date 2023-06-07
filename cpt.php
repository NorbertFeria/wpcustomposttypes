<?php

/**
 *
 * @link              https://github.com/norbertferia
 * @since             1.0.0
 * @package           Cpt
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Post Types
 * Plugin URI:        https://github.com/NorbertFeria/wpcustomposttypes
 * Description:       The Custom Post Types plugin enables you to create dynamic custom post types from the dashboard.
 * Version:           1.0.0
 * Author:            Norbert Feria
 * Author URI:        https://github.com/norbertferia
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cpt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'CPT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-cpt-activator.php
 */
function activate_cpt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cpt-activator.php';
	Cpt_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-cpt-deactivator.php
 */
function deactivate_cpt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cpt-deactivator.php';
	Cpt_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cpt' );
register_deactivation_hook( __FILE__, 'deactivate_cpt' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cpt.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_cpt() {

	$plugin = new Cpt();
	$plugin->run();

}
run_cpt();
