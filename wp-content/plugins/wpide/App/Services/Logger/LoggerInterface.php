<?php

namespace WPIDE\App\Services\Logger;

interface LoggerInterface
{
    public function log(string $message, int $level);
}
