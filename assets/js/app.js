/* ============================================================
   HostSW – Dashboard Application Logic
   ============================================================ */

'use strict';

// ── State ────────────────────────────────────────────────────
const State = {
  websites:     [],
  filtered:     [],
  sortBy:       'deployed_at',
  sortDir:      'desc',
  filterStatus: 'all',
  searchQuery:  '',
  stats:        {},
};

// ── DOM helpers ──────────────────────────────────────────────
const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

// ── Toast ────────────────────────────────────────────────────
const Toast = (() => {
  const container = document.getElementById('toast-container');

  return {
    show(type, title, message, duration = 4000) {
      const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
      const t = document.createElement('div');
      t.className = `toast toast-${type}`;
      t.innerHTML = `
        <span class="toast-icon">${icons[type]}</span>
        <div class="toast-body">
          <div class="toast-title">${title}</div>
          ${message ? `<div class="toast-message">${message}</div>` : ''}
        </div>
        <button class="toast-close" aria-label="Close">✕</button>
      `;
      container.appendChild(t);
      requestAnimationFrame(() => t.classList.add('show'));
      const dismiss = () => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 400);
      };
      t.querySelector('.toast-close').addEventListener('click', dismiss);
      if (duration > 0) setTimeout(dismiss, duration);
      return dismiss;
    },
    success: (title, msg) => Toast.show('success', title, msg),
    error:   (title, msg) => Toast.show('error',   title, msg),
    info:    (title, msg) => Toast.show('info',     title, msg),
  };
})();

// ── API helper ───────────────────────────────────────────────
async function apiPost(url, formData) {
  const res = await fetch(url, { method: 'POST', body: formData });
  return res.json();
}

// ── Clipboard ────────────────────────────────────────────────
async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text);
    Toast.success('Copied!', text);
  } catch {
    Toast.error('Copy failed', 'Please copy the URL manually.');
  }
}

// ── Format helpers ───────────────────────────────────────────
function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
function siteInitials(name) {
  return name.slice(0, 2).toUpperCase();
}
function iconColor(name) {
  const colors = [
    'linear-gradient(135deg,#6c63ff,#9c6dff)',
    'linear-gradient(135deg,#3b82f6,#60a5fa)',
    'linear-gradient(135deg,#ec4899,#f472b6)',
    'linear-gradient(135deg,#f59e0b,#fbbf24)',
    'linear-gradient(135deg,#10b981,#34d399)',
    'linear-gradient(135deg,#ef4444,#f87171)',
    'linear-gradient(135deg,#8b5cf6,#a78bfa)',
    'linear-gradient(135deg,#06b6d4,#22d3ee)',
  ];
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return colors[Math.abs(hash) % colors.length];
}

