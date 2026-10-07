<?php
session_start();
include_once('../config/connect.php');

// Permissions Check (Must be an admin)
if (!isset($_SESSION['user_id']) || strpos($_SESSION['user_type'], 'A') !== 0) {
    header("Location: ../main/index.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$selected_customer = isset($_GET['cid']) ? $_GET['cid'] : null;

// Handle Admin Reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_msg']) && $selected_customer) {
    $msg = mysqli_real_escape_string($conn, $_POST['reply_msg']);
    if (!empty($msg)) {
        $insert_sql = "INSERT INTO messages (customer_id, sender_type, message_text, status) 
                       VALUES ('$selected_customer', 'admin', '$msg', 'unread')";
        mysqli_query($conn, $insert_sql);
        // Refresh page to show new message
        header("Location: message.php?cid=" . $selected_customer);
        exit();
    }
}

// Update message status to 'read' when admin views the conversation
if ($selected_customer) {
    mysqli_query($conn, "UPDATE messages SET status='read' WHERE customer_id='$selected_customer' AND sender_type='customer'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Message</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/message.css">
</head>
<body>

<?php include_once('../include/topbar.php'); ?> 
<div class="chat-container">
    <div class="sidebar">
        <div class="sidebar-header">Customer Messages</div>
        <ul class="user-list">
            <?php
            // Fetch customers with messages, most recent first
            $sql_users = "SELECT c.customer_id, c.username, c.upload, MAX(m.created_at) as last_msg 
                          FROM messages m 
                          JOIN customer c ON m.customer_id = c.customer_id 
                          GROUP BY c.customer_id 
                          ORDER BY last_msg DESC";
            $res_users = mysqli_query($conn, $sql_users);

            $current_customer_name = "Unknown User";
            $current_customer_img = "";

            while($u = mysqli_fetch_assoc($res_users)) {
                $activeClass = ($selected_customer == $u['customer_id']) ? 'active' : '';
                $img = !empty($u['upload']) ? "../img/customer_img/".$u['upload'] : "img/default-avatar.png"; 
                
                // Store details for the active conversation header
                if($selected_customer == $u['customer_id']) {
                    $current_customer_name = $u['username'];
                    $current_customer_img = $img;
                }
                ?>
                <a href="message.php?cid=<?php echo $u['customer_id']; ?>" class="user-item <?php echo $activeClass; ?>">
                    <img src="<?php echo $img; ?>" class="user-avatar" alt="Avatar">
                    <div>
                        <div style="font-weight: bold;"><?php echo htmlspecialchars($u['username']); ?></div>
                    </div>
                </a>
            <?php } ?>
        </ul>
    </div>

    <div class="chat-area">
        <?php if ($selected_customer): ?>
            <div class="chat-header">
                <img src="<?php echo $current_customer_img; ?>" class="user-avatar" style="width:35px;height:35px;">
                Chatting with: <?php echo htmlspecialchars($current_customer_name); ?>
            </div>
            
            <div class="chat-messages" id="chatBox">
                <?php
                // Fetch all messages for this customer
                $sql_msgs = "SELECT * FROM messages WHERE customer_id = '$selected_customer' ORDER BY created_at ASC";
                $res_msgs = mysqli_query($conn, $sql_msgs);

                if (mysqli_num_rows($res_msgs) > 0) {
                    while ($m = mysqli_fetch_assoc($res_msgs)) {
                        $is_admin = ($m['sender_type'] == 'admin');
                        $bubble_class = $is_admin ? 'msg-admin' : 'msg-customer';
                        $time = date('H:i', strtotime($m['created_at']));
                        ?>
                        <div class="msg-bubble <?php echo $bubble_class; ?>">
                            <?php echo nl2br(htmlspecialchars($m['message_text'])); ?>
                            <span class="msg-time"><?php echo $time; ?></span>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p style='text-align:center; color:#ccc; margin-top:20px;'>No messages yet.</p>";
                }
                ?>
            </div>

            <form class="chat-input-area" method="POST" action="message.php?cid=<?php echo $selected_customer; ?>">
                <input type="text" name="reply_msg" class="chat-input" placeholder="Type a reply..." required autocomplete="off" autofocus>
                <button type="submit" class="chat-send-btn">Send</button>
            </form>

            <script>
                var chatBox = document.getElementById("chatBox");
                chatBox.scrollTop = chatBox.scrollHeight;
            </script>

        <?php else: ?>
            <div class="no-chat-selected">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#eaddd3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <p>Select a customer from the left to start chatting</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>