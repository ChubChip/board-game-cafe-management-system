<?php
include_once('../config/connect.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// set Default variables
$currentUser = null;
$img_folder = "customer_img"; 
$isLoggedIn = false; 
$isAdmin = false; 
$chat_messages = []; // Array for storing chat history
$favorite_games = []; // Array for storing favorite boardgames

// check type user
if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $uid = $_SESSION['user_id'];
    $utype = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'C01';
    
    // Define admin roles array for strict checking (A01 to A05)
    $admin_roles = ['A01', 'A02', 'A03', 'A04', 'A05'];
    
    // Check if user type matches any admin role
    if (in_array($utype, $admin_roles)) {
        $isAdmin = true; 
        $sql_topbar = "SELECT * FROM admins WHERE admin_id = '$uid'";
        $img_folder = "admin_img";
    } else {
        // Assume customer if not in admin roles
        $sql_topbar = "SELECT * FROM customer WHERE customer_id = '$uid'";
        $img_folder = "customer_img";
        
        // Fetch chat history for this customer
        $sql_chat = "SELECT * FROM messages WHERE customer_id = '$uid' ORDER BY created_at ASC";
        $res_chat = mysqli_query($conn, $sql_chat);
        if ($res_chat) {
            while($row = mysqli_fetch_assoc($res_chat)){
                $chat_messages[] = $row;
            }
        }

        // Fetch favorite games
        $sql_fav = "SELECT b.* FROM favorites f JOIN boardgame b ON f.boardgame_id = b.boardgame_id WHERE f.customer_id = '$uid'";
        $res_fav = mysqli_query($conn, $sql_fav);
        if($res_fav) {
            while($row = mysqli_fetch_assoc($res_fav)){
                $favorite_games[] = $row;
            }
        }
        // Fetch history bookings
        $bookings = [];
        $sql_book = "SELECT r.rent_id, r.rent_date, r.rent_time, r.status, t.table_name
                     FROM rental r
                     JOIN rental_detail rd ON r.rent_id = rd.rent_id
                     JOIN table_info t ON rd.table_id = t.table_id
                     WHERE r.customer_id = '$uid'
                     ORDER BY r.rent_date DESC, r.rent_time DESC";
        $res_book = mysqli_query($conn, $sql_book);
        if ($res_book) {
            while($row = mysqli_fetch_assoc($res_book)) {
                $bookings[] = $row;
            }
        }
    }

    // Execute the main user query
    $res_topbar = mysqli_query($conn, $sql_topbar);
    
    // BUG FIX: Validate if user actually exists in the correct table
    if ($res_topbar && mysqli_num_rows($res_topbar) > 0) {
        $currentUser = mysqli_fetch_assoc($res_topbar);
    } else {
        // Mismatch found (e.g. session data is stuck from previous login)
        // Clear all session variables to force a clean state
        session_unset();
        session_destroy();
        $isLoggedIn = false;
        $isAdmin = false;
        $currentUser = null;
    }
}
?>

