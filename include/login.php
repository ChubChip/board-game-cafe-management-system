<?php session_start(); ?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Login</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    <!-- LINK -->
    <link rel="stylesheet" href="../style/login-regis.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <!-- PHP INCLUDE -->   
    <?php
    include('../config/connect.php');
    include('../config/install.php');
    ?>

    <div class="bg-overlay">
        <div class="login-container">

            <!-- CLOSE BUTTON -->
            <a href="../main/index.php" class="close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="33" height="36" viewBox="0 0 33 36" fill="none">
                <path d="M31.2412 34.6912C30.4648 35.4675 29.4118 35.9036 28.3138 35.9036C27.2159 35.9036 26.1629 35.4675 25.3865 34.6912L16.2406 24.2377L7.09461 34.6877C6.71133 35.0773 6.25471 35.3871 5.75109 35.5993C5.24747 35.8116 4.70681 35.922 4.16031 35.9242C3.6138 35.9264 3.07226 35.8204 2.56693 35.6123C2.0616 35.4042 1.60247 35.0981 1.21603 34.7117C0.829587 34.3252 0.523483 33.8661 0.315371 33.3608C0.10726 32.8554 0.00125876 32.3139 0.00348407 31.7674C0.00570939 31.2209 0.116116 30.6802 0.328336 30.1766C0.540556 29.673 0.85039 29.2163 1.23997 28.8331L10.7551 17.9656L1.23652 7.09117C0.846941 6.70789 0.537107 6.25126 0.324887 5.74764C0.112666 5.24402 0.00225964 4.70336 3.43239e-05 4.15686C-0.00219099 3.61035 0.10381 3.06882 0.311922 2.56348C0.520033 2.05815 0.826137 1.59902 1.21258 1.21258C1.59902 0.826137 2.05815 0.520033 2.56348 0.311922C3.06881 0.10381 3.61035 -0.00219099 4.15686 3.43239e-05C4.70336 0.00225964 5.24402 0.112666 5.74764 0.324887C6.25126 0.537107 6.70788 0.846941 7.09116 1.23652L16.2406 11.6935L25.3865 1.23652C25.7698 0.846941 26.2264 0.537107 26.73 0.324887C27.2337 0.112666 27.7743 0.00225964 28.3208 3.43239e-05C28.8673 -0.00219099 29.4089 0.10381 29.9142 0.311922C30.4195 0.520033 30.8787 0.826137 31.2651 1.21258C31.6515 1.59902 31.9576 2.05815 32.1658 2.56348C32.3739 3.06882 32.4799 3.61035 32.4776 4.15686C32.4754 4.70336 32.365 5.24402 32.1528 5.74764C31.9406 6.25126 31.6307 6.70789 31.2412 7.09117L21.7261 17.9656L31.2412 28.8331C31.6261 29.2176 31.9314 29.6742 32.1398 30.1767C32.3481 30.6793 32.4554 31.2181 32.4554 31.7621C32.4554 32.3062 32.3481 32.8449 32.1398 33.3475C31.9314 33.8501 31.6261 34.3067 31.2412 34.6912Z" fill="#5D4038"/>
                </svg>
            </a>
            <!-- END CLOSE  BUTTON -->

            <h1>LOGIN</h1>

            <!-- FORM -->
            <form action="../config/check_login.php" method="POST" class="auth-card">
    
                <!-- Username/E-mail -->
                <div class="input-group">
                    <label>Username/E-mail</label>
                    <input type="text" name="identity" required>
                </div>
                
                <!-- Password -->
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <!-- Forgot Password -->
                <a href="forgot_password.php" class="forgot-pwd">Forgot Password?</a>

                <!-- Submit -->
                <div class="btn-row">
                    <button type="submit" name="login_btn" class="btn-inner-login">Login</button>
                </div>
                
            </form>
            <!--  END FORM -->
            
            <p class="divider">or</p>
            
            <a href="register.php" class="btn-outer-signup">Sign up</a>
        </div>
    </div>


    <!-- SweetAlert2 for error messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบไม่สำเร็จ',
                text: '<?php echo $_SESSION['error']; ?>',
                confirmButtonColor: '#5D4038',
                background: '#F5EBE0',
                color: '#5D4038'
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
</body>
</html>