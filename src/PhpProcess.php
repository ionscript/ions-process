<?php

namespace Ions\Process;

/**
 * Class PhpProcess
 * @package Ions\Process
 */
class PhpProcess extends Process
{
    /**
     * PhpProcess constructor.
     * @param $php
     * @param null $script
     * @param null $cwd
     * @param array|null $env
     * @param int $timeout
     * @param array $options
     */
    public function __construct($php, $script, $cwd = null, array $env = null, $timeout = 60, array $options = [])
    {
        if ('phpdbg' === PHP_SAPI) {
            $file = tempnam(sys_get_temp_dir(), 'dbg');
            file_put_contents($file, $script);
            register_shutdown_function('unlink', $file);
            $php .= ' ' . ProcessUtils::escapeArgument($file);
            $script = null;
        }

        if ('\\' !== DIRECTORY_SEPARATOR && null !== $php) {
            $php = 'exec ' . $php;
        }

        parent::__construct($php, $cwd, $env, $script, $timeout, $options);
    }

    /**
     * @param $php
     */
    public function setPhpBinary($php)
    {
        $this->setCommandLine($php);
    }

    /**
     * @param null $callback
     * @throws \RuntimeException
     */
    public function start($callback = null)
    {
        if (null === $this->getCommandLine()) {
            throw new \RuntimeException('Unable to find the PHP executable.');
        }

        parent::start($callback);
    }
}
