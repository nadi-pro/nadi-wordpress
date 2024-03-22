<?php

class Composer
{
    public static function install()
    {
        exec('cd '.NADI_DIR.' && php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"');
        exec('cd '.NADI_DIR.' && php composer-setup.php');
        exec('cd '.NADI_DIR.' && php -r "unlink(\'composer-setup.php\');"');
    }

    public static function installDependencies()
    {
        exec('cd '.NADI_DIR.' && composer install');
    }

    public static function isInstalled(): bool
    {
        return file_exists(
            NADI_DIR.'/vendor/autoload.php'
        );
    }

    public static function notice()
    {
        ?>
        <div class="error">
            <p><?php _e('Nadi requires Composer version 2.0 or higher. Please make sure Composer is installed and up-to-date, then run <code>composer install</code>.', 'nadi'); ?></p>
        </div>
        <?php
    }
}
