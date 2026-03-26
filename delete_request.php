<?php
session_start();
include 'db.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Kunin ang detalye ng leave at ang role ng user
    $query = "SELECT lr.*, u.role FROM leave_requests lr 
              JOIN users u ON lr.user_id = u.id 
              WHERE lr.id = '$id'";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);

    if ($data) {
        $u_id = $data['user_id'];
        $days = $data['days_requested'];
        $type = $data['leave_type'];
        $user_role = $data['role'];
        $status = $data['status'];

        // 2. REFUND LOGIC: Gagawin lang ito kung 'approved' na ang status
        if ($status == 'approved') {
            if ($user_role == 'faculty') {
                // Refund para sa Faculty (Hours: Days * 8)
                $hours = $days * 8;
                mysqli_query($conn, "UPDATE users SET faculty_hours = faculty_hours + $hours WHERE id = '$u_id'");
            } else {
                // Refund para sa Staff/Maintenance (Days)
                $column = ($type == 'sick') ? 'sick_leave' : 'vacation_leave';
                mysqli_query($conn, "UPDATE users SET $column = $column + $days WHERE id = '$u_id'");
            }
        }

        // 3. Ngayon, burahin na ang record sa SQL
        mysqli_query($conn, "DELETE FROM leave_requests WHERE id = '$id'");
        
        header("Location: full_history.php?msg=deleted");
        exit();
    }
}
?>