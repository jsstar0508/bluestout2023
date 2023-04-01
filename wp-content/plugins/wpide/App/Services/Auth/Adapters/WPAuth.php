<?php

namespace WPIDE\App\Services\Auth\Adapters;

use WPIDE\App\Services\Auth\AuthInterface;
use WPIDE\App\Services\Auth\User;
use WPIDE\App\Services\Auth\UsersCollection;
use WPIDE\App\Services\Service;

/**
 * @codeCoverageIgnore
 */
class WPAuth implements Service, AuthInterface
{

    protected $permissions = [];

    protected $private_repos = false;

    public function init(array $config = [])
    {

        $this->permissions = isset($config['permissions']) ? (array)$config['permissions'] : [];
        $this->private_repos = isset($config['private_repos']) ? (bool)$config['private_repos'] : false;
    }

    public function user(): ?User
    {
        $wpuser = wp_get_current_user();

        if ($wpuser->exists()) {
            return $this->transformUser($wpuser);
        }

        return $this->getGuest();
    }

    /**
     * @throws \Exception
     */
    public function transformUser($wpuser): User
    {
        $user = new User();
        $user->setUsername($wpuser->data->user_login);
        $user->setName($wpuser->data->display_name);
        $user->setEmail($wpuser->user_email);
        $user->setAvatar(get_avatar_url( $wpuser->user_email ));
        $user->setRole('user');
        $user->setPermissions($this->permissions);
        $user->setHomedir('/');

        // private repositories for each user?
        if ($this->private_repos) {
            $user->setHomedir('/'.$wpuser->data->user_login);
        }

        // ...but not for wp admins
        if (in_array('administrator', (array)$wpuser->roles)) {
            $user->setHomedir('/');
            $user->setRole('admin');
        }

        return $user;
    }

    public function authenticate($username, $password): bool
    {
        $creds = [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        ];

        $wpuser = wp_signon($creds, false);

        if (!is_wp_error($wpuser)) {
            wp_set_current_user($wpuser->data->ID);
            $this->transformUser($wpuser);
            return true;
        }

        return false;
    }

    public function forget()
    {
        wp_logout();
    }

    public function getGuest(): User
    {
        $guest = new User();

        $guest->setUsername('guest');
        $guest->setName('Guest');
        $guest->setRole('guest');
        $guest->setHomedir('/');
        $guest->setPermissions([]);

        return $guest;
    }

}
