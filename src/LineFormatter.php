<?php


namespace Behnamhosseini\LogToJson;


use Monolog\Formatter\NormalizerFormatter;
use Monolog\Utils;
use Throwable;

/**
 * Formats incoming records into a one-line string
 *
 * This is especially useful for logging to files
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Christophe Coevoet <stof@notk.org>
 */
class LineFormatter extends NormalizerFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %level_name%: %message% %file% %context% %extra%  \n";

    protected $format;
    protected $allowInlineLineBreaks;
    protected $ignoreEmptyContextAndExtra;
    protected $includeStacktraces;

    /**
     * @param string $format The format of the message
     * @param string $dateFormat The format of the timestamp: one supported by DateTime::format
     * @param bool $allowInlineLineBreaks Whether to allow inline line breaks in log entries
     * @param bool $ignoreEmptyContextAndExtra
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        parent::__construct($dateFormat);
    }

    public function includeStacktraces($include = true)
    {
        $this->includeStacktraces = $include;
        if ($this->includeStacktraces) {
            $this->allowInlineLineBreaks = true;
        }
    }

    public function allowInlineLineBreaks($allow = true)
    {
        $this->allowInlineLineBreaks = $allow;
    }

    public function ignoreEmptyContextAndExtra($ignore = true)
    {
        $this->ignoreEmptyContextAndExtra = $ignore;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $config = config('logToJson');

        $content=[];
        if ($config['toJson']) {
            $content['json'] = $this->jsonFormat($record,$config);
        };
        if ($config['normal']) {
            $content['normal'] = $this->defultFormat($record,$config);
        }
        return $content;
    }

    public function jsonFormat($record,$config)
    {
        $vars = parent::format($record);
        if ($vars['level_name'] == 'ERROR') {
            unset($vars['message']);
        };

        if (isset($vars['context']['json'])) {
            $vars = array_merge($vars, $vars['context']['normal']);
        }
        unset($vars['context']['json']);
        unset($vars['context']['normal']);

        if (!$config['json-detail']){
            unset($vars['context']['exception']);
        }
        $output = $this->format;

        if ($vars['level_name'] != 'ERROR') {
            $output=str_replace('%file%','',$output);
        };

        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.' . $var . '%')) {
                $output = str_replace('%extra.' . $var . '%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }

        if (isset($vars['context']['json'])) {
            $vars = array_reverse($vars);
        }
        return json_encode($vars);
    }

    public function defultFormat($record,$config)
    {
        $vars = parent::format($record);

        if ($vars['level_name'] == 'ERROR') {
            unset($vars['message']);
        };
        if (isset($vars['context']['normal'])) {
            $vars = array_merge($vars, $vars['context']['normal']);
        }
        unset($vars['context']['json']);
        unset($vars['context']['normal']);

        if (!$config['normal-detail']){
            unset($vars['context']['exception']);
        }
        $output = $this->format;
        if ($vars['level_name'] != 'ERROR') {
            $output=str_replace('%file%','',$output);
        };
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }


//        foreach ($vars['context'] as $var => $val) {
//            if (false !== strpos($output, '%context.'.$var.'%')) {
//                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
//                unset($vars['context'][$var]);
//            }
//        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }
        return $output;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    protected function normalizeException(Throwable $e, int $depth = 0)
    {
        if ($e instanceof \JsonSerializable) {
            return (array) $e->jsonSerialize();
        }

        $data = [
            'class' => Utils::getClass($e),
            'message' => $e->getMessage(),
            'code' => (int) $e->getCode(),
            'file' => $e->getFile().':'.$e->getLine(),
        ];

        if ($e instanceof \SoapFault) {
            if (isset($e->faultcode)) {
                $data['faultcode'] = $e->faultcode;
            }

            if (isset($e->faultactor)) {
                $data['faultactor'] = $e->faultactor;
            }

            if (isset($e->detail)) {
                if (is_string($e->detail)) {
                    $data['detail'] = $e->detail;
                } elseif (is_object($e->detail) || is_array($e->detail)) {
                    $data['detail'] = $this->toJson($e->detail, true);
                }
            }
        }

        $trace = $e->getTrace();
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $data['trace'][] = $frame['file'].':'.$frame['line'];
            }
        }

        if ($previous = $e->getPrevious()) {
            $data['previous'] = $this->normalizeException($previous, $depth + 1);
        }

        return $data;
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return $this->toJson($data, true);
        }

        return str_replace('\\/', '/', $this->toJson($data, true));
    }

    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks) {
            if (0 === strpos($str, '{')) {
                return str_replace(array('\r', '\n'), array("\r", "\n"), $str);
            }

            return $str;
        }

        return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
    }
}
