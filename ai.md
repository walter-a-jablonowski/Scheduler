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

 --

In config.yml add a type field per scheduler task. Types are 'URL' and 'Script'. In Scheduler class in the run method add a switch case for each type below shouldRunTask. We already have he code for the url case. Add the script case which is a of anonym function to have a new scope and in there perform a require.

 --

Rename the field url to 'name', update the code where needed. Also add a field 'args' that may be present or missing. it cointains multiple args that are attached to the query string of the url in Scheuler class fpr type url. For type script make it usable in the anonym function.
