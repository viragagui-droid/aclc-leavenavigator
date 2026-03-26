<?php
session_start(); // Mahalaga ito para sa $_SESSION['role']
include 'db.php';

// 1. SECURITY CHECK: Admin lang ang pwedeng mag-approve/reject
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// 2. MARK AS SEEN: Kapag binuksan ang page na ito, ituring na "nabasa" na ni admin ang mga pending.
// Siguraduhin na na-run mo na ang SQL command: ALTER TABLE leave_requests ADD COLUMN is_seen INT DEFAULT 0;
mysqli_query($conn, "UPDATE leave_requests SET is_seen = 1 WHERE status = 'pending' AND is_seen = 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. APPROVAL / REJECTION LOGIC
if (isset($_POST['status'])) {
    $req_id = intval($_POST['request_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']); 
    
    // Hanapin lang ang request kung 'pending' pa para iwas sa double-deduction ng credits
    $req_query = "SELECT lr.*, u.name FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.id = $req_id AND lr.status = 'pending'";
    $req_run = mysqli_query($conn, $req_query);
    $req_data = mysqli_fetch_assoc($req_run);
    
    if ($req_data) {
        $uid = $req_data['user_id'];
        $days = $req_data['days_requested'];
        $type = $req_data['leave_type'];

        if ($status == 'approved') {
            if ($type == 'faculty') {
                $hours = $days * 8;
                $conn->query("UPDATE users SET faculty_hours = faculty_hours - $hours WHERE id = $uid");
                $duration_text = "$hours Hours";
            } else {
                $col = ($type == 'sick') ? 'sick_leave' : 'vacation_leave';
                $conn->query("UPDATE users SET $col = $col - $days WHERE id = $uid");
                $duration_text = "$days Day(s)";
            }
            $notif_msg = "Your $type leave request ($duration_text) has been APPROVED.";
        } else {
            $duration_text = ($type == 'faculty') ? ($days * 8) . " Hours" : "$days Day(s)";
            $notif_msg = "Your $type leave request ($duration_text) was REJECTED.";
        }

        // I-update ang status at magpadala ng notification sa staff
        $conn->query("UPDATE leave_requests SET status = '$status' WHERE id = $req_id");
        $conn->query("INSERT INTO notifications (user_id, message, is_read) VALUES ($uid, '$notif_msg', 0)");

        header("Location: leave_requests.php?msg=updated");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Requests | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --blue: #004a99; --yellow: #eab308; --light: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--blue); color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; }
        .sidebar h2 { color: var(--yellow); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .nav-link { display: block; color: white; text-decoration: none; padding: 12px; margin: 5px 0; border-radius: 5px; transition: 0.3s; }
        .nav-link.active { background: rgba(255,255,255,0.2); border-left: 4px solid var(--yellow); font-weight: bold; }
        .wrapper { margin-left: 260px; width: 100%; }
        .top-nav { background: white; height: 60px; display: flex; justify-content: flex-end; align-items: center; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .main-content { padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: #64748b; font-size: 12px; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .btn { padding: 8px 15px; border: none; border-radius: 6px; color: white; cursor: pointer; font-weight: bold; }
        .btn-approve { background: #22c55e; }
        .btn-reject { background: #ef4444; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>ACLC Navigator</h2>
    <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_staff.php" class="nav-link"><i class="fas fa-users"></i> Manage Staff</a>
    <a href="leave_requests.php" class="nav-link active"><i class="fas fa-file-signature"></i> Leave Requests</a>
    <a href="full_history.php" class="nav-link"><i class="fas fa-history"></i> Master History</a>
</div>

<div class="wrapper">
    <div class="top-nav">
        <span style="margin-right: 20px;">Admin: <b><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></b></span>
        <a href="logout.php" style="color:red; text-decoration:none; font-weight:bold;">Logout</a>
    </div>

    <div class="main-content">
        <div class="card">
            <h3><i class="fas fa-hourglass-half"></i> Pending Approvals</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th>Dates</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $reqs = $conn->query("SELECT r.*, u.name, u.role FROM leave_requests r JOIN users u ON r.user_id = u.id WHERE r.status = 'pending' ORDER BY r.id DESC");
                    if ($reqs && $reqs->num_rows > 0):
                        while($r = $reqs->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><b><?= htmlspecialchars($r['name']) ?></b><br><small><?= strtoupper($r['role']) ?></small></td>
                        <td><?= strtoupper($r['leave_type']) ?></td>
                        <td><?= ($r['leave_type'] == 'faculty') ? ($r['days_requested'] * 8) . " Hours" : $r['days_requested'] . " Day(s)" ?></td>
                        <td><?= date('M d', strtotime($r['start_date'])) ?> - <?= date('M d', strtotime($r['end_date'])) ?></td>
                        <td style="text-align: center;">
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <button name="status" value="approved" class="btn btn-approve" onclick="return confirm('Approve this request?')">Approve</button>
                                <button name="status" value="rejected" class="btn btn-reject" onclick="return confirm('Reject this request?')">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center;">No pending requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Pigilan ang resubmission ng form kapag nirefresh ang page
if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
}

// SweetAlert Notification para sa message galing sa URL
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('msg')) {
    Swal.fire({
        icon: 'success',
        title: 'Updated!',
        text: 'Leave request status has been successfully updated.',
        timer: 2000,
        showConfirmButton: false
    });
}
</script>
</body>
</html>