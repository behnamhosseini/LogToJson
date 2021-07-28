<?php

namespace Behnamhosseini\LogToJson;

use Behnamhosseini\LogToJson\Logger;
use Illuminate\Log\LogManager as LM;


class LogManager extends LM
{
        protected function formatter()
    {
        return tap(new LineFormatter(null, null, true, true), function ($formatter) {
            $formatter->includeStacktraces();
        });
    }

    protected function get($name)
    {
        try {
            return $this->channels[$name] ?? with($this->resolve($name), function ($logger) use ($name) {
                    return $this->channels[$name] = $this->tap($name, new \Behnamhosseini\LogToJson\Logger($logger, $this->app['events']));
                });
        } catch (Throwable $e) {
            return tap($this->createEmergencyLogger(), function ($logger) use ($e) {
                $logger->emergency('Unable to create configured logger. Using emergency logger.', [
                    'exception' => $e,
                ]);
            });
        }
    }

}
