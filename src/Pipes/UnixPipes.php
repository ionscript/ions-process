<?php

namespace Ions\Process\Pipes;

use Ions\Process\Process;

/**
 * Class UnixPipes
 * @package Ions\Process\Pipes
 */
class UnixPipes extends AbstractPipes
{
    /**
     * @var bool
     */
    private $ttyMode;
    /**
     * @var bool
     */
    private $ptyMode;
    /**
     * @var bool
     */
    private $disableOutput;

    /**
     * UnixPipes constructor.
     * @param $ttyMode
     * @param $ptyMode
     * @param $input
     * @param $disableOutput
     */
    public function __construct($ttyMode, $ptyMode, $input, $disableOutput)
    {
        $this->ttyMode = (bool)$ttyMode;
        $this->ptyMode = (bool)$ptyMode;
        $this->disableOutput = (bool)$disableOutput;
        parent::__construct($input);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return array
     */
    public function getDescriptors()
    {
        if ($this->disableOutput) {
            $nullstream = fopen('/dev/null', 'c');
            return [['pipe', 'r'], $nullstream, $nullstream,];
        }

        if ($this->ttyMode) {
            return [['file', '/dev/tty', 'r'], ['file', '/dev/tty', 'w'], ['file', '/dev/tty', 'w'],];
        }

        if ($this->ptyMode && Process::isPtySupported()) {
            return [['pty'], ['pty'], ['pty'],];
        }

        return [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w'],];
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return [];
    }

    /**
     * @param $blocking
     * @param bool $close
     * @return array
     */
    public function readAndWrite($blocking, $close = false)
    {
        $this->unblock();
        $w = $this->write();
        $read = $e = [];
        $r = $this->pipes;

        unset($r[0]);

        if (($r || $w) && false === $n = @stream_select($r, $w, $e, 0, $blocking ? Process::TIMEOUT_PRECISION * 1E6 : 0)) {
            if (!$this->hasSystemCallBeenInterrupted()) {
                $this->pipes = [];
            }

            return $read;
        }

        foreach ($r as $pipe) {
            $read[$type = array_search($pipe, $this->pipes, true)] = '';

            do {
                $data = fread($pipe, self::CHUNK_SIZE);
                $read[$type] .= $data;
            } while (isset($data[0]) && ($close || isset($data[self::CHUNK_SIZE - 1])));

            if (!isset($read[$type][0])) {
                unset($read[$type]);
            }

            if ($close && feof($pipe)) {
                fclose($pipe);
                unset($this->pipes[$type]);
            }
        }

        return $read;
    }

    /**
     * @return bool
     */
    public function areOpen()
    {
        return (bool)$this->pipes;
    }

    /**
     * @param Process $process
     * @param $input
     * @return static
     */
    public static function create(Process $process, $input)
    {
        return new static($process->isTty(), $process->isPty(), $input, $process->isOutputDisabled());
    }
}
