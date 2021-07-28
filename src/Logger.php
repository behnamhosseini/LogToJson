<?php

namespace Behnamhosseini\LogToJson;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Logger as LL;
use Psr\Log\LoggerInterface;

class Logger extends LL
{
    public function __construct(LoggerInterface $logger, Dispatcher $dispatcher = null)
    {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }
}
