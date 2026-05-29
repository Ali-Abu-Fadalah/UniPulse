<?php
$inc = is_dir('Includes') ? 'Includes' : 'includes';
require_once $inc . '/auth.php';
require_once $inc . '/db.php';

require_login();
require_admin();

$name     = $_SESSION['full_name'];
$initial  = strtoupper(substr($name, 0, 1));
$first    = htmlspecialchars(explode(' ', $name)[0]);

// High level stats
$total_users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_notes = $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
$total_skills = $pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — UniHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=6">
    <link rel="stylesheet" href="assets/css/enhancements.css?v=6">
    <link rel="stylesheet" href="assets/css/tailwind.css?v=6">
    <script src="assets/js/theme.js?v=6"></script>
</head>
<body>

<div class="app">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">U</div>
            <span class="wordmark">UniHub <span>Admin</span></span>
            <button class="icon-btn sidebar-lock-btn" id="sidebar-lock-btn" title="Toggle Sidebar" style="margin-left: auto; width: 28px; height: 28px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
            </button>
        </div>

        <div class="sidebar-nav">
            <span class="sidebar-section-label">Management</span>

            <button class="nav-item active" data-tab="users">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                <span>Users</span>
            </button>

            <button class="nav-item" data-tab="notes">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                <span>Notes</span>
            </button>

            <button class="nav-item" data-tab="skills">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Skills</span>
            </button>

            <button class="nav-item" data-tab="products">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
                <span>Products</span>
            </button>

            <button class="nav-item" data-tab="events">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                <span>Events</span>
            </button>
            
        </div>

        <div class="sidebar-bottom">
            <div class="user-chip">
                <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($name) ?></div>
                    <div class="role">Admin User</div>
                </div>
            </div>
            <a href="dashboard.php" class="nav-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                <span>Back to App</span>
            </a>
            <a href="logout.php" class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" /></svg>
                <span>Sign Out</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <button class="mobile-menu-btn" id="menu-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            <h1 class="topbar-title" id="topbar-title">Users</h1>
            <div class="topbar-right" style="margin-left: auto;">
                <div class="theme-switcher">
                    <button class="theme-btn active" data-theme="dark" title="Dark Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                    </button>
                    <button class="theme-btn" data-theme="light" title="Light Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21M4.22 4.22l1.58 1.58m12.4 12.4 1.58 1.58M3 12h2.25m13.5 0H21M6.78 17.22l-1.58 1.58m12.4-12.4 1.58-1.58M12 7.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" /></svg>
                    </button>
                    <button class="theme-btn" data-theme="blue" title="Blue Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-1.897v-1.476ZM21.75 12.75a4.5 4.5 0 0 1-4.75 4.5h-.75a.75.75 0 0 0-.75.75c0 .414-.336.75-.75.75H13.5a.75.75 0 0 1-.75-.75v-1.476M21.75 12.75A9 9 0 0 0 12 3v1.5a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 0-.75.75v1.5a.75.75 0 0 1-.75.75H7.5A.75.75 0 0 0 6.75 9v1.5a.75.75 0 0 1-.75.75H4.5A2.25 2.25 0 0 0 2.25 13.5v.75m19.5-1.5a9 9 0 0 1-2.25 5.86" /></svg>
                    </button>
                    <div class="theme-indicator"></div>
                </div>
            </div>
        </div>

        <main class="content-area">
            
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-wrap green">👥</div>
                    </div>
                    <div class="stat-value"><?= $total_users ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-wrap purple">📝</div>
                    </div>
                    <div class="stat-value"><?= $total_notes ?></div>
                    <div class="stat-label">Total Notes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon-wrap blue">🔄</div>
                    </div>
                    <div class="stat-value"><?= $total_skills ?></div>
                    <div class="stat-label">Total Skills</div>
                </div>
            </div>

            <!-- USERS TAB -->
            <div class="tab-panel active" id="tab-users">
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-users-list">
                            <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <input type="hidden" id="current-user-id" value="<?= $_SESSION['user_id'] ?>">

            <!-- NOTES TAB -->
            <div class="tab-panel" id="tab-notes">
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Content Snippet</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-notes-list">
                            <tr><td colspan="5" style="text-align:center;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SKILLS TAB -->
            <div class="tab-panel" id="tab-skills">
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Offered</th>
                                <th>Needed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-skills-list">
                            <tr><td colspan="5" style="text-align:center;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PRODUCTS TAB -->
            <div class="tab-panel" id="tab-products">
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Seller</th>
                                <th>Title</th>
                                <th>Price</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-products-list">
                            <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- EVENTS TAB -->
            <div class="tab-panel" id="tab-events">
                <div class="admin-table-card">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Host</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="admin-events-list">
                            <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<script src="assets/js/admin.js?v=<?= time() ?>"></script>
<script>
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var menuBtn = document.getElementById('menu-toggle');

    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    const TAB_LABELS = {
        'users':    'Users',
        'notes':    'Notes',
        'skills':   'Skills',
        'products': 'Products',
        'events':   'Events'
    };

    function switchAdminTab(tabId) {
        if (!tabId) return;
        
        // Update URL hash without jumping
        // history.replaceState(null, null, '#tab-' + tabId);

        // Update UI
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => {
            if (n.dataset.tab === tabId) n.classList.add('active');
            else n.classList.remove('active');
        });

        const panel = document.getElementById('tab-' + tabId);
        if (panel) panel.classList.add('active');

        document.getElementById('topbar-title').textContent = TAB_LABELS[tabId] || 'Admin';
        
        Admin.loadTab(tabId);
    }

    // Use event delegation for nav items
    document.addEventListener('click', function(e) {
        const navItem = e.target.closest('.nav-item[data-tab]');
        if (navItem) {
            e.preventDefault();
            switchAdminTab(navItem.dataset.tab);
        }
    });

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash.replace('#tab-', '');
        if (hash && TAB_LABELS[hash]) {
            switchAdminTab(hash);
        } else {
            switchAdminTab('users');
        }
    });
</script>

<script src="assets/js/sidebar.js"></script>
</body>
</html>
