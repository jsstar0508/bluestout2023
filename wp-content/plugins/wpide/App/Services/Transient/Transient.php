<?php
namespace WPIDE\App\Services\Transient;

use WPIDE\App\Services\Service;

class Transient implements Service
{

    protected $prefix;

    public function init(array $config = [])
    {

        $this->prefix = !empty($config['prefix']) ? $config['prefix'] : '';
    }

    public function set($key, $val, $expiration = YEAR_IN_SECONDS): bool
    {

        return set_transient( $this->prefix.$key, $val, $expiration );
    }

    public function get($key, $default = null) {

        $value = get_transient( $this->prefix.$key );

        if($value === false) {
            return $default;
        }

        return $value;
    }

    public function delete($key): bool
    {

        return delete_transient( $this->prefix.$key );
    }

    public function exists($key): bool
    {

        return $this->get( $key ) !== false;
    }

    public function result($key, callable $callback, $expiration = YEAR_IN_SECONDS) {

        $cached = $this->get($key);

        if($cached === false || !empty($_GET['nocache'])) {

            $cached = $callback();

            $this->set($key, $cached, $expiration);
        }

        return $cached;
    }

}