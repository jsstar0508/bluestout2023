<?php
namespace WPIDE\App\Services;

class Services
{
    protected $services = [];

    public function __construct(array $services = [])
    {

        $this->services = $services;
    }

    public function get()
    {
        return $this->services;
    }
}
