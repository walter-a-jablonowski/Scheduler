<?php

function logTask( array $data): void
{
  $log = date('Y-m-d H:i:s') . " Task completed\n";
  $config = $data['config'];

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

  $log .= "Time:       {$data['time']}s\n";

  // For URL tasks
  if( $config['type'] === 'URL')
  {
    $log .= "HTTP Code:  {$data['http_code']}\n";
    $log .= "Response:   " . substr($data['response'], 0, 100) . (strlen($data['response']) > 100 ? '...' : '') . "\n";
  }
  // For Script tasks
  else if( $config['type'] === 'Script')
  {
    $log .= "Output:     " . substr($data['output'], 0, 100) . (strlen($data['output']) > 100 ? '...' : '') . "\n";
    if( isset($data['return']))
      $log .= "Return:     " . print_r($data['return'], true) . "\n";
  }

  $log .= "\n";
  file_put_contents( __DIR__ . '/scheduler.log', $log, FILE_APPEND);
}

?>