<link rel="stylesheet" href="../style/history.css">
<link rel="stylesheet" href="../style/topbar.css">
<link rel="stylesheet" href="../style/message.css"> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<nav class="navbar">

    <!-- LEFT -->
     <div class="nav-left" style="display: flex; gap: 15px; align-items: center; height: 100%;">

        <!-- Icon user -->
         <div class="icon-user" onclick="checkProfileClick()" style="cursor: pointer; margin: 0;">
            <?php if ($currentUser && !empty($currentUser['upload'])) { 
                $imgShow = "../img/$img_folder/" . $currentUser['upload'];
            ?>
                <img src="<?php echo $imgShow; ?>" style="width:40px; height:40px; border-radius:10px; object-fit:cover; border: 2px solid #E5989B; display: block;">
            <?php } else { ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 65 65" fill="none" style="display: block;">
                    <rect width="65" height="65" rx="10" fill="#E5989B"/>
                    <path d="M32.5 10.8333C35.3732 10.8333 38.1287 11.9747 40.1603 14.0063C42.192 16.038 43.3334 18.7935 43.3334 21.6667C43.3334 24.5398 
                    42.192 27.2953 40.1603 29.327C38.1287 31.3586 35.3732 32.5 32.5 32.5C29.6268 32.5 26.8713 31.3586 24.8397 29.327C22.8081 27.2953 21.6667 
                    24.5398 21.6667 21.6667C21.6667 18.7935 22.8081 16.038 24.8397 14.0063C26.8713 11.9747 29.6268 10.8333 32.5 10.8333ZM32.5 16.25C31.0634 
                    16.25 29.6857 16.8207 28.6699 17.8365C27.654 18.8523 27.0834 20.2301 27.0834 21.6667C27.0834 23.1033 27.654 24.481 28.6699 25.4968C29.6857 
                    26.5126 31.0634 27.0833 32.5 27.0833C33.9366 27.0833 35.3144 26.5126 36.3302 25.4968C37.346 24.481 37.9167 23.1033 37.9167 21.6667C37.9167 
                    20.2301 37.346 18.8523 36.3302 17.8365C35.3144 16.8207 33.9366 16.25 32.5 16.25ZM32.5 35.2083C39.7313 35.2083 54.1667 38.8104 54.1667 
                    46.0417V54.1667H10.8334V46.0417C10.8334 38.8104 25.2688 35.2083 32.5 35.2083ZM32.5 40.3542C24.4563 40.3542 15.9792 44.3083 15.9792 
                    46.0417V49.0208H49.0209V46.0417C49.0209 44.3083 40.5438 40.3542 32.5 40.3542Z" fill="#F5ECE3"/>
                </svg>
            <?php } ?>
         </div>
        <!-- end Icon user -->
        
        <?php if (!$isAdmin): ?>

        <!-- Icon history -->
         <div class="icon-history" onclick="checkHistoryClick()" style="cursor: pointer; margin: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 55 55" fill="none" style="display: block;">
                <rect width="55" height="55" rx="10" fill="#9B6E60"/>
                <path d="M27.1721 47.5512C22.417 47.5512 18.2091 46.1081 14.5484 43.2218C10.8877 40.3355 8.51016 36.6461 7.41572 32.1537C7.26477 31.5876 7.37799 
                31.0691 7.75538 30.5981C8.13277 30.1271 8.64224 29.8531 9.28381 29.7761C9.88763 29.7006 10.4348 29.8139 10.9255 30.1158C11.4161 30.4177 11.7557 
                30.8706 11.9444 31.4744C12.8502 34.8709 14.7182 37.6447 17.5487 39.7958C20.3791 41.947 23.5869 43.0225 27.1721 43.0225C31.5876 43.0225 35.3336 
                41.485 38.4101 38.4101C41.4866 35.3351 43.024 31.5891 43.0225 27.1721C43.021 22.7551 41.4835 19.0099 38.4101 15.9365C35.3366 12.863 31.5906 11.3247 
                27.1721 11.3217C24.5681 11.3217 22.134 11.9255 19.8696 13.1332C17.6053 14.3408 15.6995 16.0014 14.1521 18.1147H18.1148C18.7563 18.1147 19.2945 18.3321 
                19.7292 18.7669C20.164 19.2016 20.3806 19.739 20.3791 20.3791C20.3776 21.0191 20.1602 21.5573 19.727 21.9936C19.2937 22.4298 18.7563 22.6465 18.1148 
                22.6434H9.05737C8.41581 22.6434 7.87841 22.4261 7.44516 21.9913C7.01192 21.5566 6.79454 21.0191 6.79303 20.3791V11.3217C6.79303 10.6802 7.01041 10.1427 
                7.44516 9.70951C7.87992 9.27626 8.41732 9.05888 9.05737 9.05737C9.69743 9.05586 10.2356 9.27324 10.6719 9.70951C11.1081 10.1458 11.3247 10.6832 11.3217 
                11.3217V14.3786C13.2464 11.9633 15.596 10.0952 18.3706 8.77433C21.1452 7.45346 24.079 6.79303 27.1721 6.79303C30.0026 6.79303 32.6541 7.33119 35.1268 
                8.40751C37.5994 9.48383 39.7506 10.936 41.5802 12.7641C43.4097 14.5922 44.8627 16.7433 45.939 19.2175C47.0153 21.6917 47.5527 24.3432 47.5512 
                27.1721C47.5497 30.001 47.0123 32.6526 45.939 35.1268C44.8657 37.6009 43.4128 39.7521 41.5802 41.5801C39.7475 43.4082 37.5964 44.8612 35.1268 45.939C32.6571 
                47.0168 30.0056 47.5542 27.1721 47.5512ZM29.4365 26.2664L35.0973 31.9272C35.5125 32.3424 35.72 32.8707 35.72 33.5123C35.72 34.1539 35.5125 34.6822 35.0973 
                35.0973C34.6822 35.5125 34.1539 35.72 33.5123 35.72C32.8707 35.72 32.3424 35.5125 31.9273 35.0973L25.5871 28.7572C25.3607 28.5307 25.1908 28.2764 25.0776 
                27.9941C24.9644 27.7118 24.9078 27.4189 24.9078 27.1155V18.1147C24.9078 17.4732 25.1252 16.9358 25.5599 16.5025C25.9947 16.0693 26.5321 15.8519 27.1721 
                15.8504C27.8122 15.8489 28.3503 16.0663 28.7866 16.5025C29.2229 16.9388 29.4395 17.4762 29.4365 18.1147V26.2664Z" fill="#F5ECE3"/>
            </svg>
         </div>
        <!-- end Icon history -->

        <!-- Icon favorite -->
         <div class="icon-favorite" onclick="checkFavoriteClick()" style="cursor: pointer; margin: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 61 61" fill="none" style="display: block;">
                <rect width="61" height="61" rx="10" fill="#E5989B"/> <path d="M29.8024 45.0947L27.5622 43.0554C19.6058 35.8405 14.353 31.0667 14.353 25.2423C14.353 20.4684 18.0918 16.7451 22.8502 16.7451C25.5384 16.7451 
                28.1184 17.9965 29.8024 19.9586C31.4864 17.9965 34.0664 16.7451 36.7546 16.7451C41.513 16.7451 45.2517 20.4684 45.2517 25.2423C45.2517 31.0667 39.9989 
                35.8405 32.0425 43.0554L29.8024 45.0947Z" fill="#F5EBE0"/>
            </svg>
         </div>
        <!-- end -->

        <?php endif; ?>
     </div>
    <!-- end LEFT -->

    <!-- CENTER -->
     <ul class="nav-menu">

        <?php if ($isAdmin): ?>
        <!-- ADMIN -->
            <li>
                <a href="../admin/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Dashboard</a>
            </li>

            <li>
                <a href="../admin/manage_bill.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_bill.php') ? 'active' : ''; ?>">Bill</a>
            </li>

            <li>
                <a href="../admin/message.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'message.php') ? 'active' : ''; ?>">Message</a>
            </li>
        <!-- end ADMIN -->

        <?php else: ?>

        <!-- USER -->
            <li>
                <a <?php if (isset($_SESSION['username'])): ?> href="../main/home.php" <?php else: ?>href="../main/index.php"<?php endif; ?> class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'home.php' || basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a>
            </li>

            <li>
                <a href="#" id="searchBtn" class="nav-link">Search</a>
            </li>

            <li>
                <a href="#" onclick="openMessageModal(event)" class="nav-link">Message</a>
            </li>
        <!-- end USER -->

        <?php endif; ?>

        <!-- underline -->
        <div class="nav-underline"></div>

     </ul>
    <!-- end CENTER -->

    <!-- BUTTON login-regis/logout -->
     <div class="auth-buttons">
        <?php if (isset($_SESSION['username'])): ?>
            <span style="margin-right: 10px; color: #5D4038; font-weight: bold;">
                <?php echo $_SESSION['username']; ?>
            </span>
            <a href="../include/logout.php" class="btn-logout">Logout</a> 
        <?php else: ?>
            <a href="../include/login.php" class="btn-login">Log In</a>
            <a href="../include/register.php" class="btn-register">Sign Up</a>
        <?php endif; ?>
     </div>
    <!-- end BUTTON login-regis/logout -->

