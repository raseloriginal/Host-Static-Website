<?php
require_once __DIR__ . '/config/config.php';

ini_set('session.cookie_httponly', 1);
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}

$admin_user = htmlspecialchars($_SESSION['user'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard – HostSW</title>
  <meta name="description" content="Manage all your hosted static websites from one dashboard.">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div id="sidebar-overlay" class="sidebar-overlay" onclick="closeSidebar()" style="display:none"></div>

<!-- ── Sidebar ─────────────────────────────────────────────── -->
<aside id="sidebar" class="sidebar" role="navigation" aria-label="Main navigation">
  <div class="sidebar-header">
    <div class="sidebar-logo-icon">🚀</div>
    <div class="sidebar-logo-text">Host<span>SW</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Platform</div>
    <div class="nav-item active" onclick="loadWebsites()">
      <span class="nav-icon">🌐</span>
      <span>My Websites</span>
      <span id="nav-sites-badge" class="nav-badge">0</span>
    </div>

    <div class="nav-section-label" style="margin-top:.5rem">Quick Actions</div>
    <div class="nav-item" onclick="openAddModal()">
      <span class="nav-icon">➕</span>
      <span>Add New Website</span>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($admin_user, 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= $admin_user ?></div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
    <a href="api/logout.php" class="nav-item" style="color:var(--danger)" id="logout-btn">
      <span class="nav-icon">🚪</span>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- ── Main Content ─────────────────────────────────────────── -->
<div class="main-content">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="btn btn-icon btn-ghost mobile-menu-btn" onclick="openSidebar()" aria-label="Open menu">☰</button>
      <span class="topbar-title">Dashboard</span>
    </div>
    <div class="topbar-right">
      <div class="search-container">
        <span class="search-icon">🔍</span>
        <input
          type="search"
          class="search-input"
          placeholder="Search websites…"
          data-search
          aria-label="Search websites"
          id="topbar-search"
        >
      </div>
      <button class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme" title="Toggle theme">🌙</button>
      <button class="btn btn-primary btn-sm" onclick="openAddModal()" id="add-website-btn">
        ➕ Add Website
      </button>
    </div>
  </header>

  <!-- Page content -->
  <main class="page-content">

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon stat-icon-purple">🌐</div>
        <div class="stat-info">
          <div class="stat-label">Total Websites</div>
          <div class="stat-value" id="stat-total-sites">–</div>
          <div class="stat-sub">Hosted static sites</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-blue">💾</div>
        <div class="stat-info">
          <div class="stat-label">Storage Used</div>
          <div class="stat-value" id="stat-total-storage">–</div>
          <div class="stat-sub">Across all websites</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon-green">⏱️</div>
        <div class="stat-info">
          <div class="stat-label">Last Deployment</div>
          <div class="stat-value" id="stat-last-upload" style="font-size:1.1rem;padding-top:.25rem">–</div>
          <div class="stat-sub">Most recent upload</div>
        </div>
      </div>
    </div>

    <!-- Table section -->
    <section class="table-section" aria-label="Hosted websites">
      <div class="table-header">
        <div class="table-header-left">
          <span class="table-header-title">Hosted Websites</span>
          <span id="table-count" class="table-header-count">0</span>
        </div>
        <div class="table-controls">
          <div class="search-container" style="width:200px">
            <span class="search-icon">🔍</span>
            <input
              type="search"
              class="search-input"
              placeholder="Search…"
              data-search
              aria-label="Search websites"
              id="table-search"
            >
          </div>
          <select id="status-filter" class="filter-select" aria-label="Filter by status">
            <option value="all">All Status</option>
            <option value="live">Live</option>
            <option value="broken">Broken</option>
          </select>
          <button class="btn btn-primary btn-sm" onclick="openAddModal()" id="add-website-table-btn">
            ➕ Add Website
          </button>
        </div>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th data-sort="name">Website <span class="sort-icon">⇅</span></th>
              <th>URL</th>
              <th data-sort="deployed_at" class="sorted">Upload Date <span class="sort-icon">↓</span></th>
              <th data-sort="size">Size <span class="sort-icon">⇅</span></th>
              <th data-sort="status">Status <span class="sort-icon">⇅</span></th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="sites-tbody">
            <tr class="loading-row">
              <td colspan="6">
                <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;padding:1rem">
                  <div class="spinner" style="border-top-color:var(--accent-primary);border-color:var(--border);"></div>
                  Loading…
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<!-- Toast container -->
<div id="toast-container" class="toast-container" aria-live="polite"></div>

<!-- ══════════════════════════════════════════════════════════
     ADD WEBSITE MODAL
═══════════════════════════════════════════════════════════ -->
<div id="add-modal-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="add-modal-title">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h2 class="modal-title" id="add-modal-title">🚀 Add New Website</h2>
      <button class="modal-close" onclick="closeAddModal()" aria-label="Close">✕</button>
    </div>
    <form id="add-form" novalidate>
      <div class="modal-body">

        <!-- Error box -->
        <div id="add-form-error" class="login-error hidden" role="alert">
          <span>⚠️</span>
          <span id="add-form-error-msg"></span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group" style="grid-column:1/3">
            <label class="form-label" for="add-name">Website Name <span style="color:var(--danger)">*</span></label>
            <input
              class="form-input"
              type="text"
              id="add-name"
              name="name"
              placeholder="e.g. my-portfolio"
              pattern="[a-z0-9][a-z0-9\-]{0,49}"
              required
              autocomplete="off"
            >
            <div id="add-name-error" class="form-error"></div>
            <div class="form-hint">Lowercase letters, numbers, and hyphens only. Max 50 characters.</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="add-title">Website Title</label>
            <input class="form-input" type="text" id="add-title" name="title" placeholder="My Portfolio">
            <div class="form-hint">Display name (optional)</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="add-desc">Description</label>
            <input class="form-input" type="text" id="add-desc" name="description" placeholder="Short description…">
          </div>
        </div>

        <!-- Drop zone -->
        <div class="form-group">
          <label class="form-label">ZIP File <span style="color:var(--danger)">*</span></label>
          <div class="drop-zone" id="add-drop-zone" role="button" tabindex="0" aria-label="Upload ZIP file">
            <input type="file" id="add-zip" name="zipfile" accept=".zip,application/zip" required>
            <div class="drop-zone-icon">📦</div>
            <div class="drop-zone-text">Drag & drop your ZIP here</div>
            <div class="drop-zone-sub">or click to browse · ZIP files only · Max 500 MB</div>
          </div>
          <div id="add-file-info" class="file-info hidden">
            <span class="file-info-icon">📦</span>
            <span class="file-info-name"></span>
            <span class="file-info-size"></span>
            <button type="button" class="file-info-clear" title="Remove file">✕</button>
          </div>
        </div>

        <!-- Upload progress -->
        <div id="add-upload-progress" class="upload-progress hidden">
          <div class="progress-text">
            <span id="add-progress-label">Uploading…</span>
            <span id="add-progress-pct">0%</span>
          </div>
          <div class="progress-bar-container">
            <div class="progress-bar-fill" id="add-progress-fill"></div>
          </div>
        </div>

        <div style="background:var(--bg-tertiary);border-radius:var(--radius-md);padding:.85rem 1rem;font-size:.8rem;color:var(--text-secondary)">
          <strong>📋 Requirements:</strong> Your ZIP must contain <code style="color:var(--text-accent)">index.html</code> at the root level.
          Only static files are allowed (HTML, CSS, JS, images, fonts, etc.). PHP and server-side scripts are rejected.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="add-submit-btn">🚀 Deploy Website</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
═══════════════════════════════════════════════════════════ -->
<div id="delete-modal-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
  <div class="modal modal-sm">
    <div class="modal-header">
      <h2 class="modal-title" id="delete-modal-title">Delete Website</h2>
      <button class="modal-close" onclick="closeDeleteModal()" aria-label="Close">✕</button>
    </div>
    <div class="modal-body">
      <div class="confirm-icon-danger">🗑️</div>
      <div class="confirm-title">Delete this website?</div>
      <div class="confirm-message">
        You are about to permanently delete <strong id="delete-site-name"></strong>.
        All files will be removed and the URL will stop working immediately.
        <br><br>
        <span style="color:var(--danger);font-weight:600">This action cannot be undone.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
      <button class="btn btn-danger" id="delete-confirm-btn" onclick="confirmDelete()">🗑️ Delete</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     RENAME MODAL
═══════════════════════════════════════════════════════════ -->
<div id="rename-modal-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="rename-modal-title">
  <div class="modal modal-sm">
    <div class="modal-header">
      <h2 class="modal-title" id="rename-modal-title">✏️ Rename Website</h2>
      <button class="modal-close" onclick="closeRenameModal()" aria-label="Close">✕</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-secondary);font-size:.875rem">
        Renaming <strong id="rename-old-name"></strong> will change its public URL.
        Make sure to update any links pointing to the old URL.
      </p>
      <div class="form-group">
        <label class="form-label" for="rename-new-name">New Name</label>
        <input
          class="form-input"
          type="text"
          id="rename-new-name"
          placeholder="new-website-name"
          pattern="[a-z0-9][a-z0-9\-]{0,49}"
          autocomplete="off"
        >
        <div id="rename-error" class="form-error"></div>
        <div class="form-hint">Lowercase letters, numbers, hyphens only.</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeRenameModal()">Cancel</button>
      <button class="btn btn-primary" id="rename-confirm-btn" onclick="confirmRename()">✏️ Rename</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     REPLACE MODAL
═══════════════════════════════════════════════════════════ -->
<div id="replace-modal-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="replace-modal-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="replace-modal-title">🔄 Replace Website</h2>
      <button class="modal-close" onclick="closeReplaceModal()" aria-label="Close">✕</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-secondary);font-size:.875rem">
        Upload a new ZIP to replace <strong id="replace-site-name"></strong>.
        The URL will remain the same. All current files will be overwritten.
      </p>

      <!-- Error box -->
      <div id="replace-form-error" class="login-error hidden" role="alert">
        <span>⚠️</span>
        <span id="replace-form-error-msg"></span>
      </div>

      <div class="form-group">
        <label class="form-label">New ZIP File <span style="color:var(--danger)">*</span></label>
        <div class="drop-zone" id="replace-drop-zone" role="button" tabindex="0" aria-label="Upload replacement ZIP">
          <input type="file" id="replace-zip" accept=".zip,application/zip">
          <div class="drop-zone-icon">📦</div>
          <div class="drop-zone-text">Drag & drop your new ZIP</div>
          <div class="drop-zone-sub">or click to browse · ZIP files only</div>
        </div>
        <div id="replace-file-info" class="file-info hidden">
          <span class="file-info-icon">📦</span>
          <span class="file-info-name"></span>
          <span class="file-info-size"></span>
          <button type="button" class="file-info-clear" title="Remove file">✕</button>
        </div>
      </div>

      <div id="replace-upload-progress" class="upload-progress hidden">
        <div class="progress-text">
          <span>Uploading…</span>
          <span id="replace-progress-pct">0%</span>
        </div>
        <div class="progress-bar-container">
          <div class="progress-bar-fill" id="replace-progress-fill"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeReplaceModal()">Cancel</button>
      <button class="btn btn-primary" id="replace-confirm-btn" onclick="confirmReplace()">🔄 Replace</button>
    </div>
  </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
