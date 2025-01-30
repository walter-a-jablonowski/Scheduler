
Own process
----------------------------------------------------------

- `exec('start cmd.exe /k your_command');`
  - /k flag keeps the window open
- `pclose(popen('start your_command', 'r'));`
- `shell_exec('start cmd.exe /k "cd /d ' . escapeshellarg($path) . ' && your_command"');`

```bash
// Running a Python script in a new window
exec('start cmd.exe /k "python script.py"');

// Running multiple commands
exec('start cmd.exe /k "cd /d C:\your\path && npm start"');
```

- The working directory can be set using cd /d path
- Use " around paths that contain spaces
