<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php'; 

$display_name = "User";
$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // 1. Hatakin ang Pangalan
    $user_query = mysqli_query($conn, "SELECT name FROM users WHERE id = '$uid'");
    if($user_row = mysqli_fetch_assoc($user_query)) { $display_name = $user_row['name']; }

    // 2. Bilangin ang Unread Notifications (0 = Unread)
    $notif_count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = '$uid' AND is_read = 0");
    $notif_data = mysqli_fetch_assoc($notif_count_query);
    $unread_count = $notif_data['total'] ?? 0;
}
?>

<audio id="notifSound" src="notify.mp3" preload="auto"></audio>

<div class="top-navbar" style="position: fixed; top: 0; left: 260px; right: 0; height: 65px; background: white; display: flex; justify-content: space-between; align-items: center; padding: 0 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 999;">
    <div style="font-weight: bold; color: #004a99; font-size: 18px;">
        <i class="fas fa-desktop"></i> ACLC Leave Navigator
    </div>
    
    <div style="display: flex; align-items: center; gap: 20px;">
        
        <div style="position: relative; cursor: pointer;" onclick="toggleNotif()">
            <i class="fas fa-bell" style="font-size: 20px; color: #64748b;"></i>
            <?php if($unread_count > 0): ?>
                <span id="notif-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; border: 2px solid white;">
                    <?= $unread_count ?>
                </span>
            <?php endif; ?>
            
            <div id="notifBox" style="display:none; position: absolute; right: 0; top: 35px; width: 320px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden; border: 1px solid #eee; z-index: 10000;">
                <div style="padding: 15px; background: #f8f9fa; border-bottom: 1px solid #eee; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px;">Recent Notifications</span>
                    <i class="fas fa-check-double" style="color: #004a99; font-size: 12px;" title="Mark all as read"></i>
                </div>
                <div id="notifList" style="max-height: 350px; overflow-y: auto;">
                    <p style="padding: 20px; text-align: center; color: #999; font-size: 12px;">Loading...</p>
                </div>
            </div>
        </div>

        <span style="font-weight: 600; color: #333;">Hi, <?= htmlspecialchars($display_name) ?>!</span>
        
        <button onclick="document.getElementById('passMod').style.display='block'" style="padding: 8px 15px; cursor: pointer; border: 1px solid #ddd; background: #f8f9fa; border-radius: 5px; font-weight: 600;">
            <i class="fas fa-key"></i> Password
        </button>
        
        <a href="logout.php" style="padding: 8px 15px; color: #d93025; text-decoration: none; font-weight: bold; background: #fee2e2; border-radius: 5px; font-size: 13px;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div id="passMod" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
    <div style="background:white; width:380px; margin:100px auto; border-radius:12px; overflow:hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <div style="background:#004a99; color:white; padding:15px 20px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px;">Update Security</h3>
            <span onclick="document.getElementById('passMod').style.display='none'" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <form action="process_password.php" method="POST" style="padding:20px;">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:bold; color:#667; margin-bottom:5px;">CURRENT PASSWORD</label>
                <input type="password" name="current_pass" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;" required>
            </div>
            <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-size:12px; font-weight:bold; color:#667; margin-bottom:5px;">NEW PASSWORD</label>
                <input type="password" name="new_pass" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;" required minlength="6">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12px; font-weight:bold; color:#667; margin-bottom:5px;">CONFIRM NEW PASSWORD</label>
                <input type="password" name="confirm_pass" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;" required>
            </div>
            <div style="text-align:right; display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('passMod').style.display='none'" style="padding:10px 20px; border:none; background:#e2e8f0; border-radius:6px; cursor:pointer; font-weight:600;">Cancel</button>
                <button type="submit" name="btn_update_pass" style="padding:10px 20px; border:none; background:#004a99; color:white; border-radius:6px; cursor:pointer; font-weight:bold;">Update Now</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleNotif() {
    var box = document.getElementById('notifBox');
    if (box.style.display === "none" || box.style.display === "") {
        box.style.display = "block";
        loadNotifications(); // Tatawagin ang fetch_notifications.php
    } else {
        box.style.display = "none";
    }
}

// Fetch list ng notifications via AJAX
function loadNotifications() {
    $.ajax({
        url: 'fetch_notifications.php',
        type: 'GET',
        success: function(data) {
            $('#notifList').html(data);
            $('#notif-badge').hide(); // Itago ang red dot kapag na-click na
        }
    });
}

// Real-time Check (Bawat 10 seconds)
setInterval(function() {
    $.ajax({
        url: 'check_new_notif.php',
        success: function(res) {
            if(res.trim() === "new") {
                // Tumunog dapat! Siguraduhin na may notify.mp3 sa folder.
                document.getElementById('notifSound').play().catch(e => console.log('Sound error:', e));
                
                // I-update ang badge count nang hindi nagre-refresh ng buong page
                location.reload(); 
            }
        }
    });
}, 10000);
</script>