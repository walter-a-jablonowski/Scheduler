<?php

$log = date('Y-m-d H:i:s') . " Status check completed:\n";
$log .= "HTTP Code: $http_code\n";
$log .= "Response: $response\n";
$log .= "Time: {$time}s\n\n";

file_put_contents('debug/status-check.log', $log, FILE_APPEND);

?>
