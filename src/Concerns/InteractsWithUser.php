<?php

namespace Nadi\WordPress\Concerns;

trait InteractsWithUser
{
    /**
     * Retrieve the current user info from WordPress.
     */
    protected function getUser(): ?array
    {
        require ABSPATH.'wp-includes/pluggable.php';

        $user = \wp_get_current_user();

        if (! $user || ($user instanceof WP_User && ! $user->exists())) {
            return [
                'id' => 0,
                'name' => 'guest',
            ];
        }

        return [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
        ];
    }
}
