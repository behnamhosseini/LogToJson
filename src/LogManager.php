<?php

namespace App\Classes;

use Illuminate\Log\LogManager as LM;


class LogManager extends LM
{
    protected function formatter()
    {
        return tap(new LineFormatter(null, null, true, true), function ($formatter) {
            $formatter->includeStacktraces();
        });
    }
}
