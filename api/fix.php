<?php
// 1. Koneksyon (Siguraduhin na tama ang DB name dito)
$conn = new mysqli("localhost", "root", "", "leave_navigator_db");

if ($conn->connect_error) { die("Koneksyon Bigo: " . $conn->connect_error); }

// 2. Linisin at ayusin ang Table Structure
$conn->query("ALTER TABLE users MODIFY password VARCHAR(255)");

// 3. I-delete ang lumang admin para walang conflict
$conn->query("DELETE FROM users WHERE username = 'admin-2026'");

// 4. Mag-insert ng SARIWANG Admin Account
$raw_password = 'admin123';
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, username, password, role, type, date_hired) 
        VALUES ('Juan Admin', 'admin-2026', '$hashed_password', 'admin', 'regular', NOW())";

if ($conn->query($sql)) {
    echo "<h3>SUCCESS!</h3>";
    echo "Ang Admin account ay na-reset na.<br>";
    echo "<b>Username:</b> admin-2026<br>";
    echo "<b>Password:</b> admin123<br>";
    echo "<br><a href='index.php'>Bumalik sa Login at subukan ulit.</a>";
} else {
    echo "Error: " . $conn->error;
}
?>