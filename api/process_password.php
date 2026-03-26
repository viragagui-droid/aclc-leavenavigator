<?php
session_start();
include 'db.php';

if (isset($_POST['btn_update_pass'])) {
    $uid = $_SESSION['user_id'];
    $current_input = $_POST['current_pass']; // Ang tinype ng user
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // 1. Kunin ang NAKA-HASH na password mula sa database
    $query = mysqli_query($conn, "SELECT password FROM users WHERE id = '$uid'");
    $row = mysqli_fetch_assoc($query);
    $hashed_password_db = $row['password'];

    // 2. GAMITIN ANG password_verify() para i-check kung tama ang current password
    if (password_verify($current_input, $hashed_password_db)) {
        
        if ($new_pass === $confirm_pass) {
            // 3. I-hash ang bagong password bago i-save
            $new_hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $update = mysqli_query($conn, "UPDATE users SET password = '$new_hashed_pass' WHERE id = '$uid'");
            
            if ($update) {
                echo "<script>alert('Password updated successfully!'); window.location.href='dashboard.php';</script>";
            }
        } else {
            echo "<script>alert('New passwords do not match!'); window.history.back();</script>";
        }

    } else {
        // Ito ang lumalabas sa screenshot mo
        echo "<script>alert('Error: Current password is incorrect.'); window.history.back();</script>";
    }
}
?>