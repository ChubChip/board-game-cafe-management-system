<?php
include('../config/connect.php'); 

// 1. Fetch customer data
$sql = "SELECT c.*, t.type_name 
        FROM customer c 
        LEFT JOIN type_user t ON c.type_user = t.type_user 
        ORDER BY c.customer_id ASC";
$result = mysqli_query($conn, $sql);

// (เอาโค้ดดึงข้อมูล type_user ออกไปแล้วเพราะไม่ได้ใช้)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Manage customer</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/manage_customer.css">
    <link rel="stylesheet" href="../style/back.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<div class="container-box">
    <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>
    <h2>Customer List</h2>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Manage</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td>
                    <?php 
                    $base_path = "../img/customer_img/";
                    $full_path = $base_path . $row['upload'];

                    if (!empty($row['upload']) && file_exists($full_path)) {
                        echo '<img src="' . $full_path . '" class="user-img">';
                    } else {
                        echo '<div class="user-img" style="background:#ccc; display:flex; align-items:center; justify-content:center; font-size:10px; color:#666;">No Pic</div>';
                    }
                    ?>
                </td>
                <td><?php echo $row['customer_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo !empty($row['phone']) ? htmlspecialchars($row['phone']) : '-'; ?></td> 
                <td><?php echo $row['type_name']; ?></td>
                <td class="action-cell">
                    <button class="btn-edit" style="cursor: pointer; border: none;" 
                        onclick="openModal(this)"
                        data-id="<?php echo $row['customer_id']; ?>"
                        data-username="<?php echo htmlspecialchars($row['username']); ?>"
                        data-email="<?php echo htmlspecialchars($row['email']); ?>"
                        data-phone="<?php echo htmlspecialchars($row['phone']); ?>"
                        data-gender="<?php echo htmlspecialchars($row['gender'] ?? ''); ?>"> edit
                    </button>
                    
                    <span class="divider">|</span>
                    
                    <a href="#" 
                    class="btn-delete" 
                    onclick="confirmDelete(event, '../config/delete_customer.php?id=<?php echo $row['customer_id']; ?>')">delete</a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2>Edit Customer</h2>
        
        <form action="../config/update_customer.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="customer_id" id="edit_id">
            
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" id="edit_username" required>
            </div>
            
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="input-group">
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone">
            </div>

            <div class="input-group">
                <label>Gender</label>
                <select name="gender" id="edit_gender">
                    <option value="">-- Select Gender --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Prefer not to say">Prefer not to say</option>
                </select>
            </div>

            <div class="input-group">
                <label>New Photo (Optional)</label>
                <input type="file" name="upload" accept="image/*">
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("editModal");

    function openModal(btn) {
        const id = btn.getAttribute("data-id");
        const username = btn.getAttribute("data-username");
        const email = btn.getAttribute("data-email");
        const phone = btn.getAttribute("data-phone");
        const gender = btn.getAttribute("data-gender");

        document.getElementById("edit_id").value = id;
        document.getElementById("edit_username").value = username;
        document.getElementById("edit_email").value = email;
        
        document.getElementById("edit_phone").value = (phone !== '-' && phone !== 'null' && phone !== null) ? phone : '';
        
        if (gender) {
            document.getElementById("edit_gender").value = gender;
        } else {
            document.getElementById("edit_gender").value = "";
        }

        modal.style.display = "flex";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    // DELETE SWEETALERT
    function confirmDelete(event, url) {
        event.preventDefault();

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to delete this customer? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5D4038',
            cancelButtonColor: '#E5989B',
            background: '#F5EBE1',
            color: '#5D4038',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

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