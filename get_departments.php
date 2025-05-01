<?php
include("connection.php");

// Fetch all departments
$stmt = $conn->prepare("SELECT department_id, department_name FROM department");
$stmt->execute();
$result = $stmt->get_result();

// Prepare an array to hold the department data
$departments = array();

// Fetch data into the array
while ($row = $result->fetch_assoc()) {
    $departments[] = $row; // Add each department to the array
}

// Return JSON encoded data
header('Content-Type: application/json');
echo json_encode($departments);

$stmt->close();
$conn->close();
?>
