<?php
session_start();
include 'db.php';

// 1. PROTECTION: Admin/Handler only
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php"); 
    exit();
}

$msg = "";

// 2. LOGIC: REGISTER NEW USER
if (isset($_POST['register'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $type = $_POST['type'];
    $hired = $_POST['date_hired'];
    
    $fh = ($role == 'faculty') ? 60 : 0;
    $vl = ($role == 'faculty') ? 0 : 10.00; 
    $sl = ($role == 'faculty') ? 0 : 7.50;  

    $sql = "INSERT INTO users (name, username, password, role, type, date_hired, faculty_hours, vacation_leave, sick_leave, last_refresh) 
            VALUES ('$name', '$username', '$password', '$role', '$type', '$hired', '$fh', '$vl', '$sl', NOW())";
    
    if ($conn->query($sql)) { $msg = "registered"; }
}

// 3. LOGIC: UPDATE ROLE
if (isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    $conn->query("UPDATE users SET role = '$new_role' WHERE id = $id");
    $msg = "updated";
}

// 4. LOGIC: DELETE USER
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id = $id AND role != 'admin'");
    header("Location: manage_staff.php?msg=deleted");
    exit();
}

// 5. SEARCH LOGIC
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT *, DATEDIFF(NOW(), date_hired) as days_employed FROM users 
          WHERE (name LIKE '%$search%' OR username LIKE '%$search%') 
          AND role != 'admin' ORDER BY name ASC";
$res = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --blue: #004a99; --yellow: #eab308; --dark: #1e293b; --light: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--blue); color: white; height: 100vh; position: fixed; left: 0; top: 0; padding: 20px; box-sizing: border-box; z-index: 1000; }
        .sidebar h2 { color: var(--yellow); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 20px; font-size: 20px; }
        .nav-link { display: block; color: white; text-decoration: none; padding: 12px; margin: 8px 0; border-radius: 5px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); }
        .nav-link.active { background: rgba(255,255,255,0.2); border-left: 4px solid var(--yellow); font-weight: bold; }

        /* Content Layout */
        .wrapper { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; }
        .top-nav { background: white; height: 65px; display: flex; justify-content: flex-end; align-items: center; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .logout-btn { background: #ef4444; color: white; text-decoration: none; padding: 8px 18px; border-radius: 5px; font-weight: bold; font-size: 13px; }

        .main-content { padding: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* UI Components */
        .reg-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        input, select { padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        .btn-reg { background: var(--blue); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; background: #f1f5f9; color: #64748b; font-size: 11px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-faculty { background: #dcfce7; color: #15803d; }
        .badge-staff { background: #fef3c7; color: #9a3412; }
    </style>
</head>
<body>

<audio id="notifSound" src="notify.mp3" preload="auto"></audio>

<div class="sidebar">
    <h2>ACLC NAV</h2>
    <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_staff.php" class="nav-link active"><i class="fas fa-users"></i> Manage Staff</a>
    <a href="leave_requests.php" class="nav-link"><i class="fas fa-file-signature"></i> Leave Requests</a>
    <a href="full_history.php" class="nav-link"><i class="fas fa-history"></i> Master History</a>
</div>

<div class="wrapper">
    <div class="top-nav">
        <span style="margin-right: 20px; color: #64748b;">Admin: <b><?= $_SESSION['username'] ?></b></span>
        <a href="logout.php" class="logout-btn"><i class="fas fa-power-off"></i> LOGOUT</a>
    </div>

    <div class="main-content">
        <div class="card">
            <h3 style="margin-top:0;"><i class="fas fa-plus-circle"></i> Add New Employee</h3>
            <form method="POST" class="reg-form">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="text" name="username" placeholder="Employee ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="maintenance">Maintenance</option>
                    <option value="faculty">Faculty</option>
                    <option value="admin_staff">Office Admin</option>
                </select>
                <select name="type">
                    <option value="regular">Regular</option>
                    <option value="irregular">Irregular</option>
                </select>
                <input type="date" name="date_hired" required>
                <button type="submit" name="register" class="btn-reg">Create Account</button>
            </form>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0;">Employee Directory</h3>
                <form method="GET" style="display:flex; gap:5px;">
                    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" style="background:var(--blue); color:white; border:none; padding:10px; border-radius:8px;"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Staff Info</th>
                        <th>Position</th>
                        <th>Credits</th>
                        <th>Change Role</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $res->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= $u['name'] ?></b><br><small>ID: <?= $u['username'] ?></small></td>
                        <td><span class="badge <?= ($u['role']=='faculty')?'badge-faculty':'badge-staff' ?>"><?= $u['role'] ?></span></td>
                        <td>
                            <?php if($u['role'] == 'faculty'): ?>
                                <small>Hours: <b><?= $u['faculty_hours'] ?></b></small>
                            <?php else: ?>
                                <small>VL: <b><?= $u['vacation_leave'] ?></b> | SL: <b><?= $u['sick_leave'] ?></b></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" style="font-size:11px; padding:5px;">
                                    <option value="maintenance" <?= $u['role']=='maintenance'?'selected':'' ?>>Maintenance</option>
                                    <option value="faculty" <?= $u['role']=='faculty'?'selected':'' ?>>Faculty</option>
                                    <option value="admin_staff" <?= $u['role']=='admin_staff'?'selected':'' ?>>Admin Staff</option>
                                </select>
                                <button type="submit" name="update_user" style="color:var(--blue); border:none; background:none; cursor:pointer;"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        <td>
                            <a href="javascript:confirmDelete(<?= $u['id'] ?>)" style="color:red;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Logic para sa Delete Confirmation
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Hindi na mababawi ang data!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => { 
        if (result.isConfirmed) { 
            window.location.href = 'manage_staff.php?delete=' + id; 
        } 
    })
}

// Logic para sa Registration Success
<?php if($msg == "registered"): ?>
    Swal.fire('Success!', 'New employee has been added.', 'success');
<?php elseif($msg == "updated"): ?>
    Swal.fire('Updated!', 'User role has been changed.', 'info');
<?php endif; ?>

// --- NOTIFICATION POP-UP LOGIC ---
let lastCount = 0;

// Step 1: Initialize the count
$.get('check_notifications.php', function(data) {
    lastCount = data.pending_count;
}, 'json');

// Step 2: Checker function
function checkNewRequests() {
    $.ajax({
        url: 'check_notifications.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.pending_count > lastCount) {
                // Play Sound
                let sound = document.getElementById('notifSound');
                if(sound) sound.play().catch(e => console.log("Sound played after interaction"));
                
                // Show SweetAlert
                Swal.fire({
                    title: 'New Leave Request!',
                    text: 'A staff member has submitted a new leave application.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#004a99',
                    confirmButtonText: 'Review Now',
                    toast: true,
                    position: 'top-end',
                    timer: 10000,
                    timerProgressBar: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'leave_requests.php';
                    }
                });
                lastCount = data.pending_count;
            } else {
                lastCount = data.pending_count;
            }
        }
    });
}

// Step 3: Run every 5 seconds
setInterval(checkNewRequests, 5000);
</script>

</body>
</html>