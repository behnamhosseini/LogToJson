<?php

namespace Behnamhosseini\LogToJson;

use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        if (is_callable($reportCallable = [$e, 'report'])) {
            return $this->container->call($reportCallable);
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e;
        }
        $content=['file'=>$e->getFile(),'message'=>$e->getMessage(),'code'=>$e->getCode()];

        $logger->error(
            $e->getMessage(),
            array_merge($this->context(), ['exception' => $content]
            ));
    }
}
