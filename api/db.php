<?php
$conn = mysqli_connect("localhost", "root", "", "leave_navigator_db"); // Palitan ang db name kung iba ang gamit mo

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>