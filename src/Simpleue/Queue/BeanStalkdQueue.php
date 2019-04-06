<?php

// Notice: not tested with new queue system

namespace Simpleue\Queue;

use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;

/**
 * Class BeanstalkdQueue
 * @author Adeyemi Olaoye <yemexx1@gmail.com>
 * @package Simpleue\Queue
 */
class BeanStalkdQueue implements Queue
{
    /** @var  Pheanstalk */
    private $beanStalkdClient;
    private $sourceQueue;
    private $failedQueue;
    private $errorQueue;

    public function __construct($beanStalkdClient, $queueName)
    {
        $this->beanStalkdClient = $beanStalkdClient;
        $this->setQueues($queueName);
    }


    protected function setQueues($queueName)
    {
        $this->sourceQueue = $queueName;
        $this->failedQueue = $queueName . '-failed';
        $this->errorQueue = $queueName . '-error';
    }

    public function next()
    {
        $this->beanStalkdClient->watch($this->sourceQueue);
        return $this->beanStalkdClient->reserve(0);
    }

    public function successful($job)
    {
        return $this->beanStalkdClient->delete($job);
    }

    public function stop()
    {
        // TODO
    }

    /**
     * @param $job Job
     */
    public function failed($job)
    {
        $this->beanStalkdClient->putInTube($this->failedQueue, $job->getData());
        $this->beanStalkdClient->delete($job);
    }

    /**
     * @param $job Job
     */
    public function error($job)
    {
        $this->beanStalkdClient->putInTube($this->errorQueue, $job->getData());
        $this->beanStalkdClient->delete($job);
    }

    public function ping()
    {
        return;
    }

    public function stopped($job)
    {
        return $this->beanStalkdClient->delete($job);
    }

    /**
     * @param $job Job
     * @return string
     */
    public function toString($job)
    {
        return json_encode(['id' => $job->getId(), 'data' => $job->getData()]);
    }

    /**
     * @param string $job
     * @param array $payload
     * @return int
     */
    public function push($job, $payload = [])
    {
        return $this->beanStalkdClient->putInTube($this->sourceQueue, [$job, $payload]);
    }

    /**
     * Try again!
     * @param array $job
     * @param string|null $output
     * @return void
     */
    public function retry($job, $output)
    {
        // TODO: Implement retry() method.
    }
}
