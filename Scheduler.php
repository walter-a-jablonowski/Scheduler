<?php

class Scheduler
{
  private array  $config;
  private string $cacheFile;
  private array  $placeholders;
  private        $callback;
  
  private array  $cache = [];
  
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
    $this->config       = $config;
    $this->cacheFile    = $cacheFile;
    $this->placeholders = $placeholders;
    $this->callback     = $callback;

    if( file_exists( $this->cacheFile ))
      $this->cache = json_decode( file_get_contents($this->cacheFile), true) ?? [];

    // Validate tasks
    
    foreach( $this->config as $task )
      $this->validateTask($task);

    // Clear cache entry if startDate changed in config

    foreach( $this->config as $task )
    {
      if( ! isset($task['name']))
        continue;

      $taskName = $task['name'];
      
      if( isset($this->cache[$taskName]))
      {
        $cacheStartDate  = isset( $this->cache[$taskName]['startDate']) ? $this->cache[$taskName]['startDate'] : null;
        $configStartDate = isset( $task['startDate']) ? $task['startDate'] : null;
          
        if( $cacheStartDate !== $configStartDate)
        {
          unset($this->cache[$taskName]);
          file_put_contents(
            $this->cacheFile,
            json_encode( $this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
          );
        }
      }
    }
  }

  public function run() : void
  {
    foreach( $this->config as $task )
    {
      // Decide run and run tasks per type

      if( $this->shouldRunTask($task))
      {
        switch( $task['type'])
        {
          case 'URL':
          case 'Script':
          case 'Process':
          case 'Command':
            $method = 'run' . $task['type'];
            $this->$method( $task );
            break;
          default:
            throw new Exception("Invalid task type: $task[type]");
        }
        
        // Update cache with last run time and startDate

        $this->cache[ $task['name']] = [
          'lastRun'   => (new DateTime())->format('Y-m-d H:i:s'),
          'startDate' => isset($task['startDate']) ? $task['startDate'] : null
        ];
        
        file_put_contents( 
          $this->cacheFile, 
          json_encode( $this->cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
      }
    }
  }

  private function shouldRunTask( array $task ) : bool
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
      $likely = mt_rand(1, 100) <= $likeliness;
    }

    if( ! $lastRun )
      return $likely;  // trie for first run if behind startDate, also use likliness

    // Check interval

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

  private function runCommand( array $task ) : void 
  {
    $command = $task['command'];
    $args    = '';
    $time    = microtime(true);

    if( isset($task['args']) && is_array($task['args']))
    {
      foreach( $task['args'] as $key => $value )
      {
        if( is_numeric($key))
          $args .= " $value";  // for simple args like ['-v', '-f']
        else
          $args .= " $value";  // for named args like ['format' => '/B']
      }
    }
    
    $fullCommand = "$command$args";
    
    // error_log("Command: $fullCommand");  // DEBUG

    $output    = [];
    $returnVar = 0;
    exec( $fullCommand, $output, $returnVar);
    
    if( $this->callback )
    {
      if( $returnVar !== 0 )
      {
        ($this->callback)('error', [
          'error'   => "Command failed with code $returnVar",
          'output'  => implode("\n", $output)
        ], $time, $task);
      }
      else
      {
        ($this->callback)('success', [
          'output'  => implode("\n", $output)
        ], $time, $task);
      }
    }
  }

  private function runProcess( array $task ) : void 
  {
    $command = $task['command'];
    $args    = '';
    $time    = microtime(true);

    if( isset($task['args']) && is_array($task['args']))
    {
      foreach( $task['args'] as $key => $value )
      {
        if( is_numeric($key))
          $args .= " $value";  // for simple args like ['-v', '-f']
        else
          $args .= " $value";  // for named args like ['format' => '/B']
      }
    }
    
    $fullCommand = "$command$args";

    // error_log("Process: $fullCommand");  // DEBUG

    try 
    {
      if( substr(php_uname(), 0, 7) == "Windows" )
        pclose( popen("start /B " . $fullCommand, "r"));
      else
        exec( $fullCommand . " > /dev/null 2>&1 &");

      if( $this->callback )
      {
        ($this->callback)('success', [
          'message' => "Process started: $fullCommand"
        ], $time, $task);
      }
    }
    catch( Exception $e ) 
    {
      if( $this->callback )
      {
        ($this->callback)('error', [
          'error'   => $e->getMessage(),
          'command' => $fullCommand
        ], $time, $task);
      }
    }
  }

  private function runURL( array $task ) : void
  {
    $startTime = microtime(true);
    
    // Make url
    
    $url = $task['url'];

    if( isset($task['args']) && is_array($task['args']))
    {
      $query = [];
      foreach( $task['args'] as $key => $value)
        $query[] = urlencode($key) . '=' . urlencode($value);
        
      if( ! empty($query))
        $url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $query);
    }

    // Call the url

    $ch = curl_init( $url);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec( $ch );
    $error    = curl_error( $ch );
    $info     = curl_getinfo( $ch );
    curl_close( $ch );

    if( ! $response && $error )  // do this here ins of construct
      throw new Exception("CURL error: $error");

    $time = microtime(true) - $startTime;

    // Callback

    if( $this->callback )
    {
      if( $error || $info['http_code'] >= 400 )
        ($this->callback)('error', [
          'error'     => $error ?: 'HTTP error ' . $info['http_code'],
          'response'  => $response,
          'http_code' => $info['http_code']
        ], $time, $task);
      else
        ($this->callback)('success', [
          'response'  => $response,
          'http_code' => $info['http_code']
        ], $time, $task);
    }
  }
  
