<?php

$log = date('Y-m-d H:i:s') . " Task completed\n";

$log .= "Config:\n";
$log .= "  Type:       {$config['type']}\n";
$log .= "  Name:       {$config['name']}\n";
$log .= "  Interval:   {$config['interval']}\n";

if( !empty($config['args']))
{
  $log .= "  Args:\n";
  foreach( $config['args'] as $key => $value)
    $log .= "    $key: $value\n";
}

if( $config['likeliness'] < 100)
  $log .= "  Likeliness: {$config['likeliness']}%\n";

$log .= "Time:       {$time}s\n";

// For URL tasks
if( $config['type'] === 'URL')
{
  $log .= "HTTP Code:  $http_code\n";
  $log .= "Response:   " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '') . "\n";
}
// For Script tasks
else if( $config['type'] === 'Script')
{
  $log .= "Output:     " . substr($output, 0, 100) . (strlen($output) > 100 ? '...' : '') . "\n";
  if( isset($return))
    $log .= "Return:     " . print_r($return, true) . "\n";
}

$log .= "\n";
file_put_contents( __DIR__ . '/scheduler.log', $log, FILE_APPEND);

?>
