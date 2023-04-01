<?php

namespace WPIDE\App\Controllers;

use WPIDE\App\Kernel\Response;
use WPIDE\App\Services\View\ViewInterface;

class ViewController
{

    public function index(Response $response, ViewInterface $view)
    {
        return $response->html($view->getIndexPage());
    }
}
