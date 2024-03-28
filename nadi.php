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

use Nadi\WordPress\Exceptions\WordPressException;
use Nadi\WordPress\Handler\HandleExceptionEvent;
use Nadi\WordPress\Nadi;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    exit;
}

// Don't load during plugin updates to prevent function signature changes causing issues between versions.
if (is_admin()) {
    if (isset($_GET['action']) && $_GET['action'] === 'upgrade-plugin') {
        return;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update-plugin') {
        return;
    }
}

define('NADI_VERSION', '1.0.0');
define('NADI_DIR', plugin_dir_path(__FILE__));
define('NADI_START', microtime(true));

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

set_exception_handler([HandleExceptionEvent::class, 'make']);

$nadi = (new Nadi())
    ->setRequestMethod($_SERVER['REQUEST_METHOD'])
    ->setPostData($_POST)
    ->setup()
    ->run();

// $error = new WP_Error('nadi_exception_test', 'An error occurred in my code.', ['file' => __FILE__, 'line' => __LINE__]);

// $error_data = $error->get_error_data();
// $message = $error->get_error_message();
// $code = (int) $error->get_error_code();
// $trace = debug_backtrace();
// $file = $trace[0]['file'];
// $line = $trace[0]['line'];
// $class = get_class($error);

// throw new WordPressException($trace, $message, $file, $line, $code, $error_data, $class);
