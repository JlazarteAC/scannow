<?php
include("connection.php");

if (isset($_GET['department_id'])) {
    $department_id = intval($_GET['department_id']);
    
    // Fetch statuses based on department_id
    $stmt = $conn->prepare("SELECT status_id, status_name FROM status WHERE department_id = ?");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate options for the status dropdown
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['status_id'] . '">' . htmlspecialchars($row['status_name']) . '</option>';
    }

    $stmt->close();
}
$conn->close();
?>
