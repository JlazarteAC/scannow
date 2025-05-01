<?php
include 'connection.php';

if (isset($_GET['department_id'])) {
    $department_id = intval($_GET['department_id']);
    
    // Query to fetch statuses based on the selected department
    $status_query = "SELECT status_id, status_name FROM status WHERE department_id = $department_id";
    $status_result = $conn->query($status_query);

    echo "<option value=''></option>"; // Default empty option
    while ($status_row = $status_result->fetch_assoc()) {
        echo "<option value='" . $status_row['status_id'] . "'>" . $status_row['status_name'] . "</option>";
    }
}
?>
