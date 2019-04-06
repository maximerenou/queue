<?php
/**
 * @author Maxime Renou
 */

namespace Simpleue\Job;

interface Job
{
    const JOB_STATUS_RETRY = -1;
    const JOB_STATUS_FAILED = 0;
    const JOB_STATUS_SUCCESS = 1;

    /**
     * Method called to perform the job
     * @return integer
     */
    public function execute();
}