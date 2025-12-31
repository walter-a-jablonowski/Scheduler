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
  <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
</head>
<body>
  
  <header class="header">
    <h3>Scheduler Tasks</h3>
    <div class="header-buttons">
      <button id="btnLog" class="btn-log">Log</button>
      <div class="dropdown-wrapper">
        <button id="btnNew" class="btn-new">New <span class="arrow-down">▼</span></button>
        <div id="dropdownMenu" class="dropdown-menu">
          <button class="dropdown-item" data-action="new-task">New Task</button>
          <button class="dropdown-item" data-action="new-group">New Group</button>
        </div>
      </div>
    </div>
  </header>

  <div class="container">
    
    <div class="task-list-panel">
      <div id="taskList" class="task-list task-list-desktop">
        <?php if( ! empty($tasksGrouped) ): ?>
          <?php 
          $globalIndex = 0;
          foreach( $tasksGrouped as $groupName => $tasks ):
            if( ! is_array($tasks) )
              continue;
          ?>
            <div class="group-header-wrapper">
              <div class="group-header-text"><?= htmlspecialchars($groupName) ?></div>
              <button class="btn-delete-group" data-group="<?= htmlspecialchars($groupName) ?>" title="Delete group">×</button>
            </div>
            <?php foreach( $tasks as $task ): ?>
              <div class="task-item" data-index="<?= $globalIndex ?>">
                <div class="drag-handle">⋮⋮</div>
                <div class="task-info">
                  <div class="task-name"><?= htmlspecialchars($task['name'] ?? '') ?></div>
                  <div class="task-meta">
                    <span class="task-type"><?= htmlspecialchars($task['type'] ?? '') ?></span>
                    <span class="task-interval"><?= htmlspecialchars($task['interval'] ?? '') ?><?php if( isset($task['likeliness']) && $task['likeliness'] != 100 ): ?> <?= htmlspecialchars($task['likeliness']) ?>%<?php endif; ?></span>
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
      
      <div id="taskListMobile" class="task-list task-list-mobile">
        <?php if( ! empty($tasksGrouped) ): ?>
          <?php 
          $globalIndex = 0;
          foreach( $tasksGrouped as $groupName => $tasks ):
            if( ! is_array($tasks) )
              continue;
          ?>
            <div class="group-header-wrapper-mobile">
              <div class="group-header-text"><?= htmlspecialchars($groupName) ?></div>
              <button class="btn-delete-group" data-group="<?= htmlspecialchars($groupName) ?>" title="Delete group">×</button>
            </div>
            <?php foreach( $tasks as $task ): ?>
              <div class="task-item-mobile" data-index="<?= $globalIndex ?>">
                <div class="drag-handle">⋮⋮</div>
                <div class="task-info-mobile">
                  <div class="task-name"><?= htmlspecialchars($task['name'] ?? '') ?></div>
                  <div class="task-meta-mobile">
                    <span class="task-type"><?= htmlspecialchars($task['type'] ?? '') ?></span>
                    <span class="task-interval"><?= htmlspecialchars($task['interval'] ?? '') ?><?php if( isset($task['likeliness']) && $task['likeliness'] != 100 ): ?> <?= htmlspecialchars($task['likeliness']) ?>%<?php endif; ?></span>
                  </div>
                  <?php if( ! empty($task['comment']) ): ?>
                    <div class="task-comment"><?= htmlspecialchars($task['comment']) ?></div>
                  <?php endif; ?>
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
      <div id="modalEditor" class="task-editor"></div>
    </div>
  </div>
  
  <div id="logModal" class="modal-overlay log-modal">
    <div class="modal-content log-modal-content">
      <button class="modal-close">×</button>
      <div class="log-header">Log File</div>
      <pre id="logContent" class="log-content"></pre>
    </div>
  </div>
  
  <div id="confirmModal" class="modal-overlay confirm-modal">
    <div class="modal-content confirm-modal-content">
      <h3 id="confirmTitle">Confirm Action</h3>
      <p id="confirmMessage">Are you sure?</p>
      <div class="form-actions">
        <button class="btn-confirm" id="btnConfirmAction">Confirm</button>
        <button class="btn-cancel" id="btnCancelAction">Cancel</button>
      </div>
    </div>
  </div>

  <script src="controller.js?v=<?= filemtime('controller.js') ?>"></script>
</body>
</html>
