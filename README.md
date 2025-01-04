# Scheduler

All in one scheduler

- configure a single task in your system's scheduler instead of many (less config work)
- just run a single script that uses this class
- configure multiple tasks in yml (syncable on multiple devices)
- also supports advanced features like running a task based on likeliness

## Installation

```bash
composer install
```

## Sample

```php
$config = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json');
$scheduler->run();
```

**Config**

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

## Fields

- `type`:       Type of task ('URL' or 'Script')
- `name`:       Full URL without query parameters (URL tasks) or full path to the script
- `args`:       (Optional) Named arguments (URL: converted to query parameters, Script: available as variables)
- `startDate`:  (Optional) Start date and time in YYYY-MM-DD HH:MM:SS format, task will only run from this time onwards
  - you may also set this when a task already has been run
- `interval`:   Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due


## Debug out

![alt text](misc/img.png)
