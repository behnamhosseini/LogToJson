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
        $config = config('logToJson');
        $this->validateConfig($config);
        if (!$config['toJson']){
            parent::report($e);
        }
        if ($this->shouldntReport($e)) {
            return;
        }

        if (is_callable($reportCallable = [$e, 'report'])) {
            return $this->container->call($reportCallable);
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $ex;
        }
        if ($config['toJson']) {
            $logger->error('',$this->content($e,$config));
        }
    }

    private function validateConfig($config)
    {

        if (!isset($config)) {
            throw new \ErrorException('logToJsona Config file not found - please publish it');
        }

        $logToJson = include(__DIR__ . '\config\config.php');
        foreach ($logToJson as $key => $value) {
            if (!array_key_exists($key, $config)) {
                throw new \ErrorException($key.' was not found in the logToJson file');
            }
        }
    }

    private function content($e,$config) : array {
        $content=[];
        foreach ($config['toJson-data'] as $value) {
            $method='get'.(strToUpper($value));
            if (!method_exists($e,$method)) {
                throw new \ErrorException('The value you selected to create in the log file is incorrect');
            }
            $content[$value] = $e->$method();
        }
        return $content;
    }
}
