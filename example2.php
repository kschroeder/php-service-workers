<?php

class Worker extends ServiceWorker
{

    protected function run()
    {
        while (true) {
            $url = $this->getQueue()->get();
            $contents = file_get_contents($url);
            $doc = new DOMDocument();
            $doc->loadHTML($contents);
            $xpath = new DOMXPath($doc);
            $nodeList = $xpath->query('//title');
            if ($nodeList->length > 0) {
                $this->getQueue()->put((string)$nodeList->item(0));
            } else {
                $this->getQueue()->put('Unknown');
            }
        }
    }
}


$worker = new Worker();
$worker->start();
$queue = $worker->getQueue();
$queue->put('https://www.magiumlib.com/');
$queue->put('https://www.eschrade.com/');
// Do stuff

echo sprintf("The title is %s\n", $queue->get());
echo sprintf("The title is %s\n", $queue->get());
