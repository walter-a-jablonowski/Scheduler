<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class TaskManager
{
  private $configFile = 'config.yml';
  private $dataFile;

  public function __construct()
  {
    $config = $this->loadConfig();
    $this->dataFile = $config['dataFile'] ?? 'data/tasks.yml';
  }

  private function loadConfig() : array
  {
    if( ! file_exists($this->configFile) )
      return [];
    
    return Yaml::parseFile($this->configFile);
  }

  public function loadTasks() : array
  {
    if( ! file_exists($this->dataFile) )
      return [];
    
    $data = Yaml::parseFile($this->dataFile);
    if( ! is_array($data) )
      return [];
    
    return $this->flattenGroups($data);
  }

  public function loadTasksGrouped() : array
  {
    if( ! file_exists($this->dataFile) )
      return [];
    
    $data = Yaml::parseFile($this->dataFile);
    if( ! is_array($data) )
      return [];
    
    if( $this->isGroupedStructure($data) )
      return $data;
    
    return ['default' => $data];
  }

  private function isGroupedStructure( array $data ) : bool
  {
    if( empty($data) )
      return false;
    
    $firstKey = array_key_first($data);
    $firstValue = $data[$firstKey];
    
    if( is_numeric($firstKey) && is_array($firstValue) && isset($firstValue['type']) )
      return false;
    
    if( is_string($firstKey) && is_array($firstValue) && isset($firstValue[0]) )
      return true;
    
    return false;
  }

  private function flattenGroups( array $data ) : array
  {
    $flattened = [];
    
    if( $this->isGroupedStructure($data) ) {
      foreach( $data as $groupName => $tasks ) {
        if( is_array($tasks) ) {
          foreach( $tasks as $task ) {
            if( is_array($task) && isset($task['type']) )
              $flattened[] = $task;
          }
        }
      }
    }
    else {
      foreach( $data as $task ) {
        if( is_array($task) && isset($task['type']) )
          $flattened[] = $task;
      }
    }
    
    return $flattened;
  }

  public function saveTasks( array $tasks ) : bool
  {
    $dir = dirname($this->dataFile);
    if( ! is_dir($dir) )
      mkdir($dir, 0755, true);
    
    $grouped = $this->rebuildGroups($tasks);
    $yaml = Yaml::dump($grouped, 4, 2);
    return file_put_contents($this->dataFile, $yaml) !== false;
  }
  
  private function rebuildGroups( array $flatTasks ) : array
  {
    $originalData = [];
    if( file_exists($this->dataFile) )
      $originalData = Yaml::parseFile($this->dataFile);
    
    if( ! $this->isGroupedStructure($originalData) )
      return $flatTasks;
    
    $groupNames = array_keys($originalData);
    $grouped = [];
    
    foreach( $groupNames as $groupName )
      $grouped[$groupName] = [];
    
    $groupIndex = 0;
    $tasksPerGroup = ceil(count($flatTasks) / count($groupNames));
    
    foreach( $flatTasks as $i => $task ) {
      $currentGroup = $groupNames[$groupIndex];
      $grouped[$currentGroup][] = $task;
      
      if( count($grouped[$currentGroup]) >= $tasksPerGroup && $groupIndex < count($groupNames) - 1 )
        $groupIndex++;
    }
    
    return $grouped;
  }

  public function getTask( int $index ) : ?array
  {
    $tasks = $this->loadTasks();
    return $tasks[$index] ?? null;
  }

  public function updateTask( int $index, array $taskData ) : bool
  {
    $tasks = $this->loadTasks();
    
    if( ! isset($tasks[$index]) )
      return false;
    
    $type = $taskData['type'] ?? $tasks[$index]['type'] ?? null;
    
    if( $type === 'Command' || $type === 'Process' ) {
      unset($taskData['url']);
      unset($taskData['file']);
    }
    elseif( $type === 'URL' ) {
      unset($taskData['command']);
      unset($taskData['file']);
      unset($taskData['workingDir']);
    }
    elseif( $type === 'Script' ) {
      unset($taskData['command']);
      unset($taskData['url']);
    }
    
    $tasks[$index] = array_merge($tasks[$index], $taskData);
    
    if( $type === 'Command' || $type === 'Process' ) {
      unset($tasks[$index]['url']);
      unset($tasks[$index]['file']);
    }
    elseif( $type === 'URL' ) {
      unset($tasks[$index]['command']);
      unset($tasks[$index]['file']);
      unset($tasks[$index]['workingDir']);
    }
    elseif( $type === 'Script' ) {
      unset($tasks[$index]['command']);
      unset($tasks[$index]['url']);
    }
    
    return $this->saveTasks($tasks);
  }

  public function createTask( array $taskData ) : bool
  {
    $originalData = [];
    if( file_exists($this->dataFile) )
      $originalData = Yaml::parseFile($this->dataFile);
    
    if( $this->isGroupedStructure($originalData) ) {
      $groupNames = array_keys($originalData);
      $lastGroup = end($groupNames);
      $originalData[$lastGroup][] = $taskData;
      
      $dir = dirname($this->dataFile);
      if( ! is_dir($dir) )
        mkdir($dir, 0755, true);
      
      $yaml = Yaml::dump($originalData, 4, 2);
      return file_put_contents($this->dataFile, $yaml) !== false;
    }
    else {
      $tasks = $this->loadTasks();
      $tasks[] = $taskData;
      return $this->saveTasks($tasks);
    }
  }

  public function deleteTask( int $index ) : bool
  {
    $originalData = [];
    if( file_exists($this->dataFile) )
      $originalData = Yaml::parseFile($this->dataFile);
    
    if( $this->isGroupedStructure($originalData) ) {
      $flatIndex = 0;
      $found = false;
      
      foreach( $originalData as $groupName => &$tasks ) {
        if( ! is_array($tasks) )
          continue;
        
        foreach( $tasks as $taskIndex => $task ) {
          if( $flatIndex === $index ) {
            unset($tasks[$taskIndex]);
            $tasks = array_values($tasks);
            $found = true;
            break 2;
          }
          $flatIndex++;
        }
      }
      
      if( ! $found )
        return false;
      
      $dir = dirname($this->dataFile);
      if( ! is_dir($dir) )
        mkdir($dir, 0755, true);
      
      $yaml = Yaml::dump($originalData, 4, 2);
      return file_put_contents($this->dataFile, $yaml) !== false;
    }
    else {
      $tasks = $this->loadTasks();
      
      if( ! isset($tasks[$index]) )
        return false;
      
      array_splice($tasks, $index, 1);
      return $this->saveTasks($tasks);
    }
  }

  public function reorderTasks( array $newOrder ) : bool
  {
    $originalData = [];
    if( file_exists($this->dataFile) )
      $originalData = Yaml::parseFile($this->dataFile);
    
    $tasks = $this->loadTasks();
    $reordered = [];
    
    foreach( $newOrder as $index ) {
      if( isset($tasks[$index]) )
        $reordered[] = $tasks[$index];
    }
    
    if( $this->isGroupedStructure($originalData) ) {
      $groupNames = array_keys($originalData);
      $grouped = [];
      
      foreach( $groupNames as $groupName )
        $grouped[$groupName] = [];
      
      $groupIndex = 0;
      $tasksPerGroup = ceil(count($reordered) / count($groupNames));
      
      foreach( $reordered as $i => $task ) {
        $currentGroup = $groupNames[$groupIndex];
        $grouped[$currentGroup][] = $task;
        
        if( count($grouped[$currentGroup]) >= $tasksPerGroup && $groupIndex < count($groupNames) - 1 )
          $groupIndex++;
      }
      
      $dir = dirname($this->dataFile);
      if( ! is_dir($dir) )
        mkdir($dir, 0755, true);
      
      $yaml = Yaml::dump($grouped, 4, 2);
      return file_put_contents($this->dataFile, $yaml) !== false;
    }
    else {
      return $this->saveTasks($reordered);
    }
  }

  public function validateTask( array $task ) : array
  {
    $errors = [];

    if( empty($task['type']) )
      $errors[] = 'Type is required';
    elseif( ! in_array($task['type'], ['Command', 'Process', 'URL', 'Script']) )
      $errors[] = 'Invalid type';

    if( empty($task['name']) )
      $errors[] = 'Name is required';

    if( $task['type'] === 'Command' || $task['type'] === 'Process' ) {
      if( empty($task['command']) )
        $errors[] = 'Command is required';
    }
    elseif( $task['type'] === 'URL' ) {
      if( empty($task['url']) )
        $errors[] = 'URL is required';
      elseif( ! filter_var($task['url'], FILTER_VALIDATE_URL) )
        $errors[] = 'Invalid URL format';
    }
    elseif( $task['type'] === 'Script' ) {
      if( empty($task['file']) )
        $errors[] = 'Script file is required';
    }

    if( empty($task['interval']) )
      $errors[] = 'Interval is required';
    elseif( ! in_array($task['interval'], ['5sec', '10sec', '5min', '10min', '30min', 'hourly', 'daily', 'weekly', 'monthly']) )
      $errors[] = 'Invalid interval';

    if( isset($task['likeliness']) ) {
      $likeliness = (int)$task['likeliness'];
      if( $likeliness < 1 || $likeliness > 100 )
        $errors[] = 'Likeliness must be between 1 and 100';
    }

    if( ! empty($task['startDate']) ) {
      $date = \DateTime::createFromFormat('Y-m-d H:i:s', $task['startDate']);
      if( ! $date || $date->format('Y-m-d H:i:s') !== $task['startDate'] )
        $errors[] = 'Invalid startDate format (use YYYY-MM-DD HH:MM:SS)';
    }

    return $errors;
  }
}
