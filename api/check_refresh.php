<?php
// check_refresh.php

function refreshUserCredits($conn, $user_id) {
    // 1. Kunin ang data ng user
    $query = "SELECT role, last_refresh, date_hired FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    $today = new DateTime();
    $last_refresh = new DateTime($user['last_refresh']);
    $current_year = $today->format('Y');
    $last_refresh_year = $last_refresh->format('Y');

    // --- LOGIC 1: PARA SA MAINTENANCE / ADMIN (Every New Year - Jan 1) ---
    if ($user['role'] == 'maintenance' || $user['role'] == 'admin') {
        // Kung ang huling refresh ay nung nakaraang taon pa, i-reset sa 15 days VL/SL
        if ($current_year > $last_refresh_year) {
            $update = "UPDATE users SET 
                        vacation_leave = 15, 
                        sick_leave = 15, 
                        last_refresh = NOW() 
                       WHERE id = $user_id";
            mysqli_query($conn, $update);
        }
    }

    // --- LOGIC 2: PARA SA FACULTY (Every Semester - 6 Months Interval) ---
    // Note: Sa school setup, karaniwang every 6 months ang palit ng sem (June at November)
    if ($user['role'] == 'faculty') {
        $interval = $today->diff($last_refresh);
        $months_passed = ($interval->y * 12) + $interval->m;

        // Kung lumipas na ang 5-6 buwan simula nung huling refresh
        if ($months_passed >= 5) { 
            $update = "UPDATE users SET 
                        faculty_hours = 60, 
                        last_refresh = NOW() 
                       WHERE id = $user_id";
            mysqli_query($conn, $update);
        }
    }
}
?>