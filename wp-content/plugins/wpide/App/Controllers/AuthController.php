<?php

namespace WPIDE\App\Controllers;

use WPIDE\App\Kernel\Response;
use WPIDE\App\Services\Auth\AuthInterface;
use WPIDE\App\Services\Logger\LoggerInterface;

class AuthController
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getUser(Response $response, AuthInterface $auth)
    {
        $user = $auth->user() ?: $auth->getGuest();

        return $response->json($user);
    }
}
