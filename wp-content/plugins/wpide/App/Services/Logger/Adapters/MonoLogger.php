<?php

namespace WPIDE\App\Services\Logger\Adapters;

use WPIDE\App\Services\Logger\LoggerInterface;
use WPIDE\App\Services\Service;
use Monolog\ErrorHandler;
use Monolog\Logger;

class MonoLogger implements Service, LoggerInterface
{
    protected $logger;

    public function init(array $config = [])
    {
        $this->logger = new Logger('default');

        foreach ($config['monolog_handlers'] as $handler) {
            $this->logger->pushHandler($handler());
        }

        $handler = new ErrorHandler($this->logger);
        $handler->registerErrorHandler([], true);
        $handler->registerFatalHandler();
    }

    public function log(string $message, int $level = Logger::INFO)
    {
        $this->logger->log($level, $message);
    }
}
