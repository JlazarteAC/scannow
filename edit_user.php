<?php 
session_start();
include("connection.php");
include("activity_logger.php"); // Include the activity logger

// Fetch existing data
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch data by joining user, status, and department
    $stmt = $conn->prepare("
        SELECT user.*, status.status_name, status.status_id, department.department_name, department.department_id
        FROM user 
        LEFT JOIN status ON user.status_id = status.status_id 
        LEFT JOIN department ON status.department_id = department.department_id
        WHERE user.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("Error: User not found.");
    }
}

// Fetch available departments
$department_query = $conn->query("SELECT department_id, department_name FROM department");
$departments = $department_query->fetch_all(MYSQLI_ASSOC);

// Update user data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']) ?: null; // Default to NULL if empty
    $last_name = trim($_POST['last_name']);
    $status_id = intval($_POST['status']);
    $school_id = trim($_POST['school_id']);
    $rfid_signature = intval($_POST['rfid_signature']);
    $logged_in_admin = $_SESSION['login_id']; // Logged-in admin ID

    // Verify status belongs to the department
    $department_id = intval($_POST['department']);
    $status_check = $conn->prepare("SELECT status_id FROM status WHERE status_id = ? AND department_id = ?");
    $status_check->bind_param("ii", $status_id, $department_id);
    $status_check->execute();
    if ($status_check->get_result()->num_rows === 0) {
        die("Error: Invalid status for the selected department.");
    }
    $status_check->close();

    // Handle image upload if a new image is provided
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "ProfilePic/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            die("Error: File is not an image.");
        }
        if ($_FILES["image"]["size"] > 5000000) {
            die("Error: File size exceeds 5MB.");
        }
        if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            die("Error: Invalid image format.");
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            die("Error: Unable to upload the image.");
        }

        // Delete the old image if it exists
        if (!empty($user['image']) && file_exists($user['image'])) {
            unlink($user['image']);
        }

        $image = $target_file;
    } else {
        $image = $user['image'];
    }

    // Prepare the update query
    if ($image) {
        $query = "UPDATE user SET first_name = ?, middle_name = ?, last_name = ?, status_id = ?, school_id = ?, rfid_signature = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $first_name, $middle_name, $last_name, $status_id, $school_id, $rfid_signature, $image, $id);
    } else {
        $query = "UPDATE user SET first_name = ?, middle_name = ?, last_name = ?, status_id = ?, school_id = ?, rfid_signature = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $status_id, $school_id, $rfid_signature, $id);
    }

    // Execute the query and check for errors
    if ($stmt->execute()) {
        // Log the activity
        $description = "Edit user: {$first_name} {$last_name} (ID: {$id}, School ID: {$school_id})";
        logAdminActivity($logged_in_admin, "edit_user.php", "Edit", $description);

        // Redirect to dashboard on success
        header("Location: dashboard.php?success=user_updated");
        exit();
    } else {
        error_log("Error updating user: " . $stmt->error);
        die("Error updating user. Check logs for details.");
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <script src="./js/popper.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="./css/bootstrap.css" >
    <script src="./js/bootstrap.js"></script>
    <script src="./js/jquery-3.7.1.min.js"></script>
</head>
<style>
    /* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
<body>

<nav class="navbar navbar-expand-lg navbar-light p-2" style="background-color: #343a40;">
    <a href="dashboard.php" class="btn btn-info">Back</a>
</nav>

<div class="container my-5">
    <h2>Edit User</h2>
    <form action="edit_user.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
        
        <div class="mb-3">
            <label for="school_id" class="form-label">School ID</label>
            <input type="text" class="form-control" id="school_id" name="school_id" value="<?php echo htmlspecialchars($user['school_id']); ?>" autocomplete="off" required>
        </div>
        
        <div class="mb-3">
            <label for="rfid_signature" class="form-label">RFID Signature</label>
            <input type="number" class="form-control" id="rfid_signature" name="rfid_signature" value="<?php echo htmlspecialchars($user['rfid_signature']); ?>" autocomplete="off" required readonly>
        </div>
        <div class="row">
            <div class="col">
                <div class="form-outline">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
            </div>
            <div class="col">
                <div class="form-outline">
                    <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                </div>
            </div>
            <div class="col">
                <div class="form-outline">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label for="department" class="form-label">Department</label>
            <select class="form-control" id="department" name="department" required>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo $department['department_id']; ?>" <?php if ($user['department_id'] == $department['department_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-control" id="status" name="status" required>
                <option value="">Select Status</option>
                <?php
                // Preload statuses for the current department when editing
                $status_query = $conn->prepare("SELECT status_id, status_name FROM status WHERE department_id = ?");
                $status_query->bind_param("i", $user['department_id']);
                $status_query->execute();
                $status_result = $status_query->get_result();
                while ($status = $status_result->fetch_assoc()) {
                    $selected = ($user['status_id'] == $status['status_id']) ? 'selected' : '';
                    echo "<option value='{$status['status_id']}' $selected>{$status['status_name']}</option>";
                }
                $status_query->close();
                ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="image" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#department').change(function() {
        const department_id = $(this).val();
        if (department_id) {
            $.post("fetch_statuses_edit.php", { department_id }, function(data) {
                $('#status').html(data);
            });
        }
    });
});

</script>

</body>
</html>
