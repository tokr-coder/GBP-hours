<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://tomaskrejcar.com
 * @since             1.0.0
 * @package           Gbp_Hours
 *
 * @wordpress-plugin
 * Plugin Name:       Business Profile Hours Sync
 * Plugin URI:        https://twoviewsstudio.com
 * Description:       Plugin to retrieve business hours using Google Places API and display them on your website. 
 * Version:           1.0.0
 * Author:            TV Plugins
 * Author URI:        https://tomaskrejcar.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gbp-hours
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GBP_HOURS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gbp-hours-activator.php
 */
function activate_gbp_hours() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gbp-hours-activator.php';
	Gbp_Hours_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gbp-hours-deactivator.php
 */
function deactivate_gbp_hours() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gbp-hours-deactivator.php';
	Gbp_Hours_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gbp_hours' );
register_deactivation_hook( __FILE__, 'deactivate_gbp_hours' );

/**
 * The core plugin class 
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gbp-hours.php';

/**
 * Begins execution of the plugin.
 */
function run_gbp_hours() {

	$plugin = new BusinessProfileHours();
	//$plugin->run();

}
run_gbp_hours();
