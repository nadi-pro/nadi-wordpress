<?php

class PHP
{
    public static function isValid(): bool
    {
        return version_compare(phpversion(), '8.0.0', '>=');
    }

    public static function notice()
    {
        ?>
    <div class="error">
        <p><?php _e('Nadi requires PHP version 8.2 or higher. Please upgrade PHP to run this plugin.', 'nadi'); ?></p>
    </div>
    <?php
    }
}
