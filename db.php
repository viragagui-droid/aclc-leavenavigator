<?php
$host = "mysql-314fa574-viragagui-af01.a.aivencloud.com";
$user = "avnadmin";
$pass = "AVNS_W5Nle2VJ42Qy1-Hrc5V"; // I-click ang 'Reveal Password' sa Aiven
$db   = "defaultdb";
$port = "14398";

// Importante sa Aiven: Kailangan ng SSL connection
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
