I am making a PHP scheluler script that can run multiple tasks (scripts) based on defined time intervals. Instead of running multiple tasks in a scheduler app, I can just add the single script and it will run all tasks at once.

Time intervals

- once per hour
- once per day
- once per week on monday
- once per month

We use Symfony yaml for the task definition. A task consists of a name, a url for the script and a time interval.

We need to have a cache file that saves when the last run was for each task. When the scheuler script runs, it checks for tasks to run based the last run time plus the defined interval. If the current time is equal or greater run the task. If for some reason the scheduler app wasn't running last time and a task therefore was skiped multiple times, run it only once.

The list of time intervals might be extended later, so I'd suggest that we calculate the interval in seconds and add it to the last run time.

Implement the scheduler script as a class.

Indent all codes with 2 spaces and put the { on the next line. Write no space behind conditions of loop but the bracket and then a space like "if( ..."
