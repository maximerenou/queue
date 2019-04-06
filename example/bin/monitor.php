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
    var_dump('source', $client->lrange($queue->getSourceQueue(), 0, -1));
    var_dump('processing', $client->lrange($queue->getProcessingQueue(), 0, -1));
    var_dump('retry', $client->lrange($queue->getRetryQueue(), 0, -1));
    var_dump('successful', $client->lrange($queue->getSuccessfulQueue(), 0, -1));
    var_dump('failed', $client->lrange($queue->getFailedQueue(), 0, -1));
    var_dump('error', $client->lrange($queue->getErrorQueue(), 0, -1));

    sleep(5);
    echo '========================'.PHP_EOL;
}