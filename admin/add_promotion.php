<?php
session_start();
require_once '../config/connect.php'; // Call database connection file

// Fetch all promotion data to display in the table (sorted from newest to oldest)
$sql = "SELECT * FROM promotions ORDER BY created_at DESC";
$result = $conn->query($sql);
$promotions = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Table-Talk Add promotion</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/table.css">
    <link rel="stylesheet" href="../style/back.css">
    <link rel="stylesheet" href="../style/promotions.css">
</head>
<body>

<div class="main-wrapper">
    <div class="header">
        <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>
        <h1 style="color: var(--brown-dark);">Manage Promotions (Splash Page)</h1>
        <div></div> </div>

    <div class="box-container">
        <h2 style="color: var(--brown-dark); border-bottom: 2px solid var(--brown-light); padding-bottom: 10px;">➕ Add New Promotion</h2>
        <form action="../config/insert_promotion.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Promotion Title *</label>
                <input type="text" name="title" required placeholder="e.g., Grand Opening, 50% Off">
            </div>
            <div class="form-group">
                <label>Description / Conditions</label>
                <textarea name="description" rows="3" placeholder="Enter promotion details..."></textarea>
            </div>
            <div class="form-group">
                <label>Upload Promotion Image *</label>
                <input type="file" name="promo_image" accept="image/*" required>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Button Text</label>
                    <input type="text" name="button_text" value="View Details">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Action Link (URL)</label>
                    <input type="text" name="action_link" value="#">
                </div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Display Start Time *</label>
                    <input type="datetime-local" name="start_date" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Display End Time *</label>
                    <input type="datetime-local" name="end_date" required>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <button type="submit" class="submit-btn">Save Promotion</button>
        </form>
    </div>

    <div class="box-container">
        <h2 style="color: var(--brown-dark); border-bottom: 2px solid var(--brown-light); padding-bottom: 10px;">📋 Created Promotions List</h2>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($promotions)): ?>
                        <tr><td colspan="6" style="text-align:center;">No promotion data found</td></tr>
                    <?php else: ?>
                        <?php foreach($promotions as $promo): 
                            // Check if expired (compare end_date with current time)
                            $is_expired = (strtotime($promo['end_date']) < time());
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($promo['image_url']); ?>" class="img-thumbnail" onclick='openViewModal(<?php echo json_encode($promo, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                            </td>
                            <td><?php echo htmlspecialchars($promo['title']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($promo['start_date'])); ?></td>
                            <td style="<?php echo $is_expired ? 'color: red; font-weight: bold;' : ''; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($promo['end_date'])); ?>
                                <?php if($is_expired) echo "<br><small>(Expired)</small>"; ?>
                            </td>
                            <td>
                                <?php 
                                    if($promo['status'] == 'active') echo '<span class="status-badge status-active">Active</span>';
                                    elseif($promo['status'] == 'inactive') echo '<span class="status-badge status-inactive">Inactive</span>';
                                    else echo '<span class="status-badge status-draft">Draft</span>';
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn-view" onclick='openViewModal(<?php echo json_encode($promo, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>👁️ View</button>
                                
                                <?php if(!$is_expired): ?>
                                    <a href="edit_promotion.php?id=<?php echo $promo['promo_id']; ?>" class="btn-edit">✏️ Edit</a>
                                <?php else: ?>
                                    <button class="btn-disabled" disabled>🔒 Edit Locked</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeViewModal()">&times;</span>
        <h2 id="modalTitle" style="color: var(--brown-dark); margin-top:0;">Promotion Title</h2>
        <hr style="border: 0; border-top: 1px solid var(--cream); margin-bottom: 15px;">
        
        <img id="modalImg" src="" alt="Promo Image" class="modal-img">
        
        <div class="modal-details">
            <p><strong>Description:</strong> <span id="modalDesc"></span></p>
            <p><strong>Button Link (<span id="modalBtnText"></span>):</strong> <a href="#" id="modalLink" target="_blank" style="color: #17a2b8; word-break: break-all;"></a></p>
            <p><strong>Display Period:</strong> <span id="modalStartDate"></span> <b>to</b> <span id="modalEndDate"></span></p>
            <p><strong>Total Views:</strong> <span id="modalViews">0</span> times | <strong>Total Clicks:</strong> <span id="modalClicks">0</span> times</p>
        </div>
    </div>
</div>

<script>
    // Function to open Modal and populate with promotion data
    function openViewModal(promoData) {
        document.getElementById('modalTitle').innerText = promoData.title;
        document.getElementById('modalImg').src = promoData.image_url;
        document.getElementById('modalDesc').innerText = promoData.description ? promoData.description : '- No additional details -';
        
        document.getElementById('modalBtnText').innerText = promoData.button_text;
        document.getElementById('modalLink').href = promoData.action_link;
        document.getElementById('modalLink').innerText = promoData.action_link;
        
        document.getElementById('modalStartDate').innerText = promoData.start_date;
        document.getElementById('modalEndDate').innerText = promoData.end_date;
        
        document.getElementById('modalViews').innerText = promoData.view_count;
        document.getElementById('modalClicks').innerText = promoData.click_count;

        // Display Modal using Flex for center alignment
        document.getElementById('viewModal').style.display = 'flex';
    }

    // Function to close Modal
    function closeViewModal() {
        document.getElementById('viewModal').style.display = 'none';
    }

    // Close when clicking on the dark background overlay
    window.onclick = function(event) {
        var modal = document.getElementById('viewModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>