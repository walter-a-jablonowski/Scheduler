<?php

echo "Script running with args:\n";
echo "param1: " . $args['param1'] . "\n";
echo "param2: " . $args['param2'] . "\n";

$return = [
  'status' => 'success',
  'timestamp' => time()
];