</nav>


<?php if (!$isAdmin): ?><!-- if not ADMIN -->


<!-- Search modal -->
 <div id="searchModal" class="search-modal">
    <div class="search-modal-content">
        <span class="close-search">&times;</span>
        <h2 style="color: #5D4038; margin-bottom: 15px;">Search Board Games</h2>
        <input type="text" id="searchInput" placeholder="Search board game name..." autocomplete="off">
        <div id="searchResults" class="search-results-container"></div>
    </div>
 </div>
<!-- end Search modal -->

<!-- Favorite modal -->
 <div id="favoriteModal" class="history-modal" style="display: none;">
    <div class="history-modal-content" style="max-width: 600px;">
        <span class="history-close" onclick="closeFavoriteModal()">&times;</span>
        <h2 style="text-align:center; color:#5D4038; margin-bottom: 20px;">❤️ My Favorite Games</h2>
        <div id="favoriteList" style="max-height: 400px; overflow-y: auto; padding: 10px;">
            <?php if (empty($favorite_games)): ?>
                <p style="text-align:center; color:#888;">No favorite games added yet.</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 15px;">
                <?php foreach ($favorite_games as $fav): 
                    $img = !empty($fav['upload']) ? "../img/boardgame/".$fav['upload'] : '../img/default_bg.jpg';
                ?>
                    <div style="border: 1px solid #eee; border-radius: 8px; padding: 10px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                        <img src="<?= htmlspecialchars($img) ?>" style="width: 100%; height: 100px; object-fit: cover; border-radius: 5px; margin-bottom: 8px;">
                        <h4 style="margin: 0 0 5px 0; font-size: 14px; color: #5D4038; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($fav['boardgame_name']) ?></h4>
                        <a href="../main/boardgame_detail.php?id=<?= urlencode($fav['boardgame_id']) ?>" style="display: inline-block; background-color: #E5989B; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold;">View</a>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
 </div>
