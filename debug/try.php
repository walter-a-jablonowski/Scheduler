<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';
require_once '../Scheduler.php';

$scheduler = new Scheduler(
  Yaml::parseFile('config.yml')['scheduler'],
  'scheduler-cache.json',
  'log.php'
);

echo "Running scheduler for 30 seconds...\n";
$endTime = time() + 30;

while( time() < $endTime )
{
  $scheduler->run();
  sleep(1);  // Wait 1 second between checks
}

echo "Done\n";
