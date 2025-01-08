<?php

if( ! headers_sent())
{
  header('Content-Type: text/html; charset=utf-8');
  ?>
  <!DOCTYPE html>
  <html>
  <head>
    <style>
      body {
        font-family: monospace;
        background: #f5f5f5;
        padding: 20px;
        margin: 0;
      }
      .task {
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      }
      .task-time {
        color: #666;
        font-size: 0.9em;
      }
      .config {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
      }
      .args {
        margin-left: 20px;
      }
      .result {
        border-left: 3px solid #FFA500;
        padding-left: 10px;
        margin: 10px 0;
      }
      pre {
        margin: 5px 0;
        white-space: pre-wrap;
      }
    </style>
  </head>
  <body>
  <?php
}

function logTask( string $state, array $result, float $time, array $config ): void
{
  echo "<div class='task'>\n";
  echo "<div class='task-time'>" . date('Y-m-d H:i:s') . " Task completed</div>\n";
  
  echo "<div class='config'>\n";
  echo "<strong>Type:</strong> {$config['type']}<br>\n";
  echo "<strong>Name:</strong> {$config['name']}<br>\n";
  echo "<strong>Interval:</strong> {$config['interval']}<br>\n";
  
  if( !empty($config['args']))
  {
    echo "<div class='args'>\n";
    echo "<strong>Args:</strong><br>\n";
    foreach( $config['args'] as $key => $value)
      echo "• $key: $value<br>\n";
    echo "</div>\n";
  }
  
  if( $config['likeliness'] < 100)
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
  else if( $config['type'] === 'Script')
  {
    echo "<strong>Output:</strong><br>\n";
    echo "<pre>" . htmlspecialchars($result['output']) . "</pre>\n";
    if( isset($result['return']))
    {
      echo "<strong>Return:</strong><br>\n";
      echo "<pre>" . htmlspecialchars(print_r($result['return'], true)) . "</pre>\n";
    }
  }
  echo "</div>\n";
  echo "</div>\n";
  
  flush();
}

?>
</body>
</html>
