<?php
session_start();
include('../config/connect.php'); 

// ==========================================
// 1. จัดการคำสั่ง เพิ่ม / ลบ / แก้ไข (CRUD)
// ==========================================

// --- คำสั่งลบข้อมูล (Delete) ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    $sql_img = "SELECT upload FROM boardgame WHERE boardgame_id = '$del_id'";
    $res_img = mysqli_query($conn, $sql_img);
    if ($row_img = mysqli_fetch_assoc($res_img)) {
        if (!empty($row_img['upload']) && file_exists("../img/boardgame/" . $row_img['upload'])) {
            unlink("../img/boardgame/" . $row_img['upload']); 
        }
    }

    mysqli_query($conn, "DELETE FROM type_boardgame WHERE boardgame_id = '$del_id'");
    $sql_del = "DELETE FROM boardgame WHERE boardgame_id = '$del_id'";
    
    if (mysqli_query($conn, $sql_del)) {
        $_SESSION['success'] = "Board game $del_id deleted!";
    } else {
        $_SESSION['error'] = "Failed to delete data: " . mysqli_error($conn);
    }
    header("Location: boardgame_info.php");
    exit();
}

// --- คำสั่งเพิ่ม/แก้ไขข้อมูล (Add / Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bg_name = mysqli_real_escape_string($conn, $_POST['boardgame_name']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // รับหมวดหมู่เป็น Array จาก Checkbox
    $category_ids = isset($_POST['category_id']) ? $_POST['category_id'] : [];
    
    $min_p = !empty($_POST['min_players']) ? (int)$_POST['min_players'] : "NULL";
    $max_p = !empty($_POST['max_players']) ? (int)$_POST['max_players'] : "NULL";
    $diff = !empty($_POST['difficulty']) ? (float)$_POST['difficulty'] : "NULL";
    
    $equipment = !empty($_POST['equipment_list']) ? "'" . mysqli_real_escape_string($conn, $_POST['equipment_list']) . "'" : "NULL";
    $defect = !empty($_POST['defect']) ? "'" . mysqli_real_escape_string($conn, $_POST['defect']) . "'" : "NULL";
    $genre = !empty($_POST['genre']) ? "'" . mysqli_real_escape_string($conn, $_POST['genre']) . "'" : "NULL";

    $upload_file = ""; 
    $update_image_sql = ""; 
    $target_dir = "../img/boardgame/"; 
    
    if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); } 
        $file_name = time() . '_' . basename($_FILES['upload']['name']); 
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['upload']['tmp_name'], $target_file)) {
            $upload_file = $file_name;
            $update_image_sql = ", upload = '$file_name'";
        }
    }

    if ($_POST['action'] == 'add') {
        $primary_cat = !empty($category_ids) ? mysqli_real_escape_string($conn, $category_ids[0]) : 'U';

        $sql_last_id = "SELECT boardgame_id FROM boardgame 
                        WHERE boardgame_id LIKE '$primary_cat-%' 
                        ORDER BY CAST(SUBSTRING_INDEX(boardgame_id, '-', -1) AS UNSIGNED) DESC LIMIT 1";
        $res_last = mysqli_query($conn, $sql_last_id);
        if (mysqli_num_rows($res_last) > 0) {
            $last_id = mysqli_fetch_assoc($res_last)['boardgame_id']; 
            $next_num = (int) substr($last_id, strpos($last_id, '-') + 1) + 1;
        } else {
            $next_num = 1;
        }
        $new_bg_id = $primary_cat . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

        $upload_val = $upload_file ? "'$upload_file'" : "NULL";
        
        // *แก้ไขแล้ว: เอา type_boardgame ออกจากการ Insert เข้าตารางหลัก
        $sql_add = "INSERT INTO boardgame (boardgame_id, boardgame_name, status, min_players, max_players, equipment_list, difficulty, defect, genre, upload) 
                    VALUES ('$new_bg_id', '$bg_name', '$status', $min_p, $max_p, $equipment, $diff, $defect, $genre, $upload_val)";
                    
        if (mysqli_query($conn, $sql_add)) {
            // บันทึกหมวดหมู่ลงตารางเชื่อมแยกต่างหาก
            foreach($category_ids as $cat_id) {
                $safe_cat_id = mysqli_real_escape_string($conn, $cat_id);
                mysqli_query($conn, "INSERT INTO type_boardgame (boardgame_id, category_id) VALUES ('$new_bg_id', '$safe_cat_id')");
            }
            $_SESSION['success'] = "Board game $new_bg_id created successfully!";
        } else {
            $_SESSION['error'] = "Error occurred: " . mysqli_error($conn);
        }

    } elseif ($_POST['action'] == 'edit') {
        $bg_id = mysqli_real_escape_string($conn, $_POST['boardgame_id']);
        
        // *แก้ไขแล้ว: เอา type_boardgame ออกจากการ Update ตารางหลัก
        $sql_edit = "UPDATE boardgame 
                     SET boardgame_name = '$bg_name',
                         status = '$status',
                         min_players = $min_p,
                         max_players = $max_p,
                         difficulty = $diff,
                         equipment_list = $equipment,
                         defect = $defect,
                         genre = $genre
                         $update_image_sql
                     WHERE boardgame_id = '$bg_id'";
                     
        if (mysqli_query($conn, $sql_edit)) {
            // ลบหมวดหมู่เดิมแล้วบันทึกหมวดหมู่ใหม่ลงตารางเชื่อม
            mysqli_query($conn, "DELETE FROM type_boardgame WHERE boardgame_id = '$bg_id'");
            foreach($category_ids as $cat_id) {
                $safe_cat_id = mysqli_real_escape_string($conn, $cat_id);
                mysqli_query($conn, "INSERT INTO type_boardgame (boardgame_id, category_id) VALUES ('$bg_id', '$safe_cat_id')");
            }
            $_SESSION['success'] = "อัปเดตข้อมูลเรียบร้อยแล้ว!";
        } else {
            $_SESSION['error'] = "Error updating: " . mysqli_error($conn);
        }
    }
    header("Location: boardgame_info.php");
    exit();
}

