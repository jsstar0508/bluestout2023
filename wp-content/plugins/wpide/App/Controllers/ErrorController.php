<?php

namespace WPIDE\App\Controllers;

use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;

class ErrorController
{
    protected $request_type;

    public function __construct(Request $request)
    {
        $this->request_type = $request->getContentType();
    }

    public function notFound(Response $response)
    {
        return $this->request_type == 'json' ? $response->json('Not Found', 404) : $response->html('Not Found', 404);
    }

    public function methodNotAllowed(Response $response)
    {
        return $this->request_type == 'json' ? $response->json('Not Allowed', 401) : $response->html('Not Found', 401);
    }
}
