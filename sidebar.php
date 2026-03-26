<div class="sidebar">
    <div class="logo-area" style="padding: 10px 0; text-align: center;">
        <h2 style="margin: 0; font-size: 22px;">ACLC Navigator</h2>
        <small style="opacity: 0.7;">Library Management</small>
    </div>
    
    <hr style="opacity: 0.1; margin: 20px 0;">

    <nav>
        <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="apply_leave.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'apply_leave.php' ? 'active' : '' ?>">
            <i class="fas fa-paper-plane"></i> File a Request
        </a>
        <a href="leave_history.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'leave_history.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> My History
        </a>
    </nav>
</div>

<style>
    .sidebar { 
        width: 260px; 
        background: #004a99; 
        color: white; 
        height: 100vh; 
        position: fixed; 
        left: 0; 
        top: 0; 
        padding: 20px; 
        box-sizing: border-box; 
    }
    .nav-link { 
        display: block; 
        color: white; 
        text-decoration: none; 
        padding: 15px; 
        margin: 5px 0; 
        border-radius: 8px; 
        transition: 0.3s; 
    }
    .nav-link:hover { background: rgba(255,255,255,0.1); }
    .active { background: #eab308 !important; color: #004a99 !important; font-weight: bold; }
    .nav-link i { margin-right: 12px; width: 20px; text-align: center; }
</style>