// ── Data loading ─────────────────────────────────────────────
async function loadWebsites() {
  const tbody = document.getElementById('sites-tbody');
  tbody.innerHTML = `<tr class="loading-row"><td colspan="7">
    <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;">
      <div class="spinner" style="border-top-color:var(--accent-primary);border-color:var(--border);"></div>
      Loading websites…
    </div></td></tr>`;

  try {
    const data = await fetch('api/list.php').then(r => r.json());
    if (!data.success) throw new Error(data.error);

    State.websites = data.websites;
    State.stats    = data.stats;

    updateStats(data.stats);
    applyFilters();
    document.getElementById('nav-sites-badge').textContent = data.stats.total_sites;
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" style="padding:2rem;text-align:center;color:var(--danger);">
      Failed to load websites: ${escHtml(e.message)}</td></tr>`;
  }
}

function updateStats(stats) {
  document.getElementById('stat-total-sites').textContent   = stats.total_sites;
  document.getElementById('stat-total-storage').textContent = stats.total_storage;
  document.getElementById('stat-last-upload').textContent   = stats.last_upload;
  document.getElementById('nav-sites-badge').textContent    = stats.total_sites;
}

// ── Filter / Sort / Render ───────────────────────────────────
function applyFilters() {
  const q = State.searchQuery.toLowerCase();
  const s = State.filterStatus;

  State.filtered = State.websites.filter(site => {
    const matchSearch = !q || site.name.includes(q) || site.title.toLowerCase().includes(q);
    const matchStatus = s === 'all' || site.status === s;
    return matchSearch && matchStatus;
  });

  sortFiltered();
  renderTable();
}

function sortFiltered() {
  const { sortBy, sortDir } = State;
  State.filtered.sort((a, b) => {
    let av = a[sortBy], bv = b[sortBy];
    if (typeof av === 'string') av = av.toLowerCase(), bv = bv.toLowerCase();
    if (av < bv) return sortDir === 'asc' ? -1 : 1;
    if (av > bv) return sortDir === 'asc' ? 1 : -1;
    return 0;
  });
}

function renderTable() {
  const tbody = document.getElementById('sites-tbody');
  document.getElementById('table-count').textContent = State.filtered.length;

  if (State.filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7">
      <div class="empty-state">
        <div class="empty-state-icon">🌐</div>
        <div class="empty-state-title">${State.searchQuery || State.filterStatus !== 'all' ? 'No matching websites' : 'No websites yet'}</div>
        <div class="empty-state-sub">${State.searchQuery || State.filterStatus !== 'all' ? 'Try adjusting your filters.' : 'Click "Add New Website" to deploy your first site.'}</div>
      </div>
    </td></tr>`;
    return;
  }

  tbody.innerHTML = State.filtered.map(site => `
    <tr class="fade-in" data-name="${escHtml(site.name)}">
      <td>
        <div class="site-info">
          <div class="site-icon" style="background:${iconColor(site.name)}">${siteInitials(site.name)}</div>
          <div>
            <div class="site-name">${escHtml(site.title || site.name)}</div>
            <div class="site-desc mono">${escHtml(site.name)}${site.description ? ` · ${escHtml(site.description)}` : ''}</div>
          </div>
        </div>
      </td>
      <td>
        <div class="url-cell">
          <span class="url-text" title="${escHtml(site.url)}">${escHtml(site.url)}</span>
        </div>
      </td>
      <td style="white-space:nowrap;color:var(--text-secondary)">${escHtml(site.deployed_at_fmt)}</td>
      <td style="white-space:nowrap;color:var(--text-secondary)">${escHtml(site.size_fmt)}</td>
      <td><span class="badge badge-${site.status}">${site.status === 'live' ? 'Live' : 'Broken'}</span></td>
      <td>
        <div class="actions-cell">
          <button class="btn btn-icon btn-ghost" title="Open website" onclick="openSite('${escHtml(site.url)}')">🌐</button>
          <button class="btn btn-icon btn-ghost" title="Edit Code" onclick="window.location.href='editor.php?site=${encodeURIComponent(site.name)}'">💻</button>
          <button class="btn btn-icon btn-ghost" title="Copy URL" onclick="copyToClipboard('${escHtml(site.url)}')">📋</button>
          <button class="btn btn-icon btn-ghost" title="Download ZIP" onclick="downloadSite('${escHtml(site.name)}')">⬇️</button>
          <button class="btn btn-icon btn-ghost" title="Replace website" onclick="openReplaceModal('${escHtml(site.name)}')">🔄</button>
          <button class="btn btn-icon btn-ghost" title="Rename website" onclick="openRenameModal('${escHtml(site.name)}')">✏️</button>
          <button class="btn btn-icon btn-danger" title="Delete website" onclick="openDeleteModal('${escHtml(site.name)}')">🗑️</button>
        </div>
      </td>
    </tr>
  `).join('');
}

// ── Sort column headers ──────────────────────────────────────
function initSortHeaders() {
  $$('[data-sort]').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.sort;
      if (State.sortBy === col) {
        State.sortDir = State.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        State.sortBy  = col;
        State.sortDir = 'asc';
      }
      $$('[data-sort]').forEach(h => {
        h.classList.remove('sorted');
        h.querySelector('.sort-icon').textContent = '⇅';
      });
      th.classList.add('sorted');
      th.querySelector('.sort-icon').textContent = State.sortDir === 'asc' ? '↑' : '↓';
      sortFiltered();
      renderTable();
    });
  });
}

