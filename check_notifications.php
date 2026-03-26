<?php
session_start();
include 'db.php';

// Mag-check kung may 'pending' request na 'is_seen = 0'
$query = "SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending' AND is_seen = 0";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

echo json_encode(['unseen_count' => (int)$data['total']]);
?>