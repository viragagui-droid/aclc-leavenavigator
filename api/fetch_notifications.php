<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { exit(); }
$uid = $_SESSION['user_id'];

// 1. Mark as read muna para mawala ang red badge sa susunod na refresh
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");

// 2. Kunin ang listahan
$res = $conn->query("SELECT * FROM notifications WHERE user_id = $uid ORDER BY id DESC LIMIT 5");

if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo "<div style='padding: 12px 15px; border-bottom: 1px solid #eee; background: white; font-size: 13px;'>
                <i class='fas fa-info-circle' style='color: #004a99; margin-right: 8px;'></i>
                " . htmlspecialchars($row['message']) . "
              </div>";
    }
} else {
    echo "<p style='padding: 20px; text-align: center; color: #999; font-size: 12px;'>Walang bagong balita.</p>";
}
?>