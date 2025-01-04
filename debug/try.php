<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';
require_once '../Scheduler.php';
require_once 'log.php';

$scheduler = new Scheduler(
  Yaml::parseFile('config.yml')['scheduler'],
  'scheduler-cache.json',
  'logTask'
);

echo "Running scheduler for 15 seconds...\n";
$endTime = time() + 15;

while( time() < $endTime )
{
  try  {
    $scheduler->run();
  }
  catch( Exception $e ) {
    error_log("Scheduler error: " . $e->getMessage());
    exit();
  }

  sleep(1);  // wait 1 second between checks
}

echo "Done\n";
