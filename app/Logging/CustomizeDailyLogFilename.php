<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\RotatingFileHandler;

class CustomizeDailyLogFilename
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                // base path is storage/logs/bakpia.log -> {filename} = "bakpia"
                // produces: bakpia_20260611.log
                $handler->setFilenameFormat('{filename}_{date}', 'Ymd');
            }
        }
    }
}
