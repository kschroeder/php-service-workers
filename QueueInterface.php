<?php

interface QueueInterface
{

    public function get();

    public function put($data);

    public function flush();

}
