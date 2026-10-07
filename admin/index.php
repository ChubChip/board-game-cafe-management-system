<?php
session_start();
require_once '../config/connect.php';

// Check if user is logged in and has either A01 (Owner) or A02 (Admin) role
if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'A01' && $_SESSION['user_type'] !== 'A02' && $_SESSION['user_type'] !== 'A03' && $_SESSION['user_type'] !== 'A04' && $_SESSION['user_type'] !== 'A05')) {
    // Set error message for SweetAlert to display on the login/main page
    $_SESSION['error'] = "Access Denied! Only Admin or Owner can access the dashboard.";
    header("Location: ../main/index.php"); 
    exit();
}

// === Fetch existing data ===
$sql_rev = "SELECT SUM(total_amount) as total FROM rental WHERE status = 'Paid'";
$res_rev = mysqli_query($conn, $sql_rev);
$row_rev = mysqli_fetch_assoc($res_rev);
$revenue = $row_rev['total'] ? $row_rev['total'] : 2840;

$sql_cust = "SELECT COUNT(customer_id) as total FROM customer WHERE type_user = 'C02'";
$res_cust = mysqli_query($conn, $sql_cust);
$row_cust = mysqli_fetch_assoc($res_cust);
$customer_count = $row_cust['total'];

$sql_visit = "SELECT COUNT(id_user) as total FROM visitor_log";
$res_visit = mysqli_query($conn, $sql_visit);
$row_visit = mysqli_fetch_assoc($res_visit);
$visitor_count = $row_visit['total'];

/* GENDER DATA */
$sql_gender = "SELECT gender, COUNT(*) as count FROM customer WHERE gender IS NOT NULL GROUP BY gender";
$res_gender = mysqli_query($conn, $sql_gender);
$gender_data = ['female' => 0, 'male' => 0, 'none' => 0];

while($row = mysqli_fetch_assoc($res_gender)){
    if($row['gender'] == 'female') $gender_data['female'] = $row['count'];
    elseif($row['gender'] == 'male') $gender_data['male'] = $row['count'];
    else $gender_data['none'] = $row['count'];
}

if(array_sum($gender_data) == 0) { $gender_data = ['female' => 50, 'male' => 0, 'none' => 50]; }

/* BIRTHDAY DATA */
$sql_age = "SELECT 
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18 THEN 1 ELSE 0 END) AS age_under_18,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 22 THEN 1 ELSE 0 END) AS age_18_22,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 23 AND 30 THEN 1 ELSE 0 END) AS age_23_30,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) >= 31 THEN 1 ELSE 0 END) AS age_above_31,
    COUNT(customer_id) as total_valid_age
FROM customer 
WHERE birthday IS NOT NULL AND birthday != '0000-00-00'";

$res_age = mysqli_query($conn, $sql_age);
$age_data = mysqli_fetch_assoc($res_age);

$total_age = $age_data['total_valid_age'] > 0 ? $age_data['total_valid_age'] : 1; // Prevent division by zero error

$pct_under_18 = ($age_data['age_under_18'] / $total_age) * 100;
$pct_18_22 = ($age_data['age_18_22'] / $total_age) * 100;
$pct_23_30 = ($age_data['age_23_30'] / $total_age) * 100;
$pct_above_31 = ($age_data['age_above_31'] / $total_age) * 100;

/* WEEKLY REVENUE DATA */
$date_col = 'created_at'; 

date_default_timezone_set('Asia/Bangkok');
$thisWeekStart = date("Y-m-d", strtotime('monday this week'));
$thisWeekEnd = date("Y-m-d", strtotime('sunday this week'));
$lastWeekStart = date("Y-m-d", strtotime('monday last week'));
$lastWeekEnd = date("Y-m-d", strtotime('sunday last week'));

// Initialize arrays with 0 for 7 days
$thisWeekData = [0, 0, 0, 0, 0, 0, 0];
$lastWeekData = [0, 0, 0, 0, 0, 0, 0];

