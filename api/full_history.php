<?php
session_start();
include 'db.php';

// Security Check: Admin lang ang pwede rito
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// --- FIXED QUERY ---
$query = "SELECT lr.*, u.name, u.role as user_role 
          FROM leave_requests lr 
          JOIN users u ON lr.user_id = u.id 
          WHERE (u.name LIKE '%$search%' 
          OR lr.leave_type LIKE '%$search%' 
          OR lr.status LIKE '%$search%')
          ORDER BY lr.date_applied DESC";

$history_res = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master History | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --blue: #004a99; --yellow: #eab308; --light: #f4f7f6; --dark: #1e293b; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        
        .sidebar { width: 260px; background: var(--blue); color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; z-index: 1000; }
        .sidebar h2 { color: var(--yellow); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; text-align: center; font-size: 20px; }
        .nav-link { display: block; color: white; text-decoration: none; padding: 12px; margin: 8px 0; border-radius: 5px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); }
        .nav-link.active { background: rgba(255,255,255,0.2); border-left: 4px solid var(--yellow); font-weight: bold; }

        .wrapper { margin-left: 260px; width: 100%; display: flex; flex-direction: column; }

        .top-nav { background: white; height: 65px; display: flex; justify-content: flex-end; align-items: center; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .logout-btn { background: #ef4444; color: white; text-decoration: none; padding: 8px 18px; border-radius: 5px; font-weight: bold; font-size: 13px; transition: 0.3s; }
        .logout-btn:hover { background: #b91c1c; }

        .main-content { padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.05); }
        
        .tracking-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tracking-table td, .tracking-table th { padding: 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .tracking-table th { background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-pending { background: #fef9c3; color: #a16207; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }

        .search-box { padding: 8px 15px; border-radius: 5px; border: 1px solid #ddd; width: 250px; outline: none; }
        .search-btn { padding: 8px 20px; background: var(--blue); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        
        .delete-btn { color: #ef4444; text-decoration: none; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .delete-btn:hover { color: #b91c1c; transform: scale(1.1); }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>ACLC NAV</h2>
    <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_staff.php" class="nav-link"><i class="fas fa-users"></i> Manage Staff</a>
    <a href="leave_requests.php" class="nav-link"><i class="fas fa-file-signature"></i> Leave Requests</a>
    <a href="full_history.php" class="nav-link active"><i class="fas fa-history"></i> Master History</a>
</div>

<div class="wrapper">
    <div class="top-nav">
        <span style="margin-right: 20px; color: #64748b; font-size: 13px;">Admin: <b><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></b></span>
        <a href="logout.php" class="logout-btn"><i class="fas fa-power-off"></i> LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; color: #1e293b;"><i class="fas fa-database"></i> Master Leave Records</h3>
                <form method="GET" style="display:flex; gap:10px;">
                    <input type="text" name="search" class="search-box" placeholder="Search name or status..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <table class="tracking-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Inclusive Dates</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_res->num_rows > 0): ?>
                        <?php while($h = $history_res->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($h['name']) ?></strong><br>
                                <small style="color:#64748b;"><?= strtoupper($h['user_role']) ?></small>
                            </td>
                            <td style="text-transform:uppercase; font-weight: 500;"><?= $h['leave_type'] ?></td>
                            <td>
                                <?= ($h['leave_type'] == 'faculty') ? ($h['days_requested'] * 8)." hrs" : $h['days_requested']." day(s)" ?>
                            </td>
                            <td>
                                <span style="color: #475569;">
                                    <?= date('M d', strtotime($h['start_date'])) ?> - <?= date('M d, Y', strtotime($h['end_date'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($h['status']) ?>">
                                    <?= $h['status'] ?>
                                </span>
                            </td>
                            <td style="color: #64748b; font-size: 13px;">
                                <?= date('M d, Y', strtotime($h['date_applied'])) ?>
                            </td>
                            <td style="text-align:center;">
                                <a onclick="confirmDelete(<?= $h['id'] ?>)" class="delete-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the record and REFUND the credits if status is Approved!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_request.php?id=' + id;
            }
        })
    }

    // Para hindi ma-resubmit ang form sa refresh
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }

    // Alert kung galing sa delete_request.php
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'deleted') {
        Swal.fire('Deleted!', 'The record has been removed and credits refunded.', 'success');
    }
</script>

</body>
</html>