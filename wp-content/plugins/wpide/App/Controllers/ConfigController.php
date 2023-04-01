<?php

namespace WPIDE\App\Controllers;

use WPIDE\App\Config\Config;
use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;

class ConfigController
{

    public function getConfig(Response $response, Config $config)
    {

        return $response->json([
            'config' => $config->get(),
            'defaults' => $config->getDefaults()
        ]);
    }

    public function updateConfig(Request $request, Response $response, Config $config)
    {

        $key = $request->input('key');
        $value = $request->input('value');

        $config = $config->update($key, $value);

        if($config === false) {

            return $response->json('Cannot save settings!', 422);
        }

        return $response->json($config);
    }

}
