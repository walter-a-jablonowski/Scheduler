const App = {
  currentTaskIndex: null,
  tasks: [],
  draggedElement: null,
  validationErrors: {},

  init()
  {
    this.loadTasks();
    this.attachEventListeners();
    this.setupDragAndDrop();
  },

  attachEventListeners()
  {
    const btnNew = document.getElementById('btnNew');
    const dropdownMenu = document.getElementById('dropdownMenu');
    
    btnNew.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownMenu.classList.toggle('active');
    });
    
    document.addEventListener('click', (e) => {
      if( ! e.target.closest('.dropdown-wrapper') )
        dropdownMenu.classList.remove('active');
    });
    
    document.querySelectorAll('.dropdown-item').forEach(item => {
      item.addEventListener('click', (e) => {
        const action = e.target.dataset.action;
        dropdownMenu.classList.remove('active');
        
        if( action === 'new-task' )
          this.createNewTask();
        else if( action === 'new-group' )
          this.createNewGroup();
      });
    });
    
    this.attachTaskItemListeners();
    this.attachGroupDeleteListeners();

    document.querySelector('.modal-close')?.addEventListener('click', () => this.closeModal());
    document.getElementById('modalOverlay')?.addEventListener('click', (e) => {
      if( e.target.id === 'modalOverlay' )
        this.closeModal();
    });
  },
  
  attachTaskItemListeners()
  {
    document.querySelectorAll('.task-item').forEach(item => {
      if( item.dataset.clickListenersAttached )
        return;
      
      item.dataset.clickListenersAttached = 'true';
      
      item.addEventListener('click', (e) => {
        if( ! e.target.classList.contains('btn-delete') && ! e.target.classList.contains('drag-handle') )
          this.editTask(parseInt(item.dataset.index));
      });
      
      const deleteBtn = item.querySelector('.btn-delete');
      if( deleteBtn ) {
        deleteBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          this.deleteTask(parseInt(deleteBtn.dataset.index));
        });
      }
    });
  },
  
  attachGroupDeleteListeners()
  {
    document.querySelectorAll('.btn-delete-group').forEach(btn => {
      if( btn.dataset.listenerAttached )
        return;
      
      btn.dataset.listenerAttached = 'true';
      
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const groupName = btn.dataset.group;
        this.deleteGroup(groupName);
      });
    });
  },

  setupDragAndDrop()
  {
    const taskList = document.getElementById('taskList');
    
    if( this.dragOverListenerAdded )
      return;
    
    this.dragOverListenerAdded = true;

    taskList.addEventListener('dragover', (e) => {
      e.preventDefault();
      const afterElement = this.getDragAfterElement(taskList, e.clientY);
      const draggable = this.draggedElement;
      
      if( ! draggable )
        return;
      
      if( afterElement == null ) {
        taskList.appendChild(draggable);
      }
      else {
        if( afterElement.classList.contains('group-header') )
          return;
        taskList.insertBefore(draggable, afterElement);
      }
    });
    
    this.attachDragListenersToItems();
  },
  
  attachDragListenersToItems()
  {
    const items = document.querySelectorAll('.task-item');

    items.forEach(item => {
      if( item.dataset.dragListenersAttached )
        return;
      
      item.dataset.dragListenersAttached = 'true';
      
      const handle = item.querySelector('.drag-handle');
      if( ! handle )
        return;
      
      handle.addEventListener('mousedown', (e) => {
        e.stopPropagation();
        item.setAttribute('draggable', 'true');
      });
      
      item.addEventListener('dragstart', (e) => {
        if( item.getAttribute('draggable') !== 'true' ) {
          e.preventDefault();
          return false;
        }
        this.draggedElement = item;
        item.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
      });

      item.addEventListener('dragend', () => {
        item.setAttribute('draggable', 'false');
        item.classList.remove('dragging');
        this.saveOrder();
      });
      
      item.addEventListener('click', (e) => {
        if( e.target.classList.contains('drag-handle') || e.target.closest('.drag-handle') ) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });
  },

  getDragAfterElement(container, y)
  {
    const draggableElements = [...container.querySelectorAll('.task-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;

      if( offset < 0 && offset > closest.offset )
        return { offset: offset, element: child };
      else
        return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  },

  saveOrder()
  {
    const taskList = document.getElementById('taskList');
    const items = [];
    
    Array.from(taskList.children).forEach(child => {
      if( child.classList.contains('task-item') )
        items.push(parseInt(child.dataset.index));
    });

    fetch('ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reorder', order: items })
    })
    .then(response => response.json())
    .then(data => {
      if( data.success ) {
        this.updateTaskIndices();
      }
      else
        this.showSnackbar('Failed to reorder tasks: ' + (data.error || 'Unknown error'), 'error');
    })
    .catch(err => this.showSnackbar('Error: ' + err.message, 'error'));
  },

  updateTaskIndices()
  {
    const items = document.querySelectorAll('.task-item');
    items.forEach((item, index) => {
      item.dataset.index = index;
      const deleteBtn = item.querySelector('.btn-delete');
      if( deleteBtn )
        deleteBtn.dataset.index = index;
    });
  },

  loadTasks()
  {
    fetch('ajax.php?action=list')
      .then(response => response.json())
      .then(data => {
        this.tasks = data.tasks || [];
      })
      .catch(err => console.error('Error loading tasks:', err));
  },

  createNewTask()
  {
    this.currentTaskIndex = null;
    const editorHtml = this.renderEditor({
      type: 'Command',
      name: '',
      command: '',
      url: '',
      file: '',
      workingDir: '',
      args: '',
      startDate: '',
      interval: '5min',
      likeliness: 100,
      comment: '',
      devInfo: ''
    });

    if( window.innerWidth <= 768 )
      this.showModal(editorHtml);
    else
      this.showEditor(editorHtml);
  },
  
  createNewGroup()
  {
    const promptHtml = `
      <div class="group-name-prompt">
        <h3>Create New Group</h3>
        <input type="text" id="groupNameInput" placeholder="Enter group name" />
        <div class="form-actions">
          <button class="btn-confirm" id="btnConfirmGroup">Create</button>
          <button class="btn-cancel" id="btnCancelGroup">Cancel</button>
        </div>
      </div>
    `;
    
    if( window.innerWidth <= 768 )
      this.showModal(promptHtml);
    else
      this.showEditor(promptHtml);
    
    setTimeout(() => {
      const input = document.getElementById('groupNameInput');
      const btnConfirm = document.getElementById('btnConfirmGroup');
      const btnCancel = document.getElementById('btnCancelGroup');
      
      if( input ) {
        input.focus();
        input.addEventListener('keypress', (e) => {
          if( e.key === 'Enter' )
            this.saveNewGroup();
        });
      }
      
      if( btnConfirm )
        btnConfirm.addEventListener('click', () => this.saveNewGroup());
      
      if( btnCancel ) {
        btnCancel.addEventListener('click', () => {
          if( window.innerWidth <= 768 )
            this.closeModal();
          else
            document.getElementById('taskEditor').innerHTML = '<div class="editor-placeholder">Select a task to edit or create a new one</div>';
        });
      }
    }, 10);
  },
  
  saveNewGroup()
  {
    const input = document.getElementById('groupNameInput');
    if( ! input )
      return;
    
    const groupName = input.value.trim();
    if( ! groupName ) {
      alert('Please enter a group name');
      return;
    }
    
    fetch('ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'createGroup', groupName: groupName })
    })
    .then(response => response.json())
    .then(data => {
      if( data.success ) {
        location.reload();
      }
      else
        alert('Failed to create group: ' + (data.error || 'Unknown error'));
    })
    .catch(err => alert('Error: ' + err.message));
  },
  
  deleteGroup(groupName)
  {
    if( ! confirm(`Are you sure you want to delete the group "${groupName}" and all its tasks?`) )
      return;
    
    fetch('ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'deleteGroup', groupName: groupName })
    })
    .then(response => response.json())
    .then(data => {
      if( data.success ) {
        location.reload();
      }
      else
        this.showSnackbar('Failed to delete group: ' + (data.error || 'Unknown error'), 'error');
    })
    .catch(err => this.showSnackbar('Error: ' + err.message, 'error'));
  },

  editTask(index)
  {
    this.currentTaskIndex = index;

    fetch(`ajax.php?action=get&index=${index}`)
      .then(response => response.json())
      .then(data => {
        if( data.success ) {
          const task = data.task;
          
          if( typeof task.args === 'object' && task.args !== null )
            task.args = JSON.stringify(task.args, null, 2);
          else if( Array.isArray(task.args) )
            task.args = task.args.join('\n');
          else
            task.args = task.args || '';

          const editorHtml = this.renderEditor(task);

          if( window.innerWidth <= 768 )
            this.showModal(editorHtml);
          else
            this.showEditor(editorHtml);
        }
        else
          alert('Failed to load task: ' + (data.error || 'Unknown error'));
      })
      .catch(err => alert('Error: ' + err.message));
  },

  deleteTask(index)
  {
    if( ! confirm('Are you sure you want to delete this task?') )
      return;

    fetch('ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', index: index })
    })
    .then(response => response.json())
    .then(data => {
      if( data.success ) {
        const item = document.querySelector(`.task-item[data-index="${index}"]`);
        if( item ) {
          item.remove();
          this.updateTaskIndices();
          
          if( this.currentTaskIndex === index ) {
            document.getElementById('taskEditor').innerHTML = '<div class="editor-placeholder">Select a task to edit or create a new one</div>';
            this.currentTaskIndex = null;
          }
        }
      }
      else
        this.showSnackbar('Failed to delete task: ' + (data.error || 'Unknown error'), 'error');
    })
    .catch(err => this.showSnackbar('Error: ' + err.message, 'error'));
  },

  renderEditor(task)
  {
    return `
      <div id="taskEditor" class="task-form">
        <div class="editor-tabs">
          <button class="editor-tab active" data-tab="edit">Edit</button>
          <button class="editor-tab" data-tab="devinfo">Dev Info</button>
        </div>
        
        <div class="tab-content active" data-tab-content="edit">
          <div class="form-group">
            <label>Type *</label>
            <select name="type" id="taskType" required>
              <option value="Command" ${task.type === 'Command' ? 'selected' : ''}>Command</option>
              <option value="Process" ${task.type === 'Process' ? 'selected' : ''}>Process</option>
              <option value="URL" ${task.type === 'URL' ? 'selected' : ''}>URL</option>
              <option value="Script" ${task.type === 'Script' ? 'selected' : ''}>Script</option>
            </select>
          </div>

          <div class="form-group">
            <label>Name *</label>
            <input type="text" name="name" value="${this.escapeHtml(task.name || '')}" required>
          </div>

          <div class="form-group" id="commandFieldGroup">
            <label id="commandFieldLabel">Command *</label>
            <input type="text" id="commandField" name="command" value="${this.escapeHtml(task.command || task.url || task.file || '')}">
          </div>

          <div class="form-group conditional-field" data-types="Command,Process,Script">
            <label>Working Directory</label>
            <input type="text" name="workingDir" value="${this.escapeHtml(task.workingDir || '')}">
          </div>

          <div class="form-group">
            <label>Arguments / Query Parameters</label>
            <textarea name="args" rows="3" placeholder="JSON object, array, or line-separated values">${this.escapeHtml(task.args || '')}</textarea>
          </div>

          <div class="form-group">
            <label>Start Date</label>
            <input type="text" name="startDate" value="${this.escapeHtml(task.startDate || '')}" placeholder="YYYY-MM-DD HH:MM:SS">
          </div>

          <div class="form-group">
            <label>Interval *</label>
            <select name="interval" required>
              <option value="5sec" ${task.interval === '5sec' ? 'selected' : ''}>5 seconds (debug)</option>
              <option value="10sec" ${task.interval === '10sec' ? 'selected' : ''}>10 seconds (debug)</option>
              <option value="5min" ${task.interval === '5min' ? 'selected' : ''}>5 minutes</option>
              <option value="10min" ${task.interval === '10min' ? 'selected' : ''}>10 minutes</option>
              <option value="30min" ${task.interval === '30min' ? 'selected' : ''}>30 minutes</option>
              <option value="hourly" ${task.interval === 'hourly' ? 'selected' : ''}>Hourly</option>
              <option value="daily" ${task.interval === 'daily' ? 'selected' : ''}>Daily</option>
              <option value="weekly" ${task.interval === 'weekly' ? 'selected' : ''}>Weekly</option>
              <option value="monthly" ${task.interval === 'monthly' ? 'selected' : ''}>Monthly</option>
            </select>
          </div>

          <div class="form-group">
            <label>Likeliness (%)</label>
            <input type="number" name="likeliness" value="${task.likeliness || 100}" min="1" max="100">
          </div>

          <div class="form-group">
            <label>Comment</label>
            <textarea name="comment" rows="2">${this.escapeHtml(task.comment || '')}</textarea>
          </div>
        </div>
        
        <div class="tab-content" data-tab-content="devinfo">
          <div class="form-group">
            <textarea name="devInfo" placeholder="Enter development notes, technical details, etc.">${this.escapeHtml(task.devInfo || '')}</textarea>
          </div>
        </div>
      </div>
    `;
  },

  showEditor(html)
  {
    const editor = document.getElementById('taskEditor');
    editor.innerHTML = html;
    this.attachFormListeners();
  },

  showModal(html)
  {
    const modal = document.getElementById('modalOverlay');
    const modalEditor = document.getElementById('modalEditor');
    modalEditor.innerHTML = html;
    modal.classList.add('active');
    this.attachFormListeners();
  },

  closeModal()
  {
    document.getElementById('modalOverlay').classList.remove('active');
  },

  attachFormListeners()
  {
    const editorDiv = document.querySelector('.task-form');
    const typeSelect = document.getElementById('taskType');

    if( typeSelect ) {
      typeSelect.addEventListener('change', (e) => {
        this.updateCommandField(e.target.value);
      });
      
      this.updateCommandField(typeSelect.value);
    }
    
    document.querySelectorAll('.editor-tab').forEach(tab => {
      tab.addEventListener('click', (e) => {
        const targetTab = e.target.dataset.tab;
        
        document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        e.target.classList.add('active');
        document.querySelector(`[data-tab-content="${targetTab}"]`).classList.add('active');
      });
    });

    if( editorDiv ) {
      editorDiv.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('change', () => {
          this.clearValidationErrors();
          if( this.currentTaskIndex !== null )
            this.autoSave();
          else
            this.saveTask(true);
        });
        
        field.addEventListener('input', () => {
          this.clearValidationErrors();
          if( this.currentTaskIndex !== null )
            this.autoSave();
        });
      });
    }
  },

  updateCommandField(type)
  {
    const label = document.getElementById('commandFieldLabel');
    const input = document.getElementById('commandField');
    const workingDirGroup = document.querySelector('.conditional-field[data-types="Command,Process,Script"]');
    
    if( ! label || ! input )
      return;
    
    const currentValue = input.value;
    
    if( type === 'Command' || type === 'Process' ) {
      label.textContent = 'Command *';
      input.setAttribute('name', 'command');
      input.setAttribute('type', 'text');
      input.required = true;
      if( workingDirGroup )
        workingDirGroup.style.display = 'block';
    }
    else if( type === 'URL' ) {
      label.textContent = 'URL *';
      input.setAttribute('name', 'url');
      input.setAttribute('type', 'url');
      input.required = true;
      if( workingDirGroup )
        workingDirGroup.style.display = 'none';
    }
    else if( type === 'Script' ) {
      label.textContent = 'Script File *';
      input.setAttribute('name', 'file');
      input.setAttribute('type', 'text');
      input.required = true;
      if( workingDirGroup )
        workingDirGroup.style.display = 'block';
    }
    
    input.value = currentValue;
  },

  autoSave()
  {
    clearTimeout(this.autoSaveTimer);
    this.autoSaveTimer = setTimeout(() => this.saveTask(true), 500);
  },

  saveTask(silent = false)
  {
    const editorDiv = document.querySelector('.task-form');
    if( ! editorDiv )
      return;
    
    const taskData = {};

    editorDiv.querySelectorAll('input, select, textarea').forEach(field => {
      if( field.id === 'commandField' )
        return;
      
      const name = field.getAttribute('name');
      if( name )
        taskData[name] = field.value;
    });
    
    const commandField = editorDiv.querySelector('#commandField');
    if( commandField ) {
      const fieldName = commandField.getAttribute('name');
      if( fieldName ) {
        taskData[fieldName] = commandField.value;
        
        if( fieldName === 'command' ) {
          delete taskData.url;
          delete taskData.file;
        }
        else if( fieldName === 'url' ) {
          delete taskData.command;
          delete taskData.file;
        }
        else if( fieldName === 'file' ) {
          delete taskData.command;
          delete taskData.url;
        }
      }
    }

    let args = taskData.args?.trim();
    if( args ) {
      try {
        taskData.args = JSON.parse(args);
      }
      catch(e) {
        if( args.includes('\n') )
          taskData.args = args.split('\n').map(line => line.trim()).filter(line => line);
        else
          taskData.args = args;
      }
    }
    else
      delete taskData.args;

    if( ! taskData.startDate )
      delete taskData.startDate;
    if( ! taskData.workingDir )
      delete taskData.workingDir;
    if( ! taskData.comment )
      delete taskData.comment;
    if( ! taskData.devInfo )
      delete taskData.devInfo;

    taskData.likeliness = parseInt(taskData.likeliness) || 100;

    const action = this.currentTaskIndex !== null ? 'update' : 'create';
    const payload = {
      action: action,
      task: taskData
    };

    if( action === 'update' )
      payload.index = this.currentTaskIndex;

    fetch('ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
      if( data.success ) {
        this.clearValidationErrors();
        if( action === 'create' ) {
          this.addTaskToList(taskData);
          if( window.innerWidth <= 768 )
            this.closeModal();
        }
        else if( ! silent ) {
          this.updateTaskInList(this.currentTaskIndex, taskData);
        }
      }
      else {
        const errors = data.errors || [data.error || 'Unknown error'];
        this.showValidationErrors(errors);
      }
    })
    .catch(err => {
      if( ! silent )
        this.showSnackbar('Error: ' + err.message, 'error');
    });
  },

  addTaskToList(taskData)
  {
    const taskList = document.getElementById('taskList');
    const emptyState = taskList.querySelector('.empty-state');
    if( emptyState )
      emptyState.remove();
    
    const newIndex = document.querySelectorAll('.task-item').length;
    const taskHtml = `
      <div class="task-item" data-index="${newIndex}">
        <div class="drag-handle">⋮⋮</div>
        <div class="task-info">
          <div class="task-name">${this.escapeHtml(taskData.name || '')}</div>
          <div class="task-meta">
            <span class="task-type">${this.escapeHtml(taskData.type || '')}</span>
            <span class="task-interval">${this.escapeHtml(taskData.interval || '')}</span>
            <span class="task-comment">${this.escapeHtml(taskData.comment || '')}</span>
          </div>
        </div>
        <button class="btn-delete" data-index="${newIndex}" title="Delete task">×</button>
      </div>
    `;
    
    taskList.insertAdjacentHTML('beforeend', taskHtml);
    
    this.attachTaskItemListeners();
    this.attachDragListenersToItems();
  },

  updateTaskInList(index, taskData)
  {
    const item = document.querySelector(`.task-item[data-index="${index}"]`);
    if( ! item )
      return;
    
    const nameEl = item.querySelector('.task-name');
    const typeEl = item.querySelector('.task-type');
    const intervalEl = item.querySelector('.task-interval');
    const commentEl = item.querySelector('.task-comment');
    
    if( nameEl )
      nameEl.textContent = taskData.name || '';
    if( typeEl )
      typeEl.textContent = taskData.type || '';
    if( intervalEl )
      intervalEl.textContent = taskData.interval || '';
    if( commentEl )
      commentEl.textContent = taskData.comment || '';
  },

  showValidationErrors(errors)
  {
    this.clearValidationErrors();
    
    const editorDiv = document.querySelector('.task-form');
    if( ! editorDiv )
      return;
    
    errors.forEach(error => {
      const lowerError = error.toLowerCase();
      
      if( lowerError.includes('type') ) {
        const field = editorDiv.querySelector('[name="type"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('name') ) {
        const field = editorDiv.querySelector('[name="name"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('command') || lowerError.includes('url') || lowerError.includes('file') || lowerError.includes('script') ) {
        const field = editorDiv.querySelector('#commandField');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('interval') ) {
        const field = editorDiv.querySelector('[name="interval"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('startdate') || lowerError.includes('start date') ) {
        const field = editorDiv.querySelector('[name="startDate"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('likeliness') ) {
        const field = editorDiv.querySelector('[name="likeliness"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('args') || lowerError.includes('argument') || lowerError.includes('parameter') ) {
        const field = editorDiv.querySelector('[name="args"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('comment') ) {
        const field = editorDiv.querySelector('[name="comment"]');
        if( field )
          field.classList.add('error');
      }
      else if( lowerError.includes('workingdir') || lowerError.includes('working dir') ) {
        const field = editorDiv.querySelector('[name="workingDir"]');
        if( field )
          field.classList.add('error');
      }
    });
  },

  clearValidationErrors()
  {
    const editorDiv = document.querySelector('.task-form');
    if( editorDiv ) {
      editorDiv.querySelectorAll('input, select, textarea').forEach(field => {
        field.classList.remove('error');
      });
    }
  },

  showSnackbar(message, type = 'info')
  {
    let snackbar = document.getElementById('snackbar');
    if( ! snackbar ) {
      snackbar = document.createElement('div');
      snackbar.id = 'snackbar';
      document.body.appendChild(snackbar);
    }
    
    snackbar.textContent = message;
    snackbar.className = `snackbar snackbar-${type} show`;
    
    setTimeout(() => {
      snackbar.classList.remove('show');
    }, 3000);
  },

  escapeHtml(text)
  {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
};

document.addEventListener('DOMContentLoaded', () => App.init());
