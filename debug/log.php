<?php

$log = date('Y-m-d H:i:s') . " Task completed\n";
$log .= "Type:       $type\n";
$log .= "Name:       $name\n";
$log .= "Interval:   $interval\n";
$log .= "Time:       {$time}s\n";

if( !empty($args))
{
  $log .= "Args:\n";
  foreach( $args as $key => $value)
    $log .= "  $key: $value\n";
}

if( $likeliness < 100)
  $log .= "Likeliness: $likeliness%\n";

// For URL tasks
if( $type === 'URL')
{
  $log .= "HTTP Code:  $http_code\n";
  $log .= "Response:   " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '') . "\n";
}
// For Script tasks
else if( $type === 'Script')
{
  $log .= "Output:     " . substr($output, 0, 100) . (strlen($output) > 100 ? '...' : '') . "\n";
  if( isset($return))
    $log .= "Return:     " . print_r($return, true) . "\n";
}

$log .= "\n";
file_put_contents( __DIR__ . '/scheduler.log', $log, FILE_APPEND);

?>
