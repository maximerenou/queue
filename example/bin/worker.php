<?php
/**
 * @author Maxime Renou
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../vendor/autoload.php';

$client = new \Predis\Client(array('host' => 'localhost', 'port' => 6379, 'schema' => 'tcp'));
$queue = new \Simpleue\Queue\RedisQueue($client, 'queue:default', 30, 2);
$worker = new \Simpleue\Worker\QueueWorker($queue, 0, true);
$logger = new \Simpleue\Mocks\LoggerSpy();
$worker->setLogger($logger);
$worker->start();