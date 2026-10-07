<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Register</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/login-regis.css">
</head>

<body>
    <?php
    include('../config/connect.php');
    include('../config/install.php');
    ?>

    <div class="bg-overlay">
        <div class="register-container">

            <a href="../main/index.php" class="close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="33" height="36" viewBox="0 0 33 36" fill="none">
                <path d="M31.2412 34.6912C30.4648 35.4675 29.4118 35.9036 28.3138 35.9036C27.2159 35.9036 26.1629 35.4675 25.3865 34.6912L16.2406 24.2377L7.09461 34.6877C6.71133 35.0773 6.25471 35.3871 5.75109 35.5993C5.24747 35.8116 4.70681 35.922 4.16031 35.9242C3.6138 35.9264 3.07226 35.8204 2.56693 35.6123C2.0616 35.4042 1.60247 35.0981 1.21603 34.7117C0.829587 34.3252 0.523483 33.8661 0.315371 33.3608C0.10726 32.8554 0.00125876 32.3139 0.00348407 31.7674C0.00570939 31.2209 0.116116 30.6802 0.328336 30.1766C0.540556 29.673 0.85039 29.2163 1.23997 28.8331L10.7551 17.9656L1.23652 7.09117C0.846941 6.70789 0.537107 6.25126 0.324887 5.74764C0.112666 5.24402 0.00225964 4.70336 3.43239e-05 4.15686C-0.00219099 3.61035 0.10381 3.06882 0.311922 2.56348C0.520033 2.05815 0.826137 1.59902 1.21258 1.21258C1.59902 0.826137 2.05815 0.520033 2.56348 0.311922C3.06881 0.10381 3.61035 -0.00219099 4.15686 3.43239e-05C4.70336 0.00225964 5.24402 0.112666 5.74764 0.324887C6.25126 0.537107 6.70788 0.846941 7.09116 1.23652L16.2406 11.6935L25.3865 1.23652C25.7698 0.846941 26.2264 0.537107 26.73 0.324887C27.2337 0.112666 27.7743 0.00225964 28.3208 3.43239e-05C28.8673 -0.00219099 29.4089 0.10381 29.9142 0.311922C30.4195 0.520033 30.8787 0.826137 31.2651 1.21258C31.6515 1.59902 31.9576 2.05815 32.1658 2.56348C32.3739 3.06882 32.4799 3.61035 32.4776 4.15686C32.4754 4.70336 32.365 5.24402 32.1528 5.74764C31.9406 6.25126 31.6307 6.70789 31.2412 7.09117L21.7261 17.9656L31.2412 28.8331C31.6261 29.2176 31.9314 29.6742 32.1398 30.1767C32.3481 30.6793 32.4554 31.2181 32.4554 31.7621C32.4554 32.3062 32.3481 32.8449 32.1398 33.3475C31.9314 33.8501 31.6261 34.3067 31.2412 34.6912Z" fill="#5D4038"/>
                </svg>
            </a>

            <h1>REGISTER</h1>

            <form action="../config/save_regis.php" method="POST" class="auth-card">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username_input" required autocomplete="off">
                    <span id="username_msg" style="font-size: 0.85rem; margin-top: 5px; display: block; font-weight: bold;"></span>
                </div>
                <div class="input-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <div class="btn-row">
                    <button type="submit" id="submit_btn" class="btn-inner-login">Sign Up</button>
                </div>
            </form>
            <p class="divider">or</p>
            
            <a href="login.php" class="btn-outer-signup">Back to Login</a>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด!',
                text: '<?php echo $_SESSION['error']; ?>',
                confirmButtonColor: '#5D4038'
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const usernameInput = document.getElementById('username_input');
        const usernameMsg = document.getElementById('username_msg');
        const submitBtn = document.getElementById('submit_btn');

        // เมื่อผู้ใช้พิมพ์ข้อความ
        usernameInput.addEventListener('input', function() {
            
            // 1. ดักจับภาษาไทย อักขระพิเศษ และการเว้นวรรค (ให้เหลือแค่ a-z, A-Z และ 0-9)
            let sanitizedValue = this.value.replace(/[^a-zA-Z0-9]/g, ''); 
            
            // ถ้าค่าที่พิมพ์มา มีอักขระแปลกปลอมผสมอยู่
            if (this.value !== sanitizedValue) {
                this.value = sanitizedValue; // ลบภาษาไทย/อักขระพิเศษออกทันทีในช่องพิมพ์
                usernameMsg.textContent = '⚠️ Please english or number';
                usernameMsg.style.color = '#d97706'; // สีส้มเตือน
                
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
                
                if (sanitizedValue.length === 0) return; 
            }

            let username = sanitizedValue.trim();
            
            if (username.length > 0) {
                // send to check_username.php
                fetch('../config/check_username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'taken') {
                        usernameMsg.textContent = '❌ This username is already taken.';
                        usernameMsg.style.color = '#E5989B';
                        
                        // Lock Button
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.5';
                        submitBtn.style.cursor = 'not-allowed';
                    } else if (data.status === 'available') {
                        usernameMsg.textContent = '✅ Username is available!';
                        usernameMsg.style.color = '#576d5c';
                        
                        // Unlock Button
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    }
                })
                .catch(error => console.error('Error:', error));
            } else {
                // ถ้าลบข้อความออกจนหมด ให้เคลียร์ข้อความแจ้งเตือน
                usernameMsg.textContent = '';
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        });
    });
    </script>
    
</body>
</html>