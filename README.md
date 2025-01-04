# Scheduler

All in one task scheduler: run a single script in a scheduler that runs multiple tasks based on configurable time intervals. Also supports advanced features like likeliness of running.

## Installation

```bash
composer install
```

## Configuration

```yaml
scheduler:

  - name:       quick_sync
    type:       URL
    url:        http://example.com/quick-sync
    args:       ["param1=value1", "param2=value2"]
    interval:   5min  # available: 5min, 10min, 30min, hourly, daily, weekly, monthly
    likeliness: 75    # 75% chance of running when due

  - name:       daily_backup
    type:       URL
    url:        http://example.com/daily-backup
    interval:   daily

  # - name:     local_cleanup
  #   type:     Script
  #   url:      /path/to/cleanup.php
  #   args:     ["arg1", "arg2"]
  #   interval: daily
```

## Usage

```php
$config = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json');
$scheduler->run();
```

## Configuration Options

- `name`: Unique identifier for the task
- `type`: Type of task ('URL' or 'Script')
- `url`: The target URL for URL tasks or script path for Script tasks
- `args`: (Optional) For URL tasks: Query parameters to append to the URL. For Script tasks: Arguments available in the script
- `interval`: Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due. If not set, task always runs when due