<!-- end Favorite modal -->

<!-- Message modal -->
 <div id="messageModal" class="msg-modal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
    <div class="msg-modal-content" style="background-color: white; width: 90%; padding: 0; overflow: hidden; display: flex; flex-direction: column; height: 500px; max-width: 450px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        
        <div style="background-color: #E5989B; color: white; padding: 15px; text-align: center; position: relative;">
            <span class="msg-close" onclick="closeMessageModal()" style="position: absolute; right: 15px; top: 10px; cursor: pointer; font-size: 24px; color: white;">&times;</span>
            <h3 style="margin: 0; font-family: 'Kanit', sans-serif;">💬 Talk to Us</h3>
        </div>

        <div class="chat-messages" id="chatBoxModal" style="flex: 1; padding: 15px; overflow-y: auto; background-color: #fcf9f7; display: flex; flex-direction: column;">
            <?php
            if(empty($chat_messages)) {
                echo '<p style="text-align:center; color:#ccc; margin-top:20px;">Start a conversation with the admin.</p>';
            } else {
                foreach($chat_messages as $m) {
                    $is_self = ($m['sender_type'] == 'customer');
                    $bubble_align = $is_self ? 'align-self: flex-end; background-color: #E5989B; color: white; border-bottom-right-radius: 5px;' : 'align-self: flex-start; background-color: #5D4038; color: white; border-bottom-left-radius: 5px;';
                    $time = date('H:i', strtotime($m['created_at']));
                    
                    echo '<div style="max-width: 75%; padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; font-size: 15px; line-height: 1.4; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,0.1); ' . $bubble_align . '">';
                    echo nl2br(htmlspecialchars($m['message_text']));
                    echo '<span style="font-size: 11px; opacity: 0.7; margin-top: 5px; display: block; text-align:'.($is_self?'right':'left').';">'.$time.'</span>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <div style="padding: 10px; background-color: #fff; border-top: 1px solid #eee;">
            <form id="chatForm" style="display: flex; gap: 10px; margin: 0;">
                <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off" required style="flex: 1; padding: 10px 15px; border: 1px solid #ccc; border-radius: 20px; outline: none; font-family: 'Kanit', sans-serif;">
                <button type="submit" style="background-color: #E5989B; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer; font-weight: bold; font-family: 'Kanit', sans-serif;">Send</button>
            </form>
        </div>
        
    </div>
 </div>
<!-- end Message modal -->

<?php endif; ?>

<!-- History modal -->
 <div id="historyModal" class="history-modal" style="display: none;">
    <div class="history-modal-content">
        <span class="history-close" onclick="closeHistoryModal()">&times;</span>
        <h2 style="text-align:center; color:#5D4038; margin-bottom: 20px;">Booking History</h2>
        
        <div id="historyList">
            <?php if (!empty($bookings)): ?>
                <?php foreach ($bookings as $b): ?>
                    <div class="booking-card" style="background: white; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid <?php echo $b['status'] == 'Unpaid' ? '#E5989B' : ($b['status'] == 'Cancelled' ? '#999' : '#576d5c'); ?>;">
                        <h4 style="margin: 0 0 10px 0; color: #5D4038; font-size: 1.1rem;"> <i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($b['table_name']); ?> </h4>
                        <p style="margin: 5px 0; font-size: 0.9rem; color: #666;"><strong>Date:</strong> <?php echo htmlspecialchars($b['rent_date']); ?></p>
                        <p style="margin: 5px 0; font-size: 0.9rem; color: #666;"><strong>Time:</strong> <?php echo htmlspecialchars($b['rent_time']); ?></p>
                        <p style="margin: 5px 0; font-size: 0.9rem; color: #666;">
                            <strong>Status:</strong> 
                            <span style="font-weight: bold; color: <?php echo $b['status'] == 'Unpaid' ? '#E5989B' : ($b['status'] == 'Cancelled' ? '#999' : '#576d5c'); ?>;">
                                <?php echo htmlspecialchars($b['status']); ?>
                            </span>
                        </p>
                        
                        <?php if ($b['status'] == 'Unpaid'): ?>
                            <button onclick="cancelBooking('<?php echo htmlspecialchars($b['rent_id']); ?>')" style="margin-top: 10px; width: 100%; padding: 8px; background-color: #E5989B; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s;" onmouseover="this.style.backgroundColor='#d33'" onmouseout="this.style.backgroundColor='#E5989B'">
                                Cancel Booking
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #9B6E60; padding: 20px 0;">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No booking history found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
 </div>
<!-- end History modal -->

<!-- if user Login and have an information -->
<?php if ($isLoggedIn && $currentUser): ?>

<!-- Edit profile modal -->
 <div id="profileModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeProfileModal()">&times;</span>
    <h2 style="text-align:center; color:#5D4038; margin-bottom: 20px;">Edit Profile</h2>
    <form action="../config/update_profile.php" method="post" enctype="multipart/form-data" onsubmit="return validateProfileForm()">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="position: relative; display: inline-block;">
                <?php if (!empty($currentUser['upload'])) { ?>
                    <img id="previewImg" src="../img/<?php echo $img_folder; ?>/<?php echo $currentUser['upload']; ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #E5989B;">
                <?php } else { ?>
                    <div id="previewSvg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 65 65" fill="none">
                            <rect width="65" height="65" rx="10" fill="#E5989B"/>
                        </svg>
                    </div>
                <?php } ?>
            </div>
            <br>
            <label for="file-upload" class="custom-file-upload">Change Picture</label>
            <input id="file-upload" type="file" name="profile_img" accept="image/*" onchange="previewImage(event)">
        </div>

        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
        </div>

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
        </div>

        <div class="input-group">
            <label>Birthday :</label>
            <input type="date" name="birthday" value="<?php echo $currentUser['birthday'] ?? ''; ?>">
        </div>

        <div class="input-group">
            <label>Phone number :</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" maxlength="10">
        </div>

        <div class="input-group">
            <label>Gender</label>
            <select name="gender">
                <option value="male" <?php if(isset($currentUser['gender']) && $currentUser['gender'] == 'male') echo 'selected'; ?>>Male</option>
                <option value="female" <?php if(isset($currentUser['gender']) && $currentUser['gender'] == 'female') echo 'selected'; ?>>Female</option>
                
                <option value="Prefer not to say" <?php if(empty($currentUser['gender']) || $currentUser['gender'] == 'Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
            </select>
        </div>

        <hr style="border: 0; border-top: 1px solid #ccc; margin: 20px 0;">
        <p style="font-size: 14px; color: #888; margin-bottom: 10px;">* Leave blank to keep current password.</p>
        
        <div class="input-group">
            <label>New password</label>
            <input type="password" name="new_password" id="new_password">
        </div>
        <div class="input-group">
            <label>Confirm password</label>
            <input type="password" id="confirm_password">
        </div>

        <button type="submit" class="btn-save">Save</button>
    </form>
  </div>
 </div>
<!-- end Edit Profile modal -->

<?php endif; ?>


<script>
var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>; // if user no login

// --- Profile warning & open/close Profile modal ---
 function checkProfileClick() {
    if (isLoggedIn) {
        openProfileModal();
    } else {
        Swal.fire({
            title: 'Please Log In',
            text: "You must log in to manage your profile.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5D4038',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Login / Sign Up'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../include/login.php';
            }
        });
    }
 }
 function openProfileModal() { document.getElementById("profileModal").style.display = "block"; }
 function closeProfileModal() { document.getElementById("profileModal").style.display = "none"; }
// --- end Profile warning & open/close Profile modal ---



// --- preview img ---
 function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('previewImg');
        if(output) output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
 }
// --- end preview img ---



// --- new password ---
 function validateProfileForm() {
    const phone = document.getElementById('phone').value;
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;

    if (phone !== "" && (!/^\d+$/.test(phone) || phone.length !== 10)) {
        Swal.fire('Error', 'Please enter a 10-digit phone number.', 'error');
        return false;
    }

    if (newPass !== "") {
        if (newPass.length < 6) {
            Swal.fire('Error', 'Password must be at least 6 characters long.', 'error');
            return false;
        }
        if (newPass !== confirmPass) {
            Swal.fire('Error', 'New passwords do not match.', 'error');
            return false;
        }
    }
    return true;
 }
// --- end new password ---



// --- History warning & open/close History ---
 function checkHistoryClick() {
    if (isLoggedIn) {
        openHistoryModal();
    } else {
        Swal.fire({
            title: 'Please Log In',
            text: "You must log in to view your booking history.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5D4038',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Login / Sign Up'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../include/login.php';
            }
        });
    }
 }
 function openHistoryModal() { document.getElementById("historyModal").style.display = "block"; }
 function closeHistoryModal() { document.getElementById("historyModal").style.display = "none"; }
