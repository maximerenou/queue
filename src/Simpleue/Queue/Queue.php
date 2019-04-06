<?php
/**
 * @author Maxime Renou
 */

namespace Simpleue\Queue;

interface Queue
{
    public function stop();

    /**
     * Add job to queue
     * @param string $job Job class
     * @param array $payload Job data (for constructor)
     * @return int
     */
    public function push($job, $payload = []);

    /**
     * Get next job to perform
     * @return array|false
     */
    public function next();

    /**
     * In case there's nothing to do
     * @return void
     */
    public function ping();

    // Status

    /**
     * Mark job as done
     * @param array $job
     * @param string|null $output
     * @return void
     */
    public function successful($job, $output);

    /**
     * Try again!
     * @param array $job
     * @param string|null $output
     * @return void
     */
    public function retry($job, $output);

    /**
     * Mark job as failed (job level)
     * @param array $job
     * @param string|null $output
     * @return void
     */
    public function failed($job, $output);

    /**
     * Mark job as failed (queue level)
     * @param array $job
     * @param string|null $output
     * @return void
     */
    public function error($job, $output);

    /**
     * Mark job as stopped
     * @param array $job
     * @return void
     */
    public function stopped($job);

    // Helpers

    /**
     * Convert a job to string
     * @param array $job
     * @return string
     */
    public function toString($job);
}