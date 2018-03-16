<?php

abstract class ServiceWorker
{
    private $queue;

    public final function getQueue(): QueueInterface
    {
        /*
         * Somewhere in PHP a queue is created.
         *
         * This PHP code is just a representation of what would happen internally.  The $queue object is the ONLY thing
         * that would be shared between the main process and service worker.
         */

        if (!$this->queue instanceof QueueInterface) {
            $this->queue = new Queue();
        }
        return $this->queue;
    }

    protected abstract function run();

    public final function start()
    {
        // magic
        $this->run();
    }

    public final function stop()
    {
        // magic
    }

}