  private function runScript( array $task ) : void
  {
    $file      = $task['file'];
    $result    = null;
    $startTime = microtime(true);

    // Placeholders
    
    $file = $task['file'];
 
    foreach( $this->placeholders as $placeholder => $value) 
      $file = str_replace('{' . $placeholder . '}', $value, $file);

    // Validate

    if( ! file_exists($file))  // do this here ins of construct
      throw new Exception("Script file not found: $file");
    if( ! is_readable($file))
      throw new Exception("Script file not readable: $file");

    // Call the script
    
    try 
    {
      ( function() use ($file, $task, &$result) {  // new scope

        extract(['args' => isset($task['args']) ? $task['args'] : []], EXTR_SKIP);
        ob_start();

        require $file;

        $result = [
          'output' => ob_get_clean(),
          'return' => isset($return) ? $return : null
        ];

      })();

      // Success callback

      $time = microtime(true) - $startTime;

      if( $this->callback )
        ($this->callback)('success', ['output' => $result['output'], 'return' => $result['return']],
          $time, $task
        );
    }
    catch( Exception $e )
    {
      $time = microtime(true) - $startTime;
      
      if( $this->callback )
        ($this->callback)('error', ['error' => $e->getMessage()],
          $time, $task
        );
    }
  }
  
  private function validateTask( array $task ) : void
  {
    if( ! isset( $task['type'], $task['name'], $task['interval']))
      throw new Exception('Missing required fields (type, name, interval): ' . json_encode($task));
    
    // type
    if( ! in_array($task['type'], ['URL', 'Script', 'Command', 'Process']))
      throw new Exception("Invalid task type: {$task['type']}");

    // Type specific validation
    if( $task['type'] === 'URL')
    {
      if( ! isset($task['url']))
        throw new Exception('URL field is required for URL type tasks');
      
      if( ! filter_var($task['url'], FILTER_VALIDATE_URL))
        throw new Exception("Invalid URL format: {$task['url']}");
    }
    else if( $task['type'] === 'Script')
    {
      if( ! isset($task['file']))
        throw new Exception('File field is required for Script type tasks');
    }
    else if( $task['type'] === 'Command' || $task['type'] === 'Process')
    {
      if( ! isset($task['command']))
        throw new Exception('Command field is required for Command and Process type tasks');
    }

    // startDate
    if( isset($task['startDate']))
    {
      $startDate = DateTime::createFromFormat('Y-m-d H:i:s', $task['startDate']);
      if( ! $startDate)
        throw new Exception("Invalid startDate format for task {$task['name']}, use YYYY-MM-DD HH:MM:SS");
    }

    // interval
    if( ! in_array($task['interval'], array_keys(self::INTERVALS)))
      throw new Exception("Invalid interval: {$task['interval']}");

    // likeliness
    if( isset($task['likeliness']))
    {
      $likeliness = (int) $task['likeliness'];
      if( $likeliness < 1 || $likeliness > 100)
        throw new Exception("Likeliness must be between 1 and 100 for task {$task['name']}");
    }
  }
}

?>
