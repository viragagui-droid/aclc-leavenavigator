<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];

// 1. Kunin ang data ng user at ang tenure (days_employed)
$user_query = mysqli_query($conn, "SELECT *, DATEDIFF(NOW(), date_hired) as days_employed FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

$_SESSION['user_name'] = $user['name'];

$msg = "";
$available_credits = 0; 

if (isset($_POST['apply_leave'])) {
    $type = mysqli_real_escape_string($conn, $_POST['leave_type']);
    $start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end = mysqli_real_escape_string($conn, $_POST['end_date']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $days = intval($_POST['days']);

    // --- CREDIT & TENURE VALIDATION LOGIC ---
    
    // Alamin kung magkano ang available credits base sa type
    if ($type == 'faculty') {
        $available_credits = $user['faculty_hours'] / 8; // Convert hours to days for checking
    } elseif ($type == 'vacation') {
        $available_credits = $user['vacation_leave'];
    } else {
        $available_credits = $user['sick_leave'];
    }

    $today = new DateTime();
    $startDateObj = new DateTime($start);
    $diff = (int)$today->diff($startDateObj)->format("%r%a");

    // 1. DUPLICATE CHECK
    $check_duplicate = mysqli_query($conn, "SELECT id FROM leave_requests 
        WHERE user_id = $user_id AND status != 'rejected' 
        AND (('$start' BETWEEN start_date AND end_date) OR ('$end' BETWEEN start_date AND end_date))");

    if (mysqli_num_rows($check_duplicate) > 0) {
        $msg = "duplicate_error";
    } 
    // 2. TENURE CHECK (Para sa Staff/Admin lang, Faculty is exempted)
    elseif ($user['role'] != 'faculty' && $user['days_employed'] < 365) {
        $msg = "tenure_error";
    }
    // 3. INSUFFICIENT CREDITS CHECK
    elseif ($days > $available_credits) {
        $msg = "insufficient_credits";
    }
    // 4. ADVANCE NOTICE (3 days rule for Vacation)
    elseif ($type == 'vacation' && $diff < 3) {
        $msg = "vacation_error";
    } 
    else {
        // Safe to INSERT
        $sql = "INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, days_requested, reason, status, date_applied) 
                VALUES ($user_id, '$type', '$start', '$end', $days, '$reason', 'pending', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $msg = "success";
        } else {
            die("SQL Error: " . mysqli_error($conn));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Request | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --blue: #004a99; --light: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main { margin-left: 260px; width: calc(100% - 260px); padding: 40px; box-sizing: border-box; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 700px; margin: auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: 0.3s; }
        input:focus { border-color: var(--blue); outline: none; }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 16px; width: 100%; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn-submit:hover { background: #003366; }
        .credit-info { background: #eff6ff; padding: 15px; border-radius: 10px; margin-bottom: 25px; border-left: 5px solid var(--blue); }
        .lock-notice { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; border-left: 5px solid #dc2626; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="card">
        <h2 style="margin:0 0 10px 0;"><i class="fas fa-paper-plane" style="color: var(--blue);"></i> Apply for Leave</h2>
        
        <?php if($user['role'] != 'faculty' && $user['days_employed'] < 365): ?>
            <div class="lock-notice">
                <i class="fas fa-lock"></i> <strong>Filing is currently locked.</strong><br>
                You can start filing leave requests after 1 year of employment.
            </div>
        <?php else: ?>
            <div class="credit-info">
                <small style="font-weight: bold; color: var(--blue);">CURRENT BALANCE:</small><br>
                <?php if($user['role'] == 'faculty'): ?>
                    <strong><?= number_format($user['faculty_hours'], 2) ?> Hours Available</strong>
                <?php else: ?>
                    <strong>Vacation: <?= $user['vacation_leave'] ?>d</strong> | <strong>Sick: <?= $user['sick_leave'] ?>d</strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="leaveForm">
            <div class="form-group">
                <label>Leave Type</label>
                <select name="leave_type" id="leave_type" required onchange="updateMinDate()">
                    <option value="">-- Select Type --</option>
                    <?php if($user['role'] == 'faculty'): ?>
                        <option value="faculty">Faculty Leave</option>
                    <?php elseif($user['days_employed'] >= 365): ?>
                        <option value="vacation">Vacation Leave</option>
                        <option value="sick">Sick Leave</option>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" id="start_date" required onchange="calculateDays()">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" id="end_date" required onchange="calculateDays()">
                </div>
            </div>

            <div class="form-group">
                <label>Duration</label>
                <input type="text" id="display_days" readonly style="background: #f8fafc; font-weight: bold; color: var(--blue);" placeholder="0 Day(s)">
                <input type="hidden" name="days" id="days_count" value="0">
            </div>

            <div class="form-group">
                <label>Reason</label>
                <textarea name="reason" rows="3" required placeholder="Reason for leave..."></textarea>
            </div>

            <button type="submit" name="apply_leave" class="btn-submit" 
                <?php if($user['role'] != 'faculty' && $user['days_employed'] < 365) echo 'disabled style="background: #cbd5e1; cursor: not-allowed;"'; ?>>
                Submit Application
            </button>
        </form>
    </div>
</div>

<script>
function updateMinDate() {
    const type = document.getElementById('leave_type').value;
    const startInput = document.getElementById('start_date');
    let minDate = new Date();
    if (type === 'vacation') { minDate.setDate(minDate.getDate() + 3); }
    startInput.min = minDate.toISOString().split('T')[0];
}

function calculateDays() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    const display = document.getElementById('display_days');
    const hiddenCount = document.getElementById('days_count');
    const type = document.getElementById('leave_type').value;

    if(start && end) {
        const d1 = new Date(start);
        const d2 = new Date(end);
        if(d2 < d1) {
            Swal.fire('Error', 'End date cannot be earlier than start date.', 'error');
            document.getElementById('end_date').value = "";
            return;
        }
        const diffTime = Math.abs(d2 - d1);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        hiddenCount.value = diffDays;
        display.value = (type === 'faculty') ? (diffDays * 8) + " Hours (" + diffDays + " days)" : diffDays + " Day(s)";
    }
}

// SweetAlert Feedbacks
<?php if($msg == "success"): ?>
    Swal.fire('Submitted!', 'Your request is pending.', 'success').then(()=> location.href='leave_history.php');
<?php elseif($msg == "insufficient_credits"): ?>
    Swal.fire('Insufficient Balance', 'You cannot file more than your available credits.', 'error');
<?php elseif($msg == "tenure_error"): ?>
    Swal.fire('Locked', 'Leave filing is available after 1 year of service.', 'warning');
<?php elseif($msg == "duplicate_error"): ?>
    Swal.fire('Overlap', 'You have an existing request for these dates.', 'error');
<?php elseif($msg == "vacation_error"): ?>
    Swal.fire('Policy', 'Vacation leave requires 3 days advance notice.', 'info');
<?php endif; ?>
</script>

</body>
</html>