// --- end History warning & open/close History ---



// --- Favorite warning ---
 function checkFavoriteClick() {
    if (isLoggedIn) {
        openFavoriteModal();
    } else {
        Swal.fire({
            title: 'Please Log In',
            text: "You must log in to view your favorite games.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5D4038',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Login / Sign Up'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../include/login.php';
            }
        });
    }
 }
// --- end Favorite warning ---

// --- open/close Favorite ---
 function openFavoriteModal() { document.getElementById("favoriteModal").style.display = "block"; }
 function closeFavoriteModal() { document.getElementById("favoriteModal").style.display = "none"; }
// --- end open/close Favorite ---



// --- open & close Message ---
 function openMessageModal(e) {
    if(e) e.preventDefault();
    
    if (isLoggedIn) {
        document.getElementById("messageModal").style.display = "flex";
        
        // Active Nav State Setup
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            link.style.fontWeight = 'normal';
        });
        
        const msgBtn = Array.from(document.querySelectorAll('.nav-link')).find(el => el.textContent.trim() === 'Message');
        if(msgBtn) {
            msgBtn.classList.add('active');
            msgBtn.style.fontWeight = 'bold';
        }

        // Save state
        localStorage.setItem('chatModalOpen', 'true');

        var chatBox = document.getElementById("chatBoxModal");
        if(chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    } else {
        Swal.fire({
            title: 'Please Log In',
            text: "You must log in to chat with the admin.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5D4038',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Login / Sign Up'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../include/login.php';
            }
        });
    }
 }
 function closeMessageModal() {
    const modal = document.getElementById("messageModal");
    if(modal) modal.style.display = "none";
    
    localStorage.removeItem('chatModalOpen');
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        link.style.fontWeight = 'normal';
    });
    const homeBtn = Array.from(document.querySelectorAll('.nav-link')).find(el => el.textContent.trim() === 'Home');
    if(homeBtn) {
        homeBtn.classList.add('active');
    }
 }
