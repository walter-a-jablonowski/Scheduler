# Scheduler

All in one scheduler: configure a single task in your system's scheduler instead of many (less config work)

- just run a single script that uses this class
- configure multiple tasks in yml (syncable on multiple devices)
- also supports advanced features like running a task based on likeliness

```bash
> cd debug
> composer install

> composer require symfony/yaml
```

## Sample

```php
$config    = Yaml::parseFile('config.yml');
$scheduler = new Scheduler( $config['scheduler'], 'cache.json');

try  {
  $scheduler->run();
}
catch( Exception $e ) {
  error_log("Scheduler error: " . $e->getMessage());  // or use your own logging
  exit();
}
```

**Config**

```yaml

scheduler:

  - type:       URL
    name:       http://example.com/quick-sync
    args:       
      action:   sync
      mode:     quick
    interval:   5min  # 5min, 10min, 30min, hourly, daily, weekly, monthly (5sec, 10sec used for debugging)
    likeliness: 75    # 75% chance of running when due

  - type:       URL
    name:       http://example.com/daily-backup
    interval:   daily

  - type:       Script
    name:       /path/to/cleanup.php
    args:     
      logLevel: debug
      mode:     full
    interval:   daily
```

## Fields

- `type`:       Type of task ('URL' or 'Script')
- `name`:       Full URL without query parameters (URL tasks) or full path to the script
- `args`:       (Optional) Named arguments (URL: converted to query parameters, Script: available as variables)
- `startDate`:  (Optional) YYYY-MM-DD HH:MM:SS task will only run from this time onwards (you may edit this at any time)
  - you may also set this when a task already has been run
- `interval`:   Time interval between runs
- `likeliness`: (Optional) Percentage chance (1-100) of running when due


## Debug out

![alt text](misc/img.png)


LICENSE
----------------------------------------------------------

Copyright (C) Walter A. Jablonowski 2024, free under MIT [License](LICENSE)

This app is build upon PHP and free software (see [credits](credits.md))

[Privacy](https://walter-a-jablonowski.github.io/privacy.html) | [Legal](https://walter-a-jablonowski.github.io/imprint.html)
