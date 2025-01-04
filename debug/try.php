<?php

use Symfony\Component\Yaml\Yaml;

require_once 'vendor/autoload.php';
require_once '../Scheduler.php';

$scheduler = new Scheduler(
  Yaml::parseFile('config.yml')['scheduler'],
  'scheduler-cache.json'
);

$scheduler->run();
