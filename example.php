<?php

class Worker extends ServiceWorker
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    protected function run()
    {
        $contents = file_get_contents($this->url);
        $doc = new DOMDocument();
        $doc->loadHTML($contents);
        $xpath = new DOMXPath($doc);
        $nodeList = $xpath->query('//title');
        if ($nodeList->length > 0) {
            $this->getQueue()->put((string)$nodeList->item(0));
            return;
        }
        $this->getQueue()->put('Unknown');
    }
}


$worker = new Worker('https://www.magiumlib.com/');
$worker->start();
// Do stuff

$contents = $worker->getQueue()->get();
echo sprintf("The title is %s\n", $contents);
