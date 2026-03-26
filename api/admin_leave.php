<?php
session_start();
include 'db.php';

// Protection: Admin lang dapat ang nakaka-access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'student') { 
    header("Location: index.php"); 
    exit(); 
}

// Logic para sa Pag-Approve o Pag-Reject
if (isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    
    $update_sql = "UPDATE leave_requests SET status = '$new_status' WHERE id = $request_id";
    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('Request updated successfully!'); window.location.href='admin_leave.php';</script>";
    }
}

// Kunin ang lahat ng requests na may kasamang pangalan ng user
$query = "SELECT lr.*, u.name, u.role as user_role 
          FROM leave_requests lr 
          JOIN users u ON lr.user_id = u.id 
          ORDER BY lr.date_filed DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Leave Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .main { margin-left: 260px; margin-top: 70px; width: calc(100% - 260px); padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #004a99; color: white; padding: 12px; text-align: left; font-size: 13px; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; }
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 11px; font-weight: bold; }
        .btn-approve { background: #2e7d32; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
        .btn-reject { background: #c62828; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }
        .status-pending { color: #eab308; font-weight: bold; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<?php include 'navbar.php'; ?>

<div class="main">
    <div class="card">
        <h2><i class="fas fa-tasks" style="color: #004a99;"></i> Employee Leave Requests</h2>
        <p style="color: #666;">Dito mo maaring i-manage ang lahat ng leave applications ng Faculty at Staff.</p>

        <table>
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>
                        <b><?= $row['name'] ?></b><br>
                        <small style="color: #888;"><?= strtoupper($row['user_role']) ?></small>
                    </td>
                    <td><?= ucfirst($row['leave_type']) ?></td>
                    <td>
                        <?= $row['start_date'] ?> to <?= $row['end_date'] ?><br>
                        <small>(<?= $row['days_requested'] ?> Days)</small>
                    </td>
                    <td style="max-width: 200px; font-style: italic;"><?= htmlspecialchars($row['reason']) ?></td>
                    <td>
                        <span class="status-<?= $row['status'] ?>"><?= strtoupper($row['status']) ?></span>
                    </td>
                    <td>
                        <?php if($row['status'] == 'pending'): ?>
                        <form method="POST" style="display: flex; gap: 5px;">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='approved'" class="btn-approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='rejected'" class="btn-reject">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" name="status" value="">
                        </form>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">Processed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>