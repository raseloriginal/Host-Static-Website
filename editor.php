<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/api/auth_check.php';

if (empty($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}

$site = $_GET['site'] ?? '';
if ($site === '' || !preg_match('/^[a-z0-9][a-z0-9\-]{0,49}$/', $site)) {
    die("Invalid website name.");
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editor - <?= htmlspecialchars($site) ?> - HostSW</title>
  <link rel="stylesheet" href="assets/css/app.css">
  <style>
    body {
        margin: 0;
        overflow: hidden;
    }
    .editor-layout {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }
    .editor-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1.5rem;
        background: var(--bg-card);
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .editor-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .editor-body {
        display: flex;
        flex: 1;
        overflow: hidden;
    }
    .editor-sidebar {
        width: 250px;
        background: var(--bg-secondary);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    .editor-sidebar-title {
        padding: 1rem;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-muted);
        text-transform: uppercase;
        border-bottom: 1px solid var(--border);
    }
    .file-tree {
        padding: 0.5rem 0;
        list-style: none;
        margin: 0;
    }
    .file-item {
        padding: 0.4rem 1rem;
        cursor: pointer;
        font-family: var(--font-mono);
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background var(--transition), color var(--transition);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .file-item:hover {
        background: var(--bg-hover);
        color: var(--text-primary);
    }
    .file-item.active {
        background: rgba(108, 99, 255, 0.1);
        color: var(--accent-primary);
        border-left: 3px solid var(--accent-primary);
        padding-left: calc(1rem - 3px);
    }
    .editor-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--bg-primary);
    }
    .editor-tab-bar {
        display: flex;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border);
        overflow-x: auto;
    }
    .editor-tab {
        padding: 0.6rem 1.2rem;
        font-family: var(--font-mono);
        font-size: 0.8rem;
        background: var(--bg-card);
        color: var(--text-primary);
        border-right: 1px solid var(--border);
        border-top: 2px solid var(--accent-primary);
    }
    .editor-content-area {
        flex: 1;
        padding: 0;
        display: flex;
    }
    #code-editor {
        width: 100%;
        height: 100%;
        border: none;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-family: var(--font-mono);
        font-size: 0.9rem;
        padding: 1rem;
        resize: none;
        outline: none;
        line-height: 1.5;
    }
    #code-editor:disabled {
        background: var(--bg-secondary);
        color: var(--text-muted);
        cursor: not-allowed;
    }
  </style>
</head>
<body>

<div class="editor-layout">
  <header class="editor-header">
    <div class="editor-header-left">
      <a href="dashboard.php" class="btn btn-ghost btn-icon" title="Back to Dashboard">⬅️</a>
      <div style="display:flex; flex-direction:column;">
        <span style="font-weight:700; font-size:1rem;"><?= htmlspecialchars($site) ?></span>
        <span style="font-size:0.75rem; color:var(--text-muted);">Code Editor</span>
      </div>
    </div>
    <div class="editor-header-right">
      <span id="save-status" style="margin-right:1rem; font-size:0.8rem; color:var(--text-muted);"></span>
      <button class="btn btn-primary" id="save-btn" onclick="saveCurrentFile()" disabled>💾 Save File</button>
    </div>
  </header>

  <div class="editor-body">
    <aside class="editor-sidebar">
      <div class="editor-sidebar-title">Files</div>
      <ul class="file-tree" id="file-tree">
        <li style="padding:1rem; color:var(--text-muted); font-size:0.85rem; text-align:center;">Loading...</li>
      </ul>
    </aside>

    <main class="editor-main">
      <div class="editor-tab-bar" id="editor-tabs" style="display:none;">
        <div class="editor-tab" id="current-file-tab">No file selected</div>
      </div>
      <div class="editor-content-area">
        <textarea id="code-editor" disabled placeholder="Select a file from the sidebar to start editing..."></textarea>
      </div>
    </main>
  </div>
</div>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

<script>
const siteName = "<?= htmlspecialchars($site) ?>";
let currentFile = null;

const $fileTree = document.getElementById('file-tree');
const $codeEditor = document.getElementById('code-editor');
const $saveBtn = document.getElementById('save-btn');
const $currentTab = document.getElementById('current-file-tab');
const $tabBar = document.getElementById('editor-tabs');
const $saveStatus = document.getElementById('save-status');