// ── Search ───────────────────────────────────────────────────
function initSearch() {
  const inputs = $$('[data-search]');
  inputs.forEach(input => {
    input.addEventListener('input', () => {
      State.searchQuery = input.value;
      inputs.forEach(i => { if (i !== input) i.value = input.value; });
      applyFilters();
    });
  });
}

// ── Status filter ────────────────────────────────────────────
function initFilters() {
  const sel = document.getElementById('status-filter');
  if (sel) sel.addEventListener('change', () => { State.filterStatus = sel.value; applyFilters(); });
}

// ── Actions ──────────────────────────────────────────────────
function openSite(url) { window.open(url, '_blank'); }

function downloadSite(name) {
  window.location.href = `api/download.php?name=${encodeURIComponent(name)}`;
}

// ── ADD WEBSITE MODAL ────────────────────────────────────────
function switchAddTab(mode) {
  const typeInput = document.getElementById('add-deploy-type');
  if (typeInput) typeInput.value = mode;

  const btnZip   = document.getElementById('tab-btn-zip');
  const btnHtml  = document.getElementById('tab-btn-html');
  const paneZip  = document.getElementById('add-zip-container');
  const paneHtml = document.getElementById('add-html-container');
  const hint     = document.getElementById('add-req-hint');

  if (mode === 'html') {
    if (btnZip) btnZip.classList.remove('active');
    if (btnHtml) btnHtml.classList.add('active');
    if (paneZip) paneZip.classList.add('hidden');
    if (paneHtml) paneHtml.classList.remove('hidden');
    if (hint) {
      hint.innerHTML = '<strong>📋 Note:</strong> Your HTML code will be saved as <code style="color:var(--text-accent)">index.html</code>. Only static HTML/CSS/JS is supported.';
    }
  } else {
    if (btnHtml) btnHtml.classList.remove('active');
    if (btnZip) btnZip.classList.add('active');
    if (paneHtml) paneHtml.classList.add('hidden');
    if (paneZip) paneZip.classList.remove('hidden');
    if (hint) {
      hint.innerHTML = '<strong>📋 Requirements:</strong> Your ZIP must contain <code style="color:var(--text-accent)">index.html</code> at the root level. Only static files are allowed. PHP and server-side scripts are rejected.';
    }
  }
}

