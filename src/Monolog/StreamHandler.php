<?php


namespace Behnamhosseini\LogToJson\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Utils;

/**
 * Stores to any stream resource
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class StreamHandler extends AbstractProcessingHandler
{
    protected $stream;
    protected $url;
    private $errorMessage;
    protected $filePermission;
    protected $useLocking;
    private $dirCreated;

    /**
     * @param resource|string $stream
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     * @param int|null $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param bool $useLocking Try to lock log file before doing any writes
     *
     * @throws \Exception                If a missing directory is not buildable
     * @throws \InvalidArgumentException If stream is not a resource or string
     */
    public function __construct($stream, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = Utils::canonicalizePath($stream);
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {

        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->dirCreated = null;
    }

    /**
     * Return the currently active stream if it is open
     *
     * @return resource|null
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Return the stream URL if it was configured with a URL and not an active resource
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {

        if (!is_resource($this->stream)) {
            if (null === $this->url || '' === $this->url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }

            $this->createDir();
            $this->errorMessage = null;

            set_error_handler(array($this, 'customErrorHandler'));


            $config = config('logToJson');
            if ($config['toJson']) {
                $this->stream = fopen(str_replace('.log','.json',$this->url), 'a');
                $streams['json']=$this->stream;
            }
            if ($config['normal']) {
                $this->stream = fopen($this->url, 'a');
                $streams['normal']=$this->stream;
            }

            if ($this->filePermission !== null) {
                @chmod($this->url, $this->filePermission);
            }

            foreach ($streams as $stream){
                if (!is_resource($stream)) {
                    $stream = null;
                    throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened in append mode: ' . $this->errorMessage, $this->url));
                }
            }
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            foreach ($streams as $stream){
                if (!is_resource($stream)) {
                    $stream = null;
                    throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened in append mode: ' . $this->errorMessage, $this->url));
                }
            }
        }
        foreach ($streams as $key=>$stream){
            $this->streamWrite($stream,$key, $record);
            if ($this->useLocking) {
                flock($stream, LOCK_UN);
            }
        }
    }


    protected function streamWrite($stream,$key, array $record)
    {

        if ($key == 'json'){
            $file=str_replace('.log','.json',$this->url);
            $jsonString = file_get_contents($file);
            $data = json_decode($jsonString, true) ?? [];
            $value =json_decode($record['formatted'][$key], TRUE);
            array_push($data,$value);
            $newJsonString = json_encode($data);
            $handle = fopen ($file, "w+");
            fclose($handle);
            fwrite($stream, $newJsonString);
        }

        if ($key == 'normal'){
            fwrite($stream, (string)$record['formatted'][$key]);
        }
    }

    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
    }

    /**
     * @param string $stream
     *
     * @return null|string
     */
    private function getDirFromStream($stream)
    {
        $pos = strpos($stream, '://');
        if ($pos === false) {
            return dirname($stream);
        }

        if ('file://' === substr($stream, 0, 7)) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    private function createDir()
    {
        // Do not try to create dir if it has already been tried.
        if ($this->dirCreated) {
            return;
        }

        $dir = $this->getDirFromStream($this->url);
        if (null !== $dir && !is_dir($dir)) {
            $this->errorMessage = null;
            set_error_handler(array($this, 'customErrorHandler'));
            $status = mkdir($dir, 0777, true);
            restore_error_handler();
            if (false === $status && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s" and its not buildable: ' . $this->errorMessage, $dir));
            }
        }
        $this->dirCreated = true;
    }
}
