<?php
require_once 'lib/TaskManager.php';

$taskManager = new TaskManager();
$tasksGrouped = $taskManager->loadTasksGrouped();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scheduler Task Manager</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  
  <header class="header">
    <h1>Scheduler Tasks</h1>
    <button id="btnNew" class="btn-new">+ New Task</button>
  </header>

  <div class="container">
    
    <div class="task-list-panel">
      <div id="taskList" class="task-list">
        <?php if( ! empty($tasksGrouped) ): ?>
          <?php 
          $globalIndex = 0;
          foreach( $tasksGrouped as $groupName => $tasks ):
            if( ! is_array($tasks) || empty($tasks) )
              continue;
          ?>
            <div class="group-header"><?= htmlspecialchars($groupName) ?></div>
            <?php foreach( $tasks as $task ): ?>
              <div class="task-item" data-index="<?= $globalIndex ?>">
                <div class="drag-handle">⋮⋮</div>
                <div class="task-info">
                  <div class="task-name"><?= htmlspecialchars($task['name'] ?? '') ?></div>
                  <div class="task-meta">
                    <span class="task-type"><?= htmlspecialchars($task['type'] ?? '') ?></span>
                    <span class="task-interval"><?= htmlspecialchars($task['interval'] ?? '') ?></span>
                    <span class="task-comment"><?= htmlspecialchars($task['comment'] ?? '') ?></span>
                  </div>
                </div>
                <button class="btn-delete" data-index="<?= $globalIndex ?>" title="Delete task">×</button>
              </div>
              <?php $globalIndex++; ?>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">No tasks yet. Click "New Task" to create one.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="task-editor-panel">
      <div id="taskEditor" class="task-editor">
        <div class="editor-placeholder">
          Select a task to edit or create a new one
        </div>
      </div>
    </div>

  </div>

  <div id="modalOverlay" class="modal-overlay">
    <div class="modal-content">
      <button class="modal-close">×</button>
      <div id="modalEditor" class="task-editor"></div>
    </div>
  </div>

  <script src="controller.js"></script>
</body>
</html>