// ==========================================
// 2. ดึงข้อมูลเพื่อมาแสดงผลหน้าเว็บ
// ==========================================

$categories = [];
$res_cat = mysqli_query($conn, "SELECT * FROM category ORDER BY category_id ASC");
if($res_cat){
    while($row = mysqli_fetch_assoc($res_cat)) { $categories[] = $row; }
}

$boardgames = [];
$sql_fetch = "
    SELECT b.*, 
           (SELECT GROUP_CONCAT(category_id) FROM type_boardgame WHERE boardgame_id = b.boardgame_id) AS category_ids
    FROM boardgame b 
    ORDER BY b.boardgame_name ASC 
";
$result = mysqli_query($conn, $sql_fetch);
if($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $boardgames[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Table-Talk Boardgame info</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style/boardgame_info.css">
    <link rel="stylesheet" href="../style/back.css">
</head>
<body>
    <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>

    <?php if (isset($_SESSION['success'])): ?>
        <script>Swal.fire({ icon: 'success', title: 'สำเร็จ', text: '<?php echo $_SESSION['success']; ?>', timer: 2000, showConfirmButton: false });</script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <script>Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: '<?php echo $_SESSION['error']; ?>' });</script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <h1 class="main-title">Table Talk</h1>

    <div class="container">
        <div class="header-row">
            <h2>Update Board Game</h2>
            <button class="btn-add" onclick="openModal('add')">+</button>
        </div>

        <div class="table-header">
            <div>ID</div>
            <div>Board Game Name</div>
            <div style="text-align: right; padding-right: 35px;">edit/delete</div>
        </div>

        <?php if (count($boardgames) > 0): ?>
            <?php foreach ($boardgames as $bg): ?>
                <div class="table-row">
                    <div style="font-weight: 600; color: var(--brown-dark);">
                        <?php echo htmlspecialchars($bg['boardgame_id']); ?>
                    </div>
                    <div>
                        <?php echo htmlspecialchars($bg['boardgame_name']); ?>
                    </div>
                    <div class="action-btns">
                        <button class="btn-edit" 
                            data-id="<?php echo htmlspecialchars($bg['boardgame_id']); ?>"
                            data-name="<?php echo htmlspecialchars($bg['boardgame_name']); ?>"
                            data-cat="<?php echo htmlspecialchars($bg['category_ids'] ?? ''); ?>" 
                            data-status="<?php echo htmlspecialchars($bg['status']); ?>"
                            data-min="<?php echo htmlspecialchars($bg['min_players']); ?>"
                            data-max="<?php echo htmlspecialchars($bg['max_players']); ?>"
                            data-diff="<?php echo htmlspecialchars($bg['difficulty']); ?>"
                            data-equip="<?php /*echo htmlspecialchars($bg['equipment_lists']); */?>"
                            data-defect="<?php echo htmlspecialchars($bg['defect']); ?>"
                            data-genre="<?php echo htmlspecialchars($bg['genre']); ?>"
                            onclick="openModal('edit', this)">edit</button>
                        <button class="btn-delete" onclick="confirmDelete('<?php echo $bg['boardgame_id']; ?>')">delete</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="table-row" style="text-align: center; display: block;">No board games found.</div>
        <?php endif; ?>
    </div>

    <div id="gameModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Add Board Game</h3>
            
            <form method="POST" action="boardgame_info.php" enctype="multipart/form-data" onsubmit="return validateTags()">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="boardgame_id" id="bg_id" value="">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="group-title">Board Game Name (Required)</label>
                        <input type="text" name="boardgame_name" id="bg_name" required placeholder="Enter board game name...">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="group-title">Status (Required)</label>
                        <select name="status" id="bg_status" required>
                            <option value="Available">Available</option>
                            <option value="Rented">Rented</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="group-title">Categories (Select at least 1 category)</label>
                    <div class="checkbox-grid">
                        <?php foreach($categories as $cat): ?>
                            <div class="checkbox-tag">
                                <input type="checkbox" name="category_id[]" id="cat_<?php echo htmlspecialchars($cat['category_id']); ?>" value="<?php echo htmlspecialchars($cat['category_id']); ?>">
                                <label for="cat_<?php echo htmlspecialchars($cat['category_id']); ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small id="tag-error" style="color: red; display: none;">* Please select at least 1 category</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="group-title">Minimum Players</label>
                        <input type="number" name="min_players" id="bg_min" min="1" placeholder="เช่น 2">
                    </div>
                    <div class="form-group">
                        <label class="group-title">Maximum Players</label>
                        <input type="number" name="max_players" id="bg_max" min="1" placeholder="เช่น 4">
                    </div>
                    <div class="form-group">
                        <label class="group-title">Difficulty</label>
                        <input type="number" step="0.1" name="difficulty" id="bg_diff" min="1.0" max="5.0" placeholder="1.0 - 5.0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="group-title">Game Info & Genre</label>
                    <textarea name="genre" id="bg_genre" rows="2" placeholder="รายละเอียดของเกม..."></textarea>
                </div>

                <div class="form-group">
                    <label class="group-title">Equipment</label>
                    <textarea name="equipment_list" id="bg_equip" rows="2" placeholder="เช่น กระดาน 1 แผ่น, ลูกเต๋า 2 ลูก..."></textarea>
                </div>

                <div class="form-group">
                    <label class="group-title">Defect</label>
                    <textarea name="defect" id="bg_defect" rows="2" placeholder="เช่น กล่องขาดมุม, ไพ่หาย 1 ใบ..."></textarea>
                </div>

                <div class="form-group">
                    <label class="group-title">Upload Image</label>
                    <input type="file" name="upload" id="bg_upload" accept="image/png, image/jpeg, image/webp" style="border: none; padding: 0;">
                    <small style="color:#666; font-size:12px; display:block; margin-top:5px;">* Leave blank to keep current photo.</small>
                </div>
                
                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function validateTags() {
            const checkboxes = document.querySelectorAll('input[name="category_id[]"]:checked');
            if(checkboxes.length === 0) {
                document.getElementById('tag-error').style.display = 'block';
                return false;
            }
            document.getElementById('tag-error').style.display = 'none';
            return true;
        }

        function openModal(type, el = null) {
            const modal = document.getElementById('gameModal');
            const title = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            
            const checkboxes = document.querySelectorAll('input[name="category_id[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('tag-error').style.display = 'none';

            if (type === 'add') {
                title.innerText = 'Add Board Game';
                formAction.value = 'add';
                document.getElementById('bg_id').value = '';
                document.getElementById('bg_name').value = '';
                document.getElementById('bg_status').value = 'Available'; 
                
                document.getElementById('bg_min').value = '';
                document.getElementById('bg_max').value = '';
                document.getElementById('bg_diff').value = '';
                document.getElementById('bg_equip').value = '';
                document.getElementById('bg_defect').value = '';
                document.getElementById('bg_genre').value = '';
                document.getElementById('bg_upload').value = ''; 

            } else if (type === 'edit') {
                title.innerText = 'Edit Board Game (' + el.dataset.id + ')';
                formAction.value = 'edit';
                document.getElementById('bg_id').value = el.dataset.id;
                document.getElementById('bg_name').value = el.dataset.name;
                document.getElementById('bg_status').value = el.dataset.status;
                
                if(el.dataset.cat) {
                    const selectedCats = el.dataset.cat.split(',');
                    checkboxes.forEach(cb => {
                        if (selectedCats.includes(cb.value)) {
                            cb.checked = true;
                        }
                    });
                }
                
                document.getElementById('bg_min').value = el.dataset.min;
                document.getElementById('bg_max').value = el.dataset.max;
                document.getElementById('bg_diff').value = el.dataset.diff;
                document.getElementById('bg_equip').value = el.dataset.equip;
                document.getElementById('bg_defect').value = el.dataset.defect;
                document.getElementById('bg_genre').value = el.dataset.genre;
                document.getElementById('bg_upload').value = ''; 
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('gameModal').style.display = 'none';
        }

        function confirmDelete(id) {
            Swal.fire({
                title: `Confirm delete ${id}?`,
                text: "Once deleted, this cannot be undone (Cover image will also be removed).",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E5989B',
                cancelButtonColor: '#999',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `boardgame_info.php?delete_id=${id}`;
                }
            });
        }
    </script>
</body>
</html>