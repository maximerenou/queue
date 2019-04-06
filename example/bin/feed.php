<?php
/**
 * @author Maxime Renou
 */

require __DIR__ . '/../../vendor/autoload.php';

$client = new \Predis\Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp'));
$queue = new \Simpleue\Queue\RedisQueue($client, 'queue:default', 30);

$i = 0;

while(true)
{
    $job = \Simpleue\Job\ExampleJob::class;
    $data = ['hello' => $i];
    echo 'pushing job: '.$job.' '.json_encode($data).PHP_EOL;
    $queue->push($job, $data);
    $i++;
    sleep(5);
}