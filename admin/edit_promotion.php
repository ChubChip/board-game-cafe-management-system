<?php
session_start();
require_once '../config/connect.php';

// Check if a promotion ID has been sent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Promotion ID to edit not found'); window.location.href='add_promotion.php';</script>";
    exit();
}

$promo_id = intval($_GET['id']);

// Fetch existing promotion data to display
$sql = "SELECT * FROM promotions WHERE promo_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $promo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Promotion data not found in the system'); window.location.href='add_promotion.php';</script>";
    exit();
}

$promo = $result->fetch_assoc();

// Converting Database time to fit <input type="datetime-local"> 
// Must convert Format to YYYY-MM-DDThh:mm (with a 'T' separator)
$start_date_form = date('Y-m-d\TH:i', strtotime($promo['start_date']));
$end_date_form = date('Y-m-d\TH:i', strtotime($promo['end_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Edit promotion</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/table.css">
    <link rel="stylesheet" href="../style/back.css">
    <style>
        /* Using the same CSS as add_promotion page */
        :root {
            --brown-dark: #5D4038; --brown-light: #9B6E60; --cream: #F5EBE1;
            --green: #576d5c; --green-hover: #4a5c4b; --white: #FFFFFF;
        }
        body { font-family: 'Kanit', sans-serif; background-color: var(--cream); }
        .container-box { background-color: var(--white); padding: 30px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: var(--brown-dark); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid var(--brown-light); border-radius: 10px; box-sizing: border-box; font-family: 'Kanit', sans-serif; }
        .submit-btn { width: 100%; padding: 12px; background-color: #ffc107; color: black; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .submit-btn:hover { background-color: #e0a800; }
        .img-preview { width: 100%; max-height: 200px; object-fit: contain; border-radius: 10px; margin-bottom: 10px; border: 1px dashed #ccc; padding: 5px; }
    </style>
</head>
<body>

<div style="display: flex; flex-direction: column; align-items: center; padding: 20px;">
    <div style="width: 100%; max-width: 600px; display: flex; justify-content: space-between; align-items: center;">
        <button onclick="window.location.href='add_promotion.php'" class="back-btn">← Back</button>
        <h1 style="color: var(--brown-dark);">✏️ Edit Promotion</h1>
        <div></div>
    </div>

    <div class="container-box">
        <form action="../config/update_promotion.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="promo_id" value="<?php echo $promo['promo_id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($promo['image_url']); ?>">

            <div class="form-group">
                <label>Promotion Title *</label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($promo['title']); ?>">
            </div>

            <div class="form-group">
                <label>Details / Conditions</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($promo['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <img src="<?php echo htmlspecialchars($promo['image_url']); ?>" class="img-preview">
                <label>Upload New Image (Leave blank to keep current image)</label>
                <input type="file" name="promo_image" accept="image/*">
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Button Text</label>
                    <input type="text" name="button_text" value="<?php echo htmlspecialchars($promo['button_text']); ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Action Link (URL)</label>
                    <input type="text" name="action_link" value="<?php echo htmlspecialchars($promo['action_link']); ?>">
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Display Start Time *</label>
                    <input type="datetime-local" name="start_date" required value="<?php echo $start_date_form; ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Display End Time *</label>
                    <input type="datetime-local" name="end_date" required value="<?php echo $end_date_form; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?php echo ($promo['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($promo['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo ($promo['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>