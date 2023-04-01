<?php
namespace WPIDE\App\Services\Cache;

use WPIDE\App\Services\Service;

class Cache implements Service
{

    protected $group;

    public function init(array $config = [])
    {

        $this->group = !empty($config['group']) ? $config['group'] : '';
    }

    public function set($key, $val, $expire = 0) {

        return wp_cache_set($key, $val, $this->group, $expire);
    }

    public function get($key) {

        return wp_cache_get( $key, $this->group );
    }

    public function delete($key) {

        return wp_cache_delete( $key, $this->group );
    }

    public function exists($key) {

        return $this->get( $key ) !== false;
    }

    public function flush() {

        return wp_cache_flush();
    }

    public function result($key, callable $callback, $expiration = 0) {

        $cached = $this->get($key);

        if($cached === false) {

            $cached = $callback();

            $this->set($key, $cached, $expiration);
        }

        return $cached;
    }

}