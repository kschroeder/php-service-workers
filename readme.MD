# PHP Service Workers / Data Queue

This is a public repo to accumulate some thoughts on handling asynchronous functionality in PHP.  It is mostly a dump of my thoughts on the subject.  The impetus behind creating this repository was some work I've been doing recently with Python and Node and thinking "Gee.  It would be nice to be able to do some of this in PHP." As such, this might just be a silly endeavor.

Please feel free to comment on this or open issues on this repo if you have thoughts you would like to share (besides the obvious "this is stupid" response).  Or if you have thoughts on API changes, feel free to open a PR.

# Introductory Thoughts

I have been playing around with worker queues and data queues in PHP for at least 10 years.   But most implementations require some level of a) infrastructure, or b) custom programming (which usually involves process handling).  I was recently working with some IFrame-based JavaScript and found that there was a kind of message queue that cross-domain IFrames could use to [communicate with the parent window](https://developer.mozilla.org/en-US/docs/Web/API/Window/postMessage).

In addition, I'm doing some work with Python where I can simply execute:

```python
data_queue = Queue()
producer(data_queue) # multiprocessor function

result = data_queue.get()
```

What I like about both solutions is that I can do asynchronous processing out of the box without having to think about it.  It's not a full featured, distributed, asynchronous processing system, but it gets the job done when there is some level of concurrency needed. 

# Use Cases

The most blatant use case is on the CLI.  I have written several multi-process scripts to handle concurrency on the CLI and it is truly a pain to do.  One that I'm working on right now that I wrote in Python gets all local system instrumentation and MySQL instrumentation and then pushes them to an endpoint using OAuth once every second.  Because I don't want the system sampling to be skewed too much by HTTPS delays I poll once per second and send the results to a queue which another process then dequeues.  This is all done in about 5 lines of code in Python (give or take).

Another option could be code scanners, where a list of files is put into a data queue and X service workers would dequeue and process them as needed.

One might thing that front-end requests would be less pertinent.  But that person has never worked with Magento's totals collection.  Any time where there are synchronous API calls that do not depend on each other, a service worker could provide functionality that drastically reduces response times.

# Implementation

I have very, very, very little experience in the Zend engine itself.  So take this with a grain of salt.

My thought is that the service workers would each run in their own thread.  They would act like a mini-PHP-VM where they have their own variable stack, their own *everything*, but they would be closely associated with the main process.  I would think that they would be killed off on R_SHUTDOWN.  Though I could see some scenarios where you might want them to persist for a bit.  Communication would be facilitated via an intermediary broker.

My thought is that the intermediary broker is the only thing that needs to be aware of both the main and the child functionality.

# Code level implementation

It might look something like this

```php

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
```

The second example is starting up the service worker initially and then pumping it with information.  This example has an implicit directional flow that `put()` on one side pushes to `get()` on the other side, and vice versa.  

```php
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
```

What are your thoughts?
