<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';
require_once '../Scheduler.php';

$config    = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'scheduler-cache.json',
  ['fld' => __DIR__ . '/sub'],
  'logTask'
);

while (ob_get_level())
  ob_end_clean();
  
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Send headers and initial HTML
header('Content-Type: text/html; charset=utf-8');
header('X-Accel-Buffering: no');

echo file_get_contents('header.html');

echo "Running scheduler for 15 seconds...<br><br>\n";
$endTime = time() + 15;

while( time() < $endTime )
{
  try {
    $scheduler->run();
  }
  catch( Exception $e ) {
    error_log("Scheduler error: " . $e->getMessage());
    exit();
  }

  sleep(1);  // wait 1 second between checks
}

echo "<script>window.autoScroll = false;</script>Done\n";


function logTask( string $state, array $result, float $time, array $config ): void
{
  // Start buffering just this task output
  ob_start();
  
  echo "<div class='task'>\n";
  echo "<div class='task-time'>" . date('Y-m-d H:i:s') . " Task started</div>\n";
  
  echo "<div class='config'>\n";
  echo "<strong>Type:</strong> {$config['type']}<br>\n";
  echo "<strong>Name:</strong> {$config['name']}<br>\n";

  if( isset($config['file']))
    echo "<strong>File:</strong> {$config['file']}<br>\n";
  else if( isset($config['url']))
    echo "<strong>URL:</strong> {$config['url']}<br>\n";

  if( ! empty($config['args']))
  {
    echo "<div class='args'>\n";
    echo "<strong>Args:</strong><br>\n";
    foreach( $config['args'] as $key => $value)
      echo "• $key: $value<br>\n";
    echo "</div>\n";
  }

  if( isset($config['startDate']))
    echo "<strong>Start Date:</strong> {$config['startDate']}<br>\n";

  echo "<strong>Interval:</strong> {$config['interval']}<br>\n";

  if( isset($config['likeliness']))
    echo "<strong>Likeliness:</strong> {$config['likeliness']}%<br>\n";
  echo "</div>\n";
  
  echo "<strong>Time:</strong> " . round($time, 3) . "s<br>\n";
  
  echo "<div class='result'>\n";
  // For URL tasks
  if( $config['type'] === 'URL')
  {
    echo "<strong>HTTP Code:</strong> {$result['http_code']}<br>\n";
    echo "<strong>Response:</strong><br>\n";
    echo "<pre>" . htmlspecialchars(substr($result['response'], 0, 100)) . 
         (strlen($result['response']) > 100 ? '...' : '') . "</pre>\n";
  }
  // For Script tasks
  elseif( $config['type'] === 'Script')
  {
    echo "<strong>Output:</strong><br>\n";
    echo "<pre>" . htmlspecialchars($result['output']) . "</pre>\n";
    if( isset($result['return']))
    {
      echo "<strong>Return:</strong><br>\n";
      echo "<pre>" . htmlspecialchars(print_r($result['return'], true)) . "</pre>\n";
    }
  }
  // For Command tasks
  elseif( $config['type'] === 'Command')
  {
    echo "<strong>Output:</strong><br>\n";
    echo "<pre>" . htmlspecialchars($result['output']) . "</pre>\n";
  }
  // For Process tasks
  elseif( $config['type'] === 'Process')
  {
    echo "<strong>Message:</strong> {$result['message']}<br>\n";
  }
  echo "</div>\n";
  echo "</div>\n";
  
  // Flush this task's output
  ob_end_flush();
  flush();
}
