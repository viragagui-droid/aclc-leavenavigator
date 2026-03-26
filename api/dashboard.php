<?php
session_start();
include 'db.php';

// VERCEL FIX: Redirect to /api/index.php if not logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: /api/index.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// --- STEP 1: DATABASE AUTO-REFRESH LOGIC ---
mysqli_query($conn, "UPDATE users SET 
    vacation_leave = 10.00, 
    sick_leave = 7.50,
    last_refresh = NOW()
    WHERE id = $user_id 
    AND role IN ('admin', 'maintenance', 'staff') 
    AND DATEDIFF(NOW(), date_hired) >= 365 
    AND (vacation_leave = 0 OR last_refresh IS NULL OR YEAR(last_refresh) < YEAR(NOW()))");

// --- STEP 2: FETCH USER DATA ---
$user_query = mysqli_query($conn, "SELECT *, DATEDIFF(NOW(), date_hired) as days_employed FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// --- STEP 4: ELIGIBILITY ---
$is_eligible = ($user['days_employed'] >= 365 || $user['role'] == 'faculty');

// --- CALENDAR EVENTS ---
$events = [];
$cal_query = mysqli_query($conn, "SELECT * FROM leave_requests WHERE user_id = $user_id AND status != 'rejected'");
while($row = mysqli_fetch_assoc($cal_query)) {
    $color = ($row['status'] == 'approved') ? '#2e7d32' : '#eab308';
    $events[] = [
        'title' => strtoupper($row['leave_type']) . " (" . ucfirst($row['status']) . ")", 
        'start' => $row['start_date'], 
        'end' => date('Y-m-d', strtotime($row['end_date'] . ' +1 day')), 
        'backgroundColor' => $color,
        'borderColor' => $color
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .main { margin-left: 260px; width: calc(100% - 260px); padding: 30px; box-sizing: border-box; transition: 0.3s; }
        
        /* Adjust for Top Navbar height */
        .content-area { margin-top: 70px; }

        .credit-container { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .info-card { 
            flex: 1; 
            min-width: 250px;
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            text-align: center; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border-top: 4px solid #004a99; 
        }
        .info-card h3 { margin: 10px 0; color: #1e293b; font-size: 24px; }
        .info-card p { color: #64748b; margin: 0; font-size: 14px; font-weight: 500; }
        
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        #calendar { background: white; padding: 10px; border-radius: 10px; }

        /* Mobile Responsive Sidebar Fix */
        @media (max-width: 768px) {
            .main { margin-left: 0; width: 100%; }
            .sidebar { display: none; } /* Assuming you have a toggle logic later */
        }
    </style>
</head>
<body>
    <?php 
        // Siguraduhin na ang sidebar.php at navbar.php ay nasa loob din ng /api/ folder
        include 'sidebar.php'; 
        include 'navbar.php'; 
    ?>

    <div class="main">
        <div class="content-area">
            <div class="credit-container">
                <div class="info-card">
                    <?php if($user['days_employed'] < 365 && $user['role'] != 'faculty'): ?>
                        <h3 style="color: #94a3b8;"><i class="fas fa-lock"></i> LOCKED</h3>
                        <p>Vacation Leave (Unlocks after 1yr)</p>
                    <?php else: ?>
                        <h3 style="color: #004a99;"><?= number_format($user['vacation_leave'], 2) ?> Days</h3>
                        <p>Available Vacation Leave</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <?php if($user['days_employed'] < 365 && $user['role'] != 'faculty'): ?>
                        <h3 style="color: #94a3b8;"><i class="fas fa-lock"></i> LOCKED</h3>
                        <p>Sick Leave (Unlocks after 1yr)</p>
                    <?php else: ?>
                        <h3 style="color: #004a99;"><?= number_format($user['sick_leave'], 2) ?> Days</h3>
                        <p>Available Sick Leave</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-calendar-check" style="color: #004a99;"></i> My Leave Schedule</h3>
                <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            events: <?= json_encode($events); ?>,
            height: 'auto',
            aspectRatio: 1.5
        });
        calendar.render();
    });
    </script>
</body>
</html>
