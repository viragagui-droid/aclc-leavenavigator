<?php
// Gamitin ang details mula sa Aiven Dashboard
$host = "mysql-314fa574-viragagui-af01.a.aivencloud.com";
$user = "avnadmin";
$pass = "AVNS_W5Nle2VJ42Qy1-Hrc5V"; 
$db   = "defaultdb";
$port = "14398";

// Importante: Ang Aiven ay nangangailangan ng SSL connection
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Pag-connect gamit ang MySQLi SSL
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
