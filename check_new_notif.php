<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { exit(); }
$uid = $_SESSION['user_id'];

// I-check kung may is_read = 0 (ibig sabihin bago)
$res = $conn->query("SELECT id FROM notifications WHERE user_id = $uid AND is_read = 0 LIMIT 1");

if ($res->num_rows > 0) {
    echo "new";
} else {
    echo "none";
}
?>