// --- end open & close Message ---


// --- SEARCHING Real-time & Modal Close Handler ---
 document.addEventListener("DOMContentLoaded", function() {
    const searchBtn = document.getElementById('searchBtn');
    const searchModal = document.getElementById('searchModal');
    const closeSearch = document.querySelector('.close-search');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if(searchBtn && searchModal) {
        searchBtn.addEventListener('click', (e) => {
            e.preventDefault();
            searchModal.classList.add('active');
            searchInput.focus();
        });

        closeSearch.addEventListener('click', () => {
            searchModal.classList.remove('active');
            searchInput.value = '';
            searchResults.innerHTML = '';
        });

        window.addEventListener('click', (e) => {
            const historyModal = document.getElementById("historyModal");
            const favoriteModal = document.getElementById("favoriteModal");
            const profileModal = document.getElementById("profileModal");
            const messageModal = document.getElementById("messageModal");

            if (e.target === searchModal || e.target === profileModal || e.target === historyModal || e.target === favoriteModal || e.target === messageModal) {
                if (searchModal) {
                    searchModal.classList.remove('active');
                    searchInput.value = '';
                    searchResults.innerHTML = '';
                }
                if(profileModal) profileModal.style.display = "none";
                if(historyModal) historyModal.style.display = "none";
                if(favoriteModal) favoriteModal.style.display = "none"; // ปิด Modal Favorite เมื่อคลิกข้างนอก
                
                if(messageModal) {
                    closeMessageModal();
                }
            }
        });

        searchInput.addEventListener('input', async function() {
            const query = this.value.trim();
            if (query.length > 0) {
                try {
                    const response = await fetch(`../config/search_ajax.php?q=${encodeURIComponent(query)}`);
                    const html = await response.text();
                    searchResults.innerHTML = html;
                } catch (error) {
                    console.error('Error fetching search results:', error);
                }
            } else {
                searchResults.innerHTML = '';
            }
        });
    }

    if (isLoggedIn && localStorage.getItem('chatModalOpen') === 'true') {
        openMessageModal(); 
    }
 });
