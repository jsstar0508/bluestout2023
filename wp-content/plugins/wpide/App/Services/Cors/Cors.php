<?php

namespace WPIDE\App\Services\Cors;

use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;
use WPIDE\App\Services\Service;

/**
 * @codeCoverageIgnore
 */
class Cors implements Service
{
    protected $request;

    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function init(array $config = [])
    {
        if ($config['enabled'] !== true) {
            return;
        }

        $this->response->headers->set('Access-Control-Allow-Origin', $this->request->headers->get('Origin'));
        $this->response->headers->set('Access-Control-Allow-Credentials', 'true');
        $this->response->headers->set('Access-Control-Expose-Headers', 'x-csrf-token');

        if ($this->request->server->get('REQUEST_METHOD') == 'OPTIONS') {
            $this->response->headers->set('Access-Control-Allow-Headers', 'content-type, x-csrf-token');
            $this->response->send();
            die;
        }
    }
}
