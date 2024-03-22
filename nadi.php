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
define('NADI_DIR', plugin_dir_path(__FILE__));

require_once NADI_DIR.'/classes/Composer.php';
require_once NADI_DIR.'/classes/PHP.php';

if (! PHP::isValid()) {
    add_action('admin_notices', 'PHP::notice');

    return;
}

if (! Composer::isInstalled()) {
    add_action('admin_notices', 'Composer::notice');

    return;
}

require NADI_DIR.'/vendor/autoload.php';

/** Activation */
function activate_nadi()
{
    if (! Composer::isInstalled()) {
        Composer::install();
        Composer::installDependencies();
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $api_key = sanitize_text_field($_POST['nadi_api_key']);
    $application_key = sanitize_text_field($_POST['nadi_application_key']);

    $plugin->updateConfig('apiKey', $api_key);
    $plugin->updateConfig('token', $application_key);
}
