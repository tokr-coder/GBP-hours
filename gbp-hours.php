<?php
/**
 * @link              https://plugins.knowsync.dev
 * @since             1.0.0
 * @package           gbp_Hours
 *
 * @wordpress-plugin
 * Plugin Name:       Business Profile Hours Sync
 * Plugin URI:        https://plugins.knowsync.dev/gbp-hours
 * Description:       Plugin to retrieve business hours using Google Places API and display them on your website. 
 * Version:           1.0.0
 * Author:            KnowSync Plugins
 * Author URI:        https://plugins.knowsync.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       business-profile-hours-sync
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GBP_HOURS_VERSION', '1.0.0' );

function business_hours_add_settings_link($links) {
    // Create the settings link 
    $settings_link = '<a href="' . admin_url('options-general.php?page=business-profile-hours') . '">' . __('Settings', 'business-profile-hours-sync') . '</a>';
    
    // Add the settings link to the beginning of the array
    array_unshift($links, $settings_link);
    
    return $links;
}

// Add the filter - make sure to use your actual plugin basename
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'business_hours_add_settings_link');

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

// Add widget support
require plugin_dir_path( __FILE__ ) . 'includes/class-gbp-hours-widget.php';

/**
 * Begins execution of the plugin.
 */
function run_gbp_hours() {

	$plugin = new BusinessProfileHours();

}
run_gbp_hours();
