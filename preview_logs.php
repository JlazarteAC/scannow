<?php
include 'connection.php';

$dateFrom = $_POST['date_from'];
$dateTo = $_POST['date_to'];
$departmentId = $_POST['department_id'];
$statusId = $_POST['status_id'];
$fullName = strtolower($_POST['full_name']);

// Base query to get log entries from both log_entry and log_exit
$query = "
    SELECT log_entry.school_id, 
           CONCAT(user.first_name, ' ', IFNULL(user.middle_name, ''), ' ', user.last_name) AS full_name, 
           log_entry.timestamp AS timestamp, 
           department.department_name AS department,
           status.status_name AS status,
           CASE 
                WHEN log_entry.status = 'Access Granted' THEN 'Access Granted - Entry Gate'
                ELSE 'Access Denied - Entry Gate'
            END AS action
    FROM log_entry
    JOIN user ON log_entry.school_id = user.school_id
    JOIN status ON user.status_id = status.status_id
    JOIN department ON status.department_id = department.department_id
    WHERE log_entry.timestamp BETWEEN '$dateFrom' AND '$dateTo'";

// Add additional filters for log_entry
if (!empty($departmentId)) {
    $query .= " AND department.department_id = $departmentId";
}
if (!empty($statusId)) {
    $query .= " AND status.status_id = $statusId";
}
if (!empty($fullName)) {
    $query .= " AND LOWER(CONCAT(user.first_name, ' ', IFNULL(user.middle_name, ''), ' ', user.last_name)) LIKE '%$fullName%'";
}

// Add union query to include log_exit data
$query .= " 
    UNION ALL
    SELECT log_exit.school_id, 
           CONCAT(user.first_name, ' ', IFNULL(user.middle_name, ''), ' ', user.last_name) AS full_name, 
           log_exit.timestamp AS timestamp, 
           department.department_name AS department,
           status.status_name AS status,
           CASE 
                WHEN log_exit.status = 'Access Granted' THEN 'Access Granted - Exit Gate'
                ELSE 'Access Denied - Exit Gate'
            END AS action
    FROM log_exit
    JOIN user ON log_exit.school_id = user.school_id
    JOIN status ON user.status_id = status.status_id
    JOIN department ON status.department_id = department.department_id
    WHERE log_exit.timestamp BETWEEN '$dateFrom' AND '$dateTo'";

// Add additional filters for log_exit
if (!empty($departmentId)) {
    $query .= " AND department.department_id = $departmentId";
}
if (!empty($statusId)) {
    $query .= " AND status.status_id = $statusId";
}
if (!empty($fullName)) {
    $query .= " AND LOWER(CONCAT(user.first_name, ' ', IFNULL(user.middle_name, ''), ' ', user.last_name)) LIKE '%$fullName%'";
}

$query .= " ORDER BY timestamp DESC";

// Execute query and generate HTML rows
$result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$row['school_id']}</td>
            <td>{$row['full_name']}</td>
            <td>{$row['department']}</td>
            <td>{$row['status']}</td>
            <td>{$row['timestamp']}</td>
            <td>{$row['action']}</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='6'>No records found.</td></tr>";
}
?>
