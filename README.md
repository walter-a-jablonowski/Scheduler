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
- `type`: Type of task ('URL' or 'Script')
- `url`: For URL tasks: The URL to call. For Script tasks: Path to the PHP script to execute
- `interval`: Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due. If not set, task always runs when due

## Task Types

### URL Tasks
Tasks that make HTTP requests to specified URLs:

```yaml
scheduler:
  - name: daily_backup
    type: URL
    url: http://example.com/daily-backup
    interval: daily
```

### Script Tasks
Tasks that execute local PHP scripts:

```yaml
scheduler:
  - name: local_cleanup
    type: Script
    url: /path/to/cleanup.php
    interval: daily
```

The script will be executed in its own scope to prevent variable conflicts.
