<?php
session_start();
// Call the database connection file
require_once 'connect.php';

// Check if data was sent from the form via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Receive values from the form (if no value exists, set as an empty string)
    $table_id = isset($_POST['table_id']) ? $_POST['table_id'] : '';
    $table_name = isset($_POST['table_name']) ? $_POST['table_name'] : '';
    $table_name_th = isset($_POST['table_name_th']) ? $_POST['table_name_th'] : '';
    $type_table = isset($_POST['type_table']) ? $_POST['type_table'] : '';

    // Check if there is a table_id to update (prevent errors)
    if (!empty($table_id)) {
        
        // Create SQL command for updating data
        // Use Prepared Statements to prevent SQL Injection
        $sql = "UPDATE table_info SET 
                table_name = ?, 
                table_name_th = ?, 
                type_table = ? 
                WHERE table_id = ?";
                
        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Bind variables to the SQL command ("sssi" means String, String, String, Integer)
            $stmt->bind_param("sssi", $table_name, $table_name_th, $type_table, $table_id);
            
            // Execute the command
            if ($stmt->execute()) {
                // Update successful - Create a notification Session (if desired) and redirect back
                $_SESSION['success'] = "Table information updated successfully!";
                
                // Redirect back to table_info.php (ensure the path matches your project folder)
                header("Location: ../admin/table_info.php"); 
                exit();
            } else {
                // In case of execution error
                echo "An error occurred during update: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // In case the statement could not be prepared (often due to wrong field or table names)
            echo "SQL Prepare failed: " . $conn->error;
        }
        
    } else {
         echo "Table ID for update not found";
    }
} else {
    // If this page was not accessed via a Submit button, redirect back
    header("Location: ../admin/table_info.php");
    exit();
}

$conn->close();
?>