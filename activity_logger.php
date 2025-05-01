<?php
include "connection.php"; // Include database connection

/**
 * Logs admin activity into the admin_log table.
 * 
 * @param int $login_id - The ID of the logged-in admin.
 * @param string $page - The page or endpoint accessed.
 * @param string $action_type - The type of action performed (e.g., VIEW, CREATE, UPDATE, DELETE).
 * @param string|null $description - Additional details about the activity (e.g., "Viewed User Profile of John Doe").
 * 
 * @return void
 */
function logAdminActivity($login_id, $page, $action_type, $description = null) {
    global $conn; // Use the global connection from connection.php

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Ensure log_activity has a default value
    $description = $description ?? '';

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO admin_log (login_id, page, action_type, log_activity) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Failed to prepare SQL statement: " . $conn->error);
    }

    // Bind parameters and execute the statement
    $stmt->bind_param("isss", $login_id, $page, $action_type, $description);
    if (!$stmt->execute()) {
        die("Failed to execute SQL statement: " . $stmt->error);
    }

    $stmt->close();
}

