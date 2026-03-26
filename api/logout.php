<?php
// logout.php
session_start();

// 1. Burahin ang lahat ng session data
$_SESSION = array();

// 2. Burahin ang session cookie sa browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Sirain ang session sa server side
session_destroy();

// 4. CRITICAL: Pigilan ang Browser Cache
// Nilalagay ito para hindi ma-"Back" ng user ang dashboard
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// 5. I-redirect sa login page
header("Location: index.php");
exit();
?>