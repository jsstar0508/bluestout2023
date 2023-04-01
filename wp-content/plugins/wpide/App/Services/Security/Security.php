<?php

namespace WPIDE\App\Services\Security;

use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;
use WPIDE\App\Services\Service;
use WPIDE\App\Services\Logger\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class Security implements Service
{
    protected $request;

    protected $response;

    protected $logger;

    public function __construct(Request $request, Response $response, LoggerInterface $logger)
    {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
    }

    public function init(array $config = [])
    {
        if(!wp_doing_ajax() || wp_doing_cron() || defined('WPIDE_DOING_TASK')) {
            return;
        }

        $nonce = wp_create_nonce();

        $http_method = $this->request->getMethod();

        if (in_array($http_method, ['GET', 'HEAD', 'OPTIONS'])) {
            $this->response->headers->set('X-CSRF-Token', $nonce);
        } else {
            $nonce = $this->request->headers->get('X-CSRF-Token');

            if (wp_verify_nonce($nonce) === false) {
                $message = "Csrf token not valid";
                $this->response->json($message, 403);
                $this->response->send();
                $this->logger->log($message);
                die;
            }
        }

        if (! empty($config['ip_whitelist'])) $config['ip_allowlist'] = $config['ip_whitelist']; // deprecated, compatibility

        if (! empty($config['ip_allowlist'])) {
            $pass = false;
            foreach ($config['ip_allowlist'] as $ip) {
                if ($this->request->getClientIp() == $ip) {
                    $pass = true;
                }
            }
            if (! $pass) {
                $message = "Forbidden - IP not found in allowlist ".$this->request->getClientIp();
                $this->response->json($message, 403);
                $this->response->send();
                $this->logger->log($message);
                die;
            }
        }

        if (! empty($config['ip_blacklist'])) $config['ip_denylist'] = $config['ip_blacklist']; // deprecated, compatibility

        if (! empty($config['ip_denylist'])) {
            $pass = true;
            foreach ($config['ip_denylist'] as $ip) {
                if ($this->request->getClientIp() == $ip) {
                    $pass = false;
                }
            }
            if (! $pass) {
                $message = "Forbidden - IP matched against denylist ".$this->request->getClientIp();
                $this->response->json($message, 403);
                $this->response->send();
                $this->logger->log($message);
                die;
            }
        }

    }
}
