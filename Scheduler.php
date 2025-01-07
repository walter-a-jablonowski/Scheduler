<?php

class Scheduler
{
  private string $cacheFile;
  private array  $config;
  private array  $cache;
  private array  $placeholders;
  private $callback;
  
  private const INTERVALS = [
    '5sec'    => 5,       // used for debugging
    '10sec'   => 10,      // used for debugging
    '5min'    => 300,
    '10min'   => 600,
    '30min'   => 1800,
    'hourly'  => 3600,
    'daily'   => 86400,
    'weekly'  => 604800,
    'monthly' => 2592000
  ];

  public function __construct( array $config, string $cacheFile, array $placeholders = [], callable $callback = null)
  {
    $this->config      = $config;
    $this->cacheFile   = $cacheFile;
    $this->placeholders = $placeholders;
    $this->callback    = $callback;

    if( file_exists( $this->cacheFile ))
      $this->cache = json_decode( file_get_contents($this->cacheFile), true) ?? [];
    else
      $this->cache = [];

    // Clear cache entry if startDate changed in config

    foreach( $this->config as $task )
    {
      if( ! isset($task['name']))
        continue;

      $taskName = $task['name'];
      
      if( isset($this->cache[$taskName]))
      {
        $cacheStartDate  = isset($this->cache[$taskName]['startDate']) ? $this->cache[$taskName]['startDate'] : null;
        $configStartDate = isset($task['startDate']) ? $task['startDate'] : null;
          
        if( $cacheStartDate !== $configStartDate)
        {
          unset($this->cache[$taskName]);
          file_put_contents(
            $this->cacheFile,
            json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
          );
        }
      }
    }
  }

  public function run(): void
  {
    foreach( $this->config as $task )
    {
      if( ! isset($task['type'], $task['name'], $task['interval']))
        throw new Exception('Invalid task configuration: ' . json_encode($task));

      // Decide run and run tasks per type

      if( $this->shouldRunTask($task))
      {
        switch( $task['type'])
        {
          case 'URL':

            if( ! isset($task['url']))
              throw new Exception('URL field is required for URL type tasks: ' . json_encode($task));

            $startTime = microtime(true);
            $url = $task['url'];
            
            if( isset($task['args']) && is_array($task['args']))
            {
              $query = [];
              foreach( $task['args'] as $key => $value)
                $query[] = urlencode($key) . '=' . urlencode($value);
                
              if( ! empty($query))
                $url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $query);
            }
            
            $ch = curl_init( $url);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec( $ch );
            $info = curl_getinfo( $ch );
            curl_close( $ch );

            $time = microtime(true) - $startTime;

            if( $this->callback)  // for debug use
              
              ($this->callback)([
                'response'  => $response,
                'http_code' => $info['http_code'],
                'time'      => round($time, 3),
                'config' => [
                  'type'       => $task['type'],
                  'name'       => $task['name'],
                  'url'        => $task['url'],
                  'args'       => isset($task['args']) ? $task['args'] : [],
                  'startDate'  => isset($task['startDate']) ? $task['startDate'] : null,
                  'interval'   => $task['interval'],
                  'likeliness' => isset($task['likeliness']) ? $task['likeliness'] : 100
                ]
              ]);

            break;

          case 'Script':

            if( ! isset($task['file']))
              throw new Exception('File field is required for Script type tasks: ' . json_encode($task));

            $result    = null;
            $startTime = microtime(true);
            $file      = $task['file'];

            foreach( $this->placeholders as $placeholder => $value) 
              $file = str_replace('{' . $placeholder . '}', $value, $file);
            
            ( function() use ($file, $task, &$result) {

              extract(['args' => isset($task['args']) ? $task['args'] : []], EXTR_SKIP);
              ob_start();

              require $file;

              $result = [
                'output' => ob_get_clean(),
                'return' => isset($return) ? $return : null
              ];

            })();

            $time = microtime(true) - $startTime;
            
            if( $this->callback)  // for debug use

              ($this->callback)([
                'output' => $result['output'],
                'return' => $result['return'],
                'time'   => round($time, 3),
                'config' => [
                  'type'       => $task['type'],
                  'name'       => $task['name'],
                  'file'       => $file,
                  'args'       => isset($task['args']) ? $task['args'] : [],
                  'startDate'  => isset($task['startDate']) ? $task['startDate'] : null,
                  'interval'   => $task['interval'],
                  'likeliness' => isset($task['likeliness']) ? $task['likeliness'] : 100
                ]
              ]);

            break;

          default:
            throw new Exception('Invalid task type: ' . $task['type']);
        }

        // Update cache with last run time and startDate

        $this->cache[ $task['name']] = [
          'lastRun'   => (new DateTime())->format('Y-m-d H:i:s'),
          'startDate' => isset($task['startDate']) ? $task['startDate'] : null
        ];
        
        file_put_contents( 
          $this->cacheFile, 
          json_encode($this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
      }
    }
  }

  private function shouldRunTask( array $task ): bool
  {
    $now = new DateTime();

    // Check startDate

    if( isset($task['startDate']))
    {
      $startDate = DateTime::createFromFormat('Y-m-d H:i:s', $task['startDate']);
      if( ! $startDate)
        throw new Exception("Invalid startDate format for task $task[name], use YYYY-MM-DD HH:MM:SS");

      if( $now < $startDate)
        return false;
    }

    $lastRun = isset($this->cache[ $task['name']]) 
      ? DateTime::createFromFormat('Y-m-d H:i:s', $this->cache[ $task['name']]['lastRun'])
      : null;

    // Likeliness

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
      case '5sec':
        $nextRun->add( new DateInterval('PT5S'));   // PT5S: period of 5 seconds
        break;
      case '10sec':
        $nextRun->add( new DateInterval('PT10S'));  // PT10S: period of 10 seconds
        break;
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