// --- end SEARCHING Real-time & Modal Close Handler ---

// --- AJAX Chat Submit ---
 document.addEventListener("DOMContentLoaded", function() {
    const chatForm = document.getElementById('chatForm');
    if(chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            const chatInput = document.getElementById('chatInput');
            const message = chatInput.value.trim();
            if(message === '') return;

            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });

            fetch('../config/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    const chatBox = document.getElementById('chatBoxModal');
                    
                    const emptyText = chatBox.querySelector('p');
                    if(emptyText && emptyText.innerText.includes('Start a conversation')) {
                        emptyText.remove();
                    }

                    const newBubble = `
                    <div style="max-width: 75%; padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; font-size: 15px; line-height: 1.4; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,0.1); align-self: flex-end; background-color: #E5989B; color: white; border-bottom-right-radius: 5px;">
                        ${message.replace(/\n/g, '<br>')}
                        <span style="font-size: 11px; opacity: 0.7; margin-top: 5px; display: block; text-align:right;">${timeString}</span>
                    </div>`;
                    
                    chatBox.insertAdjacentHTML('beforeend', newBubble);
                    
                    chatInput.value = '';
                    chatBox.scrollTop = chatBox.scrollHeight;
                } else {
                    Swal.fire('Error', 'Failed to send message: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Server connection problem.', 'error');
            });
        });
    }
 });
// end open & close Message

// Function to cancel booking via AJAX
 function cancelBooking(rentId) {
    Swal.fire({
        title: 'Cancel Booking?',
        text: "Are you sure you want to cancel this reservation?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#9B6E60',
        confirmButtonText: 'Yes, cancel it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../config/cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'rent_id=' + encodeURIComponent(rentId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: 'Your booking has been cancelled.',
                        confirmButtonColor: '#576d5c'
                    }).then(() => {
                        location.reload(); // Reload page to update history
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to connect to the server.', 'error');
            });
        }
    });
 }
// end Function to cancel booking via AJAX

</script>