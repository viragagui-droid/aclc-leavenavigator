<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// --- STEP 1: DATABASE AUTO-REFRESH LOGIC (Stay as is) ---
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

// --- STEP 4: SIMPLIFIED CALCULATION ---
$is_eligible = ($user['days_employed'] >= 365 || $user['role'] == 'faculty');

$rem_v = $user['vacation_leave']; 
$rem_s = $user['sick_leave'];
$rem_f = $user['faculty_hours'];

// --- CALENDAR EVENTS (Stay as is) ---
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
    <title>Dashboard - ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .main { margin-left: 260px; margin-top: 70px; width: calc(100% - 260px); padding: 30px; box-sizing: border-box; }
        .credit-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .credit-box { flex: 1; padding: 25px; border-radius: 12px; color: white; text-align: center; position: relative; }
        .role-badge { position: absolute; top: 12px; right: 12px; font-size: 10px; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px; text-transform: uppercase; font-weight: bold; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
        /* Style para sa bagong cards mo para mag-match sa system */
        .info-card { flex: 1; background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 4px solid #004a99; }
        .info-card h3 { margin: 10px 0; color: #1e293b; font-size: 24px; }
        .info-card p { color: #64748b; margin: 0; font-size: 14px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; include 'navbar.php'; ?>

    <div class="main">
        

        <div class="credit-container">
    <div class="info-card">
        <?php if($user['days_employed'] < 365): ?>
            <h3 style="color: #64748b;">LOCKED</h3>
            <p>Unlocks after 1 year</p>
        <?php else: ?>
            <h3><?= number_format($user['vacation_leave'], 2) ?> Days</h3>
            <p>Available Vacation Leave</p>
        <?php endif; ?>
    </div>

    <div class="info-card">
        <?php if($user['days_employed'] < 365): ?>
            <h3 style="color: #64748b;">LOCKED</h3>
            <p>Unlocks after 1 year</p>
        <?php else: ?>
            <h3><?= number_format($user['sick_leave'], 2) ?> Days</h3>
            <p>Available Sick Leave</p>
        <?php endif; ?>
    </div>
</div>

        <div class="card">
            <h3><i class="fas fa-calendar-alt"></i> My Schedule</h3>
            <div id='calendar'></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            events: <?= json_encode($events); ?>,
            height: 600
        });
        calendar.render();
    });
    </script>
</body>
</html>