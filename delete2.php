<?php
include "connection.php"; // Ensure database connection

if (isset($_GET['id']) || isset($_POST['school_id'])) {
    $school_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : $conn->real_escape_string($_POST['school_id']);

    // Log the received school_id
    error_log("Attempting to delete user with school_id: $school_id");

    // Retrieve the user's image file path
    $get_image_query = "SELECT image FROM user WHERE school_id = '$school_id'";
    $result = $conn->query($get_image_query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image'];

        // Delete the image file from the server
        $full_image_path = __DIR__ . '/' . $image_path;
        if (file_exists($full_image_path)) {
            if (!unlink($full_image_path)) {
                error_log("Failed to delete image file: $full_image_path");
            }
        } else {
            error_log("Image file does not exist: $full_image_path");
        }
    } else {
        error_log("No user found with school_id: $school_id");
    }

    // Delete user data
    $delete_user_query = "DELETE FROM user WHERE school_id = '$school_id'";
    if ($conn->query($delete_user_query) === TRUE) {
        // Delete user logs
        $delete_logs_entry = "DELETE FROM log_entry WHERE school_id = '$school_id'";
        $delete_logs_exit = "DELETE FROM log_exit WHERE school_id = '$school_id'";
        if ($conn->query($delete_logs_entry) === TRUE && $conn->query($delete_logs_exit) === TRUE) {
            // Success
            echo "<script>
                    alert('User and logs deleted successfully!');
                    window.location.href = 'dashboard.php';
                  </script>";
        } else {
            error_log("Error deleting logs: " . $conn->error);
        }
    } else {
        error_log("Error deleting user: " . $conn->error);
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
    error_log("Invalid request to delete.php. No ID provided.");
}
?>
