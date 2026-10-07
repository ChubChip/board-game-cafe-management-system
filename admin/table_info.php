<?php
include('../config/connect.php');

// 1. Fetch all table information
$sql = "SELECT ti.*, tt.type_name, tt.min_customer, tt.max_customer 
        FROM table_info ti 
        LEFT JOIN type_table tt ON ti.type_table = tt.type_table 
        ORDER BY ti.table_id ASC";
$result = $conn->query($sql);
$tables = [];

while($row = $result->fetch_assoc()) {
    $tables[$row['table_id']] = [
        'table_id' => $row['table_id'],
        'name' => $row['table_name'],
        'name_th' => $row['table_name_th'],
        'type_table' => $row['type_table'], // Store table size ID for default dropdown selection
        'capacity' => $row['min_customer'] . ' - ' . $row['max_customer'] . ' People'
    ];
}

// 2. Fetch all table types to create the dropdown and for JavaScript use
$sql_types = "SELECT * FROM type_table";
$res_types = $conn->query($sql_types);
$table_types = [];
$type_options_html = "";

while($row = $res_types->fetch_assoc()) {
    $capacity = $row['min_customer'] . ' - ' . $row['max_customer'] . ' People';
    $table_types[$row['type_table']] = [
        'name' => $row['type_name'],
        'capacity' => $capacity
    ];
    // Create <option> tags for the <select> element
    $type_options_html .= "<option value='".$row['type_table']."'>".$row['type_name']."</option>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Table Table info</title>
    <link rel="icon" type="image/png" href="../img/web/table-talk.png">

    <link rel="stylesheet" href="../style/table.css">
    <link rel="stylesheet" href="../style/back.css">
</head>

<body>
<div class="container">
    <div class="header">
        <button onclick="window.location.href='index.php'" class="back-btn">← Back</button>
        <h1>Table Selection</h1>
    </div>
    
    <div class="table-container">
        <a class="table table1" onclick="showModal(8)"><?php echo $tables[8]['name']; ?></a>
        <div class="space space1">2</div>

        <a class="table table2" onclick="showModal(9)"><?php echo $tables[9]['name']; ?></a>
        <div class="space space2">4</div>

        <a class="table table3" onclick="showModal(1)"><?php echo $tables[1]['name']; ?></a>
        <div class="space space3">6</div>

        <a class="table table4" onclick="showModal(2)"><?php echo $tables[2]['name']; ?></a>
        <div class="space space4">8</div>
        <div class="space space5">9</div>

        <a class="table table5" onclick="showModal(3)"><?php echo $tables[3]['name']; ?></a>
        <a class="table table6" onclick="showModal(4)"><?php echo $tables[4]['name']; ?></a>
        <div class="space space6">12</div>
        <div class="space space7">13</div>

        <a class="table table7" onclick="showModal(5)"><?php echo $tables[5]['name']; ?></a>
        <a class="table table8" onclick="showModal(6)"><?php echo $tables[6]['name']; ?></a>
        <div class="space space8">16</div>

        <a class="table table9" onclick="showModal(10)"><?php echo $tables[10]['name']; ?></a>
        <div class="space space9">18</div>
        <div class="space space10">19</div>
        <div class="space space11">24</div>

        <div class="counter">Counter</div>
        <a class="boardgame" href="boardgame_info.php">Board Game</a>
    </div>
</div>

<div id="tableModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2 class="text-center">Edit Table Information</h2>
        <hr style="margin-bottom: 20px;">
        
        <form action="../config/update_table.php" method="POST">
            <input type="hidden" name="table_id" id="modalTableId">

            <div class="form-group">
                <label>Table Name (English):</label>
                <input type="text" name="table_name" id="modalTableName" required>
            </div>

            <div class="form-group">
                <label>Table Name (Thai):</label>
                <input type="text" name="table_name_th" id="modalTableThName">
            </div>

            <div class="form-group">
                <label>Table Size:</label>
                <select name="type_table" id="modalTableSize" onchange="updateCapacity()">
                    <?php echo $type_options_html; ?>
                </select>
            </div>

            <div class="form-group">
                <p><strong>Capacity:</strong> <span id="modalTableCapacity" style="color: #d9534f; font-weight:bold;"></span></p>
            </div>
            
            <button type="submit" class="save-btn">Save</button>
        </form>
    </div>
</div>

<script>
    const tableData = <?php echo json_encode($tables); ?>;
    const typeData = <?php echo json_encode($table_types); ?>;

    function showModal(tableId) {
        const modal = document.getElementById('tableModal');
        const data = tableData[tableId];

        if(data) {
            document.getElementById('modalTableId').value = data.table_id;
            document.getElementById('modalTableName').value = data.name;
            document.getElementById('modalTableThName').value = data.name_th;
            
            document.getElementById('modalTableSize').value = data.type_table;
            
            document.getElementById('modalTableCapacity').innerText = data.capacity;
            
            modal.style.display = 'block';
        }
    }

    function updateCapacity() {
        const selectedType = document.getElementById('modalTableSize').value;
        
        if(typeData[selectedType]) {
            document.getElementById('modalTableCapacity').innerText = typeData[selectedType].capacity;
        }
    }

    function closeModal() {
        document.getElementById('tableModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('tableModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>