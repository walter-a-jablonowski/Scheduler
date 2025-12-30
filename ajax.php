<?php
header('Content-Type: application/json');

require_once 'lib/TaskManager.php';

$taskManager = new TaskManager();

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
  
  switch( $action )
  {
    case 'list':
      echo json_encode(['success' => true, 'tasks' => $taskManager->loadTasks()]);
      break;

    case 'get':
      $index = (int)($_GET['index'] ?? -1);
      $task = $taskManager->getTask($index);
      
      if( $task !== null )
        echo json_encode(['success' => true, 'task' => $task]);
      else
        echo json_encode(['success' => false, 'error' => 'Task not found']);
      break;

    default:
      echo json_encode(['success' => false, 'error' => 'Invalid action']);
  }
}
elseif( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
  
  $input = file_get_contents('php://input');
  $data  = json_decode($input, true);
  
  if( ! $data ) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
  }

  $action = $data['action'] ?? null;

  switch( $action )
  {
    case 'create':
      $task = $data['task'] ?? [];
      $errors = $taskManager->validateTask($task);
      
      if( empty($errors) ) {
        $success = $taskManager->createTask($task);
        echo json_encode(['success' => $success]);
      }
      else
        echo json_encode(['success' => false, 'errors' => $errors]);
      break;

    case 'update':
      $index = (int)($data['index'] ?? -1);
      $task = $data['task'] ?? [];
      $errors = $taskManager->validateTask($task);
      
      if( empty($errors) ) {
        $success = $taskManager->updateTask($index, $task);
        echo json_encode(['success' => $success]);
      }
      else
        echo json_encode(['success' => false, 'errors' => $errors]);
      break;

    case 'delete':
      $index = (int)($data['index'] ?? -1);
      $success = $taskManager->deleteTask($index);
      echo json_encode(['success' => $success]);
      break;

    case 'reorder':
      $order = $data['order'] ?? [];
      $success = $taskManager->reorderTasks($order);
      echo json_encode(['success' => $success]);
      break;

    default:
      echo json_encode(['success' => false, 'error' => 'Invalid action']);
  }
}
else {
  echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
