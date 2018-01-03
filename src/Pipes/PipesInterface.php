<?php

namespace Ions\Process\Pipes;

/**
 * Interface PipesInterface
 * @package Ions\Process\Pipes
 */
interface PipesInterface
{
    const CHUNK_SIZE = 16384;

    /**
     * @return mixed
     */
    public function getDescriptors();

    /**
     * @return mixed
     */
    public function getFiles();

    /**
     * @param $blocking
     * @param bool $close
     * @return mixed
     */
    public function readAndWrite($blocking, $close = false);

    /**
     * @return mixed
     */
    public function areOpen();

    /**
     * @return mixed
     */
    public function close();
}
