<?php
session_start();
require_once '../config/connect.php';

// 1. Security Check: Only Owner (A01) can access this page
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A01') {
    $_SESSION['error'] = "Access Denied! Only Owner (A01) can manage roles.";
    header("Location: index.php");
    exit();
}

// 2. Determine active tab (admins or customers) - default is 'admins'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'admins';

// 3. Fetch data based on the active tab
$users = [];
if ($active_tab === 'customers') {
    $sql = "SELECT customer_id AS id, username, email, type_user FROM customer ORDER BY customer_id ASC";
} else {
    $sql = "SELECT admin_id AS id, username, email, type_user FROM admins ORDER BY admin_id ASC";
}

$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Manage role</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    
    <link rel="stylesheet" href="../style/admin.css">
    <link rel="stylesheet" href="../style/manage_role.css">
    <link rel="stylesheet" href="../style/back.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="dashboard-container">
    <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>
    
    <div class="role-container">
        <div class="role-header">
            <h2>Role Management</h2>
            <p>Select a group to view and update user roles.</p>
        </div>

        <div class="tab-container">
            <a href="manage_role.php?tab=admins" class="tab-btn <?php echo ($active_tab === 'admins') ? 'active' : ''; ?>">
                Admin Roles
            </a>
            <a href="manage_role.php?tab=customers" class="tab-btn <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                Customer Roles
            </a>
        </div>

        <div class="table-wrapper">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Change Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($users) > 0): ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($user['type_user']); ?></span></td>
                            <td>
                                <form action="../config/process_role.php" method="POST" class="role-form">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <input type="hidden" name="source_table" value="<?php echo $active_tab; ?>">
                                    
                                    <select name="new_role" class="role-select">
                                        <?php if($active_tab === 'admins'): ?>
                                            <option value="A01" <?php if($user['type_user'] == 'A01') echo 'selected'; ?>>Owner</option>
                                            <option value="A02" <?php if($user['type_user'] == 'A02') echo 'selected'; ?>>Admin</option>
                                            <option value="A03" <?php if($user['type_user'] == 'A03') echo 'selected'; ?>>Game master (GM)</option>
                                            <option value="A04" <?php if($user['type_user'] == 'A04') echo 'selected'; ?>>Barista</option>
                                            <option value="A05" <?php if($user['type_user'] == 'A05') echo 'selected'; ?>>Cashier</option>
                                        <?php else: ?>
                                            <option value="C02" <?php if($user['type_user'] == 'C02') echo 'selected'; ?>>Member (C02)</option>
                                        <?php endif; ?>
                                    </select>
                                    
                                    <button type="submit" name="update_btn" class="btn-save-role">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No data found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonColor: '#5D4038',
            background: '#F5EBE1',
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
            confirmButtonColor: '#576d5c',
            background: '#F5EBE1',
            color: '#5D4038'
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

</body>
</html>