<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';
require_once '../Scheduler.php';
require_once 'log.php';

$config    = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'scheduler-cache.json',
  ['fld' => __DIR__ . '/sub' ],
  'logTask'
);

echo "Running scheduler for 15 seconds...<br><br>\n";
$endTime = time() + 15;

while( time() < $endTime )
{
  try  {
    $scheduler->run();  // TASK: maybe try the overrides
  }
  catch( Exception $e ) {
    error_log("Scheduler error: " . $e->getMessage());
    exit();
  }

  sleep(1);  // wait 1 second between checks
}

echo "Done\n";
