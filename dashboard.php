<?php
$inc = is_dir('Includes') ? 'Includes' : 'includes';
require_once $inc . '/auth.php';
require_once $inc . '/db.php';

require_login();

$name    = $_SESSION['full_name'];
$email   = $_SESSION['email'];
$initial = strtoupper(substr($name, 0, 1));
$first   = htmlspecialchars(explode(' ', $name)[0]);
$avatar  = $_SESSION['avatar'] ?? '';

$stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
$total_users = $stmt->fetch()['total'];

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM notes WHERE user_id = ?');
$stmt->execute([$user_id]);
$total_notes = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM skills WHERE user_id = ?');
$stmt->execute([$user_id]);
$total_skills = $stmt->fetch()['total'];

$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM messages WHERE sender_id = ? OR receiver_id = ?');
$stmt->execute([$user_id, $user_id]);
$total_messages = $stmt->fetch()['total'];

// Real recent activity (last 5 actions across notes, skills, messages)
$activity_items = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM (
            SELECT SUBSTR(content, 1, 70) AS label, created_at, 'note' AS type
            FROM notes WHERE user_id = ?
            UNION ALL
            SELECT CONCAT(offered, ' \u21c4 ', needed), created_at, 'skill' AS type
            FROM skills WHERE user_id = ?
            UNION ALL
            SELECT SUBSTR(message, 1, 70), created_at, 'message' AS type
            FROM messages WHERE sender_id = ?
        ) ORDER BY created_at DESC LIMIT 5
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $activity_items = $stmt->fetchAll();
} catch (Exception $ex) { $activity_items = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — UniHub</title>
    <meta name="description" content="Your UniHub student campus portal.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false,
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/style.css?v=6">
    <link rel="stylesheet" href="assets/css/enhancements.css?v=6">
    <link rel="stylesheet" href="assets/css/tailwind.css?v=6">
    <script src="assets/js/theme.js?v=6"></script>
</head>
<body>

<div class="app">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-logo">
            <div class="logo-icon">U</div>
            <span class="wordmark">Uni<span>Hub</span></span>
            <button class="icon-btn sidebar-lock-btn" id="sidebar-lock-btn" title="Toggle Sidebar" style="margin-left: auto; width: 28px; height: 28px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" /></svg>
            </button>
        </div>

        <nav class="sidebar-nav">

            <span class="sidebar-section-label">Main</span>

            <button class="nav-item active" data-tab="dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                <span>Dashboard</span>
            </button>

            <button class="nav-item" data-tab="study-tools">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                <span>Study Tools</span>
            </button>

            <button class="nav-item" data-tab="skill-exchange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                <span>Skill Exchange</span>
            </button>

            <span class="sidebar-section-label">Explore</span>

            <button class="nav-item" data-tab="marketplace">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
                <span>Marketplace</span>
            </button>

            <button class="nav-item" data-tab="community">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                <span>Messages</span>
            </button>

            <button class="nav-item" data-tab="events">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                <span>Events</span>
            </button>

            <span class="sidebar-section-label">Account</span>

            <button class="nav-item" data-tab="profile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                <span>Profile</span>
            </button>

            <?php if (is_admin()): ?>
            <div class="nav-group" id="admin-nav-group">
                <button class="nav-item nav-has-submenu" id="admin-menu-btn" data-tab="admin">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" /></svg>
                    <span>Admin Panel</span>
                    <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="margin-left: auto; width: 12px; height: 12px; transition: transform var(--tr);"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div class="submenu" id="admin-submenu" style="display: none; padding-left: 1.5rem; flex-direction: column; gap: 2px; margin-top: 2px;">
                    <button class="nav-item submenu-item" data-tab="admin" data-subtab="users" style="padding: 0.5rem 0.85rem; font-size: 0.82rem;">
                        <span>👥 Users</span>
                    </button>
                    <button class="nav-item submenu-item" data-tab="admin" data-subtab="notes" style="padding: 0.5rem 0.85rem; font-size: 0.82rem;">
                        <span>📝 Notes</span>
                    </button>
                    <button class="nav-item submenu-item" data-tab="admin" data-subtab="skills" style="padding: 0.5rem 0.85rem; font-size: 0.82rem;">
                        <span>🔄 Skills</span>
                    </button>
                    <button class="nav-item submenu-item" data-tab="admin" data-subtab="marketplace" style="padding: 0.5rem 0.85rem; font-size: 0.82rem;">
                        <span>🛒 Marketplace</span>
                    </button>
                    <button class="nav-item submenu-item" data-tab="admin" data-subtab="events" style="padding: 0.5rem 0.85rem; font-size: 0.82rem;">
                        <span>📅 Events</span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </nav>

        <div class="sidebar-bottom">
            <div class="user-chip">
                <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($name) ?></div>
                    <div class="role"><?= is_admin() ? 'Admin User' : 'Student' ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" /></svg>
                <span>Sign Out</span>
            </a>
        </div>

    </aside>

    <!-- ── MAIN ── -->
    <div class="main">

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="mobile-menu-btn" id="menu-toggle" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>
            <span class="topbar-title" id="topbar-title">Dashboard</span>

            <div class="topbar-search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input type="text" id="search-input" placeholder="Search UniHub…">
            </div>

            <div class="topbar-right">
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
                <div class="icon-btn-container" style="position: relative;">
                    <div class="icon-btn" id="notif-btn" title="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                        <span class="notif-badge" id="notif-badge" style="display: none;">0</span>
                    </div>
                    
                    <div class="notif-dropdown" id="notif-dropdown">
                        <div class="notif-dropdown-header">
                            <h3>Notifications</h3>
                            <div class="notif-actions">
                                <button id="notif-read-all" class="notif-header-btn">Mark all read</button>
                                <button id="notif-clear" class="notif-header-btn">Clear</button>
                            </div>
                        </div>
                        <div class="notif-dropdown-list" id="notif-dropdown-list">
                            <div class="notif-loading">Loading...</div>
                        </div>
                    </div>
                </div>
                <?php if ($avatar): ?>
                    <div class="user-avatar" style="cursor:pointer;background-image:url('<?= htmlspecialchars($avatar) ?>');background-size:cover;background-position:center;" title="<?= htmlspecialchars($email) ?>"></div>
                <?php else: ?>
                    <div class="user-avatar" style="cursor:pointer" title="<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($initial) ?></div>
                <?php endif; ?>
            </div>
        </header>

        <!-- PAGE PANELS -->
        <main class="page">

            <!-- ── DASHBOARD TAB ── -->
            <div class="tab-panel active" id="tab-dashboard">

                <div class="welcome-banner">
                    <div>
                        <h1>Good day, <?= $first ?> 👋</h1>
                        <p>Here’s your campus hub. Ready to get things done?</p>
                    </div>
                    <div class="banner-actions">
                        <button class="btn-outline" onclick="switchTab('study-tools')">My Notes</button>
                        <button class="btn btn-primary btn-sm" onclick="switchTab('skill-exchange')">Skill Exchange</button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon-wrap purple">📝</div>
                        </div>
                        <div class="stat-value"><?= $total_notes ?></div>
                        <div class="stat-label">Saved Notes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon-wrap blue">🔄</div>
                        </div>
                        <div class="stat-value"><?= $total_skills ?></div>
                        <div class="stat-label">Skills Offered/Needed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon-wrap green">👥</div>
                            <span class="stat-trend up">Live</span>
                        </div>
                        <div class="stat-value"><?= $total_users ?></div>
                        <div class="stat-label">Students Joined</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-icon-wrap amber">💬</div>
                        </div>
                        <div class="stat-value"><?= $total_messages ?></div>
                        <div class="stat-label">Messages</div>
                    </div>
                </div>

                <div class="two-col">

                    <div class="section-card">
                        <div class="section-head">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="activity-list">
                        <?php if (empty($activity_items)): ?>
                            <div class="activity-item">
                                <div class="activity-dot purple"></div>
                                <div class="activity-body">
                                    <div class="activity-text">Welcome to UniHub!</div>
                                    <div class="activity-time">Your activity will appear here once you start using the platform.</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($activity_items as $item):
                                $dot   = $item['type'] === 'note' ? 'purple' : ($item['type'] === 'skill' ? 'green' : 'blue');
                                $label = $item['type'] === 'note' ? 'Note saved' : ($item['type'] === 'skill' ? 'Skill added' : 'Message sent');
                                $when  = date('M j, g:ia', strtotime($item['created_at']));
                            ?>
                            <div class="activity-item">
                                <div class="activity-dot <?= $dot ?>"></div>
                                <div class="activity-body">
                                    <div class="activity-text"><?= htmlspecialchars($item['label']) ?></div>
                                    <div class="activity-time"><?= $label ?> &middot; <?= $when ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:1.25rem;">

                        <div class="section-card">
                            <div class="section-head"><h3>Quick Actions</h3></div>
                            <div class="quick-actions">
                                <button class="qa-item" onclick="switchTab('study-tools')">
                                    <div class="qa-icon purple">📚</div>
                                    <span class="qa-label">Open Study Tools</span>
                                    <span class="qa-arrow">›</span>
                                </button>
                                <button class="qa-item" onclick="switchTab('skill-exchange')">
                                    <div class="qa-icon blue">🔄</div>
                                    <span class="qa-label">Skill Exchange</span>
                                    <span class="qa-arrow">›</span>
                                </button>
                                <button class="qa-item" onclick="switchTab('marketplace')">
                                    <div class="qa-icon amber">🛒</div>
                                    <span class="qa-label">Browse Marketplace</span>
                                    <span class="qa-arrow">›</span>
                                </button>
                                <button class="qa-item" onclick="switchTab('community')">
                                    <div class="qa-icon green">💬</div>
                                    <span class="qa-label">Join Community</span>
                                    <span class="qa-arrow">›</span>
                                </button>
                            </div>
                        </div>

                        <div class="section-card">
                            <div class="section-head"><h3>Upcoming Deadlines</h3></div>
                            <div class="deadline-list">
                                <div class="deadline-item">
                                    <div class="deadline-body">
                                        <div class="dl-title">No deadlines yet</div>
                                        <div class="dl-sub">Deadlines from your courses will appear here</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ── STUDY TOOLS TAB ── -->
            <div class="tab-panel" id="tab-study-tools">
                <div class="notes-page">

                    <h2>My Notes</h2>
                    <p class="notes-subtitle">Capture ideas, lecture notes, and anything worth keeping.</p>

                    <div class="note-compose">
                        <textarea
                            id="note-input"
                            placeholder="What’s on your mind? (Ctrl+Enter to save)"
                            maxlength="1000"
                        ></textarea>
                        <p class="note-error" id="note-error"></p>
                        <div class="compose-footer">
                            <span class="char-count" id="char-count">0 / 1000</span>
                            <div class="compose-actions">
                                <span class="compose-hint">Ctrl+Enter to save</span>
                                <button class="btn-save" id="note-add-btn">Save Note</button>
                            </div>
                        </div>
                    </div>

                    <div class="notes-header">
                        <h3>Your Notes</h3>
                        <span class="notes-count-label" id="notes-count">Loading…</span>
                    </div>

                    <div class="notes-list" id="notes-list"></div>

                </div>
            </div>

            <!-- ── SKILL EXCHANGE TAB ── -->
            <div class="tab-panel" id="tab-skill-exchange">
                <div class="skills-page">

                    <h2>Skill Exchange</h2>
                    <p class="skills-subtitle">Share what you know. Learn what you don’t. Find your perfect match.</p>

                    <div id="skills-loading">Loading…</div>

                    <div class="skills-top-grid">

                        <div class="skill-form-card">
                            <div class="skill-card-title">Add a Skill</div>
                            <div class="skill-input-group">
                                <label>I can teach</label>
                                <input type="text" id="skill-offered" placeholder="e.g. Python, Graphic Design, Calculus…" maxlength="60">
                            </div>
                            <div class="skill-input-group">
                                <label>I want to learn</label>
                                <input type="text" id="skill-needed" placeholder="e.g. Essay Writing, Guitar, Spanish…" maxlength="60">
                            </div>
                            <p class="skill-error" id="skill-error"></p>
                            <button class="btn-add-skill" id="skill-add-btn">Add Skill</button>
                        </div>

                        <div class="my-skills-card">
                            <div class="skill-card-title">
                                My Skills
                                <span class="skill-card-count" id="my-skills-count">0 / 10</span>
                            </div>
                            <div class="my-skills-list" id="my-skills-list">
                                <p class="skill-empty-msg">No skills added yet.</p>
                            </div>
                        </div>

                    </div>

                    <div class="skills-section" id="matches-section" style="display:none">
                        <div class="skills-section-head">
                            <h3>Your Matches</h3>
                            <span class="skills-section-count" id="matches-count"></span>
                        </div>
                        <div class="matches-grid" id="matches-list"></div>
                    </div>

                    <div class="skills-section">
                        <div class="skills-section-head" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <h3>Browse Students</h3>
                                <span class="skills-section-count" id="others-count"></span>
                            </div>
                            <div class="skill-search-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                                <input type="text" id="skill-search" placeholder="Search by skill...">
                            </div>
                        </div>
                        <div class="others-grid" id="others-list"></div>
                    </div>

                </div>
            </div>

            <!-- ── MARKETPLACE TAB ── -->
            <div class="tab-panel" id="tab-marketplace">
                <div class="market-page">
                    <div class="market-header-row">
                        <div>
                            <h2>Campus Marketplace</h2>
                            <p class="market-subtitle">Buy, sell, and trade textbooks, notes, electronics, and gear with peers.</p>
                        </div>
                        <div class="market-tabs">
                            <button class="market-tab-btn active" data-view="browse">Browse Items</button>
                            <button class="market-tab-btn" data-view="sell">Sell / My Listings</button>
                        </div>
                    </div>

                    <!-- BROWSE VIEW -->
                    <div id="market-view-browse" class="market-content-view">
                        <div class="market-products-grid" id="market-products-grid"></div>
                    </div>

                    <!-- SELL & MY LISTINGS VIEW -->
                    <div id="market-view-sell" class="market-content-view" style="display:none;">
                        <div class="market-form-column">
                            <div class="market-form-card">
                                <div class="market-card-title">List an Item for Sale</div>
                                <div class="market-input-group">
                                    <label for="market-title">Item Title</label>
                                    <input type="text" id="market-title" placeholder="e.g. Calculus Textbook, Scientific Calculator..." maxlength="100">
                                </div>
                                <div class="market-input-group">
                                    <label for="market-price">Price ($ USD)</label>
                                    <input type="number" id="market-price" placeholder="0.00" step="0.01" min="0.01">
                                </div>
                                <div class="market-input-group">
                                    <label for="market-description">Description</label>
                                    <textarea id="market-description" placeholder="Describe the item's condition, meet-up details..." maxlength="1000"></textarea>
                                </div>
                                <div class="market-input-group">
                                    <label>Product Image <span class="field-hint">JPG, PNG or WebP · Max 2MB</span></label>
                                    <div class="avatar-upload-row">
                                        <button type="button" class="btn-choose-avatar" id="market-choose-btn">Choose Photo</button>
                                        <span class="avatar-filename" id="market-filename"></span>
                                    </div>
                                    <input type="file" id="market-image" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                                </div>
                                <p class="market-error" id="market-error"></p>
                                <button class="btn-add-skill" id="market-add-btn" style="width:100%;">Post Item</button>
                            </div>
                        </div>

                        <div class="market-listings-column">
                            <div class="my-skills-card" style="height: 100%;">
                                <div class="skill-card-title">My Active Listings</div>
                                <div class="my-listings-list" id="my-listings-list">
                                    <p class="market-empty-msg">You have not listed any items for sale yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── MESSAGES TAB ── -->
            <div class="tab-panel" id="tab-community">
                <input type="hidden" id="current-user-id" value="<?= $_SESSION['user_id'] ?>">
                <div class="messages-layout">

                    <div class="conv-sidebar">
                        <div class="conv-sidebar-head">
                            <h3>Conversations</h3>
                            <button class="new-chat-btn" id="new-chat-btn" title="New conversation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </button>
                        </div>
                        <div class="conv-list" id="conv-list"></div>
                    </div>

                    <div class="chat-area">

                        <div class="chat-empty-state" id="chat-empty">
                            <div class="chat-empty-icon">💬</div>
                            <p>Select a conversation to start chatting</p>
                            <span>Or message a student from Skill Exchange</span>
                        </div>

                        <div id="chat-panel" style="display:none;flex-direction:column;height:100%;">
                            <div class="chat-header">
                                <div class="chat-header-avatar" id="chat-header-avatar">?</div>
                                <span class="chat-header-name" id="chat-header-name"></span>
                            </div>
                            <div class="chat-messages" id="chat-messages"></div>
                            <div class="chat-input-area">
                                <input type="text" id="chat-input" placeholder="Type a message… (Enter to send)" maxlength="1000">
                                <button class="chat-send-btn" id="chat-send-btn" title="Send">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ── EVENTS TAB ── -->
            <div class="tab-panel" id="tab-events">
                <div class="events-page">
                    <div class="market-header-row">
                        <div>
                            <h2>Campus Events</h2>
                            <p class="market-subtitle">Join workshops, seminars, campus clubs, and social meetups.</p>
                        </div>
                        <div class="market-tabs">
                            <button class="events-tab-btn active" data-view="browse">Upcoming Events</button>
                            <?php if (is_admin()): ?>
                            <button class="events-tab-btn" data-view="host">Host / My Events</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- BROWSE EVENTS VIEW -->
                    <div id="events-view-browse" class="market-content-view">
                        <div class="events-list" id="events-list"></div>
                    </div>

                    <?php if (is_admin()): ?>
                    <!-- HOST & MY EVENTS VIEW -->
                    <div id="events-view-host" class="market-content-view" style="display:none;">
                        <div class="market-form-column">
                            <div class="market-form-card">
                                <div class="market-card-title">Host an Event</div>
                                <div class="market-input-group">
                                    <label for="event-title">Event Title</label>
                                    <input type="text" id="event-title" placeholder="e.g. Intro to Python Workshop, Study Group..." maxlength="100">
                                </div>
                                <div class="market-input-group" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                                    <div>
                                        <label for="event-date">Date</label>
                                        <input type="date" id="event-date">
                                    </div>
                                    <div>
                                        <label for="event-time">Time</label>
                                        <input type="time" id="event-time">
                                    </div>
                                </div>
                                <div class="market-input-group">
                                    <label for="event-location">Location</label>
                                    <input type="text" id="event-location" placeholder="e.g. Room 402, Campus Library, Zoom..." maxlength="100">
                                </div>
                                <div class="market-input-group">
                                    <label for="event-description">Description</label>
                                    <textarea id="event-description" placeholder="Describe the event, agenda, target audience..." maxlength="1000"></textarea>
                                </div>
                                <p class="market-error" id="event-error"></p>
                                <button class="btn-add-skill" id="event-add-btn" style="width:100%;">Host Event</button>
                            </div>
                        </div>

                        <div class="market-listings-column">
                            <div class="my-skills-card" style="height: 100%;">
                                <div class="skill-card-title">My Hosted Events</div>
                                <div class="my-listings-list" id="my-events-list">
                                    <p class="market-empty-msg">You have not hosted any events yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── PROFILE TAB ── -->
            <div class="tab-panel" id="tab-profile">
                <div class="profile-page">

                    <div class="profile-card">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar-circle" id="profile-avatar-lg">?</div>
                        </div>
                        <div class="profile-card-info">
                            <h2 id="profile-display-name">Loading…</h2>
                            <p class="profile-email" id="profile-display-email"></p>
                            <p class="profile-bio-display" id="profile-display-bio"></p>
                        </div>
                    </div>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="ps-value" id="stat-notes">—</div>
                            <div class="ps-label">Notes</div>
                        </div>
                        <div class="profile-stat">
                            <div class="ps-value" id="stat-skills">—</div>
                            <div class="ps-label">Skills</div>
                        </div>
                        <div class="profile-stat">
                            <div class="ps-value" id="stat-since">—</div>
                            <div class="ps-label">Member since</div>
                        </div>
                    </div>

                    <div class="profile-edit-card">
                        <h3>Edit Profile</h3>

                        <div class="profile-field">
                            <label for="profile-name-input">Full name</label>
                            <input type="text" id="profile-name-input" placeholder="Your full name" maxlength="80">
                        </div>

                        <div class="profile-field">
                            <label for="profile-bio-input">
                                Bio
                                <span class="field-hint">Tell other students about yourself</span>
                            </label>
                            <textarea id="profile-bio-input" maxlength="300" placeholder="e.g. Computer Science student who loves building things…"></textarea>
                            <div class="bio-footer">
                                <span class="bio-count" id="bio-char-count">0 / 300</span>
                            </div>
                        </div>

                        <div class="profile-field">
                            <label>Profile photo <span class="field-hint">JPG, PNG or WebP · Max 2MB</span></label>
                            <div class="avatar-upload-row">
                                <div class="profile-avatar-circle sm" id="profile-avatar-sm">?</div>
                                <button type="button" class="btn-choose-avatar" id="avatar-choose-btn">Choose Photo</button>
                                <span class="avatar-filename" id="avatar-filename"></span>
                            </div>
                            <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                        </div>

                        <p class="profile-save-msg" id="profile-save-msg"></p>
                        <button class="btn-save-profile" id="profile-save-btn">Save Changes</button>
                    </div>

                </div>
            </div>

            <!-- ── ADMIN TAB ── -->
            <div class="tab-panel hidden" id="tab-admin">
                <div class="admin-page">
                    <div class="market-header-row" style="margin-bottom: 2rem;">
                        <div>
                            <h2>Admin Control Center</h2>
                            <p class="market-subtitle">Manage users, content, and system settings.</p>
                        </div>
                        <div class="market-tabs" id="admin-subtabs">
                            <button class="market-tab-btn active" onclick="switchAdminTab('users', this)">Users</button>
                            <button class="market-tab-btn" onclick="switchAdminTab('notes', this)">Notes</button>
                            <button class="market-tab-btn" onclick="switchAdminTab('skills', this)">Skills</button>
                            <button class="market-tab-btn" onclick="switchAdminTab('products', this)">Marketplace</button>
                            <button class="market-tab-btn" onclick="switchAdminTab('events', this)">Events</button>
                        </div>
                    </div>

                    <!-- Tab Contents inside admin glass table card -->
                    <div class="admin-table-card">
                        <!-- Tab Contents -->
                        <div id="admin-subtab-users" class="admin-subtab block">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-users-list">
                                    <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="admin-subtab-notes" class="admin-subtab hidden">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Content Snippet</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-notes-list">
                                    <tr><td colspan="3" style="text-align:center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="admin-subtab-skills" class="admin-subtab hidden">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Offered</th>
                                        <th>Needed</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-skills-list">
                                    <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="admin-subtab-products" class="admin-subtab hidden">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Title</th>
                                        <th>Price</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-products-list">
                                    <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="admin-subtab-events" class="admin-subtab hidden">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Host</th>
                                        <th>Title</th>
                                        <th>Date & Time</th>
                                        <th>RSVPs</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-events-list">
                                    <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="assets/js/main.js?v=6"></script>
<script src="assets/js/notes.js?v=6"></script>
<script src="assets/js/skills.js?v=6"></script>
<script src="assets/js/messages.js?v=6"></script>
<script src="assets/js/profile.js?v=6"></script>
<script src="assets/js/marketplace.js?v=6"></script>
<script src="assets/js/events.js?v=6"></script>
<?php if (is_admin()): ?>
<script src="assets/js/admin.js?v=6"></script>
<?php endif; ?>
<script>
const TAB_LABELS = {
    'dashboard':     'Dashboard',
    'study-tools':   'Study Tools',
    'skill-exchange':'Skill Exchange',
    'marketplace':   'Marketplace',
    'community':     'Messages',
    'events':        'Events',
    'profile':       'Profile',
    'admin':         'Admin Panel',
};

function switchAdminTab(subtabId, btnElement) {
    document.querySelectorAll('.admin-subtab').forEach(el => {
        el.classList.remove('block');
        el.classList.add('hidden');
    });
    document.getElementById('admin-subtab-' + subtabId).classList.remove('hidden');
    document.getElementById('admin-subtab-' + subtabId).classList.add('block');
    
    document.querySelectorAll('#admin-subtabs button').forEach(btn => {
        btn.classList.remove('active');
    });
    btnElement.classList.add('active');
    
    // Highlight matching sidebar submenu item
    document.querySelectorAll('.submenu-item').forEach(item => {
        if (item.dataset.subtab === subtabId) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // Make sure parent Admin menu button is marked active
    const adminMenuBtn = document.getElementById('admin-menu-btn');
    if (adminMenuBtn) adminMenuBtn.classList.add('active');

    // Load content dynamically if Admin object exists
    if (typeof Admin !== 'undefined') {
        Admin.loadTab(subtabId);
    }
}

function switchTab(tabId, isInitial = false) {
    if (typeof Messages !== 'undefined' && tabId !== 'community') {
        Messages.stopAutoRefresh();
    }
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    const panel = document.getElementById('tab-' + tabId);
    if (panel) panel.classList.add('active');

    // Highlight menu button (only parent buttons, not submenu items)
    const btn = document.querySelector('.nav-item[data-tab="' + tabId + '"]:not(.submenu-item)');
    if (btn) btn.classList.add('active');

    // Admin Submenu state sync
    const adminSubmenu = document.getElementById('admin-submenu');
    const adminMenuBtn = document.getElementById('admin-menu-btn');
    if (adminSubmenu && adminMenuBtn) {
        if (tabId === 'admin') {
            adminSubmenu.style.display = 'flex';
            adminMenuBtn.classList.add('active');
            const arrow = adminMenuBtn.querySelector('.submenu-arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
            adminSubmenu.style.display = 'none';
            adminMenuBtn.classList.remove('active');
            const arrow = adminMenuBtn.querySelector('.submenu-arrow');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
            
            // Clear highlights of submenu items
            document.querySelectorAll('.submenu-item').forEach(item => {
                item.classList.remove('active');
            });
        }
    }

    document.getElementById('topbar-title').textContent = TAB_LABELS[tabId] || 'UniHub';

    // Clear search input on tab switch
    const sInput = document.getElementById('search-input');
    if (sInput) sInput.value = '';

    // Lazy-load each tab on first visit (works for both sidebar clicks AND quick-action buttons)
    if (tabId === 'study-tools' && !notesLoaded) {
        notesLoaded = true;
        Notes.load();
    }
    if (tabId === 'skill-exchange' && !skillsLoaded) {
        skillsLoaded = true;
        Skills.load();
    }
    if (tabId === 'community' && !messagesLoaded) {
        messagesLoaded = true;
        Messages.load();
    }
    if (tabId === 'profile' && !profileLoaded) {
        profileLoaded = true;
        Profile.load();
    }
    if (tabId === 'marketplace' && !marketLoaded) {
        marketLoaded = true;
        Marketplace.load();
    }
    if (tabId === 'events' && !eventsLoaded) {
        eventsLoaded = true;
        Events.load();
    }
    if (tabId === 'admin' && !adminLoaded) {
        adminLoaded = true;
        // Determine active subtab
        let activeSubtab = 'users';
        const activeBtn = document.querySelector('#admin-subtabs .market-tab-btn.active');
        if (activeBtn) {
            const onclickAttr = activeBtn.getAttribute('onclick') || '';
            const match = onclickAttr.match(/switchAdminTab\('([^']+)'/);
            if (match) activeSubtab = match[1];
        }
        
        if (typeof Admin !== 'undefined') {
            Admin.loadTab(activeSubtab);
        }
        
        // Highlight matching submenu item
        document.querySelectorAll('.submenu-item').forEach(item => {
            if (item.dataset.subtab === activeSubtab) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Persist tab choice
    localStorage.setItem('unihub_active_tab', tabId);
    if (window.location.hash !== '#' + tabId) {
        window.location.hash = tabId;
    }

    if (!isInitial) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

var notesLoaded      = false;
var skillsLoaded     = false;
var profileLoaded    = false;
var messagesLoaded   = false;
var marketLoaded     = false;
var eventsLoaded     = false;
var adminLoaded      = false;

document.querySelectorAll('.nav-item[data-tab]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (this.id === 'admin-menu-btn') {
            e.preventDefault();
            e.stopPropagation();
            
            const adminSubmenu = document.getElementById('admin-submenu');
            const isCollapsed = adminSubmenu.style.display === 'none';
            const arrow = this.querySelector('.submenu-arrow');
            
            if (isCollapsed) {
                adminSubmenu.style.display = 'flex';
                if (arrow) arrow.style.transform = 'rotate(180deg)';
                
                // If not currently on admin tab, switch to admin tab and active users subtab
                const activeTab = document.querySelector('.nav-item.active:not(.submenu-item)')?.dataset.tab;
                if (activeTab !== 'admin') {
                    switchTab('admin');
                    const defaultSubtabBtn = document.querySelector(`#admin-subtabs button[onclick*="users"]`);
                    if (defaultSubtabBtn) switchAdminTab('users', defaultSubtabBtn);
                }
            } else {
                adminSubmenu.style.display = 'none';
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
            return;
        }

        const tab = this.dataset.tab;
        const subtab = this.dataset.subtab;
        
        if (tab === 'admin' && subtab) {
            switchTab('admin');
            const subtabBtn = document.querySelector(`#admin-subtabs button[onclick*="${subtab}"]`);
            if (subtabBtn) {
                switchAdminTab(subtab, subtabBtn);
            }
        } else {
            switchTab(tab);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const savedTab = window.location.hash.replace('#', '') || localStorage.getItem('unihub_active_tab') || 'dashboard';
    if (savedTab && savedTab !== 'dashboard' && TAB_LABELS[savedTab]) {
        // If it's an admin subtab (e.g. #admin/users, or we can parse hash query)
        const parts = savedTab.split('/');
        const tab = parts[0];
        const subtab = parts[1];
        
        if (tab === 'admin') {
            switchTab('admin', true);
            if (subtab) {
                const subtabBtn = document.querySelector(`#admin-subtabs button[onclick*="${subtab}"]`);
                if (subtabBtn) switchAdminTab(subtab, subtabBtn);
            }
        } else {
            switchTab(tab, true);
        }
    }
});

// Mobile sidebar is handled by sidebar.js

    // Context-Aware Global Search
    let globalSearchTimeout;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const activeTab = document.querySelector('.nav-item.active')?.dataset.tab || 'dashboard';
            
            clearTimeout(globalSearchTimeout);
            
            if (activeTab === 'study-tools') {
                const notes = document.querySelectorAll('#notes-list .note-card');
                notes.forEach(note => {
                    const text = note.querySelector('.note-content')?.textContent.toLowerCase() || '';
                    if (text.includes(query)) {
                        note.style.display = 'block';
                      } else {
                        note.style.display = 'none';
                      }
                });
            } else if (activeTab === 'skill-exchange') {
                globalSearchTimeout = setTimeout(() => {
                    if (typeof Skills !== 'undefined') Skills.load(query);
                }, 300);
            } else if (activeTab === 'marketplace') {
                globalSearchTimeout = setTimeout(() => {
                    if (typeof Marketplace !== 'undefined') Marketplace.load(query);
                }, 300);
            } else if (activeTab === 'events') {
                globalSearchTimeout = setTimeout(() => {
                    if (typeof Events !== 'undefined') Events.load(query);
                }, 300);
            } else if (activeTab === 'community') {
                const convs = document.querySelectorAll('#conv-list .conv-item');
                convs.forEach(conv => {
                    const name = conv.querySelector('.conv-name')?.textContent.toLowerCase() || '';
                    if (name.includes(query)) {
                        conv.style.display = 'flex';
                    } else {
                        conv.style.display = 'none';
                    }
                });
            }
        });
    }

    // Notifications Logic
    const notifBtn = document.getElementById('notif-btn');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-dropdown-list');
    const notifReadAll = document.getElementById('notif-read-all');
    const notifClear = document.getElementById('notif-clear');

    async function fetchNotifications() {
        try {
            const res = await fetch('modules/notifications.php');
            const data = await res.json();
            if (data.success) {
                // Update badge
                const unread = data.unread_count;
                if (unread > 0) {
                    notifBadge.textContent = unread;
                    notifBadge.style.display = 'flex';
                } else {
                    notifBadge.style.display = 'none';
                }

                // Render list
                if (data.notifications.length === 0) {
                    notifList.innerHTML = `
                        <div class="notif-empty">
                            <span class="notif-empty-icon">🔔</span>
                            <p>No notifications yet</p>
                        </div>
                    `;
                } else {
                    notifList.innerHTML = data.notifications.map(n => {
                        let icon = '🔔';
                        let colorClass = 'notif-icon-generic';
                        if (n.type === 'message') { icon = '💬'; colorClass = 'notif-icon-msg'; }
                        else if (n.type === 'rsvp') { icon = '📅'; colorClass = 'notif-icon-rsvp'; }
                        else if (n.type === 'skill_match') { icon = '🔄'; colorClass = 'notif-icon-skill'; }

                        const readClass = n.is_read ? 'read' : 'unread';
                        
                        // Parse MySQL datetime string to JavaScript Date
                        const dateVal = n.created_at ? new Date(n.created_at.replace(/-/g, '/')) : new Date();
                        const timeStr = formatRelativeTime(dateVal);

                        return `
                            <div class="notif-item ${readClass}" data-id="${n.id}" data-link="${n.link}">
                                <div class="notif-icon ${colorClass}">${icon}</div>
                                <div class="notif-content">
                                    <div class="notif-title">${escapeHTML(n.title)}</div>
                                    <div class="notif-text">${escapeHTML(n.message)}</div>
                                    <div class="notif-time">${timeStr}</div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    // Add click listeners to items
                    document.querySelectorAll('.notif-item').forEach(item => {
                        item.addEventListener('click', async function() {
                            const id = this.dataset.id;
                            const link = this.dataset.link;
                            
                            // Mark as read in backend
                            await fetch('modules/notifications.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'mark_read', id: id })
                            });

                            // Close dropdown
                            notifDropdown.classList.remove('open');
                            
                            // Redirect/Switch tab if link is present
                            if (link && link.startsWith('#')) {
                                const tab = link.substring(1);
                                if (typeof switchTab === 'function') {
                                    switchTab(tab);
                                }
                            }
                            fetchNotifications();
                        });
                    });
                }
            }
        } catch (err) {
            console.error('Error fetching notifications:', err);
        }
    }

    function formatRelativeTime(date) {
        const diffMs = new Date() - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDays = Math.floor(diffHr / 24);

        if (diffSec < 60) return 'Just now';
        if (diffMin < 60) return `${diffMin}m ago`;
        if (diffHr < 24) return `${diffHr}h ago`;
        return `${diffDays}d ago`;
    }

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    // Toggle Dropdown
    notifBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('open');
        if (notifDropdown.classList.contains('open')) {
            fetchNotifications();
        }
    });

    // Close on click outside
    document.addEventListener('click', function(e) {
        if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
    });

    // Mark All Read
    notifReadAll.addEventListener('click', async function(e) {
        e.stopPropagation();
        const res = await fetch('modules/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read' })
        });
        const data = await res.json();
        if (data.success) {
            fetchNotifications();
        }
    });

    // Clear All
    notifClear.addEventListener('click', async function(e) {
        e.stopPropagation();
        const res = await fetch('modules/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
        });
        const data = await res.json();
        if (data.success) {
            fetchNotifications();
        }
    });

    // Initialize and poll
    fetchNotifications();
    setInterval(fetchNotifications, 15000);
    </script>

<div class="sidebar-overlay" id="sidebar-overlay"></div>
<script src="assets/js/sidebar.js?v=6"></script>
</body>
</html>
