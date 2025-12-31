<?php

use Symfony\Component\Yaml\Yaml;

set_time_limit(0);

require_once 'vendor/autoload.php';
require_once 'lib/Scheduler/Scheduler.php';

// Init your error handler

$config    = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json', [
  'some' => 'placeholder',   // placeholders for config fields command, url, file, workingDir like "{some}/file.php"
  'some' => 'placeholder2',
  // ...
], function( string $state, array $result, float $time, array $config ) {
  // runs when a task is finished with state = success|error (see below)
  // fallback err handling (ideally called tool does this)
});

// Delay, wait until apps are ready on system startup

echo "Scheduler starting...\n";
sleep( 2 * 60 );

// Run Scheduler each 5 min

echo "Scheduler running... Press 'q' and Enter to stop gracefully\n";

while( ! connection_aborted())
{
  $scheduler->run();  // exception handled by error handler (or use try catch)
  
  // Check for quit command while sleeping

  $start = time(); 
  while( time() - $start < 5 * 60 )
  {
    if(( $input = fgets(STDIN)) !== false && trim($input) === 'q')
      break;

    sleep(1);
  }
}

echo "Scheduler stopped\n";
