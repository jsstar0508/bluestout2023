<?php

namespace WPIDE\App\Services\Auth;

interface AuthInterface
{
    public function user(): ?User;

    public function authenticate($username, $password): bool;

    public function forget();

    public function getGuest(): User;
}