// Fetch this week's data
$sql_this_week = "SELECT DATE($date_col) as order_date, SUM(total_amount) as total 
                  FROM rental 
                  WHERE status = 'Paid' 
                  AND DATE($date_col) BETWEEN '$thisWeekStart' AND '$thisWeekEnd' 
                  GROUP BY DATE($date_col)";
$res_this_week = mysqli_query($conn, $sql_this_week);
if($res_this_week) {
    while($row = mysqli_fetch_assoc($res_this_week)) {
        $dayIndex = date('N', strtotime($row['order_date'])) - 1; // 0 = Mon, 6 = Sun
        $thisWeekData[$dayIndex] = (float)$row['total'];
    }
}

// Fetch last week's data
$sql_last_week = "SELECT DATE($date_col) as order_date, SUM(total_amount) as total 
                  FROM rental 
                  WHERE status = 'Paid' 
                  AND DATE($date_col) BETWEEN '$lastWeekStart' AND '$lastWeekEnd' 
                  GROUP BY DATE($date_col)";
$res_last_week = mysqli_query($conn, $sql_last_week);
if($res_last_week) {
    while($row = mysqli_fetch_assoc($res_last_week)) {
        $dayIndex = date('N', strtotime($row['order_date'])) - 1; 
        $lastWeekData[$dayIndex] = (float)$row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Dashboard</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    
    <link rel="stylesheet" href="../style/admin.css">
    <link rel="stylesheet" href="../style/dashboard.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div class="dashboard-container">
    <?php include_once('../include/topbar.php'); ?>
    
    <div class="main-grid">
        
        <div class="col-left">
            <div class="card-block-brown">
                <h2 class="block-title">Total</h2>
                <div class="total-cards">
                    <div class="revenue-card">
                        <div class="icon-circle">$</div>
                        <span>Revenue</span>
                        <div class="amount">฿<?php echo number_format($revenue / 1000, 2); ?> k</div>
                    </div>
                    <div class="small-card">
                        <div class="icon-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        </div>
                        <span>Customer</span>
                        <div class="count"><?php echo number_format($customer_count); ?></div>
                    </div>
                    <div class="small-card">
                        <div class="icon-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                        </div>
                        <span>Visitor</span>
                        <div class="count"><?php echo number_format($visitor_count); ?></div>
                    </div>
                </div>
            </div>

            <div class="card-block-brown">
                <h2 class="block-title">Age range</h2>
                
                <div class="age-bar-container" style="display: flex; height: 30px; border-radius: 15px; overflow: hidden; background-color: #ddd; margin-bottom: 15px;">
                    <?php if($pct_under_18 > 0): ?>
                    <div style="width: <?php echo $pct_under_18; ?>%; background-color: #E5989B; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                        <?php echo ($pct_under_18 > 10) ? round($pct_under_18).'%' : ''; ?>
                    </div>
                    <?php endif; ?>

                    <?php if($pct_18_22 > 0): ?>
                    <div style="width: <?php echo $pct_18_22; ?>%; background-color: #FFB4A2; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                        <?php echo ($pct_18_22 > 10) ? round($pct_18_22).'%' : ''; ?>
                    </div>
                    <?php endif; ?>

                    <?php if($pct_23_30 > 0): ?>
                    <div style="width: <?php echo $pct_23_30; ?>%; background-color: #B5838D; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                        <?php echo ($pct_23_30 > 10) ? round($pct_23_30).'%' : ''; ?>
                    </div>
                    <?php endif; ?>

                    <?php if($pct_above_31 > 0): ?>
                    <div style="width: <?php echo $pct_above_31; ?>%; background-color: #6D6875; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold;">
                        <?php echo ($pct_above_31 > 10) ? round($pct_above_31).'%' : ''; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px; font-size: 13px; color: var(--cream, #F5EBE1);">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #E5989B;"></span> < 18 ปี (<?php echo $age_data['age_under_18']; ?> คน)
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #FFB4A2;"></span> 18-22 ปี (<?php echo $age_data['age_18_22']; ?> คน)
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #B5838D;"></span> 23-30 ปี (<?php echo $age_data['age_23_30']; ?> คน)
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #6D6875;"></span> >= 31 ปี (<?php echo $age_data['age_above_31']; ?> คน)
                    </div>
                </div>
            </div>

            <div class="card-block-green">
                <h2 class="block-title">other</h2>
                <div class="action-btns">
                    <a href="manage_review.php" class="btn-other">Review</a>
                    <a href="manage_customer.php" class="btn-other">Customer</a>
                    <a href="manage_role.php" class="btn-other">Role</a>
                </div>
            </div>
        </div>

        <div class="col-center">
            
            <div class="chart-card">
                <div class="chart-title">Type of customer</div>
                <div class="canvas-container">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-title">Gender of customer</div>
                <div class="canvas-container">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="canvas-line-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

        </div>

        <div class="col-right">
            <h3>Update</h3>
            <a href="admin_checkout.php" class="update-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 38 38" fill="none">
                    <path d="M21.6667 2.70833C21.6667 1.99004 21.3813 1.30116 20.8734 0.793253C20.3655 0.285342 
                    19.6766 0 18.9583 0C18.24 0 17.5512 0.285342 17.0433 0.793253C16.5353 1.30116 16.25 1.99004 
                    16.25 2.70833V16.25H2.70833C1.99004 16.25 1.30116 16.5353 0.793253 17.0433C0.285342 17.5512 
                    0 18.24 0 18.9583C0 19.6766 0.285342 20.3655 0.793253 20.8734C1.30116 21.3813 1.99004 21.6667 
                    2.70833 21.6667H16.25V35.2083C16.25 35.9266 16.5353 36.6155 17.0433 37.1234C17.5512 37.6313 
                    18.24 37.9167 18.9583 37.9167C19.6766 37.9167 20.3655 37.6313 20.8734 37.1234C21.3813 36.6155 
                    21.6667 35.9266 21.6667 35.2083V21.6667H35.2083C35.9266 21.6667 36.6155 21.3813 37.1234 
                    20.8734C37.6313 20.3655 37.9167 19.6766 37.9167 18.9583C37.9167 18.24 37.6313 17.5512 37.1234 
                    17.0433C36.6155 16.5353 35.9266 16.25 35.2083 16.25H21.6667V2.70833Z" fill="#5D4038"/>
                </svg>
                Add Order
            </a>

            <a href="add_promotion.php" class="update-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M77.6961 16.5216L62.3386 7.31195L46.9812 16.5216L29.1439 18.1004L22.127 
                        34.5742L8.62744 46.3355L12.6382 63.79L8.62744 81.2446L22.127 93.0058L29.1439 109.48L46.9812 111.058L62.3386 120.268L77.6961 
                        111.058L95.5334 109.48L102.55 93.0058L116.058 81.2446L112.031 63.79L116.05 46.3355L102.542 34.5742L95.5254 18.1004L77.6961 
                        16.5216ZM43.8555 51.8294C43.8555 50.772 44.2755 49.7579 45.0232 49.0103C45.7709 48.2626 46.785 47.8425 47.8423 47.8425C48.8997 
                        47.8425 49.9138 48.2626 50.6615 49.0103C51.4092 49.7579 51.8292 50.772 51.8292 51.8294C51.8292 52.8868 51.4092 53.9009 50.6615 
                        54.6485C49.9138 55.3962 48.8997 55.8163 47.8423 55.8163C46.785 55.8163 45.7709 55.3962 45.0232 54.6485C44.2755 53.9009 43.8555 
                        52.8868 43.8555 51.8294ZM47.8423 39.8688C44.6702 39.8688 41.628 41.1289 39.3849 43.372C37.1419 45.615 35.8817 48.6572 35.8817 
                        51.8294C35.8817 55.0016 37.1419 58.0438 39.3849 60.2868C41.628 62.5299 44.6702 63.79 47.8423 63.79C51.0145 63.79 54.0567 62.5299 
                        56.2998 60.2868C58.5428 58.0438 59.803 55.0016 59.803 51.8294C59.803 48.6572 58.5428 45.615 56.2998 43.372C54.0567 41.1289 51.0145 
                        39.8688 47.8423 39.8688ZM46.6782 86.5471L86.5469 46.6784L80.9015 41.0329L41.0328 80.9017L46.6782 86.5471ZM75.7505 79.7375C75.7505 
                        78.6801 76.1705 77.6661 76.9182 76.9184C77.6659 76.1707 78.68 75.7506 79.7374 75.7506C80.7947 75.7506 81.8088 76.1707 82.5565 76.9184C83.3042 
                        77.6661 83.7242 78.6801 83.7242 79.7375C83.7242 80.7949 83.3042 81.809 82.5565 82.5567C81.8088 83.3044 80.7947 83.7244 79.7374 
                        83.7244C78.68 83.7244 77.6659 83.3044 76.9182 82.5567C76.1705 81.809 75.7505 80.7949 75.7505 79.7375ZM79.7374 67.7769C76.5652 
                        67.7769 73.523 69.037 71.2799 71.2801C69.0369 73.5231 67.7767 76.5654 67.7767 79.7375C67.7767 82.9097 69.0369 85.9519 71.2799 
                        88.195C73.523 90.438 76.5652 91.6981 79.7374 91.6981C82.9095 91.6981 85.9517 90.438 88.1948 88.195C90.4378 85.9519 91.698 82.9097 
                        91.698 79.7375C91.698 76.5654 90.4378 73.5231 88.1948 71.2801C85.9517 69.037 82.9095 67.7769 79.7374 67.7769Z" fill="#5D4038"/>
                </svg>
                Add Promotion
            </a>

            <a href="boardgame_info.php" class="update-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128" fill="none">
                    <path d="M31.8468 55.3254C32.2614 59.7109 29.9211 63.5143 26.6519 63.7775C23.3827 64.0366 20.3926 60.6956 19.9819 56.3101C19.5673 
                    51.9206 21.9076 48.1211 25.1768 47.858C28.446 47.567 31.4361 50.904 31.8468 55.3254ZM50.5769 103.642C53.8461 103.383 56.1824 99.5832 
                    55.7678 95.1937C55.3571 90.7723 52.363 87.4353 49.0978 87.7304C45.8286 87.9895 43.4883 91.789 43.903 96.1785C44.3136 100.564 47.3117 
                    103.905 50.5769 103.642ZM102.406 47.858C105.675 48.1172 108.011 51.9166 107.597 56.3061C107.186 60.7275 104.192 64.0645 100.923 
                    63.7735C97.6535 63.5103 95.3172 59.7109 95.7318 55.3214C96.1425 50.9359 99.1366 47.5949 102.406 47.858ZM95.6362 76.2403C96.0508 71.8548 
                    93.7105 68.0513 90.4453 67.7882C87.1761 67.5291 84.186 70.87 83.7713 75.2556C83.3567 79.6411 85.697 83.4445 88.9622 83.7077C92.2314 83.9987 
                    95.2215 80.6617 95.6362 76.2403ZM78.4848 87.7264C81.754 87.9855 84.0903 91.785 83.6757 96.1745C83.265 100.596 80.2709 103.933 77.0017 
                    103.638C73.7325 103.379 71.3962 99.5792 71.8108 95.1897C72.2215 90.8042 75.2156 87.4632 78.4848 87.7264Z" fill="#5D4038"/>
                    <path d="M55.3492 9.77182L17.9168 25.9305C17.0424 26.3238 16.2583 26.8421 15.5646 27.4853C13.3974 28.0173 11.4714 29.2612 10.0951 31.0177C8.71882 
                    32.7743 7.97178 34.9418 7.97364 37.1734V93.6668C7.97391 95.9901 8.65083 98.2631 9.9217 100.208C11.1926 102.153 13.0024 103.685 15.13 104.619L53.8063 
                    121.575C54.9904 122.093 56.1984 122.364 57.3945 122.412C61.5142 123.984 66.0686 123.984 70.1882 122.412C71.3763 122.364 72.5883 122.093 73.7724 
                    121.575L112.445 104.619C114.574 103.686 116.385 102.153 117.656 100.207C118.928 98.2614 119.605 95.9873 119.605 93.6629V37.1694C119.606 35.0247 
                    118.916 32.9368 117.636 31.2159C116.356 29.495 114.555 28.2329 112.501 27.6169C111.802 26.9079 110.977 26.3359 110.069 25.9305L72.2335 
                    9.77182C69.5767 8.58727 66.7003 7.9751 63.7914 7.9751C60.8824 7.9751 58.0061 8.58727 55.3492 9.77182ZM59.8025 59.0451V112.453C59.8017 112.783 59.7187 
                    113.109 59.5609 113.399C59.4032 113.69 59.1757 113.936 58.8989 114.117C58.622 114.298 58.3046 114.407 57.9752 114.434C57.6458 114.462 57.3147 114.407 
                    57.0117 114.275L18.3275 97.3188C17.6184 97.0066 17.0157 96.4945 16.593 95.8452C16.1703 95.196 15.9459 94.4376 15.9473 93.6629V37.1694C15.9477 36.8364 
                    16.0315 36.5088 16.1911 36.2165C16.3507 35.9242 16.5809 35.6765 16.8609 35.4961C17.1408 35.3157 17.4614 35.2083 17.7935 35.1838C18.1256 35.1592 
                    18.4586 35.2182 18.762 35.3554L55.11 51.7732C56.5092 52.4049 57.6963 53.4271 58.5287 54.7171C59.3611 56.0071 59.8034 57.5099 59.8025 59.0451ZM67.7762 
                    112.453V59.0372C67.7761 57.5026 68.2187 56.0006 69.0511 54.7114C69.8834 53.4223 71.0701 52.4006 72.4687 51.7692L108.817 35.3514C109.12 35.214 109.454 
                    35.1551 109.786 35.1798C110.119 35.2046 110.44 35.3124 110.72 35.4932C111 35.6741 111.23 35.9223 111.389 36.2151C111.548 36.5079 111.632 36.836 
                    111.631 37.1694V93.6629C111.631 94.4367 111.405 95.1936 110.982 95.8414C110.559 96.4892 109.956 96.9997 109.247 97.3108L70.567 114.271C70.2638 114.404 
                    69.9323 114.459 69.6023 114.432C69.2724 114.405 68.9543 114.296 68.677 114.115C68.3996 113.935 68.1717 113.688 68.0137 113.397C67.8558 113.106 67.7728 
                    112.78 67.7722 112.449M63.7894 29.9014C57.1832 29.9014 51.8288 28.1153 51.8288 25.9145C51.8288 23.7138 57.1832 21.9277 63.7894 21.9277C70.3955 
                    21.9277 75.7499 23.7138 75.7499 25.9145C75.7499 28.1153 70.3955 29.9014 63.7894 29.9014Z" fill="#5D4038"/>
                </svg>
                Board Game info
            </a>

            <a href="menu_info.php" class="update-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 157 157" fill="none">
                    <path d="M14 136.781C14 139.912 16.6096 142.58 19.7991 142.58H95.1873C98.4348 142.58 100.986 139.912 100.986 
                    136.781V130.982H14V136.781ZM57.4932 61.3927C35.7466 61.3927 14 72.9909 14 96.1873H100.986C100.986 72.9909 79.2398 
                    61.3927 57.4932 61.3927ZM29.1936 84.5891C35.6306 75.6005 49.3165 72.9909 57.4932 72.9909C65.6699 72.9909 79.3558 
                    75.6005 85.7927 84.5891H29.1936ZM14 107.785H100.986V119.384H14V107.785ZM112.585 38.1964V15H100.986V38.1964H71.9909L73.3247 
                    49.7945H128.764L120.645 130.982H112.585V142.58H122.559C127.43 142.58 131.432 138.811 132.012 134.055L141.58 38.1964H112.585Z" fill="#5D4038"/>
                </svg>
                Menu info
            </a>

            <a href="table_info.php" class="update-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128" fill="none">
                    <path d="M89.3049 58.4736H38.4063L36.9444 69.1052H90.6339L89.3049 58.4736ZM22.0603 100.335L27.7747 
                    58.4736H15.9471C14.1752 58.4736 12.7807 57.7648 11.7636 56.3473C10.7465 54.9297 10.4577 53.3793 10.8971 
                    51.696L18.4721 25.1171C18.8265 23.9653 19.4467 23.035 20.3326 22.3263C21.2186 21.6175 22.2817 21.2631 
                    23.5221 21.2631H104.056C105.297 21.2631 106.36 21.6175 107.246 22.3263C108.132 23.035 108.752 23.9653 
                    109.106 25.1171L116.681 51.696C117.124 53.3793 116.837 54.9297 115.82 56.3473C114.803 57.7648 113.407 
                    58.4736 111.631 58.4736H99.9365L105.518 100.335C105.695 101.93 105.274 103.326 104.253 104.524C103.232 
                    105.722 101.926 106.319 100.335 106.316C99.0062 106.316 97.8314 105.894 96.8108 105.05C95.7902 104.207 
                    95.193 103.123 95.0194 101.797L92.0957 79.7367H35.4826L32.5589 101.797C32.3817 103.126 31.7846 104.212 
                    30.7675 105.056C29.7504 105.899 28.5756 106.319 27.2431 106.316C25.6484 106.316 24.3425 105.719 23.3254 
                    104.524C22.3083 103.33 21.8866 101.934 22.0603 100.335Z" fill="#5D4038"/>
                </svg>
                About Table
            </a>

            

            

            
        </div>

    </div>
</div>

<script>
    const ctxType = document.getElementById('typeChart').getContext('2d');
    new Chart(ctxType, {
        type: 'pie',
        data: {
            labels: ['member', 'guest'],
            datasets: [{
                data: [<?php echo $customer_count; ?>, <?php echo $visitor_count; ?>],
                backgroundColor: ['#E5989B', '#576d5c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 10 } }
            }
        }
    });

    const ctxGender = document.getElementById('genderChart').getContext('2d');
    new Chart(ctxGender, {
        type: 'doughnut',
        data: {
            labels: ['female', 'male', 'prefer not to say'],
            datasets: [{
                data: [<?php echo $gender_data['female']; ?>, <?php echo $gender_data['male']; ?>, <?php echo $gender_data['none']; ?>],
                backgroundColor: ['#EAA4A4', '#9B6E60', '#576d5c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 10 } }
            }
        }
    });

    const dataThisWeek = <?php echo json_encode($thisWeekData); ?>;
    const dataLastWeek = <?php echo json_encode($lastWeekData); ?>;

    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [
                {
                    label: 'This Week',
                    data: dataThisWeek,
                    borderColor: '#576d5c',
                    backgroundColor: '#576d5c',
                    tension: 0.1,
                    pointRadius: 3
                },
                {
                    label: 'Last Week',
                    data: dataLastWeek,
                    borderColor: '#E5989B',
                    backgroundColor: '#E5989B',
                    tension: 0.1,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } }, 
            scales: {
                y: {
                    beginAtZero: true
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonColor: '#5D4038', /* var(--brown-dark) */
            background: '#F5EBE1',         /* var(--cream) */
            color: '#5D4038'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $_SESSION['success']; ?>',
            confirmButtonColor: '#576d5c', /* var(--green) */
            background: '#F5EBE1',         /* var(--cream) */
            color: '#5D4038'
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

</body>
</html>