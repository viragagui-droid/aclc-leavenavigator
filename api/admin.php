<?php
session_start();
include 'db.php';

// VERCEL FIX: Siguraduhing tama ang redirect pabalik sa login kung walang session
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: /api/index.php"); 
    exit();
}

// Stats for Dashboard
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];
$total_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'maintenance'"))['total'];
$total_faculty = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'faculty'"))['total'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'"))['total'];

$recent_activities = mysqli_query($conn, "SELECT lr.*, u.name, u.role FROM leave_requests lr JOIN users u ON lr.user_id = u.id ORDER BY lr.id DESC LIMIT 3");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --blue: #004a99; --yellow: #eab308; --light: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--blue); color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; }
        .nav-link { display: block; color: white; text-decoration: none; padding: 12px; margin: 8px 0; border-radius: 5px; }
        .nav-link.active { background: rgba(255,255,255,0.2); border-left: 4px solid var(--yellow); font-weight: bold; }
        .wrapper { margin-left: 260px; width: 100%; }
        .top-nav { background: white; height: 65px; display: flex; justify-content: flex-end; align-items: center; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .logout-btn { background: #ef4444; color: white; text-decoration: none; padding: 8px 18px; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .logout-btn:hover { background: #b91c1c; }
        .main-content { padding: 30px; }
        .tracker-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-top: 5px solid var(--blue); }
        .activity-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<audio id="notifSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<div class="sidebar">
    <h2 style="color:var(--yellow); text-align:center;">ACLC NAV</h2>
    <a href="/api/admin.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="/api/manage_staff.php" class="nav-link"><i class="fas fa-users"></i> Manage Staff</a>
    <a href="/api/leave_requests.php" class="nav-link"><i class="fas fa-file-signature"></i> Leave Requests</a>
    <a href="/api/full_history.php" class="nav-link"><i class="fas fa-history"></i> Master History</a>
</div>

<div class="wrapper">
    <div class="top-nav">
        <span style="margin-right: 20px; color: #64748b; font-size: 13px;">Admin: <b><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></b></span>
        <a href="/api/logout.php" class="logout-btn"><i class="fas fa-power-off"></i> LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="tracker-grid">
            <div class="stat-card"><h3>Admins</h3><h1><?= $total_admins ?></h1></div>
            <div class="stat-card"><h3>Staff</h3><h1><?= $total_staff ?></h1></div>
            <div class="stat-card"><h3>Faculty</h3><h1><?= $total_faculty ?></h1></div>
            <div class="stat-card" style="border-top-color: var(--yellow);">
                <h3>Pending</h3><h1 style="color:var(--yellow);"><?= $pending_count ?></h1>
            </div>
        </div>

        <div class="activity-card">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <table>
                <thead><tr><th>Name</th><th>Type</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($recent_activities)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= strtoupper($row['leave_type']) ?></td>
                        <td><span style="font-weight:bold;"><?= $row['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let isInteracted = false;
document.addEventListener('click', () => { isInteracted = true; }, { once: true });

function checkNotif() {
    $.ajax({
        url: '/api/check_notifications.php', // VERCEL FIX: Added /api/
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.unseen_count > 0) {
                if (isInteracted) {
                    document.getElementById('notifSound').play().catch(() => {});
                }

                Swal.fire({
                    title: 'New Leave Request!',
                    text: 'You have ' + data.unseen_count + ' new request(s) to review.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Review Now',
                    cancelButtonText: 'Later',
                    toast: true,
                    position: 'top-end',
                    timer: 10000 
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/api/leave_requests.php'; // VERCEL FIX: Added /api/
                    }
                });
            }
        }
    });
}

setInterval(checkNotif, 8000);
</script>
</body>
</html>
