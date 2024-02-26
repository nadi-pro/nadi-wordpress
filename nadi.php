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

use Nadi\WordPress\Nadi;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    exit;
}

define('NADI_VERSION', '1.0.0');

if (version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', 'nadi_php_version_notice');
    return;
}

if (! is_composer_installed()) {
    add_action('admin_notices', 'nadi_missing_composer_notice');

    return;
}

// Include Composer autoloader
require plugin_dir_path(__FILE__).'vendor/autoload.php';

/**
 * Display admin notice if minimum PHP version requirement is not met.
 */
function nadi_php_version_notice() {
    ?>
    <div class="error">
        <p><?php _e('Nadi requires PHP version 8.2 or higher. Please upgrade PHP to run this plugin.', 'nadi'); ?></p>
    </div>
    <?php
}

/**
 * Check if Composer is installed.
 *
 * @return bool Whether Composer is installed or not.
 */
function is_composer_installed()
{
    return file_exists(plugin_dir_path(__FILE__).'vendor/autoload.php');
}

/**
 * Display admin notice if Composer is missing or invalid.
 */
function nadi_missing_composer_notice()
{
    ?>
    <div class="error">
        <p><?php _e('Nadi requires Composer version 2.0 or higher. Please make sure Composer is installed and up-to-date, then run <code>composer install</code>.', 'nadi'); ?></p>
    </div>
    <?php
}


/**
 * Class to check Composer installation and version.
 */
class ComposerChecker {
    /**
     * Check if Composer is installed.
     *
     * @return bool Whether Composer is installed or not.
     */
    public function isInstalled() {
        return file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
    }
}

/**
 * Class to install Composer and run 'composer install'.
 */
class ComposerInstaller {
    /**
     * Install Composer.
     */
    public function installComposer() {
        // Install Composer
        exec('cd ' . plugin_dir_path(__FILE__) . ' && php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"');
        exec('cd ' . plugin_dir_path(__FILE__) . ' && php composer-setup.php');
        exec('cd ' . plugin_dir_path(__FILE__) . ' && php -r "unlink(\'composer-setup.php\');"');
    }

    /**
     * Run 'composer install'.
     */
    public function runComposerInstall() {
        // Run 'composer install'
        exec('cd ' . plugin_dir_path(__FILE__) . ' && composer install');
    }
}

/** Activation */

function activate_nadi()
{
	$composerChecker = new ComposerChecker();
    if (!$composerChecker->isInstalled()) {
        // Install Composer
        $composerInstaller = new ComposerInstaller();
        $composerInstaller->installComposer();

        // Run 'composer install'
        $composerInstaller->runComposerInstall();
    }
	
    Nadi::activate();
}

function deactivate_nadi()
{
    Nadi::deactivate();
}

register_activation_hook(__FILE__, 'activate_nadi');
register_deactivation_hook(__FILE__, 'deactivate_nadi');

$plugin = (new Nadi());
$plugin->run();
