# Scheduler

A flexible task scheduler that supports various time intervals from 5 minutes to monthly

## Installation

```bash
composer install
```

## Configuration

```yaml

scheduler:

  - name:       quick_sync
    url:        http://example.com/quick-sync
    interval:   5min  # available: 5min, 10min, 30min, hourly, daily, weekly, monthly
    likeliness: 75    # 75% chance of running when due

  - name:       daily_backup
    url:        http://example.com/daily-backup
    interval:   daily
```

## Usage

```php

$config = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json');
$scheduler->run();
```

## Configuration Options

- `name`: Unique identifier for the task
- `url`: The URL to call when the task is due
- `interval`: Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due. If not set, task always runs when due