// Toast logic from app.js
const Toast = {
  show(type, title, message) {
    const icons = { success: '✅', error: '❌' };
    const container = document.getElementById('toast-container');
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `
      <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
      <div class="toast-body">
        <div class="toast-title">${title}</div>
        ${message ? `<div class="toast-message">${message}</div>` : ''}
      </div>
      <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 3000);
  },
  success(title, msg) { this.show('success', title, msg); },
  error(title, msg) { this.show('error', title, msg); }
};

// Load files
async function loadFiles() {
    try {
        const res = await fetch(`api/editor_api.php?action=list&site=${encodeURIComponent(siteName)}`);
        const data = await res.json();
        if (data.success) {
            renderFileTree(data.files);
        } else {
            $fileTree.innerHTML = `<li style="color:var(--danger);padding:1rem;">${data.error}</li>`;
        }
    } catch (e) {
        $fileTree.innerHTML = `<li style="color:var(--danger);padding:1rem;">Failed to load files.</li>`;
    }
}

function renderFileTree(files) {
    if (!files || files.length === 0) {
        $fileTree.innerHTML = '<li style="padding:1rem; color:var(--text-muted);">No editable files found.</li>';
        return;
    }
    
    // Sort files alphabetically
    files.sort();
    
    $fileTree.innerHTML = '';
    files.forEach(file => {
        const li = document.createElement('li');
        li.className = 'file-item';
        li.dataset.file = file;
        
        let icon = '📄';
        if (file.endsWith('.html')) icon = '🌐';
        else if (file.endsWith('.css')) icon = '🎨';
        else if (file.endsWith('.js')) icon = '⚡';
        else if (file.endsWith('.json')) icon = '📋';
        
        li.innerHTML = `<span>${icon}</span> <span>${file}</span>`;
        li.onclick = () => loadFileContent(file);
        $fileTree.appendChild(li);
    });
}

async function loadFileContent(filepath) {
    if (currentFile && $saveBtn.textContent === '* Save File' && !confirm('You have unsaved changes. Discard?')) {
        return;
    }
    
    $codeEditor.disabled = true;
    $codeEditor.value = 'Loading...';
    
    try {
        const res = await fetch(`api/editor_api.php?action=read&site=${encodeURIComponent(siteName)}&file=${encodeURIComponent(filepath)}`);
        const data = await res.json();
        
        if (data.success) {
            currentFile = filepath;
            $codeEditor.value = data.content;
            $codeEditor.disabled = false;
            $saveBtn.disabled = false;
            $saveBtn.textContent = '💾 Save File';
            $saveStatus.textContent = '';
            
            $tabBar.style.display = 'flex';
            $currentTab.textContent = filepath;
            
            // Highlight active file in sidebar
            document.querySelectorAll('.file-item').forEach(el => el.classList.remove('active'));
            document.querySelector(`.file-item[data-file="${filepath}"]`)?.classList.add('active');
        } else {
            Toast.error('Error', data.error);
            $codeEditor.value = '';
        }
    } catch (e) {
        Toast.error('Error', 'Failed to read file.');
        $codeEditor.value = '';
    }
}

$codeEditor.addEventListener('input', () => {
    if (currentFile) {
        $saveBtn.textContent = '* Save File';
        $saveStatus.textContent = 'Unsaved changes';
    }
});

$codeEditor.addEventListener('keydown', function(e) {
  // Support tab indentation
  if (e.key === 'Tab') {
    e.preventDefault();
    const start = this.selectionStart;
    const end = this.selectionEnd;
    this.value = this.value.substring(0, start) + "  " + this.value.substring(end);
    this.selectionStart = this.selectionEnd = start + 2;
  }
  
  // Support Ctrl+S / Cmd+S
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      saveCurrentFile();
  }
});

async function saveCurrentFile() {
    if (!currentFile) return;
    
    $saveBtn.disabled = true;
    $saveBtn.textContent = 'Saving...';
    $saveStatus.textContent = 'Saving...';
    
    const fd = new FormData();
    fd.append('file', currentFile);
    fd.append('content', $codeEditor.value);
    
    try {
        const res = await fetch(`api/editor_api.php?action=save&site=${encodeURIComponent(siteName)}`, {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        
        if (data.success) {
            Toast.success('Saved!', `${currentFile} updated.`);
            $saveBtn.textContent = '💾 Save File';
            $saveStatus.textContent = 'All changes saved';
            setTimeout(() => {
                 if($saveStatus.textContent === 'All changes saved') $saveStatus.textContent = '';
            }, 3000);
        } else {
            Toast.error('Save Failed', data.error);
            $saveBtn.textContent = '* Save File';
            $saveStatus.textContent = 'Save failed';
        }
    } catch (e) {
        Toast.error('Error', 'Network error.');
        $saveBtn.textContent = '* Save File';
        $saveStatus.textContent = 'Network error';
    } finally {
        $saveBtn.disabled = false;
    }
}

// Initial load
loadFiles();
</script>

</body>
</html>
