<?php

class Scheduler
{
  private string $cacheFile;
  private array  $config;
  private array  $cache;
  
  private const INTERVALS = [
    '5min'    => 300,
    '10min'   => 600,
    '30min'   => 1800,
    'hourly'  => 3600,
    'daily'   => 86400,
    'weekly'  => 604800,
    'monthly' => 2592000
  ];

  public function __construct( array $config, string $cacheFile)
  {
    $this->config    = $config;
    $this->cacheFile = $cacheFile;

    if( file_exists( $this->cacheFile ))
      $this->cache = json_decode( file_get_contents($this->cacheFile), true) ?? [];
    else
      $this->cache = [];
  }

  public function run(): void
  {
    foreach( $this->config as $task )
    {
      if( ! isset($task['name'], $task['interval']))
        throw new Exception('Invalid task configuration: ' . json_encode($task));

      if( $this->shouldRunTask($task))
      {
        if( !isset($task['type']))
          throw new Exception('Task type is not specified: ' . $task['name']);

        if( ! isset($task['name']))
          throw new Exception('Task name/path is not specified: ' . $task['name']);

        switch( $task['type'])
        {
          case 'URL':
            $url = $task['name'];
            
            if( isset($task['args']) && is_array($task['args']))
              $url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $task['args']);
            
            $ch = curl_init( $url);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec( $ch );
            curl_close( $ch );
            break;

          case 'Script':

            ( function() use ($task) {
              extract(['args' => isset($task['args']) ? $task['args'] : []], EXTR_SKIP);
              require $task['name'];

            })();
          
            break;

          default:
            throw new Exception('Invalid task type: ' . $task['type']);
        }

        $this->cache[ $task['name']] = (new DateTime())->format('Y-m-d H:i:s');
        file_put_contents( 
          $this->cacheFile, 
          json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
      }
    }
  }

  private function shouldRunTask( array $task ): bool
  {
    $now     = new DateTime();
    $lastRun = isset( $this->cache[$task['name']]) 
      ? DateTime::createFromFormat('Y-m-d H:i:s', $this->cache[$task['name']])
      : null;

    $likely = true; 

    if( isset($task['likeliness']))
    {
      $likeliness = (int) $task['likeliness'];
      
      if( $likeliness < 1 || $likeliness > 100)
        throw new Exception("Likeliness must be between 1 and 100: $task[name]");
        
      $likely = mt_rand(1, 100) <= $likeliness;
    }

    if( ! $lastRun )
      return $likely;

    $interval = $task['interval'];
    $nextRun  = clone $lastRun;

    switch( $interval )
    {
      case '5min':
        $nextRun->add( new DateInterval('PT5M'));   // PT5M: period of 5 minutes
        break;
      case '10min':
        $nextRun->add( new DateInterval('PT10M'));  // PT10M: period of 10 minutes
        break;
      case '30min':
        $nextRun->add( new DateInterval('PT30M'));  // PT30M: period of 30 minutes
        break;
      case 'hourly':
        $nextRun->add( new DateInterval('PT1H'));   // PT1H: period of 1 hour
        break;
      case 'daily':
        $nextRun->add( new DateInterval('P1D'));    // P1D: period of 1 day
        break;
      case 'weekly':
        // If it's a weekly task, ensure it runs on Monday
        $nextRun->add( new DateInterval('P1W'));    // P1W: period of 1 week
        $nextRun->modify('this monday');
        break;
      case 'monthly':
        $nextRun->add( new DateInterval('P1M'));    // P1M: period of 1 month
        break;
      default:
        throw new Exception("Invalid interval: {$interval}");
    }

    return ($now >= $nextRun) && $likely;
  }
}
