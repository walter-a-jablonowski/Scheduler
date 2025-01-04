<?php

$log = date('Y-m-d H:i:s') . " Task completed\n";

// For URL tasks
if( isset($http_code))
{
  $log .= "Type:       URL\n";
  $log .= "URL:        $url\n";
  $log .= "HTTP Code:  $http_code\n";
  $log .= "Time:       {$time}s\n";
  $log .= "Response:   " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '') . "\n";
}
// For Script tasks
else if( isset($output))
{
  $log .= "Type:       Script\n";
  $log .= "Output:     " . substr($output, 0, 100) . (strlen($output) > 100 ? '...' : '') . "\n";
  if( isset($return))
    $log .= "Return:     " . print_r($return, true) . "\n";
}

$log .= "\n";
file_put_contents( __DIR__ . '/scheduler.log', $log, FILE_APPEND);

?>
