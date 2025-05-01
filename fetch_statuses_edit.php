<?php 
include("connection.php");

if (isset($_POST['department_id'])) {
    $department_id = filter_var($_POST['department_id'], FILTER_VALIDATE_INT);

    if (!$department_id) {
        echo "<option value=''>Invalid Department</option>";
        exit();
    }

    // Fetch statuses for the selected department
    $status_query = $conn->prepare("SELECT status_id, status_name FROM status WHERE department_id = ?");
    $status_query->bind_param("i", $department_id);
    $status_query->execute();
    $status_result = $status_query->get_result();

    // Check if any statuses were found
    if ($status_result->num_rows > 0) {
        // Generate the options for the status dropdown
        $options = "<option value=''>Select Status</option>";
        while ($status = $status_result->fetch_assoc()) {
            $options .= "<option value='" . htmlspecialchars($status['status_id'], ENT_QUOTES, 'UTF-8') . "'>" . 
                        htmlspecialchars($status['status_name'], ENT_QUOTES, 'UTF-8') . 
                        "</option>";
        }
        echo $options;
    } else {
        echo "<option value=''>No statuses available</option>";
    }

    $status_query->close();
} else {
    echo "<option value=''>Department not provided</option>";
}
?>