function insertSampleHtml() {
  const textarea = document.getElementById('add-html-code');
  if (textarea) {
    textarea.value = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to My Website</title>
  <style>
    body {
      font-family: system-ui, -apple-system, sans-serif;
      background: #0f172a;
      color: #f8fafc;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
    }
    .card {
      background: #1e293b;
      padding: 2.5rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
      text-align: center;
      max-width: 450px;
    }
    h1 { color: #38bdf8; margin-top: 0; }
    p { color: #94a3b8; line-height: 1.6; }
    .btn {
      display: inline-block;
      margin-top: 1rem;
      padding: 0.75rem 1.5rem;
      background: #0284c7;
      color: #fff;
      text-decoration: none;
      border-radius: 0.5rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>🚀 Website Online!</h1>
    <p>This static website was hosted directly using HTML code on HostSW.</p>
    <a href="#" class="btn">Explore More</a>
  </div>
</body>
</html>`;
  }
}

function openAddModal() {
  const overlay = document.getElementById('add-modal-overlay');
  overlay.classList.add('open');
  document.getElementById('add-form').reset();
  switchAddTab('zip');
  document.getElementById('add-file-info').classList.add('hidden');
  document.getElementById('add-drop-zone').classList.remove('has-file');
  document.getElementById('add-drop-zone').querySelector('.drop-zone-icon').textContent = '📦';
  document.getElementById('add-upload-progress').classList.add('hidden');
  document.getElementById('add-submit-btn').disabled = false;
  document.getElementById('add-name-error').textContent = '';
  document.getElementById('add-form-error').classList.add('hidden');
  document.getElementById('add-name').focus();
}

function closeAddModal() {
  document.getElementById('add-modal-overlay').classList.remove('open');
}

// Name validation
document.addEventListener('DOMContentLoaded', () => {
  const nameInput = document.getElementById('add-name');
  if (nameInput) {
    nameInput.addEventListener('input', () => {
      const val = nameInput.value;
      const err = document.getElementById('add-name-error');
      if (val && !/^[a-z0-9][a-z0-9\-]{0,49}$/.test(val)) {
        err.textContent = 'Use only lowercase letters, numbers, hyphens. Start with letter/number.';
        nameInput.classList.add('error');
      } else {
        err.textContent = '';
        nameInput.classList.remove('error');
      }
    });
  }
});

// Drag-and-drop
function initDropZone(zoneId, inputId, infoId) {
  const zone  = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  const info  = document.getElementById(infoId);
  if (!zone) return;

  ['dragenter','dragover'].forEach(e => zone.addEventListener(e, ev => {
    ev.preventDefault();
    zone.classList.add('drag-over');
  }));
  ['dragleave','drop'].forEach(e => zone.addEventListener(e, () => zone.classList.remove('drag-over')));

  zone.addEventListener('drop', ev => {
    ev.preventDefault();
    const file = ev.dataTransfer?.files?.[0];
    if (file) setDropFile(zone, input, info, file);
  });

  input.addEventListener('change', () => {
    if (input.files[0]) setDropFile(zone, input, info, input.files[0]);
  });

  // Clear button
  const clearBtn = info.querySelector('.file-info-clear');
  if (clearBtn) clearBtn.addEventListener('click', e => {
    e.stopPropagation();
    input.value = '';
    info.classList.add('hidden');
    zone.classList.remove('has-file');
    zone.querySelector('.drop-zone-icon').textContent = '📦';
  });
}

function setDropFile(zone, input, info, file) {
  zone.classList.add('has-file');
  zone.querySelector('.drop-zone-icon').textContent = '✅';
  info.classList.remove('hidden');
  info.querySelector('.file-info-name').textContent = file.name;
  info.querySelector('.file-info-size').textContent = formatFileSize(file.size);
  // Sync to input via DataTransfer if dropped externally
  if (!input.files.length || input.files[0] !== file) {
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
  }
}

function formatFileSize(bytes) {
  if (bytes >= 1073741824) return (bytes/1073741824).toFixed(2) + ' GB';
  if (bytes >= 1048576)    return (bytes/1048576).toFixed(2)    + ' MB';
  if (bytes >= 1024)       return (bytes/1024).toFixed(2)       + ' KB';
  return bytes + ' B';
}

// Submit add form
async function submitAddForm(e) {
  e.preventDefault();
  const btn      = document.getElementById('add-submit-btn');
  const progress = document.getElementById('add-upload-progress');
  const errBox   = document.getElementById('add-form-error');
  const errMsg   = document.getElementById('add-form-error-msg');

  errBox.classList.add('hidden');

  const mode  = document.getElementById('add-deploy-type').value;
  const name  = document.getElementById('add-name').value.trim();
  const title = document.getElementById('add-title').value.trim();
  const desc  = document.getElementById('add-desc').value.trim();

  if (!name) { showFormError(errMsg, errBox, 'Website name is required.'); return; }
  if (!/^[a-z0-9][a-z0-9\-]{0,49}$/.test(name)) { showFormError(errMsg, errBox, 'Invalid website name format.'); return; }

  const fd = new FormData();
  fd.append('deploy_type', mode);
  fd.append('name', name);
  fd.append('title', title);
  fd.append('description', desc);

  if (mode === 'html') {
    const code = document.getElementById('add-html-code').value;
    if (!code.trim()) { showFormError(errMsg, errBox, 'Please enter or paste your HTML code.'); return; }
    fd.append('html_code', code);
  } else {
    const file = document.getElementById('add-zip').files[0];
    if (!file) { showFormError(errMsg, errBox, 'Please select a ZIP file.'); return; }
    if (!file.name.toLowerCase().endsWith('.zip')) { showFormError(errMsg, errBox, 'Only ZIP files are accepted.'); return; }
    fd.append('zipfile', file);
  }

  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div> Deploying…';
  if (mode === 'zip') progress.classList.remove('hidden');

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'api/upload.php', true);

  xhr.upload.onprogress = ev => {
    if (ev.lengthComputable && mode === 'zip') {
      const pct = Math.round((ev.loaded / ev.total) * 100);
      document.getElementById('add-progress-fill').style.width = pct + '%';
      document.getElementById('add-progress-pct').textContent  = pct + '%';
      document.getElementById('add-progress-label').textContent = pct < 100 ? 'Uploading…' : 'Extracting…';
    }
  };

  xhr.onload = () => {
    btn.disabled = false;
    btn.innerHTML = '🚀 Deploy Website';
    progress.classList.add('hidden');
    document.getElementById('add-progress-fill').style.width = '0%';

    let data;
    try { data = JSON.parse(xhr.responseText); } catch { data = { success: false, error: 'Server returned an invalid response.' }; }

    if (data.success) {
      closeAddModal();
      Toast.success('Deployed!', `<a href="${escHtml(data.url)}" target="_blank" style="color:var(--text-accent)">${escHtml(data.url)}</a>`);
      loadWebsites();
    } else {
      showFormError(errMsg, errBox, data.error || 'Deployment failed.');
    }
  };

  xhr.onerror = () => {
    btn.disabled = false;
    btn.innerHTML = '🚀 Deploy Website';
    progress.classList.add('hidden');
    showFormError(errMsg, errBox, 'Network error. Please try again.');
  };

  xhr.send(fd);
}


function showFormError(msgEl, boxEl, msg) {
  msgEl.textContent = msg;
  boxEl.classList.remove('hidden');
}

// ── DELETE MODAL ─────────────────────────────────────────────
let pendingDeleteName = null;

function openDeleteModal(name) {
  pendingDeleteName = name;
  document.getElementById('delete-site-name').textContent = name;
  document.getElementById('delete-modal-overlay').classList.add('open');
}

function closeDeleteModal() {
  document.getElementById('delete-modal-overlay').classList.remove('open');
  pendingDeleteName = null;
}

async function confirmDelete() {
  if (!pendingDeleteName) return;
  const btn = document.getElementById('delete-confirm-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div> Deleting…';

  const fd = new FormData();
  fd.append('name', pendingDeleteName);

  const data = await apiPost('api/delete.php', fd);

  btn.disabled = false;
  btn.innerHTML = '🗑️ Delete';

  if (data.success) {
    closeDeleteModal();
    Toast.success('Deleted', `"${pendingDeleteName}" was removed.`);
    loadWebsites();
  } else {
    Toast.error('Delete failed', data.error);
  }
}

// ── RENAME MODAL ─────────────────────────────────────────────
let pendingRenameName = null;

function openRenameModal(name) {
  pendingRenameName = name;
  document.getElementById('rename-old-name').textContent = name;
  document.getElementById('rename-new-name').value = name;
  document.getElementById('rename-error').textContent = '';
  document.getElementById('rename-modal-overlay').classList.add('open');
  setTimeout(() => document.getElementById('rename-new-name').select(), 50);
}

function closeRenameModal() {
  document.getElementById('rename-modal-overlay').classList.remove('open');
  pendingRenameName = null;
}

async function confirmRename() {
  if (!pendingRenameName) return;
  const newName = document.getElementById('rename-new-name').value.trim();
  const errEl   = document.getElementById('rename-error');

  if (!newName) { errEl.textContent = 'New name is required.'; return; }
  if (!/^[a-z0-9][a-z0-9\-]{0,49}$/.test(newName)) { errEl.textContent = 'Invalid name format.'; return; }

  const btn = document.getElementById('rename-confirm-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div> Renaming…';

  const fd = new FormData();
  fd.append('old_name', pendingRenameName);
  fd.append('new_name', newName);

  const data = await apiPost('api/rename.php', fd);

  btn.disabled = false;
  btn.innerHTML = '✏️ Rename';

  if (data.success) {
    closeRenameModal();
    Toast.success('Renamed', data.message);
    loadWebsites();
  } else {
    errEl.textContent = data.error || 'Rename failed.';
  }
}

// ── REPLACE MODAL ────────────────────────────────────────────
let pendingReplaceName = null;

function openReplaceModal(name) {
  pendingReplaceName = name;
  document.getElementById('replace-site-name').textContent = name;
  document.getElementById('replace-zip').value = '';
  document.getElementById('replace-file-info').classList.add('hidden');
  document.getElementById('replace-drop-zone').classList.remove('has-file');
  document.getElementById('replace-drop-zone').querySelector('.drop-zone-icon').textContent = '📦';
  document.getElementById('replace-upload-progress').classList.add('hidden');
  document.getElementById('replace-form-error').classList.add('hidden');
  document.getElementById('replace-confirm-btn').disabled = false;
  document.getElementById('replace-confirm-btn').innerHTML = '🔄 Replace';
  document.getElementById('replace-modal-overlay').classList.add('open');
}

function closeReplaceModal() {
  document.getElementById('replace-modal-overlay').classList.remove('open');
  pendingReplaceName = null;
}

async function confirmReplace() {
  if (!pendingReplaceName) return;
  const file   = document.getElementById('replace-zip').files[0];
  const errBox = document.getElementById('replace-form-error');
  const errMsg = document.getElementById('replace-form-error-msg');

  if (!file) { showFormError(errMsg, errBox, 'Please select a ZIP file.'); return; }
  if (!file.name.toLowerCase().endsWith('.zip')) { showFormError(errMsg, errBox, 'Only ZIP files are accepted.'); return; }

  const btn      = document.getElementById('replace-confirm-btn');
  const progress = document.getElementById('replace-upload-progress');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner"></div> Replacing…';
  progress.classList.remove('hidden');
  errBox.classList.add('hidden');

  const fd = new FormData();
  fd.append('name', pendingReplaceName);
  fd.append('zipfile', file);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'api/replace.php', true);

  xhr.upload.onprogress = ev => {
    if (ev.lengthComputable) {
      const pct = Math.round((ev.loaded / ev.total) * 100);
      document.getElementById('replace-progress-fill').style.width = pct + '%';
      document.getElementById('replace-progress-pct').textContent  = pct + '%';
    }
  };

  xhr.onload = () => {
    btn.disabled = false;
    btn.innerHTML = '🔄 Replace';
    progress.classList.add('hidden');

    let data;
    try { data = JSON.parse(xhr.responseText); } catch { data = { success: false, error: 'Invalid server response.' }; }

    if (data.success) {
      closeReplaceModal();
      Toast.success('Replaced!', `Website updated successfully.`);
      loadWebsites();
    } else {
      showFormError(errMsg, errBox, data.error || 'Replace failed.');
    }
  };
  xhr.onerror = () => {
    btn.disabled = false; btn.innerHTML = '🔄 Replace';
    showFormError(errMsg, errBox, 'Network error.');
  };
  xhr.send(fd);
}

// ── Theme ────────────────────────────────────────────────────
function initTheme() {
  const saved = localStorage.getItem('hostsw_theme') || 'dark';
  setTheme(saved);
}
function setTheme(t) {
  document.documentElement.setAttribute('data-theme', t);
  localStorage.setItem('hostsw_theme', t);
  const btn = document.getElementById('theme-toggle');
  if (btn) btn.title = t === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
}
function toggleTheme() {
  const cur = document.documentElement.getAttribute('data-theme') || 'dark';
  setTheme(cur === 'dark' ? 'light' : 'dark');
  const btn = document.getElementById('theme-toggle');
  if (btn) {
    btn.style.transform = 'rotate(360deg)';
    setTimeout(() => btn.style.transform = '', 300);
  }
}

// ── Mobile sidebar ───────────────────────────────────────────
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebar-overlay').style.display='block'; }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebar-overlay').style.display='none'; }

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initSearch();
  initFilters();
  initSortHeaders();

  initDropZone('add-drop-zone',     'add-zip',     'add-file-info');
  initDropZone('replace-drop-zone', 'replace-zip', 'replace-file-info');

  // Add form submit
  const addForm = document.getElementById('add-form');
  if (addForm) addForm.addEventListener('submit', submitAddForm);

  // Rename enter key
  const renameInput = document.getElementById('rename-new-name');
  if (renameInput) renameInput.addEventListener('keydown', e => { if (e.key === 'Enter') confirmRename(); });

  // Close modals on overlay click
  $$('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) {
        overlay.classList.remove('open');
      }
    });
  });

  // Close modals on Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') $$('.modal-overlay.open').forEach(o => o.classList.remove('open'));
  });

  loadWebsites();
});
