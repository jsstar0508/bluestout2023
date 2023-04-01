<?php
namespace WPIDE\App\Config;

use Adbar\Dot;
use const WPIDE\Constants\SLUG;

class Config
{
    /* @var $config array */
    protected $config;

    /* @var $values Dot */
    protected $values;

    /* @var $defaults Dot */
    protected $defaults;

    /* @var $meta_key string */
    protected $meta_key;

    public function __construct(array $config = [])
    {

        $this->meta_key = SLUG.'_config';
        $this->config = $config;

        $this->loadDefaults();
        $this->loadValues();
    }

    public function loadDefaults()
    {

        $this->defaults = new Dot($this->getConfigDefaults($this->config));
    }

    public function loadValues()
    {

        $options = $this->getOption();
        $this->values = new Dot($options);
    }

    public function getDefaults(): array
    {

        return $this->defaults->all();
    }

    protected function getConfigDefaults($config): array
    {

        $values = [];

        if(!is_array($config)){
            return $values;
        }

        foreach($config as $key => $conf) {

            $values[$key] = $conf['default'] ?? $this->getConfigDefaults($conf);
        }

        return $values;
    }

    public function getOption()
    {
        $config = get_option($this->meta_key);

        if(empty($config)) {

            $config = $this->defaults->all();
            $this->saveOption($config);
        }

        return $config;
    }

    public function saveOption($config): bool
    {
        $updated = update_option($this->meta_key, $config);
        $this->loadValues();

        return $updated;
    }

    public function getConfigFields(): array
    {
        return $this->config;
    }

    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->values->all();
        }

        return $this->values->get($key, $default);

    }

    public function update($key, $value)
    {

        if($this->fieldExists($key)) {

            if(is_object($value)) {
                $value = (array) $value;
            }

            $this->values->set($key, $value);

            $this->saveOption($this->values->all());

            return $this->values->all();

        }

        return false;
    }

    public function delete($key) {

        if($this->values->has($key)) {
            $this->values->delete($key);
            $this->saveOption($this->values->all());
        }
    }

    public function getField($key)
    {

        $config = new Dot($this->config);

        return $config->get($key);
    }

    public function fieldExists($key): bool
    {

        $config = new Dot($this->config);

        return $config->has($key);
    }
}
