<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://nadi.pro
 * @since             1.0.0
 * @package           Nadi
 *
 * @wordpress-plugin
 * Plugin Name:       Nadi
 * Plugin URI:        https://github.com/nadi-pro/nadi-wordpress
 * Description:       Monitoring applications made simple for developers. Monitor applications crashes with Nadi, your Crash Care Companion
 * Version:           1.0.0
 * Author:            Nadi Pro
 * Author URI:        https://nadi.pro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nadi
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'NADI_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-nadi-activator.php
 */
function activate_nadi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nadi-activator.php';
	Nadi_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-nadi-deactivator.php
 */
function deactivate_nadi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-nadi-deactivator.php';
	Nadi_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_nadi' );
register_deactivation_hook( __FILE__, 'deactivate_nadi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-nadi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_nadi() {

	$plugin = new Nadi();
	$plugin->run();

}
run_nadi();
