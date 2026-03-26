<?php
$host = "mysql-314fa574-viragagui-af01.a.aivencloud.com";
$user = "avnadmin";
$pass = "AVNS_WSNle2VJ42Qy1-Hrc5V"; 
$db   = "defaultdb";
$port = "14398";

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Ito ang magko-connect sa Vercel papunta sa Aiven
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
