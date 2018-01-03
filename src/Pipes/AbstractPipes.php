<?php

namespace Ions\Process\Pipes;

/**
 * Class AbstractPipes
 * @package Ions\Process\Pipes
 */
abstract class AbstractPipes implements PipesInterface
{
    /**
     * @var array
     */
    public $pipes = [];

    /**
     * @var string
     */
    private $inputBuffer = '';

    /**
     * @var resource
     */
    private $input;

    /**
     * @var bool
     */
    private $blocked = true;

    /**
     * AbstractPipes constructor.
     * @param $input
     */
    public function __construct($input)
    {
        if (is_resource($input)) {
            $this->input = $input;
        } elseif (is_string($input)) {
            $this->inputBuffer = $input;
        } else {
            $this->inputBuffer = (string)$input;
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $this->pipes = [];
    }

    /**
     * @return bool
     */
    protected function hasSystemCallBeenInterrupted()
    {
        $lastError = error_get_last();
        return isset($lastError['message']) && false !== stripos($lastError['message'], 'interrupted system call');
    }

    /**
     * @return void
     */
    protected function unblock()
    {
        if (!$this->blocked) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, 0);
        }

        if (null !== $this->input) {
            stream_set_blocking($this->input, 0);
        }

        $this->blocked = false;
    }

    /**
     * @return array|void
     */
    protected function write()
    {
        if (!isset($this->pipes[0])) {
            return;
        }

        $input = $this->input;
        $r = $e = [];
        $w = [$this->pipes[0]];

        if (false === $n = @stream_select($r, $w, $e, 0, 0)) {
            return;
        }

        foreach ($w as $stdin) {
            if (isset($this->inputBuffer[0])) {
                $written = fwrite($stdin, $this->inputBuffer);
                $this->inputBuffer = substr($this->inputBuffer, $written);
                if (isset($this->inputBuffer[0])) {
                    return [$this->pipes[0]];
                }
            }

            if ($input) {
                for (; ;) {
                    $data = fread($input, self::CHUNK_SIZE);
                    if (!isset($data[0])) {
                        break;
                    }
                    $written = fwrite($stdin, $data);
                    $data = substr($data, $written);
                    if (isset($data[0])) {
                        $this->inputBuffer = $data;
                        return [$this->pipes[0]];
                    }
                }
                if (feof($input)) {
                    $this->input = null;
                }
            }
        }

        if (null === $this->input && !isset($this->inputBuffer[0])) {
            fclose($this->pipes[0]);
            unset($this->pipes[0]);
        } elseif (!$w) {
            return [$this->pipes[0]];
        }
    }
}
