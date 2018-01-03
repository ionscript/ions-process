<?php

namespace Ions\Process;

/**
 * Class ProcessBuilder
 * @package Ions\Process
 */
class ProcessBuilder
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @var
     */
    private $cwd;

    /**
     * @var array
     */
    private $env = [];

    /**
     * @var
     */
    private $input;

    /**
     * @var int
     */
    private $timeout = 60;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $inheritEnv = true;

    /**
     * @var array
     */
    private $prefix = [];

    /**
     * @var bool
     */
    private $outputDisabled = false;

    /**
     * ProcessBuilder constructor.
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * @param array $arguments
     * @return static
     */
    public static function create(array $arguments = [])
    {
        return new static($arguments);
    }

    /**
     * @param $argument
     * @return $this
     */
    public function add($argument)
    {
        $this->arguments[] = $argument;
        return $this;
    }

    /**
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = is_[$prefix] ? $prefix : [$prefix];
        return $this;
    }

    /**
     * @param array $arguments
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @param $cwd
     * @return $this
     */
    public function setWorkingDirectory($cwd)
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * @param bool $inheritEnv
     * @return $this
     */
    public function inheritEnvironmentVariables($inheritEnv = true)
    {
        $this->inheritEnv = $inheritEnv;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setEnv($name, $value)
    {
        $this->env[$name] = $value;
        return $this;
    }

    /**
     * @param array $variables
     * @return $this
     */
    public function addEnvironmentVariables(array $variables)
    {
        $this->env = array_replace($this->env, $variables);
        return $this;
    }

    /**
     * @param $input
     * @return $this
     */
    public function setInput($input)
    {
        $this->input = ProcessUtils::validateInput(__METHOD__, $input);
        return $this;
    }

    /**
     * @param $timeout
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setTimeout($timeout)
    {
        if (null === $timeout) {
            $this->timeout = null;
            return $this;
        }

        $timeout = (float)$timeout;

        if ($timeout < 0) {
            throw new \InvalidArgumentException('The timeout value must be a valid positive integer or float number.');
        }

        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableOutput()
    {
        $this->outputDisabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function enableOutput()
    {
        $this->outputDisabled = false;
        return $this;
    }

    /**
     * @return Process
     * @throws \LogicException
     */
    public function getProcess()
    {
        if (0 === count($this->prefix) && 0 === count($this->arguments)) {
            throw new \LogicException('You must add() command arguments before calling getProcess().');
        }

        $options = $this->options;
        $arguments = array_merge($this->prefix, $this->arguments);
        $script = implode(' ', array_map([__NAMESPACE__ . '\\ProcessUtils', 'escapeArgument'], $arguments));

        if ($this->inheritEnv) {
            $env = array_replace($_ENV, $_SERVER, $this->env);
        } else {
            $env = $this->env;
        }

        $process = new Process($script, $this->cwd, $env, $this->input, $this->timeout, $options);

        if ($this->outputDisabled) {
            $process->disableOutput();
        }

        return $process;
    }
}
