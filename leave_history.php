<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FIXED CANCEL LOGIC ---
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    // Siguraduhin na ang pending request lang ang pwedeng burahin
    mysqli_query($conn, "DELETE FROM leave_requests WHERE id = $cancel_id AND user_id = $user_id AND status = 'pending'");
    header("Location: leave_history.php?msg=cancelled");
    exit();
}

// Mark as seen para sa notifications
mysqli_query($conn, "UPDATE leave_requests SET is_seen_by_staff = 1 WHERE user_id = $user_id AND status != 'pending'");

$history_query = mysqli_query($conn, "SELECT * FROM leave_requests WHERE user_id = $user_id ORDER BY date_applied DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Leave History | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #004a99; --accent: #eab308; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; box-sizing: border-box; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .status-badge { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; }
        .status-pending { background: #fffbeb; color: #d97706; }
        .status-approved { background: #ecfdf5; color: #059669; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="card">
            <h2><i class="fas fa-history"></i> My Leave History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Applied On</th>
                        <th>Type</th>
                        <th>Inclusive Dates</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($history_query)): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['date_applied'])) ?></td>
                        <td style="text-transform: uppercase; font-weight: bold;"><?= $row['leave_type'] ?></td>
                        <td><?= date('M d', strtotime($row['start_date'])) ?> - <?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                        <td><?= ($row['leave_type'] == 'faculty') ? ($row['days_requested'] * 8)." hrs" : $row['days_requested']." day(s)" ?></td>
                        <td>
                            <span class="status-badge status-<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span>
                        </td>
                        <td>
                            <?php if($row['status'] == 'pending'): ?>
                                <button onclick="confirmCancel(<?= $row['id'] ?>)" style="color:#ef4444; background:none; border:none; cursor:pointer; font-weight:bold;">
                                    <i class="fas fa-trash"></i> Cancel
                                </button>
                            <?php else: ?>
                                <span style="color: #cbd5e1;">--</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function confirmCancel(id) {
        Swal.fire({
            title: 'Sigurado ka ba?',
            text: "Hindi mo na ito mababawi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'leave_history.php?cancel_id=' + id;
            }
        });
    }

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
        Swal.fire('Cancelled!', 'Ang iyong request ay tinanggal na.', 'success');
    <?php endif; ?>
    </script>
</body>
</html>