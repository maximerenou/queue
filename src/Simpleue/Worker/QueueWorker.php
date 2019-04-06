<?php
/**
 * @author Maxime Renou
 */

namespace Simpleue\Worker;

use Simpleue\Job\Job;
use Simpleue\Queue\Queue;
use Psr\Log\LoggerInterface;

class QueueWorker
{
    /**
     * @var Queue Queue handler
     */
    protected $queue;

    /**
     * @var int Jobs performed
     */
    protected $iterations;

    /**
     * @var int Max jobs to perform
     */
    protected $maxIterations;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var bool Received a stop signal
     */
    protected $terminated;

    /**
     * QueueWorker constructor.
     * @param Queue $queue
     * @param int $maxIterations
     * @param bool $handleSignals
     * @throws \Exception
     */
    public function __construct(Queue $queue, $maxIterations = 0, $handleSignals = false)
    {
        $this->queue = $queue;
        $this->maxIterations = (int) $maxIterations;
        $this->iterations = 0;
        $this->logger = null;
        $this->terminated = false;

        if ($handleSignals)
            $this->registerSignalHandlers();
    }

    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    public function setMaxIterations($maxIterations)
    {
        $this->maxIterations = (int) $maxIterations;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    //

    /**
     * Setup signals handlers
     * @throws \Exception
     */
    protected function registerSignalHandlers()
    {
        if (!function_exists('pcntl_signal'))
        {
            $message = 'Please make sure that \'pcntl\' is enabled if you want us to handle signals';
            $this->log('error', $message);
            throw new \Exception($message);
        }

        declare(ticks = 1);

        pcntl_signal(SIGTERM, [$this, 'terminate']);
        pcntl_signal(SIGINT,  [$this, 'terminate']);

        $this->log('debug', 'Finished Setting up Handler for signals SIGTERM and SIGINT');
    }

    public function start()
    {
        $this->iterations = 0;

        $this->log('debug', 'Starting Queue Worker!');
        $this->starting();

        while ($this->running())
        {
            ++$this->iterations;

            try {
                $data = $this->queue->next();
            }
            catch (\Exception $exception)
            {
                $this->log('error', 'Error getting data. Message: '.$exception->getMessage());
                continue;
            }

            if ($this->checkJob($data))
            {
                $this->performJob($data);
            }
            elseif ($this->checkStopInstruction($data))
            {
                $this->log('debug', 'STOP instruction received.');
                $this->queue->stopped($data);
                break;
            }
            else {
                $this->log('debug', 'Nothing to do.');
                $this->queue->ping();
            }
        }

        $this->log('debug', 'Queue Worker finished.');
        $this->finished();
    }

    private function performJob($job)
    {
        try {
            $instance = null;
            $status = null;
            $output = null;

            ob_start();

            try {
                $class = $job[0];

                if (method_exists($class, '__construct'))
                {
                    $instance = call_user_func_array([$class, '__construct'], $job[1]);
                }
                else {
                    $instance = new $class();
                }

                $status = $instance->execute();
                $output = ob_get_contents();
            }
            catch (\Exception $exception)
            {
                $status = Job::JOB_STATUS_FAILED;
                $output = $exception->getMessage().PHP_EOL.ob_get_contents();
            }

            ob_end_clean();

            if ($status === Job::JOB_STATUS_SUCCESS)
            {
                $this->log('debug', 'Job done: '.$this->queue->toString($job));
                $this->queue->successful($job, $output);
            }
            elseif ($status === Job::JOB_STATUS_RETRY)
            {
                $this->log('debug', 'Job to try again: '.$this->queue->toString($job));
                $this->queue->retry($job, $output);
            }
            else {
                $this->log('debug', 'Job failed: '.$this->queue->toString($job));
                $this->queue->failed($job, $output);
            }
        }
        catch (\Exception $exception)
        {
            $this->log('error', 'Error performing job:'.$this->queue->toString($job).'. Message: '.$exception->getMessage());
            $this->queue->error($job, $exception->getMessage());
        }
    }

    // Status setters

    protected function terminate()
    {
        $this->log('debug', 'Caught signals: trying a graceful exit.');
        $this->terminated = true;
    }

    protected function starting()
    {
        //
    }

    protected function finished()
    {
        //
    }

    // Status getters

    protected function running()
    {
        if ($this->terminated) {
            return false;
        }

        if ($this->maxIterations > 0) {
            return $this->iterations < $this->maxIterations;
        }

        return true;
    }

    // Helpers

    protected function log($type, $message)
    {
        if ($this->logger)
            $this->logger->$type($message);
    }

    protected function checkJob($data)
    {
        return is_array($data);
    }

    protected function checkStopInstruction($data)
    {
        return $data === 'STOP';
    }
}
