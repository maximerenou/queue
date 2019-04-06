<?php
/**
 * @author Maxime Renou
 */

namespace Simpleue\Job;

class ExampleJob implements Job
{
    public function execute()
    {
        echo 'ExampleJob started...'.PHP_EOL;

        for($i = 0; $i < 10; $i++)
        {
            echo $i.PHP_EOL;
        }

        echo 'ExampleJob ended.'.PHP_EOL;

        return self::JOB_STATUS_RETRY;
    }
}