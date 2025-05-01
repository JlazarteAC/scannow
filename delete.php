<?php
session_start();
include "connection.php";
include "activity_logger.php"; // Include activity logger

if (isset($_GET['id']) || isset($_POST['school_id'])) {
    $school_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : $conn->real_escape_string($_POST['school_id']);
    
    // Retrieve the user's image file path and user details
    $get_user_query = "SELECT first_name, last_name, image FROM user WHERE school_id = '$school_id'";
    $result = $conn->query($get_user_query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image'];
        $user_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); // User's name

        // Delete the image file from the server
        $full_image_path = __DIR__ . '/' . $image_path;
        if (file_exists($full_image_path)) {
            if (!unlink($full_image_path)) {
                echo "Failed to delete image file: $full_image_path.<br>";
            }
        }

        // Delete user data
        $delete_user_query = "DELETE FROM user WHERE school_id = '$school_id'";
        if ($conn->query($delete_user_query) === TRUE) {
            // Delete user logs
            $delete_logs_entry = "DELETE FROM log_entry WHERE school_id = '$school_id'";
            $delete_logs_exit = "DELETE FROM log_exit WHERE school_id = '$school_id'";
            if ($conn->query($delete_logs_entry) === TRUE && $conn->query($delete_logs_exit) === TRUE) {
                // Log the deletion action
                if (isset($_SESSION['login_id'])) {
                    $admin_id = $_SESSION['login_id'];
                    $description = "Deleted User Profile: $user_name (School ID: $school_id)";
                    logAdminActivity($admin_id, "delete.php", "DELETE", $description);
                }

                // Success: Redirect with an alert
                echo "<script>
                        alert('User and logs deleted successfully!');
                        window.location.href = 'dashboard.php';
                      </script>";
            } else {
                echo "Error deleting logs: " . $conn->error . "<br>";
            }
        } else {
            echo "Error deleting user: " . $conn->error . "<br>";
        }
    } else {
        echo "No user found with school_id: $school_id.<br>";
    }
} else {
    http_response_code(400);
    echo "Invalid request.";
}
?>
