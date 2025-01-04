# Scheduler

All in one task scheduler: run a single script in a scheduler that runs multiple tasks based on configurable time intervals. Also supports advanced features like likeliness of running.

## Installation

```bash
composer install
```

## Configuration

```yaml

scheduler:

  - type:       URL
    name:       http://example.com/quick-sync
    args:       ["param1=value1", "param2=value2"]
    interval:   5min  # available: 5min, 10min, 30min, hourly, daily, weekly, monthly (5sec, 10sec used for debugging)
    likeliness: 75    # 75% chance of running when due

  - type:       URL
    name:       http://example.com/daily-backup
    interval:   daily

  - type:     Script
    name:     /path/to/cleanup.php
    args:     ["arg1", "arg2"]  # TASK: named
    interval: daily
```

## Usage

```php
$config = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json');
$scheduler->run();
```

## Configuration Options

- `type`:       Type of task ('URL' or 'Script')
- `name`:       Full URL without query parameters (URL tasks) or full path to the script
- `args`:       (Optional) Arguments (or url query parameters)
- `interval`:   Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due
- `callback`:   (Optional) Path to a PHP script that will be called after task execution
  - Type URL
    ```php
    $response;   // Response body from the URL
    $http_code;  // HTTP status code
    $time;       // Run time
    $url;        // Full URL that was called (including query parameters)
    ```
  - Type Script
    ```php
    $output;     // Captured output from the script
    $return;     // Value of $return variable if set in the script
    ```
