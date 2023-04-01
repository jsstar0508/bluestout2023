<?php

namespace WPIDE\App\Container;

interface ContainerInterface
{
    public function get($name);

    public function set(string $name, $value);

    public function call($callable, array $parameters = []);
}
