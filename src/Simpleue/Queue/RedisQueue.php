<?php
/**
 * @author Maxime Renou
 */

namespace Simpleue\Queue;

use Predis\Client;

class RedisQueue implements Queue
{

    private $redisClient;
    private $sourceQueue;
    private $maxWaitingSeconds;
    private $maxLoggedJobs;

    public function __construct(Client $redisClient, $queueName, $maxWaitingSeconds = 30, $maxLoggedJobs = 20)
    {
        $this->redisClient = $redisClient;
        $this->sourceQueue = $queueName;
        $this->maxWaitingSeconds = $maxWaitingSeconds;
        $this->maxLoggedJobs = $maxLoggedJobs;
    }

    // Queue functions

    public function push($job, $payload = [])
    {
        $data = $this->serialize([$job, $payload]);
        $this->redisClient->lpush($this->getSourceQueue(), [ $data ]);
        return 1;
    }

    public function next()
    {
        $queueItem = $this->redisClient->brpoplpush($this->getSourceQueue(), $this->getProcessingQueue(), $this->maxWaitingSeconds);
        return !is_null($queueItem) ? $this->unserialize($queueItem) : false;
    }

    public function ping()
    {
        $this->redisClient->ping();
    }

    // Status

    public function successful($job, $output)
    {
        $this->redisClient->lpush($this->getSuccessfulQueue(), [$this->serialize($job, $output)]);
        if ($this->redisClient->llen($this->getSuccessfulQueue()) >= $this->maxLoggedJobs)
            $this->redisClient->rpop($this->getSuccessfulQueue());

        $this->redisClient->lrem($this->getProcessingQueue(), 1, $this->serialize($job));
        return;
    }

    public function retry($job, $output)
    {
        $this->redisClient->lpush($this->getRetryQueue(), [$this->serialize($job, $output)]);
        if ($this->redisClient->llen($this->getRetryQueue()) >= $this->maxLoggedJobs)
            $this->redisClient->rpop($this->getRetryQueue());

        $this->redisClient->lpush($this->getSourceQueue(), [$this->serialize($job)]);
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $this->serialize($job));
    }

    public function failed($job, $output)
    {
        $this->redisClient->lpush($this->getFailedQueue(), [$this->serialize($job, $output)]);
        if ($this->redisClient->llen($this->getFailedQueue()) >= $this->maxLoggedJobs)
            $this->redisClient->rpop($this->getFailedQueue());

        $this->redisClient->lrem($this->getProcessingQueue(), 1, $this->serialize($job));
    }

    public function error($job, $output)
    {
        $this->redisClient->lpush($this->getErrorQueue(), [$this->serialize($job, $output)]);
        if ($this->redisClient->llen($this->getErrorQueue()) >= $this->maxLoggedJobs)
            $this->redisClient->rpop($this->getErrorQueue());

        $this->redisClient->lrem($this->getProcessingQueue(), 1, $this->serialize($job));
    }

    public function stopped($job)
    {
        $this->redisClient->lrem($this->getProcessingQueue(), 1, $this->serialize($job));
    }

    // Helpers

    public function toString($data)
    {
        return $data[0].': '.serialize($data[1]);
    }

    // Redis-specific

    public function setRedisClient(Client $redisClient)
    {
        $this->redisClient = $redisClient;
        return $this;
    }

    public function setQueueName($queueName)
    {
        $this->sourceQueue = $queueName;
        return $this;
    }

    public function setMaxWaitingSeconds($maxWaitingSeconds)
    {
        $this->maxWaitingSeconds = $maxWaitingSeconds;
        return $this;
    }

    public function getSourceQueue()
    {
        return $this->sourceQueue;
    }

    public function getProcessingQueue()
    {
        return $this->sourceQueue . ":processing";
    }

    public function getRetryQueue()
    {
        return $this->sourceQueue . ":retry";
    }

    public function getFailedQueue()
    {
        return $this->sourceQueue . ":failed";
    }

    public function getSuccessfulQueue()
    {
        return $this->sourceQueue . ":success";
    }

    public function getErrorQueue()
    {
        return $this->sourceQueue . ":error";
    }

    public function serialize($data, $output = null)
    {
        $json = [ $data[0], serialize($data[1]) ];
        if (!is_null($output))
        {
            $json[] = time();
            $json[] = $output;
        }

        return @json_encode($json);
    }

    public function unserialize($data)
    {
        $data = @json_decode($data);
        if ($data) {
            if (is_array($data)) {
                return [$data[0], unserialize($data[1])];
            }
            else {
                return $data;
            }
        }
        return false;
    }
}