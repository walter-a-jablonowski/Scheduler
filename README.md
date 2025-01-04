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
    args:       
      action:   sync
      mode:     quick
    interval:   5min  # available: 5min, 10min, 30min, hourly, daily, weekly, monthly (5sec, 10sec used for debugging)
    likeliness: 75    # 75% chance of running when due

  - type:       URL
    name:       http://example.com/daily-backup
    interval:   daily

  - type:     Script
    name:     /path/to/cleanup.php
    args:     
      logLevel: debug
      mode:     full
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
- `args`:       (Optional) Named arguments (URL: converted to query parameters, Script: available as variables)
- `interval`:   Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due
- `callback`:   (Optional) Path to a PHP script that will be called after task execution
  - Available variables in callback script:
    ```php
    $config;    // Task configuration (type, name, args, interval, likeliness)
    $time;      // Execution time in seconds

    // For URL tasks
    $response;  // Response body
    $http_code; // HTTP status code

    // For Script tasks
    $output;    // Captured output
    $return;    // Return value if set
    ```
