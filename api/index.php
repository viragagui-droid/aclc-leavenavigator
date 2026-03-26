<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// LOGIN LOGIC
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check using Password Verify (Standard) or Plain Text (for legacy/testing)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // VERCEL FIX: Nilagyan natin ng /api/ sa unahan ang redirects
            if ($user['role'] == 'admin' && $user['username'] == 'admin-2026') {
                echo "<script>window.location.href='/api/admin.php';</script>";
            } else {
                echo "<script>window.location.href='/api/dashboard.php';</script>";
            }
            exit(); 
        } else {
            $error_msg = "Maling Password! Pakisuyong ulitin.";
        }
    } else {
        $error_msg = "ID Number ($username) ay hindi nahanap sa system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ACLC Navigator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { 
            --primary: #004a99; 
            --accent: #facc15; 
            --bg-gradient: linear-gradient(135deg, #004a99 0%, #002d5f 100%);
            --white: #ffffff;
        }

        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: var(--bg-gradient);
            height: 100vh; 
            margin: 0; 
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .circle {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            z-index: -1;
        }
        .circle-1 { width: 300px; height: 300px; top: -100px; right: -50px; }
        .circle-2 { width: 200px; height: 200px; bottom: -50px; left: -50px; }

        .login-card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 50px 40px; 
            border-radius: 24px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            width: 100%; 
            max-width: 380px; 
            text-align: center;
            transition: all 0.4s ease;
        }

        .logo-section { margin-bottom: 35px; }
        .logo-text { font-size: 28px; font-weight: 800; color: var(--primary); letter-spacing: -1px; }
        .logo-text span { color: #d97706; }
        .sub-text { color: #64748b; font-size: 14px; margin-top: 5px; font-weight: 500; }

        .input-group { margin-bottom: 22px; text-align: left; }
        .input-group label { font-size: 13px; font-weight: 600; color: #1e293b; display: block; margin-bottom: 8px; }
        
        .input-wrapper { position: relative; }
        .input-wrapper i { 
            position: absolute; 
            left: 16px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #94a3b8; 
        }

        .input-wrapper input { 
            width: 100%; 
            padding: 14px 14px 14px 45px; 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            box-sizing: border-box; 
            outline: none; 
            font-size: 15px;
            background: #f8fafc;
        }

        .input-wrapper input:focus { 
            border-color: var(--primary); 
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 74, 153, 0.1);
        }

        .btn-login { 
            background: var(--primary); 
            color: white; 
            border: none; 
            width: 100%; 
            padding: 16px; 
            border-radius: 12px; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 16px; 
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-login:hover { 
            background: #003366; 
            transform: scale(1.02);
        }

        .error-banner { 
            background: #fef2f2; 
            color: #dc2626; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 25px; 
            font-size: 13px; 
            border: 1px solid #fee2e2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; line-height: 1.5; }
    </style>
</head>
<body>

<div class="circle circle-1"></div>
<div class="circle circle-2"></div>

<div class="login-card">
    <div class="logo-section">
        <div class="logo-text">ACLC <span>NAVIGATOR</span></div>
        <div class="sub-text">Leave Management System</div>
    </div>

    <?php if(isset($error_msg)): ?>
        <div class="error-banner">
            <i class="fas fa-circle-exclamation"></i> 
            <span><?php echo $error_msg; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label>ACLC ID Number</label>
            <div class="input-wrapper">
                <i class="fas fa-user-tag"></i>
                <input type="text" name="username" placeholder="e.g. faculty-101" required autocomplete="off">
            </div>
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class="fas fa-shield-halved"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn-login">
            Secure Sign In <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 14px;"></i>
        </button>
    </form>

    <div class="footer">
        © 2026 ACLC College of Manila<br>
        <span style="font-weight: 600;">Student Services Division</span>
    </div>
</div>

</body>
</html>
